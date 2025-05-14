<?php
session_start();
require_once '../includes/supabase.php';

// Restrict to SACO only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active']) || ($_SESSION['department'] ?? '') !== 'SACO') {
    header("Location: ../patrol.php");
    exit;
}

// Set MDT context
$_SESSION['active_mdt'] = 'saco';

$status = $_SESSION['status'] ?? '10-7';
$username = $_SESSION['username'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'SACO';
$callsign = $_SESSION['callsign'] ?? 'None';

// Penal Code & 10-Codes (for modals)
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
  <title>SACO Dispatcher MDT</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
      <h2 class="text-2xl font-semibold mb-6 text-white">Dispatcher Info</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-gray-300">
        <div>
          <p class="text-sm text-gray-400 uppercase mb-1">Username</p>
          <p class="text-xl font-semibold"><?= htmlspecialchars($username) ?></p>
        </div>
        <div>
          <p class="text-sm text-gray-400 uppercase mb-1">Department</p>
          <p class="text-xl font-semibold">San Andreas Communications</p>
        </div>
        <div>
          <p class="text-sm text-gray-400 uppercase mb-1">Callsign</p>
          <p class="text-xl font-semibold"><?= htmlspecialchars($callsign) ?></p>
        </div>
        <div class="col-span-1 sm:col-span-2 lg:col-span-3">
          <p class="text-sm text-gray-400 uppercase mb-1">Status</p>
          <div class="flex flex-col sm:flex-row sm:items-center justify-between">
            <p id="currentStatus" class="text-xl font-semibold text-green-400 mb-4 sm:mb-0"><?= htmlspecialchars($status) ?></p>
            <div class="flex flex-wrap gap-2 mt-2">
              <?php if ($status === '10-7'): ?>
                <button onclick="updateStatus('10-8')" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm">
                  Go 10-8
                </button>
              <?php else: ?>
                <?php
                $statuses = ['10-8', '10-6', '10-7'];
                foreach ($statuses as $opt):
                  $isActive = $status === $opt;
                  $classes = $isActive
                    ? 'bg-green-600 text-white ring-2 ring-green-400'
                    : 'bg-gray-700 hover:bg-gray-600 text-white';
                ?>
                  <button onclick="updateStatus('<?= $opt ?>')" class="<?= $classes ?> px-4 py-2 rounded text-sm"><?= $opt ?></button>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-12 text-center text-gray-500 text-sm">
      Dispatcher tools will be added here.
    </div>
  </div>
</main>

<!-- Status Script -->
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

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
