<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

$id = max(0, (int) ($_POST['id'] ?? 0));
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$visibility = $_POST['visibility'] ?? 'private';
$visibility = in_array($visibility, ['private', 'public', 'class'], true) ? $visibility : 'private';
$classId = $visibility === 'class' ? max(0, (int) ($_POST['class_id'] ?? 0)) : 0;
$isPublic = $visibility === 'public' ? 1 : 0;

if (!$pdo || $id <= 0) {
    set_flash('danger', 'Yêu cầu không hợp lệ.');
    redirect('pages/sets.php');
}

if ($title === '') {
    set_flash('danger', 'Tiêu đề bộ từ không được để trống.');
    redirect('pages/edit_set.php?id=' . $id);
}

$set = get_editable_set($pdo, $id, current_user_id());

if (!$set) {
    set_flash('danger', 'Bạn không có quyền sửa bộ từ này.');
    redirect('pages/sets.php');
}

if ((int) $set['user_id'] !== current_user_id()) {
    $visibility = $set['visibility'] ?? ($set['is_public'] ? 'public' : 'private');
    $classId = (int) ($set['class_id'] ?? 0);
    $isPublic = (int) ($set['is_public'] ?? 0);
} elseif ($visibility === 'class' && !user_can_use_class($pdo, $classId, current_user_id())) {
    set_flash('danger', 'Vui lòng chọn một lớp bạn đang tham gia để cấp quyền học.');
    redirect('pages/edit_set.php?id=' . $id);
}

$stmt = $pdo->prepare('
    UPDATE vocabulary_sets
    SET title = ?, description = ?, is_public = ?, visibility = ?, class_id = ?, updated_at = NOW()
    WHERE id = ?
');
$stmt->execute([$title, $description, $isPublic, $visibility, $classId ?: null, $id]);

set_flash('success', 'Đã cập nhật bộ từ.');
redirect('pages/edit_set.php?id=' . $id);
