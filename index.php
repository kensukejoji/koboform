<?php
// index.php - 大学入力フォーム
$id = $_GET['id'] ?? '';
if (!$id || !preg_match('/^[a-zA-Z0-9-]+$/', $id)) {
    echo '<div style="text-align:center;padding:50px;font-family:sans-serif;"><h1>無効なURLです</h1><p>正しいURLにアクセスしてください。</p><p><a href="admin.php">管理者ログインはこちら</a></p></div>';
    exit;
}
require_once 'db_config.php';
$stmtMenu = $pdo->prepare("SELECT data FROM universities WHERE id = ?");
$stmtMenu->execute([$id]);
$uniRow = $stmtMenu->fetch();
$pageMenu = 'menu1';
if ($uniRow) {
    $uniData = json_decode($uniRow['data'], true);
    $pageMenu = $uniData['_menu'] ?? 'menu1';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>産学連携リ・スキリング 申請書作成ツール</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Hiragino Kaku Gothic ProN', 'Meiryo', sans-serif; }
  .badge-uni  { background:#dbeafe; color:#1d4ed8; border:1px solid #93c5fd; }
  .badge-jg   { background:#fef3c7; color:#b45309; border:1px solid #fcd34d; }
  .badge-both { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }
  textarea { resize: vertical; }
  .tab-btn.active { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
  .tab-btn { cursor:pointer; transition:all .15s; }
  .tab-btn:not(.active):hover { background:#eff6ff; }
  .form-section { display:none; }
  .form-section.active { display:block; }
  .char-counter { font-size:.75rem; color:#6b7280; }
  .char-counter.warn { color:#ef4444; font-weight:bold; }
  /* 必須バッジ */
  .required-mark { display:inline-flex; align-items:center; background:#fee2e2; color:#dc2626; font-size:10px; font-weight:700; padding:1px 5px; border-radius:3px; margin-left:4px; vertical-align:middle; line-height:1.4; }
  /* 進捗バー */
  #progressWidget { background:#fff; border-bottom:1px solid #e5e7eb; box-shadow:0 1px 4px rgba(0,0,0,.06); }
  /* 印刷スタイル */
  @media print {
    .no-print { display:none !important; }
    #printOutput { display:block !important; }
    body { background:#fff; }
    .print-page { page-break-after: always; }
    .print-page:last-child { page-break-after: avoid; }
  }
  /* 申請様式出力 */
  .shoshiki-box { border:2px solid #000; margin-bottom:1rem; }
  .shoshiki-title { background:#1e3a5f; color:#fff; padding:.4rem .8rem; font-weight:bold; font-size:.9rem; }
  .shoshiki-row { display:flex; border-top:1px solid #999; min-height:2rem; }
  .shoshiki-label { background:#f0f0f0; font-weight:bold; font-size:.78rem; padding:.3rem .5rem; min-width:160px; width:160px; border-right:1px solid #999; display:flex; align-items:flex-start; padding-top:.4rem; }
  .shoshiki-val { padding:.3rem .5rem; font-size:.82rem; flex:1; white-space:pre-wrap; }
  .shoshiki-table { width:100%; border-collapse:collapse; }
  .shoshiki-table th { background:#1e3a5f; color:#fff; font-size:.75rem; padding:.3rem .5rem; border:1px solid #999; }
  .shoshiki-table td { font-size:.78rem; padding:.3rem .5rem; border:1px solid #999; vertical-align:top; }
  /* チャットウィジェット */
  #chatWindow { transition: opacity .3s ease, transform .3s ease; }
  #chatWindow.chat-hidden { opacity:0; transform:translateY(20px) scale(.95); pointer-events:none; }
  #chatWindow.chat-visible { opacity:1; transform:translateY(0) scale(1); }
  .chat-msg-user { background:#4f46e5; color:#fff; border-radius:16px 16px 4px 16px; margin-left:48px; padding:10px 14px; font-size:13px; line-height:1.6; word-break:break-word; }
  .chat-msg-ai { background:#fff; color:#1f2937; border:1px solid #e5e7eb; border-radius:16px 16px 16px 4px; margin-right:24px; padding:10px 14px; font-size:13px; line-height:1.6; word-break:break-word; }
  .chat-msg-ai ul, .chat-msg-ai ol { padding-left:1.2em; margin:4px 0; }
  .chat-msg-ai li { margin-bottom:2px; }
  .chat-typing { display:inline-flex; gap:4px; padding:8px 14px; }
  .chat-typing span { width:8px; height:8px; background:#9ca3af; border-radius:50%; animation:chatBounce 1.4s infinite; }
  .chat-typing span:nth-child(2) { animation-delay:.2s; }
  .chat-typing span:nth-child(3) { animation-delay:.4s; }
  @keyframes chatBounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-8px)} }
  .chat-suggestion { display:inline-block; background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe; border-radius:999px; padding:4px 12px; font-size:12px; cursor:pointer; white-space:nowrap; transition:background .15s; }
  .chat-suggestion:hover { background:#c7d2fe; }
  @media (max-width:640px) {
    #chatWindow { width:100%!important; height:85vh!important; bottom:0!important; right:0!important; border-radius:16px 16px 0 0!important; }
    #chatToggleBtn { bottom:16px!important; right:16px!important; }
  }
  @media print { #chatToggleBtn, #chatWindow { display:none!important; } }
</style>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- ===================== FORM PAGE ===================== -->
<div id="formPage">

  <!-- ヘッダー -->
  <header class="bg-blue-900 text-white px-4 py-3 no-print">
    <div class="max-w-6xl mx-auto flex items-center justify-between flex-wrap gap-2">
      <div>
        <p class="text-xs text-blue-300">産学連携リ・スキリング申請書作成ツール</p>
        <h1 class="text-base font-bold" id="formHeader">○○大学　入力フォーム</h1>
      </div>
      <div class="flex gap-2 flex-wrap">
        <button onclick="openAiModal()" class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-3 py-2 rounded font-bold flex items-center gap-1"><span>🤖</span> AIで提案作成</button>
        <button onclick="saveData()" class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-2 rounded font-bold">💾 保存</button>
        <button onclick="showOutput()" class="bg-amber-500 hover:bg-amber-600 text-white text-xs px-3 py-2 rounded font-bold">📄 申請様式を出力</button>
        <button onclick="exportJSON()" class="bg-gray-500 hover:bg-gray-600 text-white text-xs px-3 py-2 rounded font-bold">📥 JSONで保存</button>
      </div>
    </div>
  </header>
  <?php $currentPage = 'index'; $navWidth = 'max-w-6xl'; include 'nav.php'; ?>

  <!-- 凡例 -->
  <div class="max-w-6xl mx-auto px-4 pt-2 flex gap-3 text-xs no-print flex-wrap">
    <span class="badge-uni px-2 py-1 rounded font-bold">🎓 大学側記入</span>
    <span class="badge-jg px-2 py-1 rounded font-bold">🏢 JollyGood記入</span>
    <span class="badge-both px-2 py-1 rounded font-bold">🤝 共同記入</span>
    <span class="text-gray-500 ml-2">※データは30秒ごとに自動保存されます</span>
  </div>

  <!-- タブナビ -->
  <div class="max-w-6xl mx-auto px-4 pt-3 no-print">
    <div class="flex gap-1 overflow-x-auto">
      <?php if ($pageMenu === 'menu2'): ?>
      <button class="tab-btn active whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-orange-700" onclick="showTab('s21')">様式1-1<br><span class="font-normal">提出状</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s22')">様式1-2<br><span class="font-normal">基本情報</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s23')">事業計画書<br><span class="font-normal">体制・プログラム</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s3')">申請経費<br><span class="font-normal">様式3</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s24')">伴走支援<br><span class="font-normal">様式4</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-green-700" onclick="showTab('sslide')">スライド構成案<br><span class="font-normal">AI生成</span></button>
      <?php else: ?>
      <button class="tab-btn active whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-blue-800" onclick="showTab('s11')">様式1-1<br><span class="font-normal">提出状</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s12')">様式1-2<br><span class="font-normal">基本情報</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s13')">様式1-3<br><span class="font-normal">実施委員会</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s2')">様式2<br><span class="font-normal">企画提案書</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s3')">様式3<br><span class="font-normal">申請経費</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-green-700" onclick="showTab('sslide')">スライド構成案<br><span class="font-normal">AI生成</span></button>
      <?php endif; ?>
    </div>
  </div>

  <!-- 進捗バー + 次にやること -->
  <div id="progressWidget" class="px-4 py-3 no-print">
    <div class="max-w-6xl mx-auto">
      <div class="flex items-center gap-3 mb-2">
        <span class="text-xs font-bold text-gray-600 whitespace-nowrap">入力進捗</span>
        <div class="flex-1 bg-gray-200 rounded-full h-3 overflow-hidden">
          <div id="progressBar" class="h-3 rounded-full transition-all duration-500 bg-red-400" style="width:0%"></div>
        </div>
        <span id="progressPct" class="text-sm font-bold text-gray-700 w-10 text-right">0%</span>
      </div>
      <div class="flex items-start gap-2 flex-wrap">
        <span class="text-xs text-orange-600 font-bold whitespace-nowrap mt-0.5">📝 次に入力すべき項目:</span>
        <div id="top3Tasks" class="flex flex-wrap gap-1"></div>
      </div>
    </div>
  </div>

  <!-- AI生成モーダル -->
  <div id="aiModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 no-print">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md mx-4">
      <h3 class="text-lg font-bold text-purple-900 mb-2">🤖 AIで提案・見積もりを自動作成</h3>
      <p class="text-xs text-gray-500 mb-4">テーマを入力すると、ジョリーグッドの事例を元に申請書の下書きと予算案を生成します。<br><span class="text-red-500 font-bold">※現在の入力内容は上書きされます。</span></p>
      
      <label class="block text-sm font-bold text-gray-700 mb-1">地域（任意）</label>
      <input type="text" id="aiRegion" class="w-full border rounded px-3 py-2 text-sm mb-3" placeholder="例：北海道夕張市">
      
      <label class="block text-sm font-bold text-gray-700 mb-1">事業テーマ</label>
      <input type="text" id="aiTheme" class="w-full border rounded px-3 py-2 text-sm mb-3" placeholder="例：地域医療を支えるVR看護教育">

      <div class="border-2 border-dashed border-gray-300 rounded-lg p-3 mb-4 bg-gray-50">
        <label class="block text-xs font-bold text-gray-600 mb-1">PDF資料（任意・最大2ファイル、各10MBまで）</label>
        <input type="file" id="aiPdfFiles" multiple accept=".pdf" class="text-xs w-full">
        <div id="aiPdfExisting" class="mt-1 text-xs text-green-600"></div>
        <p class="text-xs text-gray-400 mt-1">スライド資料や企画書をアップロードすると、AIがPDFを読み込んでより具体的な文案を生成します。</p>
      </div>

      <div class="flex gap-2">
        <button onclick="runAiGenerate()" id="aiGenBtn" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 rounded">生成する</button>
        <button onclick="document.getElementById('aiModal').classList.add('hidden')" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 rounded">キャンセル</button>
      </div>
    </div>
  </div>

  <!-- フォーム本体 -->
  <div class="max-w-6xl mx-auto px-4 pb-10">

    <!-- 様式1-1 -->
    <div id="s11" class="form-section active bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-blue-900 border-b-2 border-blue-900 pb-2 mb-4">様式１-１　企画提案書提出状</h2>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">提出年月日 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label>
          <input type="date" id="s11_date" class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">大学等名 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label>
          <input type="text" id="s11_daigakuname" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：○○大学">
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">学長等氏名 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label>
          <input type="text" id="s11_gakucho" class="w-full border rounded px-3 py-2 text-sm" placeholder="氏名">
        </div>
      </div>
      <div class="flex justify-end mt-5"><button onclick="showTab('s12')" class="bg-blue-700 text-white px-5 py-2 rounded font-bold hover:bg-blue-800 text-sm">次へ →</button></div>
    </div>

    <!-- 様式1-2 -->
    <div id="s12" class="form-section bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-blue-900 border-b-2 border-blue-900 pb-2 mb-4">様式１-２　基本情報</h2>
      <div class="space-y-5">

        <div class="border rounded p-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">１．実施主体 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label>
          <input type="text" id="s12_jisshisyutai" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：○○大学">
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-2">２．事業者（大学等の設置者） <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></p>
          <div class="grid grid-cols-3 gap-3">
            <div><label class="text-xs text-gray-500">ふりがな</label><input type="text" id="s12_jigyosha_furi" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="ふりがな"></div>
            <div><label class="text-xs text-gray-500">氏名</label><input type="text" id="s12_jigyosha_name" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></div>
            <div><label class="text-xs text-gray-500">所属・職名</label><input type="text" id="s12_jigyosha_shoku" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></div>
          </div>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-2">３．申請者（大学等の学長等） <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></p>
          <div class="grid grid-cols-3 gap-3">
            <div><label class="text-xs text-gray-500">ふりがな</label><input type="text" id="s12_shinseisha_furi" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="ふりがな"></div>
            <div><label class="text-xs text-gray-500">氏名</label><input type="text" id="s12_shinseisha_name" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></div>
            <div><label class="text-xs text-gray-500">所属・職名</label><input type="text" id="s12_shinseisha_shoku" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></div>
          </div>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-2">４．事業責任者 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></p>
          <div class="grid grid-cols-3 gap-3">
            <div><label class="text-xs text-gray-500">ふりがな</label><input type="text" id="s12_sekininsha_furi" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="ふりがな"></div>
            <div><label class="text-xs text-gray-500">氏名</label><input type="text" id="s12_sekininsha_name" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></div>
            <div><label class="text-xs text-gray-500">所属・職名</label><input type="text" id="s12_sekininsha_shoku" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></div>
          </div>
        </div>

        <div class="border rounded p-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">５．事業名 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label>
          <input type="text" id="s12_jigyomei" class="w-full border rounded px-3 py-2 text-sm" placeholder="事業名を入力">
        </div>

        <div class="border rounded p-4">
          <label class="block text-sm font-bold text-gray-700 mb-2">６．事業のポイント（400字以内） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label>
          <textarea id="s12_point" rows="5" maxlength="400" oninput="updateCounter(this,'counter6')" class="w-full border rounded px-3 py-2 text-sm" placeholder="プログラムの概要と特色を簡潔にまとめてください"></textarea>
          <p id="counter6" class="char-counter text-right mt-1">0 / 400字</p>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-3">７．事業経費（単位：千円） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs font-bold text-gray-600">事業規模（総事業費）</label><div class="flex items-center mt-1"><input type="number" id="s12_sogaku" class="w-full border rounded px-2 py-1.5 text-sm"><span class="ml-2 text-sm text-gray-500">千円</span></div></div>
            <div><label class="text-xs font-bold text-gray-600">補助金申請額</label><div class="flex items-center mt-1"><input type="number" id="s12_hojokinn" class="w-full border rounded px-2 py-1.5 text-sm"><span class="ml-2 text-sm text-gray-500">千円</span></div></div>
            <div><label class="text-xs font-bold text-gray-600">機関負担額 <span class="badge-uni px-1 rounded text-xs">🎓</span></label><div class="flex items-center mt-1"><input type="number" id="s12_kikan_futan" class="w-full border rounded px-2 py-1.5 text-sm"><span class="ml-2 text-sm text-gray-500">千円</span></div></div>
            <div><label class="text-xs font-bold text-gray-600">受講料収入見込み額</label><div class="flex items-center mt-1"><input type="number" id="s12_jukoryosyu" class="w-full border rounded px-2 py-1.5 text-sm"><span class="ml-2 text-sm text-gray-500">千円</span></div></div>
          </div>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-3">９．事業協働機関</p>
          <div class="space-y-2">
            <div><label class="text-xs font-bold text-gray-600">（産）産業界 <span class="badge-jg px-1 rounded text-xs">🏢 JG</span></label><textarea id="s12_kyodo_san" rows="2" class="w-full border rounded px-3 py-2 text-sm mt-1" placeholder="例：株式会社ジョリーグッド（XR/VR技術によるリスキリングプログラム開発・提供）"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">（官）行政機関 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s12_kyodo_kan" rows="2" class="w-full border rounded px-3 py-2 text-sm mt-1" placeholder="例：○○県、△△市等"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">（学）大学等 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s12_kyodo_gaku" rows="2" class="w-full border rounded px-3 py-2 text-sm mt-1" placeholder="例：○○大学（主幹機関）、連携大学等"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">（金）金融機関 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s12_kyodo_kin" rows="2" class="w-full border rounded px-3 py-2 text-sm mt-1" placeholder="例：○○銀行、△△信用金庫等"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">（その他） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><textarea id="s12_kyodo_other" rows="2" class="w-full border rounded px-3 py-2 text-sm mt-1"></textarea></div>
          </div>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-3">１０．主たる大学等の学生・教職員数 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></p>
          <div class="mb-2"><label class="text-xs text-gray-500">大学名</label><input type="text" id="s12_daigaku_name" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="○○大学"></div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead><tr class="bg-blue-900 text-white"><th class="border px-2 py-1"></th><th class="border px-2 py-1">入学定員（R7）</th><th class="border px-2 py-1">全学生数（R6.7.1）</th><th class="border px-2 py-1">収容定員（R7）</th><th class="border px-2 py-1">教員数</th><th class="border px-2 py-1">職員数</th></tr></thead>
              <tbody>
                <tr><td class="border px-2 py-1 font-bold bg-gray-50">学部</td><td class="border px-1"><input type="number" id="s12_gakubu_nyugaku" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1"><input type="number" id="s12_gakubu_zengakusei" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1"><input type="number" id="s12_gakubu_shuyoteiin" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1"><input type="number" id="s12_kyoinsuu" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1"><input type="number" id="s12_shokuinsuu" class="w-full text-sm px-1 py-0.5" placeholder="0"></td></tr>
                <tr><td class="border px-2 py-1 font-bold bg-gray-50">大学院</td><td class="border px-1"><input type="number" id="s12_daigakuin_nyugaku" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1"><input type="number" id="s12_daigakuin_zengakusei" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1"><input type="number" id="s12_daigakuin_shuyoteiin" class="w-full text-sm px-1 py-0.5" placeholder="0"></td><td class="border px-1 text-center text-gray-400">―</td><td class="border px-1 text-center text-gray-400">―</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-2">１１．取組を実施する学部等名 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></p>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="text-xs text-gray-500">学部等名</label><input type="text" id="s12_gakubu_jisshi" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：経営学部等"></div>
            <div><label class="text-xs text-gray-500">研究科等名</label><input type="text" id="s12_kenkyuka" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：経営学研究科等"></div>
          </div>
        </div>

        <div class="border rounded p-4">
          <p class="text-sm font-bold text-gray-700 mb-3">１２．事業事務総括者部課の連絡先 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></p>
          <div class="grid grid-cols-2 gap-3 mb-3">
            <div><label class="text-xs text-gray-500">部課名</label><input type="text" id="s12_bukaname" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：研究推進部研究助成課"></div>
            <div><label class="text-xs text-gray-500">所在地（〒）</label><input type="text" id="s12_shozaichi" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="〒000-0000 住所"></div>
          </div>
          <div class="bg-blue-50 rounded p-3 mb-2">
            <p class="text-xs font-bold text-blue-800 mb-2">責任者（課長相当職）</p>
            <div class="grid grid-cols-3 gap-2">
              <div><label class="text-xs text-gray-500">ふりがな</label><input type="text" id="s12_sekinin_furi" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
              <div><label class="text-xs text-gray-500">氏名</label><input type="text" id="s12_sekinin_name" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
              <div><label class="text-xs text-gray-500">所属・職名</label><input type="text" id="s12_sekinin_shoku" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
            </div>
          </div>
          <div class="bg-green-50 rounded p-3">
            <p class="text-xs font-bold text-green-800 mb-2">担当者（係長相当職）</p>
            <div class="grid grid-cols-2 gap-2">
              <div><label class="text-xs text-gray-500">ふりがな</label><input type="text" id="s12_tanto_furi" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
              <div><label class="text-xs text-gray-500">氏名</label><input type="text" id="s12_tanto_name" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
              <div><label class="text-xs text-gray-500">所属・職名</label><input type="text" id="s12_tanto_shoku" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
              <div><label class="text-xs text-gray-500">電話番号</label><input type="tel" id="s12_tanto_tel" class="w-full border rounded px-2 py-1 text-sm mt-1" placeholder="03-0000-0000"></div>
              <div><label class="text-xs text-gray-500">緊急連絡先</label><input type="tel" id="s12_tanto_emg" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
              <div><label class="text-xs text-gray-500">e-mail（主）</label><input type="email" id="s12_tanto_mail1" class="w-full border rounded px-2 py-1 text-sm mt-1" placeholder="group@xxx.ac.jp"></div>
              <div><label class="text-xs text-gray-500">e-mail（副）</label><input type="email" id="s12_tanto_mail2" class="w-full border rounded px-2 py-1 text-sm mt-1"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="flex justify-between mt-5">
        <button onclick="showTab('s11')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('s13')" class="bg-blue-700 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-800">次へ →</button>
      </div>
    </div>

    <!-- 様式1-3 -->
    <div id="s13" class="form-section bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-blue-900 border-b-2 border-blue-900 pb-2 mb-4">様式１-３　事業実施委員会（プラットフォーム）</h2>
      <div class="space-y-4">
        <div><label class="block text-sm font-bold text-gray-700 mb-1">委員会名 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><input type="text" id="s13_iinkaime" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：産学連携リ・スキリング推進委員会"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">目的・役割 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><textarea id="s13_mokuteki" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">検討の具体的内容 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><textarea id="s13_kentou" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">委員数</label><div class="flex items-center"><input type="number" id="s13_iinsuu" class="w-24 border rounded px-3 py-2 text-sm" placeholder="0"><span class="ml-2 text-sm">名</span></div></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">開催頻度</label><div class="flex items-center"><input type="number" id="s13_kaiji" class="w-24 border rounded px-3 py-2 text-sm" placeholder="0"><span class="ml-2 text-sm">回/年</span></div></div>
        </div>
        <div>
          <p class="text-sm font-bold text-gray-700 mb-2">委員会の構成員 <span class="text-xs font-normal text-gray-500">※役割欄に承諾状況（承諾済み／打診中）を記入</span></p>
          <table class="w-full text-sm border-collapse"><thead><tr class="bg-blue-900 text-white"><th class="border px-2 py-1 w-8">No.</th><th class="border px-2 py-1">氏名</th><th class="border px-2 py-1">所属・職名</th><th class="border px-2 py-1">役割等（承諾状況）</th></tr></thead><tbody id="committeeTbody"></tbody></table>
        </div>
      </div>
      <div class="flex justify-between mt-5">
        <button onclick="showTab('s12')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('s2')" class="bg-blue-700 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-800">次へ →</button>
      </div>
    </div>

    <!-- 様式2 -->
    <div id="s2" class="form-section bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-blue-900 border-b-2 border-blue-900 pb-2 mb-4">様式２　企画提案書（スライド内容） ※30枚以内</h2>
      <div class="space-y-5">
        <div class="border-l-4 border-blue-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-2">【P2】プラットフォームの体制と教育プログラムの概要 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <div class="grid grid-cols-2 gap-3 mb-2">
            <div><label class="text-xs font-bold text-gray-600">産業界の構成・役割 <span class="badge-jg px-1 rounded text-xs">🏢 JG</span></label><textarea id="s2_sangyo" rows="3" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：株式会社ジョリーグッド（XR/VRによるリスキリングプログラム開発・提供）"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">大学の構成・役割 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_daigaku" rows="3" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：○○大学（プログラム設計・認証・デジタルバッジ発行）"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">行政の構成・役割 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_gyosei" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：○○県・市（地域課題の提供、派遣企業支援）"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">金融機関の構成・役割 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_kinyu" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="例：○○銀行（企業紹介・経営支援）"></textarea></div>
          </div>
          <div><label class="text-xs font-bold text-gray-600">プラットフォームで取り組む主な事項 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><textarea id="s2_platform_jiko" rows="3" class="w-full border rounded px-2 py-1.5 text-sm mt-1" placeholder="・地域課題を踏まえたリスキリングプログラムの企画・開発&#10;・産学官金連携によるエコシステム構築"></textarea></div>
        </div>

        <div class="border-l-4 border-green-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-2">【P4】教育プログラム一覧 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse"><thead><tr class="bg-green-800 text-white"><th class="border px-2 py-1 w-72">プログラム名</th><th class="border px-2 py-1 w-28">対象者</th><th class="border px-2 py-1 w-14">定員</th><th class="border px-2 py-1 w-32">受講料（円）</th><th class="border px-2 py-1">目的・内容</th><th class="border px-2 py-1 w-8">削除</th></tr></thead><tbody id="programTbody"></tbody></table>
          </div>
          <button onclick="addProgramRow()" class="mt-2 bg-green-600 text-white text-xs px-3 py-1 rounded hover:bg-green-700">＋ プログラムを追加</button>
        </div>

        <div class="border-l-4 border-purple-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-1">【P3】プラットフォームの活動範囲と体制構築 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <textarea id="s2_katsudo" rows="4" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div class="border-l-4 border-orange-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-1">【P5】企業／エコシステムとの連携 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <textarea id="s2_kigyorenkei" rows="4" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div class="border-l-4 border-red-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-2">【P6】課題への対応（令和8年度中の取組） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <div class="space-y-2">
            <div><label class="text-xs font-bold text-gray-600">①アドバンストエッセンシャルワーカーの育成</label><textarea id="s2_kadai1" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">②就職氷河期世代等の支援</label><textarea id="s2_kadai2" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">③地方人材確保のための仕組み構築</label><textarea id="s2_kadai3" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">④スキルの可視化や正当な評価による処遇改善</label><textarea id="s2_kadai4" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">⑤教員のインセンティブ向上 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_kadai5" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">⑥全学的なリ・スキリング推進に向けた体制 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_kadai6" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">⑦修士課程・博士課程への接続 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_kadai7" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">⑧大学間連携の強化 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><textarea id="s2_kadai8" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
          </div>
        </div>

        <div class="border-l-4 border-cyan-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-1">【P7】自走化：受講生・企業等からの評価 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <textarea id="s2_jisoka_hyoka" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div class="border-l-4 border-indigo-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-1">【P8】自走化：取組の年間計画（令和8年度） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <textarea id="s2_nenkan" rows="4" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>

        <div class="border-l-4 border-pink-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-2">【P9】自走化：事業終了後の継続計画（令和9年度以降） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <div class="space-y-2">
            <div><label class="text-xs font-bold text-gray-600">①自走化に向けた目標像（2〜4年後）</label><textarea id="s2_jisoka_goal" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">②取組計画（R9〜）</label><textarea id="s2_jisoka_plan" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">③財務計画</label><textarea id="s2_jisoka_zaimu" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
            <div><label class="text-xs font-bold text-gray-600">④人員確保の計画</label><textarea id="s2_jisoka_jinzai" rows="2" class="w-full border rounded px-2 py-1.5 text-sm mt-1"></textarea></div>
          </div>
        </div>

        <div class="border-l-4 border-yellow-500 pl-4">
          <p class="text-sm font-bold text-gray-700 mb-1">デジタルバッジの発行について <span class="badge-both px-1 rounded text-xs">🤝 共同</span></p>
          <textarea id="s2_badge" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
        </div>
      </div>
      <div class="flex justify-between mt-5">
        <button onclick="showTab('s13')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('s3')" class="bg-blue-700 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-800">次へ →</button>
      </div>
    </div>

    <!-- 様式3 -->
    <div id="s3" class="form-section bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-blue-900 border-b-2 border-blue-900 pb-2 mb-4">様式３　申請経費明細（単位：千円） <span class="badge-both px-1 rounded text-xs">🤝 共同</span></h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead><tr class="bg-blue-900 text-white"><th class="border px-3 py-2 text-left">経費区分</th><th class="border px-2 py-2 w-28">補助金①（千円）</th><th class="border px-2 py-2 w-28">大学負担②（千円）</th><th class="border px-2 py-2 w-28">事業規模①+②</th><th class="border px-3 py-2 text-left">内容・積算根拠</th></tr></thead>
          <tbody id="keihi_tbody"></tbody>
          <tfoot><tr class="bg-gray-100 font-bold"><td class="border px-3 py-2">合計</td><td class="border px-2 py-2 text-right" id="total_hojo">0</td><td class="border px-2 py-2 text-right" id="total_futan">0</td><td class="border px-2 py-2 text-right" id="total_kibo">0</td><td class="border"></td></tr></tfoot>
        </table>
      </div>
      <div class="flex justify-between mt-5">
        <?php if ($pageMenu === 'menu2'): ?>
        <button onclick="showTab('s23')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('sslide')" class="bg-green-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-green-700">次へ（スライド構成案）→</button>
        <?php else: ?>
        <button onclick="showTab('s2')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('sslide')" class="bg-green-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-green-700">次へ（スライド構成案）→</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($pageMenu === 'menu2'): ?>
  <!-- ==================== メニュー②フォーム ==================== -->

  <!-- 様式1-1 提出状 -->
  <div id="s21" class="form-section active max-w-6xl mx-auto px-4 pb-10">
    <div class="bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-orange-800 border-b-2 border-orange-800 pb-2 mb-4">様式１-１　企画提案書提出状</h2>
      <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-bold text-gray-700 mb-1">提出年月日 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><input type="date" id="s21_date" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">大学等名 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><input type="text" id="s21_daigakuname" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：○○大学"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">学長等氏名 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><input type="text" id="s21_gakucho" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：○○ ○○"></div>
      </div>
      <div class="flex justify-end mt-5"><button onclick="showTab('s22')" class="bg-blue-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-700">次へ →</button></div>
    </div>
  </div>

  <!-- 様式1-2 基本情報 -->
  <div id="s22" class="form-section max-w-6xl mx-auto px-4 pb-10">
    <div class="bg-white rounded-b rounded-r shadow p-6 space-y-5">
      <h2 class="text-base font-bold text-orange-800 border-b-2 border-orange-800 pb-2 mb-4">様式１-２　基本情報</h2>
      <div><label class="block text-sm font-bold text-gray-700 mb-1">１. 実施主体 <span class="badge-uni px-1 rounded text-xs">🎓 大学</span></label><input type="text" id="s22_jisshisyutai" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：○○大学（設置者：○○）"></div>

      <div class="grid grid-cols-3 gap-3">
        <div><label class="block text-sm font-bold text-gray-700 mb-1">２. 事業者（ふりがな）<span class="badge-uni px-1 rounded text-xs ml-1">🎓</span></label><input type="text" id="s22_jigyosha_furi" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">氏名</label><input type="text" id="s22_jigyosha_name" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">所属・職名</label><input type="text" id="s22_jigyosha_shoku" class="w-full border rounded px-3 py-2 text-sm"></div>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div><label class="block text-sm font-bold text-gray-700 mb-1">３. 申請者（ふりがな）<span class="badge-uni px-1 rounded text-xs ml-1">🎓</span></label><input type="text" id="s22_shinseisha_furi" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">氏名</label><input type="text" id="s22_shinseisha_name" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">所属・職名</label><input type="text" id="s22_shinseisha_shoku" class="w-full border rounded px-3 py-2 text-sm"></div>
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div><label class="block text-sm font-bold text-gray-700 mb-1">４. 事業責任者（ふりがな）<span class="badge-uni px-1 rounded text-xs ml-1">🎓</span></label><input type="text" id="s22_sekinin_furi" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">氏名</label><input type="text" id="s22_sekinin_name" class="w-full border rounded px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">所属・職名</label><input type="text" id="s22_sekinin_shoku" class="w-full border rounded px-3 py-2 text-sm"></div>
      </div>

      <div class="border-t pt-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">５〜７. プログラム情報</h3>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">プログラム名（事業名）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><input type="text" id="s22_jigyomei" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：産業DX人材育成VRリスキリングプログラム"></div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">プログラムの領域（メイン）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><input type="text" id="s22_ryoiki1_main" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：DXプロ、介護、モビリティ等"></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">プログラムの領域（サブ）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><input type="text" id="s22_ryoiki1_sub" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：VR活用技能習得"></div>
        </div>
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-1">事業のポイント（400字以内）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label>
          <textarea id="s22_point" rows="5" class="w-full border rounded px-3 py-2 text-sm" oninput="updateCounter(this,'counter22p')"></textarea>
          <div id="counter22p" class="char-counter text-right">0 / 400字</div>
        </div>
      </div>

      <div class="border-t pt-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">８. 事業経費（千円）</h3>
        <div class="grid grid-cols-4 gap-3">
          <div><label class="block text-xs font-bold text-gray-700 mb-1">事業規模（総事業費）</label><input type="number" id="s22_sogaku" class="w-full border rounded px-3 py-2 text-sm" placeholder="0"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">補助金申請額（上限39,500）</label><input type="number" id="s22_hojokinn" class="w-full border rounded px-3 py-2 text-sm" placeholder="0"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">大学等負担額</label><input type="number" id="s22_kikan_futan" class="w-full border rounded px-3 py-2 text-sm" placeholder="0"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">受講料収入見込み</label><input type="number" id="s22_jukoryosyu" class="w-full border rounded px-3 py-2 text-sm" placeholder="0"></div>
        </div>
      </div>

      <div class="border-t pt-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">９. 事業協働機関</h3>
        <div class="space-y-2">
          <div><label class="block text-xs font-bold text-gray-700 mb-1">産業界・企業・経済団体<span class="badge-jg px-1 rounded text-xs ml-1">🏢 JG</span></label><input type="text" id="s22_kyodo_kigyo" class="w-full border rounded px-3 py-2 text-sm" placeholder="株式会社ジョリーグッド（VRコンテンツ制作・プラットフォーム提供）、受講生派遣企業等"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">教育機関<span class="badge-uni px-1 rounded text-xs ml-1">🎓</span></label><input type="text" id="s22_kyodo_kyo" class="w-full border rounded px-3 py-2 text-sm" placeholder="連携大学等があれば"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">その他</label><input type="text" id="s22_kyodo_other" class="w-full border rounded px-3 py-2 text-sm" placeholder="行政・業界団体等"></div>
        </div>
      </div>

      <div class="border-t pt-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">１０. 学生・教職員数</h3>
        <input type="text" id="s22_daigaku_name" class="border rounded px-3 py-2 text-sm mb-2 w-64" placeholder="大学等名">
        <div class="overflow-x-auto"><table class="text-sm border-collapse w-full">
          <thead><tr class="bg-gray-100"><th class="border px-2 py-1"></th><th class="border px-2 py-1">入学定員</th><th class="border px-2 py-1">全学生数</th><th class="border px-2 py-1">収容定員</th><th class="border px-2 py-1">教員数</th><th class="border px-2 py-1">職員数</th></tr></thead>
          <tbody>
            <tr><td class="border px-2 py-1 font-bold text-xs">学部</td><td class="border px-1 py-1"><input type="number" id="s22_gakubu_nyugaku" class="w-full text-sm px-1"></td><td class="border px-1 py-1"><input type="number" id="s22_gakubu_zengakusei" class="w-full text-sm px-1"></td><td class="border px-1 py-1"><input type="number" id="s22_gakubu_shuyoteiin" class="w-full text-sm px-1"></td><td class="border px-1 py-1"><input type="number" id="s22_kyoinsuu" class="w-full text-sm px-1"></td><td class="border px-1 py-1"><input type="number" id="s22_shokuinsuu" class="w-full text-sm px-1"></td></tr>
            <tr><td class="border px-2 py-1 font-bold text-xs">大学院</td><td class="border px-1 py-1"><input type="number" id="s22_daigakuin_nyugaku" class="w-full text-sm px-1"></td><td class="border px-1 py-1"><input type="number" id="s22_daigakuin_zengakusei" class="w-full text-sm px-1"></td><td class="border px-1 py-1"><input type="number" id="s22_daigakuin_shuyoteiin" class="w-full text-sm px-1"></td><td class="border px-2 py-1 text-center text-gray-400">―</td><td class="border px-2 py-1 text-center text-gray-400">―</td></tr>
          </tbody>
        </table></div>
      </div>

      <div class="border-t pt-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">担当部署・連絡先<span class="badge-uni px-1 rounded text-xs ml-1">🎓</span></h3>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs font-bold text-gray-700 mb-1">取組を実施する組織名</label><input type="text" id="s22_tanto_busyo" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：リスキリング推進室"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">所在地</label><input type="text" id="s22_shozaichi" class="w-full border rounded px-3 py-2 text-sm" placeholder="〒000-0000 都道府県..."></div>
        </div>
        <div class="grid grid-cols-3 gap-3 mt-2">
          <div><label class="block text-xs font-bold text-gray-700 mb-1">担当者（ふりがな）</label><input type="text" id="s22_tanto_furi" class="w-full border rounded px-3 py-2 text-sm"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">担当者氏名</label><input type="text" id="s22_tanto_name" class="w-full border rounded px-3 py-2 text-sm"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">所属・職名</label><input type="text" id="s22_tanto_shoku" class="w-full border rounded px-3 py-2 text-sm"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">電話番号</label><input type="text" id="s22_tanto_tel" class="w-full border rounded px-3 py-2 text-sm"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">緊急連絡先</label><input type="text" id="s22_tanto_emg" class="w-full border rounded px-3 py-2 text-sm"></div>
          <div><label class="block text-xs font-bold text-gray-700 mb-1">メールアドレス</label><input type="text" id="s22_tanto_mail1" class="w-full border rounded px-3 py-2 text-sm"></div>
        </div>
      </div>

      <div class="flex justify-between mt-5">
        <button onclick="showTab('s21')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('s23')" class="bg-blue-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-700">次へ →</button>
      </div>
    </div>
  </div>

  <!-- 事業計画書（様式3 PPT相当） -->
  <div id="s23" class="form-section max-w-6xl mx-auto px-4 pb-10">
    <div class="bg-white rounded-b rounded-r shadow p-6 space-y-5">
      <h2 class="text-base font-bold text-orange-800 border-b-2 border-orange-800 pb-2 mb-4">事業計画書（様式３）</h2>

      <div><label class="block text-sm font-bold text-gray-700 mb-1">【大学全体の体制】経営層参画・全学方針・担当部署 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><textarea id="s23_taisei" rows="4" class="w-full border rounded px-3 py-2 text-sm" placeholder="学長のリーダーシップのもと、全学的なリスキリング推進方針を策定。専任コーディネーター配置予定..."></textarea></div>

      <div class="border-t pt-4"><h3 class="text-sm font-bold text-orange-800 mb-2">企業/エコシステムとの連携（必須要件）</h3>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">受講生派遣確約・議論体制・効果調査・学修者意欲向上の工夫 <span class="badge-both px-1 rounded text-xs">🤝</span></label><textarea id="s23_kigyorenkei" rows="5" class="w-full border rounded px-3 py-2 text-sm" placeholder="●社から受講生派遣の確約を取得済み。四半期ごとに産学協働会議を開催..."></textarea></div>
      </div>

      <div class="border-t pt-4"><h3 class="text-sm font-bold text-orange-800 mb-2">プログラム開発・実施</h3>
        <div><label class="block text-sm font-bold text-gray-700 mb-1">産業成長への貢献・VR実習設計・170人達成計画・デジタルバッジ <span class="badge-both px-1 rounded text-xs">🤝</span></label><textarea id="s23_program" rows="6" class="w-full border rounded px-3 py-2 text-sm" placeholder="当プログラムは〇〇産業の成長に直結する人材育成を目的とし、VRシミュレーション実習と座学を組み合わせた独自設計..."></textarea></div>
        <div class="mt-3 grid grid-cols-2 gap-3">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">企業ニーズの把握とプログラムへの反映（加点）<span class="badge-jg px-1 rounded text-xs ml-1">🏢</span></label><textarea id="s23_senzai" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="ヒアリング等で把握した企業ニーズ..."></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">連携企業を増やす工夫（加点）<span class="badge-jg px-1 rounded text-xs ml-1">🏢</span></label><textarea id="s23_kigyozoukyou" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="業界団体・商工会議所を通じた展開..."></textarea></div>
        </div>
        <div class="mt-3">
          <label class="block text-sm font-bold text-gray-700 mb-1">【P4】教育プログラム一覧 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse"><thead><tr class="bg-orange-700 text-white"><th class="border px-2 py-1 w-72">プログラム名</th><th class="border px-2 py-1 w-28">対象者</th><th class="border px-2 py-1 w-14">定員</th><th class="border px-2 py-1 w-32">受講料（円）</th><th class="border px-2 py-1">目的・内容</th><th class="border px-2 py-1 w-8">削除</th></tr></thead><tbody id="programTbody2"></tbody></table>
          </div>
          <button onclick="addProgramRow2()" class="mt-2 bg-orange-600 text-white text-xs px-3 py-1 rounded hover:bg-orange-700">＋ プログラムを追加</button>
        </div>
      </div>

      <div class="border-t pt-4"><h3 class="text-sm font-bold text-orange-800 mb-2">加点要件（現下の課題への対応）</h3>
        <div class="space-y-3">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">①就職氷河期世代等の支援</label><textarea id="s23_kadai1" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">②地方人材確保のための仕組み構築</label><textarea id="s23_kadai2" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">③スキルの可視化・処遇改善</label><textarea id="s23_kadai3" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">⑥修士・博士課程への接続</label><textarea id="s23_kadai6" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
        </div>
      </div>

      <div class="border-t pt-4"><h3 class="text-sm font-bold text-orange-800 mb-2">自走化計画</h3>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-sm font-bold text-gray-700 mb-1">自走化目標像（2〜4年後）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><textarea id="s23_jisoka" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">財務計画（年度別収支）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><textarea id="s23_jisoka_zaimu" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="2年目：受講料収入〇〇千円、コスト〇〇千円&#10;3年目：〜&#10;4年目：収支均衡〜"></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">取組計画（年度別アクション）<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><textarea id="s23_jisoka_plan" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">人員確保計画<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><textarea id="s23_jisoka_jinzai" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
        </div>
        <div class="mt-3"><label class="block text-sm font-bold text-gray-700 mb-1">R8年度スケジュール<span class="badge-both px-1 rounded text-xs ml-1">🤝</span></label><textarea id="s23_schedule" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="前期（4〜9月）：体制整備・VRコンテンツ開発・試行実施&#10;後期（10〜3月）：本格実施・効果測定・改善"></textarea></div>
      </div>

      <div class="flex justify-between mt-5">
        <button onclick="showTab('s22')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="showTab('s3')" class="bg-blue-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-700">次へ（申請経費）→</button>
      </div>
    </div>
  </div>

  <!-- 伴走支援（様式4） -->
  <div id="s24" class="form-section max-w-6xl mx-auto px-4 pb-10">
    <div class="bg-white rounded-b rounded-r shadow p-6 space-y-5">
      <h2 class="text-base font-bold text-orange-800 border-b-2 border-orange-800 pb-2 mb-4">様式４　伴走支援について</h2>
      <p class="text-xs text-gray-500">文部科学省からの伴走支援（プログラム改善アドバイス・企業マッチング等）について、期待する内容と解決したい課題を記入してください。</p>
      <div><label class="block text-sm font-bold text-gray-700 mb-1">伴走支援に期待する内容・解決したい課題 <span class="badge-both px-1 rounded text-xs">🤝 共同</span></label><textarea id="s23_bansosien" rows="8" class="w-full border rounded px-3 py-2 text-sm" placeholder="例：連携企業のマッチング支援、プログラムの質向上に向けたアドバイス..."></textarea></div>
      <div class="flex justify-between mt-5">
        <button onclick="showTab('sslide')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ（スライド構成案）</button>
        <button onclick="saveData(); showOutput();" class="bg-green-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-green-700">💾 保存して申請様式を出力 →</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- スライド構成案（両メニュー共通） -->
  <div id="sslide" class="form-section max-w-6xl mx-auto px-4 pb-10">
    <div class="bg-white rounded-b rounded-r shadow p-6">
      <h2 class="text-base font-bold text-green-800 border-b-2 border-green-700 pb-2 mb-3">スライド構成案（AI生成）</h2>
      <p class="text-xs text-gray-500 mb-4">入力した申請書の内容をもとに、プレゼン用スライドの詳細な構成案をAIが生成します。<br>生成されたテキストをそのままコピーして、<span class="font-bold text-gray-700">NotebookLM、Manus、GenSpark</span> などのAIスライドツールに貼り付けてご利用いただけます。</p>

      <div class="flex items-center gap-3 mb-4">
        <button id="slideGenBtn" onclick="generateSlideOutline()" class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2 rounded text-sm flex items-center gap-2">
          <span>✨</span> スライド構成案を生成する（約10〜20秒）
        </button>
        <span id="slideStatus" class="text-sm text-gray-500"></span>
      </div>

      <div id="slideResult" class="hidden">
        <div class="flex justify-between items-center mb-2">
          <p class="text-xs text-green-700 font-bold">生成完了！以下のテキストをコピーしてAIスライドツールに貼り付けてください。</p>
          <button onclick="copySlideText()" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded flex items-center gap-1">
            <span>📋</span> 全文コピー
          </button>
        </div>
        <textarea id="slideText" readonly rows="30" class="w-full border rounded px-3 py-2 text-xs font-mono bg-gray-50 leading-relaxed" style="font-family: 'Courier New', monospace;"></textarea>
      </div>

      <div class="flex justify-between mt-5">
        <button onclick="showTab('s3')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ（申請経費）</button>
        <?php if ($pageMenu === 'menu2'): ?>
        <button onclick="showTab('s24')" class="bg-blue-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-blue-700">次へ（伴走支援）→</button>
        <?php else: ?>
        <button onclick="saveData(); showOutput();" class="bg-green-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-green-700">💾 保存して申請様式を出力 →</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ===================== OUTPUT PAGE ===================== -->
<div id="outputPage" class="hidden">
  <div class="no-print bg-blue-900 text-white px-4 py-3">
    <div class="max-w-4xl mx-auto flex items-center justify-between">
      <div>
        <p class="text-xs text-blue-300">申請様式 出力プレビュー</p>
        <h1 class="text-base font-bold" id="outputHeader">申請書</h1>
      </div>
      <div class="flex gap-2">
        <button onclick="goToForm()" class="bg-gray-600 text-white text-xs px-3 py-2 rounded font-bold hover:bg-gray-700">← 入力に戻る</button>
        <button onclick="window.print()" class="bg-amber-500 text-white text-xs px-3 py-2 rounded font-bold hover:bg-amber-600">🖨 印刷・PDF保存</button>
        <button onclick="exportJSON()" class="bg-gray-500 text-white text-xs px-3 py-2 rounded font-bold hover:bg-gray-600">📥 JSONで保存</button>
      </div>
    </div>
  </div>
  <div class="max-w-4xl mx-auto px-4 py-6" id="printOutput"></div>
</div>

<script>
// ================================================================
// STATE
// ================================================================
const UNI_ID = "<?php echo $id; ?>";
let currentUniName = "";

const keihiRows = [
  {cat:'物品費', sub:'①設備備品費', id:'kb1'},
  {cat:'物品費', sub:'②消耗品費', id:'kb2'},
  {cat:'人件費・謝金', sub:'①人件費', id:'kb3'},
  {cat:'人件費・謝金', sub:'②謝金', id:'kb4'},
  {cat:'旅費', sub:'旅費', id:'kb5'},
  {cat:'その他', sub:'①外注費', id:'kb6'},
  {cat:'その他', sub:'②印刷製本費', id:'kb7'},
  {cat:'その他', sub:'③通信運搬費', id:'kb8'},
  {cat:'その他', sub:'④その他（諸経費）', id:'kb9'},
];

let programs = [{name:'',target:'',teiin:'',ryokin:'',naiyou:''}];
let committee = Array.from({length:10}, ()=>({name:'',shoku:'',yakuwari:''}));

const MENU = "<?php echo $pageMenu; ?>";
const MENU2_FIELD_IDS = [
  's21_date','s21_daigakuname','s21_gakucho',
  's22_jisshisyutai','s22_jigyosha_furi','s22_jigyosha_name','s22_jigyosha_shoku',
  's22_shinseisha_furi','s22_shinseisha_name','s22_shinseisha_shoku',
  's22_sekinin_furi','s22_sekinin_name','s22_sekinin_shoku',
  's22_jigyomei','s22_ryoiki1_main','s22_ryoiki1_sub','s22_point',
  's22_sogaku','s22_hojokinn','s22_kikan_futan','s22_jukoryosyu',
  's22_kyodo_kigyo','s22_kyodo_kyo','s22_kyodo_other',
  's22_daigaku_name','s22_gakubu_nyugaku','s22_gakubu_zengakusei','s22_gakubu_shuyoteiin','s22_kyoinsuu','s22_shokuinsuu',
  's22_daigakuin_nyugaku','s22_daigakuin_zengakusei','s22_daigakuin_shuyoteiin',
  's22_tanto_busyo','s22_shozaichi','s22_tanto_furi','s22_tanto_name','s22_tanto_shoku','s22_tanto_tel','s22_tanto_emg','s22_tanto_mail1',
  's23_taisei','s23_kigyorenkei','s23_program','s23_senzai','s23_kigyozoukyou',
  's23_kadai1','s23_kadai2','s23_kadai3','s23_kadai6',
  's23_jisoka','s23_jisoka_zaimu','s23_jisoka_plan','s23_jisoka_jinzai','s23_schedule',
  's23_bansosien',
];
let programs2 = [{name:'',target:'',teiin:'',ryokin:'',naiyou:''}];

// ================================================================
// 進捗バー・必須フィールド
// ================================================================
// 進捗バー計算用（admin.php と同じ10フィールド）
const PROGRESS_KEYS_1 = ['s11_daigakuname','s11_gakucho','s12_jisshisyutai','s12_jigyomei','s12_point','s12_sogaku','s12_hojokinn','s13_iinkaime','s2_sangyo','s2_daigaku'];
const PROGRESS_KEYS_2 = ['s21_daigakuname','s21_gakucho','s22_jisshisyutai','s22_jigyomei','s22_point','s22_sogaku','s22_hojokinn','s23_taisei','s23_kigyorenkei','s23_program'];

// 必須フィールド（表示マーカー＋次にやること）
const REQUIRED_FIELDS_1 = [
  { id:'s11_daigakuname', label:'様式1-1 ▶ 大学名', tab:'s11' },
  { id:'s11_gakucho',     label:'様式1-1 ▶ 学長等氏名', tab:'s11' },
  { id:'s12_jisshisyutai',label:'様式1-2 ▶ 実施主体', tab:'s12' },
  { id:'s12_jigyomei',    label:'様式1-2 ▶ 事業名', tab:'s12' },
  { id:'s12_point',       label:'様式1-2 ▶ 事業のポイント', tab:'s12' },
  { id:'s12_sogaku',      label:'様式1-2 ▶ 総事業費', tab:'s12' },
  { id:'s12_hojokinn',    label:'様式1-2 ▶ 補助金申請額', tab:'s12' },
  { id:'s13_iinkaime',    label:'様式1-3 ▶ 委員会名', tab:'s13' },
  { id:'s13_mokuteki',    label:'様式1-3 ▶ 委員会の目的', tab:'s13' },
  { id:'s13_kentou',      label:'様式1-3 ▶ 検討内容', tab:'s13' },
  { id:'s2_sangyo',       label:'様式2 ▶ 産業界の参画機関', tab:'s2' },
  { id:'s2_daigaku',      label:'様式2 ▶ 大学の役割', tab:'s2' },
  { id:'s2_kigyorenkei',  label:'様式2 ▶ 企業連携', tab:'s2' },
  { id:'s2_kadai1',       label:'様式2 ▶ 課題①アドバンストEW', tab:'s2' },
  { id:'s2_kadai2',       label:'様式2 ▶ 課題②就職氷河期', tab:'s2' },
  { id:'s2_kadai3',       label:'様式2 ▶ 課題③地方人材確保', tab:'s2' },
  { id:'s2_kadai4',       label:'様式2 ▶ 課題④スキル可視化', tab:'s2' },
  { id:'s2_kadai5',       label:'様式2 ▶ 課題⑤教員インセンティブ', tab:'s2' },
  { id:'s2_kadai6',       label:'様式2 ▶ 課題⑥全学的体制', tab:'s2' },
  { id:'s2_kadai7',       label:'様式2 ▶ 課題⑦修士博士接続', tab:'s2' },
  { id:'s2_kadai8',       label:'様式2 ▶ 課題⑧大学間連携', tab:'s2' },
  { id:'s2_jisoka_goal',  label:'様式2 ▶ 自走化目標像', tab:'s2' },
  { id:'s2_jisoka_plan',  label:'様式2 ▶ 自走化計画', tab:'s2' },
  { id:'s2_jisoka_zaimu', label:'様式2 ▶ 財務計画', tab:'s2' },
];
const REQUIRED_FIELDS_2 = [
  { id:'s21_daigakuname',  label:'様式1-1 ▶ 大学名', tab:'s21' },
  { id:'s21_gakucho',      label:'様式1-1 ▶ 学長等氏名', tab:'s21' },
  { id:'s22_jisshisyutai', label:'様式1-2 ▶ 実施主体', tab:'s22' },
  { id:'s22_jigyomei',     label:'様式1-2 ▶ 事業名', tab:'s22' },
  { id:'s22_point',        label:'様式1-2 ▶ 事業のポイント', tab:'s22' },
  { id:'s22_sogaku',       label:'様式1-2 ▶ 総事業費', tab:'s22' },
  { id:'s22_hojokinn',     label:'様式1-2 ▶ 補助金申請額', tab:'s22' },
  { id:'s23_taisei',       label:'事業計画書 ▶ 学内体制', tab:'s23' },
  { id:'s23_kigyorenkei',  label:'事業計画書 ▶ 企業連携', tab:'s23' },
  { id:'s23_program',      label:'事業計画書 ▶ プログラム詳細', tab:'s23' },
  { id:'s23_senzai',       label:'事業計画書 ▶ 企業ニーズ把握', tab:'s23' },
  { id:'s23_jisoka',       label:'事業計画書 ▶ 自走化目標像', tab:'s23' },
  { id:'s23_jisoka_zaimu', label:'事業計画書 ▶ 財務計画', tab:'s23' },
  { id:'s23_jisoka_plan',  label:'事業計画書 ▶ 自走化計画', tab:'s23' },
];

function updateProgress() {
  const reqFields    = MENU === 'menu2' ? REQUIRED_FIELDS_2 : REQUIRED_FIELDS_1;
  const progressKeys = MENU === 'menu2' ? PROGRESS_KEYS_2   : PROGRESS_KEYS_1;

  const filled = progressKeys.filter(k => { const el = document.getElementById(k); return el && el.value.trim(); }).length;
  const pct = Math.round(filled / progressKeys.length * 100);

  const bar = document.getElementById('progressBar');
  const pctEl = document.getElementById('progressPct');
  if (bar) {
    bar.style.width = pct + '%';
    bar.className = 'h-3 rounded-full transition-all duration-500 ' +
      (pct >= 100 ? 'bg-green-500' : pct >= 70 ? 'bg-blue-500' : pct >= 30 ? 'bg-yellow-500' : 'bg-red-400');
  }
  if (pctEl) pctEl.textContent = pct + '%';

  const unfilled = reqFields.filter(f => { const el = document.getElementById(f.id); return el && !el.value.trim(); }).slice(0, 3);
  const top3El = document.getElementById('top3Tasks');
  if (!top3El) return;
  if (unfilled.length === 0) {
    top3El.innerHTML = '<span class="text-green-600 font-bold text-xs">✅ 必須項目がすべて入力済みです！</span>';
  } else {
    top3El.innerHTML = unfilled.map((f, i) =>
      `<button onclick="jumpToField('${f.id}','${f.tab}')" class="inline-flex items-center bg-orange-50 border border-orange-300 text-orange-700 text-xs px-2 py-1 rounded hover:bg-orange-100 cursor-pointer"><span class="font-bold mr-1">${i+1}.</span>${f.label}</button>`
    ).join('');
  }
}

function jumpToField(fieldId, tab) {
  showTab(tab);
  setTimeout(() => {
    const el = document.getElementById(fieldId);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    el.focus();
    el.classList.add('ring-2', 'ring-orange-400', 'ring-offset-1');
    setTimeout(() => el.classList.remove('ring-2', 'ring-orange-400', 'ring-offset-1'), 2000);
  }, 150);
}

function addRequiredMarkers() {
  const reqFields = MENU === 'menu2' ? REQUIRED_FIELDS_2 : REQUIRED_FIELDS_1;
  const reqIds = new Set(reqFields.map(f => f.id));
  reqIds.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    // label[for=id] または親 div 内の label を探す
    let labelEl = document.querySelector(`label[for="${id}"]`);
    if (!labelEl) {
      const parent = el.closest('div');
      labelEl = parent ? parent.querySelector('label') : null;
    }
    if (labelEl && !labelEl.querySelector('.required-mark')) {
      labelEl.insertAdjacentHTML('beforeend', '<span class="required-mark">必須</span>');
    }
  });
}

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
  buildKeihiTable();
  if (MENU === 'menu2') {
    buildProgramTable2();
    showTab('s21'); // s11 の active を解除
  } else {
    buildCommitteeTable();
    buildProgramTable();
  }
  loadData(); // サーバーからロード（完了後に addRequiredMarkers + updateProgress を呼ぶ）
  setupAutoSave();

  // 入力のたびに進捗を更新
  document.addEventListener('input', updateProgress);
  document.addEventListener('change', updateProgress);
});

// ================================================================
function goToForm() {
  document.getElementById('outputPage').classList.add('hidden');
  document.getElementById('formPage').classList.remove('hidden');
}

// ================================================================
// TABS
// ================================================================
function showTab(id) {
  document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  const sec = document.getElementById(id);
  if (sec) sec.classList.add('active');
  document.querySelectorAll('.tab-btn').forEach(b => {
    if (b.getAttribute('onclick') && b.getAttribute('onclick').includes(`'${id}'`)) b.classList.add('active');
  });
}

// ================================================================
// DYNAMIC TABLES
// ================================================================
function buildCommitteeTable() {
  const tbody = document.getElementById('committeeTbody');
  tbody.innerHTML = '';
  committee.forEach((m, i) => {
    const tr = document.createElement('tr');
    tr.className = i%2===0 ? '' : 'bg-gray-50';
    tr.innerHTML = `<td class="border px-2 py-1 text-center text-gray-500 text-xs">${i+1}</td>
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${m.name}" oninput="committee[${i}].name=this.value" placeholder="氏名"></td>
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${m.shoku}" oninput="committee[${i}].shoku=this.value" placeholder="所属・職名"></td>
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${m.yakuwari}" oninput="committee[${i}].yakuwari=this.value" placeholder="承諾済み / 打診中"></td>`;
    tbody.appendChild(tr);
  });
}

function buildProgramTable() {
  const tbody = document.getElementById('programTbody');
  tbody.innerHTML = '';
  programs.forEach((p, i) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${p.name}" oninput="programs[${i}].name=this.value" placeholder="プログラム名"></td>
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${p.target}" oninput="programs[${i}].target=this.value"></td>
      <td class="border px-1 py-1"><input type="number" class="w-full text-sm px-1 py-0.5" value="${p.teiin}" oninput="programs[${i}].teiin=this.value"></td>
      <td class="border px-1 py-1"><input type="number" class="w-full text-sm px-1 py-0.5" value="${p.ryokin}" oninput="programs[${i}].ryokin=this.value"></td>
      <td class="border px-1 py-1"><textarea class="w-full text-sm px-1 py-0.5" rows="2" oninput="programs[${i}].naiyou=this.value">${p.naiyou}</textarea></td>
      <td class="border px-1 py-1 text-center"><button onclick="removeProgramRow(${i})" class="text-red-500 hover:text-red-700 font-bold">×</button></td>`;
    tbody.appendChild(tr);
  });
}

function addProgramRow() { programs.push({name:'',target:'',teiin:'',ryokin:'',naiyou:''}); buildProgramTable(); }
function removeProgramRow(i) { programs.splice(i,1); buildProgramTable(); }

function buildProgramTable2() {
  const tbody = document.getElementById('programTbody2');
  if (!tbody) return;
  tbody.innerHTML = '';
  programs2.forEach((p, i) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${p.name}" oninput="programs2[${i}].name=this.value" placeholder="プログラム名"></td>
      <td class="border px-1 py-1"><input type="text" class="w-full text-sm px-1 py-0.5" value="${p.target}" oninput="programs2[${i}].target=this.value"></td>
      <td class="border px-1 py-1"><input type="number" class="w-full text-sm px-1 py-0.5" value="${p.teiin}" oninput="programs2[${i}].teiin=this.value"></td>
      <td class="border px-1 py-1"><input type="number" class="w-full text-sm px-1 py-0.5" value="${p.ryokin}" oninput="programs2[${i}].ryokin=this.value"></td>
      <td class="border px-1 py-1"><textarea class="w-full text-sm px-1 py-0.5" rows="2" oninput="programs2[${i}].naiyou=this.value">${p.naiyou}</textarea></td>
      <td class="border px-1 py-1 text-center"><button onclick="removeProgramRow2(${i})" class="text-red-500 hover:text-red-700 font-bold">×</button></td>`;
    tbody.appendChild(tr);
  });
}
function addProgramRow2() { programs2.push({name:'',target:'',teiin:'',ryokin:'',naiyou:''}); buildProgramTable2(); }
function removeProgramRow2(i) { programs2.splice(i,1); buildProgramTable2(); }

function buildKeihiTable() {
  const tbody = document.getElementById('keihi_tbody');
  tbody.innerHTML = '';
  keihiRows.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="border px-3 py-1 text-sm font-bold bg-gray-50">${row.cat}：${row.sub}</td>
      <td class="border px-1 py-1"><input type="number" id="${row.id}_hojo" class="w-full text-sm px-1 py-0.5 text-right" placeholder="0" oninput="updateKeihiTotal()"></td>
      <td class="border px-1 py-1"><input type="number" id="${row.id}_futan" class="w-full text-sm px-1 py-0.5 text-right" placeholder="0" oninput="updateKeihiTotal()"></td>
      <td class="border px-2 py-1 text-right text-sm" id="${row.id}_kibo">0</td>
      <td class="border px-1 py-1"><textarea id="${row.id}_naiyou" rows="1" class="w-full text-sm px-1 py-0.5" placeholder="内容・積算根拠"></textarea></td>`;
    tbody.appendChild(tr);
  });
}

function updateKeihiTotal() {
  let h=0, f=0;
  keihiRows.forEach(row => {
    const hv = parseFloat(document.getElementById(`${row.id}_hojo`)?.value||0)||0;
    const fv = parseFloat(document.getElementById(`${row.id}_futan`)?.value||0)||0;
    const kibo = document.getElementById(`${row.id}_kibo`);
    if(kibo) kibo.textContent = (hv+fv).toLocaleString();
    h+=hv; f+=fv;
  });
  document.getElementById('total_hojo').textContent = h.toLocaleString();
  document.getElementById('total_futan').textContent = f.toLocaleString();
  document.getElementById('total_kibo').textContent = (h+f).toLocaleString();
}

// ================================================================
// CHAR COUNTER
// ================================================================
function updateCounter(el, id) {
  const len = el.value.length, max = parseInt(el.getAttribute('maxlength')||400);
  const c = document.getElementById(id);
  if(c){ c.textContent=`${len} / ${max}字`; c.className='char-counter text-right mt-1'+(len>max*.9?' warn':''); }
}

// ================================================================
// SAVE / LOAD
// ================================================================
const FIELD_IDS = [
  's11_date','s11_daigakuname','s11_gakucho',
  's12_jisshisyutai','s12_jigyosha_furi','s12_jigyosha_name','s12_jigyosha_shoku',
  's12_shinseisha_furi','s12_shinseisha_name','s12_shinseisha_shoku',
  's12_sekininsha_furi','s12_sekininsha_name','s12_sekininsha_shoku',
  's12_jigyomei','s12_point','s12_sogaku','s12_hojokinn','s12_kikan_futan','s12_jukoryosyu',
  's12_kyodo_san','s12_kyodo_kan','s12_kyodo_gaku','s12_kyodo_kin','s12_kyodo_other',
  's12_daigaku_name','s12_gakubu_nyugaku','s12_gakubu_zengakusei','s12_gakubu_shuyoteiin',
  's12_kyoinsuu','s12_shokuinsuu','s12_daigakuin_nyugaku','s12_daigakuin_zengakusei','s12_daigakuin_shuyoteiin',
  's12_gakubu_jisshi','s12_kenkyuka','s12_bukaname','s12_shozaichi',
  's12_sekinin_furi','s12_sekinin_name','s12_sekinin_shoku',
  's12_tanto_furi','s12_tanto_name','s12_tanto_shoku','s12_tanto_tel','s12_tanto_emg','s12_tanto_mail1','s12_tanto_mail2',
  's13_iinkaime','s13_mokuteki','s13_kentou','s13_iinsuu','s13_kaiji',
  's2_sangyo','s2_daigaku','s2_gyosei','s2_kinyu','s2_platform_jiko',
  's2_katsudo','s2_kigyorenkei',
  's2_kadai1','s2_kadai2','s2_kadai3','s2_kadai4','s2_kadai5','s2_kadai6','s2_kadai7','s2_kadai8',
  's2_jisoka_hyoka','s2_nenkan','s2_jisoka_goal','s2_jisoka_plan','s2_jisoka_zaimu','s2_jisoka_jinzai','s2_badge',
];

function getUniData(name) {
  try { return JSON.parse(localStorage.getItem(getUniDataKey(name))) || {}; } catch{ return {}; }
}

function gatherData() {
  const fields = {};
  const fieldIds = MENU === 'menu2' ? MENU2_FIELD_IDS : FIELD_IDS;
  fieldIds.forEach(id => { const el=document.getElementById(id); if(el) fields[id]=el.value; });
  const keihi = {};
  keihiRows.forEach(row => {
    keihi[row.id] = {
      hojo: document.getElementById(`${row.id}_hojo`)?.value||'',
      futan: document.getElementById(`${row.id}_futan`)?.value||'',
      naiyou: document.getElementById(`${row.id}_naiyou`)?.value||'',
    };
  });
  const theme = document.getElementById('aiTheme')?.value || '';
  const region = document.getElementById('aiRegion')?.value || '';
  if (MENU === 'menu2') {
    return { fields, programs2: JSON.parse(JSON.stringify(programs2)), keihi, _uni: currentUniName, _theme: theme, _region: region, _menu: 'menu2' };
  }
  return { fields, programs: JSON.parse(JSON.stringify(programs)), committee: JSON.parse(JSON.stringify(committee)), keihi, _uni: currentUniName, _theme: theme, _region: region };
}

async function saveData() {
  const data = gatherData();
  
  try {
    const res = await fetch(`api.php?action=save&id=${UNI_ID}`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(data)
    });
    if(res.ok) {
      showToast('保存しました ✅');
    } else {
      showToast('保存に失敗しました ❌');
    }
  } catch(e) {
    console.error(e);
    showToast('通信エラー ❌');
  }
}

let autoSaveTimer = null;
function setupAutoSave() {
  if (autoSaveTimer) clearInterval(autoSaveTimer);
  autoSaveTimer = setInterval(saveData, 30000);
}

async function loadData() {
  try {
    const res = await fetch(`api.php?action=get&id=${UNI_ID}`);
    if(!res.ok) throw new Error('Load failed');
    const data = await res.json();
    applyData(data);
  } catch(e) {
    alert('データの読み込みに失敗しました');
  }
}

function applyData(data) {
  currentUniName = data._uni || '';
  document.getElementById('formHeader').textContent = `${currentUniName}　入力フォーム`;

  if (MENU === 'menu2') {
    programs2 = data.programs2 || [{name:'',target:'',teiin:'',ryokin:'',naiyou:''}];
    buildProgramTable2();
    if (data.fields) {
      MENU2_FIELD_IDS.forEach(id => {
        const el = document.getElementById(id);
        if (el && data.fields[id] !== undefined) el.value = data.fields[id];
      });
    }
    const el22p = document.getElementById('s22_point');
    if(el22p) updateCounter(el22p,'counter22p');
  } else {
    programs = data.programs || [{name:'',target:'',teiin:'',ryokin:'',naiyou:''}];
    committee = data.committee || Array.from({length:10},()=>({name:'',shoku:'',yakuwari:''}));
    buildProgramTable();
    buildCommitteeTable();
    if (data.fields) {
      FIELD_IDS.forEach(id => {
        const el = document.getElementById(id);
        if (el && data.fields[id] !== undefined) el.value = data.fields[id];
      });
    }
    const el6 = document.getElementById('s12_point');
    if(el6) updateCounter(el6,'counter6');
  }

  if (data.keihi) {
    keihiRows.forEach(row => {
      const k = data.keihi[row.id]; if(!k) return;
      const h=document.getElementById(`${row.id}_hojo`), f=document.getElementById(`${row.id}_futan`), n=document.getElementById(`${row.id}_naiyou`);
      if(h) h.value=k.hojo; if(f) f.value=k.futan; if(n) n.value=k.naiyou;
    });
    updateKeihiTotal();
  }
  if (data._theme) document.getElementById('aiTheme').value = data._theme;
  if (data._region) document.getElementById('aiRegion').value = data._region;
  window._existingPdfs = data._pdfs || [];

  // 大学名フィールドを自動入力（空欄の場合のみ）
  if (currentUniName) {
    const autoFill = MENU === 'menu2'
      ? { 's21_daigakuname': currentUniName, 's22_jisshisyutai': currentUniName, 's22_daigaku_name': currentUniName }
      : { 's11_daigakuname': currentUniName, 's12_jisshisyutai': currentUniName, 's12_daigaku_name': currentUniName };
    Object.entries(autoFill).forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (el && !el.value.trim()) el.value = val;
    });
  }

  // データ読み込み完了後に必須マークと進捗を更新
  addRequiredMarkers();
  updateProgress();
}

// ================================================================
// EXPORT / IMPORT
// ================================================================
function exportJSON() {
  const data = gatherData();
  const blob = new Blob([JSON.stringify(data, null, 2)], {type:'application/json'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `koboform_${currentUniName||'data'}_${new Date().toISOString().slice(0,10)}.json`;
  a.click();
}

function openAiModal() {
  document.getElementById('aiModal').classList.remove('hidden');
  document.getElementById('aiTheme').focus();
  // Show existing PDF info
  const existingDiv = document.getElementById('aiPdfExisting');
  if (window._existingPdfs && window._existingPdfs.length > 0) {
    existingDiv.textContent = window._existingPdfs.length + '件のPDFが添付済みです。新しいファイルを選択すると置き換えられます。';
  } else {
    existingDiv.textContent = '';
  }
}

async function runAiGenerate() {
  const theme = document.getElementById('aiTheme').value.trim();
  const region = document.getElementById('aiRegion').value.trim();
  if(!theme) { alert('テーマを入力してください'); return; }

  const btn = document.getElementById('aiGenBtn');
  const originalText = btn.textContent;
  btn.disabled = true;

  try {
    // Step 1: Upload PDFs if any
    const pdfInput = document.getElementById('aiPdfFiles');
    if (pdfInput.files.length > 0) {
      btn.textContent = 'PDFをアップロード中...';
      const formData = new FormData();
      formData.append('university_id', UNI_ID);
      for (let i = 0; i < Math.min(pdfInput.files.length, 2); i++) {
        formData.append('pdfs[]', pdfInput.files[i]);
      }
      const uploadRes = await fetch('pdf_upload.php', { method: 'POST', body: formData });
      const uploadData = await uploadRes.json();
      if (uploadData.error) throw new Error(uploadData.error);
      window._existingPdfs = uploadData.files.map(f => f.path);
    }

    // Step 2: Call AI generation (server reads uploaded PDFs)
    btn.textContent = 'AIが生成中... (約10〜30秒)';
    const res = await fetch('ai_generate.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ theme, region, name: currentUniName, menu: MENU, university_id: UNI_ID })
    });
    const data = await res.json();
    if(data.error) throw new Error(data.error);

    // データを反映
    if(data.fields) {
      Object.keys(data.fields).forEach(k => {
        const el = document.getElementById(k);
        if(el) el.value = data.fields[k];
      });
    }
    if (MENU === 'menu2') {
      if(data.programs2) { programs2 = data.programs2; buildProgramTable2(); }
      const el22p = document.getElementById('s22_point');
      if(el22p) updateCounter(el22p,'counter22p');
    } else {
      if(data.programs) { programs = data.programs; buildProgramTable(); }
      const el6 = document.getElementById('s12_point');
      if(el6) updateCounter(el6,'counter6');
    }
    if(data.keihi) {
      Object.keys(data.keihi).forEach(k => {
        const row = data.keihi[k];
        const h=document.getElementById(`${k}_hojo`), f=document.getElementById(`${k}_futan`), n=document.getElementById(`${k}_naiyou`);
        if(h) h.value=row.hojo; if(f) f.value=row.futan; if(n) n.value=row.naiyou;
      });
      updateKeihiTotal();
    }

    document.getElementById('aiModal').classList.add('hidden');
    pdfInput.value = '';
    showToast('AIによる生成が完了しました');
    saveData();
    updateProgress();
  } catch(e) {
    alert('エラー: ' + e.message);
  } finally {
    btn.disabled = false;
    btn.textContent = originalText;
  }
}

function importData() { document.getElementById('importFile').click(); }

function loadImportFile(e) {
  const file = e.target.files[0]; if(!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    try {
      const data = JSON.parse(ev.target.result);
      applyData(data);
      showToast(`データを読み込みました`);
    } catch { alert('JSONファイルの読み込みに失敗しました'); }
  };
  reader.readAsText(file);
  e.target.value = '';
}

// ================================================================
// OUTPUT（申請様式レイアウト）
// ================================================================
function showOutput() {
  saveData();
  const data = gatherData();
  const f = data.fields;
  document.getElementById('outputHeader').textContent = `${currentUniName||''}　申請書 出力プレビュー`;

  const v = id => f[id] || '';
  const row = (label, val) => `<div class="shoshiki-row"><div class="shoshiki-label">${label}</div><div class="shoshiki-val">${val||'&nbsp;'}</div></div>`;
  const row2 = (label, val1, label2, val2) => `<div class="shoshiki-row"><div class="shoshiki-label">${label}</div><div class="shoshiki-val" style="flex:1">${val1||'&nbsp;'}</div><div class="shoshiki-label">${label2}</div><div class="shoshiki-val" style="flex:1">${val2||'&nbsp;'}</div></div>`;

  let html = '';

  if (MENU === 'menu2') {
    // ===== メニュー②産業成長 出力 =====

    // 様式1-1
    html += `<div class="shoshiki-box print-page">
      <div class="shoshiki-title">様式１-１　企画提案書提出状（メニュー②産業成長）</div>
      ${row('提出年月日', v('s21_date'))}
      ${row('大学等名', v('s21_daigakuname'))}
      ${row('学長等氏名', v('s21_gakucho'))}
    </div>`;

    // 様式1-2
    html += `<div class="shoshiki-box print-page">
      <div class="shoshiki-title">様式１-２　基本情報</div>
      ${row('１. 実施主体', v('s22_jisshisyutai'))}
      <div class="shoshiki-row"><div class="shoshiki-label">２. 事業者</div><div class="shoshiki-val">${v('s22_jigyosha_furi')} / ${v('s22_jigyosha_name')}　${v('s22_jigyosha_shoku')}</div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">３. 申請者</div><div class="shoshiki-val">${v('s22_shinseisha_furi')} / ${v('s22_shinseisha_name')}　${v('s22_shinseisha_shoku')}</div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">４. 事業責任者</div><div class="shoshiki-val">${v('s22_sekinin_furi')} / ${v('s22_sekinin_name')}　${v('s22_sekinin_shoku')}</div></div>
      ${row('プログラム名（事業名）', v('s22_jigyomei'))}
      ${row2('領域（メイン）', v('s22_ryoiki1_main'), '領域（サブ）', v('s22_ryoiki1_sub'))}
      <div class="shoshiki-row"><div class="shoshiki-label">事業のポイント<br>（400字以内）</div><div class="shoshiki-val">${v('s22_point')}</div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">８. 事業経費（千円）</div><div class="shoshiki-val" style="flex:1">
        <table class="shoshiki-table w-auto"><tr><th>事業規模（総事業費）</th><th>補助金申請額</th><th>大学等負担額</th><th>受講料収入見込み</th></tr>
        <tr><td>${v('s22_sogaku')||'―'}千円</td><td>${v('s22_hojokinn')||'―'}千円</td><td>${v('s22_kikan_futan')||'―'}千円</td><td>${v('s22_jukoryosyu')||'―'}千円</td></tr></table>
      </div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">９. 事業協働機関</div><div class="shoshiki-val" style="flex:1">
        <div><span class="font-bold text-xs">（産業界）</span> ${v('s22_kyodo_kigyo')}</div>
        <div><span class="font-bold text-xs">（教育機関）</span> ${v('s22_kyodo_kyo')}</div>
        <div><span class="font-bold text-xs">（その他）</span> ${v('s22_kyodo_other')}</div>
      </div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">１０. 学生・教職員数<br>（${v('s22_daigaku_name')}）</div><div class="shoshiki-val" style="flex:1">
        <table class="shoshiki-table w-auto"><tr><th></th><th>入学定員</th><th>全学生数</th><th>収容定員</th><th>教員数</th><th>職員数</th></tr>
        <tr><td class="font-bold">学部</td><td>${v('s22_gakubu_nyugaku')||'―'}</td><td>${v('s22_gakubu_zengakusei')||'―'}</td><td>${v('s22_gakubu_shuyoteiin')||'―'}</td><td>${v('s22_kyoinsuu')||'―'}</td><td>${v('s22_shokuinsuu')||'―'}</td></tr>
        <tr><td class="font-bold">大学院</td><td>${v('s22_daigakuin_nyugaku')||'―'}</td><td>${v('s22_daigakuin_zengakusei')||'―'}</td><td>${v('s22_daigakuin_shuyoteiin')||'―'}</td><td>―</td><td>―</td></tr>
        </table>
      </div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">担当部署・連絡先</div><div class="shoshiki-val" style="flex:1">
        <div><span class="text-xs text-gray-500">組織名：</span>${v('s22_tanto_busyo')}　<span class="text-xs text-gray-500">所在地：</span>${v('s22_shozaichi')}</div>
        <div>${v('s22_tanto_furi')} / ${v('s22_tanto_name')}　${v('s22_tanto_shoku')}</div>
        <div>TEL：${v('s22_tanto_tel')}　緊急：${v('s22_tanto_emg')}　mail：${v('s22_tanto_mail1')}</div>
      </div></div>
    </div>`;

    // 事業計画書
    html += `<div class="shoshiki-box print-page">
      <div class="shoshiki-title">事業計画書（様式３）</div>
      ${row('大学全体の体制', v('s23_taisei'))}
      ${row('企業/エコシステムとの連携', v('s23_kigyorenkei'))}
      ${row('プログラム開発・実施', v('s23_program'))}
      ${row('企業ニーズの把握と反映（加点）', v('s23_senzai'))}
      ${row('連携企業を増やす工夫（加点）', v('s23_kigyozoukyou'))}
      <div class="shoshiki-row"><div class="shoshiki-label">教育プログラム一覧</div><div class="shoshiki-val" style="flex:1">
        <table class="shoshiki-table w-full"><tr><th style="width:30%">プログラム名</th><th style="width:17%">対象者</th><th style="width:7%">定員</th><th style="width:14%">受講料</th><th>目的・内容</th></tr>
        ${(data.programs2||[]).map(p=>`<tr><td>${p.name||''}</td><td>${p.target||''}</td><td>${p.teiin||''}名</td><td>¥${p.ryokin||''}</td><td>${p.naiyou||''}</td></tr>`).join('')}
        </table>
      </div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">加点要件（課題対応）</div><div class="shoshiki-val" style="flex:1">
        <div><span class="font-bold text-xs">①就職氷河期世代等：</span>${v('s23_kadai1')}</div>
        <div><span class="font-bold text-xs">②地方人材確保：</span>${v('s23_kadai2')}</div>
        <div><span class="font-bold text-xs">③スキルの可視化：</span>${v('s23_kadai3')}</div>
        <div><span class="font-bold text-xs">⑥修士・博士接続：</span>${v('s23_kadai6')}</div>
      </div></div>
      <div class="shoshiki-row"><div class="shoshiki-label">自走化計画</div><div class="shoshiki-val" style="flex:1">
        <div><span class="font-bold text-xs">目標像：</span>${v('s23_jisoka')}</div>
        <div><span class="font-bold text-xs">財務計画：</span>${v('s23_jisoka_zaimu')}</div>
        <div><span class="font-bold text-xs">取組計画：</span>${v('s23_jisoka_plan')}</div>
        <div><span class="font-bold text-xs">人員確保：</span>${v('s23_jisoka_jinzai')}</div>
        <div><span class="font-bold text-xs">R8スケジュール：</span>${v('s23_schedule')}</div>
      </div></div>
    </div>`;

    // 様式4 伴走支援
    html += `<div class="shoshiki-box print-page">
      <div class="shoshiki-title">様式４　伴走支援について</div>
      ${row('伴走支援に期待する内容・解決したい課題', v('s23_bansosien'))}
    </div>`;

  } else {
  // ===== メニュー①地方創生 出力（既存） =====

  // 様式1-1
  html += `<div class="shoshiki-box print-page">
    <div class="shoshiki-title">様式１-１　企画提案書提出状</div>
    ${row('提出年月日', v('s11_date'))}
    ${row('大学等名', v('s11_daigakuname'))}
    ${row('学長等氏名', v('s11_gakucho'))}
  </div>`;

  // 様式1-2
  html += `<div class="shoshiki-box print-page">
    <div class="shoshiki-title">様式１-２　基本情報</div>
    ${row('１. 実施主体', v('s12_jisshisyutai'))}
    <div class="shoshiki-row"><div class="shoshiki-label">２. 事業者<br>（設置者）</div><div class="shoshiki-val" style="flex:1"><span class="text-xs text-gray-500">ふりがな：</span>${v('s12_jigyosha_furi')}<br><span class="text-xs text-gray-500">氏名：</span>${v('s12_jigyosha_name')}　<span class="text-xs text-gray-500">所属・職名：</span>${v('s12_jigyosha_shoku')}</div></div>
    <div class="shoshiki-row"><div class="shoshiki-label">３. 申請者<br>（学長等）</div><div class="shoshiki-val" style="flex:1"><span class="text-xs text-gray-500">ふりがな：</span>${v('s12_shinseisha_furi')}<br><span class="text-xs text-gray-500">氏名：</span>${v('s12_shinseisha_name')}　<span class="text-xs text-gray-500">所属・職名：</span>${v('s12_shinseisha_shoku')}</div></div>
    <div class="shoshiki-row"><div class="shoshiki-label">４. 事業責任者</div><div class="shoshiki-val" style="flex:1"><span class="text-xs text-gray-500">ふりがな：</span>${v('s12_sekininsha_furi')}<br><span class="text-xs text-gray-500">氏名：</span>${v('s12_sekininsha_name')}　<span class="text-xs text-gray-500">所属・職名：</span>${v('s12_sekininsha_shoku')}</div></div>
    ${row('５. 事業名', v('s12_jigyomei'))}
    <div class="shoshiki-row"><div class="shoshiki-label">６. 事業のポイント<br>（400字以内）</div><div class="shoshiki-val">${v('s12_point')}</div></div>
    <div class="shoshiki-row"><div class="shoshiki-label">７. 事業経費<br>（千円）</div><div class="shoshiki-val" style="flex:1">
      <table class="shoshiki-table w-auto"><tr><th>事業規模（総事業費）</th><th>補助金申請額</th><th>機関負担額</th><th>受講料収入見込み</th></tr>
      <tr><td>${v('s12_sogaku')||'―'}千円</td><td>${v('s12_hojokinn')||'―'}千円</td><td>${v('s12_kikan_futan')||'―'}千円</td><td>${v('s12_jukoryosyu')||'―'}千円</td></tr></table>
    </div></div>
    <div class="shoshiki-row"><div class="shoshiki-label">９. 事業協働機関</div><div class="shoshiki-val" style="flex:1">
      <div><span class="font-bold text-xs">（産）</span> ${v('s12_kyodo_san')}</div>
      <div><span class="font-bold text-xs">（官）</span> ${v('s12_kyodo_kan')}</div>
      <div><span class="font-bold text-xs">（学）</span> ${v('s12_kyodo_gaku')}</div>
      <div><span class="font-bold text-xs">（金）</span> ${v('s12_kyodo_kin')}</div>
      <div><span class="font-bold text-xs">（他）</span> ${v('s12_kyodo_other')}</div>
    </div></div>
    <div class="shoshiki-row"><div class="shoshiki-label">１０. 学生・教職員数<br>（${v('s12_daigaku_name')}）</div><div class="shoshiki-val" style="flex:1">
      <table class="shoshiki-table w-auto"><tr><th></th><th>入学定員</th><th>全学生数</th><th>収容定員</th><th>教員数</th><th>職員数</th></tr>
      <tr><td class="font-bold">学部</td><td>${v('s12_gakubu_nyugaku')||'―'}</td><td>${v('s12_gakubu_zengakusei')||'―'}</td><td>${v('s12_gakubu_shuyoteiin')||'―'}</td><td>${v('s12_kyoinsuu')||'―'}</td><td>${v('s12_shokuinsuu')||'―'}</td></tr>
      <tr><td class="font-bold">大学院</td><td>${v('s12_daigakuin_nyugaku')||'―'}</td><td>${v('s12_daigakuin_zengakusei')||'―'}</td><td>${v('s12_daigakuin_shuyoteiin')||'―'}</td><td>―</td><td>―</td></tr>
      </table>
    </div></div>
    ${row('１１. 取組実施学部等名', `学部等名：${v('s12_gakubu_jisshi')}　　研究科等名：${v('s12_kenkyuka')}`)}
    <div class="shoshiki-row"><div class="shoshiki-label">１２. 事務総括者<br>連絡先</div><div class="shoshiki-val" style="flex:1">
      <div><span class="text-xs text-gray-500">部課名：</span>${v('s12_bukaname')}　<span class="text-xs text-gray-500">所在地：</span>${v('s12_shozaichi')}</div>
      <div class="mt-1"><span class="font-bold text-xs">責任者</span>　${v('s12_sekinin_furi')} / ${v('s12_sekinin_name')}　${v('s12_sekinin_shoku')}</div>
      <div><span class="font-bold text-xs">担当者</span>　${v('s12_tanto_furi')} / ${v('s12_tanto_name')}　${v('s12_tanto_shoku')}</div>
      <div>TEL：${v('s12_tanto_tel')}　緊急：${v('s12_tanto_emg')}</div>
      <div>mail（主）：${v('s12_tanto_mail1')}　（副）：${v('s12_tanto_mail2')}</div>
    </div></div>
  </div>`;

  // 様式1-3
  html += `<div class="shoshiki-box print-page">
    <div class="shoshiki-title">様式１-３　事業実施委員会（プラットフォーム）</div>
    ${row('委員会名', v('s13_iinkaime'))}
    ${row('目的・役割', v('s13_mokuteki'))}
    ${row('検討の具体的内容', v('s13_kentou'))}
    ${row2('委員数', v('s13_iinsuu')+'名', '開催頻度', v('s13_kaiji')+'回/年')}
    <div class="shoshiki-row"><div class="shoshiki-label">委員会の構成員</div><div class="shoshiki-val" style="flex:1">
      <table class="shoshiki-table w-full"><tr><th style="width:2rem">No.</th><th>氏名</th><th>所属・職名</th><th>役割等</th></tr>
      ${(data.committee||[]).map((m,i)=>`<tr><td class="text-center">${i+1}</td><td>${m.name||''}</td><td>${m.shoku||''}</td><td>${m.yakuwari||''}</td></tr>`).join('')}
      </table>
    </div></div>
  </div>`;

  // 様式2
  html += `<div class="shoshiki-box print-page">
    <div class="shoshiki-title">様式２　企画提案書（各スライド内容）</div>
    <div class="shoshiki-row"><div class="shoshiki-label">[P2] プラットフォーム体制</div><div class="shoshiki-val" style="flex:1">
      <div><span class="font-bold text-xs">（産業界）</span> ${v('s2_sangyo')}</div>
      <div><span class="font-bold text-xs">（大学）</span> ${v('s2_daigaku')}</div>
      <div><span class="font-bold text-xs">（行政）</span> ${v('s2_gyosei')}</div>
      <div><span class="font-bold text-xs">（金融）</span> ${v('s2_kinyu')}</div>
      <div class="mt-1"><span class="font-bold text-xs">取組事項：</span>${v('s2_platform_jiko')}</div>
    </div></div>
    <div class="shoshiki-row"><div class="shoshiki-label">[P4] 教育プログラム一覧</div><div class="shoshiki-val" style="flex:1">
      <table class="shoshiki-table w-full"><tr><th style="width:30%">プログラム名</th><th style="width:17%">対象者</th><th style="width:7%">定員</th><th style="width:14%">受講料</th><th>目的・内容</th></tr>
      ${(data.programs||[]).map(p=>`<tr><td>${p.name||''}</td><td>${p.target||''}</td><td>${p.teiin||''}名</td><td>¥${p.ryokin||''}</td><td>${p.naiyou||''}</td></tr>`).join('')}
      </table>
    </div></div>
    ${row('[P3] 活動範囲と体制構築', v('s2_katsudo'))}
    ${row('[P5] 企業/エコシステムとの連携', v('s2_kigyorenkei'))}
    <div class="shoshiki-row"><div class="shoshiki-label">[P6] 課題への対応</div><div class="shoshiki-val" style="flex:1">
      ${['①アドバンストEW育成','②就職氷河期世代','③地方人材確保','④スキル可視化','⑤教員インセンティブ','⑥全学的体制','⑦修士・博士接続','⑧大学間連携'].map((t,i)=>`<div><span class="font-bold text-xs">${t}：</span>${v('s2_kadai'+(i+1))}</div>`).join('')}
    </div></div>
    ${row('[P7] 受講生・企業評価', v('s2_jisoka_hyoka'))}
    ${row('[P8] 年間計画（R8）', v('s2_nenkan'))}
    <div class="shoshiki-row"><div class="shoshiki-label">[P9] 自走化計画</div><div class="shoshiki-val" style="flex:1">
      <div><span class="font-bold text-xs">目標像：</span>${v('s2_jisoka_goal')}</div>
      <div><span class="font-bold text-xs">取組計画：</span>${v('s2_jisoka_plan')}</div>
      <div><span class="font-bold text-xs">財務計画：</span>${v('s2_jisoka_zaimu')}</div>
      <div><span class="font-bold text-xs">人員確保：</span>${v('s2_jisoka_jinzai')}</div>
    </div></div>
    ${row('デジタルバッジ', v('s2_badge'))}
  </div>`;

  } // end else (menu1)

  // 様式3（経費）は両メニュー共通
  let totalH=0, totalF=0;
  const keihiRows2 = keihiRows.map(row => {
    const k = data.keihi[row.id]||{hojo:'',futan:'',naiyou:''};
    const h=parseFloat(k.hojo)||0, f2=parseFloat(k.futan)||0;
    totalH+=h; totalF+=f2;
    return `<tr><td>${row.cat}：${row.sub}</td><td style="text-align:right">${h?h.toLocaleString():''}</td><td style="text-align:right">${f2?f2.toLocaleString():''}</td><td style="text-align:right">${h+f2?(h+f2).toLocaleString():''}</td><td>${k.naiyou||''}</td></tr>`;
  }).join('');

  html += `<div class="shoshiki-box">
    <div class="shoshiki-title">様式３　申請経費明細（単位：千円）</div>
    <div class="shoshiki-row"><div class="shoshiki-val" style="flex:1">
      <table class="shoshiki-table w-full">
        <tr><th class="text-left">経費区分</th><th>補助金申請額①</th><th>大学負担額②</th><th>事業規模①+②</th><th class="text-left">内容・積算根拠</th></tr>
        ${keihiRows2}
        <tr style="font-weight:bold;background:#f0f4ff"><td>合計</td><td style="text-align:right">${totalH.toLocaleString()}</td><td style="text-align:right">${totalF.toLocaleString()}</td><td style="text-align:right">${(totalH+totalF).toLocaleString()}</td><td></td></tr>
      </table>
    </div></div>
  </div>`;

  document.getElementById('printOutput').innerHTML = html;
  document.getElementById('formPage').classList.add('hidden');
  document.getElementById('outputPage').classList.remove('hidden');
}

// ================================================================
// SLIDE OUTLINE
// ================================================================
async function generateSlideOutline() {
  const btn    = document.getElementById('slideGenBtn');
  const status = document.getElementById('slideStatus');
  const result = document.getElementById('slideResult');

  btn.disabled = true;
  btn.textContent = '生成中... しばらくお待ちください';
  status.textContent = '（AIがスライド構成案を作成しています。約10〜20秒かかります）';
  result.classList.add('hidden');

  try {
    const data = gatherData();
    const payload = {
      menu: MENU,
      name: currentUniName,
      fields: data.fields,
      programs: data.programs || [],
      programs2: data.programs2 || [],
      keihi: data.keihi,
    };

    const res = await fetch('slide_generate.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.error) throw new Error(json.error);

    document.getElementById('slideText').value = json.text;
    result.classList.remove('hidden');
    status.textContent = '生成完了！';
  } catch(e) {
    status.textContent = 'エラー: ' + e.message;
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<span>✨</span> スライド構成案を生成する（約10〜20秒）';
  }
}

function copySlideText() {
  const text = document.getElementById('slideText').value;
  if (!text) return;
  navigator.clipboard.writeText(text).then(() => showToast('スライド構成案のテキストをコピーしました！'));
}

// ================================================================
// TOAST
// ================================================================
function showToast(msg) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:104px;right:20px;background:#166534;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.3);';
  document.body.appendChild(t);
  setTimeout(()=>t.remove(), 2200);
}
</script>

<!-- AI Chat Widget -->
<div id="chatWindow" class="chat-hidden fixed z-[9997] bg-white shadow-2xl flex flex-col no-print"
     style="width:400px; height:520px; bottom:108px; right:24px; border-radius:16px; overflow:hidden;">
  <!-- Header -->
  <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
    <div class="flex items-center gap-2">
      <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 bg-amber-50" style="border:2px solid rgba(255,255,255,0.4);">
        <img src="mascot.gif" alt="" style="width:220%; max-width:none; margin-left:-60%; margin-top:-55%;">
      </div>
      <div>
        <p class="text-sm font-bold leading-tight">ぐうた - AI</p>
        <p id="chatCurrentTab" class="text-xs text-indigo-200 leading-tight">申請書入力中</p>
      </div>
    </div>
    <button onclick="toggleChatWindow()" class="text-white hover:text-indigo-200 transition-colors p-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <!-- Messages -->
  <div id="chatMessages" class="flex-1 overflow-y-auto p-4 bg-gray-50" style="scroll-behavior:smooth;"></div>
  <!-- Suggestions -->
  <div id="chatSuggestions" class="hidden px-3 py-2 bg-white border-t flex gap-2 overflow-x-auto flex-shrink-0"></div>
  <!-- Input -->
  <div class="bg-white border-t px-3 py-2 flex-shrink-0">
    <p id="chatFieldIndicator" class="hidden text-xs text-indigo-500 mb-1 truncate"></p>
    <div class="flex items-center gap-2">
      <button onclick="askAboutField()"
              class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full
                     bg-indigo-50 text-indigo-500 hover:bg-indigo-100 transition-colors"
              title="選択中のフィールドについて質問する">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>
      <input type="text" id="chatInput"
             class="flex-1 border rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
             placeholder="質問を入力..."
             onkeydown="handleChatKeydown(event)"
             autocomplete="off">
      <button id="chatSendBtn" onclick="sendChatMessage()"
              class="flex-shrink-0 w-9 h-9 flex items-center justify-center rounded-full
                     bg-indigo-600 text-white hover:bg-indigo-700 transition-colors disabled:opacity-50"
              title="送信">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
        </svg>
      </button>
    </div>
  </div>
</div>

<!-- Chat Toggle Button -->
<button id="chatToggleBtn" onclick="toggleChatWindow()"
        class="fixed bottom-4 right-4 z-[9998] w-20 h-20
               rounded-full shadow-lg flex items-center justify-center
               transition-all duration-300 no-print hover:scale-110 bg-white border-2 border-amber-300"
        title="ぐうた - AI に質問する"
        style="padding:3px;">
  <img id="chatIconOpen" src="mascot.gif" alt="ぐうた - AI" class="w-[72px] h-[72px] rounded-full object-cover object-top">
  <svg id="chatIconClose" class="w-7 h-7 hidden text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
  </svg>
  <span id="chatBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold
        w-5 h-5 rounded-full flex items-center justify-center shadow">?</span>
</button>

<script>
// ================================================================
// AI CHATBOT
// ================================================================
let chatHistory = [];
let chatOpen = false;
let lastFocusedFieldId = '';

// Track which field the user last focused on
document.addEventListener('focusin', (e) => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
    if (e.target.closest('#chatWindow')) return;
    lastFocusedFieldId = e.target.id || '';
    updateChatFieldContext();
  }
});

function updateChatFieldContext() {
  const indicator = document.getElementById('chatFieldIndicator');
  if (!indicator) return;
  const label = lastFocusedFieldId ? getFieldLabel(lastFocusedFieldId) : '';
  if (label) {
    indicator.textContent = label + ' について質問できます';
    indicator.classList.remove('hidden');
  } else {
    indicator.classList.add('hidden');
  }
}

function getFieldLabel(fieldId) {
  const m = {
    's11_date':'提出年月日','s11_daigakuname':'大学名','s11_gakucho':'学長等氏名',
    's12_jisshisyutai':'実施主体','s12_jigyomei':'事業名','s12_point':'事業のポイント',
    's12_sogaku':'総事業費','s12_hojokinn':'補助金申請額','s12_kikan_futan':'大学負担額',
    's12_kyodo_san':'産業界協働機関','s12_kyodo_kan':'行政協働機関','s12_kyodo_gaku':'教育機関協働','s12_kyodo_kin':'金融機関協働',
    's13_iinkaime':'委員会名','s13_mokuteki':'委員会の目的','s13_kentou':'検討内容',
    's2_sangyo':'産業界の参画機関','s2_daigaku':'大学の役割','s2_gyosei':'行政の役割','s2_kinyu':'金融機関の役割',
    's2_platform_jiko':'プラットフォーム事項','s2_katsudo':'活動範囲','s2_kigyorenkei':'企業連携',
    's2_kadai1':'課題①アドバンストEW','s2_kadai2':'課題②就職氷河期','s2_kadai3':'課題③地方人材確保',
    's2_kadai4':'課題④スキル可視化','s2_kadai5':'課題⑤教員インセンティブ','s2_kadai6':'課題⑥全学的体制',
    's2_kadai7':'課題⑦修士博士接続','s2_kadai8':'課題⑧大学間連携',
    's2_jisoka_hyoka':'評価方法','s2_nenkan':'年間計画','s2_jisoka_goal':'自走化目標像',
    's2_jisoka_plan':'自走化計画','s2_jisoka_zaimu':'財務計画','s2_jisoka_jinzai':'人員確保計画',
    's21_date':'提出年月日','s21_daigakuname':'大学名','s21_gakucho':'学長等氏名',
    's22_jisshisyutai':'実施主体','s22_jigyomei':'プログラム名','s22_point':'事業のポイント',
    's22_ryoiki1_main':'主領域','s22_ryoiki1_sub':'サブ領域',
    's22_sogaku':'総事業費','s22_hojokinn':'補助金申請額','s22_kikan_futan':'大学負担額','s22_jukoryosyu':'受講料収入',
    's22_kyodo_kigyo':'産業界協働機関','s22_kyodo_kyo':'教育機関協働',
    's23_taisei':'学内体制','s23_kigyorenkei':'企業連携','s23_program':'プログラム詳細',
    's23_senzai':'企業ニーズ把握','s23_kigyozoukyou':'連携企業拡大',
    's23_kadai1':'課題①就職氷河期','s23_kadai2':'課題②地方人材','s23_kadai3':'課題③スキル可視化','s23_kadai6':'課題⑥修士博士接続',
    's23_jisoka':'自走化目標像','s23_jisoka_plan':'自走化計画','s23_jisoka_zaimu':'財務計画',
    's23_jisoka_jinzai':'人員確保計画','s23_schedule':'スケジュール','s23_bansosien':'伴走支援',
  };
  return m[fieldId] || '';
}

function getChatCurrentTab() {
  const s = document.querySelector('.form-section.active');
  return s ? s.id : '';
}

function getChatCurrentTabLabel() {
  const t = {
    's11':'様式1-1 提出状','s12':'様式1-2 基本情報','s13':'様式1-3 事業実施委員会',
    's2':'様式2 企画提案書','s3':'様式3 申請経費','sslide':'スライド構成案',
    's21':'様式1-1 提出状','s22':'様式1-2 基本情報','s23':'事業計画書',
    's24':'伴走支援',
  };
  return t[getChatCurrentTab()] || '';
}

function toggleChatWindow() {
  const win = document.getElementById('chatWindow');
  const iconOpen = document.getElementById('chatIconOpen');
  const iconClose = document.getElementById('chatIconClose');
  const badge = document.getElementById('chatBadge');

  chatOpen = !chatOpen;

  if (chatOpen) {
    win.classList.remove('chat-hidden');
    win.classList.add('chat-visible');
    iconOpen.classList.add('hidden');
    iconClose.classList.remove('hidden');
    badge.classList.add('hidden');
    if (chatHistory.length === 0) showWelcomeMessage();
    updateChatTabDisplay();
    setTimeout(() => document.getElementById('chatInput').focus(), 300);
  } else {
    win.classList.remove('chat-visible');
    win.classList.add('chat-hidden');
    iconOpen.classList.remove('hidden');
    iconClose.classList.add('hidden');
  }
}

function updateChatTabDisplay() {
  const el = document.getElementById('chatCurrentTab');
  if (el) el.textContent = getChatCurrentTabLabel() || '申請書入力中';
}

// Hook into existing showTab to update chat header
const _origShowTab = showTab;
showTab = function(id) {
  _origShowTab(id);
  updateChatTabDisplay();
};

function showWelcomeMessage() {
  const welcome = 'こんにちは！ぐうたです。\n\n申請書の記入でお困りのことがあれば、お気軽にご質問ください。\n\n例えば：\n・「事業のポイントの書き方を教えて」\n・「審査基準で配点が高い項目は？」\n・「この項目に何を書けばいい？」\n\n現在のフォーム内容を踏まえてアドバイスします。';
  appendMessage('assistant', welcome);
  showChatSuggestions(['この申請書の審査基準を教えて', '事業のポイントの書き方は？', 'VRゴーグルの経費計上方法は？']);
}

function appendMessage(role, content) {
  const el = document.getElementById('chatMessages');
  const wrapper = document.createElement('div');
  wrapper.className = 'mb-3 flex ' + (role === 'user' ? 'justify-end' : 'justify-start items-end gap-2');
  if (role === 'assistant') {
    const avatarWrap = document.createElement('div');
    avatarWrap.className = 'w-10 h-10 rounded-full overflow-hidden flex-shrink-0 bg-amber-50';
    avatarWrap.style.cssText = 'border:2px solid #fbbf24; min-width:40px;';
    const avatar = document.createElement('img');
    avatar.src = 'mascot.gif';
    avatar.style.cssText = 'width:220%; max-width:none; margin-left:-60%; margin-top:-55%;';
    avatarWrap.appendChild(avatar);
    wrapper.appendChild(avatarWrap);
  }
  const bubble = document.createElement('div');
  bubble.className = role === 'user' ? 'chat-msg-user' : 'chat-msg-ai';
  if (role === 'assistant') {
    bubble.innerHTML = formatChatMessage(content);
  } else {
    bubble.textContent = content;
  }
  wrapper.appendChild(bubble);
  el.appendChild(wrapper);
  el.scrollTop = el.scrollHeight;
}

function formatChatMessage(text) {
  let html = text
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/(https?:\/\/[^\s<)）」\]]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#4f46e5; text-decoration:underline; word-break:break-all;">$1</a>')
    .replace(/\n/g, '<br>');
  // Convert lines starting with ・ or - to list items
  html = html.replace(/((?:^|<br>)[・\-]\s?[^<]+(?:<br>|$))+/g, (match) => {
    const items = match.split('<br>').filter(s => s.trim()).map(s =>
      '<li>' + s.replace(/^[・\-]\s?/, '') + '</li>'
    ).join('');
    return '<ul>' + items + '</ul>';
  });
  return html;
}

function showChatSuggestions(suggestions) {
  const c = document.getElementById('chatSuggestions');
  if (!c) return;
  c.innerHTML = '';
  suggestions.forEach(s => {
    const btn = document.createElement('button');
    btn.className = 'chat-suggestion';
    btn.textContent = s;
    btn.onclick = () => sendChatMessage(s);
    c.appendChild(btn);
  });
  c.classList.remove('hidden');
}

function hideChatSuggestions() {
  const c = document.getElementById('chatSuggestions');
  if (c) c.classList.add('hidden');
}

function showTypingIndicator() {
  const el = document.getElementById('chatMessages');
  const d = document.createElement('div');
  d.id = 'chatTyping';
  d.className = 'mb-3 flex justify-start items-end gap-2';
  d.innerHTML = '<div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 bg-amber-50" style="border:2px solid #fbbf24; min-width:40px;"><img src="mascot.gif" style="width:220%; max-width:none; margin-left:-60%; margin-top:-55%;"></div><div class="chat-msg-ai chat-typing"><span></span><span></span><span></span></div>';
  el.appendChild(d);
  el.scrollTop = el.scrollHeight;
}

function removeTypingIndicator() {
  const t = document.getElementById('chatTyping');
  if (t) t.remove();
}

async function sendChatMessage(messageOverride) {
  const input = document.getElementById('chatInput');
  const message = messageOverride || input.value.trim();
  if (!message) return;
  if (!messageOverride) input.value = '';

  chatHistory.push({ role: 'user', content: message });
  appendMessage('user', message);
  hideChatSuggestions();
  showTypingIndicator();

  const sendBtn = document.getElementById('chatSendBtn');
  input.disabled = true;
  sendBtn.disabled = true;

  try {
    // Gather fields from active tab only (token optimization)
    const fieldIds = MENU === 'menu2' ? MENU2_FIELD_IDS : FIELD_IDS;
    const activeSection = document.querySelector('.form-section.active');
    const fieldsSnapshot = {};
    fieldIds.forEach(id => {
      const el = document.getElementById(id);
      if (el && el.value && activeSection && activeSection.contains(el)) {
        fieldsSnapshot[id] = el.value;
      }
    });

    const payload = {
      university_id: UNI_ID,
      message: message,
      conversation_history: chatHistory.slice(-10),
      current_context: {
        active_tab: getChatCurrentTab(),
        active_field: lastFocusedFieldId,
        menu: MENU,
        fields_snapshot: fieldsSnapshot,
      }
    };

    const res = await fetch('ai_chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    removeTypingIndicator();

    if (data.error) {
      appendMessage('assistant', 'エラーが発生しました: ' + data.error);
    } else {
      chatHistory.push({ role: 'assistant', content: data.reply });
      appendMessage('assistant', data.reply);
      if (data.suggestions && data.suggestions.length > 0) {
        showChatSuggestions(data.suggestions);
      }
    }
  } catch (e) {
    removeTypingIndicator();
    appendMessage('assistant', '通信エラーが発生しました。しばらくしてから再試行してください。');
    console.error('Chat error:', e);
  } finally {
    input.disabled = false;
    sendBtn.disabled = false;
    input.focus();
  }
}

function handleChatKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendChatMessage();
  }
}

function askAboutField() {
  if (!lastFocusedFieldId) return;
  const label = getFieldLabel(lastFocusedFieldId);
  if (label) {
    if (!chatOpen) toggleChatWindow();
    sendChatMessage('「' + label + '」にはどのような内容を記入すればよいですか？記入のコツも教えてください。');
  }
}
</script>
</body>
</html>
