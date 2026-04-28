<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

$setId = max(0, (int) ($_POST['set_id'] ?? 0));
$targetUserId = max(0, (int) ($_POST['user_id'] ?? 0));

if (!$pdo || $setId <= 0 || $targetUserId <= 0) {
    set_flash('danger', 'Yêu cầu không hợp lệ.');
    redirect('pages/sets.php');
}

$set = get_set_for_permission_management($pdo, $setId, current_user_id());
if (!$set) {
    set_flash('danger', 'Bạn không có quyền gỡ quyền của bộ từ này.');
    redirect('pages/sets.php');
}

if ($targetUserId === (int) $set['user_id']) {
    set_flash('warning', 'Không thể gỡ quyền của chủ sở hữu.');
    redirect('pages/edit_set.php?id=' . $setId);
}

$stmt = $pdo->prepare('DELETE FROM vocabulary_set_permissions WHERE set_id = ? AND user_id = ?');
$stmt->execute([$setId, $targetUserId]);

set_flash($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'Đã gỡ quyền người dùng.' : 'Không tìm thấy quyền cần gỡ.');
redirect('pages/edit_set.php?id=' . $setId);
