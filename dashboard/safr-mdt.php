<?php
session_start();
$_SESSION['active_mdt'] = 'safr';
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active'])) {
    header("Location: ../patrol.php");
    exit;
}

$status = $_SESSION['status'] ?? '10-7';
$username = $_SESSION['username'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'N/A';
$callsign = $_SESSION['callsign'] ?? 'None';
$userId = $_SESSION['user_id'];

// Preload penal code data for modals
[$titlesResp] = supabaseRequest("penal_titles", "GET");
$penal_titles = json_decode($titlesResp, true) ?? [];

[$sectionsResp] = supabaseRequest("penal_sections", "GET");
$penal_sections = json_decode($sectionsResp, true) ?? [];

$sections_by_title = [];
foreach ($penal_titles as $title) {
  $tid = $title['id'];
  $sections_by_title[$tid] = array_filter($penal_sections, fn($s) => $s['title_id'] == $tid);
}

// Fetch active calls
[$callRes] = supabaseRequest("calls?order=created_at.desc", "GET");
$all_calls = json_decode($callRes, true) ?? [];

// Filter calls assigned to this user
$assigned_calls = [];
foreach ($all_calls as $call) {
  $unitList = explode(',', $call['units'] ?? '');
  if (in_array($userId, $unitList)) {
    $assigned_calls[] = $call;
  }
}

// Fetch units for display
[$unitRes] = supabaseRequest("unit_status", "GET");
$active_units = json_decode($unitRes, true) ?? [];

// Map user_id => unit info
$unitMap = [];
foreach ($active_units as $unit) {
  $unitMap[$unit['user_id']] = $unit;
}

// Fetch active BOLOs
[$boloRes] = supabaseRequest("bolos?order=created_at.desc", "GET");
$active_bolos = json_decode($boloRes, true) ?? [];

// Preload 10-Codes content
[$res] = supabaseRequest("ten_codes?id=eq.1", "GET");
$data = json_decode($res, true);
$content = $data[0]['content'] ?? '<p>No 10-Codes available.</p>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SAFR MDT - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="/assets/js/alerts.js"></script>
</head>
<body class="bg-gray-900 text-white flex min-h-screen">
<!-- Alert Banner -->
<div id="alert-banner" class="w-full text-center text-white text-lg font-bold py-3 hidden fixed top-0 z-50"></div>
<!-- Spacer for fixed header -->
<div id="alert-spacer" class="h-0"></div>
<!-- Alert Sound -->
<audio id="alert-sound" src="/assets/sounds/alert.mp3" preload="auto"></audio>
<!-- Notification Sound for new call assignments -->
<audio id="notification-sound" src="/assets/sounds/notification.mp3" preload="auto"></audio>
<!-- Call Assignment Sound -->
<audio id="assigncall-sound" src="/assets/sounds/assigncall.mp3" preload="auto"></audio>

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
  <div class="max-w-7xl mx-auto">
    <h1 class="text-4xl font-bold mb-8">SAFR MDT Dashboard</h1>

    <!-- Unit Info Card -->
    <div class="bg-gray-800 rounded-2xl p-8 shadow-xl border border-gray-700">
      <h2 class="text-2xl font-semibold mb-6 text-white">Unit Information</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-gray-300">
        <div>
          <p class="text-sm text-gray-400 uppercase mb-1">Username</p>
          <p class="text-xl font-semibold"><?= htmlspecialchars($username) ?></p>
        </div>

        <div>
          <p class="text-sm text-gray-400 uppercase mb-1">Department</p>
          <p class="text-xl font-semibold"><?= htmlspecialchars($department) ?></p>
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
                $allStatuses = ['10-8', '10-6', '10-15', '10-23', '10-97', '10-7'];
                foreach ($allStatuses as $opt):
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

    <!-- Active BOLOs -->
    <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700 mt-12">
      <h2 class="text-2xl font-semibold mb-6">Active BOLOs</h2>

      <?php if (!empty($active_bolos)): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-300">
            <thead class="bg-gray-700 text-gray-400 text-sm">
              <tr>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2">Last Seen</th>
                <th class="px-4 py-2">Created At</th>
                <th class="px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($active_bolos as $bolo): ?>
                <tr class="border-b border-gray-700">
                  <td class="px-4 py-2 font-medium text-white"><?= htmlspecialchars($bolo['type']) ?></td>
                  <td class="px-4 py-2">
                    <div class="max-w-xs overflow-hidden text-ellipsis">
                      <?= htmlspecialchars(substr($bolo['description'], 0, 100)) ?><?= strlen($bolo['description']) > 100 ? '...' : '' ?>
                    </div>
                  </td>
                  <td class="px-4 py-2"><?= htmlspecialchars($bolo['last_seen']) ?></td>
                  <td class="px-4 py-2"><?= date('m/d/Y H:i', strtotime($bolo['created_at'])) ?></td>
                  <td class="px-4 py-2">
                    <button
                      type="button"
                      onclick="viewBolo(<?= htmlspecialchars(json_encode($bolo)) ?>)"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                      View
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

    <!-- Assigned Calls -->
    <div class="bg-gray-800 rounded-2xl p-6 shadow-xl border border-gray-700 mt-12 calls-section transition-colors duration-300">
      <h2 class="text-2xl font-semibold mb-6">Your Assigned Calls</h2>

      <div class="assigned-calls-container">
        <?php if (!empty($assigned_calls)): ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-300">
              <thead class="bg-gray-700 text-gray-400 text-sm">
                <tr>
                  <th class="px-4 py-2">Title</th>
                  <th class="px-4 py-2">Description</th>
                  <th class="px-4 py-2">Location</th>
                  <th class="px-4 py-2">Postal</th>
                  <th class="px-4 py-2">Units</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assigned_calls as $call): ?>
                  <tr class="border-b border-gray-700">
                    <td class="px-4 py-2 font-medium text-white"><?= htmlspecialchars($call['title']) ?></td>
                    <td class="px-4 py-2">
                      <div class="max-w-xs overflow-hidden text-ellipsis">
                        <?= htmlspecialchars(substr($call['description'], 0, 100)) ?><?= strlen($call['description']) > 100 ? '...' : '' ?>
                      </div>
                    </td>
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
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p class="text-gray-400">You have no assigned calls at the moment.</p>
        <?php endif; ?>
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

  // Track the number of assigned calls to detect changes
  let previousCallCount = <?php echo count($assigned_calls); ?>;

  // Function to refresh assigned calls
  function refreshAssignedCalls() {
    // Add a cache-busting parameter to prevent browser caching since we're calling frequently
    fetch('get-assigned-calls.php?_=' + Date.now())
      .then(response => response.json())
      .then(data => {
        if (data.success && data.calls) {
          // Update the UI with the new calls
          const callsContainer = document.querySelector('.assigned-calls-container');
          const callsSection = document.querySelector('.calls-section');

          // Check if the number of calls has increased (new assignment)
          console.log('Call check - Previous: ' + previousCallCount + ', Current: ' + data.calls.length);
          if (data.calls.length > previousCallCount) {
            console.log('New call detected! Playing sound...');
            // Flash the calls section to indicate new assignment
            if (callsSection) {
              callsSection.classList.add('bg-blue-900');
              setTimeout(() => {
                callsSection.classList.remove('bg-blue-900');
              }, 1000);

              // Play call assignment sound
              const assigncallSound = document.getElementById('assigncall-sound');
              if (assigncallSound) {
                console.log('Sound element found, attempting to play...');
                assigncallSound.currentTime = 0;
                assigncallSound.volume = 1.0; // Ensure volume is at maximum
                assigncallSound.play()
                  .then(() => console.log('Sound played successfully!'))
                  .catch(e => console.error('Error playing call assignment sound:', e));
              } else {
                console.error('Sound element not found!');
              }
            }
          }

          // Update the previous count
          previousCallCount = data.calls.length;

          if (callsContainer) {
            if (data.calls.length === 0) {
              callsContainer.innerHTML = '<p class="text-gray-400">You have no assigned calls at the moment.</p>';
            } else {
              // Create table HTML
              let tableHtml = `
                <div class="overflow-x-auto">
                  <table class="w-full text-left text-sm text-gray-300">
                    <thead class="bg-gray-700 text-gray-400 text-sm">
                      <tr>
                        <th class="px-4 py-2">Title</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2">Location</th>
                        <th class="px-4 py-2">Postal</th>
                        <th class="px-4 py-2">Units</th>
                      </tr>
                    </thead>
                    <tbody>`;

              // Add rows for each call
              data.calls.forEach(call => {
                tableHtml += `
                  <tr class="border-b border-gray-700">
                    <td class="px-4 py-2 font-medium text-white">${escapeHtml(call.title)}</td>
                    <td class="px-4 py-2">
                      <div class="max-w-xs overflow-hidden text-ellipsis">
                        ${escapeHtml(call.description.length > 100 ? call.description.substring(0, 100) + '...' : call.description)}
                      </div>
                    </td>
                    <td class="px-4 py-2">${escapeHtml(call.location)}</td>
                    <td class="px-4 py-2">${escapeHtml(call.postal)}</td>
                    <td class="px-4 py-2">${escapeHtml(call.units)}</td>
                  </tr>`;
              });

              // Close the table
              tableHtml += `
                    </tbody>
                  </table>
                </div>`;

              // Update the container
              callsContainer.innerHTML = tableHtml;
            }
          }
        }
      })
      .catch(error => console.error('Error refreshing calls:', error));
  }

  // Helper function to escape HTML
  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // Refresh calls every 3 seconds
  setInterval(refreshAssignedCalls, 3000);

  // Function to preload the sound file
  function preloadSound(audioElement) {
    return new Promise((resolve, reject) => {
      if (!audioElement) {
        reject(new Error('Audio element not found'));
        return;
      }

      // Create a new XMLHttpRequest to load the audio file
      const xhr = new XMLHttpRequest();
      xhr.open('GET', audioElement.src, true);
      xhr.responseType = 'blob';

      xhr.onload = function() {
        if (this.status === 200) {
          // Create a blob URL from the downloaded file
          const blob = new Blob([this.response], { type: 'audio/mpeg' });
          const blobUrl = URL.createObjectURL(blob);

          // Update the audio element's src to the blob URL
          audioElement.src = blobUrl;
          console.log('Sound file preloaded successfully');
          resolve();
        } else {
          console.error('Failed to preload sound file:', this.status);
          reject(new Error(`Failed to preload sound file: ${this.status}`));
        }
      };

      xhr.onerror = function() {
        console.error('Error during sound file preload');
        reject(new Error('Error during sound file preload'));
      };

      xhr.send();
    });
  }

  // Preload sound file when the page loads
  document.addEventListener('DOMContentLoaded', () => {
    // Preload the sound file
    const assigncallSound = document.getElementById('assigncall-sound');
    if (assigncallSound) {
      console.log('Preloading sound file...');
      preloadSound(assigncallSound)
        .then(() => console.log('Sound preloaded and ready to play'))
        .catch(e => console.error('Error preloading sound:', e));
    }
  });
</script>

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
  // BOLO View Modal
  const viewModal = document.getElementById('viewBoloModal');

  function viewBolo(bolo) {
    document.getElementById('view-type').textContent = bolo.type;
    document.getElementById('view-description').textContent = bolo.description;
    document.getElementById('view-last-seen').textContent = bolo.last_seen;

    // Format date
    const createdAt = new Date(bolo.created_at);
    document.getElementById('view-created-at').textContent = createdAt.toLocaleString();

    viewModal.classList.remove('hidden');
  }

  function closeViewModal() {
    viewModal.classList.add('hidden');
  }

  // Close modal with Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeViewModal();
    }
  });
</script>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
