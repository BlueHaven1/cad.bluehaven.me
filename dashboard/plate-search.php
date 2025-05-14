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

$results = [];
$searchPlate = '';

if (!empty($_GET['plate'])) {
  $searchPlate = trim($_GET['plate']);
  [$resp, $code] = supabaseRequest("civilian_vehicles?plate=ilike.*" . urlencode($searchPlate) . "*", "GET");
  $results = json_decode($resp, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Plate Search - MDT</title>
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
      <a href="plate-search.php" class="block px-3 py-2 rounded bg-gray-700">Plate Search</a>
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

<!-- Main Content -->
<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-4xl font-bold mb-6">Plate Search</h1>

    <form method="GET" class="flex gap-4 mb-6">
      <input
        type="text"
        name="plate"
        placeholder="Enter plate number"
        value="<?= htmlspecialchars($searchPlate) ?>"
        class="flex-1 px-4 py-2 rounded bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">
        Search
      </button>
    </form>

    <?php if (!empty($searchPlate)): ?>
      <?php if (!empty($results)): ?>
        <div class="space-y-4">
          <?php foreach ($results as $v): ?>
            <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
              <h2 class="text-xl font-semibold"><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></h2>
              <p class="text-sm text-gray-400">
                Plate: <?= htmlspecialchars($v['plate']) ?> |
                Color: <?= htmlspecialchars($v['color']) ?>
                <?= !empty($v['is_stolen']) ? '<span class="text-red-500 ml-2">🚨 Stolen</span>' : '' ?>
              </p>

              <?php
                $civId = $v['civilian_id'];
                [$civResp] = supabaseRequest("civilians?id=eq.$civId", "GET");
                $civ = json_decode($civResp, true)[0] ?? null;
              ?>
              <?php if ($civ): ?>
                <p class="text-sm text-gray-400">
                  Registered Owner:
                  <a href="civilian.php?id=<?= $civ['id'] ?>" class="text-blue-400 hover:underline">
                    <?= htmlspecialchars($civ['name']) ?>
                  </a> (<?= htmlspecialchars($civ['dob']) ?>)
                </p>
                <p class="text-sm text-gray-400">Phone: <?= htmlspecialchars($civ['phone']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400">No matching plates found.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<?php include '../partials/penal-modal.php'; ?>
<?php include '../partials/ten-codes-modal.php'; ?>
</body>
</html>
