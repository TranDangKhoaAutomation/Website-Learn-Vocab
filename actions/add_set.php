<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';
require_once __DIR__ . '/../includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/sets.php');
}

if (!$pdo) {
    set_flash('danger', 'Không thể kết nối database.');
    redirect('pages/create_set.php');
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$visibility = $_POST['visibility'] ?? 'private';
$visibility = in_array($visibility, ['private', 'public', 'class'], true) ? $visibility : 'private';
$classId = $visibility === 'class' ? max(0, (int) ($_POST['class_id'] ?? 0)) : 0;
$isPublic = $visibility === 'public' ? 1 : 0;
$setStatus = ($visibility === 'public' && get_setting('public_set_moderation', false)) ? 'pending' : 'active';

if ($title === '') {
    set_old($_POST);
    set_flash('danger', 'Vui lòng nhập tiêu đề bộ từ.');
    redirect('pages/create_set.php');
}

if ($visibility === 'class' && !user_can_use_class($pdo, $classId, current_user_id())) {
    set_old($_POST);
    set_flash('danger', 'Vui lòng chọn một lớp bạn đang tham gia để cấp quyền học.');
    redirect('pages/create_set.php');
}

$terms = $_POST['term'] ?? [];
$definitions = $_POST['definition'] ?? [];
$pronunciations = $_POST['pronunciation'] ?? [];
$examples = $_POST['example_sentence'] ?? [];
$images = $_POST['image_url'] ?? [];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO vocabulary_sets (user_id, class_id, title, description, is_public, visibility, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ');
    $stmt->execute([current_user_id(), $classId ?: null, $title, $description, $isPublic, $visibility, $setStatus]);
    $setId = (int) $pdo->lastInsertId();

    $achievementStmt = $pdo->prepare('INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at) SELECT ?, id, NOW() FROM achievements WHERE code = "first_set"');
    $achievementStmt->execute([current_user_id()]);

    $cardStmt = $pdo->prepare('
        INSERT INTO flashcards (set_id, term, definition, example_sentence, pronunciation, image_url, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');

    foreach ($terms as $index => $termValue) {
        $term = trim($termValue);
        $definition = trim($definitions[$index] ?? '');

        if ($term === '' && $definition === '') {
            continue;
        }

        if ($term === '' || $definition === '') {
            throw new RuntimeException('Mỗi flashcard cần có cả từ tiếng Anh và nghĩa tiếng Việt.');
        }

        $cardStmt->execute([
            $setId,
            $term,
            $definition,
            trim($examples[$index] ?? ''),
            trim($pronunciations[$index] ?? ''),
            trim($images[$index] ?? ''),
        ]);
    }

    $pdo->commit();
    set_flash('success', 'Đã tạo bộ từ mới.');
    redirect('pages/edit_set.php?id=' . $setId);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_old($_POST);
    set_flash('danger', $exception instanceof RuntimeException ? $exception->getMessage() : 'Không thể tạo bộ từ. Vui lòng thử lại.');
    redirect('pages/create_set.php');
}
