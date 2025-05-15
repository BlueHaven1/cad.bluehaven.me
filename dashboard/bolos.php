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

// Fetch BOLOs
[$boloRes] = supabaseRequest("bolos?order=created_at.desc", "GET");
$bolos = json_decode($boloRes, true) ?? [];

// Fetch units for dropdown
[$unitRes] = supabaseRequest("unit_status", "GET");
$active_units = json_decode($unitRes, true) ?? [];

// Map user_id => unit info
$unitMap = [];
foreach ($active_units as $unit) {
    $unitMap[$unit['user_id']] = $unit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BOLOs - SACO MDT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="/assets/js/alerts.js"></script>
  <style>
    .scrollbar::-webkit-scrollbar { width: 6px; }
    .scrollbar::-webkit-scrollbar-thumb { background: #4B5563; border-radius: 4px; }
  </style>
</head>
<body class="bg-gray-900 text-white flex min-h-screen">
<!-- Alert Banner -->
<div id="alert-banner" class="w-full text-center text-white text-lg font-bold py-3 hidden fixed top-0 z-50"></div>
<!-- Spacer for fixed header -->
<div id="alert-spacer" class="h-0"></div>
<!-- Alert Sound -->
<audio id="alert-sound" src="/assets/sounds/alert.mp3" preload="auto"></audio>

<!-- Sidebar -->
<aside class="w-64 bg-gray-800 p-4 flex flex-col justify-between fixed h-full">
  <div>
    <h2 class="text-2xl font-bold mb-6">SACO MDT</h2>
    <nav class="space-y-2">
      <a href="saco-mdt.php" class="block px-3 py-2 rounded hover:bg-gray-700">Dashboard</a>
      <a href="name-search.php?return=saco" class="block px-3 py-2 rounded hover:bg-gray-700">Name Search</a>
      <a href="plate-search.php?return=saco" class="block px-3 py-2 rounded hover:bg-gray-700">Plate Search</a>
      <a href="bolos.php" class="block px-3 py-2 rounded bg-gray-700">BOLOs</a>
    </nav>
  </div>
  <a href="exit-mdt.php" class="block px-3 py-2 mt-6 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">
    Exit MDT
  </a>
</aside>

<!-- Main Content -->
<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-4xl font-bold">Be On the Lookout (BOLO)</h1>
      <button id="openCreateBolo" class="bg-blue-600 hover:bg-blue-700 text-sm px-5 py-2 rounded font-semibold">
        + Create BOLO
      </button>
    </div>

    <!-- Active BOLOs -->
    <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700">
      <h2 class="text-2xl font-semibold mb-6">Active BOLOs</h2>

      <?php if (!empty($bolos)): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-300">
            <thead class="bg-gray-700 text-gray-400 text-sm">
              <tr>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2">Last Seen</th>
                <th class="px-4 py-2">Created By</th>
                <th class="px-4 py-2">Created At</th>
                <th class="px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bolos as $bolo): ?>
                <tr class="border-b border-gray-700">
                  <td class="px-4 py-2 font-medium text-white"><?= htmlspecialchars($bolo['type']) ?></td>
                  <td class="px-4 py-2">
                    <div class="max-w-xs overflow-hidden text-ellipsis">
                      <?= htmlspecialchars(substr($bolo['description'], 0, 100)) ?><?= strlen($bolo['description']) > 100 ? '...' : '' ?>
                    </div>
                  </td>
                  <td class="px-4 py-2"><?= htmlspecialchars($bolo['last_seen']) ?></td>
                  <td class="px-4 py-2">
                    <?php
                      $createdBy = $bolo['created_by'] ?? '';
                      echo isset($unitMap[$createdBy]) ? htmlspecialchars($unitMap[$createdBy]['callsign']) : 'Unknown';
                    ?>
                  </td>
                  <td class="px-4 py-2"><?= date('m/d/Y H:i', strtotime($bolo['created_at'])) ?></td>
                  <td class="px-4 py-2 flex gap-2">
                    <!-- View -->
                    <button
                      type="button"
                      onclick="viewBolo(<?= htmlspecialchars(json_encode($bolo)) ?>)"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                      View
                    </button>
                    
                    <!-- Edit -->
                    <button
                      type="button"
                      onclick="openEditModal(<?= htmlspecialchars(json_encode($bolo)) ?>)"
                      class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                      Edit
                    </button>
                    
                    <!-- Delete -->
                    <button
                      type="button"
                      onclick="deleteBolo('<?= $bolo['id'] ?>')"
                      class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                      Delete
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No active BOLOs at the moment.</p>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Create BOLO Modal -->
<div id="createBoloModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-gray-800 rounded-xl w-full max-w-xl p-6 shadow-xl border border-gray-700 relative">
    <h2 class="text-2xl font-semibold mb-4">Create a New BOLO</h2>
    <form id="createBoloForm" class="space-y-4">
      <div>
        <label class="block text-sm mb-1 text-gray-300">Type</label>
        <select name="type" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
          <option value="">Select Type</option>
          <option value="Person">Person</option>
          <option value="Vehicle">Vehicle</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Description</label>
        <textarea name="description" rows="4" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none"></textarea>
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Last Seen</label>
        <input type="text" name="last_seen" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
      </div>
      <div class="flex justify-between mt-6">
        <button type="button" onclick="closeBoloModal()" class="text-gray-300 hover:text-white">Cancel</button>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-5 py-2 rounded font-semibold">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit BOLO Modal -->
<div id="editBoloModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-gray-800 rounded-xl w-full max-w-xl p-6 shadow-xl border border-gray-700 relative">
    <h2 class="text-2xl font-semibold mb-4">Edit BOLO</h2>
    <form id="editBoloForm" class="space-y-4">
      <input type="hidden" name="id" id="edit-bolo-id">
      <div>
        <label class="block text-sm mb-1 text-gray-300">Type</label>
        <select name="type" id="edit-type" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
          <option value="Person">Person</option>
          <option value="Vehicle">Vehicle</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Description</label>
        <textarea name="description" id="edit-description" rows="4" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none"></textarea>
      </div>
      <div>
        <label class="block text-sm mb-1 text-gray-300">Last Seen</label>
        <input type="text" name="last_seen" id="edit-last-seen" required class="w-full px-4 py-2 rounded bg-gray-700 border border-gray-600 focus:outline-none">
      </div>
      <div class="flex justify-between mt-6">
        <button type="button" onclick="closeEditModal()" class="text-gray-300 hover:text-white">Cancel</button>
        <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 px-5 py-2 rounded font-semibold">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- View BOLO Modal -->
<div id="viewBoloModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 hidden">
  <div class="bg-gray-800 rounded-xl w-full max-w-xl p-6 shadow-xl border border-gray-700 relative">
    <h2 class="text-2xl font-semibold mb-4">BOLO Details</h2>
    <div class="space-y-4">
      <div>
        <h3 class="text-sm text-gray-400 uppercase">Type</h3>
        <p id="view-type" class="text-lg"></p>
      </div>
      <div>
        <h3 class="text-sm text-gray-400 uppercase">Description</h3>
        <p id="view-description" class="text-lg whitespace-pre-line"></p>
      </div>
      <div>
        <h3 class="text-sm text-gray-400 uppercase">Last Seen</h3>
        <p id="view-last-seen" class="text-lg"></p>
      </div>
      <div>
        <h3 class="text-sm text-gray-400 uppercase">Created By</h3>
        <p id="view-created-by" class="text-lg"></p>
      </div>
      <div>
        <h3 class="text-sm text-gray-400 uppercase">Created At</h3>
        <p id="view-created-at" class="text-lg"></p>
      </div>
      <div class="flex justify-end mt-6">
        <button type="button" onclick="closeViewModal()" class="bg-gray-600 hover:bg-gray-700 px-5 py-2 rounded font-semibold">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Create BOLO Modal
  const createModal = document.getElementById('createBoloModal');
  document.getElementById('openCreateBolo').addEventListener('click', () => {
    createModal.classList.remove('hidden');
  });

  function closeBoloModal() {
    createModal.classList.add('hidden');
  }

  // Edit BOLO Modal
  const editModal = document.getElementById('editBoloModal');
  
  function openEditModal(bolo) {
    document.getElementById('edit-bolo-id').value = bolo.id;
    document.getElementById('edit-type').value = bolo.type;
    document.getElementById('edit-description').value = bolo.description;
    document.getElementById('edit-last-seen').value = bolo.last_seen;
    editModal.classList.remove('hidden');
  }

  function closeEditModal() {
    editModal.classList.add('hidden');
  }

  // View BOLO Modal
  const viewModal = document.getElementById('viewBoloModal');
  
  function viewBolo(bolo) {
    document.getElementById('view-type').textContent = bolo.type;
    document.getElementById('view-description').textContent = bolo.description;
    document.getElementById('view-last-seen').textContent = bolo.last_seen;
    
    // Format created by
    const createdBy = bolo.created_by;
    <?php foreach ($active_units as $unit): ?>
      if (createdBy === '<?= $unit['user_id'] ?>') {
        document.getElementById('view-created-by').textContent = '<?= htmlspecialchars($unit['callsign']) ?>';
      }
    <?php endforeach; ?>
    
    // Format date
    const createdAt = new Date(bolo.created_at);
    document.getElementById('view-created-at').textContent = createdAt.toLocaleString();
    
    viewModal.classList.remove('hidden');
  }

  function closeViewModal() {
    viewModal.classList.add('hidden');
  }

  // Delete BOLO
  function deleteBolo(id) {
    if (confirm('Are you sure you want to delete this BOLO?')) {
      fetch('../includes/delete-bolo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Failed to delete BOLO: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(err => {
        alert('Error: ' + err.message);
      });
    }
  }

  // Create BOLO Form
  document.getElementById('createBoloForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (const pair of formData.entries()) {
      params.append(pair[0], pair[1]);
    }
    
    fetch('../includes/create-bolo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('BOLO created successfully');
        form.reset();
        closeBoloModal();
        location.reload();
      } else {
        alert('Failed to create BOLO: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      alert('Error: ' + err.message);
    });
  });

  // Edit BOLO Form
  document.getElementById('editBoloForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (const pair of formData.entries()) {
      params.append(pair[0], pair[1]);
    }
    
    fetch('../includes/update-bolo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('BOLO updated successfully');
        closeEditModal();
        location.reload();
      } else {
        alert('Failed to update BOLO: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      alert('Error: ' + err.message);
    });
  });

  // Close modals with Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeBoloModal();
      closeEditModal();
      closeViewModal();
    }
  });
</script>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
