<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Controller\SiteController;
use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;
use App\Acme\CustomBundle\Error;

class UserController extends SiteController
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
            return $this->rendering('login.html.twig', [ 'error' => 'Vous êtes déjà connecté!' ]);
        }

        // Request data name => is required (will die if empty)
        $data = API::process($request, [
            'mail' => true,
            'password' => true,
        ]);

        // If data sent
        if(!isset($data['error'])) {
            
            // Get from API
            $user = API::call('POST', '/users/login', $data);
            // $user = json_decode('{"id": 2, "name": "Baptiste", "mail": "baptiste.miquel@viacesi.fr"}');

            if(!$user) {
                return $this->rendering('login.html.twig', [ 'error' => 'Impossible de se connecter pour le moment.', 'data' => $data ]);
            }

            if(isset($user->error)) {
                return $this->rendering('login.html.twig', [ 'error' => $user->error, 'data' => $data ]);
            }

            $session->set('user', $user);

            return $this->redirect($this->generateUrl('index_page'));

        }

        return $this->rendering('login.html.twig');

    }

    public function registerPage(Request $request)
    {

        $session = $request->getSession();

        $user = new User($request);

        // Get possible centers from database
        $centers = API::call('GET', '/centers');

        $data = API::process($request, [
            'lastname' => true,
            'firstname' => true,
            'mail' => true,
            'password' => true,
            'id_Center' => true,
        ]);
        
        if(!isset($data['error'])) {
            
            // Check if valid data
            $cmail = $this->checkMail($data['mail']);
            if($cmail) {
                return $this->rendering('register.html.twig', [ 'error' => $cmail, 'data' => $data ]);
            };
            $cpass = $this->checkPassword($data['password']);
            if($cpass) {
                return $this->rendering('register.html.twig', [ 'error' => $cpass, 'data' => $data ]);
            }

            // Connect to the API
            $result = API::call('POST', '/users/register', $data);

            if(!$result) {
                return $this->rendering('register.html.twig', [ 'error' => 'Impossible de se créer un compte pour le moment.', 'data' => $data ]);
            }

            if(isset($result->error)) {
                return $this->rendering('register.html.twig', [ 'error' => $result->error, 'data' => $data ]);
            }

            // Login user
            $dataReg = [
                'mail' => $data['mail'],
                'password' => $data['password'],
            ];

            $usr = API::call('POST', '/users/login', $dataReg);

            if(!$usr) {
                return $this->rendering('register.html.twig', [ 'error' => 'Compte créé, mais impossible de s\'y connecter pour le moment.' ]);
            }

            if(isset($usr->error)) {
                return $this->rendering('register.html.twig', [ 'error' => $usr->error ]);
            }

            $session->set('user', $usr);

            return $this->redirect($this->generateUrl('index_page'));

        }

        return $this->rendering('register.html.twig', [
            "centers" => $centers
        ]);

    }

    public function logout(Request $request) {

        $session = $request->getSession();
        $session->remove('user');

        return $this->redirect($this->generateUrl('index_page'));
    
    }

    public function profilePage()
    {

        return $this->rendering('profile.html.twig', [

        ]);

    }

    public function checkPassword($pass) {

        if(strlen($pass) < 6) {
            return 'Password too short';
        }
    
        if(!preg_match("#[0-9]+#", $pass)) {
            return 'Password must have at least one number';
        }
    
        if(!preg_match("#[A-Z]+#", $pass)) {
            return 'Password must have at least one capital letter';
        }

        return false;

    }

    public function checkMail($mail) {

        if(!filter_var($mail, FILTER_VALIDATE_EMAIL) || !preg_match("#@.*?cesi\..+?$#", $mail)) {
            return 'Invalid mail!';
         }

         return false;

    }
}