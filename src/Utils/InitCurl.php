<?php

namespace Hippo\Parser\Utils;

trait InitCurl {
    function getChromeHeaders($headersToAdd = []): array {
        $simpleChromeHeaders = [
            "Connection: keep-alive",
            'sec-ch-ua: "Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"',
            "sec-ch-ua-mobile: ?0",
            'sec-ch-ua-platform: "Windows"',
            "Upgrade-Insecure-Requests: 1",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            "Sec-Fetch-Site: none",
            "Sec-Fetch-Mode: navigate",
            "Sec-Fetch-User: ?1",
            "Sec-Fetch-Dest: document",
            "Accept-Language: en-US,en;q=0.9",
            "Accept-Encoding: gzip, deflate, br"
        ];

        foreach ($headersToAdd as $headerToAdd)
            array_push($simpleChromeHeaders, $headerToAdd);

        return $simpleChromeHeaders;
    }

    function initCurl(): \CurlHandle|bool {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8888');
        return $ch;
    }

}
