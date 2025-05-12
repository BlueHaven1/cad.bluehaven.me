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

// Fetch user
[$userResp] = supabaseRequest("users?id=eq.$userId", 'GET');
$user = json_decode($userResp, true)[0] ?? null;

// Fetch all departments
[$deptResp] = supabaseRequest("departments", "GET");
$allDepartments = json_decode($deptResp, true) ?? [];

// Fetch assigned department IDs for this user
[$udResp] = supabaseRequest("user_departments?user_id=eq.$userId", "GET");
$assigned = array_column(json_decode($udResp, true), 'department_id');

// Form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $newPassword = trim($_POST['new_password'] ?? '');
    $selectedDepartments = $_POST['departments'] ?? [];

    // Update user
    $updateData = [
        'username' => $username,
        'email' => $email,
        'role' => $role
    ];
    if (!empty($newPassword)) {
        $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }
    supabaseRequest("users?id=eq.$userId", 'PATCH', $updateData);

    // Clear old links
    supabaseRequest("user_departments?user_id=eq.$userId", "DELETE");

    // Re-insert selected departments
    foreach ($selectedDepartments as $deptId) {
        supabaseRequest("user_departments", "POST", [[
            'user_id' => $userId,
            'department_id' => $deptId
        ]]);
    }

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
<body class="bg-gray-900 text-white min-h-screen flex">
  <?php include '../includes/adminsidebar.php'; ?>
  <div class="flex-1 flex flex-col">
    <?php include '../includes/header.php'; ?>

    <div class="p-6 flex flex-col items-center">
      <div class="w-full max-w-xl bg-gray-800 p-6 rounded-xl">
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
                <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>" <?= in_array($dept['id'], $assigned) ? 'checked' : '' ?> class="accent-blue-500">
                <span><?= htmlspecialchars($dept['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <label class="block mb-2">Role</label>
          <select name="role" class="w-full bg-gray-700 text-white px-4 py-2 rounded mb-4">
            <option value="member" <?= $user['role'] === 'member' ? 'selected' : '' ?>>Member</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
          </select>

          <label class="block mb-2">New Password <span class="text-gray-400 text-sm">(leave blank to keep current)</span></label>
          <input type="password" name="new_password" class="w-full bg-gray-700 text-white px-4 py-2 rounded mb-6">

          <div class="flex justify-between">
            <a href="users.php" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
