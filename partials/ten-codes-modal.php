<?php
require_once '../includes/supabase.php';
[$res] = supabaseRequest("ten_codes?id=eq.1", "GET");
$data = json_decode($res, true);
$content = $data[0]['content'] ?? '<p>No 10-Codes available.</p>';
?>

<!-- Button -->
<button onclick="openTenModal()" class="fixed bottom-6 right-24 bg-gray-800 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded shadow-lg z-40">
  10-Codes
</button>

<!-- Modal -->
<div id="tenModal" class="fixed inset-0 hidden flex items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-gray-900 text-white w-full max-w-3xl max-h-[80vh] rounded-lg p-6 overflow-y-auto transform scale-95 opacity-0 transition-all duration-300 modal-inner">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold">10-Codes</h2>
      <button onclick="closeTenModal()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
    </div>
    <div class="prose prose-invert max-w-none text-sm"><?= $content ?></div>
  </div>
</div>

<script>
function openTenModal() {
  const modal = document.getElementById('tenModal');
  const inner = modal.querySelector('.modal-inner');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => {
    inner.classList.remove('scale-95', 'opacity-0');
    inner.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closeTenModal() {
  const modal = document.getElementById('tenModal');
  const inner = modal.querySelector('.modal-inner');
  inner.classList.remove('scale-100', 'opacity-100');
  inner.classList.add('scale-95', 'opacity-0');
  setTimeout(() => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }, 300);
}
</script>
