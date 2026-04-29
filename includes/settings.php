<?php
require_once __DIR__ . '/../config/database.php';

function setting_definitions(): array
{
    return [
        'site_name' => [
            'label' => 'Tên website',
            'type' => 'text',
            'default' => APP_NAME,
            'help' => 'Hiển thị ở title, sidebar, navbar và trang public.',
        ],
        'site_tagline' => [
            'label' => 'Tagline',
            'type' => 'text',
            'default' => 'Học tiếng Anh chủ động mỗi ngày',
            'help' => 'Dòng mô tả ngắn dưới tiêu đề app.',
        ],
        'registration_enabled' => [
            'label' => 'Bật đăng ký tài khoản',
            'type' => 'boolean',
            'default' => true,
            'help' => 'Tắt mục này để người mới không tự tạo tài khoản.',
        ],
        'public_set_moderation' => [
            'label' => 'Duyệt bộ từ public',
            'type' => 'boolean',
            'default' => false,
            'help' => 'Nếu bật, bộ từ public mới tạo sẽ ở trạng thái pending.',
        ],
        'maintenance_mode' => [
            'label' => 'Maintenance mode',
            'type' => 'boolean',
            'default' => false,
            'help' => 'Trang học của user sẽ hiện bảo trì. Home, đăng nhập, đăng ký và admin vẫn dùng được.',
        ],
        'default_test_questions' => [
            'label' => 'Số câu test mặc định',
            'type' => 'number',
            'default' => 20,
            'min' => 1,
            'max' => 100,
            'help' => 'Giới hạn số câu được tạo trong Test mode.',
        ],
        'blast_default_seconds' => [
            'label' => 'Thời gian Blast',
            'type' => 'number',
            'default' => 60,
            'min' => 10,
            'max' => 600,
            'help' => 'Thời gian chơi Blast tính bằng giây.',
        ],
        'contact_email' => [
            'label' => 'Email liên hệ',
            'type' => 'text',
            'default' => 'support@example.com',
            'help' => 'Hiển thị trong trang bảo trì và footer.',
        ],
        'footer_text' => [
            'label' => 'Nội dung footer',
            'type' => 'text',
            'default' => APP_NAME,
            'help' => 'Dòng chữ cuối trang trong dashboard/public page.',
        ],
        'leaderboard_enabled' => [
            'label' => 'Bật leaderboard',
            'type' => 'boolean',
            'default' => true,
            'help' => 'Hiển thị bảng xếp hạng trên dashboard.',
        ],
        'feedback_enabled' => [
            'label' => 'Bật feedback',
            'type' => 'boolean',
            'default' => true,
            'help' => 'Cho phép user gửi feedback và báo lỗi nội dung.',
        ],
        'public_library_enabled' => [
            'label' => 'Bật public library',
            'type' => 'boolean',
            'default' => true,
            'help' => 'Cho phép user xem thư viện bộ từ public.',
        ],
    ];
}

function normalize_setting_value(mixed $value, string $type, mixed $default = null, array $definition = []): mixed
{
    if ($type === 'boolean') {
        return (bool) $value;
    }

    if ($type === 'number') {
        $number = is_numeric($value) ? (int) $value : (int) $default;
        if (isset($definition['min'])) {
            $number = max((int) $definition['min'], $number);
        }
        if (isset($definition['max'])) {
            $number = min((int) $definition['max'], $number);
        }
        return $number;
    }

    if ($type === 'json') {
        return $value;
    }

    $text = trim((string) $value);
    if ($text === '' && $default !== null) {
        return (string) $default;
    }

    return $text;
}

function load_settings(bool $force = false): array
{
    static $settings = null;

    if ($settings !== null && !$force) {
        return $settings;
    }

    $settings = [];
    $definitions = setting_definitions();

    foreach ($definitions as $key => $definition) {
        $settings[$key] = $definition['default'] ?? null;
    }

    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->query('SELECT setting_key, setting_value, setting_type FROM site_settings');
            foreach ($stmt->fetchAll() as $row) {
                $value = $row['setting_value'];
                if ($row['setting_type'] === 'boolean') {
                    $value = in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
                } elseif ($row['setting_type'] === 'number') {
                    $value = is_numeric($value) ? (int) $value : 0;
                } elseif ($row['setting_type'] === 'json') {
                    $decoded = json_decode((string) $value, true);
                    $value = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                }
                $settings[$row['setting_key']] = $value;
            }
        } catch (Throwable $exception) {
            // Keep defaults if the settings table is not available yet.
        }
    }

    return $settings;
}

function get_setting(string $key, mixed $default = null): mixed
{
    $settings = load_settings();

    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function set_setting(PDO $pdo, string $key, mixed $value, string $type = 'text'): void
{
    if ($type === 'boolean') {
        $value = $value ? '1' : '0';
    } elseif ($type === 'json') {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
    } else {
        $value = (string) $value;
    }

    $stmt = $pdo->prepare('
        INSERT INTO site_settings (setting_key, setting_value, setting_type, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_at = NOW()
    ');
    $stmt->execute([$key, $value, $type]);
    load_settings(true);
}

function settings_enabled(string $key, bool $default = true): bool
{
    return (bool) get_setting($key, $default);
}

function site_name(): string
{
    $name = trim((string) get_setting('site_name', APP_NAME));
    return $name !== '' ? $name : APP_NAME;
}

function site_tagline(): string
{
    return trim((string) get_setting('site_tagline', 'Học tiếng Anh chủ động mỗi ngày'));
}

function render_maintenance_page(): void
{
    if (!headers_sent()) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 3600');
    }

    $contact = trim((string) get_setting('contact_email', ''));
    ?>
    <!doctype html>
    <html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bảo trì - <?= e(site_name()) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    </head>
    <body class="maintenance-page">
        <main class="maintenance-card">
            <span class="maintenance-icon"><i class="bi bi-tools"></i></span>
            <h1>Website đang bảo trì</h1>
            <p>Hệ thống đang được cập nhật. Vui lòng quay lại sau.</p>
            <?php if ($contact !== ''): ?>
                <p class="maintenance-contact">Liên hệ: <?= e($contact) ?></p>
            <?php endif; ?>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-primary" href="<?= app_url('login.php') ?>">Đăng nhập</a>
                <a class="btn btn-outline-primary" href="<?= BASE_URL ?>">Trang chủ</a>
            </div>
        </main>
    </body>
    </html>
    <?php
}

function enforce_maintenance_mode(): void
{
    if (!settings_enabled('maintenance_mode', false)) {
        return;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $isAdminRoute = str_contains($script, '/admin/');
    $isPublicRoute = str_ends_with($script, '/index.php')
        || str_ends_with($script, '/login.php')
        || str_ends_with($script, '/register.php')
        || str_ends_with($script, '/logout.php');
    $isAdmin = current_user_role() === 'admin';

    if ($isAdmin || $isAdminRoute || $isPublicRoute) {
        return;
    }

    render_maintenance_page();
    exit;
}
