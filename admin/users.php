<?php
// admin/users.php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit;
}

// Handle denial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deny_user_id'])) {
    supabaseRequest("users?id=eq.{$_POST['deny_user_id']}", 'DELETE');
}

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_user_id'])) {
    $userId = $_POST['approve_user_id'];
    $departments = $_POST['departments'] ?? [];
    $role = $_POST['role'] ?? 'member';

    $body = [
        'is_approved' => true,
        'departments' => $departments,
        'role' => $role
    ];

    supabaseRequest("users?id=eq.$userId", 'PATCH', $body);
}

// Fetch pending + approved users
[$pendingResponse] = supabaseRequest('users?is_approved=eq.false', 'GET');
$pendingUsers = json_decode($pendingResponse, true);

[$approvedResponse] = supabaseRequest('users?is_approved=eq.true', 'GET');
$approvedUsers = json_decode($approvedResponse, true);

$departments = ['SAHP', 'BCSO', 'LSPD', 'SACO', 'SAFR'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    let currentUserId = '';

    function openModal(userId) {
      currentUserId = userId;
      document.getElementById('approve_user_id').value = userId;
      document.getElementById('approvalModal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('approvalModal').classList.add('hidden');
    }
  </script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
  <?php include '../includes/adminsidebar.php'; ?>

  <div class="flex-1 ml-0 md:ml-64">
    <?php include '../includes/header.php'; ?>

    <div class="p-6">
      <h1 class="text-2xl font-bold mb-6">Pending User Approvals</h1>
      <div class="space-y-4">
        <?php foreach ($pendingUsers as $user): ?>
          <div class="bg-gray-800 p-4 rounded-xl flex items-center justify-between">
            <div>
              <p class="font-semibold"><?= htmlspecialchars($user['username']) ?></p>
              <p class="text-sm text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div class="flex gap-2">
              <button onclick="openModal('<?= $user['id'] ?>')" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">Approve</button>
              <form method="POST">
                <input type="hidden" name="deny_user_id" value="<?= $user['id'] ?>">
                <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">Deny</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($pendingUsers)): ?>
          <p class="text-gray-400">No pending users.</p>
        <?php endif; ?>
      </div>

      <h2 class="text-2xl font-bold mt-10 mb-4">Approved Users</h2>
      <div class="space-y-4">
        <?php foreach ($approvedUsers as $user): ?>
          <div class="bg-gray-800 p-4 rounded-xl flex items-center justify-between">
            <div>
              <p class="font-semibold"><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role'] ?? 'member') ?>)</p>
              <p class="text-sm text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
              <p class="text-xs text-gray-400">Departments: <?= implode(', ', $user['departments'] ?? []) ?></p>
            </div>
            <a href="edit-user.php?id=<?= $user['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded">Edit</a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($approvedUsers)): ?>
          <p class="text-gray-400">No approved users.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Approval Modal -->
  <div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-gray-800 p-6 rounded-xl w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Approve User</h2>
      <form method="POST">
        <input type="hidden" name="approve_user_id" id="approve_user_id">

        <label class="block mb-2">Departments</label>
        <div class="flex flex-wrap gap-2 mb-4">
          <?php foreach ($departments as $dept): ?>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="departments[]" value="<?= $dept ?>" class="accent-blue-500">
              <span><?= $dept ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <label class="block mb-2">Role</label>
        <select name="role" required class="w-full bg-gray-700 text-white px-4 py-2 rounded mb-6">
          <option value="member">Member</option>
          <option value="admin">Admin</option>
          <option value="superadmin">Superadmin</option>
        </select>

        <div class="flex justify-between">
          <button type="button" onclick="closeModal()" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded">Cancel</button>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">Confirm Approval</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
