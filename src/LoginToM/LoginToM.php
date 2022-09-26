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

    function firstString($ch) {

        curl_setopt($ch, CURLOPT_URL, "https://members.{$this->c['word1']}.com/gateway/login");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders());

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);

        curl_exec($ch);

        return (curl_getinfo($ch)['redirect_url']);
    }

    function secondString($ch, $url): bool|string {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        return curl_exec($ch);
    }

    function thirdString($ch, $url) {

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders());

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

        curl_setopt($ch, CURLOPT_REFERER, "https://api.{$this->c['word1']}.com/");

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            http_build_query($arrayToUpload));

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(['Cache-Control: max-age=0']));

        curl_exec($ch);

        return (curl_getinfo($ch)['redirect_url']);
    }

    #[Pure] private function makeLoginPage(bool|string $loginPageHTML): string {
        $cut = $this->singleInBetween($loginPageHTML, '/as/', '/resume/');
        return "https://api.{$this->c['word1']}.com/as/$cut/resume/as/authorization.ping";
    }

    function fourthString($ch, $url) {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getChromeHeaders(['Cache-Control: max-age=0']));
        curl_setopt($ch, CURLOPT_REFERER, "https://api.{$this->c['word1']}.com/");

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_exec($ch);
        return (curl_getinfo($ch)['redirect_url']);
    }

//    function fourthString($ch, $url) {
//        $url = "https://gapiprod.awsmlogic.{$this->c['word1']}.com/oauth/refresh";
//        curl_setopt($ch, CURLOPT_URL, $url);
//        $res = curl_exec($ch);
//
//        $fp = fopen('tokens.json', 'w');
//        fwrite($fp, $res);
//        fclose($fp);
//    }

    function __construct() {
        file_put_contents("cookies.txt", "");

        $this->c = parse_ini_file("../../config.ini");

        $ch = $this->initCurl();

        $location = $this->firstString($ch);
        $loginPageHTML = $this->secondString($ch, $location);
        $loginPage = $this->makeLoginPage($loginPageHTML);
        $location2 = $this->thirdString($ch, $loginPage);
        $this->fourthString($ch, $location2);

        curl_close($ch);

        echo "Tokens saved", PHP_EOL;
    }

}

new LoginToM();
