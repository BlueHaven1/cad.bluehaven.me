<?php
// includes/adminsidebar.php
?>

<aside class="bg-gray-800 text-white w-full md:w-64 min-h-screen p-4 fixed md:relative top-0 left-0">
  <h2 class="text-xl font-bold mb-6">Admin Panel</h2>
  <nav class="space-y-2">
    <a href="/admin/index.php" class="block px-3 py-2 rounded hover:bg-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-gray-700' : '' ?>">
      Dashboard
    </a>
    <a href="/admin/users.php" class="block px-3 py-2 rounded hover:bg-gray-700 <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'bg-gray-700' : '' ?>">
      Users
    </a>
  </nav>
</aside>
