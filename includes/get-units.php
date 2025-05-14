<?php
require_once '../includes/supabase.php';

[$res] = supabaseRequest("unit_status", "GET");
$units = json_decode($res, true);

header('Content-Type: application/json');
echo json_encode(array_map(function($u) {
  return [
    'callsign' => $u['callsign'] ?? 'None',
    'department' => $u['department'] ?? 'Unknown',
    'status' => $u['status'] ?? '10-7',
  ];
}, $units ?? []));
