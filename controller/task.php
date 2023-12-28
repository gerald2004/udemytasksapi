<?php
require_once('db.php');
require_once('../model/Task.php');
require_once('../model/Response.php');

try {
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
    
}

catch(PDOException $ex) {
    error_log("Connection error - ".$ex,0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage('Database connection error');
    $response->send();
    exit();
}

//check for the existance of the task id in the GET global array
if(array_key_exists("taskid",$_GET)) {
    $taskid = $_GET['taskid'];
    if($taskid == '' || !is_numeric($taskid)){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Task ID cannot not be blank or must be numeric");
    }

    //checking the method used 
    //FOR THE GET METHOD
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
          //Make the query
        $query = $readDB->prepare('select id, title, description,deadline, completed from tbltasks where id= :taskid');
        $query->bindParam(':taskid',$taskid, PDO::PARAM_INT);
        $query->execute();

        // get the rowCount
        $rowCount = $query->rowCount();
        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage("Task not found");
            $response->send();
            exit();
        }
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $task = new Task($row['id'], $row['title'], $row['description'], null, $row['completed']);
            if(isset($row['deadline'])) {
                $task->setDeadline($row['deadline']);
            }
            $taskArray[] = $task->returnTaskAsArray();
          }  
        $returnData = [];
        $returnData['rows_returned'] = $rowCount;
        $returnData['tasks'] = $taskArray;
        //creating a success response 
        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->toCache(true);
        $response->setData($returnData);
        $response->send();
        exit();
        }
        catch (TaskException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();

        }
        catch (PDOException $ex) {
            error_log("Connection error - ".$ex,0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get task');
            $response->send();
            exit();

        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        
    }
    else {
    $response = new Response();
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage("Request method not allowed");
    $response->send();
    }
}


