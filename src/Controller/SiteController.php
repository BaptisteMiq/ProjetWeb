<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Controller\EventController;

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

		$defaultParameters = array(
			'user' => $user,
			'events' => $events,
		);
		
		return $this->render($template, array_merge($defaultParameters, $parameters));
	}

}