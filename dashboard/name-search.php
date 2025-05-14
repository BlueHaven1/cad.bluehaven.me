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

$dashboard_link = ($_SESSION['active_mdt'] ?? '') === 'safr' ? 'safr-mdt.php' : 'mdt.php';

// Preload Penal Code data for modal
[$titlesResp] = supabaseRequest("penal_titles", "GET");
$penal_titles = json_decode($titlesResp, true) ?? [];

[$sectionsResp] = supabaseRequest("penal_sections", "GET");
$penal_sections = json_decode($sectionsResp, true) ?? [];

$sections_by_title = [];
foreach ($penal_titles as $title) {
  $tid = $title['id'];
  $sections_by_title[$tid] = array_filter($penal_sections, fn($s) => $s['title_id'] == $tid);
}

// Preload 10-Codes content
[$res] = supabaseRequest("ten_codes?id=eq.1", "GET");
$data = json_decode($res, true);
$content = $data[0]['content'] ?? '<p>No 10-Codes available.</p>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Name Search - MDT</title>
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
    <h1 class="text-4xl font-bold mb-8">Name Search</h1>

    <!-- Live Search Input -->
    <div class="flex gap-4 mb-6">
      <input type="text" id="searchInput" placeholder="Enter name" class="flex-1 px-4 py-2 rounded bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Results Container -->
    <div id="resultsContainer" class="space-y-4"></div>
  </div>
</main>

<!-- Modals -->
<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>

<!-- Live Search Script with Animation -->
<script>
const input = document.getElementById('searchInput');
const resultsContainer = document.getElementById('resultsContainer');

input.addEventListener('input', () => {
  const query = input.value.trim();

  if (query.length < 2) {
    resultsContainer.innerHTML = '';
    return;
  }

  fetch(`../includes/search-civilians.php?name=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data) || data.length === 0) {
        resultsContainer.innerHTML = '<p class="text-gray-400">No civilians found.</p>';
        return;
      }

      resultsContainer.innerHTML = data.map(civ => `
        <a href="civilian.php?id=${civ.id}" 
           class="result-card block bg-gray-800 p-4 rounded-lg shadow border border-gray-700 hover:bg-gray-700 transform opacity-0 translate-y-2">
          <h2 class="text-xl font-semibold text-white">${civ.name}</h2>
          <p class="text-sm text-gray-400">DOB: ${civ.dob}</p>
          <p class="text-sm text-gray-400">Phone: ${civ.phone}</p>
          <p class="text-sm text-gray-400">Address: ${civ.address}</p>
        </a>
      `).join('');

      // Animate results in sequence
      setTimeout(() => {
        document.querySelectorAll('.result-card').forEach((card, index) => {
          setTimeout(() => {
            card.classList.remove('opacity-0', 'translate-y-2');
            card.classList.add('opacity-100', 'translate-y-0', 'transition-all', 'duration-300');
          }, index * 40);
        });
      }, 10);
    })
    .catch(() => {
      resultsContainer.innerHTML = '<p class="text-red-500">Error fetching data.</p>';
    });
});
</script>

</body>
</html>
