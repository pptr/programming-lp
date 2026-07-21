<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/apps/contact/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    redirect('./');
}
$input = normalize_contact_input($_POST);
$errors = validate_contact($input);
$_SESSION['contact_input'] = $input;
if ($errors) {
    $_SESSION['contact_errors'] = $errors;
    redirect('./');
}
$_SESSION['contact_confirmed'] = true;
$categories = contact_categories();
$sendError = $_SESSION['contact_send_error'] ?? null;
unset($_SESSION['contact_send_error']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="NK Worksへのレッスン内容、受講方法、料金、予約、学習相談などのお問い合わせはこちらから。">
  <title>入力内容の確認 | NK Works</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/contact.css">
</head>
<body>
<header class="site-header">
  <div class="container site-header-inner">
    <a class="site-logo" href="../index.html" aria-label="NK Works トップページ">
      <img src="../images/nkworks_logo.png" alt="NK Works" class="site-logo-image">
    </a>
    <nav class="site-nav" aria-label="メインナビゲーション">
      <a href="../lesson.html">レッスン詳細</a>
      <a href="../guide.html">受講案内・料金</a>
      <a href="../profile.html">講師情報</a>
      <a href="../faq.html">よくある質問</a>
    </nav>
    <div class="site-header-actions">
      <a class="site-header-button" href="https://calendar.google.com/calendar/u/0/appointments/schedules/AcZssZ1oMFq9wS3znJD91qp8JSfKwD3LoMNsMQNBw1Jx3tbOXKk782JvsL_dksaXFfuW8BPupAmnkvUB" target="_blank" rel="noopener">ご予約</a>
      <a class="site-header-button site-header-button--secondary" href="./" aria-current="page">お問い合わせ</a>
    </div>
  </div>
</header><main>
<section class="page-hero contact-page-hero"><div class="container"><div class="page-hero-content"><span class="contact-eyebrow">CONFIRM</span><h1>入力内容の確認</h1><p class="page-hero-description">内容をご確認のうえ、「この内容で送信する」を押してください。</p></div></div></section>
<section class="section contact-form-section"><div class="container contact-narrow"><div class="contact-form-card surface">
<div class="contact-form-heading"><span>確認</span><h2>お問い合わせ内容</h2></div><?php if ($sendError): ?><div class="form-alert form-alert--error" role="alert"><strong><?= h($sendError) ?></strong></div><?php endif; ?>
<dl class="confirm-list"><div><dt>氏名</dt><dd><?= h($input['name']) ?> 様</dd></div><div><dt>メールアドレス</dt><dd><?= h($input['email']) ?></dd></div><div><dt>電話番号</dt><dd><?= h($input['phone'] !== '' ? $input['phone'] : '未入力') ?></dd></div><div><dt>お問い合わせ種別</dt><dd><?= h($categories[$input['category']]) ?></dd></div><div class="confirm-list-message"><dt>お問い合わせ内容</dt><dd><?= nl2br(h($input['message'])) ?></dd></div></dl>
<div class="form-actions form-actions--split"><form action="./" method="get"><button type="submit" class="button button--secondary">入力内容を修正する</button></form><form action="send.php" method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button type="submit" class="button button--primary">この内容で送信する</button></form></div>
</div></div></section></main><footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <h3>NK Works</h3>
      <p>板橋区を拠点とするプログラミングレッスン<br>現役ソフトウェア開発者によるマンツーマン指導</p>
      <div class="footer-info">
        <p><strong>営業時間</strong>水〜日: 9:00〜21:00</p>
        <p><strong>定休日</strong>月・火</p>
      </div>
    </div>
    <nav class="footer-nav" aria-label="フッターナビゲーション">
      <h3>MENU</h3>
      <a href="../lesson.html">レッスン詳細</a>
      <a href="../guide.html">受講案内・料金</a>
      <a href="../profile.html">講師情報</a>
      <a href="../faq.html">よくある質問</a>
      <a href="https://calendar.google.com/calendar/u/0/appointments/schedules/AcZssZ1oMFq9wS3znJD91qp8JSfKwD3LoMNsMQNBw1Jx3tbOXKk782JvsL_dksaXFfuW8BPupAmnkvUB" target="_blank" rel="noopener">ご予約</a>
      <a href="./">お問い合わせ</a>
    </nav>
  </div>
  <div class="footer-bottom">©2026 NK Works</div>
</footer></body></html>