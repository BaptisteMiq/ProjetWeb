<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;

class MainController extends AbstractController
{
    public function index(Request $request) {

        $session = $request->getSession();

        $user = $session->get('user');

        // $user = json_decode('{"id": 2, "firstname": "Baptiste", "lastname": "MIQUEL", "mail": "baptiste.miquel@viacesi.fr"}');

        if(empty($user)) {
            $user = null;
        }

        // $events = API::call('GET', '/events/all');

        return $this->render('index.html.twig', [
            'user' => $user,
            // 'events' => $events,
        ]);

    }
}