<?php
// ============================================================
// register.php — 大学側自己登録ページ
// 招待コードを持つ大学担当者が、自身の入力フォームURLを発行する。
// ============================================================
require_once 'db_config.php';

$step   = 'form'; // 'form' | 'loading' | 'done' | 'error'
$errors = [];
$formUrl = '';
$uniName = '';
$aiError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name']            ?? '');
    $menu           = $_POST['menu']                 ?? '';
    $region         = trim($_POST['region']          ?? '');
    $theme          = trim($_POST['theme']           ?? '');
    $tantoshaName   = trim($_POST['tantosha_name']   ?? '');
    $tantoshaSosiki = trim($_POST['tantosha_sosiki'] ?? '');
    $email          = trim($_POST['email']           ?? '');
    $inviteInput    = trim($_POST['invite_code']     ?? '');

    // ---- バリデーション ----
    if (!$name)  $errors[] = '大学名を入力してください';
    if (!in_array($menu, ['menu1', 'menu2']))   $errors[] = 'メニューを選択してください';
    if (!$theme) $errors[] = '事業テーマを入力してください';
    if (!$tantoshaName) $errors[] = '担当者名を入力してください';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'メールアドレスを正しく入力してください';
    }
    if ($inviteInput !== $INVITE_CODE) {
        $errors[] = '招待コードが正しくありません';
    }

    if (empty($errors)) {
        // ---- AI生成 ----
        $prompt = ($menu === 'menu1')
            ? buildGeminiPrompt($name, $region, $theme)
            : buildGeminiPromptMenu2($name, $region, $theme);

        $aiResult = callGeminiApi($prompt);

        if (isset($aiResult['error'])) {
            $aiError = $aiResult['error'];
            $step = 'error';
        } else {
            $fields   = $aiResult['fields']    ?? [];
            $programs  = $aiResult['programs']  ?? [];
            $programs2 = $aiResult['programs2'] ?? [];
            $keihi     = $aiResult['keihi']     ?? [];

            // ---- 登録フォームの入力値をフィールドにマージ ----
            if ($menu === 'menu1') {
                $fields['s11_daigakuname']      = $name;
                $fields['s12_jisshisyutai']     = $name;
                $fields['s12_daigaku_name']     = $name;
                $fields['s12_sekininsha_name']  = $tantoshaName;   // ４. 事業責任者
                $fields['s12_tanto_name']       = $tantoshaName;   // 担当者
                $fields['s12_tanto_mail1']      = $email;
                if ($tantoshaSosiki) {
                    $fields['s12_sekininsha_shoku'] = $tantoshaSosiki;
                    $fields['s12_tanto_shoku']      = $tantoshaSosiki;
                }
            } else {
                $fields['s21_daigakuname']  = $name;
                $fields['s22_jisshisyutai'] = $name;
                $fields['s22_daigaku_name'] = $name;
                $fields['s22_sekinin_name'] = $tantoshaName;       // ４. 事業責任者
                $fields['s22_tanto_name']   = $tantoshaName;       // 担当者
                $fields['s22_tanto_mail1']  = $email;
                if ($tantoshaSosiki) {
                    $fields['s22_sekinin_shoku'] = $tantoshaSosiki;
                    $fields['s22_tanto_busyo']   = $tantoshaSosiki;
                }
            }

            // ---- DB保存 ----
            // キー名は index.php / api.php が期待する形式に合わせる
            $id = bin2hex(random_bytes(16));
            $dataJson = json_encode([
                '_uni'            => $name,     // index.php: data._uni
                '_menu'           => $menu,     // index.php: $uniData['_menu']
                '_theme'          => $theme,    // index.php: data._theme
                '_region'         => $region,   // index.php: data._region
                'tantosha_name'   => $tantoshaName,
                'tantosha_sosiki' => $tantoshaSosiki,
                'tantosha_email'  => $email,
                'fields'          => $fields,
                'programs'        => $programs,
                'programs2'       => $programs2,
                'keihi'           => $keihi,
            ], JSON_UNESCAPED_UNICODE);

            $stmt = $pdo->prepare(
                'INSERT INTO universities (id, name, data, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$id, $name, $dataJson]);

            // ---- URL生成 ----
            $formUrl = 'https://form.jollygoodplus.com/reskiling2603/?id=' . $id;
            $uniName = $name;

            // ---- 通知メール送信 ----
            $menuLabel = ($menu === 'menu1') ? 'メニュー①地方創生' : 'メニュー②産業成長';
            $subject = '【koboform】新規登録: ' . $name;
            $body  = "koboformに新規大学登録がありました。\n\n";
            $body .= "大学名: {$name}\n";
            $body .= "メニュー: {$menuLabel}\n";
            if ($region) $body .= "地域: {$region}\n";
            $body .= "事業テーマ: {$theme}\n";
            $body .= "担当者名: {$tantoshaName}\n";
            if ($tantoshaSosiki) $body .= "担当者所属: {$tantoshaSosiki}\n";
            $body .= "担当者メール: {$email}\n\n";
            $body .= "発行フォームURL:\n{$formUrl}\n";
            $headers = implode("\r\n", [
                'From: noreply@form.jollygoodplus.com',
                'Content-Type: text/plain; charset=UTF-8',
            ]);
            if (!empty($NOTIFY_EMAIL)) {
                mail($NOTIFY_EMAIL, $subject, $body, $headers);
            }

            // ---- Slack 通知 ----
            if (!empty($SLACK_WEBHOOK)) {
                $regionText = $region ? "\n地域: {$region}" : '';
                $sosikiText = $tantoshaSosiki ? "（{$tantoshaSosiki}）" : '';
                $slackText  = "🎓 *新規大学登録がありました*\n"
                            . "大学名: {$name}\n"
                            . "メニュー: {$menuLabel}{$regionText}\n"
                            . "事業テーマ: {$theme}\n"
                            . "担当者: {$tantoshaName}{$sosikiText}（{$email}）\n"
                            . "フォームURL: {$formUrl}";
                $ch = curl_init($SLACK_WEBHOOK);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $slackText]));
                curl_exec($ch);
                curl_close($ch);
            }

            $step = 'done';
        }
    }
}

// ---- POSTデータを保持（エラー時に再表示）。GETパラメータもフォールバック ----
$v = fn(string $k) => htmlspecialchars($_POST[$k] ?? $_GET[$k] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>申請自動生成ツール — 登録</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Hiragino Kaku Gothic ProN', 'Meiryo', sans-serif; }
  .gradient-header { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
  .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.08); }
  label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
  input[type=text], input[type=email], input[type=password], textarea, select {
    width: 100%; padding: 10px 14px; border: 1.5px solid #d1d5db; border-radius: 8px;
    font-size: 0.95rem; color: #111827; background: #f9fafb;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  input:focus, textarea:focus, select:focus {
    border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); background: #fff;
  }
  .req { color: #dc2626; margin-left: 3px; }
  .btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: #2563eb; color: #fff; font-weight: 700; font-size: 1rem;
    padding: 12px 32px; border-radius: 8px; border: none; cursor: pointer;
    transition: background .2s, transform .1s;
  }
  .btn-primary:hover { background: #1d4ed8; }
  .btn-primary:active { transform: scale(.98); }
  .btn-primary:disabled { background: #93c5fd; cursor: not-allowed; }
  .error-box { background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 8px; padding: 12px 16px; }
  .success-icon { font-size: 4rem; }
  #loadingOverlay {
    position: fixed; inset: 0; background: rgba(255,255,255,.85);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    z-index: 50; backdrop-filter: blur(4px);
  }
  .spinner {
    width: 56px; height: 56px; border: 5px solid #dbeafe;
    border-top-color: #2563eb; border-radius: 50%;
    animation: spin .8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* AI Chat Widget */
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
  @media (max-width: 640px) {
    #chatWindow { width:100%!important; height:85vh!important; bottom:0!important; right:0!important; border-radius:16px 16px 0 0!important; }
    #chatToggleBtn { bottom:16px!important; right:16px!important; }
  }
</style>
</head>
<body class="min-h-screen bg-slate-50">

<!-- ヘッダー -->
<header class="gradient-header text-white py-6 px-4 shadow-md">
  <div class="max-w-2xl mx-auto">
    <div class="text-xs font-semibold tracking-widest opacity-70 mb-1">文部科学省 産学連携リ・スキリング・エコシステム構築事業</div>
    <h1 class="text-xl font-bold">申請自動生成ツール — 登録</h1>
    <p class="text-sm opacity-80 mt-1">大学専用の申請書入力フォームを発行します</p>
  </div>
</header>
<?php $currentPage = 'register'; $navWidth = 'max-w-2xl'; include 'nav.php'; ?>

<main class="max-w-2xl mx-auto px-4 py-8">

<?php if ($step === 'form'): ?>
<!-- ============ フォーム画面 ============ -->

<?php if (!empty($errors)): ?>
<div class="error-box mb-6">
  <p class="font-bold text-red-700 mb-2">入力内容にエラーがあります</p>
  <ul class="list-disc list-inside text-red-600 text-sm space-y-1">
    <?php foreach ($errors as $e): ?>
    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="card p-6 md:p-8">
  <p class="text-sm text-gray-600 mb-6 leading-relaxed">
    以下のフォームに入力して「フォームを発行する」ボタンを押すと、AIが申請書の初期文案を自動生成し、
    大学専用の入力フォームURLが発行されます（30秒〜1分かかります）。
  </p>

  <form method="POST" action="" id="registerForm" onsubmit="showLoading()">
    <div class="space-y-5">

      <div>
        <label>大学名<span class="req">*</span></label>
        <input type="text" name="name" value="<?= $v('name') ?>"
               placeholder="例: ○○大学" required>
      </div>

      <div>
        <label>申請メニュー<span class="req">*</span></label>
        <select name="menu" required>
          <option value="">-- 選択してください --</option>
          <option value="menu1" <?= ($v('menu') === 'menu1') ? 'selected' : '' ?>>メニュー① 地方創生</option>
          <option value="menu2" <?= ($v('menu') === 'menu2') ? 'selected' : '' ?>>メニュー② 産業成長</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">メニュー①は地域の社会課題解決型、メニュー②は産業人材育成型です</p>
      </div>

      <div>
        <label>地域<span class="text-gray-400 font-normal ml-1">（任意）</span></label>
        <input type="text" name="region" value="<?= $v('region') ?>"
               placeholder="例: 北海道、東北、九州など">
        <p class="text-xs text-gray-500 mt-1">メニュー①の場合は地域名を入力するとより具体的な文案が生成されます</p>
      </div>

      <div>
        <label>事業テーマ<span class="req">*</span></label>
        <input type="text" name="theme" value="<?= $v('theme') ?>"
               placeholder="例: 医療・介護系人材のVRリスキリング">
        <p class="text-xs text-gray-500 mt-1">AIが文案を生成するための重要な情報です。具体的に入力してください</p>
      </div>

      <hr class="border-gray-200">

      <div>
        <label>担当者名<span class="req">*</span></label>
        <input type="text" name="tantosha_name" value="<?= $v('tantosha_name') ?>"
               placeholder="例: 山田 太郎">
      </div>

      <div>
        <label>担当者所属<span class="text-gray-400 font-normal ml-1">（任意）</span></label>
        <input type="text" name="tantosha_sosiki" value="<?= $v('tantosha_sosiki') ?>"
               placeholder="例: 学術研究推進部">
      </div>

      <div>
        <label>メールアドレス<span class="req">*</span></label>
        <input type="email" name="email" value="<?= $v('email') ?>"
               placeholder="例: yamada@univ.ac.jp">
        <p class="text-xs text-gray-500 mt-1">発行したURLをメモしてください（このアドレスへの自動送信はありません）</p>
      </div>

      <hr class="border-gray-200">

      <?php $prefilledCode = $_GET['invite_code'] ?? ''; ?>
      <div <?= $prefilledCode ? 'style="display:none"' : '' ?>>
        <label>招待コード<span class="req">*</span></label>
        <input type="password" name="invite_code" value="<?= htmlspecialchars($prefilledCode, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="担当者から受け取ったコードを入力" autocomplete="off">
        <p class="text-xs text-gray-500 mt-1">コードをお持ちでない場合はジョリーグッド担当者にお問い合わせください</p>
      </div>

    </div>

    <div class="mt-8 text-center">
      <button type="submit" class="btn-primary" id="submitBtn">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        フォームを発行する
      </button>
      <p class="text-xs text-gray-400 mt-3">AIによる初期文案の生成に30秒〜1分程度かかります</p>
    </div>
  </form>
</div>

<!-- ローディングオーバーレイ -->
<div id="loadingOverlay" style="display:none;">
  <div class="spinner mb-6"></div>
  <p class="text-lg font-bold text-gray-700 mb-2">AIが申請書の文案を生成中です</p>
  <p class="text-sm text-gray-500">30秒〜1分程度お待ちください...</p>
</div>

<?php elseif ($step === 'done'): ?>
<!-- ============ 完了画面 ============ -->
<div class="card p-8 text-center">
  <div class="success-icon mb-4">🎉</div>
  <h2 class="text-2xl font-bold text-gray-800 mb-2">フォームを発行しました！</h2>
  <p class="text-gray-600 mb-6">
    <span class="font-bold"><?= htmlspecialchars($uniName, ENT_QUOTES, 'UTF-8') ?></span>
    の申請書入力フォームを発行しました。<br>
    以下のURLをブックマークして、申請書の入力を進めてください。
  </p>

  <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-5 mb-6">
    <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-2">あなた専用の入力フォームURL</p>
    <a href="<?= htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8') ?>"
       target="_blank"
       class="text-blue-700 font-bold text-base break-all hover:underline">
      <?= htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8') ?>
    </a>
    <div class="mt-3">
      <button onclick="copyUrl()"
              class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        URLをコピー
      </button>
      <span id="copyMsg" class="text-green-600 text-sm font-bold ml-2 hidden">コピーしました！</span>
    </div>
  </div>

  <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-left text-sm text-amber-800 mb-6">
    <p class="font-bold mb-1">⚠️ このURLを必ず保存してください</p>
    <ul class="list-disc list-inside space-y-1 text-xs">
      <li>このページを閉じると、URLを再表示することができません</li>
      <li>入力内容は自動保存されます（URLがあれば後から再開できます）</li>
      <li>URLを紛失した場合はジョリーグッド担当者にお問い合わせください</li>
    </ul>
  </div>

  <a href="<?= htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8') ?>"
     target="_blank"
     class="btn-primary inline-flex mx-auto">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
    </svg>
    フォームを開く
  </a>
  <p class="text-xs text-gray-400 mt-4">AIが生成した初期文案が入力済みの状態で開きます</p>
</div>

<?php elseif ($step === 'error'): ?>
<!-- ============ エラー画面 ============ -->
<div class="card p-8 text-center">
  <div class="text-5xl mb-4">😢</div>
  <h2 class="text-xl font-bold text-gray-800 mb-3">AI生成中にエラーが発生しました</h2>
  <div class="error-box text-left mb-6">
    <p class="text-red-700 text-sm"><?= htmlspecialchars($aiError, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <p class="text-sm text-gray-600 mb-6">
    しばらく時間をおいてから再度お試しください。<br>
    問題が続く場合はジョリーグッド担当者にご連絡ください。
  </p>
  <a href="register.php" class="btn-primary inline-flex mx-auto">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </svg>
    もう一度試す
  </a>
</div>
<?php endif; ?>

</main>

<?php if ($step === 'form'): ?>
<!-- AI Chat Widget -->
<div id="chatWindow" class="chat-hidden fixed z-[9997] bg-white shadow-2xl flex flex-col"
     style="width:400px; height:520px; bottom:108px; right:24px; border-radius:16px; overflow:hidden;">
  <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
    <div class="flex items-center gap-2">
      <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 bg-amber-50" style="border:2px solid rgba(255,255,255,0.4);">
        <img src="mascot.gif" alt="" style="width:220%; max-width:none; margin-left:-60%; margin-top:-55%;">
      </div>
      <div>
        <p class="text-sm font-bold leading-tight">ぐうた - AI</p>
        <p class="text-xs text-indigo-200 leading-tight">申請についてお気軽にどうぞ</p>
      </div>
    </div>
    <button onclick="toggleChatWindow()" class="text-white hover:text-indigo-200 transition-colors p-1">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <div id="chatMessages" class="flex-1 overflow-y-auto p-4 bg-gray-50" style="scroll-behavior:smooth;"></div>
  <div id="chatSuggestions" class="hidden px-3 py-2 bg-white border-t flex gap-2 overflow-x-auto flex-shrink-0"></div>
  <div class="bg-white border-t px-3 py-2 flex-shrink-0">
    <div class="flex items-center gap-2">
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

<button id="chatToggleBtn" onclick="toggleChatWindow()"
        class="fixed bottom-4 right-4 z-[9998] w-20 h-20
               rounded-full shadow-lg flex items-center justify-center
               transition-all duration-300 hover:scale-110 bg-white border-2 border-amber-300"
        title="ぐうた - AI に質問する"
        style="padding:3px;">
  <img id="chatIconOpen" src="mascot.gif" alt="ぐうた - AI" class="w-[72px] h-[72px] rounded-full object-cover object-top">
  <svg id="chatIconClose" class="w-7 h-7 hidden text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
  </svg>
  <span id="chatBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold
        w-5 h-5 rounded-full flex items-center justify-center shadow">?</span>
</button>
<?php endif; ?>

<?php include 'footer.php'; ?>

<script>
function showLoading() {
  document.getElementById('loadingOverlay').style.display = 'flex';
  document.getElementById('submitBtn').disabled = true;
}

function copyUrl() {
  const url = <?= json_encode($formUrl) ?>;
  navigator.clipboard.writeText(url).then(() => {
    const msg = document.getElementById('copyMsg');
    msg.classList.remove('hidden');
    setTimeout(() => msg.classList.add('hidden'), 3000);
  });
}

// ================================================================
// AI CHATBOT
// ================================================================
let chatHistory = [];
let chatOpen = false;

function toggleChatWindow() {
  const win = document.getElementById('chatWindow');
  if (!win) return;
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
    setTimeout(() => document.getElementById('chatInput').focus(), 300);
  } else {
    win.classList.remove('chat-visible');
    win.classList.add('chat-hidden');
    iconOpen.classList.remove('hidden');
    iconClose.classList.add('hidden');
  }
}

function showWelcomeMessage() {
  const welcome = 'こんにちは！ぐうたです。\n\n登録フォームの入力でお困りのことがあれば、お気軽にご質問ください。\n\n例えば：\n・「メニュー①と②の違いは？」\n・「事業テーマは何を書けばいい？」\n・「採択されやすいポイントは？」';
  appendMessage('assistant', welcome);
  showChatSuggestions(['メニュー①と②の違いは？', '事業テーマの書き方を教えて', '採択されやすいポイントは？']);
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
    const payload = {
      university_id: '_register',
      message: message,
      conversation_history: chatHistory.slice(-10),
      current_context: {
        page: 'register',
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
</script>

</body>
</html>
