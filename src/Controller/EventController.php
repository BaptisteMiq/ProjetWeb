<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Controller\SiteController;
use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;

class EventController extends SiteController
{
    public function index() {
        return $this->rendering('event.html.twig');
    }

    public static function getEvents() {

        $events = API::call('GET', '/events/all');

        if(isset($events->error)) {
            return $events->error;
        }

        return json_encode($events->AllActivitiesFound);

    }

    public function editEventPage(Request $request, $id=null) {

        $user = new User($request);

        if($id === null) {
            return $this->redirect($this->generateUrl('index_page'));
        }

        $data = [];
        $data['id_Activities'] = API::sanitize($id);
        $event = API::call('GET', '/events/get', $data, $user->getToken());

        $centers = API::call('GET', '/centers', $data);

        if(isset($event->error) || empty($event)) {
            print_r($event->error);
            exit;
            return $this->redirect($this->generateUrl('index_page'));
        }

        $data = API::process($request, [
            'title' => true,
            'description' => true,
            'picture' => true,
            'begin_date' => true,
            'end_date' => false,
            'top_event' => true,
            'price' => true,
            'id_Center' => true,
            'id_State' => true,
            'id_Recurrence' => true,
        ]);
        $data['id'] = $id;

        if(!isset($data['error'])) {
            $res = API::call('POST', '/events/update', $data, $user->getToken());
            if(isset($res->error)) {
                print_r($res->error);
                exit;
            }
            return $this->rendering('event_edit.html.twig', [ 'event' => $event ]);
        } else {
            return $this->rendering('event_edit.html.twig', [ 'event' => $event ]);
        }

    }

    public function newEventPage(Request $request) {

        $user = new User($request);

        $centers = API::call('GET', '/centers');
        $recs = API::call('GET', '/recurrences');

        $data = API::process($request, [
            'title' => true,
            'description' => true,
            'picture' => true,
            'begin_date' => true,
            'end_date' => false,
            'price' => true,
            'id_Center' => true,
            'id_Recurrence' => true,
            'top_event' => true,
        ]);
        $data['id_State'] = 1;

        if(!isset($data['error'])) {

            $res = API::call('POST', '/events/add', $data, $user->getToken());
            if(isset($res->error)) {
                return $this->rendering('event_new.html.twig', [ 'centers' => $centers->centers, 'recs' => $recs->recurrences, 'data' => $data, 'error' => $res->error ]);
            }

            return $this->rendering('event_new.html.twig', [ 'centers' => $centers->centers, 'recs' => $recs->recurrences, 'data' => $data ]);
        } else {
            return $this->rendering('event_new.html.twig', [ 'centers' => $centers->centers, 'recs' => $recs->recurrences, 'error' => $data['error'] ]);
        }

    }

    public function deleteEvent(Request $request) {

        $user = new User($request);

        $data = API::process($request, [
            'id' => true,
        ]);

        if(!isset($data['error'])) {
            $data = API::call('POST', '/events/del', $data, $user->getToken());
            die('OK');
        } else {
            die('Il manque l\'id');
        }

    }
    
    public function showAllEventsPage(Request $request) {

        $events = json_decode($this->getEvents());

        return $this->rendering('events.html.twig');

    }

    public function showEventPage(Request $request, $id=null) {

        $user = new User($request);

        if($id === null) {
            return $this->redirect($this->generateUrl('index_page'));
        }

        $data = [];
        $data['id_Activities'] = API::sanitize($id);

        $events = API::call('GET', '/events/get', $data, $user->getToken());

        if(isset($events->error)) {
            return $this->redirect($this->generateUrl('index_page'));
        }

        $events->sub = false;
		$sub = EventController::getSubscribe($events->activity->id);
			if(count($sub) > 0 && $user != null) {
				foreach ($sub as $key2 => $value2) {
					if($user->id == $value2->id_User) {
						$events->sub = true;
					}
				}
		}
        
        return $this->rendering('event.html.twig', [ 'event' => $events ]);

    }

    public function sendLike(Request $request) {

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank('STUDENT') || $user->hasRank('MEMBER') || $user->hasRank('STAFF'))) {
            die('Not authorized');
        }

        $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

        $data = API::process($request, [    
            'eid' => true,
        ]);
        $data['uid'] = $user->getUser()->id;

        API::call('POST', '/events/like/add', $data);

        return new Response(
            'OK'
        );

    }

    public static function subscribeEvent(Request $request) {

        $session = $request->getSession();

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'id_Activities' => true,
        ]);
        
        if(!isset($data['error'])) {
            $ret = API::call('POST', '/events/subscribe', $data, $user->getToken());

            if(empty($ret)) {
                return new Response('Impossible de s\'inscrire'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible de s\'inscrire: ' . $ret->error); 
            }

            return new Response('OK');
        }

        return new Response('Missing ' . $data['error']);

    }

    public static function unSubscribeEvent(Request $request) {

        $session = $request->getSession();

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'id_Activities' => true,
        ]);
        
        if(!isset($data['error'])) {
            $ret = API::call('POST', '/events/unsubscribe', $data, $user->getToken());

            if(empty($ret)) {
                return new Response('Impossible de se désinscrire'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible de se déinscrire: ' . $ret->error); 
            }

            return new Response('OK');
        }

        return new Response('Missing ' . $data['error']);

    }

    public static function addPicture(Request $request) {

        /*

        $.ajax({
            url: "{{ path('event_addPicture') }}",
            type: 'POST',
            data: {
                    'link': "",
                    'id_Activities': 0
                },
            success: function (data) {
                console.log("Photo envoyée avec succès");
            },
            error : function(jqXHR, textStatus, errorThrown){
                console.log("Impossible d'envoyer la photo");
            }
        });

        */

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank('STUDENT') || $user->hasRank('ADMIN') || $user->hasRank('MEMBER'))) {
            die('Not authorized');
        }

        $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

        $data = API::process($request, [
            'link' => true,
            'id_Activities' => true,
        ]);

        $res = API::call('POST', '/events/addPicture', $data, $user->getToken());

        if(empty($res)) {
            return new Reponse('Ne peut pas envoyer la photo pour une raison inconnue');
            die();
        }
        if($res->error) {
            return new Reponse('Ne peut pas envoyer la photo: ' . $res->error);
            die();
        }

        return new Reponse('OK');

    }

    public static function delComment(Request $request) {

        /*

        $.ajax({
            url: "{{ path('event_delComment') }}",
            type: 'POST',
            data: {
                    'id': 0
                },
            success: function (data) {
                console.log("Commentaire supprimé avec succès");
            },
            error : function(jqXHR, textStatus, errorThrown){
                console.log("Impossible de supprimer le commentaire");
            }
        });

        */

        $data = API::process($request, [    
            'id' => true,
        ]);

        $comment = API::call('POST', '/events/getComment', $data, $user->getToken());

        if(empty($comment)) {
            return new Reponse('Commentaire non trouvé');
            die();
        }
        if($comment->error) {
            return new Reponse('Commentaire non trouvé: ' . $comment->error);
            die();
        }

        $user = new User($request);

        // Logged, member or staff or its own comment only
        if(!$user->isLogged() || !($user->hasRank('STAFF') || $user->hasRank('MEMBER') || $user->getUser()->id == $comment->id_User)) {
            die('Not authorized');
        }

        $res = API::call('POST', '/events/delComment', $data, $user->getToken());

        if(empty($res)) {
            return new Reponse('Ne peut pas supprimer le commentaire pour une raison inconnue');
            die();
        }
        if($res->error) {
            return new Reponse('Ne peut pas supprimer le commentaire: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    public static function delPicture(Request $request) {

        /*

        $.ajax({
            url: "{{ path('event_delPicture') }}",
            type: 'POST',
            data: {
                    'id': 0
                },
            success: function (data) {
                console.log("Photo supprimée avec succès");
            },
            error : function(jqXHR, textStatus, errorThrown){
                console.log("Impossible de supprimer la photo");
            }
        });

        */

        $data = API::process($request, [    
            'id' => true,
        ]);

        $pic = API::call('POST', '/events/delPicture', $data, $user->getToken());

        if(empty($pic)) {
            return new Reponse('Photo non trouvée');
            die();
        }
        if($pic->error) {
            return new Reponse('Photo non trouvée: ' . $pic->error);
            die();
        }

        $user = new User($request);

        // Logged, member or staff or its own comment only
        if(!$user->isLogged() || !($user->hasRank('STAFF') || $user->hasRank('MEMBER') || $user->getUser()->id == $pic->id_User)) {
            die('Not authorized');
        }

        $res = API::call('POST', '/events/delPicture', $data, $user->getToken());

        if(empty($res)) {
            return new Reponse('Ne peut pas supprimer la photo pour une raison inconnue');
            die();
        }
        if($res->error) {
            return new Reponse('Ne peut pas supprimer la photo: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    public static function sendComment(Request $request) {

        /*

        $.ajax({
            url: "{{ path('event_sendComment') }}",
            type: 'POST',
            data: {
                    'id_Picture': 0,
                    'id_Comments': null,
                    'content': 0
                },
            success: function (data) {
                console.log("Commentaire envoyé avec succès");
            },
            error : function(jqXHR, textStatus, errorThrown){
                console.log("Impossible d'envoyer le commentaire");
            }
        });

        */

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank('STUDENT') || $user->hasRank('ADMIN') || $user->hasRank('MEMBER'))) {
            die('Not authorized');
        }

        $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

        $data = API::process($request, [    
            'id_Picture' => true,
            'id_Comments' => false,
            'content' => true,
        ]);

        $res = API::call('POST', '/events/addComment', $data, $user->getToken());

        if(empty($res)) {
            return new Reponse('Ne peut pas envoyer le commentaire pour une raison inconnue');
            die();
        }
        if($res->error) {
            return new Reponse('Ne peut pas envoyer le commentaire: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    public static function downloadSubscribedStudents(Request $request) {

        /*

        $.ajax({
            url: "{{ path('event_sendComment') }}",
            type: 'POST',
            data: {
                    'id: 0,
                },
            success: function (data) {
                console.log(data);
            },
            error : function(jqXHR, textStatus, errorThrown){
                console.log("Impossible de récupérer les inscrits de cet évènement");
            }
        });

        */

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank('ADMIN') || $user->hasRank('MEMBER'))) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'id' => true,
        ]);

        $res = API::call('GET', '/events/getSubscribe', $data, $user->getToken());

        if(empty($res)) {
            return new Reponse('Ne peut pas récupérer la liste des inscrits');
            die();
        }
        if($res->error) {
            return new Reponse('Ne peut pas récupérer la liste des inscrits: ' . $res->error);
            die();
        }

        // Process data to generate PDF / CSV
        // ...

        print_r($res);
        die();

    }

    public function getAllPictures(Request $request) {

        $user = new User($request);
        if(!$user->isLogged() || !$user->hasRank('ADMIN')) {
            die('Not authorized');
        }

        $events = API::call('GET', '/events/all', $data, $user->getToken())->AllActivitiesFound;

        $pictures = [];

        foreach ($event as $key => $value) {
            array_push($pictures, $value->picture);
        }

        print_r($pictures);
        die();

    }

    public function checkUserSubscribedToOldEvent($req, $uid) {

        $data = API::process($req, [    
            'eid' => true,
        ]);
        $data['uid'] = $uid;

        $oldEvents = API::call('GET', '/users/events/old', $data);
        
        if(empty($oldEvents)) {
            die('Event not found');
        }

    }

    public static function subscribeCount($id=null) {

        if($id == null) {
            return 0;
        }

        $data['id_Activities'] = $id;
        
        if(!isset($data['error'])) {
            $ret = API::call('GET', '/events/getSubscribe', $data);

            if(empty($ret)) {
                return new Response('Impossible d\'obtenir la liste'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible d\'obtenir la liste: ' . $ret->error); 
            }

            $reg = $ret->register;
            return count($reg);

        }

        return new Response('Missing ' . $data['error']);

    }

    public static function getSubscribe($id=null) {

        if($id == null) {
            return false;
        }

        $data['id_Activities'] = $id;
        
        if(!isset($data['error'])) {
            $ret = API::call('GET', '/events/getSubscribe', $data);

            if(empty($ret)) {
                return new Response('Impossible d\'obtenir la liste'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible d\'obtenir la liste: ' . $ret->error); 
            }

            $reg = $ret->register;
            return $reg;

        }

        return new Response('Missing ' . $data['error']);

    }

    public static function isSubscribed($id=null) {
        
        if($id == null) {
            return false;
        }

        return true;

    }

    // Handle file upload
    // $i = 0;
    // foreach($request->files as $file) {

    //     if($i > 2) {
    //         die('You uploaded too many pictures! Limit: 2');
    //     }

    //     if(!empty($file)) {
    //         $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    //         $filename = $originalFilename.'-'.uniqid();
    //         try {
    //             $file->move('img', $filename);
    //         } catch(FileException $e) {
    //             die('Failed to upload file :(');
    //         }

    //         $data = [
    //             'file' => 'img/' . $filename
    //         ];
    //         API::call('POST', '/events/comments/addPic', $data);
    //     }

    //     $i++;
    // }

}