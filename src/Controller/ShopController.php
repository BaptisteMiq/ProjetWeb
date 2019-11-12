<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;

class ShopController extends AbstractController
{

    public function index() {

        return $this->render('shop.html.twig', [

        ]);

    }

    public function addItem(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'name' => true,
            'description' => true,
            'price' => true,
            'category' => true,
        ]);

        API::call('POST', '/shop/items/add', $data);

        return new Response(
            'OK'
        );

    }

    public function editItem(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'pid' => true,
            'name' => false,
            'description' => false,
            'price' => false,
            'category' => false,
        ]);

        API::call('PUT', '/shop/items/edit', $data);

        return new Response(
            'OK'
        );

    }

    public function removeItem(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'pid' => true,
        ]);

        API::call('POST', '/shop/items/remove', $data);

        return new Response(
            'OK'
        );

    }

    public function addToCart(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'pid' => true,
            'quantity' => true,
        ]);
        $data['user'] = $user->getUser()->id;

        API::call('POST', '/shop/cart/add', $data);

        return new Response(
            'OK'
        );

    }

    public function removeFromCart(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'cid' => true,
            'pid' => true,
        ]);
        $data['user'] = $user->getUser()->id;

        API::call('POST', '/shop/cart/remove', $data);

        return new Response(
            'OK'
        );

    }

    public function buy(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'cid' => true,
        ]);
        $data['user'] = $user->getUser()->id;

        $items = API::call('POST', '/shop/cart/getItems', $data);

        $totPrice = 0;
        foreach ($items as $item) {
            $totPrice += $item->quantity * $item->price;
        }

        // Take all the money from the user
        // ...

        $receipt = "";
        $receipt .= "RECEIPT:<br />-----------";
        $receipt .= "TO " . $user->getUser()->lastname . ' ' . $user->getUser()->firstname . '<br /><br />';
        foreach ($items as $item) {
            $receipt .= $item->name . ' x ' . $item->quantity . ' : ' . $item->price . '€ TTC<br />';
            $receipt .= $item->description . '<br /><br />';
        }
        $receipt .= 'TOT: ' . $totPrice . '€<br />';
        $buyTime = date("l jS \of F Y h:i:s A");
        $receipt .= '<br />Bought: ' . $buyTime; 

        API::call('POST', '/shop/cart/remove', $data);

        // Send mail to members
        // ...

        return new Response(
            $receipt
        );

    }

}