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
        $mostPop = ShopController::getMostPopularProducts();

        return $this->rendering('shop.html.twig', [
            'categories' => $categories->Categories,
            'mostPop' => $mostPop,
        ]);

    }

    public function categoriesPage(Request $request) {

        $categories = API::call('GET', '/shop/getCategories');

        return $this->rendering('shop.categories.html.twig', [
            'categories' => $categories->Categories,
        ]);

    }

    public function newCategory(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'label' => true,
        ]);

        if(!isset($data['error'])) {
            $res = API::call('POST', '/shop/createCategory', $data, $user->getToken());
            if(isset($res->error)) {
                return new Response($res->error);
            }
            return new Response('OK');
        } else {
            return new Response($data['error']); 
        }
        return new Response('OK');
    }

    public function removeCategory(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'id' => true,
        ]);

        if(!isset($data['error'])) {

            $res = API::call('POST', '/shop/deleteCategory', $data, $user->getToken());
            if(isset($res->error)) {
                return new Response($res->error);
            }
            return new Response('OK');
        } else {
            return new Response($data['error']); 
        }
        return new Response('OK');
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

    public static function removeProduct(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $data = API::process($request, [
            'id' => true,
        ]);

        $res = API::call('POST', '/shop/deleteProduct', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas supprimer le produit');
            die();
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas supprimer le produit: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    public function cartPage(Request $request) {

        $user = new User($request);

        $cart = API::call('GET', '/shop/getCart', [], $user->getToken());

        if(empty($cart)) {
            return $this->redirect('/shop');
        }
        if(!isset($cart->cart)) {
            return $this->redirect('/shop');
        }

        if(count($cart->cart) < 1) {
            return $this->redirect('/shop');
        }

        return $this->rendering('shop_cart.html.twig', [
            'products' => $cart->cart,
        ]);

    }

    public static function addToCart(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        API::call('POST', '/shop/createCart', [], $user->getToken());

        $cartId = API::call('GET', '/shop/getIdCart', [], $user->getToken());

        if(empty($cartId)) {
            return new Response('Ne peut pas obtenir l\'id du panier');
            die();
        }
        if(isset($cartId->error)) {
            return new Response('Ne peut pas obtenir l\'id du panier: ' . $cartId->error);
            die();
        }
        
        $cartId = $cartId->cart;


        $data = API::process($request, [
            'id_Product' => true,
            'quantity' => true,
        ]);
        $data['id_Cart'] = $cartId;

        // Check if int
        // if($data['quantity'] < 1 || $data['quantity'] > 1000) {
        //     return new Response('Quantité incorrecte !');
        //     die();
        // }

        $res = API::call('POST', '/shop/addProductToCart', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas ajouter le produit pour une raison inconnue');
            die();
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas envoyer le produit: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    public function removeFromCart(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $cartId = API::call('GET', '/shop/getIdCart', [], $user->getToken());

        if(empty($cartId)) {
            return new Response('Ne peut pas obtenir l\'id du panier');
            die();
        }
        if(isset($cartId->error)) {
            return new Response('Ne peut pas obtenir l\'id du panier: ' . $cartId->error);
            die();
        }
        
        $cartId = $cartId->cart;

        $data = API::process($request, [
            'id_Product' => true,
        ]);

        $res = API::call('POST', '/shop/delProductToCart', $data, $user->getToken());

        if(empty($res)) {
            return new Response('Ne peut pas enlever le produit pour une raison inconnue');
            die();
        }
        if(isset($res->error)) {
            return new Response('Ne peut pas enlever le produit: ' . $res->error);
            die();
        }

        return new Response('OK');

    }

    public static function getMostPopularProducts() {

        $categories = API::call('GET', '/shop/getCategoriesAndProducts')->Categories;

        $mostPop = [];
        $products = [];
        foreach ($categories as $k1 => $category) {
            foreach ($category->products as $k2 => $product) {

                $products[] = $product;

                if(count($mostPop) < 3) {
                    $mostPop[] = $product;
                }
            }
        }

        $mostPopCopy = $mostPop;
        foreach ($products as $k1 => $product) {
            foreach ($mostPop as $k2 => $mp) {
                if($mp->nb_sales < $product->nb_sales && !ShopController::checkProductInObj($mostPopCopy, $product)) {
                    $mostPopCopy[ShopController::getMinInArr($mostPopCopy)] = $product;
                    break;
                }
            }
            $mostPop = $mostPopCopy;
        }

        return $mostPop;

    }

    protected static function checkProductInObj($obj, $prod) {
        foreach ($obj as $k => $v) {
            if($v->id == $prod->id) {
                return true;
            }
        }
        return false;
    }

    protected static function getMinInArr($arr) {
        $minIndex = 0;
        $min = $arr[0];
        foreach ($arr as $k => $v) {
            if($v->nb_sales < $min->nb_sales) {
                $minIndex = $k;
                $min = $v;
            }
        }
        return $minIndex;
    }

    public static function buyCart(Request $request) {

        $user = new User($request);
        // if(!$user->isLogged() || $user->hasRank('MEMBER')) {
        //     die('Not authorized');
        // }

        $cart = API::call('GET', '/shop/getCart', [], $user->getToken());

        if(empty($cart)) {
            return new Response('Impossible de trouver le panier');
        }
        if(isset($cart->error)) {
            return new Response('Impossible de trouver le panier: ' . $cart->error);
        }

        $del = API::call('POST', '/shop/deleteCart', [], $user->getToken());

        if(empty($del)) {
            return new Response('Achat impossible');
        }
        if(isset($del->error)) {
            return new Response('Achat impossible: ' . $del->error);
        }

        $cart = $cart->cart;

        foreach ($cart as $item) {
            $data = [];
            $data['nb_sales'] = $item->quantity;
            $data['id'] = $item->product->id;
            $res = API::call('POST', '/shop/updateNbSalesProduct', $data, $user->getToken());
        }

        // $members = [];
        // foreach ($member as $key => $value) {
        //     # code...
        // }
        // $this->sendMail($dest, $subject, $content)

        // Take all the money from the user
        // ...

        // $receipt = "";
        // $receipt .= "RECEIPT:<br />-----------";
        // $receipt .= "TO " . $user->getUser()->lastname . ' ' . $user->getUser()->firstname . '<br /><br />';
        // foreach ($cart as $item) {
        //     $receipt .= $item->product->label . ' x ' . $item->quantity . ' : ' . $item->product->price . '€ TTC<br />';
        //     $receipt .= $item->product->description . '<br /><br />';
        // }
        // $receipt .= 'TOT: ' . $totPrice . '€<br />';
        // $buyTime = date("l jS \of F Y h:i:s A");
        // $receipt .= '<br />Bought: ' . $buyTime; 

        // Send mail to members
        // ...

        // return $receipt;

        return new Response('OK');

    }

}