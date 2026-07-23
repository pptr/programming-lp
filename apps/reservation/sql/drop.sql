-- ============================================================
-- NK Works 予約システム
-- drop.sql
--
-- 用途:
--   開発環境のテーブルをすべて削除し、
--   schema.sql と seed.sql で再構築するためのスクリプト。
--
-- 注意:
--   本番環境では実行しないこと。
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE DATABASE IF NOT EXISTS `nkworks`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

USE `nkworks`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `mail_logs`;
DROP TABLE IF EXISTS `stripe_webhook_events`;
DROP TABLE IF EXISTS `reservation_histories`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `reservation_slots`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `reservation_number_counters`;
DROP TABLE IF EXISTS `schedule_blocks`;
DROP TABLE IF EXISTS `availability_rules`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `system_settings`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- データベース自体も削除する場合だけ、次の行のコメントを外す。
-- DROP DATABASE IF EXISTS `nkworks`;
-- ============================================================

SELECT
    COUNT(*) AS remaining_table_count
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'nkworks';
