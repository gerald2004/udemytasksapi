<?php

require_once('db.php');
require_once('../model/Response.php');

try {
    $writeDB = DB::connectWriteDB();
} 

catch (PDOException $ex) {
    $logFilePath = 'C:/xampp/logs/error.log';
    error_log("Database Connection error ".$ex->getMessage(), 3,$logFilePath);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database Connection error");
    $response->send();
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

}
else {
    $response = new Response();
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request Method Not Allowed");
    $response->send();
    exit();
}

if (!isset($_SERVER['CONTENT_TYPE']) || strtolower($_SERVER['CONTENT_TYPE']) !== 'application/json'){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("Content Type header not set to JSON");
    $response->send();
    exit();
}

$rowPostData = file_get_contents('php://input');
if(!$jsonData = json_decode($rowPostData)){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("Request body is not valid JSON");
    $response->send();
    exit();
}

if(!isset($jsonData->fullname) || !isset($jsonData->username) || !isset($jsonData->password)){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    (!isset($jsonData->fullname) ? $response->addMessage("Full name not supplied") : false );
    (!isset($jsonData->username) ? $response->addMessage("Username not supplied") : false );
    (!isset($jsonData->password) ? $response->addMessage("User password not supplied") : false );
    $response->send();
    exit();
}

if(strlen($jsonData->fullname) < 1 || strlen($jsonData->username) < 1 || strlen($jsonData->password) < 1 ){
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    (strlen($jsonData->fullname) < 1 ? $response->addMessage("Full name cannot be blank") : false );
    (strlen($jsonData->fullname) > 255 ? $response->addMessage("Full name cannot be greater than 255 characters") : false );
    (strlen($jsonData->username) < 1 ? $response->addMessage("Username cannot be blank") : false );
    (strlen($jsonData->username) > 255 ? $response->addMessage("Username cannot be greater than 255 characters") : false );
    (strlen($jsonData->password) < 1 ?  $response->addMessage("Password cannot be blank") : false );
    (strlen($jsonData->password) > 255 ?  $response->addMessage("Password cannot be greater than 255 characters") : false );
    $response->send();
    exit();
}

$fullname = trim($jsonData->fullname);
$username = trim($jsonData->username);
$password = $jsonData->password;

try {
    $query = $writeDB->prepare('select id from tblusers where username = :username');
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount !== 0){
        $response = new Response();
        $response->setHttpStatusCode(409);
        $response->setSuccess(false);
        $response->addMessage("Username already exits");
        $response->send(); 
        exit();
    }
    $hashed_password = password_hash($password,PASSWORD_DEFAULT);

    $query = $writeDB->prepare('insert  into tblusers (fullname, username, password) values(:fullname, :username, :password)');
    $query->bindParam(':fullname',$fullname, PDO::PARAM_STR);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount === 0) {
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("There was an issue creating the user account, please try again");
    $response->send();
    exit();
    }
    $lastUserID = $writeDB->lastInsertId();

    $returnData = array();
    $returnData['user_id'] = $lastUserID;
    $returnData['fullname'] = $fullname;
    $returnData['username'] = $username;

    $response = new Response();
    $response->setHttpStatusCode(201);
    $response->setSuccess(true);
    $response->addMessage("User created");
    $response->setData($returnData);
    $response->send();
    exit();

} 
catch (PDOException $ex) {
    $logFilePath = 'C:/xampp/logs/error.log';
    error_log("Database query error".$ex,3, $logFilePath); 
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("There was an issue creating the user account, please try again");
    $response->send();
    exit();
}
