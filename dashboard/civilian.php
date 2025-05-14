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

// SAFR or LEO dashboard link
$dashboard_link = ($_SESSION['active_mdt'] ?? '') === 'safr' ? 'safr-mdt.php' : 'mdt.php';

$civId = $_GET['id'] ?? null;
if (!$civId) {
    echo "No civilian ID provided.";
    exit;
}

// Fetch data
[$civRes] = supabaseRequest("civilians?id=eq.$civId", "GET");
$civilian = json_decode($civRes, true)[0] ?? null;
if (!$civilian) {
    echo "Civilian not found.";
    exit;
}

[$licRes] = supabaseRequest("civilian_licenses?civilian_id=eq.$civId", "GET");
$licenses = json_decode($licRes, true);

[$vehRes] = supabaseRequest("civilian_vehicles?civilian_id=eq.$civId", "GET");
$vehicles = json_decode($vehRes, true);

[$weapRes] = supabaseRequest("civilian_weapons?civilian_id=eq.$civId", "GET");
$weapons = json_decode($weapRes, true);

[$citationsRes] = supabaseRequest("citations?civilian_id=eq.$civId", "GET");
$citations = json_decode($citationsRes, true);

[$warningsRes] = supabaseRequest("written_warnings?civilian_id=eq.$civId", "GET");
$warnings = json_decode($warningsRes, true);

[$arrestsRes] = supabaseRequest("arrest_reports?civilian_id=eq.$civId", "GET");
$arrests = json_decode($arrestsRes, true);

[$warrantsRes] = supabaseRequest("warrants?civilian_id=eq.$civId", "GET");
$warrants = json_decode($warrantsRes, true);

[$titlesResp] = supabaseRequest("penal_titles", "GET");
$penal_titles = json_decode($titlesResp, true) ?? [];

[$sectionsResp] = supabaseRequest("penal_sections", "GET");
$penal_sections = json_decode($sectionsResp, true) ?? [];

$sections_by_title = [];
foreach ($penal_titles as $title) {
    $tid = $title['id'];
    $sections_by_title[$tid] = array_filter($penal_sections, fn($s) => $s['title_id'] == $tid);
}

[$res] = supabaseRequest("ten_codes?id=eq.1", "GET");
$data = json_decode($res, true);
$content = $data[0]['content'] ?? '<p>No 10-Codes available.</p>';
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
      <a href="<?= $dashboard_link ?>" class="block px-3 py-2 rounded hover:bg-gray-700">Dashboard</a>
      <a href="name-search.php" class="block px-3 py-2 rounded hover:bg-gray-700">Name Search</a>
      <a href="plate-search.php" class="block px-3 py-2 rounded hover:bg-gray-700">Plate Search</a>
      <?php if (($_SESSION['active_mdt'] ?? '') !== 'safr'): ?>
        <a href="citation.php" class="block px-3 py-2 rounded hover:bg-gray-700">Citation</a>
        <a href="warning.php" class="block px-3 py-2 rounded hover:bg-gray-700">Written Warning</a>
        <a href="arrest.php" class="block px-3 py-2 rounded hover:bg-gray-700">Arrest Report</a>
        <a href="file-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">File Warrant</a>
        <a href="serve-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">Serve Warrant</a>
      <?php endif; ?>
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
            <li><strong><?= htmlspecialchars($lic['license_type']) ?>:</strong> <?= htmlspecialchars($lic['status']) ?></li>
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
              <p><strong><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></strong></p>
              <p>Plate: <?= htmlspecialchars($v['plate']) ?> | Color: <?= htmlspecialchars($v['color']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No vehicles found.</p>
      <?php endif; ?>
    </div>

    <!-- Weapons -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mb-8">
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

    <!-- Citations -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mt-8">
      <h2 class="text-xl font-semibold mb-4">Citations</h2>
      <?php if (!empty($citations)): ?>
        <div class="space-y-2">
          <?php foreach ($citations as $c): ?>
            <div class="bg-gray-700 rounded p-3">
              <p><strong>Violation:</strong> <?= htmlspecialchars($c['violation']) ?></p>
              <p><strong>Fine:</strong> $<?= number_format($c['fine']) ?></p>
              <p><strong>Location:</strong> <?= htmlspecialchars($c['location']) ?></p>
              <p><strong>Officer:</strong> <?= htmlspecialchars($c['signature']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No citations found.</p>
      <?php endif; ?>
    </div>

<!-- Written Warnings -->
<div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mt-8">
  <h2 class="text-xl font-semibold mb-4">Written Warnings</h2>
  <?php
    $validWarnings = array_filter($warnings, fn($w) => !empty($w['violation']) || !empty($w['notes']));
  ?>
  <?php if (!empty($validWarnings)): ?>
    <div class="space-y-2">
      <?php foreach ($validWarnings as $w): ?>
        <div class="bg-gray-700 rounded p-3">
          <p><strong>Reason:</strong> <?= htmlspecialchars($w['violation']) ?></p>
          <p><strong>Notes:</strong> <?= htmlspecialchars($w['notes']) ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($w['location'] ?? 'N/A') ?></p>
          <p><strong>Officer:</strong> <?= htmlspecialchars($w['signature'] ?? 'Unknown') ?></p>
          <p><strong>Date:</strong> <?= htmlspecialchars($w['created_at'] ?? 'N/A') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-gray-400">No written warnings found.</p>
  <?php endif; ?>
</div>



    <!-- Arrest Reports -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mt-8">
      <h2 class="text-xl font-semibold mb-4">Arrest Reports</h2>
      <?php if (!empty($arrests)): ?>
        <div class="space-y-2">
          <?php foreach ($arrests as $a): ?>
            <div class="bg-gray-700 rounded p-3">
              <p><strong>Charges:</strong> <?= htmlspecialchars($a['charges']) ?></p>
              <p><strong>Location:</strong> <?= htmlspecialchars($a['location']) ?></p>
              <p><strong>Officer:</strong> <?= htmlspecialchars($a['signature']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No arrest reports found.</p>
      <?php endif; ?>
    </div>

    <!-- Warrants -->
    <div class="bg-gray-800 rounded-xl p-6 shadow border border-gray-700 mt-8">
      <h2 class="text-xl font-semibold mb-4">Warrants</h2>
      <?php if (!empty($warrants)): ?>
        <div class="space-y-2">
          <?php foreach ($warrants as $w): ?>
            <div class="bg-gray-700 rounded p-3">
              <p><strong>Violation:</strong> <?= htmlspecialchars($w['violation']) ?></p>
              <p><strong>Fine:</strong> <?= $w['fine'] ? '$' . number_format($w['fine']) : 'N/A' ?></p>
              <p><strong>Jail Time:</strong> <?= $w['jail_time'] ? $w['jail_time'] . ' months' : 'N/A' ?></p>
              <p><strong>Location:</strong> <?= htmlspecialchars($w['location']) ?></p>
              <p><strong>Status:</strong> <?= !empty($w['is_served']) ? '<span class="text-green-400">Served</span>' : '<span class="text-yellow-400">Active</span>' ?></p>
              <p><strong>Officer:</strong> <?= htmlspecialchars($w['signature']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No warrants found.</p>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
