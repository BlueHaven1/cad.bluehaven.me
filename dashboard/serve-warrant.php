<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active'])) {
  header("Location: ../patrol.php");
  exit;
}

$officerId = $_SESSION['user_id'];
$status = $_SESSION['status'] ?? '10-7';
$username = $_SESSION['username'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'N/A';
$callsign = $_SESSION['callsign'] ?? 'None';
$success = false;
$error = '';

// Handle serving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['serve_warrant_id'])) {
  $warrant_id = $_POST['serve_warrant_id'];

  $update = [
    'is_served' => true,
    'served_by' => $officerId,
    'served_at' => date('c')
  ];

  [$resp, $code] = supabaseRequest("warrants?id=eq.$warrant_id", "PATCH", [$update]);

  $success = $code === 204;
  if (!$success) $error = 'Failed to serve warrant.';
}

// Fetch active warrants
[$warrantResp] = supabaseRequest("warrants?is_served=is.false", "GET");
$warrants = json_decode($warrantResp, true) ?? [];

$civilians = [];
foreach ($warrants as $w) {
  $cid = $w['civilian_id'];
  if (!isset($civilians[$cid])) {
    [$civResp] = supabaseRequest("civilians?id=eq.$cid", "GET");
    $civilians[$cid] = json_decode($civResp, true)[0] ?? null;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Serve Warrants - MDT</title>
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
      <a href="warning.php" class="block px-3 py-2 rounded hover:bg-gray-700">Written Warning</a>
      <a href="arrest.php" class="block px-3 py-2 rounded hover:bg-gray-700">Arrest Report</a>
      <a href="file-warrant.php" class="block px-3 py-2 rounded hover:bg-gray-700">File Warrant</a>
      <a href="serve-warrant.php" class="block px-3 py-2 rounded bg-gray-700">Serve Warrant</a>
      <a href="penal-code.php" class="block px-3 py-2 rounded hover:bg-gray-700">Penal Code</a>
      <a href="10-codes.php" class="block px-3 py-2 rounded hover:bg-gray-700">10-Codes</a>
    </nav>
  </div>
  <a href="exit-mdt.php" class="block px-3 py-2 mt-6 rounded bg-red-600 hover:bg-red-700 text-center font-semibold">
    Exit MDT
  </a>
</aside>

<main class="ml-64 p-8 w-full bg-gray-900 min-h-screen">
  <div class="max-w-5xl mx-auto">
    <h1 class="text-4xl font-bold mb-6">Active Warrants</h1>

    <?php if ($success): ?>
      <p class="text-green-500 mb-4">Warrant served successfully.</p>
    <?php elseif ($error): ?>
      <p class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (empty($warrants)): ?>
      <p class="text-gray-400">No active warrants.</p>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($warrants as $w): 
          $civ = $civilians[$w['civilian_id']] ?? null;
        ?>
          <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
            <h2 class="text-2xl font-semibold mb-2"><?= htmlspecialchars($civ['name'] ?? 'Unknown Civilian') ?></h2>
            <p class="text-sm text-gray-400 mb-2">DOB: <?= htmlspecialchars($civ['dob'] ?? 'N/A') ?> | Phone: <?= htmlspecialchars($civ['phone'] ?? 'N/A') ?></p>
            <p><strong>Violation:</strong> <?= htmlspecialchars($w['violation']) ?></p>
            <p><strong>Fine:</strong> $<?= $w['fine'] ?? '0' ?></p>
            <p><strong>Jail Time:</strong> <?= $w['jail_time'] ?? '0' ?> mins</p>
            <p><strong>Reason:</strong> <?= htmlspecialchars($w['reason']) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($w['location']) ?></p>
            <p><strong>Filed By:</strong> <?= htmlspecialchars($w['signature']) ?></p>

            <form method="POST" class="mt-4">
              <input type="hidden" name="serve_warrant_id" value="<?= $w['id'] ?>">
              <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded font-semibold">Mark as Served</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
