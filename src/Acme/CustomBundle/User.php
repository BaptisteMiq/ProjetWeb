<?php

namespace App\Acme\CustomBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class User extends Bundle
{

    const STUDENT = 1;
    const STAFF = 2;
    const MEMBER = 3;

    function __construct($req=null) {

        $this->req = $req;

    }

    function getUser() {

        $session = $this->req->getSession();

        if(!$this->isLogged()) {
            die('User not logged');
        }

        return $session->get('user');

    }
    
    function isLogged() {

        $session = $this->req->getSession();

        return $session->get('user') !== null;

    }

    function hasRank($rank) {

        $session = $this->req->getSession();

        if(!$this->isLogged()) {
            die('User not logged');
        }

        return  $session->get('user')->id_Rank == $rank;

    }

    function getToken() {

        $session = $this->req->getSession();

        if(!$this->isLogged()) {
            die('User not logged');
        }

        return $session->get('user')->token;
    }

}