<?php

namespace Hippo\Parser\Swap;

class SwapLinkProcessor
{
    private array $swapLinkParts;
    private string $link;
    private static int $howManyHits = 0;

    function getLink(): string {
        return $this->link;
    }
    function howDoWeProceed($conn): string {
        $sql = "SELECT * FROM cars_vins_discounts WHERE swapalease_sale_id = '{$this->swapLinkParts[1]}'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->fetchAll();
        if (count($res) != 0) {
            self::$howManyHits ++;
            if (self::$howManyHits <= 3)
                return "Skip"; // do not write to db, but proceed
            else
                return "Exit"; // we reached the head, exit

        } else return "Write"; // write to db and proceed
    }

    function getPrefix(): string {
        return $this->swapLinkParts[0];
    }

    function getSaleId(): string {
        return $this->swapLinkParts[1];
    }

    function __construct(string $link) {
        $this->swapLinkParts = preg_split("/=/", str_replace("/lease/details/",
            "", $link));
        $this->link = $link;
    }

}
