<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unauthorized or missing data']);
    exit;
}

$userId = $_SESSION['user_id'];
$newStatus = $_POST['status'];

// Update unit_status table
$data = [
  'status' => $newStatus,
  'updated_at' => date('c')
];
supabaseRequest("unit_status?user_id=eq.$userId", "PATCH", $data);

// Update session
$_SESSION['status'] = $newStatus;

echo json_encode(['success' => true, 'newStatus' => $newStatus]);
