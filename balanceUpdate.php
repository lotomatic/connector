<?php

require_once "LotomaticConnector.php";

$provider = new LotomaticConnector('c9ebad9d7c46b33e36030fd09c475998', '2d56745df15627f425dcf64d44d0a92f', 'http://game2.epay/provider');

try {
    $demo = $provider->gameInitDemo(5, "http://ya.ru", "ru");

    print "\n\ngameInitDemo result:\n\n";
    print_r($demo);

    $session = $demo["session"];

    $update_result = $provider->balanceUpdate($session, 80000);

    print "\n\nbalanceUpdate result with demo session:\n\n";
    print_r($update_result);

    $game = $provider->gameInit(5, "http://ya.ru", "ru");

    print "\n\ngameInit result:\n\n";
    print_r($game);

    $session = $game["session"];

    $update_result = $provider->balanceUpdate($session, 80000);

    print "\n\nbalanceUpdate result with real session:\n\n";
    print_r($update_result);

    $update_result = $provider->balanceUpdate("wefw3ur34r24242tweqdwfgegw", 80000);

    print "\n\nbalanceUpdate result with unreal session:\n\n";
    print_r($update_result);

} catch (Exception $e) {
    echo 'Exception: ',  $e->getMessage(), "\n";
    exit;
}

