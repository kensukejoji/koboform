<?php
// ai_chat.php - AIチャットボットAPI
// index.php（申請書入力）と register.php（登録ページ）の両方から呼ばれる
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$universityId = trim($input['university_id'] ?? '');
$message      = trim($input['message'] ?? '');
$history      = $input['conversation_history'] ?? [];
$context      = $input['current_context'] ?? [];

if (!$message) {
    http_response_code(400);
    echo json_encode(['error' => 'メッセージを入力してください']);
    exit;
}

// _register は登録ページからの特別なコンテキスト
$isRegisterContext = ($universityId === '_register');

if (!$isRegisterContext) {
    if (!$universityId || !preg_match('/^[a-zA-Z0-9-]+$/', $universityId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid university ID']);
        exit;
    }
}

// Load university data for context (skip for register page)
$uniName = '';
$uniData = [];
if (!$isRegisterContext && $universityId) {
    $stmt = $pdo->prepare("SELECT name, data FROM universities WHERE id = ?");
    $stmt->execute([$universityId]);
    $row = $stmt->fetch();
    if ($row) {
        $uniName = $row['name'];
        $uniData = json_decode($row['data'], true) ?: [];
    }
}

$menu = $context['menu'] ?? ($uniData['_menu'] ?? 'menu1');

// Load knowledge base text files (extracted from PDFs/URLs at upload time)
$kbDir = __DIR__ . '/uploads/ai_knowledge/';
$kbText = '';
if (is_dir($kbDir)) {
    $txtFiles = glob($kbDir . '*.txt');
    if ($txtFiles) {
        foreach ($txtFiles as $f) {
            $displayName = preg_replace('/^\d+_\d+_/', '', pathinfo(basename($f), PATHINFO_FILENAME));
            $displayName = preg_replace('/^url_/', '[Web] ', $displayName);
            $content = file_get_contents($f);
            if ($content) {
                $kbText .= "--- {$displayName} ---\n{$content}\n\n";
            }
        }
    }
}

// Build system prompt
$hasKnowledgeBase = !empty($kbText);

if ($isRegisterContext) {
    $systemPrompt = buildRegisterSystemPrompt($context, $hasKnowledgeBase);
} else {
    $systemPrompt = buildChatSystemPrompt($uniName, $menu, $context, $hasKnowledgeBase);
}

// Append knowledge base text to system prompt
if ($hasKnowledgeBase) {
    $systemPrompt .= "\n\n【参考資料の内容】\n" . $kbText;
}

// Build conversation text with recent history (last 10 messages)
$recentHistory = array_slice($history, -10);
$conversationText = $systemPrompt . "\n\n";

foreach ($recentHistory as $msg) {
    $role = $msg['role'] === 'user' ? 'ユーザー' : 'アシスタント';
    $conversationText .= "【{$role}】\n{$msg['content']}\n\n";
}

$conversationText .= "【ユーザー】\n{$message}\n\n【アシスタント】\n";

// Always use text mode
$result = callGeminiApiText($conversationText);

if (isset($result['error'])) {
    echo json_encode(['error' => $result['error']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Generate suggestions
$suggestions = $isRegisterContext
    ? generateRegisterSuggestions($context)
    : generateChatSuggestions($context);

echo json_encode([
    'reply'       => $result['text'],
    'suggestions' => $suggestions,
], JSON_UNESCAPED_UNICODE);


// ================================================================
// Functions
// ================================================================

/**
 * Gemini APIをテキストモードで呼び出す
 */
if (!function_exists('callGeminiApiText')) {
    function callGeminiApiText(string $prompt): array
    {
        global $GEMINI_API_KEY;
        if (empty($GEMINI_API_KEY)) {
            return ['error' => 'APIキーが設定されていません'];
        }
        return callGeminiRaw($prompt, false);
    }
}

/**
 * Gemini API 低レベル呼び出し（リトライ付き）
 */
if (!function_exists('callGeminiRaw')) {
    function callGeminiRaw(string $prompt, bool $jsonMode): array
    {
        global $GEMINI_API_KEY;

        $body = ['contents' => [['parts' => [['text' => $prompt]]]]];
        if ($jsonMode) {
            $body['generationConfig'] = ['response_mime_type' => 'application/json'];
        }

        $maxRetries = 4;
        $waitSecs   = [5, 10, 20, 30];

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $GEMINI_API_KEY);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $err = curl_error($ch);
                curl_close($ch);
                return ['error' => '通信エラー: ' . $err];
            }
            curl_close($ch);

            $result = json_decode($response, true);

            $errCode = $result['error']['code'] ?? 0;
            $errMsg  = $result['error']['message'] ?? '';
            if (($errCode === 429 || str_contains($errMsg, 'Resource exhausted') || str_contains($errMsg, 'Quota exceeded'))
                && $attempt < $maxRetries) {
                sleep($waitSecs[$attempt]);
                continue;
            }

            if (isset($result['error'])) {
                return ['error' => 'Gemini APIエラー: ' . $errMsg];
            }

            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!$text) {
                return ['error' => 'AIからの応答が空でした'];
            }

            return ['text' => $text];
        }

        return ['error' => 'レート制限のため生成できませんでした。しばらく時間をおいて再試行してください。'];
    }
}

/**
 * 登録ページ用のシステムプロンプト
 */
function buildRegisterSystemPrompt(array $context, bool $hasKnowledgeBase = false): string
{
    $prompt = <<<'EOT'
あなたは文部科学省「産学連携リ・スキリング・エコシステム構築事業」の申請登録をサポートする「ぐうた」です。
大学や専門学校の担当者が、申請フォームに登録する際の疑問に答えます。

【あなたの役割】
・申請メニューの選び方（メニュー①地方創生 vs メニュー②産業成長）をアドバイスする
・事業テーマの書き方を具体的にアドバイスする
・この公募事業の概要や審査基準を説明する
・わかりやすい日本語で簡潔に回答する

【2つのメニューの違い】
■ メニュー①地方創生
・地方の社会課題（過疎化・人材不足・高齢化）解決型
・自治体（県庁・市役所）や地域金融機関（銀行・商工会議所・農協）との連携が必須
・産学官金の4者連携エコシステムを構築
・補助金は総事業費の約2/3、事業規模30,000〜50,000千円が目安
・地域に人が残る・定着するストーリーが重要

■ メニュー②産業成長
・産業の成長・競争力強化に直結する人材育成型
・業界団体や専門企業との連携が重要（自治体連携は不要）
・補助金上限: 1領域当たり39,500千円
・参加人数170人（努力目標）の達成計画が重要
・受講者のキャリアアップ・業界全体の技術向上ストーリーが重要

【メニュー選択の判断基準】
1. パートナー: 自治体と組みやすい→①、業界団体・企業と組みやすい→②
2. テーマ: 地域課題解決→①、高度専門技術→②
3. ターゲット: 地域の幅広い現場人材→①、特定の高度専門職→②
4. ゴール: 地域定着・インフラ維持→①、キャリアアップ・業界成長→②
5. 大学タイプ: 地方・地域密着→①、都市部・専門特化→②

【ジョリーグッドのVRサービス】
・VRコンテンツ制作費: 1本 2,000〜3,000千円
・VRゴーグル PICO G3: 1台 130千円（¥129,800税込）
・JollyGood+プラットフォーム年間利用料:
  エントリー: 660千円/年（5台まで）/ ライト: 1,320千円/年（20台まで）
  プロフェッショナル: 2,640千円/年（50台まで）/ エンタープライズ: 5,280千円/年（100台まで）

【回答のルール】
・日本語で簡潔に回答する（1回の回答は200〜400字程度を目安）
・箇条書きを活用して見やすくする
・この事業は医療系に限らず、農業・工業・IT・福祉など幅広い分野の大学・専門学校が対象であることを意識する
・わからないことは正直に「確認が必要です」と伝える
EOT;

    if ($hasKnowledgeBase) {
        $prompt .= "\n\n【参考資料について】\n以下にFAQ・ガイドライン等の資料が添付されています。\nユーザーの質問に回答する際は、これらの資料の内容を優先的に参照してください。";
    }

    return $prompt;
}

/**
 * 申請書入力ページ用のシステムプロンプト
 */
if (!function_exists('buildChatSystemPrompt')) {
    function buildChatSystemPrompt(string $uniName, string $menu, array $context, bool $hasKnowledgeBase = false): string
    {
        $activeTab      = $context['active_tab']      ?? '';
        $activeField    = $context['active_field']     ?? '';
        $fieldsSnapshot = $context['fields_snapshot']  ?? [];

        $filledCount = 0;
        foreach ($fieldsSnapshot as $value) {
            if (trim($value) !== '') $filledCount++;
        }

        $menuLabel = $menu === 'menu2' ? 'メニュー②産業成長' : 'メニュー①地方創生';

        $prompt = <<<EOT
あなたは文部科学省「産学連携リ・スキリング・エコシステム構築事業（{$menuLabel}）」の申請書作成を支援する「ぐうた」です。

【あなたの役割】
・大学の担当者が申請書を作成する際の質問に丁寧に答える
・各フィールドに何を書くべきか具体的にアドバイスする
・審査基準と配点を踏まえた記入のコツを教える
・わかりやすい日本語で簡潔に回答する

【現在の状況】
・大学名: {$uniName}
・メニュー: {$menuLabel}
・入力済みフィールド数: {$filledCount}件

【ジョリーグッドのVRサービス】
・VRコンテンツ制作費: 1本 2,000〜3,000千円
・VRゴーグル PICO G3: 1台 130千円（¥129,800税込）
・JollyGood+プラットフォーム年間利用料:
  エントリー: 660千円/年（5台まで）/ ライト: 1,320千円/年（20台まで）
  プロフェッショナル: 2,640千円/年（50台まで）/ エンタープライズ: 5,280千円/年（100台まで）

【回答のルール】
・日本語で簡潔に回答する（1回の回答は200〜400字程度を目安）
・箇条書きを活用して見やすくする
・わからないことは正直に「確認が必要です」と伝える
EOT;

        if ($hasKnowledgeBase) {
            $prompt .= "\n\n【参考資料について】\n以下にFAQ・ガイドライン等の資料が添付されています。\nユーザーの質問に回答する際は、これらの資料の内容を優先的に参照してください。";
        }

        return $prompt;
    }
}

/**
 * 登録ページ用のサジェスチョン
 */
function generateRegisterSuggestions(array $context): array
{
    return [
        'メニュー①と②の違いは？',
        '事業テーマの書き方を教えて',
        '採択されやすいポイントは？',
    ];
}

/**
 * タブごとのフォローアップ質問候補を返す
 */
function generateChatSuggestions(array $context): array
{
    $activeTab = $context['active_tab'] ?? '';

    $tabSuggestions = [
        's11'    => ['提出状の日付はいつにすべき？', '学長名の書き方は？'],
        's21'    => ['提出状の日付はいつにすべき？', '学長名の書き方は？'],
        's12'    => ['事業のポイントの書き方を教えて', '補助金と大学負担の比率は？', '協働機関の書き方は？'],
        's22'    => ['事業のポイントの書き方を教えて', '領域の選び方は？', '170人目標の書き方は？'],
        's13'    => ['委員会の構成メンバーは？', '委員の人数は何人が適切？'],
        's2'     => ['課題①〜⑧で配点が高いのは？', '自走化計画のポイントは？', '企業連携の書き方は？'],
        's23'    => ['最高配点のプログラム欄の書き方は？', '財務計画の書き方は？', '170人達成の具体策は？'],
        's3'     => ['経費の積算根拠の書き方は？', 'VRゴーグルの計上方法は？'],
        's24'    => ['伴走支援に何を書くべき？'],
        'sslide' => ['スライド構成のコツは？'],
    ];

    return $tabSuggestions[$activeTab] ?? ['この申請書の審査基準を教えて', '採択されやすいポイントは？'];
}
