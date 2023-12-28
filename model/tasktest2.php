<?php
// Include your Task class and any other necessary files
require_once('task.php');

// Handle incoming POST request from Postman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming you're sending JSON data in the request body
    $requestData = json_decode(file_get_contents('php://input'), true);

    try {
        // Create a Task object using the provided data
        $task = new Task(
            $requestData['id'],
            $requestData['title'],
            $requestData['description'],
            $requestData['deadline'],
            $requestData['completed']
        );

        // Convert the Task object to an array and send it back as JSON response
        $response = $task->returnTaskAsArray();
        echo json_encode($response);
    } catch (TaskException $e) {
        // Handle any exceptions and send an error response
        http_response_code(400); // Bad Request
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // Handle other types of requests (GET, PUT, DELETE, etc.) if needed
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
}
?>
