<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/classes.php');
}

if (!$pdo) {
    set_flash('danger', 'Không thể kết nối database.');
    redirect('pages/classes.php');
}

$inviteCode = strtoupper(trim($_POST['invite_code'] ?? ''));

if ($inviteCode === '') {
    set_flash('danger', 'Vui lòng nhập mã mời.');
    redirect('pages/classes.php');
}

$stmt = $pdo->prepare('SELECT id FROM learning_classes WHERE invite_code = ? LIMIT 1');
$stmt->execute([$inviteCode]);
$classId = (int) $stmt->fetchColumn();

if (!$classId) {
    set_flash('danger', 'Mã mời không hợp lệ.');
    redirect('pages/classes.php');
}

$stmt = $pdo->prepare('
    INSERT INTO class_members (class_id, user_id, role, created_at)
    VALUES (?, ?, "member", NOW())
    ON DUPLICATE KEY UPDATE role = role
');
$stmt->execute([$classId, current_user_id()]);

set_flash('success', 'Bạn đã tham gia lớp.');
redirect('pages/classes.php');
