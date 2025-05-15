<?php
require_once 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['type'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$type = $_POST['type'];
$allowed = ['signal100', '10-3'];
if (!in_array($type, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid alert type']);
    exit;
}

// Get current alert status
[$res] = supabaseRequest("alerts?type=eq.$type", "GET");
$data = json_decode($res, true);

if (!$data || !isset($data[0])) {
    echo json_encode(['success' => false, 'error' => 'Alert not found']);
    exit;
}

$alert = $data[0];
$newStatus = !$alert['status'];

// Update alert
$update = [
    'status' => $newStatus
];
$url = "alerts?id=eq." . $alert['id'];
[$res] = supabaseRequest($url, "PATCH", $update);

echo json_encode(['success' => true]);
exit;
