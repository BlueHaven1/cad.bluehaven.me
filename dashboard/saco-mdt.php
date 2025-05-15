<?php
session_start();
require_once '../includes/supabase.php';

// Restrict to SACO only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active']) || ($_SESSION['department'] ?? '') !== 'SACO') {
    header("Location: ../patrol.php");
    exit;
}

$_SESSION['active_mdt'] = 'saco';

$status = $_SESSION['status'] ?? '10-7';
$username = $_SESSION['username'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'SACO';
$callsign = $_SESSION['callsign'] ?? 'None';

// Penal code & 10-codes
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

[$unitRes] = supabaseRequest("unit_status", "GET");
$active_units = json_decode($unitRes, true) ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SACO Dispatcher MDT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .scrollbar::-webkit-scrollbar { width: 6px; }
    .scrollbar::-webkit-scrollbar-thumb { background: #4B5563; border-radius: 4px; }
  </style>
</head>
<body class="bg-gray-900 text-white flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-gray-800 p-4 flex flex-col justify-between fixed h-full">
  <div>
    <h2 class="text-2xl font-bold mb-6">SACO MDT</h2>
    <nav class="space-y-2">
      <a href="saco-mdt.php" class="block px-3 py-2 rounded bg-gray-700">Dashboard</a>
    </nav>
  </div>
  <a href="exit-mdt.php" class="block px-3 py-2 mt-6 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">
    Exit MDT
  </a>
</aside>

<!-- Main Content -->
<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-6xl mx-auto">
    <h1 class="text-4xl font-bold mb-8">SACO Dispatcher Dashboard</h1>

    <!-- Dispatcher Info -->
    <div class="bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-700">
      <h2 class="text-2xl font-semibold mb-6">Dispatcher Info</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div><p class="text-sm text-gray-400 uppercase mb-1">Username</p><p class="text-xl font-semibold"><?= htmlspecialchars($username) ?></p></div>
        <div><p class="text-sm text-gray-400 uppercase mb-1">Department</p><p class="text-xl font-semibold">San Andreas Communications</p></div>
        <div><p class="text-sm text-gray-400 uppercase mb-1">Callsign</p><p class="text-xl font-semibold"><?= htmlspecialchars($callsign) ?></p></div>
<div class="col-span-full mt-4 flex justify-between items-start flex-wrap gap-4">
  <!-- Status controls (left) -->
  <div>
    <p class="text-sm text-gray-400 uppercase mb-1">Status</p>
    <div class="flex flex-wrap gap-2 mt-2">
      <?php foreach (['10-8', '10-6', '10-7'] as $opt): ?>
        <button onclick="updateStatus('<?= $opt ?>')" class="<?= $status === $opt ? 'bg-green-600 text-white ring-2 ring-green-400' : 'bg-gray-700 hover:bg-gray-600 text-white' ?> px-4 py-2 rounded text-sm"><?= $opt ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Create Call Button (right) -->
  <div class="mt-8">
    <button class="bg-blue-600 hover:bg-blue-700 text-sm px-5 py-2 rounded font-semibold">
      + Create a Call
    </button>
  </div>
</div>

      </div>
    </div>

    <!-- Unit Overview -->
    <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700 mt-12">
      <h2 class="text-2xl font-semibold mb-6">Active Units Overview</h2>
      <div class="overflow-x-auto">
        <div class="max-h-96 overflow-y-auto scrollbar">
          <table class="w-full text-left text-sm text-gray-300">
            <thead class="sticky top-0 bg-gray-800 z-10">
              <tr class="border-b border-gray-700 text-gray-400">
                <th class="px-4 py-2">Callsign</th>
                <th class="px-4 py-2">Department</th>
                <th class="px-4 py-2">Status</th>
              </tr>
            </thead>
            <tbody id="unitsContainer">
              <tr><td colspan="3" class="px-4 py-3 text-gray-400">Loading units...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
  function updateStatus(status) {
    fetch('update-status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'status=' + encodeURIComponent(status)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        location.reload();
      } else {
        alert('Failed to update status.');
      }
    });
  }

  function loadUnits() {
    fetch('../includes/get-units.php')
      .then(res => res.json())
      .then(units => {
        const container = document.getElementById('unitsContainer');
        if (!Array.isArray(units) || units.length === 0) {
          container.innerHTML = '<tr><td colspan="3" class="px-4 py-3 text-gray-400">No active units found.</td></tr>';
          return;
        }

        container.innerHTML = units.map(unit => {
          const statusColor =
            unit.status === '10-8' ? 'text-green-400' :
            unit.status === '10-6' ? 'text-yellow-400' :
            unit.status === '10-7' ? 'text-red-400' : 'text-white';

          return `
            <tr class="border-b border-gray-700">
              <td class="px-4 py-2 font-semibold text-white">${unit.callsign}</td>
              <td class="px-4 py-2">${unit.department}</td>
              <td class="px-4 py-2 font-medium ${statusColor}">${unit.status}</td>
            </tr>
          `;
        }).join('');
      })
      .catch(() => {
        document.getElementById('unitsContainer').innerHTML = '<tr><td colspan="3" class="px-4 py-3 text-red-500">Failed to load units.</td></tr>';
      });
  }

  loadUnits();
  setInterval(loadUnits, 3000);
</script>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
