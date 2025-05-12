<?php
// admin/edit-user.php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit;
}

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header("Location: users.php");
    exit;
}

[$userResponse, $userStatus] = supabaseRequest("users?id=eq.$userId", 'GET');
$user = ($userStatus === 200 && count(json_decode($userResponse, true)) > 0) ? json_decode($userResponse, true)[0] : null;

if (!$user) {
    echo "User not found.";
    exit;
}

$allDepartments = ['SAHP', 'BCSO', 'LSPD', 'SACO', 'SAFR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $departments = $_POST['departments'] ?? [];

    $updateData = [
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'departments' => $departments
    ];

    supabaseRequest("users?id=eq.$userId", 'PATCH', $updateData);
    header("Location: users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
  <?php include '../includes/header.php'; ?>

  <div class="max-w-xl mx-auto bg-gray-800 p-6 rounded-xl mt-10">
    <h1 class="text-2xl font-bold mb-4">Edit User</h1>
    <form method="POST">
      <label class="block mb-2">Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full bg-gray-700 text-white px-4 py-2 rounded mb-4">

      <label class="block mb-2">Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full bg-gray-700 text-white px-4 py-2 rounded mb-4">

      <label class="block mb-2">Departments</label>
      <div class="flex flex-wrap gap-3 mb-4">
        <?php foreach ($allDepartments as $dept): ?>
          <label class="flex items-center space-x-2">
            <input type="checkbox" name="departments[]" value="<?= $dept ?>"
              <?= in_array($dept, $user['departments'] ?? []) ? 'checked' : '' ?>
              class="accent-blue-500">
            <span><?= $dept ?></span>
          </label>
        <?php endforeach; ?>
      </div>

      <label class="block mb-2">Role</label>
      <select name="role" class="w-full bg-gray-700 text-white px-4 py-2 rounded mb-6">
        <option value="member" <?= $user['role'] === 'member' ? 'selected' : '' ?>>Member</option>
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
      </select>

      <div class="flex justify-between">
        <a href="users.php" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded">Cancel</a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">Save Changes</button>
      </div>
    </form>
  </div>
</body>
</html>
