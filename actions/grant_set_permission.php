<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

$setId = max(0, (int) ($_POST['set_id'] ?? 0));
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'viewer';
$role = in_array($role, ['viewer', 'editor', 'admin'], true) ? $role : 'viewer';

if (!$pdo || $setId <= 0 || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Dữ liệu cấp quyền không hợp lệ.');
    redirect('pages/edit_set.php?id=' . $setId);
}

$set = get_set_for_permission_management($pdo, $setId, current_user_id());
if (!$set) {
    set_flash('danger', 'Bạn không có quyền cấp quyền cho bộ từ này.');
    redirect('pages/sets.php');
}

$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    set_flash('danger', 'Không tìm thấy user với email này.');
    redirect('pages/edit_set.php?id=' . $setId);
}

if ((int) $targetUser['id'] === (int) $set['user_id']) {
    set_flash('warning', 'Chủ sở hữu đã có toàn quyền, không cần cấp thêm.');
    redirect('pages/edit_set.php?id=' . $setId);
}

$stmt = $pdo->prepare('
    INSERT INTO vocabulary_set_permissions (set_id, user_id, role, created_at, updated_at)
    VALUES (?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE role = VALUES(role), updated_at = NOW()
');
$stmt->execute([$setId, (int) $targetUser['id'], $role]);

set_flash('success', 'Đã cấp quyền ' . set_user_role_label($role) . ' cho ' . $targetUser['email'] . '.');
redirect('pages/edit_set.php?id=' . $setId);
