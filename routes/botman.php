<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('/start', function ($bot) {
    $bot->reply('Hola!');
});

$botman->hears('/conversation', BotManController::class.'@startConversation');
$botman->hears('/linies', 'App\Http\Controllers\TMESAInfoController@mostraTempsRecorregut');

$botman->fallback(function ($bot) {
    $bot->reply("Em sap greu però no ho he entès... 😱 \n \n Utilitza /linies per a saber quan passarà el teu bus i quan tardaras en arribar.");
});