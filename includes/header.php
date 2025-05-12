<?php
// includes/header.php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>

<header class="bg-gray-800 text-white p-4 shadow-md flex items-center justify-between">
  <div class="flex items-center space-x-4">
    <img src="/assets/uploads/logo.png" alt="Logo" class="h-10 w-10 rounded-full">
    <span class="text-xl font-semibold">San Andreas Roleplay</span>
  </div>

  <?php if ($isLoggedIn): ?>
    <div>
      <a href="/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-sm font-semibold">
        Logout
      </a>
    </div>
  <?php endif; ?>
</header>
