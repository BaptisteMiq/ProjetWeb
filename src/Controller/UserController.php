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

        $mail = "qqsdqsdqs";
        $pass = "test2";
        
        // Check if valid data
        // if(!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        //    die('Invalid mail!'); 
        // }
        // if(strlen($pass) < 3 || strlen($pass) > 1e3) {
        //     die('Invalid password!');
        // }

        // Prepare payload
        $payload = array(
            'email' => $mail,
            'password' => $pass,
        );

        // Connect to the API
        $user = API::call('POST', '/users/login', $payload);

        if(isset($user->error)) {
            die('Erreur: ' . $user->error);
        }

        // $user = json_decode('{"id": 2, "name": "Baptiste", "mail": "baptiste.miquel@viacesi.fr"}');

        if(!$user) {
            die('Could not connect');
        }

        $session->set('user', $user);

        return new Response(
            'You are now logged! ' . $user->token
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