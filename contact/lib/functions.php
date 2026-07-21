<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function contact_categories(): array
{
    return [
        'lesson' => 'レッスン内容について',
        'method' => '受講方法について',
        'price' => '料金について',
        'reservation' => '予約について',
        'study' => '学習相談',
        'other' => 'その他',
    ];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function normalize_contact_input(array $source): array
{
    $trim = static fn(string $key): string => trim((string)($source[$key] ?? ''));
    return [
        'name' => preg_replace('/[\x00-\x1F\x7F]/u', '', $trim('name')) ?? '',
        'email' => str_replace(["\r", "\n"], '', $trim('email')),
        'phone' => str_replace(["\r", "\n"], '', $trim('phone')),
        'category' => $trim('category'),
        'message' => str_replace(["\r\n", "\r"], "\n", $trim('message')),
        'privacy' => isset($source['privacy']) ? '1' : '',
        'website' => $trim('website'),
    ];
}

function validate_contact(array $data): array
{
    $errors = [];
    $length = static fn(string $value): int => mb_strlen($value, 'UTF-8');

    if ($length($data['name']) < 1 || $length($data['name']) > 100) {
        $errors['name'] = '氏名は1〜100文字で入力してください。';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 254) {
        $errors['email'] = '正しい形式のメールアドレスを入力してください。';
    }
    if ($data['phone'] !== '' && !preg_match('/^[0-9０-９+＋()（）\-ー―−\s]+$/u', $data['phone'])) {
        $errors['phone'] = '電話番号は数字・ハイフン・括弧で入力してください。';
    }
    if (!array_key_exists($data['category'], contact_categories())) {
        $errors['category'] = 'お問い合わせ種別を選択してください。';
    }
    if ($length($data['message']) < 10 || $length($data['message']) > 2000) {
        $errors['message'] = 'お問い合わせ内容は10〜2,000文字で入力してください。';
    }
    if ($data['privacy'] !== '1') {
        $errors['privacy'] = 'プライバシーポリシーへの同意が必要です。';
    }
    if ($data['website'] !== '') {
        $errors['spam'] = '送信できませんでした。';
    }
    return $errors;
}

function can_submit_again(int $intervalSeconds = 60): bool
{
    $last = (int)($_SESSION['last_contact_sent_at'] ?? 0);
    return time() - $last >= $intervalSeconds;
}

function contact_app_path(string $relative = ''): string
{
    return CONTACT_ROOT . ($relative !== '' ? '/' . ltrim($relative, '/') : '');
}
