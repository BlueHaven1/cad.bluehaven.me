<?php
// admin/settings.php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: ../login.php");
    exit;
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['departments'])) {
    foreach ($_POST['departments'] as $id => $newName) {
        if (!empty($newName)) {
            supabaseRequest("departments?id=eq.$id", 'PATCH', ['name' => $newName]);
        }
    }
}

// Fetch current departments
[$response, $status] = supabaseRequest("departments", "GET");
$departments = $status === 200 ? json_decode($response, true) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
  <?php include '../includes/adminsidebar.php'; ?>

  <div class="flex-1 flex flex-col">
    <?php include '../includes/header.php'; ?>

    <div class="p-6 flex flex-col items-center">
      <div class="w-full max-w-2xl bg-gray-800 p-6 rounded-xl">
        <h1 class="text-2xl font-bold mb-6">Edit Departments</h1>

        <form method="POST">
          <?php foreach ($departments as $dept): ?>
            <div class="mb-4">
              <label class="block mb-1 font-semibold">Department ID: <?= $dept['id'] ?></label>
              <input type="text" name="departments[<?= $dept['id'] ?>]" value="<?= htmlspecialchars($dept['name']) ?>" class="w-full bg-gray-700 text-white px-4 py-2 rounded">
            </div>
          <?php endforeach; ?>

          <div class="mt-6 text-right">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded text-white font-semibold">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
