<?php
// Fetch titles + sections
require_once '../includes/supabase.php';

[$titlesResp] = supabaseRequest("penal_titles?order=created_at.asc", "GET");
$titles = json_decode($titlesResp, true) ?? [];

[$sectionsResp] = supabaseRequest("penal_sections?order=title_id.asc", "GET");
$sectionsRaw = json_decode($sectionsResp, true) ?? [];

$sections = [];
foreach ($sectionsRaw as $s) {
  $sections[$s['title_id']][] = $s;
}
?>

<!-- Trigger Button (You can move this into your header/footer) -->
<button onclick="openPenalModal()" class="fixed bottom-6 right-6 bg-gray-800 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded shadow-lg z-40">
  Penal Code
</button>

<!-- Penal Code Modal -->
<div id="penalModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-gray-900 text-white w-full max-w-3xl max-h-[80vh] rounded-lg p-6 overflow-y-auto transform scale-95 opacity-0 transition-all duration-300 modal-inner">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">Penal Code</h2>
      <button onclick="closePenalModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
    </div>

    <?php foreach ($titles as $title): ?>
      <div class="mb-4">
        <h3 class="text-lg font-semibold mb-1"><?= htmlspecialchars($title['title']) ?></h3>
        <p class="text-sm text-gray-400 mb-2"><?= htmlspecialchars($title['description']) ?></p>
        <?php if (!empty($sections[$title['id']])): ?>
          <ul class="space-y-1 pl-4 text-sm">
            <?php foreach ($sections[$title['id']] as $s): ?>
              <li class="border-l-2 border-gray-700 pl-3">
                <span class="text-white font-medium"><?= htmlspecialchars($s['code']) ?></span>: <?= htmlspecialchars($s['description']) ?>
                <?php if ($s['fine'] || $s['jail_time']): ?>
                  <div class="text-gray-400 text-xs">
                    <?= $s['fine'] ? "Fine: \$" . $s['fine'] : '' ?>
                    <?= $s['jail_time'] ? " • Jail: " . $s['jail_time'] . " mins" : '' ?>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function openPenalModal() {
  const modal = document.getElementById('penalModal');
  const inner = modal.querySelector('.modal-inner');
  modal.classList.remove('hidden');
  setTimeout(() => {
    inner.classList.remove('scale-95', 'opacity-0');
    inner.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closePenalModal() {
  const modal = document.getElementById('penalModal');
  const inner = modal.querySelector('.modal-inner');
  inner.classList.remove('scale-100', 'opacity-100');
  inner.classList.add('scale-95', 'opacity-0');
  setTimeout(() => {
    modal.classList.add('hidden');
  }, 300);
}
</script>
