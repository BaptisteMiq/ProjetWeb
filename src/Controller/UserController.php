<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;

class UserController extends AbstractController
{

    public function loginPage()
    {

        return $this->render('login.html.twig', [

        ]);

    }

    public function registerPage()
    {

        // Get possible centers from database
        $centers = API::call('GET', '/centers');

        return $this->render('register.html.twig', [
            "centers" => $centers
        ]);

    }

    public function login(Request $request)
    {

        $session = $request->getSession();

        $user = new User($request);

        if($user->isLogged()) {
            die('You are already logged!');
        }

        // Request data name => is required (will die if empty)
        $data = API::process($request, [
            'mail' => true,
            'pass' => true,
        ]);

        // Get from API
        // $user = API::call('POST', '/users/login', $data);
        $user = json_decode('{"id": 2, "name": "Baptiste", "mail": "baptiste.miquel@viacesi.fr"}');

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

        $data = [
            'lastname' => 'MIQUEL',
            'firstname' => 'Baptiste',
            'mail' => 'baptiste.miquel@viacesi.fr',
            'pass' => 'Azertyuiop0',
            'location' => 'TOULOUSE',
        ];

        // $data = API::process($request, [
        //     'lastname' => true,
        //     'firstname' => true,
        //     'mail' => true,
        //     'password' => true,
        //     'location' => true,
        // ]);
        
        // Check if valid data
        $this->checkMail($data['mail']);
        $this->checkPassword($data['pass']);

        // Connect to the API
        // $result = API::call('POST', '/users/register', $data);
        $result = 'OK';

        // return $this->render('base.html.twig', [
        // ]);
        return new Response(
            'OK'
        );

    }

    public function logout(Request $request) {

        $session = $request->getSession();
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