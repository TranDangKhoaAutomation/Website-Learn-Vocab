<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

$setId = max(0, (int) ($_POST['set_id'] ?? 0));
$term = trim($_POST['term'] ?? '');
$definition = trim($_POST['definition'] ?? '');
$pronunciation = trim($_POST['pronunciation'] ?? '');
$example = trim($_POST['example_sentence'] ?? '');
$imageUrl = trim($_POST['image_url'] ?? '');

if (!$pdo || $setId <= 0) {
    set_flash('danger', 'Yêu cầu không hợp lệ.');
    redirect('pages/sets.php');
}

if ($term === '' || $definition === '') {
    set_flash('danger', 'Vui lòng nhập từ tiếng Anh và nghĩa tiếng Việt.');
    redirect('pages/edit_set.php?id=' . $setId);
}

$set = get_editable_set($pdo, $setId, current_user_id());
if (!$set) {
    set_flash('danger', 'Bạn không có quyền thêm flashcard vào bộ từ này.');
    redirect('pages/sets.php');
}

$stmt = $pdo->prepare('
    INSERT INTO flashcards (set_id, term, definition, example_sentence, pronunciation, image_url, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
');
$stmt->execute([$setId, $term, $definition, $example, $pronunciation, $imageUrl]);

$stmt = $pdo->prepare('UPDATE vocabulary_sets SET updated_at = NOW() WHERE id = ?');
$stmt->execute([$setId]);

set_flash('success', 'Đã thêm flashcard.');
redirect('pages/edit_set.php?id=' . $setId);
