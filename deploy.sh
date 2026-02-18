#!/bin/bash
# ============================================================
# deploy.sh — koboform を Xserver へデプロイするスクリプト
# 使い方: ./deploy.sh
# ============================================================

set -e  # エラーが出たら即停止

# ---- 設定（必要に応じて変更） ----
XSERVER_USER="jollygood25s"               # Xserverのユーザー名（サーバーID）
XSERVER_SSH_HOST="sv16602.xserver.jp"     # SSH接続ホスト
XSERVER_PORT=10022                        # XserverのSSHポート（固定）
DEPLOY_DOMAIN="jollygoodplus.com"         # デプロイ先ドメイン
# Xserver上の公開ディレクトリ（ドメインのpublic_html以下）
REMOTE_DIR="/home/${XSERVER_USER}/${DEPLOY_DOMAIN}/public_html/koboform"
LOCAL_DIR="$(cd "$(dirname "$0")" && pwd)" # このスクリプトのディレクトリ

# アップロード対象（R7Reskiling等の大容量ファイルは除外）
RSYNC_OPTS=(
  -avz
  --progress
  -e "ssh -p ${XSERVER_PORT} -o StrictHostKeyChecking=accept-new"
  --exclude='.git'
  --exclude='.gitignore'
  --exclude='.claude/'
  --exclude='.DS_Store'
  --exclude='R7Reskiling/'
  --exclude='deploy.sh'
  --exclude='node_modules/'
  --exclude='*.mov'
  --exclude='*.pdf'
)

echo ""
echo "🚀 koboform デプロイ開始"
echo "   送信先: ${XSERVER_USER}@${XSERVER_SSH_HOST}:${REMOTE_DIR}"
echo ""

# Git の状態確認（ローカルの変更があれば警告）
if git -C "${LOCAL_DIR}" status --porcelain | grep -q .; then
  echo "⚠️  未コミットの変更があります:"
  git -C "${LOCAL_DIR}" status --short
  read -p "   このまま続けますか？ (y/N): " confirm
  [[ "$confirm" =~ ^[Yy]$ ]] || { echo "中断しました。"; exit 1; }
fi

# リモートにディレクトリを作成（初回のみ必要）
echo "📁 リモートディレクトリ確認..."
ssh -p "${XSERVER_PORT}" "${XSERVER_USER}@${XSERVER_SSH_HOST}" "mkdir -p ${REMOTE_DIR}"

# rsync でファイルを同期
echo "📤 ファイルを同期中..."
rsync "${RSYNC_OPTS[@]}" \
  "${LOCAL_DIR}/" \
  "${XSERVER_USER}@${XSERVER_SSH_HOST}:${REMOTE_DIR}/"

echo ""
echo "✅ デプロイ完了！"
echo "   URL: https://${DEPLOY_DOMAIN}/koboform/"
echo ""
