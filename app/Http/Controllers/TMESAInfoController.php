<?php

namespace App\Http\Controllers;

use App\Conversations\TempsRecorregutConversation;
use BotMan\BotMan\BotMan;
use Carbon\Carbon;
use Goutte;
use GuzzleHttp\Client;

class TMESAInfoController extends Controller
{



    /*
     * Agafem el nom, el títol i l'id de la línia i el sentit.
     */
    public function infoLinies(){

        $crawler = Goutte::request('GET', 'http://www.tmesa.com/index.asp?lang=ca&proces=horarisCalcul');
        $result = $crawler->filter('select#IdLineaMenuHorari option')->each(function ($node) {
            return array(
                'nom'   =>  $node->text(),
                'value' =>  $node->attr('value'),
                'title' =>  $node->attr('title'),
            );
        });

        return $result;

    }

    /*
     * Agafem l'id i el nom de la parada d'orígen.
     */
    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retornaOrigen($linia, $sentit){
        $client = new Client();
        $res = $client->request('POST', 'http://www.tmesa.com/consultes.asp', [
            'form_params' => [
                'nocache' => (float)rand()/(float)getrandmax(),
                'idLinea' => $linia,
                'idSentido' => $sentit,
                'proces' => "PassaLinea",
            ]
        ]);

        return $this->_calculaParades(utf8_encode($res->getBody()));
    }

    /*
     * Agafem l'id i el nom de la parada de destí.
     */
    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retornaDesti($calcul, $linia, $sentit){
        $client = new Client();
        $res = $client->request('POST', 'http://www.tmesa.com/consultes.asp', [
            'form_params' => [
                'nocache' => (float)rand()/(float)getrandmax(),
                'calcul' => $calcul,
                'idLinea' => $linia,
                'idSentido' => $sentit,
                'proces' => "PassaLinea",
            ]
        ]);

        return $this->_calculaParades(utf8_encode($res->getBody()));
    }


    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retornaJornada($idParada, $linia, $sentit){
        $client = new Client();
        $res = $client->request('POST', 'http://www.tmesa.com/consultes.asp', [
            'form_params' => [
                'nocache' => (float)rand()/(float)getrandmax(),
                'idioma' => "ca",
                'idLinea' => $linia,
                'idSentit' => $sentit,
                'parada' => $idParada,
                'proces' => "obteJornades",
            ]
        ]);

        return $this->_calculaParades(utf8_encode($res->getBody()));
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retornaHorari($idJornada, $idParadaDe, $idParadaOr, $idSentido){
        $client = new Client();
        $res = $client->request('POST', 'http://www.tmesa.com/consultes.asp', [
            'form_params' => [
                'nocache' => (float)rand()/(float)getrandmax(),
                'idJornada' => $idJornada,
                'idParadaDe' => $idParadaDe,
                'idParadaOr' => $idParadaOr,
                'idSentido' => $idSentido,
                'proces' => "DemanaHora",
            ]
        ]);

        return $this->_horariAnadaTornada($res->getBody());
    }

    /**
     * @param $resposta
     * @return array
     *
     * Aquesta funció retorna un array amb l'hora d'anada, l'hora de tornada i els minuts que tarda en fer el recorregut.
     *
     */
    private function _horariAnadaTornada($resposta){

        $anadesTornades = explode("<", $resposta);

        $anades = explode(">", $anadesTornades[0]);
        $tornades = explode(">", $anadesTornades[1]);

        $horaris = array();

        $seguentHasSet = 0;

        foreach ($anades as $key=>$horari){
            if($horari != "si" && $horari != "" && $horari != "no") {

                setlocale(LC_TIME, 'es_ES');
                $ara = Carbon::now()->timestamp;

                $iniciHorari=Carbon::parse($horari);
                $fiHorari=Carbon::parse($tornades[$key]);

                if($iniciHorari->timestamp >= $ara && $seguentHasSet == 0){
                    $seguent = "➡️";
                    $seguentHasSet = 1;
                } else {
                    $seguent = "";
                }


                array_push($horaris, array(
                    'mostrar' => $anades[$key+1],
                    'anada' => trim($horari, " "),
                    'tornada' => trim($tornades[$key], " "),
                    'minuts' =>  $iniciHorari->diffInMinutes($fiHorari),
                    'seguent' => $seguent,
                    'temps' => gmdate("H:i:s", Carbon::now()->diffInSeconds($iniciHorari->addDays(1)))
                ));
            }

        }

        if($seguentHasSet == 0){
            $horaris[0]["seguent"] = "➡️";
        }

        return $this->_sortHorari($horaris);
    }


    /**
     * @param $horaris
     * @return array
     *
     * Aquesta funció retornarà sempre el primer bus.
     *
     */
    private function _sortHorari($horaris){

        $comenca_index = array_column($horaris, 'seguent');
        $comenca = array_search("➡️", $comenca_index);

        $sortejat = array();

        foreach (array_slice($horaris, $comenca) as $key => $val)
        {
            if($val['mostrar'] == "si") {
                array_push($sortejat, array(
                    'mostrar' => $val['mostrar'],
                    'anada' => $val['anada'],
                    'tornada' => $val['tornada'],
                    'minuts' => $val['minuts'],
                    'seguent' => $val['seguent'],
                    'temps' => $val['temps'],
                ));
            }
        }

        foreach ($horaris as $key => $val)
        {
            if($key < $comenca) {
                if($val['mostrar'] == "si") {
                    array_push($sortejat, array(
                        'mostrar' => $val['mostrar'],
                        'anada' => $val['anada'],
                        'tornada' => $val['tornada'],
                        'minuts' => $val['minuts'],
                        'seguent' => $val['seguent'],
                        'temps' => $val['temps'],
                    ));
                }
            } else {
                break;
            }
        }

        return $sortejat;
    }

    private function _calculaParades($resposta){
        $parades = explode("<", $resposta);

        $origens = array();

        foreach ($parades as $key=>$origen){
            //preparem les parades:
            try {
                $dades = explode(">", $parades[$key]);

                array_push($origens,
                    array(
                        "id" => $dades[0],
                        "nom" => rtrim($dades[1], " ")
                    )
                );
            } catch (\Exception $e){

            }
        }

        return $origens;
    }


    public function mostraTempsRecorregut(BotMan $bot)
    {
        $bot->startConversation(new TempsRecorregutConversation());
    }
}

