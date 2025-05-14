<?php
function sendDiscordWarrantLog($type, $data) {
  $webhookUrl = 'https://discord.com/api/webhooks/1372324426174955601/_ZSgyhJ5_AbuD21AYxnlMuNYWb44288-iWXwymAZIaEzeX8pLqiPVV0ICiaL3F4l4gAQ';

  if ($type === 'file') {
    $msg = "**📄 New Warrant Filed**\n"
         . "**Officer:** {$data['officer']} ({$data['department']})\n"
         . "**Civilian:** {$data['civilian']} ({$data['dob']})\n"
         . "**Violation:** {$data['violation']}\n"
         . "**Fine:** \$" . ($data['fine'] ?? 0) . " | **Jail Time:** {$data['jail_time']} mins\n"
         . "**Location:** {$data['location']}\n"
         . "**Reason:** {$data['reason']}";
  }

  if ($type === 'serve') {
    $msg = "**✅ Warrant Served**\n"
         . "**Served by:** {$data['served_by']} ({$data['served_dept']})\n"
         . "**Target:** {$data['civilian']} ({$data['dob']})\n"
         . "**Originally filed by:** {$data['filed_by']} ({$data['filed_dept']})";
  }

  $payload = json_encode(['content' => $msg]);
  $ch = curl_init($webhookUrl);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_exec($ch);
  curl_close($ch);
}
