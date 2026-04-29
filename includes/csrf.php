<?php
require_once __DIR__ . '/../config/config.php';

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        set_flash('danger', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
    }
}
