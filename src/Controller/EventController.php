<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use App\Controller\SiteController;
use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;

class EventController extends SiteController
{

    // Main event page
    public function index()
    {
        return $this->rendering('event.html.twig');
    }

    // Returns all the events
    public static function getEvents()
    {

        $events = API::call('GET', '/events/all');

        if(isset($events->error)) {
            return $events->error;
        }

        return json_encode($events->AllActivitiesFound);

    }

    // Edit event page (Members only)
    public function editEventPage(Request $request, $id=null)
    {

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        // If no id specified, redirect to index
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
            // Update event
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

    // New event page (Members only)
    public function newEventPage(Request $request)
    {

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

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

    // Delete events (Members only)
    public static function deleteEvent(Request $request)
    {

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'id' => true,
        ]);

        $res = API::call('POST', '/events/del', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas supprimer l\'évènement');
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas supprimer l\'évènement: ' . $res->error);
        }

        return new Response('OK');

    }
    
    // Show all events
    public function showAllEventsPage(Request $request)
    {

        $events = json_decode($this->getEvents());

        return $this->rendering('events.html.twig');

    }

    // Show one event
    public function showEventPage(Request $request, $id=null)
    {

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

        // Get likes for every pictures
        if(isset($events->pictures)) {
            foreach ($events->pictures as $k => $v) {
                $v->like = EventController::getLike($request, $v->id);
            }
        }

        // Get subscribes
        $sub = EventController::getSubscribe($events->activity->id);
        $events->count = EventController::subscribeCount($events->activity->id);
        if(count($sub) > 0 && $user != null) {
            foreach ($sub as $key2 => $value2) {
                if($user->getUser()->id == $value2->id_User) {
                    $events->sub = true;
                }
            }
		}
        
        return $this->rendering('event.html.twig', [ 'event' => $events ]);

    }

    // Like a picture (Student+)
    public function likePicture(Request $request)
    {

        $user = new User($request);

        if(!$user->isLogged()) {
            die('Not authorized');
        }

        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'id_Picture' => true,
        ]);
        
        if(!isset($data['error'])) {
            $ret = API::call('POST', '/events/like', $data, $user->getToken());

            if(empty($ret)) {
                return new Response('Impossible de like'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible de like: ' . $ret->error); 
            }

            return new Response('OK');
        }

        return new Response('Missing ' . $data['error']);

    }

    // Dislike a picture (Student+)
    public function unlikePicture(Request $request)
    {

        $user = new User($request);

        if(!$user->isLogged()) {
            die('Not authorized');
        }

        $user = new User($request);

        $data = API::process($request, [
            'id_Picture' => true,
        ]);

        if(!isset($data['error'])) {
            $ret = API::call('POST', '/events/unlike', $data, $user->getToken());

            if(empty($ret)) {
                return new Response('Impossible de unlike'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible de unlike: ' . $ret->error); 
            }

            return new Response('OK');
        }

        return new Response('Missing ' . $data['error']);

    }

    // Subscribe to an event (Student+)
    public static function subscribeEvent(Request $request)
    {

        $user = new User($request);

        if(!$user->isLogged()) {
            die('Not authorized');
        }

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

    // Unsub (Student+)
    public static function unSubscribeEvent(Request $request) {

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

    // Add a picture to an event (Student+)
    public static function addPicture(Request $request) {

        $user = new User($request);
        if(!$user->isLogged()) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'link' => true,
            'id_Activities' => true,
        ]);

        $res = API::call('POST', '/events/addPicture', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas envoyer la photo pour une raison inconnue');
            die();
        }
        if($res->error) {
            return new Response('Ne peut pas envoyer la photo: ' . $res->error);
            die();
        }

        return new Reponse('OK');

    }

    // Delete a comment (Student+)
    public function delComment(Request $request) {

        $user = new User($request);
        if(!$user->isLogged()) {
            die('Not authorized');
        }

        $data = API::process($request, [    
            'id' => true,
        ]);

        $comment = API::call('GET', '/events/getComment', $data, $user->getToken());

        if(empty($comment)) {
            return new Response('Impossible de trouver le commentaire');
            die();
        }
        if(isset($comment->error)) {
            return new Response('Impossible de trouver le commentaire: ' . $comment->error);
            die();
        }

        $res = API::call('POST', '/events/delComment', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas supprimer le commentaire pour une raison inconnue');
            die();
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas supprimer le commentaire: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    // Delete a picture (Staff)
    public static function delPicture(Request $request) {

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank(User::STAFF))) {
            die('Not authorized');
        }

        $data = API::process($request, [    
            'id' => true,
        ]);

        $res = API::call('POST', '/events/delPicture', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas supprimer la photo pour une raison inconnue');
            die();
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas supprimer la photo: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    // Send a comment to a picture of an event (Student+)
    public static function sendComment(Request $request) {

        $user = new User($request);
        if(!$user->isLogged()) {
            die('Not authorized');
        }

        // $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

        $data = API::process($request, [    
            'id_Picture' => true,
            'id_Comments' => false,
            'content' => true,
        ]);

        if(strlen($data['content']) < 2 || strlen($data['content']) > 1000) {
            return new Response('Taille du commentaire invalide');
        }

        $res = API::call('POST', '/events/addComment', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas envoyer le commentaire pour une raison inconnue');
            die();
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas envoyer le commentaire: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    // Return true if the user is subscribed to the event and if the event is finished
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

    // Count number of subscribe
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

    // Return subscribed students
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

    // Return true if the user has liked the picture
    public static function getLike($req, $id=null) {

        $user = new User($req);

        if($id == null) {
            return false;
        }
        $data = [];
        $data['id_Picture'] = $id;
        
        if(!isset($data['error'])) {
            $ret = API::call('GET', '/events/getLike', $data, $user->getToken());
            return !isset($ret->error);
        }
        return new Response('Missing ' . $data['error']);
    }

    // Returns all the likes
    public static function getAllLike($req, $id=null) {

        $user = new User($req);

        if($id == null) {
            return false;
        }
        $data = [];
        $data['id_Picture'] = $id;
        
        if(!isset($data['error'])) {
            $ret = API::call('GET', '/events/getAllLike', $data, $user->getToken());
            if(isset($ret->error)) {
                return 0;
            }
            return count($ret->AllLike);
        }
        return new Response('Missing ' . $data['error']);
    }

    // Export all pictures to CSV
    public function getPictureCSV() {
        
        $data = [];

        if(!isset($data['error'])) {
            $ret = API::call('GET', '/events/getAllPicture', $data);

            if(empty($ret)) {
                return new Response('Impossible d\'obtenir la liste'); 
            }
            if(isset($ret->error)) {
                return new Response('Impossible d\'obtenir la liste: ' . $ret->error); 
            }

            $reg = $ret->Activities;
            $resp = "CATEGORIE;LIEN;NOM;PRENOM;MAIL";

            foreach ($reg as $p => $pic) {
                $pics = $pic->pictures;
                foreach($pics as $ps) {
                    $resp .= "\n$ps->name;$ps->link;$ps->userLastname;$ps->userFirstname;$ps->userMail";
                }
            }

            $filename = 'liste_image.csv';
            $fileContent = $resp;

            $response = new Response($fileContent);

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );

            $response->headers->set('Content-Disposition', $disposition);

            return $response;

        }

        return new Response('Missing ' . $data['error']);

    }

    // Export all subscribed students to CSV
    public function getSubscribeCSV(Request $request, $id=null) {

        $user = new User($request);

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

            if(count($reg) < 1) {
                return new Response("Personne n'est inscrit sur cette liste!"); 
            }

            $act = API::call('GET', '/events/get', [ 'id_Activities' => $id ], $user->getToken());

            $resp = "Liste des inscrits pour " . utf8_decode($act->activity->title) . " ( " . count($reg) . " )\nIDENTIFIANT;NOM;PRENOM;MAIL";
            
            foreach ($reg as $k => $usr) {
                $user = API::call('GET', '/users/get', ['id' => $usr->id_User])->user[0];
                $resp .= "\n$usr->id_User;$user->lastname;$user->firstname;$user->mail";
            }

            $filename = 'liste_inscrits.csv';
            $fileContent = $resp;

            $response = new Response($fileContent);

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );

            $response->headers->set('Content-Disposition', $disposition);

            return $response;

        }

        return new Response('Missing ' . $data['error']);

    }

}