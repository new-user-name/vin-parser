<?php

namespace Hippo\Parser\Utils;

trait Utils {
    function singleInBetween(string $source, string $left, string $right): string {
        $source = ' ' . $source;
        $left_pos = strpos($source, $left);
        if ($left_pos == 0) return '';
        $left_pos += strlen($left);
        $len = strpos($source, $right, $left_pos) - $left_pos;
        return substr($source, $left_pos, $len);
    }
}

//CREATE TABLE `leaselink`.`cars_vins_discounts` ( `car_name` TEXT NOT NULL , `short_info` TEXT NOT NULL , `story` TEXT NOT NULL , `mileage` TEXT NOT NULL , `vin` TEXT NOT NULL , `adjusted_mmr` TEXT NOT NULL , `opt_lease_end_buyout` TEXT NOT NULL , `current_buyout` TEXT NOT NULL , `purchase_price` TEXT NOT NULL , `swapalease_link_prefix` TEXT NOT NULL , `swapalease_sale_id` TEXT NOT NULL , `link_mmr` TEXT NOT NULL , `pictures` TEXT NOT NULL , `date` TEXT NOT NULL ) ENGINE = InnoDB;
