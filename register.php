<?php
// register.php
require_once 'includes/supabase.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // hash it locally
    $is_approved = false;

    // Prepare user data
    $data = [
        [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'is_approved' => $is_approved
        ]
    ];

    // Make request to Supabase
    [$response, $status] = supabaseRequest('users', 'POST', $data);

    if ($status >= 200 && $status < 300) {
        header("Location: pending.php");
        exit;
    } else {
        $error = "Registration failed. Email may already be in use.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center h-screen">
  <form method="POST" class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
    <div class="flex justify-center mb-6">
      <img src="/assets/uploads/logo.png" alt="Logo" class="w-20 h-20 rounded-full">
    </div>
    <h1 class="text-2xl font-bold mb-6 text-center">Register</h1>

    <?php if (!empty($error)): ?>
      <p class="text-red-500 mb-4"><?= $error ?></p>
    <?php endif; ?>

    <label class="block mb-2">Username</label>
    <input type="text" name="username" required class="w-full px-4 py-2 rounded bg-gray-700 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">

    <label class="block mb-2">Email</label>
    <input type="email" name="email" required class="w-full px-4 py-2 rounded bg-gray-700 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">

    <label class="block mb-2">Password</label>
    <input type="password" name="password" required class="w-full px-4 py-2 rounded bg-gray-700 mb-6 focus:outline-none focus:ring-2 focus:ring-blue-500">

    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 py-2 rounded text-white font-semibold">Register</button>
    <div class="mt-4 text-center">
  <p class="text-gray-400 text-sm">
    Already a member?
    <a href="login.php" class="text-blue-500 hover:underline">Login</a>
  </p>
</div>
  </form>
</body>
</html>
