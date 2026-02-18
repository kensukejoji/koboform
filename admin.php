<?php
// admin.php - 管理者ダッシュボード (MySQL版)
require_once 'db_config.php';
session_start();

// --- 簡易認証設定 ---
$ADMIN_PASSWORD = 'jg2025'; // パスワード

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

    if ($name) {
        $id = bin2hex(random_bytes(8));
        $initialData = [
            '_uni' => $name,
            '_theme' => $theme,
            '_region' => $region,
            '_created' => date('c'),
            '_updated' => null,
            'fields' => ['s11_daigakuname' => $name, 's12_jisshisyutai' => $name]
        ];

        // テーマが入力されていればAI生成を実行
        if ($theme && !empty($GEMINI_API_KEY)) {
            $prompt  = buildGeminiPrompt($name, $region, $theme);
            $aiData  = callGeminiApi($prompt);
            if (!isset($aiData['error'])) {
                $initialData = array_replace_recursive($initialData, $aiData);
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

        $prompt  = buildGeminiPrompt($name, $region, $theme);
        $aiData  = callGeminiApi($prompt);

        if (isset($aiData['error'])) {
            $_SESSION['flash_msg'] = "⚠️ AI生成エラー: " . $aiData['error'];
        } else {
            $currentData['_theme']  = $theme;
            $currentData['_region'] = $region;
            $newData = array_replace_recursive($currentData, $aiData);
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

$stmt = $pdo->query("SELECT * FROM universities ORDER BY created_at DESC");
$universities = $stmt->fetchAll();

function calcProgress($jsonData) {
    $data = json_decode($jsonData, true);
    if (!$data || !isset($data['fields'])) return 0;
    $keys = ['s11_daigakuname','s11_gakucho','s12_jisshisyutai','s12_jigyomei','s12_point','s12_sogaku','s12_hojokinn','s13_iinkaime','s2_sangyo','s2_daigaku'];
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
<script src="https://cdn.tailwindcss.com"></script>
<script>
function copyUrl(url) { navigator.clipboard.writeText(url).then(()=>alert('URLをコピーしました')); }
function openRegenModal(id, name, theme, region) {
    document.getElementById('regenId').value = id;
    document.getElementById('regenName').value = name;
    document.getElementById('regenTheme').value = theme;
    document.getElementById('regenRegion').value = region;
    document.getElementById('regenModal').classList.remove('hidden');
}
</script>
</head>
<body class="bg-gray-50 min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="mb-4 px-5 py-3 rounded-lg text-sm font-bold <?= str_starts_with($_SESSION['flash_msg'], '✅') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
        <?= htmlspecialchars($_SESSION['flash_msg']) ?>
    </div>
    <?php unset($_SESSION['flash_msg']); endif; ?>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-blue-900">🏢 申請フォーム管理</h1>
        <a href="?logout" class="text-sm text-gray-500">ログアウト</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <form method="post" class="flex gap-3">
            <div class="flex-1 flex flex-col gap-2">
                <input type="text" name="create_name" class="border rounded px-4 py-2" placeholder="大学名を入力（例：○○大学）" required>
                <input type="text" name="create_region" class="border rounded px-4 py-2 text-sm" placeholder="地域（任意） 例：北海道夕張市、沖縄県離島エリア">
                <input type="text" name="create_theme" class="border rounded px-4 py-2 text-sm" placeholder="事業テーマ（任意） 例：地域医療を支えるVR看護教育">
                <p class="text-xs text-gray-500">※テーマを入力すると、AIがジョリーグッドの事例を元に申請書の下書きを自動生成します（約10秒かかります）。</p>
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
                ?>
                <tr class="border-b">
                    <td class="p-4 font-bold"><?php echo htmlspecialchars($uni['name']); ?></td>
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
                        <button onclick="openRegenModal('<?php echo $uni['id']; ?>','<?php echo htmlspecialchars($uni['name']); ?>','<?php echo htmlspecialchars($uTheme); ?>','<?php echo htmlspecialchars($uRegion); ?>')" class="bg-purple-100 text-purple-700 px-3 py-1 rounded text-xs font-bold hover:bg-purple-200">🤖 AI生成</button>
                        <a href="<?php echo $formUrl; ?>" target="_blank" class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-bold">↗ 確認</a>
                        <a href="?delete=<?php echo $uni['id']; ?>" onclick="return confirm('削除しますか？')" class="text-red-500 text-xs ml-4">削除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 再生成モーダル -->
<div id="regenModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-lg font-bold mb-4 text-purple-900">🤖 AI再生成</h2>
        <p class="text-xs text-gray-500 mb-4">指定したテーマで申請書の内容を再生成し、上書き保存します。</p>
        <form method="post">
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
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('regenModal').classList.add('hidden')" class="bg-gray-300 px-4 py-2 rounded font-bold text-sm">キャンセル</button>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded font-bold text-sm hover:bg-purple-700">再生成する</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>