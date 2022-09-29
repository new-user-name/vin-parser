<?php

declare(strict_types=1);

namespace Hippo\Parser\LoginToM;
require_once('../../vendor/autoload.php');

use Hippo\Parser\Utils\InitCurl;
use Hippo\Parser\Utils\Utils;
use JetBrains\PhpStorm\Pure;

class LoginToM {
    use InitCurl;
    use Utils;

    private array|false $c;
    private bool|\CurlHandle $ch;

    function firstString() {
        //curl_setopt($this->ch, CURLOPT_URL, "https://mmr.{$this->c['word1']}.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man");
        //https://mmr.manheim.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man
        curl_setopt($this->ch, CURLOPT_URL, "https://members.{$this->c['word1']}.com/gateway/login");
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders());

        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($this->ch, CURLOPT_COOKIEJAR, 'cookies.txt');
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, 'cookies.txt');
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);

        curl_exec($this->ch);

        return (curl_getinfo($this->ch)['redirect_url']);
    }

    function secondString($url): bool|string {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        return curl_exec($this->ch);
    }

    function thirdString($url) {

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
            "brand_href" => "false",
            "brand_name" => "{$this->c['word1']}",
            "signup" => "{$this->c['word1']}",
            "reset_pw_mode" => "forgot",
            "exit_target_url" => "https://www.{$this->c['word1']}.com"
        ];

        curl_setopt($this->ch, CURLOPT_REFERER, "https://mmr.{$this->c['word1']}.com/");

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS,
            http_build_query($arrayToUpload));

        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(['Cache-Control: max-age=0']));

        curl_exec($this->ch);

        return (curl_getinfo($this->ch)['redirect_url']);
    }

    #[Pure] private function makeLoginPage(bool|string $loginPageHTML): string {
        $cut = $this->singleInBetween($loginPageHTML, '/as/', '/resume/');
        return "https://api.{$this->c['word1']}.com/as/$cut/resume/as/authorization.ping";
    }

    function fourthString($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(['Cache-Control: max-age=0']));
        curl_setopt($this->ch, CURLOPT_REFERER, "https://api.{$this->c['word1']}.com/");

        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_exec($this->ch);
        return (curl_getinfo($this->ch)['redirect_url']);
    }

    function beforeMMR() {
        $url = "https://gapiprod.awsmlogic.{$this->c['word1']}.com/oauth/refresh";
//https://mmr.manheim.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_REFERER, "https://mmr.manheim.com/");
//        curl_setopt($this->ch, CURLOPT_POSTFIELDS,false);
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(
            [
                'X-MMR-REFERER: https://mmr.manheim.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man',
                'Origin: https://mmr.manheim.com',
                'Host: gapiprod.awsmlogic.manheim.com'
            ]));
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 1);
        $res = curl_exec($this->ch);

        $fp = fopen('tokens.json', 'w');
        fwrite($fp, $res);
        fclose($fp);
    }

    function __construct() {
        file_put_contents("cookies.txt", "");

        $this->c = parse_ini_file("../../config.ini");

        $this->ch = $this->initCurl();

        $location = $this->firstString();
        $loginPageHTML = $this->secondString($location);
        $loginPage = $this->makeLoginPage($loginPageHTML);
        $location2 = $this->thirdString($loginPage); //going to https://api.mmm.com/as/{NE6bp}/resume/as/authorization.ping
        $this->fourthString($location2); // after this we are on the main page, nick is visible in page source

        //https://mmr.manheim.com/?WT.svl=m_uni_hdr_buy&country=US&popup=true&source=man

        $this->beforeMMR();

        curl_close($this->ch);

        echo "Tokens saved", PHP_EOL;
    }

}

new LoginToM();
