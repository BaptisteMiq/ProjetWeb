<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Controller\EventController;
use App\Acme\CustomBundle\API;

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

}