<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;

class EventController extends AbstractController
{
    public function index() {

        return $this->render('event.html.twig', [

        ]);

    }

    public function getEvents(Request $request) {

        $events = API::call('GET', '/events/all');

        print_r($events);

    }

    public function subscribeEvent(Request $request) {

        $session = $request->getSession();

        if($session->get('user') === null) {
            return new Response(
                'You are not logged!'
            );
        }

        $data = API::process($request, [
            'eventId' => true,
        ]);
        $data['userId'] = $session->get('user')->id;

        $events = API::call('GET', '/events/subscribe', $data);

        print_r($events);

    }

    public function showEvents(Request $request) {

        $events = API::call('GET', 'events/all');

        return new Response(
            print_r($events)
        );

    }

    public function showEvent(Request $request) {

        $data = API::process($request, [
            'eid' => true,
        ]);
        $event = API::call('GET', 'events/get', $data);

        return new Response(
            print_r($event)
        );

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