<?php
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>スケジュール・申し込み方法｜リスキリング・エコシステム構築事業</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Hiragino Kaku Gothic ProN', 'Meiryo', sans-serif; }
  .gradient-header { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
  .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
  .timeline-dot { width: 16px; height: 16px; border-radius: 50%; border: 3px solid #2563eb; background: #fff; flex-shrink: 0; z-index: 1; }
  .timeline-dot.done { background: #2563eb; }
  .timeline-dot.current { background: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,.2); }
  .timeline-line { position: absolute; left: 7px; top: 16px; bottom: 0; width: 2px; background: #dbeafe; }
  .ext-link { color: #2563eb; text-decoration: underline; word-break: break-all; }
  .ext-link:hover { color: #1d4ed8; }
  .menu-tab { cursor: pointer; padding: 10px 20px; border-radius: 8px 8px 0 0; font-weight: 700; font-size: 0.9rem; transition: all .2s; }
  .menu-tab.active { background: #fff; color: #1e3a5f; box-shadow: 0 -2px 8px rgba(0,0,0,.06); }
  .menu-tab:not(.active) { background: #e2e8f0; color: #64748b; }
  .menu-tab:not(.active):hover { background: #cbd5e1; }
  .menu-panel { display: none; }
  .menu-panel.active { display: block; }
  .info-row { display: flex; gap: 8px; align-items: baseline; padding: 6px 0; }
  .info-label { font-weight: 700; color: #374151; white-space: nowrap; min-width: 100px; }
</style>
</head>
<body class="min-h-screen bg-slate-50">

<!-- Header -->
<header class="gradient-header text-white py-5 px-4 shadow-md">
  <div class="max-w-3xl mx-auto">
    <div class="text-xs font-semibold tracking-widest opacity-70 mb-1">文部科学省 産学連携リ・スキリング・エコシステム構築事業</div>
    <h1 class="text-lg font-bold">スケジュール・申し込み方法</h1>
  </div>
</header>
<?php $currentPage = 'schedule'; include 'nav.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-8 space-y-8">

<!-- ===== 公募受付ページ ===== -->
<div class="card p-6">
  <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
    <span class="text-2xl">🏛</span> 公募受付ページ（文部科学省）
  </h2>
  <a href="https://www.mext.go.jp/a_menu/ikusei/manabinaoshi/mext_00030.html" target="_blank" rel="noopener" class="ext-link text-sm">
    https://www.mext.go.jp/a_menu/ikusei/manabinaoshi/mext_00030.html
  </a>
</div>

<!-- ===== スケジュール ===== -->
<div class="card p-6">
  <h2 class="text-lg font-bold text-gray-800 mb-5 flex items-center gap-2">
    <span class="text-2xl">📅</span> スケジュール
  </h2>

  <div class="relative pl-8 space-y-6">
    <div class="timeline-line"></div>

    <!-- Step 1 -->
    <div class="relative flex items-start gap-4">
      <div class="timeline-dot current absolute -left-8"></div>
      <div class="flex-1">
        <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">STEP 1</div>
        <h3 class="font-bold text-gray-800">参加表明書の提出</h3>
        <div class="mt-1 bg-red-50 border border-red-200 rounded-lg px-3 py-2 inline-block">
          <span class="text-red-700 font-bold text-sm">締切: 令和8年3月3日（火）正午</span>
        </div>
        <p class="text-sm text-gray-600 mt-2">下記の参加表明フォーム（Microsoft Forms）から提出してください。</p>
      </div>
    </div>

    <!-- Step 2 -->
    <div class="relative flex items-start gap-4">
      <div class="timeline-dot absolute -left-8"></div>
      <div class="flex-1">
        <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">STEP 2</div>
        <h3 class="font-bold text-gray-800">企画提案書の提出</h3>
        <div class="mt-1 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 inline-block">
          <span class="text-amber-700 font-bold text-sm">受付期間: 令和8年3月18日（水）〜 3月25日（水）正午必着</span>
        </div>
        <p class="text-sm text-gray-600 mt-2">企画提案書（様式1〜3/5）をPDF＋Excelで提出 + メール連絡。</p>
      </div>
    </div>

    <!-- Step 3 -->
    <div class="relative flex items-start gap-4">
      <div class="timeline-dot absolute -left-8"></div>
      <div class="flex-1">
        <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">STEP 3</div>
        <h3 class="font-bold text-gray-800">選定結果通知・交付内定</h3>
        <div class="mt-1 bg-green-50 border border-green-200 rounded-lg px-3 py-2 inline-block">
          <span class="text-green-700 font-bold text-sm">令和8年6月中（予定）</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ===== 参加表明フォーム ===== -->
<div class="card p-6">
  <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
    <span class="text-2xl">📝</span> 参加表明フォーム
    <span class="text-xs bg-red-100 text-red-700 font-bold px-2 py-0.5 rounded-full">3/3 正午〆</span>
  </h2>

  <div class="space-y-4">
    <div class="border border-blue-200 bg-blue-50 rounded-xl p-4">
      <h3 class="font-bold text-blue-900 mb-2">① 「地方創生」 参加表明フォーム</h3>
      <a href="https://forms.office.com/pages/responsepage.aspx?id=sBBYVMs2kEKJJkjbwPnpL7M9tvKX80RMlhztbwX3J19UOU5aVDdHN1FFR1dLUzhSRklGODlNRTZQMC4u&route=shorturl" target="_blank" rel="noopener" class="ext-link text-sm break-all">
        Microsoft Forms を開く →
      </a>
    </div>

    <div class="border border-purple-200 bg-purple-50 rounded-xl p-4">
      <h3 class="font-bold text-purple-900 mb-2">② 「産業成長」 参加表明フォーム</h3>
      <a href="https://forms.office.com/pages/responsepage.aspx?id=sBBYVMs2kEKJJkjbwPnpL7M9tvKX80RMlhztbwX3J19URTRCR1BaSTMwRFA0NzlOVEdaMk9MVE44OS4u&route=shorturl" target="_blank" rel="noopener" class="ext-link text-sm break-all">
        Microsoft Forms を開く →
      </a>
    </div>
  </div>
</div>

<!-- ===== 企画提案書の提出方法 ===== -->
<div class="card p-6">
  <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
    <span class="text-2xl">📤</span> 企画提案書の提出方法
  </h2>

  <!-- Menu Tabs -->
  <div class="flex gap-1 mb-0">
    <div class="menu-tab active" onclick="switchTab('menu1')" id="tab-menu1">① 地方創生</div>
    <div class="menu-tab" onclick="switchTab('menu2')" id="tab-menu2">② 産業成長</div>
  </div>

  <!-- Menu 1 Panel -->
  <div class="menu-panel active border border-gray-200 rounded-b-xl rounded-tr-xl p-5 bg-white" id="panel-menu1">
    <h3 class="font-bold text-gray-800 mb-3">提出ファイル</h3>
    <div class="space-y-2 mb-5">
      <div class="flex items-start gap-2 text-sm">
        <span class="bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded text-xs flex-shrink-0 mt-0.5">PDF</span>
        <span>企画提案書を統合PDFファイル（様式1〜3の順に並べて1つに結合）</span>
      </div>
      <div class="flex items-start gap-2 text-sm">
        <span class="bg-green-100 text-green-700 font-bold px-2 py-0.5 rounded text-xs flex-shrink-0 mt-0.5">Excel</span>
        <span>企画提案書様式1（基本情報等）.xlsx</span>
      </div>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm">
      <p class="font-bold text-gray-700 mb-1">ファイル名の付け方</p>
      <ul class="list-disc list-inside text-gray-600 space-y-1">
        <li>「（機関名）企画提案書.pdf」</li>
        <li>「（機関名）企画提案書様式1（基本情報等）.xlsx」</li>
      </ul>
    </div>

    <h3 class="font-bold text-gray-800 mb-2">提出先URL</h3>
    <a href="https://mext.ent.box.com/f/7696016437b04a4daca795875acc7943" target="_blank" rel="noopener" class="ext-link text-sm block mb-4">
      https://mext.ent.box.com/f/7696016437b04a4daca795875acc7943
    </a>

    <h3 class="font-bold text-gray-800 mb-2">メール連絡</h3>
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm space-y-2">
      <p>ファイル提出時に下記宛てに電子メールで提出した旨を連絡。</p>
      <div class="info-row"><span class="info-label">件名:</span><span class="text-gray-700">「【企画提案書提出】（機関名）産学連携リ・スキリング・エコシステム構築事業メニュー①「地方創生」」</span></div>
      <div class="info-row"><span class="info-label">宛先:</span><a href="mailto:syokugyou@mext.go.jp" class="ext-link">syokugyou@mext.go.jp</a></div>
    </div>
  </div>

  <!-- Menu 2 Panel -->
  <div class="menu-panel border border-gray-200 rounded-b-xl rounded-tr-xl p-5 bg-white" id="panel-menu2">
    <h3 class="font-bold text-gray-800 mb-3">提出ファイル</h3>
    <div class="space-y-2 mb-5">
      <div class="flex items-start gap-2 text-sm">
        <span class="bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded text-xs flex-shrink-0 mt-0.5">PDF</span>
        <span>企画提案書様式1〜5をPDFに変換し、順に並べたもの</span>
      </div>
      <div class="flex items-start gap-2 text-sm">
        <span class="bg-green-100 text-green-700 font-bold px-2 py-0.5 rounded text-xs flex-shrink-0 mt-0.5">Excel</span>
        <span>企画提案書様式1（基本情報等）.xlsx</span>
      </div>
      <div class="flex items-start gap-2 text-sm">
        <span class="bg-orange-100 text-orange-700 font-bold px-2 py-0.5 rounded text-xs flex-shrink-0 mt-0.5">動画</span>
        <span>5分程度のmp4ファイル（プログラム概要・企業ニーズ対応・教育手法・スキル説明）</span>
      </div>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm">
      <p class="font-bold text-gray-700 mb-1">ファイル名の付け方</p>
      <ul class="list-disc list-inside text-gray-600 space-y-1">
        <li>「（○○大学△△）企画提案書.pdf」</li>
        <li>「（○○大学）企画提案書様式1（基本情報等）.xlsx」</li>
      </ul>
    </div>

    <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm">
      <p class="font-bold text-gray-700 mb-1">動画の盛り込み内容</p>
      <ul class="list-disc list-inside text-gray-600 space-y-1">
        <li>リ・スキリングプログラム概要</li>
        <li>企業ニーズへの対応状況</li>
        <li>教育手法の工夫</li>
        <li>学んだ社会人が身に付けられるスキル・得られる経験</li>
      </ul>
      <p class="text-xs text-gray-500 mt-2">※ コーディネーター人材等、プログラムの価値を実質的に説明できる者が出演することが望ましい</p>
    </div>

    <h3 class="font-bold text-gray-800 mb-2">提出先URL</h3>
    <div class="space-y-2 mb-4">
      <div class="text-sm">
        <span class="font-bold text-gray-600">書面審査書類:</span>
        <a href="https://mext.ent.box.com/f/8729d39af1b94a32b15d00e48a49db7" target="_blank" rel="noopener" class="ext-link block ml-4">
          https://mext.ent.box.com/f/8729d39af1b94a32b15d00e48a49db7
        </a>
      </div>
      <div class="text-sm">
        <span class="font-bold text-gray-600">動画審査ファイル:</span>
        <a href="https://mext.ent.box.com/f/6e1643a7799f47d2976688967e87747e" target="_blank" rel="noopener" class="ext-link block ml-4">
          https://mext.ent.box.com/f/6e1643a7799f47d2976688967e87747e
        </a>
      </div>
    </div>

    <h3 class="font-bold text-gray-800 mb-2">メール連絡</h3>
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm space-y-2">
      <p>ファイル提出時に下記宛てに電子メールで提出した旨を連絡。</p>
      <div class="info-row"><span class="info-label">件名:</span><span class="text-gray-700">「【企画書提出】（機関名）産学連携リ・スキリング・エコシステム構築事業メニュー②「産業成長」」</span></div>
      <div class="info-row"><span class="info-label">宛先:</span><a href="mailto:syokugyou@mext.go.jp" class="ext-link">syokugyou@mext.go.jp</a></div>
    </div>
  </div>
</div>

<!-- ===== 本件担当 ===== -->
<div class="card p-6">
  <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
    <span class="text-2xl">📞</span> 本件担当（文部科学省）
  </h2>
  <div class="text-sm text-gray-700 space-y-1">
    <p>〒100-8959 東京都千代田区霞が関 3-2-2</p>
    <p>文部科学省 総合教育政策局 生涯学習推進課</p>
    <p>リカレント教育・民間教育振興室</p>
    <p>「産学連携リ・スキリング・エコシステム構築事業」担当</p>
    <div class="mt-3 flex flex-wrap gap-4">
      <div class="flex items-center gap-2">
        <span class="text-gray-400">TEL:</span>
        <a href="tel:03-6734-3466" class="ext-link">03-6734-3466</a>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-gray-400">E-mail:</span>
        <a href="mailto:syokugyou@mext.go.jp" class="ext-link">syokugyou@mext.go.jp</a>
      </div>
    </div>
  </div>
</div>

<!-- ===== CTA ===== -->
<div class="card p-6 text-center bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100">
  <p class="text-sm text-gray-600 mb-4">申請書の作成はこちらから</p>
  <div class="flex flex-col sm:flex-row gap-3 justify-center">
    <a href="diagnose.php"
       class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-bold text-sm px-6 py-3 rounded-lg hover:opacity-90 transition shadow">
      📋 どのメニューで申請？診断する
    </a>
    <a href="register.php"
       class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-bold text-sm px-6 py-3 rounded-lg hover:opacity-90 transition shadow">
      📝 申請書を作成する
    </a>
  </div>
</div>

</main>

<?php include 'footer.php'; ?>

<script>
function switchTab(menu) {
  document.querySelectorAll('.menu-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.menu-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + menu).classList.add('active');
  document.getElementById('panel-' + menu).classList.add('active');
}
</script>
</body>
</html>
