<?php
require_once 'supabase.php';

$plate = $_GET['plate'] ?? '';
if (strlen($plate) < 2) {
    echo json_encode([]);
    exit;
}

// Search for vehicles
[$resp, $status] = supabaseRequest("civilian_vehicles?plate=ilike.*" . urlencode($plate) . "*", "GET");
$vehicles = json_decode($resp, true);

$results = [];

if ($status === 200 && is_array($vehicles)) {
    foreach ($vehicles as $v) {
        $vehicleData = [
            'make' => $v['make'] ?? '',
            'model' => $v['model'] ?? '',
            'plate' => $v['plate'] ?? '',
            'color' => $v['color'] ?? '',
            'is_stolen' => !empty($v['is_stolen']),
            'owner' => null
        ];

        // Fetch civilian (owner) info if available
        if (!empty($v['civilian_id'])) {
            [$civResp] = supabaseRequest("civilians?id=eq." . $v['civilian_id'], "GET");
            $civ = json_decode($civResp, true)[0] ?? null;
            if ($civ) {
                $vehicleData['owner'] = [
                    'id' => $civ['id'],
                    'name' => $civ['name'] ?? '',
                    'dob' => $civ['dob'] ?? '',
                    'phone' => $civ['phone'] ?? '',
                ];
            }
        }

        $results[] = $vehicleData;
    }
}

echo json_encode($results);
