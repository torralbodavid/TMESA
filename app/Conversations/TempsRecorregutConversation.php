<?php

namespace App\Conversations;

use App\TMESA\Horari;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use App\Http\Controllers\TMESAInfoController as tmesa;

class TempsRecorregutConversation extends Conversation
{
    protected $horari;

    /**
     * TempsRecorregutConversation constructor.
     */
    public function __construct()
    {
        $this->horari = new Horari();
    }


    /**
     * Pregunta la línia i el sentit que desitja consultar.
     */
    public function consultaLinia()
    {
        $linies = array();

        $tmesa = new tmesa();

        $infoLinies = $tmesa->infoLinies();

        foreach ($infoLinies as $key=>$value){
            if($value['value'] != 0) {
                array_push($linies, Button::create($value['nom'])->value($value['value']));
            }
        }

        $question = Question::create("🗺 Quina línia desitja consultar?")
            ->fallback('No he pogut consultar cap línia')
            ->callbackId('consulta_linea')
            ->addButtons($linies);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $valors = explode("-", $answer->getValue());
                $this->paradaOrigen($valors[0], $valors[1]);
            }
        });
    }

    public function paradaOrigen($linia, $sentit){
        $origen = array();

        $tmesa = new tmesa();
        $infoLinies = $tmesa->retornaOrigen($linia, $sentit);

        foreach ($infoLinies as $key=>$value){
            array_push($origen, Button::create($value['nom'])->value($value['id']."-".$linia."-".$sentit));
        }

        $question = Question::create("🚌 A quina parada agafarà el bus?")
            ->fallback('No he pogut trobar cap parada')
            ->callbackId('consulta_origen')
            ->addButtons($origen);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $valors = explode("-", $answer->getValue());
                $this->_paradaDesti($valors[0], $valors[1], $valors[2]);
            }
        });
    }

    private function _paradaDesti($calcul, $linia, $sentit){
        $this->horari->setParadaOr($calcul);
        $this->horari->setSentit($sentit);

        $origen = array();

        $tmesa = new tmesa();
        $infoLinies = $tmesa->retornaDesti($calcul, $linia, $sentit);

        foreach ($infoLinies as $key=>$value){
            array_push($origen, Button::create($value['nom'])->value($value['id']."-".$linia."-".$sentit));
        }

        $question = Question::create("🏁 A quina parada vol arribar?")
            ->fallback('No he pogut trobar cap parada')
            ->callbackId('consulta_desti')
            ->addButtons($origen);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $valors = explode("-", $answer->getValue());
                $this->_jornada($valors[0], $valors[1], $valors[2]);
            }
        });
    }

    private function _jornada($parada, $linia, $sentit){
        $this->horari->setParadaDe($parada);

        $origen = array();

        $tmesa = new tmesa();
        $infoLinies = $tmesa->retornaJornada($parada, $linia, $sentit);

        foreach ($infoLinies as $key=>$value){
            array_push($origen, Button::create($value['nom'])->value($value['id']));
        }

        $question = Question::create("🗓 Selecciona la jornada")
            ->fallback('No he pogut trobar cap jornada')
            ->callbackId('consulta_jornada')
            ->addButtons($origen);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->horari->setJornada($answer->getValue());
                $this->say("el valor ha estat ".$this->horari->getParadaDe());
            }
        });
    }

    /**
     * Comença a preguntar
     */
    public function run()
    {
        $this->consultaLinia();
    }
}
