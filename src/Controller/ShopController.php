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

        $data = [];
        $data['rid_Rank'] = 3;
        $members = API::call('GET', '/users/getAll', $data, $user->getToken());
        
        if(empty($members)) {
            return new Response('Aucun utilisateur trouvé');
        }
        if(isset($members->error)) {
            return new Response('Aucun utilisateur trouvé: ' . $cart->error);
        }

        $members = $members->allUsers;

        $buyList = "";
        foreach ($cart as $item) {
            $buyList .= " - " . $item->product->label . " x " . $item->quantity . "  <br />";
        }

        foreach ($members as $key => $value) {
            SiteController::sendMail($value->mail, "ACHAT PRODUIT", "
                <img src='data:image/jpeg;base64,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'><br />
                L'utilisateur <b>" . $user->getUser()->lastname . " " . $user->getUser()->firstname .
                "</b> vient d'acheter des produits dans la boutique. <br />Son mail pour le contacter: <b>" . $user->getUser()->mail .
                "</b><br /><br />Liste des produits: <br />" . $buyList . "<br /><br /><br />L'équipe du BDE <small>(et de HiDev)</small>"
            );
        }

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