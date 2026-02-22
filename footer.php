<?php
// footer.php — 共通フッター
// 使い方: <?php include 'footer.php'; ?>
?>
<footer class="bg-gray-800 text-gray-300 mt-12 no-print">
  <div class="max-w-5xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
      <!-- 申請ツール -->
      <div>
        <h4 class="font-bold text-white mb-3 text-xs uppercase tracking-wider">申請ツール</h4>
        <ul class="space-y-2">
          <li><a href="schedule.php" class="hover:text-white transition">📅 スケジュール・申し込み方法</a></li>
          <li><a href="diagnose.php" class="hover:text-white transition">📋 申請メニュー診断チャート</a></li>
          <li><a href="register.php" class="hover:text-white transition">📝 申請書作成ツール登録</a></li>
        </ul>
      </div>
      <!-- 申請関連 -->
      <div>
        <h4 class="font-bold text-white mb-3 text-xs uppercase tracking-wider">申請関連リンク</h4>
        <ul class="space-y-2">
          <li><a href="https://www.mext.go.jp/a_menu/ikusei/manabinaoshi/mext_00030.html" target="_blank" rel="noopener" class="hover:text-white transition">🏛 文科省 公募受付ページ</a></li>
          <li><a href="https://lp.jollygoodplus.com/reskiling/index.html" target="_blank" rel="noopener" class="hover:text-white transition">📦 スターターパッケージ</a></li>
        </ul>
      </div>
      <!-- VR関連 -->
      <div>
        <h4 class="font-bold text-white mb-3 text-xs uppercase tracking-wider">VR関連</h4>
        <ul class="space-y-2">
          <li><a href="https://lib.jollygoodplus.com/" target="_blank" rel="noopener" class="hover:text-white transition">🎬 VRライブラリ</a></li>
          <li><a href="https://pov-world-generator.web.app/" target="_blank" rel="noopener" class="hover:text-white transition">🌐 POV World Generator</a></li>
          <li><a href="https://jollygoodplus.com/" target="_blank" rel="noopener" class="hover:text-white transition">🏢 JOLLYGOOD+</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-gray-700 mt-6 pt-4 text-center text-xs text-gray-500">
      Powered by <strong class="text-gray-400">株式会社ジョリーグッド</strong>
    </div>
  </div>
</footer>
