<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/set_access.php';

if (!settings_enabled('public_library_enabled', true)) {
    set_flash('warning', 'Thư viện công khai đang tạm tắt.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $setId = max(0, (int) ($_POST['set_id'] ?? 0));

    $check = $pdo->prepare('SELECT id FROM vocabulary_sets WHERE id = ? AND visibility = "public" AND status = "active" LIMIT 1');
    $check->execute([$setId]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO saved_sets (user_id, set_id, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([current_user_id(), $setId]);
        set_flash('success', 'Đã lưu bộ từ vào thư viện của bạn.');
    } else {
        set_flash('danger', 'Bộ từ public không tồn tại hoặc chưa được duyệt.');
    }

    redirect('pages/library.php');
}

$pageTitle = 'Public Library';
$q = trim($_GET['q'] ?? '');
$level = trim($_GET['level'] ?? '');
$where = ' WHERE s.visibility = "public" AND s.status = "active" ';
$params = [];

if (in_array($level, ['beginner','elementary','intermediate','upper_intermediate','advanced'], true)) {
    $where .= ' AND s.level = ? ';
    $params[] = $level;
}

$stmt = $pdo->prepare("
    SELECT s.*, u.name owner_name, c.name category_name, COUNT(f.id) card_count,
           GROUP_CONCAT(f.term SEPARATOR ' ') card_terms,
           GROUP_CONCAT(f.definition SEPARATOR ' ') card_definitions,
           ss.id saved_id
    FROM vocabulary_sets s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN categories c ON c.id = s.category_id
    LEFT JOIN flashcards f ON f.set_id = s.id
    LEFT JOIN saved_sets ss ON ss.set_id = s.id AND ss.user_id = ?
    $where
    GROUP BY s.id
    ORDER BY s.updated_at DESC
");
$stmt->execute([current_user_id(), ...$params]);
$sets = rank_sets_by_query($stmt->fetchAll(), $q);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>Public Library</h1>
        <p>Khám phá các bộ từ public đã được duyệt.</p>
    </div>
</div>

<section class="panel mb-4">
    <form class="admin-filters" method="get">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm bộ từ">
        <select class="form-select" name="level">
            <option value="">Level</option>
            <?php foreach (['beginner','elementary','intermediate','upper_intermediate','advanced'] as $item): ?>
                <option value="<?= e($item) ?>" <?= $level === $item ? 'selected' : '' ?>><?= e($item) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Tìm</button>
    </form>
</section>

<section class="set-grid">
    <?php if (!$sets): ?>
        <div class="empty-state panel">
            <i class="bi bi-globe2"></i>
            <h3>Chưa có bộ từ public phù hợp</h3>
            <p>Thử đổi từ khóa hoặc bộ lọc.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($sets as $set): ?>
        <article class="set-card">
            <div class="set-card-body">
                <div class="d-flex justify-content-between gap-2">
                    <h3><?= e($set['title']) ?></h3>
                </div>
                <p><?= e($set['description']) ?></p>
                <div class="set-meta">
                    <span><?= (int) $set['card_count'] ?> flashcards</span>
                    <span><?= e($set['level']) ?></span>
                    <span><?= e($set['category_name'] ?: 'General') ?></span>
                </div>
            </div>
            <div class="set-card-actions">
                <a class="btn btn-sm btn-primary" href="<?= app_url('pages/flashcards.php') ?>?set_id=<?= (int) $set['id'] ?>">Học ngay</a>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="set_id" value="<?= (int) $set['id'] ?>">
                    <button class="btn btn-sm btn-outline-primary" <?= $set['saved_id'] ? 'disabled' : '' ?>><?= $set['saved_id'] ? 'Đã lưu' : 'Lưu' ?></button>
                </form>
                <?php if (settings_enabled('feedback_enabled', true)): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= app_url('pages/feedback.php') ?>?target_type=set&target_id=<?= (int) $set['id'] ?>">Báo lỗi</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
