<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

$id = max(0, (int) ($_POST['id'] ?? 0));

if (!$pdo || $id <= 0) {
    set_flash('danger', 'Yêu cầu không hợp lệ.');
    redirect('pages/sets.php');
}

$set = get_set_owner($pdo, $id, current_user_id());
if (!$set) {
    set_flash('danger', 'Chỉ chủ sở hữu mới được xóa bộ từ.');
    redirect('pages/sets.php');
}

$stmt = $pdo->prepare('DELETE FROM vocabulary_sets WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user_id()]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Đã xóa bộ từ.');
} else {
    set_flash('danger', 'Không thể xóa bộ từ này.');
}

redirect('pages/sets.php');
