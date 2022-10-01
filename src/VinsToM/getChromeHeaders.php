<?php

namespace Parser\VinsToM;

function getChromeHeaders(string $Bearer): array {
    return [
        'Authority: gapiprod.awsmlogic.manheim.com',
        'Sec-Ch-Ua: ^^Google',
        'Sec-Ch-Ua-Mobile: ?0',
        'Authorization: ' . $Bearer,
        'X-Velocity-Tracer: stnemtsov_212542_oauth',
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.54 Safari/537.36',
        'X-Mmr-Version: sha: 4e2611be022916537768b7ecd88ac5d91eb270d4, type: application',
        'Sec-Ch-Ua-Platform: ^^Windows^^\"\"',
        'Accept: */*',
        'Origin: https://mmr.manheim.com',
        'Sec-Fetch-Site: same-site',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Dest: empty',
        'Referer: https://mmr.manheim.com/',
        'Accept-Language: en-US,en;q=0.9,ru;q=0.8,uk;q=0.7'];
}
