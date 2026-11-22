
<?php
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["reply" => "Invalid request method."]);
    exit;
}

// Get the message from the frontend
$message = trim($_POST["message"] ?? "");

if ($message === "") {
    http_response_code(400);
    echo json_encode(["reply" => "Please enter a message."]);
    exit;
}

// Get NVIDIA API key from environment
$apiKey = getenv("NVIDIA_API_KEY");

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        "reply" => "NVIDIA API key is not configured."
    ]);
    exit;
}

// NVIDIA API endpoint
$url = "https://integrate.api.nvidia.com/v1/chat/completions";

// Request data
$data = [
    "model" => "nvidia/nemotron-3.5-lightning-30b-a3b",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a helpful AI Study Assistant. Explain academic topics in simple language."
        ],
        [
            "role" => "user",
            "content" => $message
        ]
    ],
    "temperature" => 0.7,
    "max_tokens" => 1024,
    "stream" => false
];

// Initialize cURL
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json",
        "Accept: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$error = curl_error($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

// Handle connection errors
if ($response === false) {
    http_response_code(500);
    echo json_encode(["reply" => "Connection error: " . $error]);
    exit;
}

// Decode NVIDIA response
$result = json_decode($response, true);

if ($statusCode !== 200) {
    http_response_code(500);
    echo json_encode([
        "reply" => $result["error"]["message"]
            ?? "NVIDIA API request failed. HTTP status: " . $statusCode
    ]);
    exit;
}

// Return AI answer
$answer = $result["choices"][0]["message"]["content"] ?? "";

echo json_encode([
    "reply" => $answer !== "" ? $answer : "No response received."
]);
?>
