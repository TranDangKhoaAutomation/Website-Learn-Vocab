<?php
require_once __DIR__ . '/../config/config.php';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
        <span><?= APP_NAME ?></span>
    </div>
    <nav class="sidebar-nav">
        <a class="nav-link <?= is_active('dashboard.php') ?>" href="<?= BASE_URL ?>dashboard.php">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
        </a>
        <a class="nav-link <?= is_active('pages/sets.php') ?>" href="<?= BASE_URL ?>pages/sets.php">
            <i class="bi bi-collection"></i><span>My Sets</span>
        </a>
        <a class="nav-link <?= is_active('pages/classes.php') ?>" href="<?= BASE_URL ?>pages/classes.php">
            <i class="bi bi-people"></i><span>Classes</span>
        </a>
        <a class="nav-link <?= is_active('pages/flashcards.php') ?>" href="<?= BASE_URL ?>pages/flashcards.php">
            <i class="bi bi-card-text"></i><span>Flashcards</span>
        </a>
        <a class="nav-link <?= is_active('pages/learn.php') ?>" href="<?= BASE_URL ?>pages/learn.php">
            <i class="bi bi-mortarboard"></i><span>Learn</span>
        </a>
        <a class="nav-link <?= is_active('pages/test.php') ?>" href="<?= BASE_URL ?>pages/test.php">
            <i class="bi bi-ui-checks-grid"></i><span>Test</span>
        </a>
        <a class="nav-link <?= is_active('pages/blocks.php') ?>" href="<?= BASE_URL ?>pages/blocks.php">
            <i class="bi bi-boxes"></i><span>Blocks</span>
        </a>
        <a class="nav-link <?= is_active('pages/blast.php') ?>" href="<?= BASE_URL ?>pages/blast.php">
            <i class="bi bi-rocket-takeoff"></i><span>Blast</span>
        </a>
        <a class="nav-link <?= is_active('pages/match.php') ?>" href="<?= BASE_URL ?>pages/match.php">
            <i class="bi bi-intersect"></i><span>Match</span>
        </a>
        <a class="nav-link <?= is_active('pages/profile.php') ?>" href="<?= BASE_URL ?>pages/profile.php">
            <i class="bi bi-person-circle"></i><span>Profile</span>
        </a>
        <a class="nav-link" href="<?= BASE_URL ?>logout.php">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
