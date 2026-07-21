<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/apps/contact/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null) || empty($_SESSION['contact_confirmed'])) {
    redirect('./');
}
if (!can_submit_again()) {
    $_SESSION['contact_send_error'] = '短時間に連続して送信することはできません。しばらく待ってからお試しください。';
    redirect('confirm.php');
}
$input = $_SESSION['contact_input'] ?? [];
$errors = validate_contact($input);
if ($errors) {
    $_SESSION['contact_errors'] = $errors;
    redirect('./');
}

$autoloadCandidates = [
    PROJECT_ROOT . '/vendor/autoload.php',
];
$autoload = null;
foreach ($autoloadCandidates as $candidate) { if (is_file($candidate)) { $autoload = $candidate; break; } }
if ($autoload === null) {
    Logger::error('PHPMailer autoload file not found.');
    $_SESSION['contact_send_error'] = 'メール送信システムを準備中です。info@nkworks.info へ直接お問い合わせください。';
    redirect('confirm.php');
}
require_once $autoload;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function create_mailer(array $config): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->Port = (int)$config['smtp']['port'];
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $secure = strtolower((string)$config['smtp']['secure']);
    if ($secure === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    if ($secure === 'ssl' || $secure === 'smtps') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->setFrom($config['from']['address'], $config['from']['name']);
    return $mail;
}

$categories = contact_categories();
$sentAt = date('Y-m-d H:i:s');
$phone = $input['phone'] !== '' ? $input['phone'] : '未入力';
$adminBody = "Webサイトからお問い合わせがありました。\n\n" .
    "【受付日時】{$sentAt}\n【お名前】{$input['name']} 様\n【メールアドレス】{$input['email']}\n【電話番号】{$phone}\n" .
    "【お問い合わせ種別】{$categories[$input['category']]}\n【お問い合わせ内容】\n{$input['message']}\n";
$customerBody = "{$input['name']} 様\n\nこの度は、お問い合わせありがとうございました。\n以下の内容でお問い合わせを受け付けいたしました。\n\n" .
    "【受付日時】{$sentAt}\n【お名前】{$input['name']} 様\n【メールアドレス】{$input['email']}\n" .
    "【お問い合わせ種別】{$categories[$input['category']]}\n【お問い合わせ内容】\n{$input['message']}\n\n" .
    "内容を確認次第、担当者より3営業日以内にご連絡させていただきます。\n今しばらくお待ちいただけますようお願い申し上げます。\n\n※本メールはシステムによる自動送信となります。\n";

try {
    $adminMail = create_mailer($mailConfig);
    $adminMail->addAddress($mailConfig['admin']['address'], $mailConfig['admin']['name']);
    $adminMail->addReplyTo($input['email'], $input['name']);
    $adminMail->Subject = '【お問い合わせ】' . $input['name'] . ' 様';
    $adminMail->Body = $adminBody;
    $adminMail->send();

    $customerMail = create_mailer($mailConfig);
    $customerMail->addAddress($input['email'], $input['name']);
    $customerMail->addReplyTo($mailConfig['admin']['address'], $mailConfig['admin']['name']);
    $customerMail->Subject = '【NK Works】お問い合わせありがとうございます';
    $customerMail->Body = $customerBody;
    $customerMail->send();

    Logger::info('mail sent successfully.');
    $_SESSION['last_contact_sent_at'] = time();
    $_SESSION['contact_completed'] = true;
    unset($_SESSION['contact_input'], $_SESSION['contact_confirmed'], $_SESSION['csrf_token']);
    redirect('complete.php');
} catch (Exception|Throwable $e) {
    Logger::error('sending mail failed: ' . $e->getMessage());
    $_SESSION['contact_send_error'] = 'メールを送信できませんでした。時間をおいて再度お試しいただくか、info@nkworks.info へ直接お問い合わせください。';
    redirect('confirm.php');
}
