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
    protected $tmesa;

    /**
     * TempsRecorregutConversation constructor.
     */
    public function __construct()
    {
        $this->horari = new Horari();
        $this->tmesa = new tmesa();
    }


    /**
     * Pregunta la línia i el sentit que desitja consultar.
     */
    public function consultaLinia()
    {
        $linies = array();

        $infoLinies = $this->tmesa->infoLinies();

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
                array_search($answer->getValue(), $this->tmesa->infoLinies());

                $valors = explode("-", $answer->getValue());
                $this->paradaOrigen($valors[0], $valors[1]);
            } else {
                $this->consultaLinia();
            }
        });
    }

    public function paradaOrigen($linia, $sentit){
        $origen = array();

        $infoLinies = $this->tmesa->retornaOrigen($linia, $sentit);

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

        $infoLinies = $this->tmesa->retornaDesti($calcul, $linia, $sentit);

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

        $infoLinies = $this->tmesa->retornaJornada($parada, $linia, $sentit);

        foreach ($infoLinies as $key=>$value){
            if($value['nom'] != ""){
                array_push($origen, Button::create($value['nom'])->value($value['id']));
            } else {
                array_push($origen, Button::create("Bus Nit")->value($value['id']));
            }
        }

        $question = Question::create("🗓 Selecciona la jornada")
            ->fallback('No he pogut trobar cap jornada')
            ->callbackId('consulta_jornada')
            ->addButtons($origen);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {
                $this->horari->setJornada($answer->getValue());
                $this->_horari();
            }
        });
    }

    private function _horari(){

        $this->say("Estem carregant els horaris. Veurà marcat amb un ➡️ l'horari del següent bus.");

        $horaris = $this->tmesa->retornaHorari($this->horari->getJornada(), $this->horari->getParadaDe(), $this->horari->getParadaOr(), $this->horari->getSentit());

        $resposta = "";

        foreach ($horaris as $key=>$value) {

            $resposta .= $horaris[$key]['seguent']." ".$horaris[$key]['temps']."🚏Arriba a l'estació a les ".$horaris[$key]['anada']. "\n". "⌛️ Temps estimat de viatje: ". $horaris[$key]['minuts']. " minuts. (".$horaris[$key]['tornada'].")\n\n";

            if($key==40){
                $this->say($resposta);
                $resposta = "";
            };
        }

        return $this->say($resposta);

    }

    /**
     * Comença a preguntar
     */
    public function run()
    {
        $this->say("👋 Benvinguda! A continuació podrà saber el temps que tardarà el seu bus en arribar i quan tardarà en arribar a la seva destinació.");
        $this->consultaLinia();
    }
}
