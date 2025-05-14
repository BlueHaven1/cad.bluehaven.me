<?php
session_start();
require_once '../includes/supabase.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit;
}

// Handle title & section creation/editing/deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_title'])) {
        $title_name = trim($_POST['title_name']);
        if (!empty($title_name)) {
            supabaseRequest("penal_titles", "POST", [['name' => $title_name]]);
        }
    }

    if (isset($_POST['update_title'])) {
        $title_id = $_POST['title_id'];
        $new_name = trim($_POST['new_name']);
        supabaseRequest("penal_titles?id=eq.$title_id", "PATCH", [['name' => $new_name]]);
    }

    if (isset($_POST['delete_title'])) {
        $title_id = $_POST['title_id'];
        supabaseRequest("penal_titles?id=eq.$title_id", "DELETE");
    }

    if (isset($_POST['create_section'])) {
        $section = [
            'title_id' => $_POST['title_id'],
            'code' => trim($_POST['code']),
            'description' => trim($_POST['description']),
            'fine' => $_POST['fine'] !== '' ? (int)$_POST['fine'] : null,
            'jail_time' => $_POST['jail_time'] !== '' ? (int)$_POST['jail_time'] : null
        ];
        supabaseRequest("penal_sections", "POST", [$section]);
    }

    if (isset($_POST['update_section'])) {
        $id = $_POST['section_id'];
        $updates = [
            'code' => $_POST['code'],
            'description' => $_POST['description'],
            'fine' => $_POST['fine'] !== '' ? (int)$_POST['fine'] : null,
            'jail_time' => $_POST['jail_time'] !== '' ? (int)$_POST['jail_time'] : null
        ];
        supabaseRequest("penal_sections?id=eq.$id", "PATCH", [$updates]);
    }

    if (isset($_POST['delete_section'])) {
        $id = $_POST['section_id'];
        supabaseRequest("penal_sections?id=eq.$id", "DELETE");
    }
}

// Fetch titles
[$titlesResp, $titlesCode] = supabaseRequest("penal_titles", "GET");
$penal_titles = $titlesCode === 200 ? json_decode($titlesResp, true) : [];

$title_ids = array_column($penal_titles, 'id');
$title_ids_query = implode(",", array_map('urlencode', $title_ids));

// Fetch sections
$sections_by_title = [];
if (!empty($title_ids_query)) {
    [$sectionsResp, $sectionsCode] = supabaseRequest("penal_sections?title_id=in.($title_ids_query)", "GET");
    $penal_sections = $sectionsCode === 200 ? json_decode($sectionsResp, true) : [];
    if (!is_array($penal_sections)) $penal_sections = [];

    foreach ($penal_sections as $s) {
        $tid = $s['title_id'];
        if (!isset($sections_by_title[$tid])) $sections_by_title[$tid] = [];
        $sections_by_title[$tid][] = $s;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Penal Codes - Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
    <?php include '../includes/header.php'; ?>
<?php include '../includes/adminsidebar.php'; ?>
<div class="flex-1 p-8">
  <h1 class="text-3xl font-bold mb-8">Penal Code Management</h1>

  <!-- Create Title -->
  <div class="bg-gray-800 p-6 rounded-xl mb-10">
    <h2 class="text-xl font-semibold mb-4">Create Penal Title</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="create_title" value="1">
      <input type="text" name="title_name" placeholder="Title name..." required class="w-full px-4 py-2 rounded bg-gray-700">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded font-semibold">Create Title</button>
    </form>
  </div>

  <!-- Create Section -->
  <div class="bg-gray-800 p-6 rounded-xl mb-10">
    <h2 class="text-xl font-semibold mb-4">Create Penal Section</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="create_section" value="1">
      <select name="title_id" required class="w-full px-4 py-2 rounded bg-gray-700">
        <?php foreach ($penal_titles as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="code" placeholder="Section code (e.g. § 2.1)" required class="w-full px-4 py-2 rounded bg-gray-700">
      <textarea name="description" placeholder="Description..." required class="w-full px-4 py-2 rounded bg-gray-700"></textarea>
      <input type="number" name="fine" placeholder="Fine (optional)" class="w-full px-4 py-2 rounded bg-gray-700">
      <input type="number" name="jail_time" placeholder="Jail Time (optional)" class="w-full px-4 py-2 rounded bg-gray-700">
      <button type="submit" class="bg-green-600 hover:bg-green-700 px-6 py-2 rounded font-semibold">Create Section</button>
    </form>
  </div>

  <!-- Titles and Sections -->
  <?php foreach ($penal_titles as $title): ?>
    <div class="bg-gray-800 rounded-xl p-6 mb-6">
      <form method="POST" class="flex justify-between items-center mb-4">
        <input type="hidden" name="title_id" value="<?= $title['id'] ?>">
        <input type="text" name="new_name" value="<?= htmlspecialchars($title['name']) ?>" class="bg-gray-700 px-4 py-2 rounded w-full max-w-md mr-4">
        <div class="flex gap-2">
          <button type="submit" name="update_title" class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 rounded">Update</button>
          <button type="submit" name="delete_title" onclick="return confirm('Delete this title and all its sections?')" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">Delete</button>
        </div>
      </form>

      <?php if (!empty($sections_by_title[$title['id']])): ?>
        <ul class="space-y-4">
          <?php foreach ($sections_by_title[$title['id']] as $sec): ?>
            <li class="bg-gray-700 p-4 rounded">
<form method="POST" class="grid grid-cols-12 gap-2 items-center">
  <input type="hidden" name="section_id" value="<?= $sec['id'] ?>">

  <input type="text" name="code" value="<?= htmlspecialchars($sec['code']) ?>" class="col-span-2 bg-gray-800 px-3 py-2 rounded text-sm">
  <input type="text" name="description" value="<?= htmlspecialchars($sec['description']) ?>" class="col-span-4 bg-gray-800 px-3 py-2 rounded text-sm">
  <input type="number" name="fine" value="<?= $sec['fine'] ?>" placeholder="Fine" class="col-span-2 bg-gray-800 px-3 py-2 rounded text-sm">
  <input type="number" name="jail_time" value="<?= $sec['jail_time'] ?>" placeholder="Jail Time" class="col-span-2 bg-gray-800 px-3 py-2 rounded text-sm">

  <div class="col-span-2 flex gap-2">
    <button type="submit" name="update_section" class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded text-sm">Update</button>
    <button type="submit" name="delete_section" onclick="return confirm('Delete this section?')" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded text-sm">Delete</button>
  </div>
</form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="text-gray-400">No sections yet under this title.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>
