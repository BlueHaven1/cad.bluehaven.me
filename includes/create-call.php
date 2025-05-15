<?php
session_start();
require_once 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../saco-mdt.php");
    exit;
}

// Validate required fields
$title = trim($_POST['title'] ?? '');
$location = trim($_POST['location'] ?? '');
$postal = trim($_POST['postal'] ?? '');
$description = trim($_POST['description'] ?? '');
$assigned_units = $_POST['units'] ?? [];

if (!$title || !$location || !$description) {
    die("Missing required fields.");
}

$dispatcher_id = $_SESSION['user_id'] ?? null;

if (!$dispatcher_id) {
    die("Unauthorized.");
}

// Insert the call into `calls` table
$callData = [
    "title" => $title,
    "location" => $location,
    "postal" => $postal,
    "description" => $description,
    "dispatcher_id" => $dispatcher_id
];

[$callRes, $callError] = supabaseRequest("calls", "POST", $callData);
$call = json_decode($callRes, true) ?? null;

if (!$call || !empty($callError) || empty($call['id'])) {
    die("Failed to create call.");
}

$call_id = $call['id'];

// Assign units (if any)
if (is_array($assigned_units)) {
    foreach ($assigned_units as $user_id) {
        $unitData = [
            "call_id" => $call_id,
            "user_id" => $user_id
        ];
        supabaseRequest("call_units", "POST", $unitData);
    }
}

header("Location: ../saco-mdt.php?success=1");
exit;
