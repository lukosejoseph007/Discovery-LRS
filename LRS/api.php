<?php
// api.php

require 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, x-experience-api-version');

// Function to generate a 6-digit incremental ID
function generateUUID() {
    $counterFile = 'counter.txt';

    // Open the file for reading and writing
    $fileHandle = fopen($counterFile, 'c+');

    if ($fileHandle === false) {
        throw new Exception("Could not open the counter file.");
    }

    // Lock the file to prevent simultaneous access
    flock($fileHandle, LOCK_EX);

    // Read the current number from the file
    $currentNumber = (int)fread($fileHandle, filesize($counterFile) ?: 1);

    // Increment the number by 1
    $nextNumber = $currentNumber + 1;

    // Pad the number with leading zeros to make it 6 digits
    $formattedNumber = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

    // Rewind the file pointer and write the new number
    ftruncate($fileHandle, 0);
    rewind($fileHandle);
    fwrite($fileHandle, $formattedNumber);

    // Unlock the file and close it
    flock($fileHandle, LOCK_UN);
    fclose($fileHandle);

    return $formattedNumber;
}

// Check the request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Log the incoming request
    error_log("Received input: " . print_r($input, true), 3, "logs/api.log");

    // Validate input data - Remove statement_id and make result optional
    if (isset($input['actor'], $input['verb'], $input['object'], $input['timestamp'])) {
        // Generate a unique statement ID
        $statement_id = generateUUID();
        $actor = json_encode($input['actor']);
        $verb = json_encode($input['verb']);
        $object = json_encode($input['object']);
        $result = isset($input['result']) ? json_encode($input['result']) : null;
        $timestamp = $input['timestamp'];
        // Fetch courseName and courseId from context
        $context = isset($input['context']) ? json_encode($input['context']) : null;
        $courseName = null;
        $courseId = null;

        if ($context) {
            $contextArray = json_decode($context, true);
            if (isset($contextArray['contextActivities'])) {
                $contextActivities = $contextArray['contextActivities'];
                if (isset($contextActivities['parent'][0]['id'])) {
                    $courseId = $contextActivities['parent'][0]['id'];
                    if (isset($contextActivities['parent'][0]['definition']['name']['en-US'])) {
                        $courseName = $contextActivities['parent'][0]['definition']['name']['en-US'];
                    } else {
                        $courseName = null;
                    }
                } else {
                    $courseId = null;
                    $courseName = null;
                }
            } else {
                $courseId = null;
                $courseName = null;
            }
        } else {
            $courseId = null;
            $courseName = null;
        }

        try {
            // Prepare and execute SQL statement
            $sql = "INSERT INTO statements (statement_id, actor, verb, object, result, timestamp, context, courseName, courseId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$statement_id, $actor, $verb, $object, $result, $timestamp, $context, $courseName, $courseId]);

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
