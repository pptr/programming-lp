-- ============================================================
-- NK Works 予約システム schema.sql
-- MySQL 8.0.16+ / utf8mb4 / Asia/Tokyo
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

-- CREATE DATABASE IF NOT EXISTS `nkworks`
--  CHARACTER SET utf8mb4
--  COLLATE utf8mb4_0900_ai_ci;

USE `nkworks-dev_school`;

CREATE TABLE `services` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'サービスID',
    `service_code` VARCHAR(50) NOT NULL COMMENT '内部識別コード',
    `name` VARCHAR(100) NOT NULL COMMENT '表示名',
    `delivery_method` VARCHAR(20) NOT NULL COMMENT 'online または visit',
    `lesson_duration_minutes` SMALLINT UNSIGNED NOT NULL COMMENT '実施時間（分）',
    `buffer_before_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT '前バッファ（分）',
    `buffer_after_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT '後バッファ（分）',
    `price_yen` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '税込価格（円）',
    `requires_payment` BOOLEAN NOT NULL DEFAULT FALSE COMMENT '決済要否',
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT '予約受付可否',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_services_service_code` (`service_code`),
    KEY `idx_services_active_sort` (`is_active`, `sort_order`),
    CONSTRAINT `chk_services_delivery_method` CHECK (`delivery_method` IN ('online', 'visit')),
    CONSTRAINT `chk_services_lesson_duration` CHECK (`lesson_duration_minutes` > 0 AND MOD(`lesson_duration_minutes`, 30) = 0),
    CONSTRAINT `chk_services_buffer_before` CHECK (MOD(`buffer_before_minutes`, 30) = 0),
    CONSTRAINT `chk_services_buffer_after` CHECK (MOD(`buffer_after_minutes`, 30) = 0),
    CONSTRAINT `chk_services_payment_consistency` CHECK (
        (`price_yen` = 0 AND `requires_payment` = FALSE)
        OR (`price_yen` > 0 AND `requires_payment` = TRUE)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='サービスマスタ';

CREATE TABLE `availability_rules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '営業時間ルールID',
    `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '0=日曜, 1=月曜, ... 6=土曜',
    `start_time` TIME NOT NULL COMMENT '営業開始時刻',
    `end_time` TIME NOT NULL COMMENT '営業終了時刻',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT '有効フラグ',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`id`),
    KEY `idx_availability_rules_day_active` (`day_of_week`, `is_active`, `start_time`, `end_time`),
    CONSTRAINT `chk_availability_rules_day` CHECK (`day_of_week` BETWEEN 0 AND 6),
    CONSTRAINT `chk_availability_rules_time_range` CHECK (`start_time` < `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='曜日別通常営業時間';

CREATE TABLE `schedule_blocks` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '予約停止枠ID',
    `block_start_at` DATETIME NOT NULL COMMENT '停止開始日時',
    `block_end_at` DATETIME NOT NULL COMMENT '停止終了日時',
    `reason` VARCHAR(255) NULL COMMENT '管理者向け理由',
    `is_all_day` BOOLEAN NOT NULL DEFAULT FALSE COMMENT '終日フラグ',
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT '有効フラグ',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`id`),
    KEY `idx_schedule_blocks_range_active` (`block_start_at`, `block_end_at`, `is_active`),
    CONSTRAINT `chk_schedule_blocks_range` CHECK (`block_start_at` < `block_end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='予約停止枠';

CREATE TABLE `reservation_number_counters` (
    `issue_date` DATE NOT NULL COMMENT '採番日',
    `last_number` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最終採番値',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`issue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='予約番号日別採番';

CREATE TABLE `reservations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '予約ID',
    `reservation_code` VARCHAR(20) NOT NULL COMMENT '予約番号 RYYYYMMDD-00001',
    `service_id` BIGINT UNSIGNED NOT NULL COMMENT 'サービスID',
    `service_name_snapshot` VARCHAR(100) NOT NULL COMMENT '予約時点のサービス名',
    `delivery_method` VARCHAR(20) NOT NULL COMMENT 'online または visit',
    `lesson_duration_minutes` SMALLINT UNSIGNED NOT NULL COMMENT '予約時点の実施時間',
    `buffer_before_minutes` SMALLINT UNSIGNED NOT NULL COMMENT '予約時点の前バッファ',
    `buffer_after_minutes` SMALLINT UNSIGNED NOT NULL COMMENT '予約時点の後バッファ',
    `price_yen` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '予約時点の税込価格',
    `start_at` DATETIME NOT NULL COMMENT 'レッスン開始日時',
    `end_at` DATETIME NOT NULL COMMENT 'レッスン終了日時',
    `occupied_start_at` DATETIME NOT NULL COMMENT 'バッファ込み占有開始日時',
    `occupied_end_at` DATETIME NOT NULL COMMENT 'バッファ込み占有終了日時',
    `customer_name` VARCHAR(100) NOT NULL COMMENT '受講者氏名',
    `customer_name_kana` VARCHAR(100) NOT NULL COMMENT '受講者氏名かな',
    `parent_name` VARCHAR(100) NULL COMMENT '保護者氏名',
    `parent_name_kana` VARCHAR(100) NULL COMMENT '保護者氏名かな',
    `email` VARCHAR(254) NOT NULL COMMENT 'メールアドレス',
    `phone` VARCHAR(30) NULL COMMENT '電話番号',
    `postal_code` VARCHAR(10) NULL COMMENT '郵便番号',
    `address` VARCHAR(500) NULL COMMENT '訪問先住所',
    `customer_category` VARCHAR(30) NOT NULL COMMENT '中学生・高校生・大学生・社会人等',
    `experience_level` VARCHAR(30) NOT NULL COMMENT '未経験・初心者・経験あり等',
    `request_text` TEXT NULL COMMENT '希望内容・相談事項',
    `payment_method` VARCHAR(20) NULL COMMENT 'stripe または bank_transfer。無料はNULL',
    `payment_status` VARCHAR(20) NOT NULL COMMENT 'not_required, unpaid, paid, failed',
    `payment_deadline` DATETIME NULL COMMENT '支払期限',
    `status` VARCHAR(30) NOT NULL COMMENT '予約状態',
    `management_token_hash` CHAR(64) NOT NULL COMMENT '利用者管理トークンSHA-256',
    `management_token_expires_at` DATETIME NULL COMMENT '管理トークン失効日時',
    `confirmed_at` DATETIME NULL COMMENT '予約確定日時',
    `cancelled_at` DATETIME NULL COMMENT 'キャンセル日時',
    `expired_at` DATETIME NULL COMMENT '期限切れ日時',
    `completed_at` DATETIME NULL COMMENT '実施完了日時',
    `admin_note` TEXT NULL COMMENT '管理者メモ',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reservations_reservation_code` (`reservation_code`),
    UNIQUE KEY `uq_reservations_management_token_hash` (`management_token_hash`),
    KEY `idx_reservations_start_status` (`start_at`, `status`),
    KEY `idx_reservations_status_deadline` (`status`, `payment_deadline`),
    KEY `idx_reservations_payment_status` (`payment_status`, `payment_deadline`),
    KEY `idx_reservations_email` (`email`),
    KEY `idx_reservations_service_start` (`service_id`, `start_at`),
    KEY `idx_reservations_created_at` (`created_at`),
    CONSTRAINT `fk_reservations_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `chk_reservations_delivery_method` CHECK (`delivery_method` IN ('online', 'visit')),
    CONSTRAINT `chk_reservations_payment_method` CHECK (`payment_method` IS NULL OR `payment_method` IN ('stripe', 'bank_transfer')),
    CONSTRAINT `chk_reservations_payment_status` CHECK (`payment_status` IN ('not_required', 'unpaid', 'paid', 'failed')),
    CONSTRAINT `chk_reservations_status` CHECK (`status` IN ('pending', 'pending_payment', 'confirmed', 'expired', 'cancelled', 'completed', 'no_show')),
    CONSTRAINT `chk_reservations_time_range` CHECK (`occupied_start_at` <= `start_at` AND `start_at` < `end_at` AND `end_at` <= `occupied_end_at`),
    CONSTRAINT `chk_reservations_duration` CHECK (TIMESTAMPDIFF(MINUTE, `start_at`, `end_at`) = `lesson_duration_minutes`),
    CONSTRAINT `chk_reservations_occupied_duration` CHECK (
        TIMESTAMPDIFF(MINUTE, `occupied_start_at`, `start_at`) = `buffer_before_minutes`
        AND TIMESTAMPDIFF(MINUTE, `end_at`, `occupied_end_at`) = `buffer_after_minutes`
    ),
    CONSTRAINT `chk_reservations_payment_consistency` CHECK (
        (`price_yen` = 0 AND `payment_method` IS NULL AND `payment_status` = 'not_required')
        OR (`price_yen` > 0 AND `payment_method` IN ('stripe', 'bank_transfer') AND `payment_status` IN ('unpaid', 'paid', 'failed'))
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='予約本体';

CREATE TABLE `reservation_slots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '予約占有枠ID',
    `reservation_id` BIGINT UNSIGNED NOT NULL COMMENT '予約ID',
    `slot_start_at` DATETIME NOT NULL COMMENT '30分枠開始日時',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reservation_slots_slot_start_at` (`slot_start_at`),
    UNIQUE KEY `uq_reservation_slots_reservation_slot` (`reservation_id`, `slot_start_at`),
    KEY `idx_reservation_slots_reservation_id` (`reservation_id`),
    CONSTRAINT `fk_reservation_slots_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `chk_reservation_slots_30_minutes` CHECK (MINUTE(`slot_start_at`) IN (0, 30) AND SECOND(`slot_start_at`) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='30分単位の予約占有枠';

CREATE TABLE `payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '決済ID',
    `reservation_id` BIGINT UNSIGNED NOT NULL COMMENT '予約ID',
    `payment_method` VARCHAR(20) NOT NULL COMMENT 'stripe または bank_transfer',
    `amount_yen` INT UNSIGNED NOT NULL COMMENT '支払金額',
    `status` VARCHAR(20) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid, paid, failed, expired',
    `stripe_checkout_session_id` VARCHAR(255) NULL COMMENT 'Stripe Checkout Session ID',
    `stripe_payment_intent_id` VARCHAR(255) NULL COMMENT 'Stripe PaymentIntent ID',
    `deadline_at` DATETIME NULL COMMENT '支払期限',
    `paid_at` DATETIME NULL COMMENT '入金確定日時',
    `failed_at` DATETIME NULL COMMENT '決済失敗日時',
    `expired_at` DATETIME NULL COMMENT '期限切れ日時',
    `confirmed_by` VARCHAR(50) NULL COMMENT '銀行振込確認者',
    `note` VARCHAR(500) NULL COMMENT '管理メモ',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_payments_checkout_session_id` (`stripe_checkout_session_id`),
    UNIQUE KEY `uq_payments_payment_intent_id` (`stripe_payment_intent_id`),
    KEY `idx_payments_reservation_created` (`reservation_id`, `created_at`),
    KEY `idx_payments_status_deadline` (`status`, `deadline_at`),
    CONSTRAINT `fk_payments_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `chk_payments_method` CHECK (`payment_method` IN ('stripe', 'bank_transfer')),
    CONSTRAINT `chk_payments_status` CHECK (`status` IN ('unpaid', 'paid', 'failed', 'expired')),
    CONSTRAINT `chk_payments_amount` CHECK (`amount_yen` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='決済履歴';

CREATE TABLE `reservation_histories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '予約履歴ID',
    `reservation_id` BIGINT UNSIGNED NOT NULL COMMENT '予約ID',
    `action_type` VARCHAR(30) NOT NULL COMMENT '操作種別',
    `actor_type` VARCHAR(20) NOT NULL COMMENT 'customer, admin, system',
    `old_status` VARCHAR(30) NULL COMMENT '変更前状態',
    `new_status` VARCHAR(30) NULL COMMENT '変更後状態',
    `old_start_at` DATETIME NULL COMMENT '変更前開始日時',
    `old_end_at` DATETIME NULL COMMENT '変更前終了日時',
    `new_start_at` DATETIME NULL COMMENT '変更後開始日時',
    `new_end_at` DATETIME NULL COMMENT '変更後終了日時',
    `detail_json` JSON NULL COMMENT 'その他差分・補足情報',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '記録日時',
    PRIMARY KEY (`id`),
    KEY `idx_reservation_histories_reservation_created` (`reservation_id`, `created_at`),
    CONSTRAINT `fk_reservation_histories_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `chk_reservation_histories_action_type` CHECK (`action_type` IN ('created', 'rescheduled', 'cancelled', 'status_changed', 'payment_confirmed', 'payment_failed', 'expired', 'completed', 'no_show')),
    CONSTRAINT `chk_reservation_histories_actor_type` CHECK (`actor_type` IN ('customer', 'admin', 'system'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='予約変更履歴';

CREATE TABLE `stripe_webhook_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Webhook受信ID',
    `stripe_event_id` VARCHAR(255) NOT NULL COMMENT 'Stripe Event ID',
    `event_type` VARCHAR(100) NOT NULL COMMENT 'Stripeイベント種別',
    `payload_json` JSON NULL COMMENT '受信内容。保存範囲は必要最小限',
    `processing_status` VARCHAR(20) NOT NULL DEFAULT 'received' COMMENT 'received, processing, processed, failed',
    `error_message` VARCHAR(1000) NULL COMMENT '処理失敗理由',
    `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '受信日時',
    `processed_at` DATETIME NULL COMMENT '処理完了日時',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_stripe_webhook_events_event_id` (`stripe_event_id`),
    KEY `idx_stripe_webhook_events_status_received` (`processing_status`, `received_at`),
    CONSTRAINT `chk_stripe_webhook_events_processing_status` CHECK (`processing_status` IN ('received', 'processing', 'processed', 'failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Stripe Webhook受信履歴';

CREATE TABLE `mail_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'メールログID',
    `reservation_id` BIGINT UNSIGNED NULL COMMENT '関連予約ID',
    `mail_type` VARCHAR(50) NOT NULL COMMENT 'メール種別',
    `recipient_email_masked` VARCHAR(254) NOT NULL COMMENT 'マスク済み宛先',
    `subject` VARCHAR(255) NOT NULL COMMENT '件名',
    `send_status` VARCHAR(20) NOT NULL COMMENT 'sent または failed',
    `error_message` VARCHAR(1000) NULL COMMENT '送信失敗理由',
    `sent_at` DATETIME NULL COMMENT '送信成功日時',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '記録日時',
    PRIMARY KEY (`id`),
    KEY `idx_mail_logs_reservation_created` (`reservation_id`, `created_at`),
    KEY `idx_mail_logs_status_created` (`send_status`, `created_at`),
    CONSTRAINT `fk_mail_logs_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON UPDATE RESTRICT ON DELETE SET NULL,
    CONSTRAINT `chk_mail_logs_send_status` CHECK (`send_status` IN ('sent', 'failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='メール送信ログ';

CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(100) NOT NULL COMMENT '設定キー',
    `setting_value` TEXT NULL COMMENT '設定値',
    `value_type` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT 'string, integer, boolean, json',
    `description` VARCHAR(255) NULL COMMENT '設定説明',
    `is_public` BOOLEAN NOT NULL DEFAULT FALSE COMMENT '利用者画面への公開可否',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    PRIMARY KEY (`setting_key`),
    CONSTRAINT `chk_system_settings_value_type` CHECK (`value_type` IN ('string', 'integer', 'boolean', 'json'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='システム設定';

SELECT TABLE_NAME, TABLE_ROWS, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'nkworks'
ORDER BY TABLE_NAME;
