<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;
use App\Acme\CustomBundle\Error;

class UserController extends AbstractController
{

    public function token(Request $request) {

        $session = $request->getSession();

        $user = new User($request);

        if(!$user->isLogged()) {
            die('You are not logged');
        }

        API::call('GET', '/token', false, $user->getUser()->token);

        return new Response(
            'Bienvenue dans la page top secrète, ' . $user->getUser()->token
        );

    }

    public function loginPage(Request $request)
    {

        $session = $request->getSession();

        $user = new User($request);

        if($user->isLogged()) {
            // die('You are already logged!');
            return $this->render('login.html.twig', [ 'error' => 'Vous êtes déjà connecté!' ]);
        }

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
            return $this->render('login.html.twig', [ 'error' => 'Vous êtes déjà connecté!' ]);
        }

        // Request data name => is required (will die if empty)
        $data = API::process($request, [
            'mail' => true,
            'password' => true,
        ]);

        // Get from API
        $user = API::call('POST', '/users/login', $data);
        // $user = json_decode('{"id": 2, "name": "Baptiste", "mail": "baptiste.miquel@viacesi.fr"}');

        if(!$user) {
            return $this->render('login.html.twig', [ 'error' => 'Impossible de se connecter pour le moment.' ]);
        }

        if(isset($user->error)) {
            return $this->render('login.html.twig', [ 'error' => $user->error ]);
        }

        $session->set('user', $user);

        return $this->redirect($this->generateUrl('index_page'));

        // return $this->render('index.html.twig', [

        // ]);
    
    }

    public function register(Request $request)
    {
        $session = $request->getSession();

        $user = new User($request);

        if($user->isLogged()) {
            return $this->render('login.html.twig', [ 'error' => 'Vous êtes déjà connecté!' ]);
        }

        // $data = [
        //     'lastname' => 'MIQUEL',
        //     'firstname' => 'Baptiste',
        //     'mail' => 'baptiste.miquel@viacesi.fr',
        //     'password' => 'Azertyuiop0',
        //     'centerId' => 0,
        // ];

        $data = API::process($request, [
            'lastname' => true,
            'firstname' => true,
            'mail' => true,
            'password' => true,
            'id_Center' => true,
        ]);
        
        // Check if valid data
        $this->checkMail($data['mail']);
        $this->checkPassword($data['password']);

        // Connect to the API
        $result = API::call('POST', '/users/register', $data);

        if(!$result) {
            return $this->render('register.html.twig', [ 'error' => 'Impossible de se créer un compte pour le moment.' ]);
        }

        if(isset($result->error)) {
            return $this->render('login.html.twig', [ 'error' => $result->error ]);
        }

        // Login user
        $dataReg = [
            'mail' => $data['mail'],
            'password' => $data['password'],
        ];

        $usr = API::call('POST', '/users/login', $dataReg);

        if(!$usr) {
            return $this->render('register.html.twig', [ 'error' => 'Compte créé, mais impossible de s\'y connecter pour le moment.' ]);
        }

        if(isset($usr->error)) {
            return $this->render('register.html.twig', [ 'error' => $user->error ]);
        }

        $session->set('user', $user);

        return $this->redirect($this->generateUrl('index_page'));

    }

    public function logout(Request $request) {

        $session = $request->getSession();
        $session->remove('user');

        return $this->render('base.html.twig', [
        ]);

    }

    public function profile()
    {
        $session = $request->getSession();
        // $user = new User($request);

        return $this->render('profile.html.twig', [

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