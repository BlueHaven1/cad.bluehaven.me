<?php
function sendDiscordWarrantLog($type, $data) {
  $webhookUrl = 'https://discord.com/api/webhooks/1372251288817107045/voxOXT1Dn5DlNR6t7680GZl2VG8n3Cq0VFkHp3-AygLGBP8__M44rzVvFZ_aO8vz2hSL';

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
