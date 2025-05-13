<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active'])) {
    header("Location: ../patrol.php");
    exit;
}

$status = $_SESSION['status'] ?? '10-7';
$username = $_SESSION['username'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'N/A';
$callsign = $_SESSION['callsign'] ?? 'None';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MDT - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex min-h-screen">

  <!-- Sidebar -->
  <aside class="w-64 bg-gray-800 p-4 flex flex-col justify-between fixed h-full">
    <div>
      <h2 class="text-2xl font-bold mb-6">MDT</h2>
      <nav class="space-y-2">
        <a href="mdt.php" class="block px-3 py-2 rounded hover:bg-gray-700 bg-gray-700">Dashboard</a>
        <a href="name-search.php" class="block px-3 py-2 rounded hover:bg-gray-700">Name Search</a>
        <a href="plate-search.php" class="block px-3 py-2 rounded hover:bg-gray-700">Plate Search</a>
        <a href="citation.php" class="block px-3 py-2 rounded hover:bg-gray-700">Citation</a>
        <a href="warning.php" class="block px-3 py-2 rounded hover:bg-gray-700">Written Warning</a>
        <a href="arrest.php" class="block px-3 py-2 rounded hover:bg-gray-700">Arrest Report</a>
        <a href="file-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">File Warrant</a>
        <a href="serve-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">Serve Warrant</a>
        <a href="penal-code.php" class="block px-3 py-2 rounded hover:bg-gray-700">Penal Code</a>
        <a href="10-codes.php" class="block px-3 py-2 rounded hover:bg-gray-700">10-Codes</a>
      </nav>
    </div>
    <a href="exit-mdt.php" class="block px-3 py-2 mt-6 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">
      Exit MDT
    </a>
  </aside>

  <!-- Main Content -->
  <main class="ml-64 p-6 w-full">
    <h1 class="text-3xl font-bold mb-6">MDT Dashboard</h1>

    <div class="bg-gray-800 rounded-xl p-6 shadow w-full max-w-3xl">
      <h2 class="text-xl font-semibold mb-4">Unit Information</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-300">
        <div>
          <p class="text-sm text-gray-400">Username</p>
          <p class="text-lg font-semibold text-white"><?= htmlspecialchars($username) ?></p>
        </div>

        <div>
          <p class="text-sm text-gray-400">Department</p>
          <p class="text-lg font-semibold text-white"><?= htmlspecialchars($department) ?></p>
        </div>

        <div>
          <p class="text-sm text-gray-400">Callsign</p>
          <p class="text-lg font-semibold text-white"><?= htmlspecialchars($callsign) ?></p>
        </div>

        <div>
          <p class="text-sm text-gray-400">Status</p>
          <p id="currentStatus" class="text-lg font-semibold text-green-400 mb-2"><?= htmlspecialchars($status) ?></p>

          <div class="flex flex-wrap gap-2 mt-2">
            <?php if ($status === '10-7'): ?>
              <button onclick="updateStatus('10-8')" class="bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded">Go 10-8</button>
            <?php else: ?>
              <?php
              $options = ['10-6', '10-15', '10-23', '10-97', '10-7'];
              foreach ($options as $opt): ?>
                <button onclick="updateStatus('<?= $opt ?>')" class="bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded"><?= $opt ?></button>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- AJAX Script -->
  <script>
    function updateStatus(status) {
      fetch('update-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'status=' + encodeURIComponent(status)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('currentStatus').textContent = data.newStatus;
          location.reload();
        } else {
          alert('Failed to update status.');
        }
      });
    }
  </script>
</body>
</html>
