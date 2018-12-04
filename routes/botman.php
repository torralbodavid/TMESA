<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('/start', function ($bot) {
    $bot->reply('Hola!');
});

$botman->hears('Start conversation', BotManController::class.'@startConversation');
