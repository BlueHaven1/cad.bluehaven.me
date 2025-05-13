<?php
session_start();

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
