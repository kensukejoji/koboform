<?php
// admin.php - 管理者ダッシュボード (MySQL版)
require_once 'db_config.php';
session_start();

// --- 簡易認証設定 ---
$ADMIN_PASSWORD = 'jg2026'; // パスワード

if (isset($_POST['password'])) {
    if ($_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "パスワードが違います";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理者ログイン</title>
<link rel="icon" href="favicon.ico">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
<div class="bg-white p-8 rounded shadow-md w-full max-w-sm">
    <h1 class="text-xl font-bold mb-4 text-blue-900">管理者ログイン</h1>
    <?php if(isset($error)) echo "<p class='text-red-500 text-sm mb-2'>$error</p>"; ?>
    <form method="post">
        <input type="password" name="password" class="w-full border p-2 rounded mb-4" placeholder="パスワード" autofocus>
        <button type="submit" class="w-full bg-blue-700 text-white font-bold py-2 rounded hover:bg-blue-800">ログイン</button>
    </form>
</div>
</body>
</html>
<?php
    exit;
}

if (isset($_POST['create_name'])) {
    $name = trim($_POST['create_name']);
    $theme = trim($_POST['create_theme'] ?? '');
    $region = trim($_POST['create_region'] ?? '');
    $menu = in_array($_POST['create_menu'] ?? '', ['menu1','menu2']) ? $_POST['create_menu'] : 'menu1';

    if ($name) {
        $id = bin2hex(random_bytes(8));

        // Handle PDF uploads
        $pdfPaths = [];
        if (!empty($_FILES['pdfs']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/' . $id . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            for ($i = 0; $i < min(count($_FILES['pdfs']['name']), 2); $i++) {
                if ($_FILES['pdfs']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['pdfs']['size'][$i] > 10 * 1024 * 1024) continue;

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['pdfs']['tmp_name'][$i]);
                finfo_close($finfo);
                if ($mime !== 'application/pdf') continue;

                $safeName = time() . '_' . preg_replace('/[^\p{L}\p{N}_\-\.]/u', '_', $_FILES['pdfs']['name'][$i]);
                $dest = $uploadDir . $safeName;
                if (move_uploaded_file($_FILES['pdfs']['tmp_name'][$i], $dest)) {
                    $pdfPaths[] = 'uploads/' . $id . '/' . $safeName;
                }
            }
        }

        $initialData = [
            '_uni' => $name,
            '_menu' => $menu,
            '_theme' => $theme,
            '_region' => $region,
            '_pdfs' => $pdfPaths,
            '_created' => date('c'),
            '_updated' => null,
            'fields' => ['s11_daigakuname' => $name, 's12_jisshisyutai' => $name]
        ];

        // テーマが入力されていればAI生成を実行（PDF付き）
        if ($theme && !empty($GEMINI_API_KEY)) {
            $prompt = $menu === 'menu2'
                ? buildGeminiPromptMenu2($name, $region, $theme)
                : buildGeminiPrompt($name, $region, $theme);
            $prompt .= buildPdfContextInstruction(count($pdfPaths));

            $aiData = !empty($pdfPaths)
                ? callGeminiApiWithPdfs($prompt, $pdfPaths)
                : callGeminiApi($prompt);
            if (!isset($aiData['error'])) {
                $initialData = array_replace_recursive($initialData, $aiData);
                $initialData['_pdfs'] = $pdfPaths;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO universities (id, name, data, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$id, $name, json_encode($initialData, JSON_UNESCAPED_UNICODE)]);
        header("Location: admin.php");
        exit;
    }
}

// 再生成処理
if (isset($_POST['regenerate_id'])) {
    $id = $_POST['regenerate_id'];
    $theme = trim($_POST['regenerate_theme'] ?? '');
    $region = trim($_POST['regenerate_region'] ?? '');

    // 既存データの取得
    $stmt = $pdo->prepare("SELECT * FROM universities WHERE id = ?");
    $stmt->execute([$id]);
    $uni = $stmt->fetch();

    if ($uni && $theme && !empty($GEMINI_API_KEY)) {
        $currentData = json_decode($uni['data'], true) ?: [];
        $name        = $uni['name'];
        $menu        = $currentData['_menu'] ?? 'menu1';

        // Handle new PDF uploads (replace existing)
        $pdfPaths = $currentData['_pdfs'] ?? [];
        if (!empty($_FILES['pdfs']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/' . $id . '/';
            if (is_dir($uploadDir)) {
                $oldFiles = glob($uploadDir . '*.pdf');
                if ($oldFiles) array_map('unlink', $oldFiles);
            }
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $pdfPaths = [];
            for ($i = 0; $i < min(count($_FILES['pdfs']['name']), 2); $i++) {
                if ($_FILES['pdfs']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['pdfs']['size'][$i] > 10 * 1024 * 1024) continue;

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['pdfs']['tmp_name'][$i]);
                finfo_close($finfo);
                if ($mime !== 'application/pdf') continue;

                $safeName = time() . '_' . preg_replace('/[^\p{L}\p{N}_\-\.]/u', '_', $_FILES['pdfs']['name'][$i]);
                $dest = $uploadDir . $safeName;
                if (move_uploaded_file($_FILES['pdfs']['tmp_name'][$i], $dest)) {
                    $pdfPaths[] = 'uploads/' . $id . '/' . $safeName;
                }
            }
        }

        $prompt = $menu === 'menu2'
            ? buildGeminiPromptMenu2($name, $region, $theme)
            : buildGeminiPrompt($name, $region, $theme);
        $prompt .= buildPdfContextInstruction(count($pdfPaths));

        $aiData = !empty($pdfPaths)
            ? callGeminiApiWithPdfs($prompt, $pdfPaths)
            : callGeminiApi($prompt);

        if (isset($aiData['error'])) {
            $_SESSION['flash_msg'] = "⚠️ AI生成エラー: " . $aiData['error'];
        } else {
            $currentData['_theme']  = $theme;
            $currentData['_region'] = $region;
            $currentData['_pdfs']   = $pdfPaths;
            $newData = array_replace_recursive($currentData, $aiData);
            $newData['_pdfs'] = $pdfPaths;
            $stmt = $pdo->prepare("UPDATE universities SET data = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([json_encode($newData, JSON_UNESCAPED_UNICODE), $id]);
            $_SESSION['flash_msg'] = "✅ AIによる再生成が完了しました！";
        }
    }
    header("Location: admin.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if (preg_match('/^[a-zA-Z0-9-]+$/', $id)) {
        $stmt = $pdo->prepare("DELETE FROM universities WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: admin.php");
    exit;
}

// AI教師データ AJAX単体アップロード（1ファイルずつ処理、JSON応答）
if (isset($_POST['action']) && $_POST['action'] === 'upload_knowledge_ajax') {
    header('Content-Type: application/json; charset=utf-8');
    $kbDir = __DIR__ . '/uploads/ai_knowledge/';
    if (!is_dir($kbDir)) mkdir($kbDir, 0755, true);

    $existing = glob($kbDir . '*.pdf');
    $existingCount = $existing ? count($existing) : 0;
    if ($existingCount >= 50) {
        echo json_encode(['error' => 'AI教師データは最大50ファイルまでです']);
        exit;
    }
    if (empty($_FILES['kb_pdf']) || $_FILES['kb_pdf']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'ファイルアップロードエラー']);
        exit;
    }
    if ($_FILES['kb_pdf']['size'] > 10 * 1024 * 1024) {
        echo json_encode(['error' => '10MBを超えています']);
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['kb_pdf']['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') {
        echo json_encode(['error' => 'PDFファイルではありません']);
        exit;
    }
    $safeName = time() . '_' . mt_rand(100,999) . '_' . preg_replace('/[^\p{L}\p{N}_\-\.]/u', '_', $_FILES['kb_pdf']['name']);
    $pdfPath = $kbDir . $safeName;
    if (!move_uploaded_file($_FILES['kb_pdf']['tmp_name'], $pdfPath)) {
        echo json_encode(['error' => 'ファイル保存に失敗しました']);
        exit;
    }
    // Gemini APIでPDFからテキスト抽出
    $extractResult = callGeminiRawMultimodal(
        "このPDFの内容をすべて正確にテキストとして抽出してください。表やリストの構造はMarkdown形式で保持してください。ヘッダー・フッター等の装飾的な要素は省略して構いません。抽出したテキストのみを出力し、それ以外の説明は不要です。",
        ['uploads/ai_knowledge/' . $safeName],
        false
    );
    $extracted = false;
    if (!empty($extractResult['text'])) {
        $txtPath = $kbDir . pathinfo($safeName, PATHINFO_FILENAME) . '.txt';
        file_put_contents($txtPath, $extractResult['text']);
        $extracted = true;
    }
    echo json_encode(['ok' => true, 'extracted' => $extracted]);
    exit;
}

// AI教師データ URL取得（Webページ → テキスト抽出 → .txt保存）
if (isset($_POST['action']) && $_POST['action'] === 'fetch_url_knowledge') {
    header('Content-Type: application/json; charset=utf-8');
    $kbDir = __DIR__ . '/uploads/ai_knowledge/';
    if (!is_dir($kbDir)) mkdir($kbDir, 0755, true);

    $url = trim($_POST['kb_url'] ?? '');
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => '有効なURLを入力してください']);
        exit;
    }

    // Fetch HTML
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; KoboFormBot/1.0)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if (!$html || $httpCode >= 400) {
        echo json_encode(['error' => "ページ取得失敗（HTTP {$httpCode}）" . ($curlErr ? ": {$curlErr}" : '')]);
        exit;
    }

    // Extract domain for filename
    $parsedUrl = parse_url($url);
    $domain = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $parsedUrl['host'] ?? 'unknown');
    $path = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($parsedUrl['path'] ?? '', '/'));
    $safeName = time() . '_' . mt_rand(100,999) . '_url_' . $domain . ($path ? '_' . substr($path, 0, 40) : '');

    // Save .url marker file
    file_put_contents($kbDir . $safeName . '.url', $url);

    // Send HTML to Gemini for text extraction
    $truncatedHtml = mb_substr($html, 0, 200000); // Limit HTML size
    $extractResult = callGeminiApiText(
        "以下のHTMLからメインコンテンツのテキストを抽出してください。ナビゲーション・フッター・広告等は省略し、本文のみを整理されたテキストとして出力してください。見出し・リスト・表の構造はMarkdown形式で保持してください。抽出したテキストのみを出力し、それ以外の説明は不要です。\n\n元URL: {$url}\n\n--- HTML ---\n{$truncatedHtml}"
    );

    $extracted = false;
    if (!empty($extractResult['text'])) {
        file_put_contents($kbDir . $safeName . '.txt', "元URL: {$url}\n\n" . $extractResult['text']);
        $extracted = true;
    }

    echo json_encode(['ok' => true, 'extracted' => $extracted]);
    exit;
}

// AI教師データ 削除（PDF/URL + 抽出テキストを同時削除）
if (isset($_POST['action']) && $_POST['action'] === 'delete_knowledge') {
    $filename = basename($_POST['kb_filename'] ?? '');
    $filepath = __DIR__ . '/uploads/ai_knowledge/' . $filename;
    $ext = pathinfo($filepath, PATHINFO_EXTENSION);
    if ($filename && file_exists($filepath) && in_array($ext, ['pdf', 'url'])) {
        unlink($filepath);
        // 対応する抽出テキストファイルも削除
        $txtPath = __DIR__ . '/uploads/ai_knowledge/' . pathinfo($filename, PATHINFO_FILENAME) . '.txt';
        if (file_exists($txtPath)) unlink($txtPath);
        $_SESSION['flash_msg'] = "✅ 削除しました";
    } else {
        $_SESSION['flash_msg'] = "⚠️ ファイルが見つかりません";
    }
    header("Location: admin.php");
    exit;
}

// AI教師データ 再抽出（AJAX）
if (isset($_POST['action']) && $_POST['action'] === 'reextract_knowledge') {
    header('Content-Type: application/json; charset=utf-8');
    $filename = basename($_POST['kb_filename'] ?? '');
    $kbDir = __DIR__ . '/uploads/ai_knowledge/';
    $filepath = $kbDir . $filename;
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    if (!$filename || !file_exists($filepath)) {
        echo json_encode(['error' => 'ファイルが見つかりません']);
        exit;
    }
    if ($ext === 'pdf') {
        $extractResult = callGeminiRawMultimodal(
            "このPDFの内容をすべて正確にテキストとして抽出してください。表やリストの構造はMarkdown形式で保持してください。ヘッダー・フッター等の装飾的な要素は省略して構いません。抽出したテキストのみを出力し、それ以外の説明は不要です。",
            ['uploads/ai_knowledge/' . $filename],
            false
        );
    } elseif ($ext === 'url') {
        $url = trim(file_get_contents($filepath));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; KoboFormBot/1.0)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $html = curl_exec($ch);
        curl_close($ch);
        $truncatedHtml = mb_substr($html ?: '', 0, 200000);
        $extractResult = callGeminiApiText(
            "以下のHTMLからメインコンテンツのテキストを抽出してください。ナビゲーション・フッター・広告等は省略し、本文のみを整理されたテキストとして出力してください。\n\n元URL: {$url}\n\n--- HTML ---\n{$truncatedHtml}"
        );
    } else {
        echo json_encode(['error' => '対応していないファイル形式です']);
        exit;
    }
    $extracted = false;
    if (!empty($extractResult['text'])) {
        $txtPath = $kbDir . pathinfo($filename, PATHINFO_FILENAME) . '.txt';
        $content = ($ext === 'url') ? "元URL: {$url}\n\n" . $extractResult['text'] : $extractResult['text'];
        file_put_contents($txtPath, $content);
        $extracted = true;
    }
    echo json_encode(['ok' => true, 'extracted' => $extracted]);
    exit;
}

$stmt = $pdo->query("SELECT * FROM universities ORDER BY created_at DESC");
$universities = $stmt->fetchAll();

function calcProgress($jsonData) {
    $data = json_decode($jsonData, true);
    if (!$data || !isset($data['fields'])) return 0;
    $menu = $data['_menu'] ?? 'menu1';
    $keys = $menu === 'menu2'
        ? ['s21_daigakuname','s21_gakucho','s22_jisshisyutai','s22_jigyomei','s22_point','s22_sogaku','s22_hojokinn','s23_taisei','s23_kigyorenkei','s23_program']
        : ['s11_daigakuname','s11_gakucho','s12_jisshisyutai','s12_jigyomei','s12_point','s12_sogaku','s12_hojokinn','s13_iinkaime','s2_sangyo','s2_daigaku'];
    $filled = 0;
    foreach ($keys as $k) { if (!empty($data['fields'][$k])) $filled++; }
    return round(($filled / count($keys)) * 100);
}

$baseUrl = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理画面</title>
<link rel="icon" href="favicon.ico">
<script src="https://cdn.tailwindcss.com"></script>
<style>
#aiLoadingOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; flex-direction:column; align-items:center; justify-content:center; }
#aiLoadingOverlay.active { display:flex; }
.spinner { width:56px; height:56px; border:5px solid rgba(255,255,255,0.2); border-top-color:#fff; border-radius:50%; animation:spin 1s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.progress-bar-track { width:320px; height:10px; background:rgba(255,255,255,0.2); border-radius:9999px; overflow:hidden; margin-top:20px; }
.progress-bar-fill { height:100%; width:0%; background:#a855f7; border-radius:9999px; transition:width 0.4s ease; }
</style>
<script>
function copyUrl(url) { navigator.clipboard.writeText(url).then(()=>alert('URLをコピーしました')); }

function openRegenModal(id, name, theme, region, pdfCount) {
    document.getElementById('regenId').value = id;
    document.getElementById('regenName').value = name;
    document.getElementById('regenTheme').value = theme;
    document.getElementById('regenRegion').value = region;
    const pdfInfo = document.getElementById('regenExistingPdfs');
    if (pdfCount > 0) {
        pdfInfo.textContent = '現在' + pdfCount + '件のPDFが添付済みです。新しいファイルを選択すると置き換えられます。';
    } else {
        pdfInfo.textContent = 'PDF資料をアップロードすると、AIがPDFの内容を読み込んでより具体的な申請書を生成します。';
    }
    document.getElementById('regenModal').classList.remove('hidden');
}

function showAiLoading(messageEl) {
    const overlay = document.getElementById('aiLoadingOverlay');
    overlay.classList.add('active');
    const fill = document.getElementById('aiProgressFill');
    const msg  = document.getElementById('aiLoadingMsg');
    if (messageEl) msg.textContent = messageEl;
    let pct = 0;
    const steps = [
        {target:20, label:'AIに接続中...'},
        {target:45, label:'申請書の文案を生成中...'},
        {target:65, label:'予算計画を作成中...'},
        {target:80, label:'データを整理中...'},
        {target:88, label:'もうすぐ完了...'},
    ];
    let si = 0;
    const interval = setInterval(() => {
        if (si < steps.length) {
            const step = steps[si];
            if (pct < step.target) {
                pct = Math.min(pct + 1, step.target);
                fill.style.width = pct + '%';
                msg.textContent = step.label;
            } else { si++; }
        }
    }, 180);
}

document.addEventListener('DOMContentLoaded', () => {
    // 新規発行フォーム（テーマ必須・発行時に常にAI生成が走る）
    document.querySelector('form[method="post"]:not(#regenForm)').addEventListener('submit', function() {
        showAiLoading('申請書の下書きを生成中...');
    });
    // 再生成フォーム
    document.getElementById('regenForm').addEventListener('submit', function() {
        document.getElementById('regenModal').classList.add('hidden');
        showAiLoading('AI再生成中...');
    });
});
</script>
</head>
<body class="bg-gray-50 min-h-screen">
<?php $currentPage = ''; $navWidth = 'max-w-5xl'; include 'nav.php'; ?>
<div class="max-w-5xl mx-auto p-6">
    <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="mb-4 px-5 py-3 rounded-lg text-sm font-bold <?= str_starts_with($_SESSION['flash_msg'], '✅') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <?= htmlspecialchars($_SESSION['flash_msg']) ?>
    </div>
    <?php unset($_SESSION['flash_msg']); endif; ?>
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-blue-900">🏢 申請フォーム管理</h1>
        <a href="?logout" class="text-sm text-gray-500">ログアウト</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <form method="post" enctype="multipart/form-data" class="flex gap-3">
            <div class="flex-1 flex flex-col gap-2">
                <input type="text" name="create_name" class="border rounded px-4 py-2" placeholder="大学名を入力（例：○○大学）" required>
                <select name="create_menu" class="border rounded px-4 py-2 text-sm bg-white">
                    <option value="menu1">メニュー①　地方創生（産学連携リ・スキリング）</option>
                    <option value="menu2">メニュー②　産業成長（産学連携リ・スキリング）</option>
                </select>
                <input type="text" name="create_region" class="border rounded px-4 py-2 text-sm" placeholder="地域（任意） 例：北海道夕張市、沖縄県離島エリア">
                <input type="text" name="create_theme" class="border rounded px-4 py-2 text-sm" placeholder="事業テーマ（必須） 例：地域医療を支えるVR看護教育" required>
                <div class="border rounded p-3 bg-gray-50">
                    <label class="block text-xs font-bold text-gray-600 mb-1">PDF資料（任意・最大2ファイル、各10MBまで）</label>
                    <input type="file" name="pdfs[]" multiple accept=".pdf" class="text-xs">
                    <p class="text-xs text-gray-400 mt-1">スライド資料・企画書等をアップロードすると、AIがPDFの内容を読み込んでより具体的な申請書を生成します。</p>
                </div>
                <p class="text-xs text-gray-500">※テーマを入力すると発行と同時にAIが申請書の下書きを自動生成します（約10〜30秒かかります）。</p>
            </div>
            <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2 rounded h-12 self-start">＋ 発行</button>
        </form>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-4">大学名</th>
                    <th class="p-4">進捗</th>
                    <th class="p-4">最終更新</th>
                    <th class="p-4">アクション</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($universities as $uni):
                    $formUrl = $baseUrl . "/index.php?id=" . $uni['id'];
                    $prog = calcProgress($uni['data']);
                    $uData = json_decode($uni['data'], true);
                    $uTheme = $uData['_theme'] ?? '';
                    $uRegion = $uData['_region'] ?? '';
                    $uMenu = $uData['_menu'] ?? 'menu1';
                    $menuLabel = $uMenu === 'menu2' ? ['②産業成長','bg-orange-100 text-orange-700'] : ['①地方創生','bg-blue-100 text-blue-700'];
                ?>
                <tr class="border-b">
                    <td class="p-4">
                        <div class="font-bold"><?php echo htmlspecialchars($uni['name']); ?></div>
                        <span class="text-xs px-2 py-0.5 rounded font-bold <?= $menuLabel[1] ?>"><?= $menuLabel[0] ?></span>
                        <?php $uPdfs = $uData['_pdfs'] ?? []; if (!empty($uPdfs)): ?>
                        <span class="text-xs px-2 py-0.5 rounded font-bold bg-green-100 text-green-700">PDF <?= count($uPdfs) ?>件</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500" style="width:<?php echo $prog; ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo $prog; ?>%</span>
                        </div>
                    </td>
                    <td class="p-4 text-sm"><?php echo $uni['updated_at'] ? date('Y/m/d H:i', strtotime($uni['updated_at'])) : '未着手'; ?></td>
                    <td class="p-4 flex gap-2">
                        <button onclick="copyUrl('<?php echo $formUrl; ?>')" class="bg-green-100 text-green-700 px-3 py-1 rounded text-xs font-bold">🔗 URLコピー</button>
                        <button onclick="openRegenModal('<?php echo $uni['id']; ?>','<?php echo htmlspecialchars($uni['name']); ?>','<?php echo htmlspecialchars($uTheme); ?>','<?php echo htmlspecialchars($uRegion); ?>',<?php echo count($uPdfs); ?>)" class="bg-purple-100 text-purple-700 px-3 py-1 rounded text-xs font-bold hover:bg-purple-200">🤖 AI生成</button>
                        <a href="<?php echo $formUrl; ?>" target="_blank" class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-bold">↗ 確認</a>
                        <a href="?delete=<?php echo $uni['id']; ?>" onclick="return confirm('削除しますか？')" class="text-red-500 text-xs ml-4">削除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- AI教師データ管理 -->
    <div class="bg-white p-6 rounded-lg shadow mt-8">
        <h2 class="text-lg font-bold text-indigo-900 mb-3">🧠 AI教師データ管理</h2>
        <p class="text-xs text-gray-500 mb-4">PDF・WebページのURLを登録すると、AIがテキストを抽出してチャットボットの知識として参照します。</p>
        <!-- PDF Upload -->
        <div class="flex items-end gap-3 mb-3">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-600 mb-1">📄 PDFファイル（各10MBまで）</label>
                <input type="file" id="kbFileInput" multiple accept=".pdf" class="text-xs w-full border rounded p-2">
            </div>
            <button onclick="startKbUpload()" class="bg-indigo-600 text-white font-bold px-4 py-2 rounded text-sm hover:bg-indigo-700 h-10">アップロード</button>
        </div>
        <!-- URL Input -->
        <div class="flex items-end gap-3 mb-5">
            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-600 mb-1">🌐 WebページURL</label>
                <input type="url" id="kbUrlInput" placeholder="https://example.com/page" class="text-sm w-full border rounded px-3 py-2">
            </div>
            <button onclick="startKbUrlFetch()" class="bg-emerald-600 text-white font-bold px-4 py-2 rounded text-sm hover:bg-emerald-700 h-10">取得</button>
        </div>
        <?php
        $kbDir = __DIR__ . '/uploads/ai_knowledge/';
        $kbPdfs = is_dir($kbDir) ? glob($kbDir . '*.pdf') : [];
        $kbUrls = is_dir($kbDir) ? glob($kbDir . '*.url') : [];
        $kbAll = array_merge($kbPdfs, $kbUrls);
        usort($kbAll, function($a, $b) { return filemtime($b) - filemtime($a); });
        if (!empty($kbAll)): ?>
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3">種別</th>
                    <th class="p-3">名前</th>
                    <th class="p-3">テキスト抽出</th>
                    <th class="p-3">登録日時</th>
                    <th class="p-3">操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($kbAll as $f):
                $fname = basename($f);
                $ext = pathinfo($fname, PATHINFO_EXTENSION);
                $fdate = date('Y/m/d H:i', filemtime($f));
                $txtExists = file_exists($kbDir . pathinfo($fname, PATHINFO_FILENAME) . '.txt');
                if ($ext === 'pdf') {
                    $typeLabel = '<span class="text-xs px-2 py-0.5 rounded font-bold bg-red-100 text-red-700">PDF</span>';
                    $displayName = preg_replace('/^\d+_\d+_/', '', $fname);
                } else {
                    $typeLabel = '<span class="text-xs px-2 py-0.5 rounded font-bold bg-blue-100 text-blue-700">URL</span>';
                    $urlContent = file_get_contents($f);
                    $displayName = mb_strlen($urlContent) > 60 ? mb_substr($urlContent, 0, 60) . '...' : $urlContent;
                }
            ?>
                <tr class="border-b">
                    <td class="p-3"><?= $typeLabel ?></td>
                    <td class="p-3 font-medium text-xs"><?= htmlspecialchars($displayName) ?></td>
                    <td class="p-3"><?= $txtExists ? '<span class="text-green-600 font-bold">✅ 済</span>' : '<span class="text-red-500 font-bold">❌ 未</span>' ?></td>
                    <td class="p-3 text-gray-500"><?= $fdate ?></td>
                    <td class="p-3 flex gap-2">
                        <?php if (!$txtExists): ?>
                        <button onclick="reextractKb('<?= htmlspecialchars($fname, ENT_QUOTES) ?>', this)" class="text-indigo-600 text-xs font-bold hover:text-indigo-800">🔄 再抽出</button>
                        <?php endif; ?>
                        <form method="post" class="inline" onsubmit="return confirm('削除しますか？')">
                            <input type="hidden" name="action" value="delete_knowledge">
                            <input type="hidden" name="kb_filename" value="<?= htmlspecialchars($fname) ?>">
                            <button type="submit" class="text-red-500 text-xs font-bold hover:text-red-700">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="text-sm text-gray-400 py-3">まだ教師データが登録されていません。</p>
        <?php endif; ?>
        <p class="text-xs text-gray-400 mt-2">現在 <?= count($kbAll) ?> 件</p>
    </div>
</div>

<!-- AI教師データ アップロード進捗オーバーレイ -->
<div id="kbUploadOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:10000; display:none; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:40px 48px; max-width:480px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <!-- Animated spinner -->
        <div id="kbSpinner" style="margin:0 auto 20px; width:56px; height:56px; border:4px solid #e5e7eb; border-top:4px solid #4f46e5; border-radius:50%; animation:kbSpin 0.8s linear infinite;"></div>
        <p id="kbUploadTitle" style="font-size:18px; font-weight:bold; color:#1e1b4b; margin-bottom:8px;">アップロード準備中...</p>
        <p id="kbUploadDetail" style="font-size:13px; color:#6b7280; margin-bottom:20px;"></p>
        <!-- Progress bar -->
        <div style="background:#e5e7eb; border-radius:999px; height:12px; overflow:hidden; margin-bottom:8px;">
            <div id="kbProgressBar" style="height:100%; background:linear-gradient(90deg,#6366f1,#8b5cf6); border-radius:999px; width:0%; transition:width 0.4s ease;"></div>
        </div>
        <p id="kbProgressText" style="font-size:12px; color:#9ca3af;">0 / 0 件</p>
        <!-- Result area (hidden initially) -->
        <div id="kbResultArea" style="display:none; margin-top:16px; padding:12px; border-radius:8px; font-size:13px; text-align:left;"></div>
    </div>
</div>
<style>
@keyframes kbSpin { to { transform: rotate(360deg); } }
</style>
<script>
async function startKbUpload() {
    const input = document.getElementById('kbFileInput');
    const files = Array.from(input.files);
    if (files.length === 0) { alert('ファイルを選択してください'); return; }

    const overlay = document.getElementById('kbUploadOverlay');
    const title = document.getElementById('kbUploadTitle');
    const detail = document.getElementById('kbUploadDetail');
    const bar = document.getElementById('kbProgressBar');
    const text = document.getElementById('kbProgressText');
    const spinner = document.getElementById('kbSpinner');
    const resultArea = document.getElementById('kbResultArea');

    overlay.style.display = 'flex';
    resultArea.style.display = 'none';
    spinner.style.display = 'block';

    let success = 0, extractFail = [], errors = [];
    const total = files.length;

    for (let i = 0; i < total; i++) {
        const file = files[i];
        const num = i + 1;
        const pct = Math.round((i / total) * 100);
        bar.style.width = pct + '%';
        text.textContent = num + ' / ' + total + ' 件';

        // Phase 1: Upload
        title.textContent = '📤 アップロード中...';
        detail.textContent = file.name + '（' + num + '/' + total + '）';

        const fd = new FormData();
        fd.append('action', 'upload_knowledge_ajax');
        fd.append('kb_pdf', file);

        try {
            // Phase 2: Extracting (update UI before await)
            title.textContent = '🧠 テキスト抽出中...';
            detail.textContent = file.name + '（' + num + '/' + total + '）— AIが読み取り中';

            const res = await fetch('admin.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) {
                errors.push(file.name + ': ' + data.error);
            } else {
                success++;
                if (!data.extracted) extractFail.push(file.name);
            }
        } catch (e) {
            errors.push(file.name + ': 通信エラー');
        }

        bar.style.width = Math.round(((i + 1) / total) * 100) + '%';
    }

    // Done
    spinner.style.display = 'none';
    bar.style.width = '100%';
    title.textContent = '✅ アップロード完了';
    detail.textContent = '';
    text.textContent = success + ' / ' + total + ' 件 成功';

    let resultHtml = '';
    if (success > 0) resultHtml += '<p style="color:#16a34a; margin-bottom:4px;">✅ ' + success + '件アップロード・テキスト抽出しました</p>';
    if (extractFail.length > 0) resultHtml += '<p style="color:#d97706; margin-bottom:4px;">⚠️ テキスト抽出失敗: ' + extractFail.join(', ') + '</p>';
    if (errors.length > 0) resultHtml += '<p style="color:#dc2626; margin-bottom:4px;">❌ エラー: ' + errors.join(', ') + '</p>';
    resultHtml += '<button onclick="location.reload()" style="margin-top:12px; background:#4f46e5; color:#fff; padding:8px 24px; border-radius:8px; font-weight:bold; font-size:14px; border:none; cursor:pointer;">OK</button>';

    resultArea.innerHTML = resultHtml;
    resultArea.style.display = 'block';
}

async function reextractKb(filename, btn) {
    btn.textContent = '⏳ 抽出中...';
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'reextract_knowledge');
        fd.append('kb_filename', filename);
        const res = await fetch('admin.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok && data.extracted) {
            btn.textContent = '✅ 完了';
            btn.style.color = '#16a34a';
            setTimeout(() => location.reload(), 1000);
        } else {
            btn.textContent = '❌ 失敗';
            btn.style.color = '#dc2626';
            btn.disabled = false;
        }
    } catch (e) {
        btn.textContent = '❌ エラー';
        btn.disabled = false;
    }
}

async function startKbUrlFetch() {
    const urlInput = document.getElementById('kbUrlInput');
    const url = urlInput.value.trim();
    if (!url) { alert('URLを入力してください'); return; }
    if (!/^https?:\/\/.+/.test(url)) { alert('有効なURLを入力してください（https://...）'); return; }

    const overlay = document.getElementById('kbUploadOverlay');
    const title = document.getElementById('kbUploadTitle');
    const detail = document.getElementById('kbUploadDetail');
    const bar = document.getElementById('kbProgressBar');
    const text = document.getElementById('kbProgressText');
    const spinner = document.getElementById('kbSpinner');
    const resultArea = document.getElementById('kbResultArea');

    overlay.style.display = 'flex';
    resultArea.style.display = 'none';
    spinner.style.display = 'block';
    bar.style.width = '30%';
    text.textContent = '';
    title.textContent = '🌐 Webページ取得中...';
    detail.textContent = url;

    const fd = new FormData();
    fd.append('action', 'fetch_url_knowledge');
    fd.append('kb_url', url);

    try {
        title.textContent = '🧠 テキスト抽出中...';
        detail.textContent = url + ' — AIが読み取り中';
        bar.style.width = '60%';

        const res = await fetch('admin.php', { method: 'POST', body: fd });
        const data = await res.json();
        bar.style.width = '100%';

        spinner.style.display = 'none';
        let resultHtml = '';
        if (data.error) {
            title.textContent = '❌ エラー';
            resultHtml = '<p style="color:#dc2626; margin-bottom:4px;">❌ ' + data.error + '</p>';
        } else {
            title.textContent = '✅ 取得完了';
            resultHtml = data.extracted
                ? '<p style="color:#16a34a; margin-bottom:4px;">✅ Webページのテキストを抽出しました</p>'
                : '<p style="color:#d97706; margin-bottom:4px;">⚠️ ページは取得しましたがテキスト抽出に失敗しました</p>';
        }
        resultHtml += '<button onclick="location.reload()" style="margin-top:12px; background:#4f46e5; color:#fff; padding:8px 24px; border-radius:8px; font-weight:bold; font-size:14px; border:none; cursor:pointer;">OK</button>';
        detail.textContent = '';
        resultArea.innerHTML = resultHtml;
        resultArea.style.display = 'block';
    } catch (e) {
        spinner.style.display = 'none';
        title.textContent = '❌ 通信エラー';
        detail.textContent = '';
        bar.style.width = '100%';
        resultArea.innerHTML = '<p style="color:#dc2626;">通信エラーが発生しました</p><button onclick="location.reload()" style="margin-top:12px; background:#4f46e5; color:#fff; padding:8px 24px; border-radius:8px; font-weight:bold; font-size:14px; border:none; cursor:pointer;">OK</button>';
        resultArea.style.display = 'block';
    }
}
</script>

<!-- AIローディングオーバーレイ -->
<div id="aiLoadingOverlay">
    <div class="spinner"></div>
    <p id="aiLoadingMsg" class="text-white font-bold mt-5 text-base">AI生成中...</p>
    <div class="progress-bar-track"><div id="aiProgressFill" class="progress-bar-fill"></div></div>
    <p class="text-white text-xs mt-3 opacity-60">通常10〜20秒かかります。しばらくお待ちください。</p>
</div>

<!-- 再生成モーダル -->
<div id="regenModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-lg font-bold mb-4 text-purple-900">🤖 AI再生成</h2>
        <p class="text-xs text-gray-500 mb-4">指定したテーマで申請書の内容を再生成し、上書き保存します。</p>
        <form method="post" id="regenForm" enctype="multipart/form-data">
            <input type="hidden" name="regenerate_id" id="regenId">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">大学名</label>
                <input type="text" id="regenName" class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600" readonly>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">地域</label>
                <input type="text" name="regenerate_region" id="regenRegion" class="w-full border rounded px-3 py-2" placeholder="例：北海道夕張市">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">事業テーマ</label>
                <input type="text" name="regenerate_theme" id="regenTheme" class="w-full border rounded px-3 py-2" placeholder="例：地域医療を支えるVR看護教育" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">PDF資料（任意・最大2ファイル、各10MBまで）</label>
                <input type="file" name="pdfs[]" multiple accept=".pdf" class="text-xs w-full">
                <p id="regenExistingPdfs" class="text-xs text-gray-400 mt-1"></p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('regenModal').classList.add('hidden')" class="bg-gray-300 px-4 py-2 rounded font-bold text-sm">キャンセル</button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded font-bold text-sm hover:bg-purple-700">再生成する</button>
            </div>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>