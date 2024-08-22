<?php
// api.php

require 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Allow certain methods
header('Access-Control-Allow-Headers: Content-Type, Authorization, x-experience-api-version'); // Allow specific headers

// Check the request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input data
    if (isset($input['statement_id'], $input['actor'], $input['verb'], $input['object'], $input['result'], $input['timestamp'])) {
        $statement_id = $input['statement_id'];
        $actor = json_encode($input['actor']);  // Convert array to JSON string
        $verb = json_encode($input['verb']);    // Convert array to JSON string
        $object = json_encode($input['object']); // Convert array to JSON string
        $result = json_encode($input['result']); // Convert array to JSON string
        $timestamp = $input['timestamp'];

        try {
            // Prepare and execute SQL statement
            $sql = "INSERT INTO statements (statement_id, actor, verb, object, result, timestamp) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$statement_id, $actor, $verb, $object, $result, $timestamp]);

            echo json_encode(['status' => 'success', 'message' => 'Statement inserted successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
