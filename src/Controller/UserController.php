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

    public function adminPage(Request $request) {

        return $this->rendering('admin.html.twig');

    }

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
                return $this->rendering('register.html.twig', [ 'centers' => $centers->centers, 'error' => $cmail, 'data' => $data ]);
            };
            $cpass = $this->checkPassword($data['password']);
            if($cpass) {
                return $this->rendering('register.html.twig', [ 'centers' => $centers->centers, 'error' => $cpass, 'data' => $data ]);
            }

            // Connect to the API
            $result = API::call('POST', '/users/register', $data);

            if(!$result) {
                return $this->rendering('register.html.twig', [ 'centers' => $centers->centers, 'error' => 'Impossible de se créer un compte pour le moment.', 'data' => $data ]);
            }

            if(isset($result->error)) {
                return $this->rendering('register.html.twig', [ 'centers' => $centers->centers, 'error' => $result->error, 'data' => $data ]);
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
            "centers" => $centers->centers
        ]);

    }

    public function logout(Request $request) {

        $session = $request->getSession();
        $session->remove('user');

        return $this->redirect($this->generateUrl('index_page'));
    
    }

    public function profilePage(Request $request)
    {
        $centers = API::call('GET', '/centers');

        $user = new User($request);
        if(!$user->isLogged()) {
            die('Not authorized');
        }

        $dataUser = API::process($request, [
            'lastname' => true,
            'firstname' => true,
            'mail' => true,
            'password' => true,
            'id_Center' => true
        ]);   

        $dataUser['id'] = $user->getUser()->id;
        $dataUser['id_Preferences'] = $user->getUser()->id_Preferences;

        if($user->getUser()->id_Preferences == 1) {
            $preference = [    
                'theme' => 1,
                'notification' => false,
            ];
        } else if($user->getUser()->id_Preferences == 2) {
            $preference = [    
                'theme' => 1,
                'notification' => true,
            ];
        } else if($user->getUser()->id_Preferences == 3) {
            $preference = [    
                'theme' => 2,
                'notification' => false,
            ];
        } else if($user->getUser()->id_Preferences == 4) {
            $preference = [    
                'theme' => 2,
                'notification' => true,
            ];
        } 

        if(!isset($dataUser['error'])) {

            $user->getUser()->lastname = $dataUser['lastname'];
            $user->getUser()->firstname = $dataUser['firstname'];
            $user->getUser()->mail = $dataUser['mail'];
            $user->getUser()->password = "";
            $user->getUser()->id_Center = $dataUser['id_Center'];
            $user->getUser()->id_Preferences = $dataUser['id_Preferences'];
    
            $res = API::call('POST', '/users/update', $dataUser, $user->getToken());

            if(isset($res->error)) {
                return $this->rendering('profile.html.twig' , [ 'centers' => $centers->centers, 'datauser' => $dataUser, 'preference' => $preference, 'error' => $res->error ]);
            }

            return $this->rendering('profile.html.twig', [ 'centers' => $centers->centers, 'datauser' => $dataUser, 'preference' => $preference]);

        } else {

            return $this->rendering('profile.html.twig', [ 'centers' => $centers->centers, 'preference' => $preference, 'error' => $dataUser['error'] ]);

        }

        return $this->rendering('profile.html.twig');

    }

    public function profilePreferencePage(Request $request)
    {
        $centers = API::call('GET', '/centers');

        $user = new User($request);
        if(!$user->isLogged()) {
            die('Not authorized');
        }

        $dataUser = API::process($request, [
            'id' => true,
            'lastname' => true,
            'firstname' => true,
            'mail' => true,
            'password' => true,
            'id_Preferences' => true,
            'id_Center' => true
        ]);   

        $dataUser['id'] = $user->getUser()->id;
        $dataUser['id_Preferences'] = $user->getUser()->id_Preferences;

        $data = API::process($request, [    
            'theme' => true,
            'notification' => true,
        ]);

        if($user->getUser()->id_Preferences == 1) {
            $preference = [    
                'theme' => 1,
                'notification' => false,
            ];
        } else if($user->getUser()->id_Preferences == 2) {
            $preference = [    
                'theme' => 1,
                'notification' => true,
            ];
        } else if($user->getUser()->id_Preferences == 3) {
            $preference = [    
                'theme' => 2,
                'notification' => false,
            ];
        } else if($user->getUser()->id_Preferences == 4) {
            $preference = [    
                'theme' => 2,
                'notification' => true,
            ];
        } 

        if(!isset($data['error'])) {

            $dataId = [];

            if($data['theme'] == 1 && $data['notification'] == false) {
                $dataId['id_Preferences'] = 1;
                $user->getUser()->id_Preferences = 1;
            } else if($data['theme'] == 1 && $data['notification'] == true) {
                $dataId['id_Preferences'] = 2;
                $user->getUser()->id_Preferences = 2;
            } else if($data['theme'] == 2 && $data['notification'] == false) {
                $dataId['id_Preferences'] = 3;
                $user->getUser()->id_Preferences = 3;
            } else if($data['theme'] == 2 && $data['notification'] == true) {
                $dataId['id_Preferences'] = 4;
                $user->getUser()->id_Preferences = 4;
            }
    
            $res = API::call('POST', '/updatePreference', $dataId, $user->getToken());
            // $res = API::call('POST', '/users/update', $dataUser, $user->getToken());

            if(isset($res->error)) {
                return $this->redirect('/profile');
            }

            return $this->redirect('/profile');

        } else {

            return $this->redirect('/profile');

        }

        return $this->redirect('/profile');

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