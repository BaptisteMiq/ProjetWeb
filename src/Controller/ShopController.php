<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use App\Controller\SiteController;
use App\Acme\CustomBundle\API;
use App\Acme\CustomBundle\User;

class ShopController extends SiteController
{

    public function index() {

        $categories = API::call('GET', '/shop/getCategoriesAndProducts');

        return $this->rendering('shop.html.twig', [
            'categories' => $categories->Categories
        ]);

    }

    public function addProduct(Request $request) {

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

    public function editProductPage(Request $request, $id=null) {

        if($id == null) {
            return $this->redirect('/shop');
        }

        $data = [];
        $data['id'] = $id;
        $product = API::call('GET', '/shop/getProduct', $data);

        if(isset($product->error)) {
            return $this->redirect('/shop');
        }

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }
        
        $centers = API::call('GET', '/centers');
        $categories = API::call('GET', '/shop/getCategories');

        $data = API::process($request, [
            'id' => true,
            'label' => true,
            'description' => true,
            'picture' => true,
            'price' => true,
            'delevery_date' => true,
            'price' => true,
            'id_Center' => true,
            'id_Category' => true,
        ]);
        $data['nb_sales'] = 0;

        if(!isset($data['error'])) {

            $res = API::call('POST', '/shop/updateProduct', $data, $user->getToken());
            if(isset($res->error)) {

                return $this->rendering('shop.edit_product.html.twig', [
                    'centers' => $centers->centers,
                    'categories' => $categories->Categories,
                    'data' => $product->product,
                    'error' => $res->error,
                ]);
            }

            // PRODUCT CREATED
            return $this->redirect('/shop');

        } else {

            return $this->rendering('shop.edit_product.html.twig', [
                'centers' => $centers->centers,
                'categories' => $categories->Categories,
                'data' => $product->product,
            ]);
        }

        return $this->rendering('shop.edit_product.html.twig', [
            'centers' => $centers->centers,
            'categories' => $categories->Categories,
        ]);
        
    }

    public function newProductPage(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }
        
        $centers = API::call('GET', '/centers');
        $categories = API::call('GET', '/shop/getCategories');

        $data = API::process($request, [
            'label' => true,
            'description' => true,
            'picture' => true,
            'price' => true,
            'delevery_date' => true,
            'price' => true,
            'id_Center' => true,
            'id_Category' => true,
        ]);
        $data['nb_sales'] = 0;

        if(!isset($data['error'])) {

            $res = API::call('POST', '/shop/createProduct', $data, $user->getToken());
            if(isset($res->error)) {

                return $this->rendering('shop.new_product.html.twig', [
                    'centers' => $centers->centers,
                    'categories' => $categories->Categories,
                    'data' => $data,
                    'error' => $res->error,
                ]);
            }

            // PRODUCT CREATED
            return $this->redirect('/shop');

        } else {

            return $this->rendering('shop.new_product.html.twig', [
                'centers' => $centers->centers,
                'categories' => $categories->Categories,
            ]);
        }

        return $this->rendering('shop.new_product.html.twig', [
            'centers' => $centers->centers,
            'categories' => $categories->Categories,
        ]);
        
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