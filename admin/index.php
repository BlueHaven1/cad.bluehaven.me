<?php
// admin/index.php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch all users
[$response, $status] = supabaseRequest('users', 'GET');
$users = $status === 200 ? json_decode($response, true) : [];

$total = count($users);
$approved = count(array_filter($users, fn($u) => $u['is_approved']));
$pending = $total - $approved;

// Optional: Department breakdown
$deptCounts = [];
foreach ($users as $user) {
    if (!empty($user['departments'])) {
        foreach ($user['departments'] as $dept) {
            $deptCounts[$dept] = ($deptCounts[$dept] ?? 0) + 1;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
  <?php include '../includes/header.php'; ?>

  <div class="p-6">
    <h1 class="text-3xl font-bold mb-4">Welcome, <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
      <div class="bg-gray-800 p-6 rounded-xl text-center">
        <h2 class="text-lg font-semibold mb-2">Total Users</h2>
        <p class="text-3xl font-bold text-blue-400"><?= $total ?></p>
      </div>
      <div class="bg-gray-800 p-6 rounded-xl text-center">
        <h2 class="text-lg font-semibold mb-2">Approved Users</h2>
        <p class="text-3xl font-bold text-green-400"><?= $approved ?></p>
      </div>
      <div class="bg-gray-800 p-6 rounded-xl text-center">
        <h2 class="text-lg font-semibold mb-2">Pending Users</h2>
        <p class="text-3xl font-bold text-yellow-400"><?= $pending ?></p>
      </div>
    </div>

    <?php if (!empty($deptCounts)): ?>
      <h2 class="text-2xl font-bold mt-10 mb-4">Users per Department</h2>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <?php foreach ($deptCounts as $dept => $count): ?>
          <div class="bg-gray-800 p-4 rounded-xl text-center">
            <h3 class="font-semibold"><?= $dept ?></h3>
            <p class="text-xl text-blue-300"><?= $count ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
