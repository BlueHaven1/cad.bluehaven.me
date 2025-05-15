<?php
require_once 'supabase.php';

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'] ?? '';

if (!in_array($type, ['signal100', '10-3'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid alert type']);
  exit;
}

// Check existing
[$res] = supabaseRequest("alerts?type=eq.$type", "GET");
$existing = json_decode($res, true);

if (!empty($existing)) {
  $id = $existing[0]['id'];
  $newStatus = !$existing[0]['status'];
  supabaseRequest("alerts?id=eq.$id", "PATCH", ['status' => $newStatus]);
} else {
  supabaseRequest("alerts", "POST", [
    'type' => $type,
    'status' => true
  ]);
}

echo json_encode(['success' => true]);
