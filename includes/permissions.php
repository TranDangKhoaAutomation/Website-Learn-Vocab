<?php
require_once __DIR__ . '/../config/config.php';

function has_role(array|string $roles): bool
{
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array(current_user_role(), $roles, true);
}

function require_role(array|string $roles): void
{
    if (!has_role($roles)) {
        set_flash('danger', 'Bạn không có quyền truy cập khu vực này.');
        redirect('dashboard.php');
    }
}

function require_admin(): void
{
    require_role('admin');
}

function require_teacher_or_admin(): void
{
    require_role(['teacher', 'admin']);
}
