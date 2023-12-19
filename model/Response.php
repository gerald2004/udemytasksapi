<?php
//creating the response class 
class Response {
    //Defining the variables 
    private $_success;
    private $_httpStatusCode;
    private $_messages = array();
    private $_data;
    private $_toCache;
    private $_responseData = array(); 
    // functions to set the above variables 
    public function setSuccess($success){
        $this->_success = $success;
    }

    public function setHttpStatusCode($httpStatusCode) {
        $this->_httpStatusCode = $httpStatusCode;
    }

    public function addMessage($message) {
        $this->_messages[] = $message;
    }

    public function setData($data){
        $this->_data = $data;
    }
    public function toCache($toCache){
        $this->_toCache = $toCache;
    }

    // Send the above 
    public function send() {
        header('Content-Type: application/json; charset=utf-8');
        // check for caching 
        if($this->_toCache == true) {
            header('Cache-control: max-age=60');
        }
        else {
            header('Cache-control:no-cache, no-store');
        }

    // Catching errors in case any of the fields is missing 
    //checking if true or false for success
    if(($this->_success !== false && $this->_success !== true) || !is_numeric($this->_httpStatusCode)){
        http_response_code(500);
        $this->_responseData['statusCode']=500;
        $this->_responseData['success'] = false;
        $this->addMessage("Response Creation Error");
        $this->_responseData['messages'] = $this->_messages;
    }
    else {
        http_response_code($this->_httpStatusCode);
        $this->_responseData['statusCode'] = $this->_httpStatusCode;
        $this->_responseData['success'] = $this->_success;
        $this->_responseData['messages'] = $this->_messages;
        $this->_responseData['data'] = $this->_data;

    }
    echo json_encode($this->_responseData); 
    }
}
