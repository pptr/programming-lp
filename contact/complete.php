<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/apps/contact/bootstrap.php';
if (empty($_SESSION['contact_completed'])) { redirect('./'); }
unset($_SESSION['contact_completed']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="NK Worksへのレッスン内容、受講方法、料金、予約、学習相談などのお問い合わせはこちらから。">
  <title>送信完了 | NK Works</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/contact.css">
  <!-- GA4 -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-VP8MTZC2JE"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-VP8MTZC2JE');
    gtag('event', 'contact_complete');
  </script>
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
</header>

<main>
  <section class="section complete-section">
    <div class="container contact-narrow">
      <div class="complete-card surface">
        <div class="complete-icon" aria-hidden="true">✓</div>
        <span class="contact-eyebrow">THANK YOU</span>
        <h1>お問い合わせを受け付けました</h1>
        <p>ご入力いただいたメールアドレスへ、自動返信メールを送信しました。</p>
        <p>内容を確認のうえ、通常3営業日以内に <strong>info@nkworks.info</strong> よりご連絡いたします。</p>
        <div class="form-actions">
          <a class="button button--primary" href="../index.html">トップページへ戻る</a>
        </div>
        <p class="complete-note">自動返信メールが届かない場合は、迷惑メールフォルダをご確認ください。</p>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
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