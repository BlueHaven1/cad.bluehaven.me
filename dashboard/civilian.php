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

$civId = $_GET['id'] ?? null;

if (!$civId) {
    echo "No civilian ID provided.";
    exit;
}

// Get civilian
[$civRes] = supabaseRequest("civilians?id=eq.$civId", "GET");
$civilian = json_decode($civRes, true)[0] ?? null;

if (!$civilian) {
    echo "Civilian not found.";
    exit;
}

// Get licenses
[$licRes] = supabaseRequest("civilian_licenses?civilian_id=eq.$civId", "GET");
$licenses = json_decode($licRes, true);

// Get vehicles
[$vehRes] = supabaseRequest("civilian_vehicles?civilian_id=eq.$civId", "GET");
$vehicles = json_decode($vehRes, true);

// Get weapons
[$weapRes] = supabaseRequest("civilian_weapons?civilian_id=eq.$civId", "GET");
$weapons = json_decode($weapRes, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($civilian['name']) ?> - Civilian Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-gray-800 p-4 flex flex-col justify-between fixed h-full">
  <div>
    <h2 class="text-2xl font-bold mb-6">MDT</h2>
    <nav class="space-y-2">
      <a href="mdt.php" class="block px-3 py-2 rounded hover:bg-gray-700">Dashboard</a>
      <a href="name-search.php" class="block px-3 py-2 rounded hover:bg-gray-700">Name Search</a>
      <a href="plate-search.php" class="block px-3 py-2 rounded hover:bg-gray-700">Plate Search</a>
      <a href="citation.php" class="block px-3 py-2 rounded hover:bg-gray-700">Citation</a>
      <a href="warning.php" class="block px-3 py-2 rounded hover:bg-gray-700">Written Warning</a>
      <a href="arrest.php" class="block px-3 py-2 rounded hover:bg-gray-700">Arrest Report</a>
      <a href="file-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">File Warrant</a>
      <a href="serve-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">Serve Warrant</a>
    </nav>
  </div>
  <a href="exit-mdt.php" class="block px-3 py-2 mt-6 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">
    Exit MDT
  </a>
</aside>

<!-- Main Content -->
<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-4xl font-bold mb-6">Civilian Profile</h1>

    <!-- Civilian Info -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mb-8">
      <h2 class="text-xl font-semibold mb-4">Personal Details</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-gray-300">
        <div><strong>Name:</strong> <?= htmlspecialchars($civilian['name']) ?></div>
        <div><strong>DOB:</strong> <?= htmlspecialchars($civilian['dob']) ?></div>
        <div><strong>Gender:</strong> <?= htmlspecialchars($civilian['gender']) ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars($civilian['phone']) ?></div>
        <div><strong>Address:</strong> <?= htmlspecialchars($civilian['address']) ?></div>
        <div><strong>Height:</strong> <?= htmlspecialchars($civilian['height']) ?></div>
        <div><strong>Weight:</strong> <?= htmlspecialchars($civilian['weight']) ?></div>
        <div><strong>Eye Color:</strong> <?= htmlspecialchars($civilian['eye_color']) ?></div>
        <div><strong>Hair Color:</strong> <?= htmlspecialchars($civilian['hair_color']) ?></div>
      </div>
    </div>

    <!-- Licenses -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mb-8">
      <h2 class="text-xl font-semibold mb-4">Licenses</h2>
      <?php if (!empty($licenses)): ?>
        <ul class="list-disc list-inside space-y-1 text-gray-300">
          <?php foreach ($licenses as $lic): ?>
            <li><strong><?= $lic['license_type'] ?>:</strong> <?= $lic['status'] ?></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-gray-400">No licenses found.</p>
      <?php endif; ?>
    </div>

    <!-- Vehicles -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mb-8">
      <h2 class="text-xl font-semibold mb-4">Vehicles</h2>
      <?php if (!empty($vehicles)): ?>
        <div class="space-y-2">
          <?php foreach ($vehicles as $v): ?>
            <div class="bg-gray-700 rounded p-3">
              <p><strong><?= htmlspecialchars($v['make']) ?> <?= htmlspecialchars($v['model']) ?></strong></p>
              <p>Plate: <?= htmlspecialchars($v['plate']) ?> | Color: <?= htmlspecialchars($v['color']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No vehicles found.</p>
      <?php endif; ?>
    </div>

    <!-- Weapons -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700">
      <h2 class="text-xl font-semibold mb-4">Weapons</h2>
      <?php if (!empty($weapons)): ?>
        <div class="space-y-2">
          <?php foreach ($weapons as $w): ?>
            <div class="bg-gray-700 rounded p-3">
              <p><strong><?= htmlspecialchars($w['model']) ?></strong> | Serial: <?= htmlspecialchars($w['serial']) ?></p>
              <p>Status: <?= $w['is_stolen'] ? '<span class="text-red-500">Stolen</span>' : 'Registered' ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No weapons found.</p>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
