<?php
if (!function_exists('supabaseRequest')) {
  require_once '../includes/supabase.php';
}

if (!isset($penal_titles) || !isset($sections_by_title)) {
  [$titlesResp] = supabaseRequest("penal_titles?order=created_at.asc", "GET");
  $penal_titles = json_decode($titlesResp, true) ?? [];

  [$sectionsResp] = supabaseRequest("penal_sections?order=title_id.asc", "GET");
  $penal_sections = json_decode($sectionsResp, true) ?? [];

  $sections_by_title = [];
  foreach ($penal_sections as $s) {
    $sections_by_title[$s['title_id']][] = $s;
  }
}
?>

<!-- Button -->
<button onclick="openPenalModal()" class="fixed bottom-6 right-6 bg-gray-800 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded shadow-lg z-40">
  Penal Code
</button>

<!-- Modal -->
<div id="penalModal" class="fixed inset-0 hidden flex items-center justify-center bg-black bg-opacity-50 z-50" onclick="closeModalOnOutsideClick(event, 'penalModal')">
  <div class="bg-gray-900 text-white w-full max-w-3xl max-h-[80vh] rounded-lg p-6 overflow-y-auto transform scale-95 opacity-0 transition-all duration-300 modal-inner">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">Penal Code</h2>
      <button onclick="closePenalModal()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
    </div>

    <div class="space-y-3">
      <?php foreach ($penal_titles as $title):
        $title_id = $title['id'];
        $title_sections = $sections_by_title[$title_id] ?? [];
      ?>
        <div class="border border-gray-700 rounded">
          <button
            class="w-full flex justify-between items-center px-4 py-3 bg-gray-800 hover:bg-gray-700 transition"
            onclick="toggleSection('section-<?= $title_id ?>', this)">
            <span class="text-left">
              <span class="block font-semibold"><?= htmlspecialchars($title['name']) ?></span>
              <span class="block text-sm text-gray-400"><?= htmlspecialchars($title['description']) ?></span>
            </span>
            <span class="text-xl font-bold transition-transform transform">+</span>
          </button>
          <div id="section-<?= $title_id ?>" class="max-h-0 overflow-hidden transition-all duration-300 ease-in-out bg-gray-850 px-4">
            <?php foreach ($title_sections as $s): ?>
              <div class="py-2 border-t border-gray-700">
                <div class="text-sm font-medium"><?= htmlspecialchars($s['code']) ?> - <?= htmlspecialchars($s['description']) ?></div>
                <div class="text-xs text-gray-400">
                  <?= $s['fine'] ? "Fine: \$" . htmlspecialchars($s['fine']) : '' ?>
                  <?= $s['jail_time'] ? " • Jail: " . htmlspecialchars($s['jail_time']) . " mins" : '' ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
function openPenalModal() {
  const modal = document.getElementById('penalModal');
  const inner = modal.querySelector('.modal-inner');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
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
    modal.classList.remove('flex');
  }, 300);
}

function toggleSection(id, button) {
  const section = document.getElementById(id);
  const icon = button.querySelector('span:last-child');

  if (section.style.maxHeight && section.style.maxHeight !== '0px') {
    section.style.maxHeight = '0px';
    icon.textContent = '+';
  } else {
    section.style.maxHeight = section.scrollHeight + 'px';
    icon.textContent = '−';
  }
}

function closeModalOnOutsideClick(event, modalId) {
  const modal = document.getElementById(modalId);
  const modalInner = modal.querySelector('.modal-inner');

  // Check if the click was outside the modal content
  if (event.target === modal) {
    // If the modal ID is penalModal, call closePenalModal
    if (modalId === 'penalModal') {
      closePenalModal();
    }
    // If the modal ID is tenModal, call closeTenModal
    else if (modalId === 'tenModal') {
      closeTenModal();
    }
  }
}
</script>
