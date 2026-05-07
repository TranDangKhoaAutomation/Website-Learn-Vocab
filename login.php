<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/settings.php';

if (!empty($_SESSION['user_id'])) {
    $redirectTarget = settings_enabled('maintenance_mode', false) && current_user_role() !== 'admin'
        ? 'index.php'
        : 'dashboard.php';
    redirect($redirectTarget);
}

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

$errors = [];
$registrationEnabled = settings_enabled('registration_enabled', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (!$errors && $pdo) {
        $stmt = $pdo->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (($user['status'] ?? 'active') === 'locked') {
                $errors[] = 'Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.';
            } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $role = $user['role'] ?? 'user';
            $_SESSION['user_role'] = $role;
            $updateLogin = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $updateLogin->execute([(int) $user['id']]);
            set_flash('success', 'Đăng nhập thành công.');
            $redirectTarget = settings_enabled('maintenance_mode', false) && $role !== 'admin'
                ? 'index.php'
                : 'dashboard.php';
            redirect($redirectTarget);
            }
        }

        if (!$errors) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        }
    }
}

$flash = get_flash();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - <?= e(site_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="auth-page" data-base-url="<?= BASE_URL ?>">
<a class="auth-home-link" href="<?= BASE_URL ?>"><i class="bi bi-arrow-left"></i> Trang chủ</a>
<button class="auth-theme-toggle" type="button" data-theme-toggle="icon" aria-label="Dark / Light mode" title="Dark / Light mode">
    <i class="bi bi-moon"></i>
</button>
<main class="auth-card auth-card-modern">
    <section class="auth-hero">
        <div class="auth-hero-content">
            <div class="auth-brand-row">
                <div class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <span><?= e(site_name()) ?></span>
            </div>
            <span class="auth-eyebrow">Học tiếng Anh chủ động</span>
            <h1>Ghi nhớ từ vựng thông minh hơn.</h1>
            <p>Flashcards, luyện phát âm và mini game được gom trong một không gian học gọn gàng.</p>
            <div class="auth-benefits">
                <span><i class="bi bi-volume-up"></i> Luyện phát âm</span>
                <span><i class="bi bi-bar-chart"></i> Theo dõi tiến độ</span>
                <span><i class="bi bi-controller"></i> Học bằng game</span>
            </div>
        </div>
    </section>
    <section class="auth-form">
        <div class="auth-form-head">
            <div>
                <span class="auth-form-kicker">Chào mừng trở lại</span>
                <h2>Đăng nhập</h2>
                <p class="text-muted mb-0">Tiếp tục học với tài khoản của bạn.</p>
            </div>
        </div>

        <?php if (!empty($database_error)): ?>
            <div class="alert alert-danger"><?= e($database_error) ?></div>
        <?php endif; ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= app_url('login.php') ?>" novalidate>
            <?= csrf_field() ?>
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
                    <input class="form-control" id="password" name="password" type="password" placeholder="Nhập mật khẩu" required>
                    <button class="auth-password-toggle" type="button" data-toggle-password="#password" aria-label="Hiện mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button class="btn btn-primary auth-submit w-100" type="submit">Đăng nhập <i class="bi bi-arrow-right"></i></button>
        </form>

        <?php if ($registrationEnabled): ?>
            <div class="auth-switch">
                Chưa có tài khoản? <a href="<?= app_url('register.php') ?>" data-auth-transition="register">Đăng ký miễn phí</a>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
