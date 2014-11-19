<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class OAuth2 {
        
    //Logic to check if token is valid or not..
    public static function isValidAccessToken($token){
        $accessTokenRows    =   R::getRow('select * from oauth_access_tokens where access_token="'.$token.'"');
        
        return ($accessTokenRows) ? true : false;
    }
    
    //Logic to add response and exit from app..
    public static function echoResponse($status_code, $response){
        $app    =   Slim\Slim::getInstance();
        $app->status($status_code);
        
        $app->contentType('application/json');
        echo json_encode($response);
    }
    
    //Logic to check if token exists in header
    public static function getToken(){
        $headers    =   apache_request_headers();
         $header     =   isset( $headers['Authorizationtoken'] ) ? $headers['Authorizationtoken'] : '';
        $accessToken=   trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $header));
        
        return $accessToken;
    }
    
    //Logic to generate api token key..
    public static function generateToken() {
        //return substr( md5(uniqid(rand(), true)), 0, 16);
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
    
    public static function generateAccessSecretToken(){
        return bin2hex(openssl_random_pseudo_bytes(16));
    }
    
    public static function determineAccessTokenInHeader(Slim\Http\Request $request)
    {
        //$header     =   $request->headers('Authorization');
        $headers    =   apache_request_headers(); 
        $header     =   isset( $headers['Authorizationtoken'] ) ? $headers['Authorizationtoken'] : '';
        $accessToken=   trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $header));

        return ($accessToken === 'Bearer') ? '' : $accessToken;
    }
    
    public static function checkLogin($userName, $password, $consumerKey){
        $query  =   "SELECT * 
                    FROM  `oauth_consumers` oc,  `users` u
                    WHERE oc.user_id = u.id
                    AND u.username =  '".$userName."'
                    AND u.password =  '".$password."'
                    AND oc.consumer_key =  '".$consumerKey."'
                    AND oc.enable_password_grant=1 AND u.active=1";
        
        
        //echo $query;
        $loginData  =   R::getRow($query);
        $response   =   array();
        
        if( $loginData ){
            $access_token           =   self::generateToken();
            $access_secret_token    =   self::generateAccessSecretToken();
            $userID                 =   $loginData['user_id'];
            
            $sql = 'insert into oauth_access_tokens set '
            . 'access_token = :access_token,' 
            . 'access_token_secret = :access_token_secret,'
            . 'consumer_key = :consumer_key, '
            . 'user_id = :user_id';
            
            try {
                $update =   R::exec($sql, array("access_token"=>$access_token, 
                                                "access_token_secret"=>$access_secret_token,
                                                "consumer_key"=>$consumerKey,
                                                "user_id"=>$userID
                                        ));
                
                $response["token"]      =   $access_token;
            } catch (PDOException $e) {
                $app->response()->status(400);
                $app->response()->header('X-Status-Reason', $e->getMessage());
            }
            
        } else {
            $response["error"]  =   true;
            $response["message"]=   "Couldn't create API key";
        }
     
        return $response;
    }
}

