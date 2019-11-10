<?php

namespace App\Acme\CustomBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

const SERVER = "http://172.20.10.4:666/api";

class API extends Bundle
{
    static function call($method, $url, $data=false)
    {
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

        $result = curl_exec($curl);

        if (curl_errno($curl)) { 
            // return curl_error($curl);
            return false;
         }

        //  if(!API::success($result)) {
        //      return false;
        //  }

        curl_close($curl);

        return json_decode($result);
    }
    // static function success($resp) {
    //     json_decode($resp);
    //     return (json_last_error() == JSON_ERROR_NONE);
    // }
}