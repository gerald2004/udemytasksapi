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
        $query = $readDB->prepare('select id, title, description,deadline, completed from tbltasks where id=:taskid');
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
      try {
        $query = $writeDB->prepare('delete from tbtasks where id=:taskid');
        $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
        $query->execute();
        $rowCount = $query->rowCount();
        if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('Task not found');
            $response->send();
            exit();
        }
        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage('Task deleted');
        $response->send();
        exit();
      } 
      catch(PDOException $ex) {
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('Failed to delete Task');
        $response->send();
        exit();

      }
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
elseif(array_key_exists("completed", $_GET)){
    $completed = $_GET['completed'];
    if($completed !== 'Y' && $completed !== 'N'){
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Completed filter must be Y or N');
        $response->send();
        exit();
    }
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $query = $readDB->prepare('select id, title, description,deadline, completed from tbltasks where completed=:completed');
            $query->bindParam(':completed', $completed, PDO::PARAM_STR);
            $query->execute();
            $rowCount = $query->rowCount();
            $taskArray = array();
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $task = new Task($row['id'],$row['title'],$row['description'], null,$row['completed']);
                if(isset($row['deadline'])) {
                    $task->setDeadline($row['deadline']);
                }
                $taskArray[] = $task->returnTaskAsArray();
            }
            $returnData = array();
            $returnData['row_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit();
        }
        catch(TaskException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        }
        catch(PDOException $ex) {
            error_log("Database query error -" . $ex->getMessage(), 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get task');
            $response->send();
            exit();
        }
    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage('Request Method not allowed');
        $response->send();
        exit();
    }
}
elseif(array_key_exists("page", $_GET)){
    if($_SERVER['REQUEST_METHOD'] = 'GET'){
        $page = $_GET['page'];
        if($page == '' || !is_numeric($page)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage('Page number cannot be blank and must be numeric');
            $response->send();
            exit();
        }
        $limitPerPage = 20;
        try {
            $query = $readDB->prepare('select count(id) as totalNoOfTasks from tbltasks');
            $query->execute();
            $row = $query->fetch(PDO::FETCH_ASSOC);
            $tasksCount = intval($row['totalNoOfTasks']);

            $numOfPages = ceil($tasksCount/$limitPerPage);
            if($numOfPages == 0) {
                $numOfPages = 1;
            }
            if($page > $numOfPages || $page == 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Page not found");
                $response->send();
                exit();
            }

            $offset = ($page == 1 ? 0 : ($limitPerPage*($page-1)));
            $query = $readDB->prepare('select id, title, description, deadline, completed from tbltasks limit :pglimit offset :offset');
            $query->bindParam(':pglimit',$limitPerPage,PDO::PARAM_INT);
            $query->bindParam(':offset',$offset,PDO::PARAM_INT);
            // echo("Limit per page: ".$limitPerPage);
            // echo("Offset: ".$offset);
            $query->execute();
            $rowCount = $query->rowCount();
            $taskArray = array();
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                    $task = new Task($row['id'], $row['title'], $row['description'], null, $row['completed']);
                    if(isset($row['deadline'])){
                        $task->setDeadline($row['deadline']);
                    }
                    $taskArray[] = $task->returnTaskAsArray();
                    error_log("Task Array: " . print_r($taskArray, true), 0);
                    
            }
            $returnData = array();

            // $taskArray = array();
            // $results = $query->fetchAll(PDO::FETCH_ASSOC);
            // $rowCount = count($results);
            // foreach($results as $row){
            //     $task = new Task($row['id'], $row['title'], $row['description'], null, $row['completed']);
            //     if(isset($row['deadline'])){
            //         $task->setDeadline($row['deadline']);
            //     }
            //     $taskArray[] = $task->returnTaskAsArray();
            //     error_log("Task Array: " . print_r($taskArray, true), 0);
            // }
            $returnData['rows_returned'] = $rowCount;
            $returnData['total_rows'] = $tasksCount;
            ($page < $numOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
            ($page > 1 ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit();
        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        }
        catch(PDOException $ex){
            error_log("Database query error".$ex, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get tasks');
            $response->send();
            exit();
        }    
    }
    else{
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage('Request method not aloowed');
        $response->send();
        exit();
    }
}

elseif(empty($_GET)){
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        try {
        $query = $readDB->prepare('select id, title, description, deadline,completed from tbltasks');
        $query->execute();
        $rowCount=$query->rowCount();
        $taskArray = array();
        while($row=$query->fetch(PDO::FETCH_ASSOC)){
            $task = new Task($row['id'],$row['title'],$row['description'],$row['deadline'],$row['completed']);
            $taskArray[] = $task->returnTaskAsArray();
        }
        $returnData = array();   
        $returnData['rows_returned'] = $rowCount;
        $returnData['tasks'] = $taskArray;
        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->toCache(true);
        $response->setData($returnData);
        $response->send();
        exit();
    } catch (TaskException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        }
        catch (PDOException $ex) {
            error_log("Database query error - " . $ex->getMessage(), 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get tasks');
            $response->send();
            exit();
        }   
    }
    elseif($_SERVER['REQUEST_METHOD'] === 'POST'){

    }
    else {
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage('Request Method not allowed');
        $response->send();
        exit();
    }
}
