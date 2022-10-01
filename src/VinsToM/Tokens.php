<?php

namespace Hippo\Parser\VinsToM;

class Tokens
{
    private string $Bearer_string;
    private string $bearer_token;

    function Bearer(): string {
        return $this->Bearer_string;
    }

    function bearer_token(): string {
        return $this->bearer_token;
    }

    function __construct()  {
        $res = file_get_contents('tokens.json');
        $json = json_decode($res);
        $this->Bearer_string = "Bearer " . $json->jwtToken;
        $this->bearer_token = $json->accessToken;
    }
}
