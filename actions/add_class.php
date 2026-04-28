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

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '') {
    set_flash('danger', 'Vui lòng nhập tên lớp.');
    redirect('pages/classes.php');
}

function generate_invite_code(PDO $pdo): string
{
    do {
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmt = $pdo->prepare('SELECT id FROM learning_classes WHERE invite_code = ? LIMIT 1');
        $stmt->execute([$code]);
    } while ($stmt->fetch());

    return $code;
}

try {
    $pdo->beginTransaction();
    $code = generate_invite_code($pdo);

    $stmt = $pdo->prepare('INSERT INTO learning_classes (owner_id, name, description, invite_code, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([current_user_id(), $name, $description, $code]);
    $classId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO class_members (class_id, user_id, role, created_at) VALUES (?, ?, "owner", NOW())');
    $stmt->execute([$classId, current_user_id()]);

    $pdo->commit();
    set_flash('success', 'Đã tạo lớp mới. Mã mời: ' . $code);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Không thể tạo lớp. Vui lòng thử lại.');
}

redirect('pages/classes.php');
