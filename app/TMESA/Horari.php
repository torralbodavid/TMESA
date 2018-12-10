<?php
/**
 * Created by PhpStorm.
 * User: dtorralbo
 * Date: 07/12/2018
 * Time: 01:43
 */

namespace App\TMESA;


class Horari
{

    private $jornada;
    private $paradaDe;
    private $paradaOr;
    private $sentit;
    private $linia;

    /**
     * @return mixed
     */
    public function getLinia()
    {
        return $this->linia;
    }

    /**
     * @param mixed $linia
     */
    public function setLinia($linia): void
    {
        $this->linia = $linia;
    }

    /**
     * @return mixed
     */
    public function getJornada()
    {
        return $this->jornada;
    }

    /**
     * @param mixed $jornada
     */
    public function setJornada($jornada): void
    {
        $this->jornada = $jornada;
    }

    /**
     * @return mixed
     */
    public function getParadaDe()
    {
        return $this->paradaDe;
    }

    /**
     * @param mixed $paradaDe
     */
    public function setParadaDe($paradaDe): void
    {
        $this->paradaDe = $paradaDe;
    }

    /**
     * @return mixed
     */
    public function getParadaOr()
    {
        return $this->paradaOr;
    }

    /**
     * @param mixed $paradaOr
     */
    public function setParadaOr($paradaOr): void
    {
        $this->paradaOr = $paradaOr;
    }

    /**
     * @return mixed
     */
    public function getSentit()
    {
        return $this->sentit;
    }

    /**
     * @param mixed $sentit
     */
    public function setSentit($sentit): void
    {
        $this->sentit = $sentit;
    }



}