<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('/start', function ($bot) {
    $bot->reply('Hola '.$bot->getUser()->getFirstName().' 🖐,  pots consultar els propers horaris del bus que vulguis agafar amb la comanda /linies');
});

$botman->hears('/conversation', BotManController::class.'@startConversation');
$botman->hears('/linies', 'App\Http\Controllers\TMESAInfoController@mostraTempsRecorregut');

$botman->fallback(function ($bot) {
    App::setLocale("ca");
    $bot->reply(__('app.fallback'));
});