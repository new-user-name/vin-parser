<?php

include 'getChromeHeaders.php';
include 'Tokens.php';
include_once '../Utils/initCurl.php';
include '../LoginToM/LoginToManheim.php';
include '../Utils/utils.php';

use function Parser\VinsToManheim\getChromeHeaders;
use function Hippo\Parser\Utils\initCurl;
use function Hippo\Parser\Utils\singleInBetween;

class M
{
    private $ch, $vin, $href, $mileage, $tokens;

    private function initialUpdateCurl()
    {
        curl_setopt($this->ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, getChromeHeaders($this->tokens->Bearer()));
    }

    private function updateCurl($href)
    {
        $innerJson = array("bearer_token" => $this->tokens->bearer_token(), "href" => $href);
        $innerJsonArray = array($innerJson);
        $outerJsonArray = array("requests" => $innerJsonArray);
        $json = json_encode($outerJsonArray);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $json);
    }

    function releaseCh()
    {
        curl_close($this->ch);
    }

    function getLinkToMMR(): string
    {
        if ($this->href == "no VIN in MMR")
            return "No data";
        else {
            $vehicleID = singleInBetween($this->href, "/id/", "?country");
            return "$vehicleID&vin=$this->vin&mileage=$this->mileage";
//        https://mmr.manheim.com/?country=US&mid=202003603430149&vin=WDD1J6JB3LF119690&mileage=6666
        }
    }

    function getAdjustedMMR($mileage): string
    {
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

    function __construct(string $vin)
    {
        //$vin = "SALWS2RU0NA202028"; example of a wrong vin
        $this->vin = $vin;

        if ($this->ch == null) {
            $this->ch = initCurl();
            $this->tokens = new Tokens();
            $this->initialUpdateCurl();
        }

        curl_setopt($this->ch, CURLOPT_URL, 'https://gapiprod.awsmlogic.manheim.com/gateway');
        $href = "https://api.manheim.com/valuations/vin/$vin?country=US&include=retail,historical,forecast&orgId=stnemtsov";
        $this->updateCurl($href);

        $tokenIsGood = null;
        do {
            $result = curl_exec($this->ch);
            if (str_contains($result, "Matching vehicles not found")) {
                $this->href = "no VIN in MMR";
            } else {
                $buff = json_decode($result);
                $this->href = $buff->responses[0]->body->items[0]->href;
            }
            if ($this->href == null) {
                $tokenIsGood = False;
                echo "New login to Manheim", PHP_EOL;
                new LoginToManheim();
                $this->tokens = new Tokens();
                $this->initialUpdateCurl();
            } else
                $tokenIsGood = True;
        } while (!$tokenIsGood);

    }
}

