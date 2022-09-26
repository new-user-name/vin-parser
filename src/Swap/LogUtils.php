<?php

function GetNumberOfRows($conn): string {
    $sql = "SELECT * FROM cars_vins_discounts";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return count($stmt->fetchAll());
}

function WriteInitialNumberOfCarsAndClearLog($conn, $today){
    $numberOfRows = GetNumberOfRows($conn);
    file_put_contents("parser_log.txt", "Started with ". $numberOfRows. " records, ". $today. PHP_EOL, FILE_APPEND);
}

function WriteFinalNumberOfCars($conn, $today){
    $numberOfRows = GetNumberOfRows($conn);
    $file = "/home/Parser/{$this->c['Word2']}/parser_log.txt";
    file_put_contents("parser_log.txt", "Stopped with ". $numberOfRows. " records, ". $today. PHP_EOL. PHP_EOL, FILE_APPEND);
}
