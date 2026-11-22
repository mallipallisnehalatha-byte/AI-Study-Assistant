
<?php

header("Content-Type: application/json");

// Allow only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "reply" => "Invalid request method."
    ]);
    exit;
}

// Get the message from the frontend
$message = trim($_POST["message"] ?? "");

// Validate message
if ($message === "" || strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode([
        "reply" => "Please enter a valid message."
    ]);
    exit;
}

// Forward the request to external_ai.php
$_POST["message"] = $message;

require_once __DIR__ . "/external_ai.php";

?>
