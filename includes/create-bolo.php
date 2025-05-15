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
$type = $_POST['type'] ?? '';
$description = $_POST['description'] ?? '';
$last_seen = $_POST['last_seen'] ?? '';

// Validate required fields
if (empty($type) || empty($description) || empty($last_seen)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Create BOLO
$data = [
    'type' => $type,
    'description' => $description,
    'last_seen' => $last_seen,
    'created_by' => $_SESSION['user_id']
];

[$response, $httpCode] = supabaseRequest("bolos", "POST", [$data]);

// Check if successful
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create BOLO']);
}
