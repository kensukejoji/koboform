<?php
// setup_db.php - データベース初期設定用スクリプト
require_once 'db_config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `universities` (
      `id` varchar(32) NOT NULL,
      `name` varchar(255) NOT NULL,
      `data` longtext,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "<h1>✅ テーブル 'universities' の作成に成功しました！</h1>";
    echo "<p>セキュリティのため、このファイル（setup_db.php）はサーバーから削除してください。</p>";
    echo '<p><a href="admin.php" style="font-weight:bold; color:blue;">管理画面へ移動する</a></p>';
} catch (PDOException $e) {
    echo "<h1>❌ エラーが発生しました</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>db_config.php のパスワードが正しいか確認してください。</p>";
}
?>