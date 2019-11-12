<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SiteController extends AbstractController
{

    protected function rendering($template, $parameters = array()){
	    
		$defaultParameters = array(
			// 'user' => $this->user,
		);
		
		return $this->render($template, array_merge($defaultParameters, $parameters));
	}

}