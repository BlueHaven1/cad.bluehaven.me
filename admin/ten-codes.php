<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
  header("Location: ../dashboard.php");
  exit;
}

$success = false;
$error = '';
$content = '';

// Handle POST save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $content = $_POST['content'] ?? '';
  $body = ['content' => $content];

  // Always update the first row
  [$updateRes, $updateCode] = supabaseRequest("ten_codes?id=eq.1", "PATCH", [$body]);

  $success = $updateCode === 204;
  if (!$success) $error = 'Failed to update 10-Codes.';
}

// Fetch current content
[$fetchRes] = supabaseRequest("ten_codes?id=eq.1", "GET");
$data = json_decode($fetchRes, true);
$content = $data[0]['content'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit 10-Codes</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white p-6 min-h-screen">
  <div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-4">Edit 10-Codes (Public MDT)</h1>

    <?php if ($success): ?>
      <p class="text-green-500 mb-4">10-Codes updated successfully!</p>
    <?php elseif ($error): ?>
      <p class="text-red-500 mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" onsubmit="document.getElementById('hiddenContent').value = quill.root.innerHTML">
      <input type="hidden" name="content" id="hiddenContent">
      <div id="editor" class="bg-white text-black rounded mb-4" style="min-height: 300px;"></div>

      <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">Save 10-Codes</button>
    </form>
  </div>

  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
  <script>
    const quill = new Quill('#editor', {
      theme: 'snow',
      placeholder: 'Write all 10-Code content here...',
      modules: {
        toolbar: [['bold', 'italic', 'underline'], ['link'], [{ 'list': 'bullet' }]]
      }
    });
    quill.root.innerHTML = <?= json_encode($content) ?>;
  </script>
</body>
</html>
