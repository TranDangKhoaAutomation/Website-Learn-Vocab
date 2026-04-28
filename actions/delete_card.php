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

$stmt = $pdo->prepare('
    SELECT f.id, f.set_id
    FROM flashcards f
    WHERE f.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$card = $stmt->fetch();

if (!$card || !get_editable_set($pdo, (int) $card['set_id'], current_user_id())) {
    set_flash('danger', 'Bạn không có quyền xóa flashcard này.');
    redirect('pages/sets.php');
}

$stmt = $pdo->prepare('DELETE FROM flashcards WHERE id = ?');
$stmt->execute([$id]);

$stmt = $pdo->prepare('UPDATE vocabulary_sets SET updated_at = NOW() WHERE id = ?');
$stmt->execute([(int) $card['set_id']]);

set_flash('success', 'Đã xóa flashcard.');
redirect('pages/edit_set.php?id=' . (int) $card['set_id']);
