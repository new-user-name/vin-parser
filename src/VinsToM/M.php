<?php

namespace Hippo\Parser\VinsToM;


use Hippo\Parser\LoginToM\LoginToM;
use Hippo\Parser\Utils\InitCurl;
use JetBrains\PhpStorm\Pure;

use function Hippo\Parser\Utils\singleInBetween;
use function Parser\VinsToM\getChromeHeaders;

class M {

    use InitCurl;
    private $ch, $vin, $href, $mileage, $tokens;

    private function initialUpdateCurl() {
        curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, getChromeHeaders($this->tokens->Bearer()));
    }

    private function updateCurl($href) {
        $innerJsonArray = [["bearer_token" => $this->tokens->bearer_token(), "href" => $href]];
        $json = json_encode(["requests" => $innerJsonArray]);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $json);
    }

    function releaseCh() {
        curl_close($this->ch);
    }

    #[Pure] function getLinkToMMR(): string {
        if ($this->href == "no VIN in MMR")
            return "No data";
        else {
            $vehicleID = singleInBetween($this->href, "/id/", "?country");
            return "$vehicleID&vin=$this->vin&mileage=$this->mileage";
//        https://mmr.manheim.com/?country=US&mid=202003603430149&vin=WDD1J6JB3LF119690&mileage=6666
        }
    }

    function getAdjustedMMR($mileage): string {
        if ($this->href == "no VIN in MMR")
            return "No data";
        else {
            $this->mileage = $mileage;
            $updatedHref = str_replace("odometer=0&", "odometer=" . $mileage . "&", $this->href);
            $this->updateCurl($updatedHref);

            $res = "";
            $result = curl_exec($this->ch);
            $href = json_decode($result);
            $res = $href->responses[0]->body->items[0]->adjustedPricing->wholesale->average;
            if ($res == null) return "No data";

            return $res;
        }
    }

    function __construct(string $vin) {
        //$vin = "SALWS2RU0NA202028"; example of a wrong vin
        // 7SAYGAEEXNF363538 good vin
        $this->vin = $vin;

        if ($this->ch == null) {
            $this->ch = $this->initCurl();
            $this->tokens = new Tokens();
            $this->initialUpdateCurl();
        }

        curl_setopt($this->ch, CURLOPT_URL, 'https://gapiprod.awsmlogic.manheim.com/gateway');
        //$href = "https://api.manheim.com/listings/vin/$vin?fields=id%2Cmid%2Cvin%2CpickupLocation%2CpickupRegion%2CpickupLocationCountry%2Cchannels%2Ccurrency%2Cstatuses%2CconditionGradeNumeric%2Codometer%2CodometerUnits%2Ctrims%2CexteriorColor%2CyearId%2CmakeId%2CmodelIds%2Cyear%2Cmake%2Cmodels%2ChasAutocheck%2CautocheckCsHash%2CisEligibleForCarfax%2CcarfaxCsHash%2CsellerNumber%2CmmrPrice%2CbidPrice%2CisAutoGradeOrManheimGrade&include=true";
        $href="https://api.manheim.com/valuations/vin/$vin?country=US&include=retail,historical,forecast";
        $this->updateCurl($href);

        $tokenIsGood = null;
        do {
            $result = curl_exec($this->ch);
            if (str_contains($result, "listing not found")) {
                $this->href = "no VIN in MMR";
            } else {
                $buff = json_decode($result);
                $this->href = $buff->responses[0]->body->items[0]->href;
            }
            if ($this->href == null) {
                $tokenIsGood = False;
                echo "New login to Manheim", PHP_EOL;
                new LoginToM();
                $this->tokens = new Tokens();
                $this->initialUpdateCurl();
            } else
                $tokenIsGood = True;
        } while (!$tokenIsGood);

    }
}

