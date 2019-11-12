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

        $events = API::call('GET', '/events/all');

        return $this->render('event.html.twig', [
            'user' => $user,
            'events' => $events,
        ]);

    }
}