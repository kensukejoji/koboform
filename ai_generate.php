<?php
// ai_generate.php - AIによる申請書生成API（index.php から呼ばれる）
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$theme  = trim($input['theme']  ?? '');
$region = trim($input['region'] ?? '');
$name   = trim($input['name']   ?? '');
$menu   = trim($input['menu']   ?? 'menu1');

if (!$theme) {
    http_response_code(400);
    echo json_encode(['error' => '事業テーマを入力してください']);
    exit;
}

$prompt = $menu === 'menu2'
    ? buildGeminiPromptMenu2($name, $region, $theme)
    : buildGeminiPrompt($name, $region, $theme);
$data   = callGeminiApi($prompt);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
