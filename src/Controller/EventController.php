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

        return $this->rendering('event.html.twig', [ 'event' => $events ]);

    }

    public function sendLike(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || !($user->hasRank('STUDENT') || $user->hasRank('ADMIN'))) {
        //     die('Not authorized');
        // }

        // $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

        $data = API::process($request, [    
            'eid' => true,
        ]);
        $data['uid'] = $user->getUser()->id;

        API::call('POST', '/events/like/add', $data);

        return new Response(
            'OK'
        );

    }

    public function subscribeEvent(Request $request) {

        $session = $request->getSession();

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF))) {
            die('Not authorized');
        }

        $data = API::process($request, [
            'eventId' => true,
        ]);
        
        if(!isset($data['error'])) {
            $events = API::call('GET', '/events/subscribe', $data);
            return new Response('OK');
        }

        return new Response('Missing ' . $data['error']);

    }

    public function sendComment(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || !($user->hasRank('STUDENT') || $user->hasRank('ADMIN'))) {
        //     die('Not authorized');
        // }

        // $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

        // Handle file upload
        $i = 0;
        foreach($request->files as $file) {

            if($i > 2) {
                die('You uploaded too many pictures! Limit: 2');
            }

            if(!empty($file)) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $filename = $originalFilename.'-'.uniqid();
                try {
                    $file->move('img', $filename);
                } catch(FileException $e) {
                    die('Failed to upload file :(');
                }

                $data = [
                    'file' => 'img/' . $filename
                ];
                API::call('POST', '/events/comments/addPic', $data);
            }

            $i++;
        }


        $data = API::process($request, [    
            'eid' => true,
            'content' => true,
        ]);
        $data['uid'] = $user->getUser()->id;

        API::call('POST', '/events/comments/add', $data);

        return new Response(
            'OK'
        );


    }

    public function newEvent(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || !($user->hasRank('MEMBER'))) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'title' => true,
            'description' => true,
            'picture' => true,
            'begin_date' => true,
            'end_date' => false,
            'recurrence' => true,
            'price' => true,
        ]);
        
        // Set the center id
        $center = null;
        if(empty($request->get('center'))) {
            $center = $user->getUser()->center;
        } else {
            $center = API::sanitize($request->get('center'));
        }
        $data['center'] = $center;

        API::call('POST', 'events/add', $data);

        return new Response(
            'OK'
        );

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

}