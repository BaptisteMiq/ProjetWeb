<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Controller\EventController;
use App\Acme\CustomBundle\API;
use \Mailjet\Resources;

class SiteController extends AbstractController
{

    protected function rendering($template, $parameters = array()){

		$user = $this->get('session')->get('user');

		if(empty($user)) {
            $user = null;
		}
		
		$events = json_decode(EventController::getEvents());
		foreach ($events as $key => $value) {
			$value->count = EventController::subscribeCount($value->id);
			$value->sub = false;
			$sub = EventController::getSubscribe($value->id);
			if(count($sub) > 0 && $user != null) {
				foreach ($sub as $key2 => $value2) {
					if($user->id == $value2->id_User) {
						$value->sub = true;
					}
				}
			}
		}

		$preferences = null;
		$preferencesAll = API::call('GET', '/preferences')->preferences;

		if($user != null) {
			foreach ($preferencesAll as $key => $value) {
				if($value->id == $user->id_Preferences) {
					$preferences = $value;
				}
			}
		}

		$defaultParameters = array(
			'user' => $user,
			'preferences' => $preferences,
			'events' => $events,
		);
		
		return $this->render($template, array_merge($defaultParameters, $parameters));
	}

	public static function sendMail($dest, $subject, $content) {
		$mj = new \Mailjet\Client('ea071e172cf98babfd2aaad4628ffecf','23deab9ba116903135bb292983675dbc',true,['version' => 'v3.1']);
		$body = [
			'Messages' => [
			[
				'From' => [
				'Email' => "baptiste.miquel@viacesi.fr",
				'Name' => "BDE CESI"
				],
				'To' => [
				[
					'Email' => $dest,
					'Name' => "Bapt"
				]
				],
				'Subject' => $subject,
				'TextPart' => "",
				'HTMLPart' => $content,
				'CustomID' => "AppGettingStartedTest"
			]
			]
		];
		$response = $mj->post(Resources::$Email, ['body' => $body]);
		$response->success();
		// var_dump($response->getData());
	}

}