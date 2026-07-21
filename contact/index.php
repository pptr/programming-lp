<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/apps/contact/bootstrap.php';

$input = $_SESSION['contact_input'] ?? [
    'name' => '', 'email' => '', 'phone' => '', 'category' => '',
    'message' => '', 'privacy' => '', 'website' => '',
];
$errors = $_SESSION['contact_errors'] ?? [];
unset($_SESSION['contact_errors']);
$categories = contact_categories();
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="NK Worksへのレッスン内容、受講方法、料金、予約、学習相談などのお問い合わせはこちらから。">
  <title>お問い合わせ | NK Works</title>
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
</header>
<main>
  <section class="page-hero contact-page-hero">
    <div class="container"><div class="page-hero-content">
      <span class="page-hero-eyebrow">CONTACT</span>
      <h1>お問い合わせ</h1>
      <p class="page-hero-description">レッスン内容、受講方法、料金、予約など、ご不明な点をお気軽にお問い合わせください。</p>
    </div></div>
  </section>
  <section class="section contact-form-section">
    <div class="container contact-layout">
      <aside class="contact-guide surface">
        <img class="contact-guide-icon" src="../images/icons/mail.png" alt="" aria-hidden="true">
        <h2>お問い合わせの前に</h2>
        <p>通常、3営業日以内に返信いたします。予約日時の確定は予約ページをご利用ください。</p>
        <dl class="contact-guide-list">
          <div><dt>営業時間</dt><dd>水〜日 9:00〜21:00</dd></div>
          <div><dt>定休日</dt><dd>月・火</dd></div>
          <div><dt>メール</dt><dd><a href="mailto:info@nkworks.info">info@nkworks.info</a></dd></div>
        </dl>
        <p class="contact-guide-note">送信できない場合は、上記メールアドレスへ直接ご連絡ください。</p>
      </aside>
      <div class="contact-form-card surface">
        <div class="contact-form-heading"><span>入力</span><h2>お問い合わせ内容を入力</h2><p><em>必須</em>の項目は必ず入力してください。</p></div>
        <?php if ($errors): ?><div class="form-alert form-alert--error" role="alert"><strong>入力内容をご確認ください。</strong><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form action="confirm.php" method="post" class="contact-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
          <div class="honeypot" aria-hidden="true"><label>ウェブサイト<input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>
          <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>"><label for="name">氏名 <span class="required">必須</span></label><input id="name" name="name" type="text" maxlength="100" autocomplete="name" value="<?= h($input['name']) ?>" required><?php if (isset($errors['name'])): ?><p class="field-error"><?= h($errors['name']) ?></p><?php endif; ?></div>
          <div class="form-field<?= isset($errors['email']) ? ' has-error' : '' ?>"><label for="email">メールアドレス <span class="required">必須</span></label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" inputmode="email" value="<?= h($input['email']) ?>" required><p class="field-help">自動返信メールを受信できるアドレスをご入力ください。</p><?php if (isset($errors['email'])): ?><p class="field-error"><?= h($errors['email']) ?></p><?php endif; ?></div>
          <div class="form-field<?= isset($errors['phone']) ? ' has-error' : '' ?>"><label for="phone">電話番号 <span class="optional">任意</span></label><input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" inputmode="tel" value="<?= h($input['phone']) ?>"><?php if (isset($errors['phone'])): ?><p class="field-error"><?= h($errors['phone']) ?></p><?php endif; ?></div>
          <div class="form-field<?= isset($errors['category']) ? ' has-error' : '' ?>"><label for="category">お問い合わせ種別 <span class="required">必須</span></label><select id="category" name="category" required><option value="">選択してください</option><?php foreach ($categories as $value => $label): ?><option value="<?= h($value) ?>"<?= $input['category'] === $value ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select><?php if (isset($errors['category'])): ?><p class="field-error"><?= h($errors['category']) ?></p><?php endif; ?></div>
          <div class="form-field<?= isset($errors['message']) ? ' has-error' : '' ?>"><div class="form-label-row"><label for="message">お問い合わせ内容 <span class="required">必須</span></label><span class="character-count" id="message-count">0 / 2,000</span></div><textarea id="message" name="message" rows="9" minlength="10" maxlength="2000" required><?= h($input['message']) ?></textarea><?php if (isset($errors['message'])): ?><p class="field-error"><?= h($errors['message']) ?></p><?php endif; ?></div>
          <div class="form-field form-field--privacy<?= isset($errors['privacy']) ? ' has-error' : '' ?>"><label class="checkbox-label"><input type="checkbox" name="privacy" value="1"<?= $input['privacy'] === '1' ? ' checked' : '' ?>><span>プライバシーポリシーに同意する <span class="required">必須</span></span></label><p class="field-help">送信いただいた情報は、お問い合わせへの回答のためにのみ使用します。</p><?php if (isset($errors['privacy'])): ?><p class="field-error"><?= h($errors['privacy']) ?></p><?php endif; ?></div>
          <div class="form-actions"><button type="submit" class="button button--primary">入力内容を確認する</button></div>
        </form>
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
</footer>
<script>const m=document.getElementById('message'),c=document.getElementById('message-count');if(m&&c){const u=()=>c.textContent=[...m.value].length.toLocaleString()+' / 2,000';m.addEventListener('input',u);u();}</script>
</body></html>