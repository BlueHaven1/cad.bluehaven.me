<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['mdt_active'])) {
    header("Location: ../patrol.php");
    exit;
}
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
<?php include 'mdt-sidebar.php'; ?>

<main class="ml-64 p-8 w-full bg-gray-900">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Name Search</h1>

    <form method="GET" class="flex gap-4 mb-6">
      <input
        type="text"
        name="name"
        placeholder="Enter full or partial name"
        class="flex-1 px-4 py-2 rounded bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        value="<?= htmlspecialchars($_GET['name'] ?? '') ?>"
      >
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">
        Search
      </button>
    </form>

    <?php
    if (!empty($_GET['name'])) {
      $name = trim($_GET['name']);
      [$response, $status] = supabaseRequest("civilians?name=ilike.*$name*", "GET");
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
        echo '<p class="text-gray-400">No results found.</p>';
      }
    }
    ?>
  </div>
</main>
</body>
</html>
