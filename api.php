<?php
// api.php - データの保存・読み込みAPI (MySQL版)
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// アクションの取得
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// IDのバリデーション（英数字とハイフンのみ許可）
if (!preg_match('/^[a-zA-Z0-9-]+$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// --- データ取得 (GET) ---
if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT data, name FROM universities WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        echo $row['data'] ?: json_encode(['_uni' => $row['name'], 'fields' => new stdClass()]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }
    exit;
}

// --- データ保存 (POST) ---
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // データベースを更新
    $stmt = $pdo->prepare("UPDATE universities SET data = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$input, $id])) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update database']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);