<?php

namespace App\Acme\CustomBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

// const HOST = '10.131.128.187';
const HOST = '127.0.0.1';
const PORT = 666;

const SERVER = "http://" . HOST . ":" . PORT . "/api";

class API extends Bundle
{
    static function call($method, $url, $data=false, $token=0)
    {

        // $wait = 10;
        // if(!$fp = fsockopen(HOST, PORT, $errCode, $errStr, $wait)){   
        //     die('Could not connect to API');
        // }
        // fclose($fp);

        $curl = curl_init();

        $url = SERVER . $url;

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        // curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // curl_setopt($curl, CURLOPT_USERPWD, "username:password");

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'token: ' . $token
        ));

        $result = curl_exec($curl);

        if (curl_errno($curl)) { 
            // print_r(curl_error($curl));
            // die();
            return false;
         }

        curl_close($curl);

        $res = json_decode($result);

        if(!$res) {
            return json_encode('{"error": "Accès au serveur de base de données impossible pour le moment."');
            // die('Could not connect to API');
        }
        if(isset($res->error)) {
            return $res;
            // die('Error: ' . $res->error);
        }

        return $res;
        
    }

    // Check, seralize and make readable data from request
    static function process($req, $data) {
        $res = array();
        foreach($data as $name => $required) 
            if($req->get($name) === null)
                $required ? $res['error'] = $name : '';
            else $res[$name] = API::sanitize($req->get($name));
        return $res;
    }

    static function sanitize($var) {
        return filter_var($var, FILTER_SANITIZE_STRING);
    }

}