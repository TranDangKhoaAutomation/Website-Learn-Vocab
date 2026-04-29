<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/audit.php';

require_admin();

function admin_count(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function admin_page_param(string $name, string $default = ''): string
{
    return trim((string) ($_GET[$name] ?? $default));
}

function admin_paginate(PDO $pdo, string $countSql, string $dataSql, array $params, int $page, int $perPage = 15): array
{
    $total = admin_count($pdo, $countSql, $params);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare($dataSql . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
    $stmt->execute($params);
    return [$stmt->fetchAll(), $total, $pages, $page];
}

function admin_badge(string $value): string
{
    $map = [
        'admin' => 'text-bg-danger',
        'teacher' => 'text-bg-primary',
        'user' => 'text-bg-secondary',
        'active' => 'text-bg-success',
        'locked' => 'text-bg-dark',
        'pending' => 'text-bg-warning',
        'hidden' => 'text-bg-secondary',
        'deleted' => 'text-bg-danger',
        'open' => 'text-bg-info',
        'reviewing' => 'text-bg-warning',
        'resolved' => 'text-bg-success',
        'rejected' => 'text-bg-secondary',
    ];
    return $map[$value] ?? 'text-bg-light';
}

function admin_header(string $title, string $active = ''): void
{
    $flash = get_flash();
    $items = [
        'index' => ['Dashboard', 'speedometer2'],
        'users' => ['Users', 'people'],
        'sets' => ['Sets', 'collection'],
        'flashcards' => ['Flashcards', 'card-text'],
        'classes' => ['Classes', 'people-fill'],
        'reports' => ['Reports', 'flag'],
        'analytics' => ['Analytics', 'bar-chart'],
        'announcements' => ['Announcements', 'megaphone'],
        'feedback' => ['Feedback', 'chat-dots'],
        'settings' => ['Settings', 'gear'],
        'audit_logs' => ['Audit Logs', 'shield-check'],
        'backup' => ['Backup', 'download'],
        'system_health' => ['System Health', 'activity'],
    ];
    ?>
    <!doctype html>
    <html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> - Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    </head>
    <body data-base-url="<?= BASE_URL ?>" class="admin-body">
    <div class="app-shell admin-shell">
        <aside class="sidebar admin-sidebar" id="sidebar">
            <div class="brand"><div class="brand-icon"><i class="bi bi-shield-lock"></i></div><span>Admin Panel</span></div>
            <nav class="sidebar-nav">
                <?php foreach ($items as $key => $item): ?>
                    <a class="nav-link <?= $active === $key ? 'active' : '' ?>" href="<?= app_url('admin/' . ($key === 'index' ? 'index.php' : $key . '.php')) ?>">
                        <i class="bi bi-<?= e($item[1]) ?>"></i><span><?= e($item[0]) ?></span>
                    </a>
                <?php endforeach; ?>
                <a class="nav-link" href="<?= app_url('dashboard.php') ?>"><i class="bi bi-arrow-left"></i><span>User Dashboard</span></a>
            </nav>
        </aside>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <main class="main-content">
            <nav class="topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn icon-btn d-lg-none" id="sidebarToggle" type="button"><i class="bi bi-list"></i></button>
                    <div>
                        <div class="topbar-title"><?= e($title) ?></div>
                        <div class="topbar-subtitle">Quản trị hệ thống học tiếng Anh</div>
                    </div>
                </div>
                <div class="topbar-actions">
                    <button class="btn btn-outline-secondary" type="button" id="themeToggle"><i class="bi bi-moon"></i></button>
                    <a class="btn btn-outline-primary" href="<?= app_url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </nav>
            <div class="content-wrap">
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= app_url('admin/index.php') ?>">Admin</a></li>
                        <li class="breadcrumb-item active"><?= e($title) ?></li>
                    </ol>
                </nav>
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show app-alert">
                        <?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
            </div>
        </main>
    </div>
    <button class="back-to-top" id="backToTop" type="button" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>
    </body>
    </html>
    <?php
}

function admin_pagination(int $page, int $pages): void
{
    if ($pages <= 1) {
        return;
    }
    $params = $_GET;
    echo '<nav class="mt-3"><ul class="pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        $params['page'] = $i;
        $url = '?' . http_build_query($params);
        echo '<li class="page-item ' . ($i === $page ? 'active' : '') . '"><a class="page-link" href="' . e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
