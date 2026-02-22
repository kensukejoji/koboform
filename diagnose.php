<?php
require_once 'db_config.php';
$INVITE_CODE = $INVITE_CODE ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>申請メニュー診断チャート｜リスキリング・エコシステム構築事業</title>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { font-family: 'Hiragino Kaku Gothic ProN', 'Meiryo', sans-serif; }
  .gradient-header { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
  .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }

  /* Step transitions */
  .step { display: none; opacity: 0; transform: translateY(20px); }
  .step.active { display: block; animation: stepIn .4s ease forwards; }
  .step.exit { display: block; animation: stepOut .25s ease forwards; }
  @keyframes stepIn { to { opacity: 1; transform: translateY(0); } }
  @keyframes stepOut { to { opacity: 0; transform: translateY(-20px); } }

  /* Choice cards */
  .choice-card {
    border: 2.5px solid #e5e7eb; border-radius: 14px; padding: 20px 24px;
    cursor: pointer; transition: all .2s ease; background: #fff;
  }
  .choice-card:hover { border-color: #93c5fd; background: #eff6ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,.1); }
  .choice-card:active { transform: scale(.98); }
  .choice-card.selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }

  /* Mascot speech bubble */
  .speech-bubble {
    position: relative; background: #f0f9ff; border: 2px solid #bae6fd;
    border-radius: 16px; padding: 16px 20px;
  }
  .speech-bubble::after {
    content: ''; position: absolute; bottom: -12px; left: 48px;
    border-width: 12px 10px 0 10px;
    border-style: solid; border-color: #bae6fd transparent transparent transparent;
  }
  .speech-bubble::before {
    content: ''; position: absolute; bottom: -9px; left: 50px;
    border-width: 10px 8px 0 8px;
    border-style: solid; border-color: #f0f9ff transparent transparent transparent;
    z-index: 1;
  }

  /* Progress bar */
  .progress-fill { transition: width .4s ease; }

  /* Result bar */
  .result-bar { height: 28px; border-radius: 999px; transition: width .8s ease; }

  /* Confetti effect for result */
  @keyframes confetti { 0% { opacity:1; transform:translateY(0) rotate(0); } 100% { opacity:0; transform:translateY(300px) rotate(720deg); } }
  .confetti-piece {
    position: fixed; width: 10px; height: 10px; border-radius: 2px;
    animation: confetti 2.5s ease-out forwards; pointer-events: none; z-index: 100;
  }

  /* Mascot face crop */
  .mascot-wrap {
    width: 64px; height: 64px; border-radius: 50%; overflow: hidden;
    border: 3px solid #fbbf24; background: #fffbeb; flex-shrink: 0;
  }
  .mascot-wrap img { width: 220%; max-width: none; margin-left: -60%; margin-top: -55%; }
  .mascot-wrap-lg {
    width: 120px; height: 120px; border-radius: 50%; overflow: hidden;
    border: 4px solid #fbbf24; background: #fffbeb; margin: 0 auto;
  }
  .mascot-wrap-lg img { width: 100%; }
</style>
</head>
<body class="min-h-screen bg-slate-50">

<!-- Header -->
<header class="gradient-header text-white py-5 px-4 shadow-md">
  <div class="max-w-2xl mx-auto">
    <div class="text-xs font-semibold tracking-widest opacity-70 mb-1">文部科学省 産学連携リ・スキリング・エコシステム構築事業</div>
    <h1 class="text-lg font-bold">申請メニュー診断チャート</h1>
  </div>
</header>
<?php $currentPage = 'diagnose'; $navWidth = 'max-w-2xl'; include 'nav.php'; ?>

<main class="max-w-2xl mx-auto px-4 py-8">

<!-- ========== STEP 0: Welcome ========== -->
<div class="step active" id="step0">
  <div class="card p-8 text-center">
    <div class="mascot-wrap-lg mb-6">
      <img src="mascot.gif" alt="アシスタント">
    </div>
    <h2 class="text-2xl font-bold text-gray-800 mb-3">
      あなたの大学に最適な<br>申請メニューを診断!
    </h2>
    <p class="text-gray-600 text-sm mb-2 leading-relaxed">
      「メニュー① 地方創生」と「メニュー② 産業成長」<br>
      どちらで申請すべきか、5つの質問で診断します。
    </p>
    <p class="text-xs text-gray-400 mb-8">所要時間: 約1分</p>
    <button onclick="goToStep(1)" class="inline-flex items-center gap-2 bg-blue-600 text-white font-bold text-lg px-8 py-4 rounded-xl hover:bg-blue-700 transition shadow-lg hover:shadow-xl active:scale-[.98]">
      診断スタート
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
    </button>
  </div>
</div>

<!-- ========== STEP 1-5: Questions ========== -->
<div class="step" id="step1">
  <div class="mb-4" id="progressWrap1">
    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>質問 1 / 5</span><span id="pct1">20%</span></div>
    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="progress-fill bg-blue-500 h-2.5 rounded-full" style="width:20%"></div></div>
  </div>
  <div class="card p-6 md:p-8">
    <div class="flex items-start gap-4 mb-6">
      <div class="mascot-wrap"><img src="mascot.gif" alt=""></div>
      <div class="speech-bubble flex-1">
        <p class="font-bold text-gray-800 text-base">今、声をかけやすい（または既に繋がっている）パートナーはどちらですか？</p>
        <p class="text-xs text-blue-600 mt-1 font-semibold">最も重要な質問です!</p>
      </div>
    </div>
    <div class="space-y-3">
      <div class="choice-card" onclick="selectAnswer(1, 'menu1', 3, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🏛️</span>
          <div>
            <p class="font-bold text-gray-800">自治体・地域金融機関</p>
            <p class="text-sm text-gray-500 mt-0.5">県庁、市役所、地元の銀行、商工会議所、農協・漁協など</p>
          </div>
        </div>
      </div>
      <div class="choice-card" onclick="selectAnswer(1, 'menu2', 3, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🏢</span>
          <div>
            <p class="font-bold text-gray-800">業界団体・専門企業</p>
            <p class="text-sm text-gray-500 mt-0.5">業界協会、関連企業、病院グループ、メーカー、専門法人など</p>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6 flex justify-between">
      <button onclick="goToStep(0)" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        戻る
      </button>
      <span></span>
    </div>
  </div>
</div>

<div class="step" id="step2">
  <div class="mb-4">
    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>質問 2 / 5</span><span>40%</span></div>
    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="progress-fill bg-blue-500 h-2.5 rounded-full" style="width:40%"></div></div>
  </div>
  <div class="card p-6 md:p-8">
    <div class="flex items-start gap-4 mb-6">
      <div class="mascot-wrap"><img src="mascot.gif" alt=""></div>
      <div class="speech-bubble flex-1">
        <p class="font-bold text-gray-800 text-base">貴学が力を入れたい教育テーマは？</p>
      </div>
    </div>
    <div class="space-y-3">
      <div class="choice-card" onclick="selectAnswer(2, 'menu1', 2, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🌾</span>
          <div>
            <p class="font-bold text-gray-800">地域の課題を解決する</p>
            <p class="text-sm text-gray-500 mt-0.5">地域の人材不足、過疎化対策、地域産業の維持・活性化など</p>
          </div>
        </div>
      </div>
      <div class="choice-card" onclick="selectAnswer(2, 'menu2', 2, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🔬</span>
          <div>
            <p class="font-bold text-gray-800">高度専門技術を極める</p>
            <p class="text-sm text-gray-500 mt-0.5">最先端技術の習得、専門資格取得、高度スキルの全国展開など</p>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6"><button onclick="goToStep(1)" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>前の質問</button></div>
  </div>
</div>

<div class="step" id="step3">
  <div class="mb-4">
    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>質問 3 / 5</span><span>60%</span></div>
    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="progress-fill bg-blue-500 h-2.5 rounded-full" style="width:60%"></div></div>
  </div>
  <div class="card p-6 md:p-8">
    <div class="flex items-start gap-4 mb-6">
      <div class="mascot-wrap"><img src="mascot.gif" alt=""></div>
      <div class="speech-bubble flex-1">
        <p class="font-bold text-gray-800 text-base">育成したいのはどんな人材ですか？</p>
      </div>
    </div>
    <div class="space-y-3">
      <div class="choice-card" onclick="selectAnswer(3, 'menu1', 2, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">👥</span>
          <div>
            <p class="font-bold text-gray-800">地域の幅広い現場人材</p>
            <p class="text-sm text-gray-500 mt-0.5">地元の現場スタッフ、地域の実務者、中小企業の従業員など</p>
          </div>
        </div>
      </div>
      <div class="choice-card" onclick="selectAnswer(3, 'menu2', 2, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🎓</span>
          <div>
            <p class="font-bold text-gray-800">特定の高度専門職</p>
            <p class="text-sm text-gray-500 mt-0.5">専門資格を目指す人材、高度技術者、特定分野のスペシャリストなど</p>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6"><button onclick="goToStep(2)" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>前の質問</button></div>
  </div>
</div>

<div class="step" id="step4">
  <div class="mb-4">
    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>質問 4 / 5</span><span>80%</span></div>
    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="progress-fill bg-blue-500 h-2.5 rounded-full" style="width:80%"></div></div>
  </div>
  <div class="card p-6 md:p-8">
    <div class="flex items-start gap-4 mb-6">
      <div class="mascot-wrap"><img src="mascot.gif" alt=""></div>
      <div class="speech-bubble flex-1">
        <p class="font-bold text-gray-800 text-base">目指すゴールに近いのはどちら？</p>
      </div>
    </div>
    <div class="space-y-3">
      <div class="choice-card" onclick="selectAnswer(4, 'menu1', 2, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🏡</span>
          <div>
            <p class="font-bold text-gray-800">地域に人が残る・定着する</p>
            <p class="text-sm text-gray-500 mt-0.5">地域のインフラや産業を維持し、人材流出を防ぐ</p>
          </div>
        </div>
      </div>
      <div class="choice-card" onclick="selectAnswer(4, 'menu2', 2, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">📈</span>
          <div>
            <p class="font-bold text-gray-800">個人のキャリア・業界レベルが上がる</p>
            <p class="text-sm text-gray-500 mt-0.5">受講者の年収・キャリアアップ、業界全体の技術底上げ</p>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6"><button onclick="goToStep(3)" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>前の質問</button></div>
  </div>
</div>

<div class="step" id="step5">
  <div class="mb-4">
    <div class="flex justify-between text-xs text-gray-500 mb-1"><span>質問 5 / 5</span><span>100%</span></div>
    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="progress-fill bg-blue-500 h-2.5 rounded-full" style="width:100%"></div></div>
  </div>
  <div class="card p-6 md:p-8">
    <div class="flex items-start gap-4 mb-6">
      <div class="mascot-wrap"><img src="mascot.gif" alt=""></div>
      <div class="speech-bubble flex-1">
        <p class="font-bold text-gray-800 text-base">貴学はどちらのタイプに近いですか？</p>
        <p class="text-xs text-gray-500 mt-1">最後の質問です!</p>
      </div>
    </div>
    <div class="space-y-3">
      <div class="choice-card" onclick="selectAnswer(5, 'menu1', 1, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🗾</span>
          <div>
            <p class="font-bold text-gray-800">地方の国立・公立大学、地域密着の大学・専門学校</p>
            <p class="text-sm text-gray-500 mt-0.5">地域で唯一の専門教育機関、自治体とのつながりが強い</p>
          </div>
        </div>
      </div>
      <div class="choice-card" onclick="selectAnswer(5, 'menu2', 1, this)">
        <div class="flex items-center gap-3">
          <span class="text-2xl">🏙️</span>
          <div>
            <p class="font-bold text-gray-800">都市部の私立大学、専門特化した大学・大学院</p>
            <p class="text-sm text-gray-500 mt-0.5">業界とのネットワークが強く、高度な専門教育に強みがある</p>
          </div>
        </div>
      </div>
    </div>
    <div class="mt-6"><button onclick="goToStep(4)" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>前の質問</button></div>
  </div>
</div>

<!-- ========== STEP 6: Result ========== -->
<div class="step" id="step6">
  <div class="card p-6 md:p-8">
    <div class="text-center mb-6">
      <div class="mascot-wrap-lg mb-4">
        <img src="mascot.gif" alt="アシスタント">
      </div>
      <h2 class="text-2xl font-bold text-gray-800 mb-2">診断結果</h2>
    </div>

    <!-- Recommended menu -->
    <div id="resultBadge" class="text-center mb-6">
      <!-- Filled by JS -->
    </div>

    <!-- Score bars -->
    <div class="bg-gray-50 rounded-xl p-5 mb-6">
      <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">診断スコア</p>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1">
          <span class="font-bold text-blue-700">メニュー① 地方創生</span>
          <span id="score1Label" class="font-bold text-blue-700">0</span>
        </div>
        <div class="w-full bg-blue-100 rounded-full h-7 overflow-hidden">
          <div id="score1Bar" class="result-bar bg-gradient-to-r from-blue-400 to-blue-600" style="width:0%"></div>
        </div>
      </div>
      <div>
        <div class="flex justify-between text-sm mb-1">
          <span class="font-bold text-orange-600">メニュー② 産業成長</span>
          <span id="score2Label" class="font-bold text-orange-600">0</span>
        </div>
        <div class="w-full bg-orange-100 rounded-full h-7 overflow-hidden">
          <div id="score2Bar" class="result-bar bg-gradient-to-r from-orange-400 to-orange-500" style="width:0%"></div>
        </div>
      </div>
    </div>

    <!-- Theme examples -->
    <div id="themeExamples" class="mb-6">
      <!-- Filled by JS -->
    </div>

    <!-- Reason -->
    <div id="resultReason" class="mb-6">
      <!-- Filled by JS -->
    </div>

    <!-- CTA -->
    <div class="text-center mb-6">
      <a id="registerLink" href="register.php?menu=menu1"
         class="inline-flex items-center gap-2 bg-blue-600 text-white font-bold text-lg px-8 py-4 rounded-xl hover:bg-blue-700 transition shadow-lg hover:shadow-xl active:scale-[.98]">
        この結果で申請登録へ進む
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
      </a>
      <div class="mt-3">
        <button onclick="resetDiagnosis()" class="text-sm text-gray-400 hover:text-gray-600 underline">もう一度診断する</button>
      </div>
    </div>

    <!-- Reassurance message -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm">
      <p class="font-bold text-amber-800 mb-2">安心ポイント</p>
      <p class="text-amber-700 leading-relaxed">
        どちらのメニューでも、やるべきことは<strong>「VRコンテンツ制作」</strong>と<strong>「プラットフォーム構築」</strong>で同じです。
        違うのは<strong>「申請書の書き方（ストーリー）」</strong>だけ。<br>
        貴学のリソースをヒアリングさせていただければ、弊社が最も採択確率が高いストーリーを組み立てて申請書を作成します。
      </p>
    </div>
  </div>
</div>

</main>

<?php include 'footer.php'; ?>

<script>
// State
let currentStep = 0;
const scores = { menu1: 0, menu2: 0 };
const answers = {};

function goToStep(n) {
  const cur = document.getElementById('step' + currentStep);
  const next = document.getElementById('step' + n);
  if (!cur || !next) return;

  cur.classList.remove('active');
  cur.classList.add('exit');
  setTimeout(() => {
    cur.classList.remove('exit');
    next.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, 250);

  currentStep = n;
}

function selectAnswer(qNum, menu, points, el) {
  // Remove previous selection for this question
  if (answers[qNum]) {
    scores[answers[qNum].menu] -= answers[qNum].points;
  }

  // Record new answer
  answers[qNum] = { menu, points };
  scores[menu] += points;

  // Visual feedback
  const cards = el.parentElement.querySelectorAll('.choice-card');
  cards.forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');

  // Auto-advance after brief delay
  setTimeout(() => {
    if (qNum < 5) {
      goToStep(qNum + 1);
    } else {
      showResult();
    }
  }, 400);
}

function showResult() {
  const s1 = scores.menu1;
  const s2 = scores.menu2;
  const maxScore = 10; // 3+2+2+2+1
  const winner = s1 >= s2 ? 'menu1' : 'menu2';

  // Badge
  const badge = document.getElementById('resultBadge');
  if (winner === 'menu1') {
    badge.innerHTML = '<div class="inline-block bg-blue-100 border-2 border-blue-300 rounded-2xl px-8 py-4"><p class="text-xs font-bold text-blue-500 mb-1">おすすめ</p><p class="text-2xl font-bold text-blue-800">メニュー① 地方創生</p></div>';
  } else {
    badge.innerHTML = '<div class="inline-block bg-orange-100 border-2 border-orange-300 rounded-2xl px-8 py-4"><p class="text-xs font-bold text-orange-500 mb-1">おすすめ</p><p class="text-2xl font-bold text-orange-700">メニュー② 産業成長</p></div>';
  }

  // Score bars (animate after display)
  document.getElementById('score1Label').textContent = s1 + ' / ' + maxScore + ' pt';
  document.getElementById('score2Label').textContent = s2 + ' / ' + maxScore + ' pt';
  setTimeout(() => {
    document.getElementById('score1Bar').style.width = (s1 / maxScore * 100) + '%';
    document.getElementById('score2Bar').style.width = (s2 / maxScore * 100) + '%';
  }, 300);

  // Theme examples
  const themes = document.getElementById('themeExamples');
  if (winner === 'menu1') {
    themes.innerHTML = '<div class="bg-blue-50 rounded-xl p-5"><p class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2">おすすめテーマ例</p><ul class="space-y-2 text-sm text-blue-800"><li class="flex items-start gap-2"><span>💡</span><span>地域の産業人材を守るVR教育プログラム</span></li><li class="flex items-start gap-2"><span>💡</span><span>過疎地域の人材不足をデジタル（VR）で解決する遠隔教育</span></li><li class="flex items-start gap-2"><span>💡</span><span>自治体連携型・地域リスキリング教育プラットフォーム構築</span></li></ul></div>';
  } else {
    themes.innerHTML = '<div class="bg-orange-50 rounded-xl p-5"><p class="text-xs font-bold text-orange-600 uppercase tracking-wider mb-2">おすすめテーマ例</p><ul class="space-y-2 text-sm text-orange-800"><li class="flex items-start gap-2"><span>💡</span><span>高度専門技術のVRコンテンツ化と全国展開</span></li><li class="flex items-start gap-2"><span>💡</span><span>業界トップレベルの実技スキルをVRトレーニング化</span></li><li class="flex items-start gap-2"><span>💡</span><span>専門人材育成のための没入型VR教育プラットフォーム構築</span></li></ul></div>';
  }

  // Reason
  const reason = document.getElementById('resultReason');
  if (winner === 'menu1') {
    reason.innerHTML = '<div class="border border-gray-200 rounded-xl p-5"><p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">推奨理由</p><p class="text-sm text-gray-700 leading-relaxed">地方の課題（過疎化・人材不足・高齢化）を解決するストーリーは、審査員に最も響きます。地域の中核教育機関としての立場を活かし、<strong>自治体と連携した地域人材のVR育成</strong>というロジックで申請することで、高い採択率が期待できます。</p></div>';
  } else {
    reason.innerHTML = '<div class="border border-gray-200 rounded-xl p-5"><p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">推奨理由</p><p class="text-sm text-gray-700 leading-relaxed">業界団体や専門企業とのネットワークを活かし、<strong>「大学にしかない高度技術をVR化して全国に展開する」</strong>というビジネスモデルが採択されやすいです。高単価なVRコンテンツを制作し、教育と収益を両立するストーリーが審査員に評価されます。</p></div>';
  }

  // Register link
  document.getElementById('registerLink').href = 'register.php?menu=' + winner + '&invite_code=<?= urlencode($INVITE_CODE) ?>';

  goToStep(6);
  setTimeout(launchConfetti, 500);
}

function launchConfetti() {
  const colors = ['#2563eb', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4'];
  for (let i = 0; i < 40; i++) {
    const el = document.createElement('div');
    el.className = 'confetti-piece';
    el.style.left = Math.random() * 100 + 'vw';
    el.style.top = '-10px';
    el.style.background = colors[Math.floor(Math.random() * colors.length)];
    el.style.animationDelay = (Math.random() * 0.8) + 's';
    el.style.animationDuration = (1.5 + Math.random() * 1.5) + 's';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }
}

function resetDiagnosis() {
  scores.menu1 = 0;
  scores.menu2 = 0;
  Object.keys(answers).forEach(k => delete answers[k]);
  // Clear all selections
  document.querySelectorAll('.choice-card.selected').forEach(c => c.classList.remove('selected'));
  // Reset score bars
  document.getElementById('score1Bar').style.width = '0%';
  document.getElementById('score2Bar').style.width = '0%';
  goToStep(0);
}
</script>

</body>
</html>
