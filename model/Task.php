<?php
class TaskException extends Exception {}
class  Task {
    private $_id;
    private $_title;
    private $_description;
    private $_deadline;
    private $_completed;
//The magic function called construct to set the variables using a single instance of the class 

public function __construct($id,$title,$description,$deadline,$completed){
$this->setID($id);
$this->setTitle($title);
$this->setDescription($description);
$this->setDeadline($deadline);
$this->setCompleted($completed);
}
//getter methods 
    public function getID(){
        return $this->_id;
    }

    public function getTitle(){
        return $this->_title;
    }

    public function getDescription(){
        return $this->_description;
    }

    public function getDeadline(){
        return $this->_deadline;
    }
    public function getCompleted(){
        return $this->_completed;
    }
    // setter methods 
    public function setID($id) {
        if ($id !== null && (!is_numeric($id) || $id <= 0 || $id > 900000000000000 || $this->_id !== null)) {
            throw new TaskException("Task ID Error");
        }
        $this->_id = $id;
    }

    public function setTitle($title) {
        if(strlen($title) < 1 || strlen($title) > 255 ) {
            throw new TaskException("Task title error");
        }
        $this->_title = $title;
    }
    public function setDescription($description){
        if(($description !== null) &&(strlen($description) >16777215)) {
            throw new TaskException("Task description error");
        }
        $this->_description = $description;
    }
    public function setDeadline($deadline) {
        try {
            if ($deadline !== null) {
                $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $deadline);
                if ($dateTime !== false && $dateTime->format('Y-m-d H:i:s') === $deadline) {
                    $this->_deadline = $dateTime->format('Y-m-d H:i:s');
                } else {
                    throw new TaskException("Invalid date format");
                }
            } else {
                $this->_deadline = null;
            }
        } catch (Exception $e) {
            // Log the exception for debugging
            error_log("Error in setDeadline: " . $e->getMessage());
            throw $e;
        }
      }      
    public function setCompleted($completed){
        if(strtoupper($completed) !== 'Y' && strtoupper($completed !== 'N')) {
            throw new TaskException("Task completed must be Y or N");
        }
        $this->_completed = $completed;
    }  
    
    public function returnTaskAsArray(){
        $task = [];
        $task['id'] = $this->getID();
        $task['title'] = $this->getTitle();
        $task['description'] = $this->getDescription();
        $task['deadline'] = $this->getDeadline();
        $task['completed'] = $this->getCompleted();
        return $task;
    }
}

