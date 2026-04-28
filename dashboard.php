<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Dashboard';
$userId = current_user_id();
$stats = [
    'sets' => 0,
    'cards' => 0,
    'study' => 0,
    'last_score' => null,
];
$recentSets = [];

if ($pdo) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM vocabulary_sets WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats['sets'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('
        SELECT COUNT(f.id)
        FROM flashcards f
        INNER JOIN vocabulary_sets s ON s.id = f.set_id
        WHERE s.user_id = ?
    ');
    $stmt->execute([$userId]);
    $stats['cards'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(correct_count + wrong_count), 0) FROM user_progress WHERE user_id = ?');
    $stmt->execute([$userId]);
    $stats['study'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT score, total_questions, created_at FROM test_results WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$userId]);
    $lastResult = $stmt->fetch();
    $stats['last_score'] = $lastResult ? round(((int) $lastResult['score'] / max(1, (int) $lastResult['total_questions'])) * 100) . '%' : 'Chưa có';

    $stmt = $pdo->prepare('
        SELECT s.*, COALESCE(fc.card_count, 0) AS card_count
        FROM vocabulary_sets s
        LEFT JOIN (
            SELECT set_id, COUNT(*) AS card_count
            FROM flashcards
            GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE s.user_id = ?
        ORDER BY s.updated_at DESC, s.created_at DESC
        LIMIT 5
    ');
    $stmt->execute([$userId]);
    $recentSets = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/includes/navbar.php';
?>

<?php if (!empty($database_error)): ?>
    <div class="alert alert-danger"><?= e($database_error) ?></div>
<?php endif; ?>

<div class="page-heading">
    <div>
        <h1>Xin chào, <?= e(current_user_name()) ?></h1>
        <p>Theo dõi tiến độ và bắt đầu một phiên học mới.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>pages/sets.php"><i class="bi bi-collection"></i> My Sets</a>
        <a class="btn btn-primary" href="<?= BASE_URL ?>pages/create_set.php"><i class="bi bi-plus-circle"></i> Tạo bộ từ</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-collection"></i></div>
        <div>
            <span>Tổng bộ từ</span>
            <strong><?= e($stats['sets']) ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-card-text"></i></div>
        <div>
            <span>Tổng flashcards</span>
            <strong><?= e($stats['cards']) ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-graph-up-arrow"></i></div>
        <div>
            <span>Lượt học</span>
            <strong><?= e($stats['study']) ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-award"></i></div>
        <div>
            <span>Điểm test gần nhất</span>
            <strong><?= e($stats['last_score']) ?></strong>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-8">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Bộ từ gần đây</h2>
                    <p>Chọn một bộ để học nhanh bằng chế độ phù hợp.</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>pages/sets.php">Xem tất cả</a>
            </div>

            <?php if (!$recentSets): ?>
                <div class="empty-state">
                    <i class="bi bi-journal-plus"></i>
                    <h3>Bạn chưa có bộ từ nào</h3>
                    <p>Tạo bộ từ đầu tiên để bắt đầu luyện tập.</p>
                    <a class="btn btn-primary" href="<?= BASE_URL ?>pages/create_set.php">Tạo bộ từ mới</a>
                </div>
            <?php else: ?>
                <div class="set-list">
                    <?php foreach ($recentSets as $set): ?>
                        <article class="set-row">
                            <div>
                                <h3><?= e($set['title']) ?></h3>
                                <p><?= e($set['description'] ?: 'Không có mô tả') ?></p>
                                <span class="badge text-bg-light"><?= (int) $set['card_count'] ?> flashcards</span>
                            </div>
                            <div class="set-row-actions">
                                <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>pages/flashcards.php?set_id=<?= (int) $set['id'] ?>">Học</a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>pages/edit_set.php?id=<?= (int) $set['id'] ?>">Sửa</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2>Học nhanh</h2>
                    <p>Vào thẳng một chế độ luyện tập.</p>
                </div>
            </div>
            <div class="quick-actions">
                <a href="<?= BASE_URL ?>pages/flashcards.php"><i class="bi bi-card-text"></i> Flashcards</a>
                <a href="<?= BASE_URL ?>pages/learn.php"><i class="bi bi-mortarboard"></i> Learn</a>
                <a href="<?= BASE_URL ?>pages/test.php"><i class="bi bi-ui-checks-grid"></i> Test</a>
                <a href="<?= BASE_URL ?>pages/blast.php"><i class="bi bi-rocket-takeoff"></i> Blast</a>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
