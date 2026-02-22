<?php
// nav.php — 共通ナビゲーションバー
// $currentPage, $navWidth を呼び出し側で設定してから include
$currentPage = $currentPage ?? '';
$navWidth = $navWidth ?? 'max-w-3xl';
?>
<nav class="bg-white border-b border-gray-200 sticky top-0 z-50 no-print shadow-sm">
  <div class="<?= $navWidth ?> mx-auto px-4 h-12 flex items-center gap-2 text-sm">
    <a href="schedule.php"
       class="px-3 py-1.5 rounded-md font-bold whitespace-nowrap transition <?= $currentPage === 'schedule' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
      📅 スケジュール
    </a>
    <a href="diagnose.php"
       class="px-3 py-1.5 rounded-md font-bold whitespace-nowrap transition <?= $currentPage === 'diagnose' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
      📋 診断チャート
    </a>
    <a href="register.php"
       class="px-3 py-1.5 rounded-md font-bold whitespace-nowrap transition <?= $currentPage === 'register' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
      📝 申請登録
    </a>
    <div class="flex-1"></div>
    <!-- 関連リンク -->
    <div class="relative" id="navDropdown">
      <button onclick="toggleNavDropdown()" class="flex items-center gap-1 px-3 py-1.5 font-bold text-gray-600 hover:bg-gray-100 rounded-md transition whitespace-nowrap">
        🔗 関連リンク
        <svg class="w-3.5 h-3.5 transition-transform" id="navDropdownArrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
      <div id="navDropdownMenu" class="hidden absolute right-0 top-full mt-1 w-72 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-[9999]">
        <div class="px-3 py-1.5 text-xs font-bold text-gray-400 uppercase tracking-wider">申請関連</div>
        <a href="https://www.mext.go.jp/a_menu/ikusei/manabinaoshi/mext_00030.html" target="_blank" rel="noopener"
           class="flex items-start gap-2 px-3 py-2 hover:bg-gray-50 transition">
          <span class="text-base mt-0.5">🏛</span>
          <div>
            <div class="text-sm font-bold text-gray-800">文科省 公募受付ページ</div>
            <div class="text-xs text-gray-400">mext.go.jp</div>
          </div>
        </a>
        <a href="https://lp.jollygoodplus.com/reskiling/index.html" target="_blank" rel="noopener"
           class="flex items-start gap-2 px-3 py-2 hover:bg-gray-50 transition">
          <span class="text-base mt-0.5">📦</span>
          <div>
            <div class="text-sm font-bold text-gray-800">スターターパッケージ</div>
            <div class="text-xs text-gray-400">lp.jollygoodplus.com</div>
          </div>
        </a>
        <div class="border-t border-gray-100 my-1.5"></div>
        <div class="px-3 py-1.5 text-xs font-bold text-gray-400 uppercase tracking-wider">VR関連</div>
        <a href="https://lib.jollygoodplus.com/" target="_blank" rel="noopener"
           class="flex items-start gap-2 px-3 py-2 hover:bg-gray-50 transition">
          <span class="text-base mt-0.5">🎬</span>
          <div>
            <div class="text-sm font-bold text-gray-800">JOLLYGOOD+ VRライブラリ</div>
            <div class="text-xs text-gray-400">VRコンテンツを試聴・検索</div>
          </div>
        </a>
        <a href="https://pov-world-generator.web.app/" target="_blank" rel="noopener"
           class="flex items-start gap-2 px-3 py-2 hover:bg-gray-50 transition">
          <span class="text-base mt-0.5">🌐</span>
          <div>
            <div class="text-sm font-bold text-gray-800">POV World Generator</div>
            <div class="text-xs text-gray-400">現場VRの映像を試しに見る</div>
          </div>
        </a>
        <a href="https://jollygoodplus.com/" target="_blank" rel="noopener"
           class="flex items-start gap-2 px-3 py-2 hover:bg-gray-50 transition">
          <span class="text-base mt-0.5">🏢</span>
          <div>
            <div class="text-sm font-bold text-gray-800">JOLLYGOOD+</div>
            <div class="text-xs text-gray-400">jollygoodplus.com</div>
          </div>
        </a>
      </div>
    </div>
  </div>
</nav>
<script>
function toggleNavDropdown() {
  const menu = document.getElementById('navDropdownMenu');
  const arrow = document.getElementById('navDropdownArrow');
  menu.classList.toggle('hidden');
  arrow.style.transform = menu.classList.contains('hidden') ? '' : 'rotate(180deg)';
}
document.addEventListener('click', function(e) {
  const dd = document.getElementById('navDropdown');
  if (dd && !dd.contains(e.target)) {
    document.getElementById('navDropdownMenu').classList.add('hidden');
    document.getElementById('navDropdownArrow').style.transform = '';
  }
});
</script>
