<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/classes.php');
}

$classId = max(0, (int) ($_POST['class_id'] ?? 0));

if (!$pdo || $classId <= 0) {
    set_flash('danger', 'Yêu cầu không hợp lệ.');
    redirect('pages/classes.php');
}

$stmt = $pdo->prepare('DELETE FROM class_members WHERE class_id = ? AND user_id = ? AND role <> "owner"');
$stmt->execute([$classId, current_user_id()]);

set_flash($stmt->rowCount() ? 'success' : 'warning', $stmt->rowCount() ? 'Bạn đã rời lớp.' : 'Không thể rời lớp này.');
redirect('pages/classes.php');
