<?php


class ParseCarPage
{
    private DOMXPath $xpathObject;

    function getVIN(): string
    {
        $maybeDirtyVIN = $this->xpathObject->query("//div[@id='lblVIN']/text()")->item(0);
        if ($maybeDirtyVIN == null)
            return "no VIN";
        else {
            $dirtyVIN = $maybeDirtyVIN->nodeValue;
            return preg_replace('/[^A-Za-z0-9]/', '', $dirtyVIN); // let it be regular expression, just my quirk
        }
    }

    function getCarName(): string
    {
        $dirtyName = $this->xpathObject->query("//div[@id='title-wrap']//h1/text()")->item(0)->nodeValue;
        return trim($dirtyName, " \n\r\\");
    }

    function getLocation(): string {
        $dirtyLocation = $this->xpathObject->query("//div[text() = 'Location']/following-sibling::div")->item(0)->nodeValue;
        return trim($dirtyLocation, " \n\r\\");
    }

    function getMileage(): string {
        $dirtyMileage = $this->xpathObject->query("//div[text() = 'Current Miles']/following-sibling::div")->item(0)->nodeValue;
        $withComma = trim($dirtyMileage, " \n\r\\");
        return str_replace(",", "", $withComma);
    }

    function getBottomValue(string $signature): string {
        $dirtyValueDiv = $this->xpathObject->query("//div[text() = '$signature']/following-sibling::div");
        if (count($dirtyValueDiv) == 0)
            return "No data";
        else {
            $dirtyValue = $dirtyValueDiv->item(0)->nodeValue;
            return trim($dirtyValue, " \n\r\\");
        }
    }

    function getStory(): string {
        $dirtyStory = $this->xpathObject->query("//div[@id = 'comments']")->item(0)->nodeValue;
        $cleanWithQuotes = trim($dirtyStory, " \n\r\\");
        return str_replace("'", "''", $cleanWithQuotes);
    }

    function getPictures(): string
    {
        $pictures = "";
        $picturesArray = $this->xpathObject->query("//img[contains(@src, '/vehiclephotos/')]/@src");
        foreach ($picturesArray as $picture)
            $pictures = $pictures. "\n". str_replace("/vehiclephotos/", "", $picture->nodeValue);
        return $pictures;
    }

    function __construct($carUrl)
    {
        $html = new DOMDocument();
        $html->loadHTMLFile("https://www.{$this->c['word2']}.com" . $carUrl);
        $this->xpathObject = new DOMXPath($html);
    }
}
