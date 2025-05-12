<?php
// dashboard.php
session_start();

// Protect the page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Unknown';
$role = $_SESSION['role'] ?? 'Member'; // Default role until role management is added
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center h-screen">
  <div class="text-center">
    <img src="/assets/uploads/logo.png" alt="Logo" class="w-32 h-32 mx-auto rounded-full mb-6">
    <h1 class="text-3xl font-bold mb-2">San Andreas Roleplay</h1>
    <p class="text-lg text-gray-300">Welcome, <span class="text-blue-400 font-semibold"><?= htmlspecialchars($username) ?></span> (<?= htmlspecialchars(ucfirst($role)) ?>)</p>
  </div>
</body>
</html>
