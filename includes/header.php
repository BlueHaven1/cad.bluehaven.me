<?php
// includes/header.php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
?>

<header class="bg-gray-800 text-white p-4 shadow-md flex items-center justify-between">
  <div class="flex items-center space-x-6">
    <img src="/assets/uploads/logo.png" alt="Logo" class="h-10 w-10 rounded-full">
    <span class="text-xl font-semibold">San Andreas Roleplay</span>

<?php if ($isLoggedIn): ?>
  <a href="/dashboard.php" class="hover:underline text-sm">Dashboard</a>
  <a href="/patrol.php" class="hover:underline text-sm">Patrol</a>
  <?php if (in_array($role, ['admin', 'superadmin'])): ?>
    <a href="/admin/index.php" class="hover:underline text-sm">Admin Panel</a>
  <?php endif; ?>
<?php endif; ?>

    
  </div>

  <?php if ($isLoggedIn): ?>
    <div>
      <a href="/logout.php" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-sm font-semibold">
        Logout
      </a>
    </div>
  <?php endif; ?>
</header>
