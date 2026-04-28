<?php
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (!$errors && $pdo) {
        $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            set_flash('success', 'Đăng nhập thành công.');
            redirect('dashboard.php');
        }

        $errors[] = 'Email hoặc mật khẩu không đúng.';
    }
}

$flash = get_flash();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
<a class="auth-home-link" href="<?= BASE_URL ?>"><i class="bi bi-arrow-left"></i> Trang chủ</a>
<main class="auth-card">
    <div class="auth-hero">
        <div class="brand-icon mb-3"><i class="bi bi-lightning-charge-fill"></i></div>
        <h1><?= APP_NAME ?></h1>
        <p>Học từ vựng bằng flashcard, quiz và mini game miễn phí.</p>
    </div>
    <div class="auth-form">
        <h2>Đăng nhập</h2>
        <p class="text-muted">Tiếp tục học với tài khoản của bạn.</p>

        <?php if (!empty($database_error)): ?>
            <div class="alert alert-danger"><?= e($database_error) ?></div>
        <?php endif; ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= BASE_URL ?>login.php" novalidate>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Mật khẩu</label>
                <input class="form-control" id="password" name="password" type="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
        </form>

        <div class="auth-switch">
            Chưa có tài khoản? <a href="<?= BASE_URL ?>register.php">Đăng ký miễn phí</a>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
