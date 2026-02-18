<?php
// index.php - 大学入力フォーム
$id = $_GET['id'] ?? '';
if (!$id || !preg_match('/^[a-zA-Z0-9-]+$/', $id)) {
    // IDがない場合は管理者画面へのリンクを表示（または404）
    echo '<div style="text-align:center;padding:50px;font-family:sans-serif;"><h1>無効なURLです</h1><p>正しいURLにアクセスしてください。</p><p><a href="admin.php">管理者ログインはこちら</a></p></div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>産学連携リ・スキリング 申請書作成ツール</title>
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
      <button class="tab-btn active whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-blue-800" onclick="showTab('s11')">様式1-1<br><span class="font-normal">提出状</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s12')">様式1-2<br><span class="font-normal">基本情報</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s13')">様式1-3<br><span class="font-normal">実施委員会</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s2')">様式2<br><span class="font-normal">企画提案書</span></button>
      <button class="tab-btn whitespace-nowrap text-xs px-4 py-2 rounded-t border font-bold border-gray-300" onclick="showTab('s3')">様式3<br><span class="font-normal">申請経費</span></button>
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
      <input type="text" id="aiTheme" class="w-full border rounded px-3 py-2 text-sm mb-4" placeholder="例：地域医療を支えるVR看護教育">
      
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
            <table class="w-full text-sm border-collapse"><thead><tr class="bg-green-800 text-white"><th class="border px-2 py-1 w-72">プログラム名</th><th class="border px-2 py-1 w-24">対象者</th><th class="border px-2 py-1 w-14">定員</th><th class="border px-2 py-1 w-32">受講料（円）</th><th class="border px-2 py-1">目的・内容</th><th class="border px-2 py-1 w-8">削除</th></tr></thead><tbody id="programTbody"></tbody></table>
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
        <button onclick="showTab('s2')" class="bg-gray-400 text-white px-5 py-2 rounded font-bold text-sm hover:bg-gray-500">← 前へ</button>
        <button onclick="saveData(); showOutput();" class="bg-green-600 text-white px-5 py-2 rounded font-bold text-sm hover:bg-green-700">💾 保存して申請様式を出力 →</button>
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

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
  buildKeihiTable();
  buildCommitteeTable();
  buildProgramTable();
  loadData(); // サーバーからロード
  setupAutoSave();
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
  FIELD_IDS.forEach(id => { const el=document.getElementById(id); if(el) fields[id]=el.value; });
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

  const el6 = document.getElementById('s12_point');
  if(el6) updateCounter(el6,'counter6');
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
}

async function runAiGenerate() {
  const theme = document.getElementById('aiTheme').value.trim();
  const region = document.getElementById('aiRegion').value.trim();
  if(!theme) { alert('テーマを入力してください'); return; }

  const btn = document.getElementById('aiGenBtn');
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = '生成中... (約10秒)';

  try {
    const res = await fetch('ai_generate.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ theme, region, name: currentUniName })
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
    if(data.programs) { programs = data.programs; buildProgramTable(); }
    if(data.keihi) {
      Object.keys(data.keihi).forEach(k => {
        const row = data.keihi[k];
        const h=document.getElementById(`${k}_hojo`), f=document.getElementById(`${k}_futan`), n=document.getElementById(`${k}_naiyou`);
        if(h) h.value=row.hojo; if(f) f.value=row.futan; if(n) n.value=row.naiyou;
      });
      updateKeihiTotal();
    }
    // 文字カウンター更新
    const el6 = document.getElementById('s12_point');
    if(el6) updateCounter(el6,'counter6');

    document.getElementById('aiModal').classList.add('hidden');
    showToast('AIによる生成が完了しました ✨');
    saveData();
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
      ${data.committee.map((m,i)=>`<tr><td class="text-center">${i+1}</td><td>${m.name||''}</td><td>${m.shoku||''}</td><td>${m.yakuwari||''}</td></tr>`).join('')}
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
      <table class="shoshiki-table w-full"><tr><th style="width:30%">プログラム名</th><th style="width:14%">対象者</th><th style="width:7%">定員</th><th style="width:14%">受講料</th><th>目的・内容</th></tr>
      ${data.programs.map(p=>`<tr><td>${p.name||''}</td><td>${p.target||''}</td><td>${p.teiin||''}名</td><td>¥${p.ryokin||''}</td><td>${p.naiyou||''}</td></tr>`).join('')}
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

  // 様式3
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
// TOAST
// ================================================================
function showToast(msg) {
  const t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#166534;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.3);';
  document.body.appendChild(t);
  setTimeout(()=>t.remove(), 2200);
}
</script>
</body>
</html>
