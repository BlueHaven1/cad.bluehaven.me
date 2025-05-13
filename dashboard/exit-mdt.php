<?php
session_start();
require_once '../includes/supabase.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Remove unit_status for this user
    supabaseRequest("unit_status?user_id=eq.$userId", "DELETE");
}

// Unset MDT-related session variables
unset(
    $_SESSION['callsign'],
    $_SESSION['department'],
    $_SESSION['department_id'],
    $_SESSION['status'],
    $_SESSION['mdt_active']
);

header("Location: /dashboard.php");
exit;
