<?php
// dashboard.php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? 'Unknown';
$role = $_SESSION['role'] ?? 'member';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
  <?php include 'includes/header.php'; ?>

  <div class="flex flex-col items-center justify-center mt-20 text-center px-4">
    <img src="/assets/uploads/logo.png" alt="Logo" class="w-32 h-32 rounded-full mb-6">
    <h1 class="text-3xl font-bold mb-2">San Andreas Roleplay</h1>
    <p class="text-lg text-gray-300">
      Welcome, <span class="text-blue-400 font-semibold"><?= htmlspecialchars($username) ?></span> 
      (<?= htmlspecialchars(ucfirst($role)) ?>)
    </p>
    <div class="mt-6">
      <a href="/patrol.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow">
        Start Patrol
      </a>
    </div>
  </div>
</body>
</html>
