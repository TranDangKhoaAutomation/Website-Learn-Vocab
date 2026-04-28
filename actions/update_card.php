<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

$id = max(0, (int) ($_POST['id'] ?? 0));
$term = trim($_POST['term'] ?? '');
$definition = trim($_POST['definition'] ?? '');
$pronunciation = trim($_POST['pronunciation'] ?? '');
$example = trim($_POST['example_sentence'] ?? '');
$imageUrl = trim($_POST['image_url'] ?? '');

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
    set_flash('danger', 'Bạn không có quyền sửa flashcard này.');
    redirect('pages/sets.php');
}

if ($term === '' || $definition === '') {
    set_flash('danger', 'Từ tiếng Anh và nghĩa tiếng Việt không được để trống.');
    redirect('pages/edit_set.php?id=' . (int) $card['set_id']);
}

$stmt = $pdo->prepare('
    UPDATE flashcards
    SET term = ?, definition = ?, example_sentence = ?, pronunciation = ?, image_url = ?
    WHERE id = ?
');
$stmt->execute([$term, $definition, $example, $pronunciation, $imageUrl, $id]);

$stmt = $pdo->prepare('UPDATE vocabulary_sets SET updated_at = NOW() WHERE id = ?');
$stmt->execute([(int) $card['set_id']]);

set_flash('success', 'Đã cập nhật flashcard.');
redirect('pages/edit_set.php?id=' . (int) $card['set_id']);
