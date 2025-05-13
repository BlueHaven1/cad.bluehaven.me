<?php
session_start();
require_once 'includes/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch departments
[$resp, $status] = supabaseRequest('departments', 'GET');
$departments = $status === 200 ? json_decode($resp, true) : [];
$departments = array_filter($departments, fn($d) => strtolower($d['name']) !== 'civilian');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callsign = trim($_POST['callsign'] ?? '');
    $selectedDeptId = $_POST['department'] ?? '';

    // Lookup selected department object
    $selectedDept = array_filter($departments, fn($d) => $d['id'] === $selectedDeptId);
    $selectedDept = array_values($selectedDept)[0] ?? null;

    if (!$selectedDept || empty($callsign)) {
        $error = "Please enter a callsign and select a department.";
    } else {
        $_SESSION['callsign'] = $callsign;
        $_SESSION['department'] = $selectedDept['name']; // for display
        $_SESSION['department_id'] = $selectedDept['id']; // for querying

        // Redirect based on department name
        switch (strtolower($selectedDept['name'])) {
            case 'safr':
                header("Location: /dashboard/safr-mdt.php"); break;
            case 'saco':
                header("Location: /dashboard/saco-mdt.php"); break;
            default:
                header("Location: /dashboard/mdt.php"); break;
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Start Patrol - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
  <?php include 'includes/header.php'; ?>

  <div class="flex items-center justify-center mt-16 px-4">
    <form method="POST" class="bg-gray-800 p-6 rounded-xl w-full max-w-md shadow-lg">
      <h1 class="text-2xl font-bold mb-4 text-center">Start Patrol</h1>

      <?php if (!empty($error)): ?>
        <p class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <label class="block mb-2 font-semibold">Callsign</label>
      <input type="text" name="callsign" required class="w-full px-4 py-2 rounded bg-gray-700 mb-4 text-white">

      <label class="block mb-2 font-semibold">Department</label>
      <select name="department" required class="w-full px-4 py-2 rounded bg-gray-700 text-white mb-6">
        <option value="">Select a Department</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 py-2 rounded text-white font-semibold">
        Enter MDT
      </button>
    </form>
  </div>
</body>
</html>
