<?php 

require_once('Response.php');

//Creating a new instance of the Response class

$response = new Response();
$response->setSuccess(true);
$response->setHttpStatusCode(200);
$response->addMessage("Test Message 1");
$response->addMessage("Test Message 2");
$response->send();

