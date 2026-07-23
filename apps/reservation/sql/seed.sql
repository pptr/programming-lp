-- ============================================================
-- NK Works 予約システム
-- seed.sql
--
-- 前提:
--   schema.sql を実行済みであること
--
-- 内容:
--   1. サービス初期データ
--   2. 通常営業時間
--   3. 非機密システム設定
--
-- 注意:
--   Stripe秘密鍵、SMTPパスワード、Google Meet URL、
--   銀行口座番号などの機密情報は投入しません。
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

USE `nkworks`;

START TRANSACTION;

-- ============================================================
-- 1. services
-- ============================================================

INSERT INTO `services` (
    `service_code`,
    `name`,
    `delivery_method`,
    `lesson_duration_minutes`,
    `buffer_before_minutes`,
    `buffer_after_minutes`,
    `price_yen`,
    `requires_payment`,
    `sort_order`,
    `is_active`
) VALUES
(
    'free_consultation_online',
    '無料相談',
    'online',
    30,
    30,
    30,
    0,
    FALSE,
    10,
    TRUE
),
(
    'trial_online',
    '体験レッスン',
    'online',
    60,
    30,
    30,
    0,
    FALSE,
    20,
    TRUE
),
(
    'trial_visit',
    '体験レッスン',
    'visit',
    60,
    30,
    30,
    1000,
    TRUE,
    30,
    TRUE
),
(
    'lesson_online',
    '通常レッスン',
    'online',
    60,
    30,
    30,
    3000,
    TRUE,
    40,
    TRUE
),
(
    'lesson_visit',
    '通常レッスン',
    'visit',
    60,
    30,
    30,
    4000,
    TRUE,
    50,
    TRUE
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `delivery_method` = VALUES(`delivery_method`),
    `lesson_duration_minutes` = VALUES(`lesson_duration_minutes`),
    `buffer_before_minutes` = VALUES(`buffer_before_minutes`),
    `buffer_after_minutes` = VALUES(`buffer_after_minutes`),
    `price_yen` = VALUES(`price_yen`),
    `requires_payment` = VALUES(`requires_payment`),
    `sort_order` = VALUES(`sort_order`),
    `is_active` = VALUES(`is_active`);

-- ============================================================
-- 2. availability_rules
--
-- day_of_week:
--   0 = 日曜日
--   1 = 月曜日
--   2 = 火曜日
--   3 = 水曜日
--   4 = 木曜日
--   5 = 金曜日
--   6 = 土曜日
--
-- 営業日:
--   水曜日〜日曜日
--
-- 営業時間:
--   09:00〜23:00
-- ============================================================

DELETE FROM `availability_rules`;

INSERT INTO `availability_rules` (
    `day_of_week`,
    `start_time`,
    `end_time`,
    `is_active`
) VALUES
    (0, '09:00:00', '23:00:00', TRUE),
    (3, '09:00:00', '23:00:00', TRUE),
    (4, '09:00:00', '23:00:00', TRUE),
    (5, '09:00:00', '23:00:00', TRUE),
    (6, '09:00:00', '23:00:00', TRUE);

-- ============================================================
-- 3. system_settings
--
-- 非機密かつ、将来管理画面から変更する可能性がある設定だけを保存。
-- 機密情報は .env またはサーバー上の非公開設定ファイルで管理する。
-- ============================================================

INSERT INTO `system_settings` (
    `setting_key`,
    `setting_value`,
    `value_type`,
    `description`,
    `is_public`
) VALUES
(
    'reservation_accept_from_days',
    '1',
    'integer',
    '予約受付開始日。今日から何日後以降を予約可能とするか',
    FALSE
),
(
    'reservation_accept_until_days',
    '30',
    'integer',
    '予約受付上限。今日から何日先まで予約可能とするか',
    FALSE
),
(
    'reservation_deadline_hours',
    '24',
    'integer',
    '予約受付締切。開始何時間前まで予約可能とするか',
    FALSE
),
(
    'reservation_change_deadline_hours',
    '24',
    'integer',
    '予約変更締切。開始何時間前まで変更可能とするか',
    FALSE
),
(
    'stripe_payment_timeout_minutes',
    '30',
    'integer',
    'Stripe未決済予約の保持時間',
    FALSE
),
(
    'bank_transfer_application_deadline_days',
    '3',
    'integer',
    '銀行振込期限の申込日起算日数',
    FALSE
),
(
    'bank_transfer_reservation_deadline_days',
    '2',
    'integer',
    '銀行振込期限の予約日起算日数',
    FALSE
),
(
    'slot_interval_minutes',
    '30',
    'integer',
    '予約枠の単位時間',
    FALSE
),
(
    'allow_customer_cancel_before_payment',
    'true',
    'boolean',
    '支払い前予約の利用者キャンセル可否',
    FALSE
),
(
    'allow_customer_reschedule_after_confirmation',
    'true',
    'boolean',
    '確定済み予約の利用者日程変更可否',
    FALSE
),
(
    'enable_refund',
    'false',
    'boolean',
    '返金機能の有効化。初期版では無効',
    FALSE
),
(
    'site_name',
    'NK Works',
    'string',
    'サイト表示名',
    TRUE
),
(
    'reservation_timezone',
    'Asia/Tokyo',
    'string',
    '予約日時の基準タイムゾーン',
    FALSE
)
ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`),
    `value_type` = VALUES(`value_type`),
    `description` = VALUES(`description`),
    `is_public` = VALUES(`is_public`);

COMMIT;

-- ============================================================
-- 投入結果確認
-- ============================================================

SELECT
    `id`,
    `service_code`,
    `name`,
    `delivery_method`,
    `lesson_duration_minutes`,
    `buffer_before_minutes`,
    `buffer_after_minutes`,
    `price_yen`,
    `requires_payment`,
    `is_active`
FROM `services`
ORDER BY `sort_order`, `id`;

SELECT
    `id`,
    `day_of_week`,
    `start_time`,
    `end_time`,
    `is_active`
FROM `availability_rules`
ORDER BY `day_of_week`, `start_time`;

SELECT
    `setting_key`,
    `setting_value`,
    `value_type`,
    `is_public`
FROM `system_settings`
ORDER BY `setting_key`;
