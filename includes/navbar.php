<?php
require_once __DIR__ . '/../config/config.php';
$flash = get_flash();
$initial = mb_strtoupper(mb_substr(current_user_name(), 0, 1, 'UTF-8'), 'UTF-8');
?>
<main class="main-content">
    <nav class="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn icon-btn d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="topbar-title"><?= e($pageTitle ?? APP_NAME) ?></div>
                <div class="topbar-subtitle">Học từ vựng tiếng Anh mỗi ngày</div>
            </div>
        </div>
        <div class="topbar-actions">
            <form class="search-box d-none d-md-flex" action="<?= app_url('pages/sets.php') ?>" method="get">
                <i class="bi bi-search"></i>
                <input type="search" name="q" placeholder="Tìm bộ từ..." value="<?= e($_GET['q'] ?? '') ?>">
            </form>
            <div class="dropdown user-menu">
                <button class="user-pill user-pill-button" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar"><?= e($initial) ?></span>
                    <span class="d-none d-sm-inline"><?= e(current_user_name()) ?></span>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end user-dropdown shadow">
                    <div class="user-dropdown-head">
                        <span class="avatar"><?= e($initial) ?></span>
                        <div>
                            <strong><?= e(current_user_name()) ?></strong>
                            <small><?= e($_SESSION['user_email'] ?? '') ?></small>
                            <span class="badge text-bg-light"><?= e(current_user_role()) ?></span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= app_url('pages/profile.php') ?>"><i class="bi bi-person-circle"></i> Profile</a>
                    <a class="dropdown-item" href="<?= app_url('pages/sets.php') ?>"><i class="bi bi-collection"></i> My Sets</a>
                    <a class="dropdown-item" href="<?= app_url('pages/library.php') ?>"><i class="bi bi-globe2"></i> Public Library</a>
                    <a class="dropdown-item" href="<?= app_url('pages/feedback.php') ?>"><i class="bi bi-chat-dots"></i> Feedback</a>
                    <button class="dropdown-item" type="button" id="themeToggle"><i class="bi bi-moon"></i> Dark / Light mode</button>
                    <?php if (current_user_role() === 'admin'): ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= app_url('admin/index.php') ?>"><i class="bi bi-shield-lock"></i> Admin Panel</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="<?= app_url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="content-wrap">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show app-alert" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
