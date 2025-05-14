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
      <a href="mdt.php" class="block px-3 py-2 rounded hover:bg-gray-700">Dashboard</a>
      <a href="name-search.php" class="block px-3 py-2 rounded bg-gray-700">Name Search</a>
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

<!-- Main -->
<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-4xl font-bold mb-8">Name Search</h1>

    <form method="GET" class="flex gap-4 mb-6">
      <input type="text" name="name" placeholder="Enter name" class="flex-1 px-4 py-2 rounded bg-gray-800 text-white" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">Search</button>
    </form>

    <?php
    if (!empty($_GET['name'])) {
      $name = trim($_GET['name']);
      $encodedName = urlencode($name);
      [$response, $status] = supabaseRequest("civilians?name=ilike.*$encodedName*", "GET");
      $results = json_decode($response, true);

      if ($status === 200 && !empty($results)) {
        echo '<div class="space-y-4">';
        foreach ($results as $civ) {
          echo '<a href="civilian.php?id=' . $civ['id'] . '" class="block bg-gray-800 p-4 rounded-lg shadow border border-gray-700 hover:bg-gray-700 transition">';
          echo '<h2 class="text-xl font-semibold text-white">' . htmlspecialchars($civ['name']) . '</h2>';
          echo '<p class="text-sm text-gray-400">DOB: ' . htmlspecialchars($civ['dob']) . '</p>';
          echo '<p class="text-sm text-gray-400">Phone: ' . htmlspecialchars($civ['phone']) . '</p>';
          echo '<p class="text-sm text-gray-400">Address: ' . htmlspecialchars($civ['address']) . '</p>';
          echo '</a>';
        }
        echo '</div>';
      } else {
        echo '<p class="text-gray-400">No civilians found.</p>';
      }
    }
    ?>
  </div>
</main>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
