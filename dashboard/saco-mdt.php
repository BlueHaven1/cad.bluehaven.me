<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active']) || ($_SESSION['department'] ?? '') !== 'SACO') {
    header("Location: ../patrol.php");
    exit;
}

$_SESSION['active_mdt'] = 'saco';

$status = $_SESSION['status'] ?? '10-7';
$username = $_SESSION['username'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'SACO';
$callsign = $_SESSION['callsign'] ?? 'None';

// Fetch penal codes & 10-codes
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

$unitMap = [];
foreach ($active_units as $unit) {
    $unitMap[$unit['user_id']] = $unit;
}

[$callRes] = supabaseRequest("calls?order=created_at.desc", "GET");
$active_calls = json_decode($callRes, true) ?? [];
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

<!-- Alert Banner -->
<div id="alert-banner" class="w-full text-center text-white text-lg font-bold py-3 hidden fixed top-0 z-50"></div>
<div id="alert-spacer" class="h-0"></div>


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

        <div class="col-span-full mt-4 flex justify-between items-start flex-wrap gap-4">
          <div>
            <p class="text-sm text-gray-400 uppercase mb-1">Status</p>
            <div class="flex flex-wrap gap-2 mt-2">
              <?php foreach (['10-8', '10-6', '10-7'] as $opt): ?>
                <button onclick="updateStatus('<?= $opt ?>')" class="<?= $status === $opt ? 'bg-green-600 text-white ring-2 ring-green-400' : 'bg-gray-700 hover:bg-gray-600 text-white' ?> px-4 py-2 rounded text-sm"><?= $opt ?></button>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="mt-4">
            <button id="openCreateCall" class="bg-blue-600 hover:bg-blue-700 text-sm px-5 py-2 rounded font-semibold">
              + Create a Call
            </button>
          </div>
          <div class="mt-6 space-x-4">
            <button id="toggle-signal100" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold">
              Toggle Signal 100
            </button>
            <button id="toggle-10-3" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded font-semibold text-black">
              Toggle 10-3
            </button>
          </div>
        </div>
      </div>
    </div>


    <!-- Active Units -->
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

    <!-- Active Calls -->
    <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700 mt-12">
      <h2 class="text-2xl font-semibold mb-6">Active Calls</h2>

      <?php if (!empty($active_calls)): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-300">
            <thead class="bg-gray-700 text-gray-400 text-sm">
              <tr>
                <th class="px-4 py-2">Title</th>
                <th class="px-4 py-2">Location</th>
                <th class="px-4 py-2">Postal</th>
                <th class="px-4 py-2">Units</th>
                <th class="px-4 py-2">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($active_calls as $call): ?>
                <tr class="border-b border-gray-700">
                  <td class="px-4 py-2 font-medium text-white"><?= htmlspecialchars($call['title']) ?></td>
                  <td class="px-4 py-2"><?= htmlspecialchars($call['location']) ?></td>
                  <td class="px-4 py-2"><?= htmlspecialchars($call['postal'] ?: '-') ?></td>
                  <td class="px-4 py-2">
                    <?php
                      $unitList = explode(',', $call['units'] ?? '');
                      if (empty($unitList[0])) {
                        echo '<span class="text-gray-400">None</span>';
                      } else {
                        $displayUnits = [];
                        foreach ($unitList as $uid) {
                          $uid = trim($uid);
                          if (isset($unitMap[$uid])) {
                            $displayUnits[] = htmlspecialchars($unitMap[$uid]['callsign']);
                          } else {
                            $displayUnits[] = htmlspecialchars($uid);
                          }
                        }
                        echo implode(', ', $displayUnits);
                      }
                    ?>
                  </td>
                  <td class="px-4 py-2 flex gap-2">
                    <button type="button"
                      onclick="openEditModal('<?= $call['id'] ?>', <?= htmlspecialchars(json_encode($call)) ?>)"
                      class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                      Edit
                    </button>
                    <form method="POST" action="../includes/close-call.php"
                      onsubmit="return confirm('Are you sure you want to close this call?');">
                      <input type="hidden" name="id" value="<?= htmlspecialchars($call['id']) ?>">
                      <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                        Close
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No active calls at the moment.</p>
      <?php endif; ?>
    </div>
  </div>
</main>


<!-- Create Call Modal -->
<div id="createCallModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-gray-800 rounded-xl w-full max-w-xl p-6 shadow-xl border border-gray-700 relative">
    <h2 class="text-2xl font-semibold mb-4">Create a New Call</h2>
    <form id="createCallForm" class="space-y-4">
      <div>
        <label class="block text-sm mb-1 text-gray-300">Title</label>
        <input type="text" name="title" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Description</label>
        <textarea name="description" rows="4" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none"></textarea>
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Location</label>
        <input type="text" name="location" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Postal</label>
        <input type="text" name="postal" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Assign Units</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-2 scrollbar bg-gray-700 p-3 rounded">
          <?php foreach ($active_units as $unit): ?>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="units[]" value="<?= htmlspecialchars($unit['user_id']) ?>" class="accent-blue-500">
              <span><?= htmlspecialchars("{$unit['callsign']} - {$unit['department']} ({$unit['status']})") ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex justify-between mt-6">
        <button type="button" onclick="closeCallModal()" class="text-gray-300 hover:text-white">Cancel</button>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-5 py-2 rounded font-semibold">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Call Modal -->
<div id="editCallModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-gray-800 rounded-xl w-full max-w-xl p-6 shadow-xl border border-gray-700 relative">
    <h2 class="text-2xl font-semibold mb-4">Edit Call</h2>
    <form id="editCallForm" class="space-y-4">
      <input type="hidden" name="id" id="edit-call-id">
      <div>
        <label class="block text-sm mb-1 text-gray-300">Title</label>
        <input type="text" name="title" id="edit-title" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600">
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Description</label>
        <textarea name="description" id="edit-description" rows="4" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600"></textarea>
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Location</label>
        <input type="text" name="location" id="edit-location" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600">
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Postal</label>
        <input type="text" name="postal" id="edit-postal" class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600">
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Assign Units</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-2 scrollbar bg-gray-700 p-3 rounded" id="edit-unit-checkboxes">
          <?php foreach ($active_units as $unit): ?>
            <label class="flex items-center space-x-2">
              <input type="checkbox" name="units[]" value="<?= htmlspecialchars($unit['user_id']) ?>" class="edit-unit-checkbox accent-blue-500">
              <span><?= htmlspecialchars("{$unit['callsign']} - {$unit['department']} ({$unit['status']})") ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex justify-between mt-6">
        <button type="button" onclick="closeEditModal()" class="text-gray-300 hover:text-white">Cancel</button>
        <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 px-5 py-2 rounded font-semibold">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- JavaScript Logic -->
<script>
function openEditModal(id, call) {
  document.getElementById('edit-call-id').value = id;
  document.getElementById('edit-title').value = call.title || '';
  document.getElementById('edit-description').value = call.description || '';
  document.getElementById('edit-location').value = call.location || '';
  document.getElementById('edit-postal').value = call.postal || '';

  const selectedUnits = (call.units || '').split(',').map(u => u.trim());
  document.querySelectorAll('.edit-unit-checkbox').forEach(cb => {
    cb.checked = selectedUnits.includes(cb.value);
  });

  document.getElementById('editCallModal').classList.remove('hidden');
}

function closeEditModal() {
  document.getElementById('editCallModal').classList.add('hidden');
}

function closeCallModal() {
  document.getElementById('createCallModal').classList.add('hidden');
}

document.getElementById('editCallForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const params = new URLSearchParams([...formData]);

  fetch('../includes/update-call.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      closeEditModal();
      location.reload();
    } else {
      alert('Failed to update call.');
    }
  })
  .catch(err => alert('Error: ' + err.message));
});

document.getElementById('createCallForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const params = new URLSearchParams([...formData]);

  fetch('../includes/create-call.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Call created successfully');
      e.target.reset();
      closeCallModal();
      location.reload();
    } else {
      alert('Failed to create call: ' + (data.error || 'Unknown error'));
    }
  })
  .catch(err => alert('Error: ' + err.message));
});
</script>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
