<?php
require_once 'supabase.php';

$name = $_GET['name'] ?? '';
if (strlen($name) < 2) {
    echo json_encode([]);
    exit;
}

[$resp] = supabaseRequest("civilians?name=ilike.*" . urlencode($name) . "*", "GET");
echo $resp;
