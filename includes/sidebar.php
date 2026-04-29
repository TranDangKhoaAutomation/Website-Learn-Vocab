<?php
require_once __DIR__ . '/../config/config.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
        <span><?= APP_NAME ?></span>
    </div>
    <nav class="sidebar-nav">
        <a class="nav-link <?= is_active('dashboard.php') ?>" href="<?= app_url('dashboard.php') ?>">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a class="nav-link <?= is_active('pages/sets.php') ?>" href="<?= app_url('pages/sets.php') ?>">
            <i class="bi bi-collection"></i><span>My Sets</span>
        </a>
        <a class="nav-link <?= is_active('pages/library.php') ?>" href="<?= app_url('pages/library.php') ?>">
            <i class="bi bi-globe2"></i><span>Library</span>
        </a>
        <a class="nav-link <?= is_active('pages/flashcards.php') ?>" href="<?= app_url('pages/flashcards.php') ?>">
            <i class="bi bi-card-text"></i><span>Flashcards</span>
        </a>
        <a class="nav-link <?= is_active('pages/learn.php') ?>" href="<?= app_url('pages/learn.php') ?>">
            <i class="bi bi-mortarboard"></i><span>Learn</span>
        </a>
        <a class="nav-link <?= is_active('pages/test.php') ?>" href="<?= app_url('pages/test.php') ?>">
            <i class="bi bi-ui-checks-grid"></i><span>Test</span>
        </a>
        <a class="nav-link <?= is_active('pages/blocks.php') ?>" href="<?= app_url('pages/blocks.php') ?>">
            <i class="bi bi-boxes"></i><span>Blocks</span>
        </a>
        <a class="nav-link <?= is_active('pages/blast.php') ?>" href="<?= app_url('pages/blast.php') ?>">
            <i class="bi bi-rocket-takeoff"></i><span>Blast</span>
        </a>
        <a class="nav-link <?= is_active('pages/match.php') ?>" href="<?= app_url('pages/match.php') ?>">
            <i class="bi bi-intersect"></i><span>Match</span>
        </a>
        <a class="nav-link <?= is_active('pages/profile.php') ?>" href="<?= app_url('pages/profile.php') ?>">
            <i class="bi bi-person-circle"></i><span>Profile</span>
        </a>
        <a class="nav-link <?= is_active('pages/feedback.php') ?>" href="<?= app_url('pages/feedback.php') ?>">
            <i class="bi bi-chat-dots"></i><span>Feedback</span>
        </a>
        <?php if (current_user_role() === 'admin'): ?>
            <a class="nav-link" href="<?= app_url('admin/index.php') ?>">
                <i class="bi bi-shield-lock"></i><span>Admin</span>
            </a>
        <?php endif; ?>
        <a class="nav-link" href="<?= app_url('logout.php') ?>">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
