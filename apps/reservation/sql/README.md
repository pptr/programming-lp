# NK Works 予約システム DBセットアップ

## 構成

```
database/
├── schema.sql
├── seed.sql
├── drop.sql
└── README.md
```

## 動作環境

- MySQL 8.0.16以上
- utf8mb4
- InnoDB
- タイムゾーン: Asia/Tokyo

---

## 初回セットアップ

```bash
mysql -u root -p < schema.sql
mysql -u root -p < seed.sql
```

---

## 開発中にDBを作り直す

```bash
mysql -u root -p < drop.sql
mysql -u root -p < schema.sql
mysql -u root -p < seed.sql
```

---

## テーブル一覧

1. services
2. availability_rules
3. schedule_blocks
4. reservation_number_counters
5. reservations
6. reservation_slots
7. payments
8. reservation_histories
9. stripe_webhook_events
10. mail_logs
11. system_settings

---

## 設計方針

- サービス情報は予約時点の内容をスナップショット保存
- 30分単位の reservation_slots で重複予約をDBレベルで防止
- payments を独立させ決済履歴を保持
- reservation_histories ですべての変更履歴を記録
- Stripe Webhook はイベントIDで冪等処理
- メール本文は保存せず送信結果のみ記録

---

## 機密情報

以下はDBへ保存しません。

- Stripe Secret Key
- Stripe Webhook Secret
- SMTPパスワード
- DBパスワード
- Google Meet URL
- 銀行口座情報

これらは .env またはサーバー側の非公開設定ファイルで管理します。

---

## 今後の実装順

1. Databaseクラス(PDO)
2. Configクラス
3. Repository層
4. Service層
5. 予約画面
6. 管理画面
7. Stripe Checkout
8. Webhook
9. cron
10. メール送信

---

## 備考

このDBは NK Works 予約システム専用です。
素のPHP + PDO + PHPMailer + Stripe SDK を前提とした構成になっています。
