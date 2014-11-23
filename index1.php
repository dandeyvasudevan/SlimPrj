<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
    header('Access-Control-Allow-Credentials: true');

require 'Slim/Slim.php';
require 'RedBean/rb.php';


$config = require 'config/params.php';
require 'Slim/Models/OAuth2.php';

\Slim\Slim::registerAutoloader();

$database   =   $config['database'];
$dbHost     =   $database['host'];
$dbUserName =   $database['username'];
$dbPassword =   $database['password'];
$dbName     =   $database['name'];

//Set up database connection
R::setup('mysql:host='.$dbHost.';dbname='.$dbName.';',$dbUserName,$dbPassword);
R::freeze(true);

//echo '<br />Password:'.md5("dandey");

//print_r( apache_request_headers() );
//var_dump($_SERVER);

class ResourceNotFoundException extends Exception {}

$version    =   "v1";
$app        =   new \Slim\Slim();
//$app->add(new Slim\Middleware\HttpBasicAuth("vasudevan", "dandey"));

function checkAccess($accessToken){
    $accessTokenRows    =   R::getRow('select * from oauth_access_tokens where access_token="'.$accessToken.'"');
    $accessTokenRowsCnt =   count($accessTokenRows);
    
    if( $accessTokenRowsCnt == 0) {
        $data['token']  =   null;
            
        echo json_encode($data);
        exit;
    }
    
    return true;
}

/**
 * Function to authenticate every request
 * Logic to check if the request has valid authorization header
 */
function authenticate(Slim\Route $route){
    
    
    $app        =   Slim\Slim::getInstance();
    
    $response   =   array();
    $request    =   $app->request();
    $headers    =   OAuth2::determineAccessTokenInHeader($request);
    
    
    if( isset($headers)){
        $token      =   $headers;
        
        if( !OAuth2::isValidAccessToken($token)){
            $response["error"]  =   true;
            $response["message"]=   "Access Denied. Invalid Token.";
            
            OAuth2::echoResponse(401, $response);
            $app->stop();
        } else {
            $response["token"]  =   $token;
        }
    } else {
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        
        OAuth2::echoResponse(400, $response);
        $app->stop();
    }
}


$app->get(
    '/',
    function () {
        $template = <<<EOT
<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8"/>
            <title>EApps Tech</title>
            
        </head>
        <body>
            
            <h1>EApps Tech</h1>
            
        </body>
    </html>
EOT;
        echo $template;
    }
);

$app->get(
        '/'.$version.'/helloworld', 
        function() use ($app){
    
            $app->response()->header('Content-Type', 'application/json');
            echo json_encode(array("name"=>"Hello World"));
        }
);

//Listing all model data - eg: watchlists
$app->get(
        '/'.$version.'/:model',
        'authenticate',
        function($model) use ($app){
            $modelData     =   R::find($model);
            $request    =   $app->request();
            //$request->headers('Access-Control-Allow-Headers','Authorization,DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type');    
             
            $app->response()->header('Content-Type', 'application/json');
            
            $data[$model]   =   R::exportAll($modelData);
            $data['token']  =   OAuth2::getToken();
            //$data['token1'] =   OAuth2::generateToken();
            
            echo json_encode($data);
        }
);

//Listing a model data - eg: watchlist/1
$app->get(
        '/'.$version.'/:model/:id',
        'authenticate',
        function($model, $id) use ($app){
            try {
                $modelData    =   R::findOne($model, 'id=?', array($id));
                
                if($modelData){
                    $app->response()->header('Content-Type', 'application/json');
                    
                    $data[$model]   =   R::exportAll($modelData);
                    $data['token']  =   OAuth2::getToken();
                    
                    echo json_encode($data);
                } else {
                    throw new ResourceNotFoundException();
                }
            } catch(ResourceNotFoundException $e) {
                $app->response()->status(404);
            } catch (Exception $e) {
                $app->response()->status(400);
                $app->response()->header('X-Status-Reason', $e->getMessage());
            }
        }
);

//Logic to handle post requests to /watchlist
$app->post(
        '/'.$version.'/:model',
        'authenticate',
        function($model) use ($app){
            try{
                $request        =   $app->request();
                $body           =   $request->getBody();
                
                $inputData      =   json_decode($body);
                $inputDataCnt   =   count($inputData);
                
                //Saving watchlist information
                $modelData  =   R::dispense($model);
                    
                if($inputDataCnt >0){
                    foreach($inputData as $key=>$val)
                        $modelData->$key    =   $val;
                }
                
                /*$watchList->user_id     =   (int)$inputData->user_id;
                $watchList->date        =   date("Y-m-d");
                $watchList->instrument  =   (string)$inputData->instrument;
                $watchList->weekly      =   (string)$inputData->weekly;
                $watchList->daily       =   (string)$inputData->daily;
                $watchList->candlestick =   (string)$inputData->candlestick;
                $watchList->resistancemajor =   (float)$inputData->resistancemajor;
                $watchList->resistanceminor =   (float)$inputData->resistanceminor;
                $watchList->supportmajor    =   (float)$inputData->supportmajor;
                $watchList->supportminor    =   (float)$inputData->supportminor;
                $watchList->notes           =   (string)$inputData->notes;
                */
                
                $id =   R::store($modelData);
                
                //
                //echo json_encode(array("data"=>$inputData));
                //echo $inputData->user_id;
                $app->response()->status(201);
                $app->response()->header('Content-Type', 'application/json');
                
                $data[$model]   =   R::exportAll($modelData);
                $data['token']  =   OAuth2::getToken();
                echo json_encode($data);
                
            } catch (Exception $e) {
                $app->response()->status(400);
                $app->response()->header('X-Status-Reason', $e->getMessage());
            }
        }
);

//Logic to handle modify model item - put watclist item
$app->put(
        '/'.$version.'/:model/:id',
        'authenticate',
        function($model, $id) use ($app){
            try{
                $request        =   $app->request();
                $body           =   $request->getBody();
                
                $inputData      = json_decode($body);
                $inputDataCnt   =   count($inputData);
                //Saving watchlist information
                $modelData  =   R::load($model, $id);
                
                if($inputDataCnt >0){
                    foreach($inputData as $key=>$val)
                        $modelData->$key    =   $val;
                }
                
                /*$watchList->user_id     =   (int)$inputData->user_id;
                $watchList->date        =   date("Y-m-d");
                $watchList->instrument  =   (string)$inputData->instrument;
                $watchList->weekly      =   (string)$inputData->weekly;
                $watchList->daily       =   (string)$inputData->daily;
                $watchList->candlestick =   (string)$inputData->candlestick;
                $watchList->resistancemajor =   (float)$inputData->resistancemajor;
                $watchList->resistanceminor =   (float)$inputData->resistanceminor;
                $watchList->supportmajor    =   (float)$inputData->supportmajor;
                $watchList->supportminor    =   (float)$inputData->supportminor;
                $watchList->notes           =   (string)$inputData->notes;
                */
                
                $id =   R::store($modelData);
                
                //
                //echo json_encode(array("data"=>$inputData));
                //echo $inputData->user_id;
                $app->response()->status(200);
                $app->response()->header('Content-Type', 'application/json');
                
                $data[$model]   =   R::exportAll($modelData);
                $data['token']  =   OAuth2::getToken();
                echo json_encode($data);
                
            } catch (Exception $e) {
                $app->response()->status(400);
                $app->response()->header('X-Status-Reason', $e->getMessage());
            }
        }
);


//Logic to handle delete model item - delete watchlist/1
$app->delete(
        '/'.$version.'/:model/:id',
        'authenticate',
        function($model, $id) use ($app) {
            try {
                $modelData  =   R::load($model, $id);
                
                if( $modelData) {
                    $delModelData   =   R::trash($modelData);
                    $app->response()->status(200);
                } else {
                    $app->notFound();
                }
                
            } catch (Exception $e) {
                $app->response()->status(400);
                $app->response()->header('X-Status-Reason', $e->getMessage());
            }
        }
);

//Logic to generate token - token api
$app->get(
        '/'.$version.'/token/:userName/:password/:consumerKey',
        function($userName, $password, $consumerKey) use ($app) {
            //echo '<br />userName:'.$userName.', password:'.$password.', consumerKey:'.$consumerKey;
            $response   =   OAuth2::checkLogin($userName, $password, $consumerKey);
            
            OAuth2::echoResponse(200, $response);
        }
);

//Logic to logout the user
$app->get(
        '/'.$version.'/logout/:token/:userID',
        function($token, $userID) use ($app) {
            $response   =   OAuth2::logout($token, $userID);
            OAuth2::echoResponse(200, $response);
        }
);

$app->run();