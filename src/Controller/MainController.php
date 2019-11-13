<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Controller\EventController;

use App\Acme\CustomBundle\API;

class MainController extends AbstractController
{
    public function index(Request $request) {

        $session = $request->getSession();

        $user = $session->get('user');

        // print_r($user);

        // $user = json_decode('{"id": 2, "firstname": "Baptiste", "lastname": "MIQUEL", "mail": "baptiste.miquel@viacesi.fr"}');

        if(empty($user)) {
            $user = null;
        }

        // $events = API::call('GET', '/events/all');

        $events = json_decode(EventController::getEvents());

        return $this->render('index.html.twig', [
            'user' => $user,
            'events' => $events,
        ]);

    }
}