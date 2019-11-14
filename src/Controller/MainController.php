<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Controller\SiteController;
use App\Controller\EventController;

use App\Acme\CustomBundle\API;

class MainController extends SiteController
{
    public function index(Request $request) {
        return $this->rendering('index.html.twig');
    }
}