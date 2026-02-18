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
    if ($name) {
        $id = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("INSERT INTO universities (id, name, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$id, $name]);
        header("Location: admin.php");
        exit;
    }
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
<script>function copyUrl(url) { navigator.clipboard.writeText(url).then(()=>alert('URLをコピーしました')); }</script>
</head>
<body class="bg-gray-50 min-h-screen p-6">
<div class="max-w-5xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-blue-900">🏢 申請フォーム管理</h1>
        <a href="?logout" class="text-sm text-gray-500">ログアウト</a>
    </div>
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <form method="post" class="flex gap-3">
            <input type="text" name="create_name" class="flex-1 border rounded px-4 py-2" placeholder="大学名を入力" required>
            <button type="submit" class="bg-blue-600 text-white font-bold px-6 py-2 rounded">＋ 発行</button>
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
                        <a href="<?php echo $formUrl; ?>" target="_blank" class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs font-bold">↗ 確認</a>
                        <a href="?delete=<?php echo $uni['id']; ?>" onclick="return confirm('削除しますか？')" class="text-red-500 text-xs ml-4">削除</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>