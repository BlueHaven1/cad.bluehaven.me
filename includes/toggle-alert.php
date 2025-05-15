<?php
require_once 'supabase.php';

$type = $_POST['type'] ?? '';
if (!in_array($type, ['signal100', '10-3'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
    exit;
}

// Get current status
[$res] = supabaseRequest("alerts?type=eq.$type", "GET");
$alerts = json_decode($res, true);
if (!$alerts || empty($alerts[0])) {
    echo json_encode(['success' => false, 'error' => 'Alert not found']);
    exit;
}

$id = $alerts[0]['id'];
$newStatus = !$alerts[0]['status'];

// Update status
$data = ['status' => $newStatus];
[$updateRes, $error] = supabaseRequest("alerts?id=eq.$id", "PATCH", $data);

if ($error) {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
} else {
    echo json_encode(['success' => true]);
}
