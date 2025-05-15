<?php
session_start();
require_once 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$postal = trim($_POST['postal'] ?? '');
$units = $_POST['units'] ?? [];

if (!$title || !$description || !$location) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Convert units to comma-separated string
$unitList = is_array($units) ? implode(',', array_map('trim', $units)) : null;

$data = [
    "title" => $title,
    "description" => $description,
    "location" => $location,
    "postal" => $postal,
    "units" => $unitList
];

[$response, $httpCode] = supabaseRequest("calls", "POST", $data);

// Decode the response and check if valid JSON
$result = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && is_array($result) && count($result) > 0) {
    echo json_encode(['success' => true]);
} else {
    // Debugging optional
    file_put_contents('debug.log', print_r([$response, $httpCode], true));
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create call']);
}
