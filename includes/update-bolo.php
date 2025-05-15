<?php
session_start();
require_once 'supabase.php';

// Check if user is logged in and is from SACO
if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active']) || ($_SESSION['department'] ?? '') !== 'SACO') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get form data
$id = $_POST['id'] ?? '';
$type = $_POST['type'] ?? '';
$description = $_POST['description'] ?? '';
$last_seen = $_POST['last_seen'] ?? '';

// Validate required fields
if (empty($id) || empty($type) || empty($description) || empty($last_seen)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Update BOLO
$data = [
    'type' => $type,
    'description' => $description,
    'last_seen' => $last_seen,
    'updated_at' => date('c')
];

[$response, $httpCode] = supabaseRequest("bolos?id=eq.$id", "PATCH", $data);

// Check if successful
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update BOLO']);
}
