<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['department'] ?? '') !== 'SACO') {
  header("Location: ../patrol.php");
  exit;
}

$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');

if ($description && $location) {
  $newCall = [
    'description' => $description,
    'location' => $location,
    'status' => 'open'
  ];

  [$res, $code] = supabaseRequest('calls', 'POST', [$newCall]);

  if ($code === 201 || $code === 200) {
    header('Location: saco-mdt.php?success=1');
    exit;
  } else {
    header('Location: saco-mdt.php?error=1');
    exit;
  }
} else {
  header('Location: saco-mdt.php?error=1');
  exit;
}
