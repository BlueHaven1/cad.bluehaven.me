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

// Get BOLO ID
$id = $_POST['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'BOLO ID is required']);
    exit;
}

// Delete BOLO
[$response, $httpCode] = supabaseRequest("bolos?id=eq.$id", "DELETE");

// Check if successful
if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete BOLO']);
}
