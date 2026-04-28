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
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Tên phải có ít nhất 2 ký tự.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    if (!$errors && $pdo) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'Email này đã được sử dụng.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            set_flash('success', 'Đăng ký thành công. Bạn có thể đăng nhập ngay.');
            redirect('login.php');
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page">
<a class="auth-home-link" href="<?= BASE_URL ?>"><i class="bi bi-arrow-left"></i> Trang chủ</a>
<main class="auth-card">
    <div class="auth-hero">
        <div class="brand-icon mb-3"><i class="bi bi-mortarboard-fill"></i></div>
        <h1>Bắt đầu miễn phí</h1>
        <p>Tạo bộ từ riêng, luyện tập và theo dõi tiến độ học tiếng Anh.</p>
    </div>
    <div class="auth-form">
        <h2>Đăng ký</h2>
        <p class="text-muted">Tạo tài khoản mới trong vài giây.</p>

        <?php if (!empty($database_error)): ?>
            <div class="alert alert-danger"><?= e($database_error) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= BASE_URL ?>register.php" novalidate>
            <div class="mb-3">
                <label class="form-label" for="name">Họ tên</label>
                <input class="form-control" id="name" name="name" type="text" value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Mật khẩu</label>
                <input class="form-control" id="password" name="password" type="password" minlength="6" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Tạo tài khoản</button>
        </form>

        <div class="auth-switch">
            Đã có tài khoản? <a href="<?= BASE_URL ?>login.php">Đăng nhập</a>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
