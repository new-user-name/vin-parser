<?php

declare(strict_types=1);

require '../../vendor/autoload.php';

use Hippo\Parser\Swap\ParseCarPage;
use Hippo\Parser\Swap\SwapLinkProcessor;
use Hippo\Parser\VinsToM\M;

const PAGES_TO_PARSE = 1;

// the mail address has to be set in two separate places:
// here and in the parser.cron
const MAIL_ADDRESS = "jeff.bishop275@gmail.com";

chdir(dirname(__FILE__));
error_reporting(E_ERROR);


function getGoodCarLinksFromPage(string $url): array {

    $html = new DOMDocument();

    $html->loadHTMLFile($url);

    $links = array();

    $xpathObject = new DOMXPath($html);

    $wrappedCarDivs = $xpathObject->query("//div[@class='image-inner']");

    foreach ($wrappedCarDivs as $div) {
        $test = $div->childNodes->length;
        if ($test == 3) // tags "sold" and "pending" add 2 nodes, so the $test is 5, we want 3.
            $links[] = $div->childNodes->item(1)->getAttribute("href");
    }

    return $links;
}

function getPageAddressByNumber(int $pageNumber, string $secretWord): string {
    if ($pageNumber == 1)
        return "https://www.$secretWord.com/lease/search.aspx?so=5&ipg=60";
    else
        return "https://www.$secretWord.com/lease/search.aspx?page=$pageNumber&so=5&ipg=60";
}

function goodNews() {
    static $mail_sent_already = false;

    if (!$mail_sent_already) {
        $to = MAIL_ADDRESS;
        $subject = 'New cars on Mnhm';
        $message = 'New cars have been listed on Mnhm, please check your database';
        $headers = 'From: webmaster@example.com' . "\r\n" .
            'Reply-To: webmaster@example.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        mail($to, $subject, $message, $headers);
        $mail_sent_already = true;
    }
}


date_default_timezone_set('Europe/Kiev');

$today = date("F j, Y, g:i a");

$mnhm = null; // Mnhm object
$conn = null; //Database

$db = parse_ini_file("../Utils/db.ini");
$c = parse_ini_file("../../config.ini");

$conn = new PDO("mysql:host={$db['servername']};dbname={$db['dbname']}", $db['username'], $db['password']);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

WriteInitialNumberOfCarsAndClearLog($conn, $today);

$singleArrayOfCars = array();

for ($pageNumber = 1; $pageNumber <= PAGES_TO_PARSE; $pageNumber++) {
// Pages start from 1, not 0.
    echo "Parsing root page $pageNumber", PHP_EOL;
    $pageAddress = getPageAddressByNumber($pageNumber, $c['word2']);

// go to the page with multiple cars
    $goodCarLinksFromPage = getGoodCarLinksFromPage($pageAddress);
    $numberOfLinksOnCurrentPage = count($goodCarLinksFromPage);

    // first link is one to avoid sponsored car
    for ($linkNumber = 1; $linkNumber < $numberOfLinksOnCurrentPage; $linkNumber++) {

        $swapLinkProcessor = new SwapLinkProcessor($goodCarLinksFromPage[$linkNumber]);
        $howDoWeProceed = $swapLinkProcessor->howDoWeProceed($conn);

        if ($howDoWeProceed == "Write")
            array_push($singleArrayOfCars, $swapLinkProcessor);
        elseif ($howDoWeProceed == "Skip")
            echo "The car is in database already", PHP_EOL;
        else { // Exit
            echo "No new cars for now", PHP_EOL;
            break 2;
        }
    }
}

for ($linkNumber = count($singleArrayOfCars) - 1; $linkNumber >= 0; $linkNumber--) {

// go to the page of a single car
    //   $parsedPage = new ParseCarPage("/lease/details/2022-Land-Rover-Defender.aspx?salid=1573514");
    $parsedPage = new ParseCarPage($singleArrayOfCars[$linkNumber]->getLink(), $c['word2']);
    $mileage = $parsedPage->getMileage();

    if ($mileage != "0") { // we don't need cars with zero mileage

        // leave as it is for debugging purposes
        $vin = $parsedPage->getVIN();
        $car_name = $parsedPage->getCarName();
        $location = $parsedPage->getLocation();
        $story = $parsedPage->getStory();
        $optLeaseEndBuyout = $parsedPage->getBottomValue("Opt. Lease End Buyout");
        $currentBuyout = $parsedPage->getBottomValue("Current Buyout");
        $purchasePrice = $parsedPage->getBottomValue("Purchase Price");
        $pictures = $parsedPage->getPictures();

        $adjustedMMR = "No data";
        $linkToMMR = "No data";

        if ($vin != "no VIN") {
            $mnhm = new M($vin);
            $adjustedMMR = $mnhm->getAdjustedMMR($mileage);
            $linkToMMR = $mnhm->getLinkToMMR();
        }

        try {
            $sql = "INSERT INTO cars_vins_discounts (car_name,
                                                         short_info,
                                                         story,
                                                         mileage,
                                                         vin,
                                                         adjusted_mmr,
                                                         opt_lease_end_buyout,
                                                         current_buyout,
                                                         purchase_price,
                                                         swapalease_link_prefix,
                                                         swapalease_sale_id,
                                                         link_mmr,
                                                         pictures,
                                                         date) 
                                VALUES ('$car_name',
                                        '$location',
                                        '$story',
                                        '$mileage',
                                        '$vin',
                                        '$adjustedMMR',
                                        '$optLeaseEndBuyout',
                                        '$currentBuyout',
                                        '$purchasePrice',
                                        '{$singleArrayOfCars[$linkNumber]->getPrefix()}=',
                                        '{$singleArrayOfCars[$linkNumber]->getSaleId()}',
                                        '$linkToMMR',
                                        '$pictures',
                                        '$today')";
            $conn->exec($sql);
            echo "Database updated, link $linkNumber", PHP_EOL;
            goodNews();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    } else
        echo "Zero mileage skipped, link $linkNumber", PHP_EOL;
}

WriteFinalNumberOfCars($conn, $today, $c['word2']);

$conn = null;

if ($mnhm != null)
    $mnhm->releaseCh();




