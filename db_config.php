<?php
// db_config.php - データベース接続設定
$db_host = 'localhost'; 
$db_name = 'jollygood25s_koboform'; 
$db_user = 'jollygood25s_usr';
$db_pass = 'TestPass123!';

// Gemini APIキー
$GEMINI_API_KEY = 'AIzaSyDddaIqL25HqJLUtvzTXuziqx6LTIXTTo8';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['error' => 'データベース接続失敗: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Gemini API 呼び出し用の共通プロンプトを構築して返す
 */
function buildGeminiPrompt(string $name, string $region, string $theme): string
{
    $systemPrompt = <<<'EOT'
あなたは文部科学省「産学連携リ・スキリング・エコシステム構築事業（メニュー①地方創生）」の申請書作成を支援するプロです。
株式会社ジョリーグッド（VR/XR技術で医療・介護・製造等のリスキリングを支援する企業）が大学と共同申請することを前提に、指定された「大学名」「地域」「事業テーマ」を深く踏まえた、採択されやすい申請書の文案と予算計画を生成してください。

【ジョリーグッドのサービスと単価（千円単位）】
・VRコンテンツ制作費: 1本 2,000〜3,000千円（医療・介護・福祉・製造業等の分野別実習VR）
・VRゴーグル（Meta Quest Pro等）: 1台 80千円
・JollyGood+プラットフォーム利用料: 年間 1,200千円（LMS・管理システム・サポート含む）
・講師派遣・VR導入研修費: 1回 300千円
・カリキュラム設計・コンサルティング費: 1,500千円

【予算計画の指針（単位はすべて千円の整数値）】
・事業規模（総事業費 = 補助金＋大学負担）: 30,000〜50,000千円程度
・補助金申請額（国負担、総事業費の約2/3）: 20,000〜33,000千円程度
・大学負担額（約1/3）: 10,000〜17,000千円程度
・VRコンテンツ: テーマに応じて3〜8本、ゴーグルは20〜80台を目安
・人件費（URA・研究員等）: 3,000〜8,000千円、謝金（外部専門家・委員）: 1,000〜2,000千円
・必ず「keihiのhojo合計 = s12_hojokinn」「hojo＋futan合計 = s12_sogaku」になるよう数値を合わせること

【出力ルール】
・以下のJSONのみを出力。Markdownコードブロック（```json等）・説明文・挨拶文は一切不要。
・数値フィールド（hojo, futan, s12_sogaku, s12_hojokinn, s12_kikan_futan等）は必ず整数で出力。絶対に文字列にしないこと。
・地域の社会課題（医師不足・高齢化・産業空洞化等）と事業テーマを文章に具体的に反映すること。
・課題①〜⑧への対応は、当事業でどう取り組むかを2〜3文で具体的に説明すること。
・教育プログラムは地域ニーズに合わせて2〜4件作成すること。

出力するJSONの構造:
{
  "fields": {
    "s12_jigyomei": "事業名（地域名・テーマを反映、30文字以内）",
    "s12_point": "事業のポイント（地域課題・VR活用効果・ジョリーグッドとの連携・自走化方針を含む、400字以内）",
    "s12_sogaku": 40000,
    "s12_hojokinn": 27000,
    "s12_kikan_futan": 13000,
    "s12_kyodo_san": "産業界協働機関（株式会社ジョリーグッドの役割、地域の協力企業等）",
    "s12_kyodo_kan": "行政協働機関（地域の都道府県・市区町村・公的機関等）",
    "s13_iinkaime": "（大学名）産学連携リスキリング推進委員会",
    "s13_mokuteki": "委員会の目的・役割（産学官金連携によるプログラムのガバナンス確保と継続的改善）",
    "s13_kentou": "委員会で検討する具体的内容（プログラム設計・効果測定・自走化計画の策定等）",
    "s2_sangyo": "産業界参画機関と役割（株式会社ジョリーグッド：VRシステム開発・コンテンツ制作・導入支援。地域企業：受講生受け入れ・ニーズ提供等）",
    "s2_daigaku": "大学の役割（プログラム設計・実施・修了認定、連携大学等）",
    "s2_gyosei": "行政参画機関と役割（地域の都道府県・市区町村：課題情報提供・広報支援・関連補助との連携）",
    "s2_kinyu": "金融機関の役割（地域銀行・信用金庫等：事業計画へのアドバイス・地域企業との橋渡し）",
    "s2_platform_jiko": "プラットフォームで取り組む事項（スキル標準の策定・デジタルバッジ発行・企業との接続等）",
    "s2_katsudo": "活動範囲と体制構築（対象地域・対象分野・ターゲット層・連携体制）",
    "s2_kigyorenkei": "ジョリーグッドとの連携（VRコンテンツ制作委託・JollyGood+プラットフォーム導入・VR研修実施・学習効果測定）を含む具体的な企業連携",
    "s2_kadai1": "①アドバンストEW育成：VRで高度専門スキルの習得機会を提供する取組",
    "s2_kadai2": "②就職氷河期世代：リスキリングプログラムによる再就職・キャリア転換支援の取組",
    "s2_kadai3": "③地方人材確保：地元での学び直し機会創出と地域定着促進の取組",
    "s2_kadai4": "④スキル可視化：VRログデータ・デジタルバッジによる習熟度の定量化",
    "s2_kadai5": "⑤教員インセンティブ：VR教材開発参加と学習効果向上による教員の動機付け",
    "s2_kadai6": "⑥全学的体制：学長のリーダーシップのもとURAが全学横断でプログラムを推進",
    "s2_kadai7": "⑦修士・博士接続：職業実践力育成プログラム（BP）認定を見据えた設計",
    "s2_kadai8": "⑧大学間連携：近隣大学との共同プログラムによる広域リスキリング体制の構築",
    "s2_jisoka_hyoka": "受講修了後のアンケート・就業状況追跡・企業満足度調査・デジタルバッジ発行による効果検証方法",
    "s2_nenkan": "R8年度スケジュール（前期：体制整備・コンテンツ開発、後期：試行実施・評価・改善）",
    "s2_jisoka_goal": "R9以降の自走化目標像（受講料収入と企業協賛による自主運営。年間受講者数・収支均衡の目標）",
    "s2_jisoka_plan": "自走化に向けた取組計画（受講料設定・企業スポンサーシップ・学内予算化等の具体的施策）",
    "s2_jisoka_zaimu": "財務計画（プログラム数×定員×受講料、企業協賛金、R9以降の収支見込み）",
    "s2_jisoka_jinzai": "人員確保計画（専任コーディネーター・URAの育成・配置計画）"
  },
  "programs": [
    { "name": "プログラム名1", "target": "対象者", "teiin": "20", "ryokin": "150000", "naiyou": "プログラムの内容・特徴" },
    { "name": "プログラム名2", "target": "対象者", "teiin": "15", "ryokin": "200000", "naiyou": "プログラムの内容・特徴" }
  ],
  "keihi": {
    "kb1": { "hojo": 6400, "futan": 0, "naiyou": "VRゴーグル（Meta Quest Pro）80台" },
    "kb2": { "hojo": 300, "futan": 0, "naiyou": "消耗品費（記録媒体・周辺機器等）" },
    "kb3": { "hojo": 5000, "futan": 2000, "naiyou": "プロジェクト研究員・URA人件費" },
    "kb4": { "hojo": 1200, "futan": 500, "naiyou": "外部専門家・委員会委員謝金" },
    "kb5": { "hojo": 600, "futan": 200, "naiyou": "国内旅費（委員会・視察・普及活動）" },
    "kb6": { "hojo": 9000, "futan": 0, "naiyou": "VRコンテンツ制作委託費（株式会社ジョリーグッド）3本" },
    "kb7": { "hojo": 200, "futan": 0, "naiyou": "成果報告書・パンフレット印刷費" },
    "kb8": { "hojo": 100, "futan": 0, "naiyou": "通信・運搬費" },
    "kb9": { "hojo": 2200, "futan": 0, "naiyou": "JollyGood+プラットフォーム利用料・導入研修費（株式会社ジョリーグッド）" }
  }
}
EOT;

    $userPrompt = "大学名: {$name}";
    if ($region) $userPrompt .= "\n地域: {$region}";
    $userPrompt .= "\n事業テーマ: {$theme}";

    return $systemPrompt . "\n\n" . $userPrompt;
}

/**
 * Gemini API を呼び出し、生成されたデータを配列で返す。
 * 失敗時は ['error' => '...'] を返す。
 */
function callGeminiApi(string $prompt): array
{
    global $GEMINI_API_KEY;

    if (empty($GEMINI_API_KEY)) {
        return ['error' => 'APIキーが設定されていません'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['response_mime_type' => 'application/json'],
    ]));
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => '通信エラー: ' . $err];
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (isset($result['error'])) {
        return ['error' => 'Gemini APIエラー: ' . ($result['error']['message'] ?? '不明')];
    }

    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!$text) {
        return ['error' => 'AIからの応答が空でした'];
    }

    // JSON抽出（Markdown除去 → デコード → { }範囲抽出）
    $clean = preg_replace('/```json\s*/i', '', $text);
    $clean = preg_replace('/```\s*/i', '', $clean);
    $clean = trim($clean);

    $data = json_decode($clean, true);
    if (!$data) {
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start !== false && $end !== false) {
            $data = json_decode(substr($text, $start, $end - $start + 1), true);
        }
    }

    if (!$data) {
        return ['error' => 'AI生成データの解析に失敗しました (json: ' . json_last_error_msg() . ')'];
    }

    return $data;
}

/**
 * メニュー②産業成長 用のプロンプトを構築して返す
 */
function buildGeminiPromptMenu2(string $name, string $region, string $theme): string
{
    $systemPrompt = <<<'EOT'
あなたは文部科学省「産学連携リ・スキリング・エコシステム構築事業（メニュー②産業成長）」の申請書作成を支援するプロです。
株式会社ジョリーグッド（VR/XR技術で医療・介護・製造等のリスキリングを支援する企業）が大学と共同申請することを前提に、指定された「大学名」「地域」「事業テーマ」を踏まえた採択されやすい申請書の文案と予算計画を生成してください。

【メニュー②産業成長の特徴】
・地域制約なし。産業の成長・競争力強化に直結する人材育成が目的。
・最高配点（12点）は「企業の成長に直結する人材育成プログラム」
・参加人数170人（努力目標）の達成計画が重要（5点）
・受講料収入・財務自走化計画（2〜4年後）が必須（8点×2）
・企業1社以上からの受講生派遣確約が必須要件
・デジタルバッジ発行が必須
・座学と実習・実践の組み合わせが必須（VRが最適）

【ジョリーグッドのサービスと単価（千円単位）】
・VRコンテンツ制作費: 1本 2,000〜3,000千円（業界別実習VR）
・VRゴーグル（Meta Quest Pro等）: 1台 80千円
・JollyGood+プラットフォーム利用料: 年間 1,200千円
・講師派遣・VR導入研修費: 1回 300千円
・カリキュラム設計・コンサルティング費: 1,500千円

【予算計画の指針（単位はすべて千円の整数値）】
・1領域当たり補助金上限: 39,500千円
・事業規模（総事業費）: 40,000〜45,000千円
・補助金申請額: 35,000〜39,500千円
・受講料収入見込み（大学負担の一部）: 2,000〜5,000千円
・VRコンテンツ: テーマに応じて2〜5本、ゴーグルは10〜40台を目安
・人件費（事業責任者・コーディネーター等）: 3,000〜8,000千円
・謝金（外部専門家・企業講師等）: 1,000〜2,000千円
・必ず「keihiのhojo合計 = s22_hojokinn」「hojo＋futan合計 = s22_sogaku」になるよう数値を合わせること

【出力ルール】
・以下のJSONのみを出力。Markdownコードブロック（```json等）・説明文は一切不要。
・数値フィールド（hojo, futan, s22_sogaku, s22_hojokinn等）は必ず整数で出力。
・プログラムは事業テーマに合わせて2〜3件作成すること。
・s23_jisoka_zaimuは2年目・3年目・4年目の財務計画を数値で具体的に記述すること。

出力するJSONの構造:
{
  "fields": {
    "s22_jigyomei": "事業名（事業テーマを反映、30文字以内）",
    "s22_ryoiki1_main": "プログラムの主領域名（例：DXプロ、介護、モビリティ等）",
    "s22_ryoiki1_sub": "サブ領域名（例：VR活用技能習得等）",
    "s22_point": "事業のポイント（産業課題・VR活用効果・ジョリーグッドとの連携・170人目標・自走化方針を含む、400字以内）",
    "s22_sogaku": 42000,
    "s22_hojokinn": 39500,
    "s22_kikan_futan": 2500,
    "s22_jukoryosyu": 3000,
    "s22_kyodo_kigyo": "産業界協働機関（株式会社ジョリーグッドの役割、受講生派遣企業等）",
    "s22_kyodo_kyo": "教育機関協働機関（連携大学等があれば）",
    "s23_taisei": "学内体制（経営層参画・専任部署・コーディネーター配置の説明）",
    "s23_kigyorenkei": "企業/エコシステムとの連携（受講生派遣確約・議論体制・効果調査・学修者意欲向上工夫の具体的説明）",
    "s23_program": "プログラム開発・実施の詳細（産業成長への貢献・VR実習の優位性・170人達成計画・座学+VR実習の設計・デジタルバッジ発行・オンライン受講の工夫）",
    "s23_senzai": "企業ニーズの把握と反映（具体的な企業ニーズ・プログラムへの反映方法）",
    "s23_kigyozoukyou": "連携企業を増やす工夫（アプローチ戦略・拡大計画）",
    "s23_jisoka": "自走化目標像（2〜4年後のプログラム自立運営の姿・受講者数・収支目標）",
    "s23_jisoka_plan": "自走化に向けた取組計画（年度別アクションプラン：2年目・3年目・4年目）",
    "s23_jisoka_zaimu": "財務計画（2年目：受講料収入〇〇千円・コスト〇〇千円、3年目：〜、4年目：収支均衡〜）",
    "s23_jisoka_jinzai": "人員確保計画（コーディネーター育成・配置計画）",
    "s23_schedule": "R8年度スケジュール（前期：体制整備・VRコンテンツ開発・試行実施、後期：本格実施・効果測定・改善）",
    "s23_bansosien": "伴走支援に期待する内容（企業マッチング支援・プログラム改善アドバイス等）と解決したい課題",
    "s23_kadai1": "就職氷河期世代等の支援：リスキリングによる再就職・キャリア転換への取組",
    "s23_kadai2": "地方人材確保：オンラインVR学習による地方在住者の受講機会創出",
    "s23_kadai3": "スキルの可視化・処遇改善：VRログデータ・デジタルバッジによる習熟度の定量化と人事評価への連携",
    "s23_kadai6": "修士・博士課程への接続：BP認定を見据えたカリキュラム設計"
  },
  "programs": [
    { "name": "プログラム名1", "target": "対象者・受講者層", "teiin": "60", "ryokin": "150000", "naiyou": "プログラムの内容・VR活用の具体的方法" },
    { "name": "プログラム名2", "target": "対象者・受講者層", "teiin": "50", "ryokin": "200000", "naiyou": "プログラムの内容・特徴" }
  ],
  "keihi": {
    "kb1": { "hojo": 2400, "futan": 0, "naiyou": "VRゴーグル（Meta Quest Pro）30台" },
    "kb2": { "hojo": 300, "futan": 0, "naiyou": "消耗品費（記録媒体・周辺機器等）" },
    "kb3": { "hojo": 5000, "futan": 2000, "naiyou": "プロジェクトコーディネーター・事業責任者人件費" },
    "kb4": { "hojo": 1500, "futan": 500, "naiyou": "外部専門家・企業講師謝金" },
    "kb5": { "hojo": 400, "futan": 0, "naiyou": "国内旅費（企業訪問・視察・成果発表）" },
    "kb6": { "hojo": 27400, "futan": 0, "naiyou": "VRコンテンツ制作委託費（株式会社ジョリーグッド）3本、JollyGood+プラットフォーム利用料・導入研修費" },
    "kb7": { "hojo": 200, "futan": 0, "naiyou": "成果報告書・プログラム案内印刷費" },
    "kb8": { "hojo": 100, "futan": 0, "naiyou": "通信・運搬費" },
    "kb9": { "hojo": 2200, "futan": 0, "naiyou": "広報費・受講者管理システム費・デジタルバッジ発行費" }
  }
}
EOT;

    $userPrompt = "大学名: {$name}";
    if ($region) $userPrompt .= "\n地域: {$region}";
    $userPrompt .= "\n事業テーマ: {$theme}";

    return $systemPrompt . "\n\n" . $userPrompt;
}
?>