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
    public function index() {
        return $this->rendering('event.html.twig');
    }

    // Returns all the events
    public static function getEvents() {

        $events = API::call('GET', '/events/all');

        if(isset($events->error)) {
            return $events->error;
        }

        return json_encode($events->AllActivitiesFound);

    }

    // Edit event page (Members only)
    public function editEventPage(Request $request, $id=null) {

        if(!$user->isLogged() || !($user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        $user = new User($request);

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

    // New event page
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

    public static function deleteEvent(Request $request) {

        $user = new User($request);

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

        if(isset($events->pictures)) {
            foreach ($events->pictures as $k => $v) {
                $v->like = EventController::getLike($request, $v->id);
            }
        }

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

    public function likePicture(Request $request) {

        $session = $request->getSession();

        $user = new User($request);

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

    public function unlikePicture(Request $request) {

        $session = $request->getSession();

        $user = new User($request);

        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

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

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
            die('Not authorized');
        }

        // $this->checkUserSubscribedToOldEvent($request, $user->getUser()->id);

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

    public function delComment(Request $request) {

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
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

    public static function delPicture(Request $request) {

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
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

    public static function sendComment(Request $request) {

        $user = new User($request);
        if(!$user->isLogged() || !($user->hasRank(User::STUDENT) || $user->hasRank(User::STAFF) || $user->hasRank(User::MEMBER))) {
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

    // public static function csvToPDF($temp, $file) {

    //     $endpoint = "https://sandbox.zamzar.com/v1/jobs";
    //     $apiKey = "7a9b61238125c036249649ca2b792d4830706226";
    //     $sourceFile = curl_file_create($file);
    //     $targetFormat = "pdf";
        
    //     $postData = array(
    //       "source_file" => $sourceFile,
    //       "target_format" => $targetFormat
    //     );
        
    //     $ch = curl_init(); // Init curl
    //     curl_setopt($ch, CURLOPT_URL, $endpoint); // API endpoint
    //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    //     curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":"); // Set the API key as the basic auth username
    //     $body = curl_exec($ch);
    //     curl_close($ch);
        
    //     $response = json_decode($body, true);

    //     sleep(5);
        
    //     echo "Response:\n---------\n";
    //     print_r($response);

    //     $jobID = $response['id'];
    //     $endpoint = "https://sandbox.zamzar.com/v1/jobs/$jobID";
    //     $apiKey = "7a9b61238125c036249649ca2b792d4830706226";

    //     $ch = curl_init(); // Init curl
    //     curl_setopt($ch, CURLOPT_URL, $endpoint); // API endpoint
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    //     curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":"); // Set the API key as the basic auth username
    //     $body = curl_exec($ch);
    //     curl_close($ch);

    //     $job = json_decode($body, true);

    //     echo "Job:\n----\n";
    //     print_r($job);

    //     $fileID = $job['target_files'][0]['id'];
    //     $localFilename = "converted.pdf";;
    //     $endpoint = "https://sandbox.zamzar.com/v1/files/$fileID/content";
    //     $apiKey = "7a9b61238125c036249649ca2b792d4830706226";

    //     $ch = curl_init(); // Init curl
    //     curl_setopt($ch, CURLOPT_URL, $endpoint); // API endpoint
    //     curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ":"); // Set the API key as the basic auth username
    //     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

    //     $fh = fopen($localFilename, "wb");
    //     curl_setopt($ch, CURLOPT_FILE, $fh);

    //     $body = curl_exec($ch);
    //     curl_close($ch);

    //     echo "File downloaded\n";
        
    // }

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