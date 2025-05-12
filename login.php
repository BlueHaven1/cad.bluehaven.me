<?php
// login.php
session_start();
require_once 'includes/supabase.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Query Supabase users table by email
    $filter = ['email' => "eq.$email"];
    [$response, $status] = supabaseRequest('users', 'GET', $filter);

    $data = json_decode($response, true);

    if ($status === 200 && count($data) > 0) {
        $user = $data[0];

        if (password_verify($password, $user['password'])) {
            if ($user['is_approved']) {
                // Store session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: pending.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - San Andreas CAD</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center h-screen">
  <form method="POST" class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
    <div class="flex justify-center mb-6">
      <img src="/assets/uploads/logo.png" alt="Logo" class="w-20 h-20 rounded-full">
    </div>
    <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>

    <?php if (!empty($error)): ?>
      <p class="text-red-500 mb-4"><?= $error ?></p>
    <?php endif; ?>

    <label class="block mb-2">Email</label>
    <input type="email" name="email" required class="w-full px-4 py-2 rounded bg-gray-700 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">

    <label class="block mb-2">Password</label>
    <input type="password" name="password" required class="w-full px-4 py-2 rounded bg-gray-700 mb-6 focus:outline-none focus:ring-2 focus:ring-blue-500">

    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 py-2 rounded text-white font-semibold">Login</button>

    <div class="mt-4 text-center">
      <p class="text-gray-400 text-sm">
        Don’t have an account?
        <a href="register.php" class="text-blue-500 hover:underline">Register</a>
      </p>
    </div>
  </form>
</body>
</html>
