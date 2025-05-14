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
$officerId = $_SESSION['user_id'];

$success = false;
$error = '';

// Fetch penal titles
[$titlesResp, $titlesCode] = supabaseRequest("penal_titles", "GET");
$penalTitlesRaw = $titlesCode === 200 ? json_decode($titlesResp, true) : [];

$penalTitles = [];
$sectionsByTitle = [];

if (!empty($penalTitlesRaw)) {
    foreach ($penalTitlesRaw as $title) {
        $titleId = $title['id'];
        [$sectionsResp, $sectionsCode] = supabaseRequest("penal_sections?title_id=eq.$titleId", "GET");
        $sections = $sectionsCode === 200 ? json_decode($sectionsResp, true) : [];
        if (!empty($sections)) {
            $penalTitles[] = $title;
            $sectionsByTitle[$titleId] = $sections;
        }
    }
}

// Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $civilian_id = $_POST['civilian_id'] ?? null;
  $violation = $_POST['violation'] ?? '';
  $location = trim($_POST['location']);
  $notes = trim($_POST['notes']);
  $signature = $_POST['signature'] ?? null;

  if ($civilian_id && $violation && $signature) {
    $body = [
      'civilian_id' => $civilian_id,
      'officer_id' => $officerId,
      'violation' => $violation,
      'location' => $location,
      'notes' => $notes,
      'signature' => $signature
    ];
    [$resp, $code] = supabaseRequest("written_warnings", "POST", [$body]);
    $success = $code === 201;
    if (!$success) $error = 'Failed to submit written warning.';
  } else {
    $error = 'Civilian, violation, and signature are required.';
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Written Warning - MDT</title>
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
      <a href="warning.php" class="block px-3 py-2 rounded bg-gray-700">Written Warning</a>
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
<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-3xl mx-auto">
    <h1 class="text-4xl font-bold mb-6">Create Written Warning</h1>

    <?php if ($success): ?>
      <p class="text-green-500 mb-4">Written warning submitted successfully!</p>
    <?php elseif (!empty($error)): ?>
      <p class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block mb-1 font-semibold">Search Civilian</label>
        <input type="text" id="civilian_display" oninput="searchCivilians(this.value)" placeholder="Type to search..." class="w-full px-4 py-2 bg-gray-800 rounded" autocomplete="off">
        <input type="hidden" name="civilian_id" id="civilian_id">
        <div id="results" class="bg-gray-800 border border-gray-600 rounded mt-1 max-h-40 overflow-y-auto"></div>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Violation</label>
        <select name="violation" required class="w-full px-4 py-2 bg-gray-800 rounded">
          <option value="">Select a penal code violation</option>
          <?php foreach ($penalTitles as $title): ?>
            <optgroup label="<?= htmlspecialchars($title['name']) ?>">
              <?php foreach ($sectionsByTitle[$title['id']] as $section): ?>
                <option value="<?= htmlspecialchars($section['code'] . ' - ' . $section['description']) ?>">
                  <?= htmlspecialchars($section['code']) ?> - <?= htmlspecialchars($section['description']) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Location</label>
        <input type="text" name="location" class="w-full px-4 py-2 bg-gray-800 rounded">
      </div>

      <div>
        <label class="block mb-1 font-semibold">Notes</label>
        <textarea name="notes" class="w-full px-4 py-2 bg-gray-800 rounded"></textarea>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Officer Signature</label>
        <input type="text" name="signature" required placeholder="Type your name" class="w-full px-4 py-2 bg-gray-800 rounded">
      </div>

      <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">Submit Warning</button>
    </form>
  </div>
</main>

<script>
  async function searchCivilians(query) {
    if (query.length < 2) return;
    const response = await fetch('../includes/search-civilians.php?name=' + encodeURIComponent(query));
    const results = await response.json();
    const list = document.getElementById('results');
    list.innerHTML = '';

    results.forEach(civ => {
      const item = document.createElement('div');
      item.className = 'px-3 py-2 hover:bg-gray-700 cursor-pointer';
      item.textContent = `${civ.name} (${civ.dob})`;
      item.onclick = () => {
        document.getElementById('civilian_id').value = civ.id;
        document.getElementById('civilian_display').value = civ.name + ' (' + civ.dob + ')';
        list.innerHTML = '';
      };
      list.appendChild(item);
    });
  }
</script>

<?php
// ✅ Make sure modal partials receive correct variable names
$penal_titles = $penalTitles;
$sections_by_title = $sectionsByTitle;
?>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
