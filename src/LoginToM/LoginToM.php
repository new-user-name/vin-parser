<?php

declare(strict_types=1);

namespace Hippo\Parser\LoginToM;

use Hippo\Parser\Utils\InitCurl;
use JetBrains\PhpStorm\Pure;
use function Hippo\Parser\Utils\singleInBetween;

class LoginToM {
    use InitCurl;

    private array|false $c;
    private bool|\CurlHandle $ch;

    function firstString() {
        $url = "https://gapiprod.awsmlogic.manheim.com/oauth/login";
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders());
        curl_setopt($this->ch, CURLOPT_REFERER, "https://mmr.manheim.com/");
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, 'cookies.txt');
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, 'cookies.txt');
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 1);

        return curl_exec($this->ch);

    }

    function secondString($url) {

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders());

        $arrayToUpload = [
            "pf.username" => $this->c["user"],
            "pf.pass" => $this->c["pass"],
            "ok" => "clicked",
            "pf.passwordreset" => "",
            "pf.usernamerecovery" => "",
            "pf.adapterId" => "{$this->c['Word1']}DirectoryFA",
            "brand_logo" => "assets/images/{$this->c['word1']}Logo.svg",
            "brand_href" => "https://www.manheim.com",
            "brand_name" => "{$this->c['word1']}",
            "signup" => "{$this->c['word1']}",
            "reset_pw_mode" => "forgot",
            "exit_target_url" => "https://www.{$this->c['word1']}.com"
        ];

        curl_setopt($this->ch, CURLOPT_REFERER, "https://api.{$this->c['word1']}.com/");

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS,
            http_build_query($arrayToUpload));

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(['Cache-Control: max-age=0']));

        curl_exec($this->ch);

        return (curl_getinfo($this->ch)['redirect_url']);
    }

    #[Pure] private function makeLoginPage(bool|string $loginPageHTML): string {
        $cut = singleInBetween($loginPageHTML, '/as/', '/resume/');
        return "https://api.{$this->c['word1']}.com/as/$cut/resume/as/authorization.ping";
    }

    function thirdString($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);

        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(['Cache-Control: max-age=0']));
        curl_setopt($this->ch, CURLOPT_REFERER, "https://api.{$this->c['word1']}.com/");

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);

        curl_exec($this->ch);
        return (curl_getinfo($this->ch)['redirect_url']);
    }

    function beforeMMR() {
        $url = "https://gapiprod.awsmlogic.{$this->c['word1']}.com/oauth/refresh";
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_REFERER, "https://mmr.manheim.com/");
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(
            [
                'X-MMR-REFERER: https://mmr.manheim.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man',
                'Origin: https://mmr.manheim.com',
                'Host: gapiprod.awsmlogic.manheim.com'
            ]));
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);
        $res = curl_exec($this->ch);

        $fp = fopen('tokens.json', 'w');
        fwrite($fp, $res);
        fclose($fp);
    }

    function __construct() {
        file_put_contents("cookies.txt", "");

        $this->c = parse_ini_file("../../config.ini");

        $this->ch = $this->initCurl();

        $pageWithLoginFormHTML = $this->firstString();
        $nextURL = $this->makeLoginPage($pageWithLoginFormHTML);
        $location2 = $this->secondString($nextURL); //going to https://api.mmm.com/as/{NE6bp}/resume/as/authorization.ping
        $this->thirdString($location2); // after this we are on the main page, nick is visible in page source

        //https://mmr.manheim.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man

        $this->beforeMMR();

        curl_close($this->ch);

        echo "Tokens saved", PHP_EOL;
    }
}

//new LoginToM();
