<?php
// includes/supabase.php

define('SUPABASE_URL', 'https://kaxjegmdsaqjvkvuyeur.supabase.co'); // replace this
define('SUPABASE_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtheGplZ21kc2FxanZrdnV5ZXVyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDY3OTk5OTAsImV4cCI6MjA2MjM3NTk5MH0.K4HNlgUnyw9VuGHGVZASPHq7DRA-QmhtmwfGfEf33l4'); // replace this


function supabaseRequest($endpoint, $method = 'GET', $body = null) {
    $ch = curl_init();

    $headers = [
        "apikey: " . SUPABASE_API_KEY,
        "Authorization: Bearer " . SUPABASE_API_KEY,
        "Content-Type: application/json"
    ];

    // Add Prefer header for POST to return inserted data
    if (strtoupper($method) === 'POST') {
        $headers[] = "Prefer: return=representation";
    }

    $url = SUPABASE_URL . "/rest/v1/" . $endpoint;

    // Append query parameters for GET
    if ($method === 'GET' && is_array($body)) {
        $url .= '?' . http_build_query($body);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Send JSON body for POST, PUT, etc.
    if ($method !== 'GET' && $body) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$response, $httpCode];
}
