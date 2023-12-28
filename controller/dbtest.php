<?php

require_once('db.php');
require_once('../model/Response.php');
try {
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();

     // Connection successful message
    //  $response = new Response();
    //  $response->setHttpStatusCode(200);
    //  $response->setSuccess(true);
    //  $response->addMessage('Database Connection Successful');
    //  $response->send();
    //  exit();
}

catch(PDOException $ex) {
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage('Database Connection error: ' . $ex->getMessage());
    $response->send();
    exit();
}    
