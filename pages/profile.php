<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Profile';
$userId = current_user_id();
$user = null;
$stats = [
    'sets' => 0,
    'cards' => 0,
    'correct' => 0,
    'wrong' => 0,
];
$results = [];
$progressRows = [];

if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

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

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(correct_count), 0), COALESCE(SUM(wrong_count), 0) FROM user_progress WHERE user_id = ?');
    $stmt->execute([$userId]);
    $progressTotals = $stmt->fetch(PDO::FETCH_NUM);
    $stats['correct'] = (int) ($progressTotals[0] ?? 0);
    $stats['wrong'] = (int) ($progressTotals[1] ?? 0);

    $stmt = $pdo->prepare('
        SELECT tr.*, s.title
        FROM test_results tr
        INNER JOIN vocabulary_sets s ON s.id = tr.set_id
        WHERE tr.user_id = ?
        ORDER BY tr.created_at DESC
        LIMIT 8
    ');
    $stmt->execute([$userId]);
    $results = $stmt->fetchAll();

    $stmt = $pdo->prepare('
        SELECT up.*, s.title AS set_title, f.term, f.definition
        FROM user_progress up
        INNER JOIN vocabulary_sets s ON s.id = up.set_id
        INNER JOIN flashcards f ON f.id = up.card_id
        WHERE up.user_id = ?
        ORDER BY up.updated_at DESC
        LIMIT 10
    ');
    $stmt->execute([$userId]);
    $progressRows = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>Profile</h1>
        <p>Thông tin tài khoản và lịch sử học tập.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
</div>

<?php if (!empty($database_error)): ?>
    <div class="alert alert-danger"><?= e($database_error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <section class="panel profile-card">
            <div class="profile-avatar"><?= e(mb_strtoupper(mb_substr(current_user_name(), 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
            <h2><?= e($user['name'] ?? current_user_name()) ?></h2>
            <p><?= e($user['email'] ?? ($_SESSION['user_email'] ?? '')) ?></p>
            <span class="badge text-bg-light">Tham gia: <?= $user ? e(date('d/m/Y', strtotime($user['created_at']))) : 'N/A' ?></span>
        </section>
    </div>
    <div class="col-lg-8">
        <div class="stats-grid compact-grid">
            <div class="stat-card">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-collection"></i></div>
                <div><span>Bộ từ</span><strong><?= e($stats['sets']) ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-card-text"></i></div>
                <div><span>Flashcards</span><strong><?= e($stats['cards']) ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-check2-circle"></i></div>
                <div><span>Đúng</span><strong><?= e($stats['correct']) ?></strong></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-x-circle"></i></div>
                <div><span>Sai</span><strong><?= e($stats['wrong']) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Kết quả test gần đây</h2>
                    <p>Điểm số được lưu sau mỗi lần nộp bài.</p>
                </div>
            </div>
            <?php if (!$results): ?>
                <div class="empty-state compact">
                    <i class="bi bi-ui-checks-grid"></i>
                    <h3>Chưa có kết quả test</h3>
                    <p>Làm một bài Test để xem lịch sử tại đây.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Bộ từ</th>
                            <th>Điểm</th>
                            <th>Ngày</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?= e($result['title']) ?></td>
                                <td><?= (int) $result['score'] ?>/<?= (int) $result['total_questions'] ?></td>
                                <td><?= e(date('d/m/Y H:i', strtotime($result['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Tiến độ gần đây</h2>
                    <p>Các thẻ đã học bằng Flashcards hoặc Learn.</p>
                </div>
            </div>
            <?php if (!$progressRows): ?>
                <div class="empty-state compact">
                    <i class="bi bi-graph-up"></i>
                    <h3>Chưa có tiến độ học</h3>
                    <p>Bắt đầu học để lưu tiến độ.</p>
                </div>
            <?php else: ?>
                <div class="progress-list">
                    <?php foreach ($progressRows as $row): ?>
                        <div class="progress-row">
                            <div>
                                <strong><?= e($row['term']) ?></strong>
                                <span><?= e($row['definition']) ?> · <?= e($row['set_title']) ?></span>
                            </div>
                            <div class="text-end">
                                <span class="badge text-bg-success"><?= (int) $row['correct_count'] ?> đúng</span>
                                <span class="badge text-bg-danger"><?= (int) $row['wrong_count'] ?> sai</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
