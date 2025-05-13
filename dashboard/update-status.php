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
$callsign = $_SESSION['callsign'] ?? null;
$department = $_SESSION['department'] ?? null;

if (!$callsign || !$department) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing callsign or department']);
    exit;
}

// Check if a unit_status record already exists
[$resp, $code] = supabaseRequest("unit_status?user_id=eq.$userId", "GET");
$existing = json_decode($resp, true);

if ($code === 200 && count($existing) > 0) {
    // Update existing record
    $patchData = [
        'status' => $newStatus,
        'updated_at' => date('c')
    ];
    supabaseRequest("unit_status?user_id=eq.$userId", "PATCH", $patchData);
} else {
    // Insert new record
    $postData = [[
        'user_id' => $userId,
        'callsign' => $callsign,
        'department' => $department,
        'status' => $newStatus
    ]];
    supabaseRequest("unit_status", "POST", $postData);
}

// Update session
$_SESSION['status'] = $newStatus;

echo json_encode(['success' => true, 'newStatus' => $newStatus]);
