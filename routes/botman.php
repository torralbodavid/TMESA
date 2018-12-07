<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('/start', function ($bot) {
    $bot->reply('Hola!');
});

$botman->hears('/conversation', BotManController::class.'@startConversation');
$botman->hears('/linies', 'App\Http\Controllers\TMESAInfoController@mostraTempsRecorregut');
