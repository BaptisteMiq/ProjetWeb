<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;

class UserController extends AbstractController
{
    public function loginPage()
    {
        return $this->render('login.html.twig', [
        ]);
    }
    public function registerPage()
    {
        return $this->render('register.html.twig', [
        ]);
    }
    public function login(Request $request)
    {
        $session = $request->getSession();

        if($session->get('user') !== null) {
            return new Response(
                'You are already logged!'
            );
        }

        // Request data verification
        // if($request->get('mail') === null || $request->get('mail')  === null) {
        //     die('Missing infos');
        // }

        // Get and filter data
        $mail = filter_var($request->get('mail'), FILTER_SANITIZE_STRING);
        $pass = filter_var($request->get('pass'), FILTER_SANITIZE_STRING);

        $mail = "qqsdqsdqs@cesi.fr";
        $pass = "tesdsq4Fdst";

        
        // Prepare payload
        $payload = array(
            'mail' => $mail,
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

        // return $this->render('base.html.twig', [
        // ]);
        return new Response(
            'Bienvenue, utilisateur numero ' . $user->id
        );

    }

    public function register(Request $request)
    {
        $session = $request->getSession();

        // Get and filter data
        // $mail = filter_var($request->get('mail'), FILTER_SANITIZE_STRING);
        // $pass = filter_var($request->get('pass'), FILTER_SANITIZE_STRING);

        $lastname = "Chirac";
        $firstname = "Jacques";
        $mail = "jacqueschirac@gouv.fr";
        $password = "jevoislafemmedemacronensecret";
        $location = "TOULOUSE";
        
        // Check if valid data
        $this->checkMail($mail);
        $this->checkPassword($pass);

        // Prepare payload
        $payload = array(
            'lastname' => $lastname,
            'firstname' => $firstname,
            'mail' => $mail,
            'password' => $password,
            'location' => $location,
        );

        // Connect to the API
        $user = API::call('POST', '/users/register', $payload);

        if(isset($user->error)) {
            die('Erreur: ' . $user->error);
        }

        if(!$user) {
            die('Could not connect');
        }

        $session->set('user', $user);

        // return $this->render('base.html.twig', [
        // ]);
        return new Response(
            'OK'
        );

    }

    public function logout(Request $request) {
        $session = $request->getSession();
        // No need to check if is logged in
        $session->remove('user');

        return $this->render('base.html.twig', [
        ]);
    }

    public function checkPassword($pass) {

        if(strlen($pass) < 6) {
            die("Password too short");
        }
    
        if(!preg_match("#[0-9]+#", $pass)) {
            die("Password must have at least one number");
        }
    
        if(!preg_match("#[A-Z]+#", $pass)) {
            die("Password must have at least one capital letter");
        }     

    }

    public function checkMail($mail) {

        if(!filter_var($mail, FILTER_VALIDATE_EMAIL) || !preg_match("#@.*?cesi\..+?$#", $mail)) {
            die('Invalid mail!');
         }

    }
}