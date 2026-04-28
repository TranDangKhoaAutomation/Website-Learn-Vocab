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
            <form class="search-box d-none d-md-flex" action="<?= BASE_URL ?>pages/sets.php" method="get">
                <i class="bi bi-search"></i>
                <input type="search" name="q" placeholder="Tìm bộ từ..." value="<?= e($_GET['q'] ?? '') ?>">
            </form>
            <div class="user-pill">
                <span class="avatar"><?= e($initial) ?></span>
                <span class="d-none d-sm-inline"><?= e(current_user_name()) ?></span>
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
