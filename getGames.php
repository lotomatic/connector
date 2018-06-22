<?php

require_once "LotomaticConnector.php";

$provider = new LotomaticConnector('c9ebad9d7c46b33e36030fd09c475998', '2d56745df15627f425dcf64d44d0a92f', 'http://game2.epay/provider');

try {
    $games = $provider->getGames();
} catch (Exception $e) {
    echo 'Exception: ',  $e->getMessage(), "\n";
    exit;
}

print_r($games);
