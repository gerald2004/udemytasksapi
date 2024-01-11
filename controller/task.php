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
            $response->setHttpStatusCode(400);
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
        $query = $writeDB->prepare('delete from tbltasks where id=:taskid');
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
        $logFilePath = 'C:/xampp/logs/error.log';
        error_log("Database error".$ex->getMessage(), 3, $logFilePath);
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('Failed to delete Task');
        $response->send();
        exit();

      }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        
        try{
            if (!isset($_SERVER['CONTENT_TYPE']) || strtolower($_SERVER['CONTENT_TYPE']) !== 'application/json'){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("content- type header not set to JSON");
                $response->send();
                exit();
            } 
         $rawPostData = file_get_contents('php://input');
         if ($rawPostData === false) {
             $response = new Response();
             $response->setHttpStatusCode(400);
             $response->setSuccess(false);
             $response->addMessage("Failed to read request body");
             $response->send();
             exit();
         }

         if(!$jsonData = json_decode($rawPostData)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Request Body is not valid JSON");
            $response->send();
            exit();
         }
         $title_updated = false;
         $description_updated = false;
         $deadline_updated = false;
         $completed_updated = false;
         
         $queryFields = "";

         if(isset($jsonData->title)){
            $title_updated = true;
            $queryFields .= "title = :title, ";
         }

         if(isset($jsonData->description)){
            $description_updated = true;
            $queryFields .= "description = :description, ";
         }
         if(isset($jsonData->deadline)){
            $deadline_updated = true;
            $queryFields .= "deadline = STR_TO_DATE(:deadline, '%Y-%m-%d %H:%i:%s'), ";
         }
         if(isset($jsonData->completed)){
            $completed_updated = true;
            $queryFields .= "completed = :completed, ";
         }

         $queryFields = rtrim($queryFields, ", ");
         if($title_updated === false && $description_updated === false && $deadline_updated === false && $completed_updated === false){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("No Task field provided to update");
            $response->send();
            exit();
         }

         $query = $writeDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%Y-%m-%d %H:%i:%s") as deadline, completed from tbltasks where id =:taskid');
         $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
         $query->execute();
         
         $rowCount = $query->rowCount();
         if($rowCount === 0 ){
           $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage("No task found to update");
            $response->send();
            exit();  
         }

         while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'],$row['completed']);
         }

         $queryString = "update tbltasks set ".$queryFields." where id=:taskid";
         $query = $writeDB->prepare($queryString);

         if($title_updated === true){
            $task->setTitle($jsonData->title);
            $up_title = $task->getTitle();
            $query->bindParam(':title', $up_title, PDO::PARAM_STR); 
         }
         
         if($description_updated === true){
            $task->setDescription($jsonData->description);
            $up_description = $task->getDescription();
            $query->bindParam(':description', $up_description, PDO::PARAM_STR);
         }

         if($deadline_updated === true){
            $task->setDeadline($jsonData->deadline);
            $up_deadline = $task->getDeadline();
            $query->bindParam(':deadline', $up_deadline, PDO::PARAM_STR);

         }

         if($completed_updated === true){
            $task->setCompleted($jsonData->completed);
            $up_completed = $task->getCompleted();
            $query->bindParam(':completed', $up_completed, PDO::PARAM_STR);

         }

        $query->bindParam(':taskid', $taskid, PDO::PARAM_STR);
        $query->execute();
        //print_r($query->errorInfo());

        $rowCount = $query->rowCount();

        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Task not updated");
            $response->send();
            exit();
        }

        $query = $writeDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%Y-%m-%d %H:%i:%s") as deadline, completed from tbltasks where id =:taskid');
        $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
        $query->execute();
        //print_r($query->errorInfo());

        $rowCount = $query->rowCount();
        //echo "Rows returned: " . $rowCount;
        if($rowCount === 0){
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage("No task found after update");
            $response->send();
            exit();
        }

        $taskArray = array();
        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $task = new Task($row['id'],$row['title'],$row['description'], $row['deadline'],$row['completed']);
            $taskArray[] = $task->returnTaskAsArray();
        }

        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['tasks'] = $taskArray;

        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->addMessage("Task updated successfully");
        $response->setData($returnData);
        $response->send();
        exit();
        
        }
        catch (TaskException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        }
        catch (PDOException $ex) {
            $logFilePath = 'C:/xampp/logs/error.log';
            error_log("Database query error - " . $ex->getMessage(), 3, $logFilePath);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to update the task - check your data for errors");
            $response->send();
            exit();
        }   
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
        try{
            if (!isset($_SERVER['CONTENT_TYPE']) || strtolower($_SERVER['CONTENT_TYPE']) !== 'application/json') {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Content type is not set to JSON");
                $response->send();
                exit();
            }
            
            $rawPostData = file_get_contents('php://input');
            if ($rawPostData === false) {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Failed to read request body");
                $response->send();
                exit();
            }
            
         if(!$jsonData = json_decode($rawPostData)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Request Body is not valid JSON");
            $response->send();
            exit();
         }
         if(!isset($jsonData->title) || !isset($jsonData->completed)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            (!isset($jsonData->title) ? $response->addMessage("Title field is mandatory and must be provided") : false );
            (!isset($jsonData->completed) ? $response->addMessage("Completed field is mandatory and must be provided"): false);
            $response->send();
            exit();
         } 
         if(isset($jsonData->deadline)){
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $jsonData->deadline);
            if (!$date || $date->format('Y-m-d H:i:s') !== $jsonData->deadline) {
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Invalid date format");
                $response->send();
                exit();
            }
         }
         // Check if a task with the same title and completion status already exists
        $query = $writeDB->prepare('SELECT id FROM tbltasks WHERE title = :title AND completed = :completed');
        $query->bindParam(':title', $jsonData->title, PDO::PARAM_STR);
        $query->bindParam(':completed', $jsonData->completed, PDO::PARAM_STR);
        $query->execute();
        if ($query->rowCount() > 0) {
            // Task with the same title and completion status already exists
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Task with the same title and completion status already exists");
            $response->send();
            exit();
        }
         // creating the new task
         $newTask = new Task(null, $jsonData->title, (isset($jsonData->description) ? $jsonData->description : null), (isset($jsonData->deadline) ? $jsonData->deadline : null),$jsonData->completed);
         //creating the variables so that data be inserted in the database
         $title = $newTask->getTitle();
         $description = $newTask->getDescription();
         $deadline = $newTask->getDeadline();
         $completed = $newTask->getCompleted();
         $query = $writeDB->prepare('insert into tbltasks (title, description, deadline, completed) values(:title, :description, :deadline, :completed)');
         //bind the parameters
         $query->bindParam(':title', $title, PDO::PARAM_STR);
         $query->bindParam(':description', $description, PDO::PARAM_STR);
         $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
         $query->bindParam(':completed', $completed, PDO::PARAM_STR);
         $query->execute();

         $rowCount = $query->rowCount();
         if($rowCount === 0 ){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to create task");
            $response->send();
            exit();
         }
        // Retrieve the last inserted ID within the same transaction
      // Get the last inserted ID directly from the PDO instance
        $lastTaskID = $writeDB->lastInsertId();
        $query = $writeDB->prepare('select id, title, description, deadline, completed from tbltasks where id=:taskid');
        $query->bindParam(':taskid', $lastTaskID, PDO::PARAM_INT);
        $query->execute();

         $rowCount = $query->rowCount();
         if($rowCount === 0) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to retrieve task after creation");
            $response->send();
            exit();
         }
         $taskArray = array();
         while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $task = new Task($row['id'],$row['title'],$row['description'], null,$row['completed']);
            if(isset($row['deadline'])) {
                $task->setDeadline($row['deadline']);
            }
            $taskArray[] = $task->returnTaskAsArray();
         }
         $returnData = array();
         $returnData['rows_returned'] = $rowCount;
         $returnData['tasks'] = $taskArray;

         $response = new Response();
         $response->setHttpStatusCode(201);
         $response->setSuccess(true);
         $response->addMessage("Task created successfully");
         $response->setData($returnData);
         $response->send();
         exit();
        }
        catch(TaskException $ex){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage($ex->getMessage());
            $response->send();
            exit();
        }
        catch(PDOException $ex){
            $logFilePath = 'C:/xampp/logs/error.log';
            error_log("Database query error - " . $ex->getMessage(), 3, $logFilePath);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(true);
            $response->addMessage('Failed to insert the task - check submitted data for errors');
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
