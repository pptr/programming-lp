# NK Works 問い合わせフォーム セットアップ

## 配置構成

リポジトリのルートを、さくらレンタルサーバーの `~/www` に配置します。

```text
~/www/
├── apps/                 # URLからのアクセスは禁止
│   └── contact/
├── contact/              # 公開フォーム
├── css/contact.css
├── composer.json
├── vendor/               # Composerが生成
└── index.html など
```

## 1. サーバーへ反映

既存の `~/www` がGitリポジトリなら、リポジトリ構成を更新後に以下を実行します。

```bash
cd ~/www
git pull
```

新規cloneの場合は、既存ファイルを退避してから実施してください。

```bash
cd ~
mv www www_backup
git clone git@github.com:pptr/programming-lp.git www
```

## 2. PHPMailer導入

```bash
cd ~/www
composer install --no-dev --optimize-autoloader
```

## 3. SMTP設定

```bash
cp ~/www/apps/contact/config/mail.example.php \
   ~/www/apps/contact/config/mail.php
nano ~/www/apps/contact/config/mail.php
```

`host`、`username`、`password` を実際のメール設定へ変更します。`mail.php` は `.gitignore` の対象です。

## 4. ログディレクトリ

```bash
chmod 700 ~/www/apps/contact/log
```

ログは `apps/contact/log/contact-YYYY-MM.log` に出力されます。

## 5. アクセス禁止確認

次が403になることを確認してください。

```text
https://nkworks.info/apps/
```

## 6. フォーム確認

```text
https://nkworks.info/contact/
```
