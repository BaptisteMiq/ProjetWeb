<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;

class UserController
{
    public function login(Request $request)
    {
        $session = $request->getSession();

        if($session->get('user') !== null) {
            return new Response(
                'You are already logged!'
            );
        }

        // Get and filter data
        // $mail = filter_var($request->get('mail'), FILTER_SANITIZE_STRING);
        // $pass = filter_var($request->get('pass'), FILTER_SANITIZE_STRING);

        $mail = "baptiste.miquel@viacesi.fr";
        $pass = "test";
        
        // Check if valid data
        if(!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
           die('Invalid mail!'); 
        }
        if(strlen($pass) < 3 || strlen($pass) > 1e3) {
            die('Invalid password!');
        }

        // Prepare payload
        $payload = array(
            'mail' => $mail,
            'pass' => $pass,
        );

        // Connect to the API
        // $user = API::call('GET', '/login', $payload);

        $user = json_decode('{"id": 2, "name": "Baptiste", "mail": "baptiste.miquel@viacesi.fr"}');

        if(!$user) {
            die('Could not connect');
        }

        $session->set('user', $user);

        return new Response(
            'You are now logged!'
        );

    }

    public function logout(Request $request) {
        $session = $request->getSession();
        // No need to check if is logged in
        $session->remove('user');

        return new Response(
            'You are now logged-out!'
        );
    }
}