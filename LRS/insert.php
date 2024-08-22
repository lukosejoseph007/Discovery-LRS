<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $statement_id = $_POST['statement_id'];
    $actor = $_POST['actor'];
    $verb = $_POST['verb'];
    $object = $_POST['object'];
    $context = $_POST['context'];
    $result = $_POST['result']; // Add this line to get the result from the form

    // Update your SQL to include the result field
    $sql = "INSERT INTO statements (statement_id, actor, verb, object, context, result) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$statement_id, $actor, $verb, $object, $context, $result]);

    // Redirect to retrieve.php after successful insertion
    header('Location: retrieve.php');
    exit;
}
?>