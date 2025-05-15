<?php
require_once 'supabase.php';

[$res] = supabaseRequest("alerts", "GET");
$alerts = json_decode($res, true) ?? [];

echo json_encode($alerts);
