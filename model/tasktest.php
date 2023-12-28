<?php

require_once('task.php');

try {
$task = new Task(1, "Title here", "descprition here","01/01/2019 12:40:25", "N");
header('Content-Type: application/json; charset=uft-8');
echo json_encode($task->returnTaskAsArray());
}
catch (TaskException $ex) {
    echo "Error: ".$ex->getMessage();
}