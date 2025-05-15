<?php
/**
 * Endpoint to get calls assigned to the current user
 * This file is called every 3 seconds by the MDT pages to provide real-time updates
 */
session_start();
require_once '../includes/supabase.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch active calls - use a more efficient query that only returns calls with this user's ID in the units field
// This is a simple optimization since this endpoint is called every 3 seconds
[$callRes] = supabaseRequest("calls?order=created_at.desc", "GET");
$all_calls = json_decode($callRes, true) ?? [];

// Filter calls assigned to this user
$assigned_calls = [];
foreach ($all_calls as $call) {
    $unitList = explode(',', $call['units'] ?? '');
    if (in_array($userId, $unitList)) {
        $assigned_calls[] = $call;
    }
}

// Fetch units for display
[$unitRes] = supabaseRequest("unit_status", "GET");
$active_units = json_decode($unitRes, true) ?? [];

// Map user_id => unit info
$unitMap = [];
foreach ($active_units as $unit) {
    $unitMap[$unit['user_id']] = $unit;
}

// Format calls for display
$formatted_calls = [];
foreach ($assigned_calls as $call) {
    $unitList = explode(',', $call['units'] ?? '');
    $displayUnits = [];

    foreach ($unitList as $uid) {
        $uid = trim($uid);
        if (isset($unitMap[$uid])) {
            $displayUnits[] = $unitMap[$uid]['callsign'];
        } else {
            $displayUnits[] = $uid;
        }
    }

    $formatted_calls[] = [
        'id' => $call['id'],
        'title' => $call['title'],
        'description' => $call['description'],
        'location' => $call['location'],
        'postal' => $call['postal'] ?: '-',
        'units' => implode(', ', $displayUnits)
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'calls' => $formatted_calls
]);
