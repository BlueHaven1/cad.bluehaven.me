<?php
require_once 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method');
}

$id = $_POST['id'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$postal = trim($_POST['postal'] ?? '');

if (!$id || !$title || !$description || !$location) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$data = [
    'title' => $title,
    'description' => $description,
    'location' => $location,
    'postal' => $postal
];

[$res, $code] = supabaseRequest("calls?id=eq.$id", "PATCH", $data);

if ($code >= 200 && $code < 300) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update']);
}
