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

        /*
         * Si no hi ha línies mostrem missatge d'error.
         */
        if($infoLinies == null){
            $this->say("⚠️ No hem pogut trobar línies en aquest moment. Provi-ho de nou en uns minuts. Disculpi les molèsties!");
            exit();
        }

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
                try {
                    $this->paradaOrigen($valors[0], $valors[1]);
                } catch (\Exception $e){
                    $this->say("⚠️ No hem pogut carregar les parades. Si us plau, torni-ho a intentar escrivint /linies");
                }
            }

        });
    }

    public function paradaOrigen($linia, $sentit){
        $origen = array();
        $this->horari->setLinia($linia);
        $this->horari->setSentit($sentit);

        try {
        $infoLinies = $this->tmesa->retornaOrigen($linia, $sentit);
        } catch (\Exception $e){
            $this->say("⚠️ No hem pogut carregar les parades. Si us plau, torni-ho a intentar escrivint /linies");
        }
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

                try {
                $this->_paradaDesti($valors[0], $valors[1], $valors[2]);
                } catch (\Exception $e){
                    $this->say("⚠️ No hem pogut carregar les parades de destí. Si us plau, torni-ho a intentar escrivint /linies");
                }
            }
        });
    }

    private function _paradaDesti($calcul, $linia, $sentit){
        $this->horari->setParadaOr($calcul);

        $origen = array();

        try {
        $infoLinies = $this->tmesa->retornaDesti($calcul, $linia, $sentit);
        } catch (\Exception $e){
            $this->say("⚠️ No hem pogut carregar les parades de destí. Si us plau, torni-ho a intentar escrivint /linies");
        }

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
                try {
                    $this->_jornada($valors[0], $valors[1], $valors[2]);
                } catch (\Exception $e){
                    $this->say("⚠️ No hem pogut carregar les jornades. Si us plau, torni-ho a intentar escrivint /linies");
                }
            }
        });
    }

    private function _jornada($parada, $linia, $sentit){
        $this->horari->setParadaDe($parada);

        $origen = array();

        try {
        $infoLinies = $this->tmesa->retornaJornada($parada, $linia, $sentit);
        } catch (\Exception $e){
            $this->say("⚠️ No hem pogut carregar les jornades. Si us plau, torni-ho a intentar escrivint /linies");
        }

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

                try {
                    $this->_horari(1);
                } catch (\Exception $e){
                    $this->say("⚠️ No hem pogut carregar els horaris. Si us plau, torni-ho a intentar escrivint /linies");
                }
            }
        });
    }

    private function _horari($voltaHorari){

        $this->say("Estem carregant els horaris. Veurà marcat amb un ➡️ l'horari del següent bus.");

        $horaris = $this->tmesa->retornaHorari($this->horari->getJornada(), $this->horari->getParadaDe(), $this->horari->getParadaOr(), $this->horari->getSentit());

        $totsHoraris = "";

        foreach ($horaris as $key=>$value) {

            $hora = explode(":", $horaris[$key]['temps'])[0];
            $minut = explode(":", $horaris[$key]['temps'])[1];

            $totsHoraris .= "🚏El bus arribarà a la teva estació en ".$hora." hores i ".$minut." minuts (A les ".$horaris[$key]['anada'].")\n". "⌛️ Temps estimat de viatje: ". $horaris[$key]['minuts']. " minuts. (Arribarà al teu destí a les ".$horaris[$key]['tornada'].")\n\n";

        }

        $algunsHoraris = explode("🚏", $totsHoraris);

        for ($i = $voltaHorari; $i <= ($voltaHorari+2); $i++) {
            try {
                $this->say("🚏" . $algunsHoraris[$i]);
            } catch (\Exception $exception){
                $this->say("No hi ha més horaris disponibles.");
                exit;
            }
        }

        $question = Question::create("📜 Vols veure més resultats?")
            ->fallback('No hi ha més resultats')
            ->callbackId('consulta_resultats')
            ->addButtons([
                Button::create("✅ Si")->value(($voltaHorari+1)+2),
                Button::create("❌ No")->value(0)
            ]);

        return $this->ask($question, function (Answer $answer) {
            if ($answer->isInteractiveMessageReply()) {

                try {
                    if($answer->getValue() == 0){
                        $this->say("Gràcies per utilitzar-me!");
                    } else {
                        $this->_horari($answer->getValue());
                    }

                } catch (\Exception $e){
                    $this->say("⚠️ No hem pogut carregar els resultats. Si us plau, torni-ho a intentar escrivint /linies");
                }
            }
        });;

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
