<?php

namespace App\Http\Controllers;

use App\Conversations\TempsRecorregutConversation;
use BotMan\BotMan\BotMan;
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
     * Agafem l'id i el nom de la parada d'orígen.
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

