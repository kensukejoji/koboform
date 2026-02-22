# koboform - 産学連携リ・スキリング申請書作成ツール

文部科学省「産学連携リ・スキリング・エコシステム構築事業」の申請書をオンラインで作成するためのWebアプリケーション。
JollyGood社員（管理者）が大学ごとに専用URLを発行し、大学担当者がフォームに入力する。Gemini AIによる文案自動生成・チャットサポート機能付き。

## デプロイ情報

| 項目 | 値 |
|------|------|
| URL | https://form.jollygoodplus.com/reskiling2603/ |
| サーバー | Xserver (sv16602.xserver.jp) |
| SSH | `jollygood25s@sv16602.xserver.jp -p 10022` |
| リモートパス | `/home/jollygood25s/jollygoodplus.com/public_html/form.jollygoodplus.com/reskiling2603/` |
| デプロイ方法 | `./deploy.sh`（rsync） |

### デプロイ手順

```bash
ssh-add ~/.ssh/id_ed25519   # SSHキーをエージェントに登録
./deploy.sh                 # rsync でサーバーに同期
```

## 技術スタック

- **バックエンド**: PHP（フレームワークなし）
- **フロントエンド**: HTML + Tailwind CSS + Vanilla JavaScript
- **データベース**: MySQL（Xserver）
- **AI**: Google Gemini API（gemini-2.0-flash）
- **ホスティング**: Xserver

## データベース

| 項目 | 値 |
|------|------|
| DB名 | `jollygood25s_koboform` |
| テーブル | `universities` |

### universities テーブル

| カラム | 型 | 説明 |
|--------|------|------|
| id | varchar(32) | 大学識別子（URL用） |
| name | varchar(255) | 大学名 |
| data | longtext | 申請データ（JSON） |
| created_at | datetime | 作成日時 |
| updated_at | datetime | 更新日時 |

## ファイル構成

```
koboform/
├── admin.php           # 管理者ダッシュボード
├── index.php           # 大学入力フォーム（メイン）
├── api.php             # データ保存・取得API
├── ai_generate.php     # AI文案生成API
├── ai_chat.php         # AIチャットAPI
├── db_config.php       # DB接続設定・APIキー・AI関数群（git未追跡）
├── register.php        # 大学自己登録ページ
├── diagnose.php        # メニュー診断チャート
├── schedule.php        # スケジュール・申込方法ページ
├── nav.php             # 共通ナビゲーションバー
├── footer.php          # 共通フッター
├── slide_generate.php  # スライド構成案生成API
├── deploy.sh           # Xserverデプロイスクリプト
├── setup_db.php        # DB初期セットアップ（初回のみ）
├── .htaccess           # Apache設定
├── htaccess            # htaccessバックアップ
├── favicon.ico         # ファビコン
├── mascot.gif          # マスコットキャラクター画像
├── index.html          # 旧バージョン（デプロイ除外）
├── .gitignore
├── PROJECT.md          # このファイル
├── uploads/
│   └── ai_knowledge/   # AI教師データ（PDF/URL抽出テキスト）
└── R7Reskiling/        # 参考資料（git未追跡・デプロイ除外）
    ├── 01_事業概要/
    ├── 02_応募要項/
    ├── 03_申請様式/
    ├── 04_説明会/
    ├── 10_R7リスキリング資料/
    ├── 11_会社資料/
    └── 12_キャラクター/
```

## 各ファイルの役割

### admin.php - 管理者ダッシュボード

JollyGood社員がパスワード認証（`jg2026`）でログインし、以下の操作を行う。

- 登録済み大学の一覧表示（進捗バー、メニュー表示）
- 新規大学登録（テーマ必須、AI自動生成、PDF添付対応）
- AI再生成（テーマ・地域・PDF差し替え）
- AI教師データ管理（PDF/URLアップロード、テキスト抽出、削除）
- 大学データ削除

**依存**: db_config.php

### index.php - 大学入力フォーム

`?id=xxx` で特定大学のデータを読み込み、複数の様式をタブで切り替えながら入力する。

- **様式1-1**: 提出状（日付、大学名、学長名）
- **様式1-2**: 基本情報（実施主体、事業者、申請者、事業名、経費等）
- **様式1-3**: 事業実施委員会（委員会名、目的、委員10名）
- **様式2**: 企画提案書（体制、プログラム一覧、課題対応、自走化）
- **様式3**: 申請経費（補助金・負担・事業規模）
- AIチャットウィジェット（画面右下、リアルタイム文案提案）
- 申請様式PDF出力機能
- JSONダウンロード

**依存**: db_config.php, api.php, ai_generate.php, ai_chat.php

### api.php - データAPI

シンプルなCRUD API。

- `GET ?action=get&id=xxx` - 大学データ取得（JSON）
- `POST ?action=save&id=xxx` - 大学データ保存（JSON）

**依存**: db_config.php

### ai_generate.php - AI文案生成API

index.phpからの初期文案生成リクエストを処理。

- `POST` - テーマ・地域・大学名からGemini APIで全フィールドの文案を一括生成

**依存**: db_config.php

### ai_chat.php - AIチャットAPI

申請書入力ページ・登録ページのチャットボット。

- `POST` - 会話履歴・大学データ・現在のコンテキストを受け取り、Gemini APIで回答生成
- AI教師データ（`uploads/ai_knowledge/*.txt`）をナレッジベースとして参照

**依存**: db_config.php

### register.php - 大学自己登録ページ

大学側が自ら登録するためのページ。

- 招待コード入力 → 大学情報フォーム → AI初期生成 → DB登録 → フォームURL発行
- メール＆Slack通知（オプション）
- チャットウィジェット付き

**依存**: db_config.php, nav.php, footer.php, ai_chat.php

### diagnose.php - メニュー診断チャート

5つの質問で最適な申請メニューを診断する。

- 質問: パートナータイプ、教育テーマ、ターゲット人材、ゴール、大学タイプ
- 結果: 「メニュー1 地方創生」or「メニュー2 産業成長」+ スコア表示
- Confetti効果付き

**依存**: nav.php, footer.php

### schedule.php - スケジュール・申込方法ページ

公募スケジュール、参加表明方法、企画提案書の提出方法等の情報を掲載。

**依存**: nav.php, footer.php

### db_config.php - DB接続設定・AI関数群（git未追跡）

MySQL接続情報、Gemini APIキー、申請書生成用プロンプト・AI呼び出し関数群を定義。

- `buildGeminiPrompt()` - メニュー1地方創生用プロンプト
- `buildGeminiPromptMenu2()` - メニュー2産業成長用プロンプト
- `callGeminiApi()` - JSON生成モード
- `callGeminiApiText()` - テキスト生成モード
- `callGeminiRaw()` - 低レベル呼び出し（レート制限リトライ付き）
- `callGeminiRawMultimodal()` - PDFマルチモーダル対応
- `callGeminiApiWithPdfs()` - PDF付き呼び出し
- 招待コード定義（`$INVITE_CODE = 'jg2026'`）

### nav.php / footer.php - 共通UI部品

ナビゲーションバー（schedule, diagnose, register等へのリンク、ドロップダウン）とフッター（3列リンク集）。

### slide_generate.php - スライド構成案生成API

Geminiのテキストモードでスライド構成案を生成する。

**依存**: db_config.php

### deploy.sh - デプロイスクリプト

rsyncでXserverにファイルを同期。以下を除外:
`.git`, `.gitignore`, `.claude/`, `.DS_Store`, `R7Reskiling/`, `deploy.sh`, `node_modules/`, `*.mov`, `data/`, `index.html`, `setup_db.php`, `*.pdf`

## アーキテクチャ

```
                    ┌─────────────────────────────────┐
                    │         Gemini API               │
                    │      (gemini-2.0-flash)          │
                    └──────────┬──────────────────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
   ai_generate.php        ai_chat.php           db_config.php
   (文案生成)            (チャット)           (AI関数・DB設定)
        │                      │                      │
        └──────────┬───────────┘                      │
                   │                                  │
┌─────────────┐    │    ┌─────────────┐               │
│  admin.php  │────┼────│  index.php  │───── api.php ─┤
│ (管理者)    │    │    │ (入力フォーム) │               │
└─────────────┘    │    └─────────────┘               │
                   │                                  │
┌─────────────┐    │    ┌─────────────┐               │
│register.php │────┘    │schedule.php │               │
│(大学登録)   │         │(スケジュール)│               │
└─────────────┘         └─────────────┘               │
                                                      │
┌─────────────┐                              ┌────────┴───────┐
│diagnose.php │                              │   MySQL DB     │
│(メニュー診断)│                              │ universities   │
└─────────────┘                              └────────────────┘

共通部品: nav.php + footer.php
ファイル: uploads/ai_knowledge/ (AI教師データ)
```

## 申請メニュー

| メニュー | 名称 | 概要 |
|---------|------|------|
| メニュー1 | 地方創生 | 地方大学×自治体連携、地域課題解決型リスキリング |
| メニュー2 | 産業成長 | 都市/地方大学×企業連携、高度専門人材育成 |

## .gitignore で除外されているファイル

- `.DS_Store` - macOS生成ファイル
- `.claude/` - Claude IDE設定
- `R7Reskiling/` - 参考資料（大容量）
- `*.mov` - 動画ファイル
- `node_modules/` - npm パッケージ
- `db_config.php` - DBパスワード＆APIキー（セキュリティ上git未追跡）

## 注意事項

- `db_config.php` はDBパスワードとGemini APIキーを含むため、gitリポジトリには含まれない。サーバーには直接デプロイされる。
- `uploads/` ディレクトリはAI教師データの保存先。サーバー上で動的に生成される。
- `R7Reskiling/` は文科省の公募資料やJollyGood社内資料を格納。ローカルのみで管理。
