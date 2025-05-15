<?php
require_once 'supabase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request');
}

$id = $_POST['id'] ?? '';

if (!$id) {
    http_response_code(400);
    exit('Missing call ID');
}

// DELETE the call
[$res, $code] = supabaseRequest("calls?id=eq.$id", "DELETE");

if ($code >= 200 && $code < 300) {
    header("Location: ../dashboard/saco-mdt.php");
    exit;
} else {
    http_response_code(500);
    echo "Failed to close call";
}
