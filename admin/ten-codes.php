<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
  header("Location: ../dashboard.php");
  exit;
}

// Fetch all 10-codes
[$res] = supabaseRequest("ten_codes?order=category.asc,title.asc", "GET");
$codes = json_decode($res, true) ?? [];

$grouped = [];

foreach ($codes as $c) {
  if (is_array($c) && isset($c['category'])) {
    $grouped[$c['category']][] = $c;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>10-Codes Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white p-6 min-h-screen">

<div class="max-w-5xl mx-auto">
  <h1 class="text-3xl font-bold mb-6">Manage 10-Codes</h1>

  <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="mb-6 bg-green-600 hover:bg-green-700 px-4 py-2 rounded font-medium">
    + Add New 10-Code
  </button>

  <?php foreach ($grouped as $category => $items): ?>
    <div class="mb-8">
      <h2 class="text-xl font-semibold border-b border-gray-700 mb-2 pb-1"><?= htmlspecialchars($category) ?></h2>
      <div class="space-y-2">
        <?php foreach ($items as $item): ?>
          <div class="bg-gray-800 p-4 rounded border border-gray-700">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-semibold text-lg"><?= htmlspecialchars($item['title']) ?></p>
                <div class="text-sm text-gray-300"><?= $item['description'] ?></div>
              </div>
              <form method="GET" action="edit-ten-code.php">
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                <button class="ml-4 bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded text-sm">Edit</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Add New Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-gray-800 rounded-lg p-6 w-full max-w-xl">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">Add New 10-Code</h2>
      <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
    </div>
    <form action="save-ten-code.php" method="POST">
      <label class="block mb-1 font-semibold">Category</label>
      <input type="text" name="category" class="w-full mb-4 px-3 py-2 rounded bg-gray-700 text-white" required>

      <label class="block mb-1 font-semibold">Title</label>
      <input type="text" name="title" class="w-full mb-4 px-3 py-2 rounded bg-gray-700 text-white" required>

      <label class="block mb-1 font-semibold">Description</label>
      <input type="hidden" name="description" id="descriptionInput">
      <div id="editor" class="bg-white text-black rounded mb-4" style="height: 150px;"></div>

      <button type="submit" onclick="document.getElementById('descriptionInput').value = quill.root.innerHTML" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded font-semibold">Save</button>
    </form>
  </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
  const quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'Describe this code in detail...',
    modules: {
      toolbar: [['bold', 'italic', 'underline'], ['link']]
    }
  });
</script>

</body>
</html>
