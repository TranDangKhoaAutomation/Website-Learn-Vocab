<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/settings.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

enforce_maintenance_mode();
$registrationEnabled = settings_enabled('registration_enabled', true);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$registrationEnabled) {
        $errors[] = 'Website đang tạm tắt đăng ký tài khoản mới.';
    }
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
    <title>Đăng ký - <?= e(site_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page auth-register-page" data-base-url="<?= BASE_URL ?>">
<a class="auth-home-link" href="<?= BASE_URL ?>"><i class="bi bi-arrow-left"></i> Trang chủ</a>
<main class="auth-card auth-card-modern">
    <section class="auth-hero">
        <div class="auth-hero-content">
            <div class="auth-brand-row">
                <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <span><?= e(site_name()) ?></span>
            </div>
            <span class="auth-eyebrow">Bắt đầu miễn phí</span>
            <h1>Tạo lộ trình học từ vựng riêng.</h1>
            <p>Tự tạo bộ từ, học theo lớp, luyện phát âm và ôn lại câu sai sau mỗi lượt học.</p>
            <div class="auth-benefits">
                <span><i class="bi bi-card-checklist"></i> Tạo bộ từ</span>
                <span><i class="bi bi-people"></i> Học theo lớp</span>
                <span><i class="bi bi-repeat"></i> Ôn câu sai</span>
            </div>
        </div>
    </section>
    <section class="auth-form">
        <div class="auth-form-head">
            <div>
                <span class="auth-form-kicker">Tài khoản mới</span>
                <h2>Đăng ký</h2>
                <p class="text-muted mb-0">Tạo tài khoản mới trong vài giây.</p>
            </div>
        </div>

        <?php if (!empty($database_error)): ?>
            <div class="alert alert-danger"><?= e($database_error) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endforeach; ?>

        <?php if (!$registrationEnabled): ?>
            <div class="alert alert-warning">Đăng ký tài khoản mới đang tạm tắt. Vui lòng liên hệ quản trị viên.</div>
        <?php else: ?>
        <form method="post" action="<?= app_url('register.php') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="name">Họ tên</label>
                <div class="auth-input">
                    <span class="auth-input-icon"><i class="bi bi-person"></i></span>
                    <input class="form-control" id="name" name="name" type="text" value="<?= e($_POST['name'] ?? '') ?>" placeholder="Tên của bạn" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <div class="auth-input">
                    <span class="auth-input-icon"><i class="bi bi-envelope"></i></span>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Mật khẩu</label>
                <div class="auth-input has-toggle">
                    <span class="auth-input-icon"><i class="bi bi-lock"></i></span>
                    <input class="form-control" id="password" name="password" type="password" minlength="6" placeholder="Tối thiểu 6 ký tự" required>
                    <button class="auth-password-toggle" type="button" data-toggle-password="#password" aria-label="Hiện mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                <div class="auth-input has-toggle">
                    <span class="auth-input-icon"><i class="bi bi-shield-check"></i></span>
                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" minlength="6" placeholder="Nhập lại mật khẩu" required>
                    <button class="auth-password-toggle" type="button" data-toggle-password="#confirm_password" aria-label="Hiện mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary auth-submit w-100" type="submit">Tạo tài khoản <i class="bi bi-arrow-right"></i></button>
        </form>
        <?php endif; ?>

        <div class="auth-switch">
            Đã có tài khoản? <a href="<?= app_url('login.php') ?>" data-auth-transition="login">Đăng nhập</a>
        </div>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
