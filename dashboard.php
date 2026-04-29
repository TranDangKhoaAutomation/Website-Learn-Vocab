<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/settings.php';

$pageTitle = 'Dashboard';
$userId = current_user_id();
$stats = [
    'sets' => 0,
    'cards' => 0,
    'study' => 0,
    'last_score' => null,
];
$recentSets = [];
$announcements = [];
$achievementRows = [];
$leaderboardRows = [];
$leaderboardEnabled = settings_enabled('leaderboard_enabled', true);
$todayGoal = ['cards' => 0, 'minutes' => 0, 'cards_goal' => 20, 'minutes_goal' => 10, 'streak' => 0];

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

    $stmt = $pdo->prepare('
        SELECT * FROM announcements
        WHERE is_active = 1
          AND target_role IN ("all", ?)
          AND (start_at IS NULL OR start_at <= NOW())
          AND (end_at IS NULL OR end_at >= NOW())
        ORDER BY created_at DESC
        LIMIT 3
    ');
    $stmt->execute([current_user_role()]);
    $announcements = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(cards_studied),0) cards, COALESCE(ROUND(SUM(duration_seconds)/60),0) minutes FROM study_sessions WHERE user_id=? AND DATE(created_at)=CURDATE()');
    $stmt->execute([$userId]);
    $today = $stmt->fetch() ?: [];
    $stmt = $pdo->prepare('SELECT daily_cards_goal, daily_minutes_goal FROM user_goals WHERE user_id=?');
    $stmt->execute([$userId]);
    $goal = $stmt->fetch() ?: ['daily_cards_goal'=>20,'daily_minutes_goal'=>10];
    $todayGoal = ['cards'=>(int)($today['cards']??0),'minutes'=>(int)($today['minutes']??0),'cards_goal'=>(int)$goal['daily_cards_goal'],'minutes_goal'=>(int)$goal['daily_minutes_goal'],'streak'=>0];

    $stmt = $pdo->prepare('SELECT a.* FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=? ORDER BY ua.earned_at DESC LIMIT 5');
    $stmt->execute([$userId]);
    $achievementRows = $stmt->fetchAll();

    if ($leaderboardEnabled) {
        $stmt = $pdo->query('
            SELECT u.name, COALESCE(SUM(up.correct_count + up.wrong_count), 0) total
            FROM users u
            LEFT JOIN user_progress up ON up.user_id = u.id
            GROUP BY u.id
            HAVING total > 0
            ORDER BY total DESC
            LIMIT 5
        ');
        $leaderboardRows = $stmt->fetchAll();
    }
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
        <a class="btn btn-outline-primary" href="<?= app_url('pages/sets.php') ?>"><i class="bi bi-collection"></i> My Sets</a>
        <a class="btn btn-primary" href="<?= app_url('pages/create_set.php') ?>"><i class="bi bi-plus-circle"></i> Tạo bộ từ</a>
    </div>
</div>

<?php foreach ($announcements as $announcement): ?>
    <div class="alert alert-<?= e($announcement['type']) ?> app-alert"><strong><?= e($announcement['title']) ?>:</strong> <?= e($announcement['content']) ?></div>
<?php endforeach; ?>

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
            <div class="panel-header"><div><h2>Mục tiêu hôm nay</h2><p>Theo dõi số thẻ và phút học trong ngày.</p></div><span class="badge text-bg-primary">Streak <?= (int) $todayGoal['streak'] ?> ngày</span></div>
            <div class="row g-3">
                <div class="col-md-6"><div class="goal-box"><div class="d-flex justify-content-between"><strong>Flashcards</strong><span><?= (int)$todayGoal['cards'] ?>/<?= (int)$todayGoal['cards_goal'] ?></span></div><div class="progress"><div class="progress-bar" style="width:<?= min(100, round($todayGoal['cards'] / max(1, $todayGoal['cards_goal']) * 100)) ?>%"></div></div></div></div>
                <div class="col-md-6"><div class="goal-box"><div class="d-flex justify-content-between"><strong>Phút học</strong><span><?= (int)$todayGoal['minutes'] ?>/<?= (int)$todayGoal['minutes_goal'] ?></span></div><div class="progress"><div class="progress-bar bg-success" style="width:<?= min(100, round($todayGoal['minutes'] / max(1, $todayGoal['minutes_goal']) * 100)) ?>%"></div></div></div></div>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="panel">
            <h2>Achievements</h2>
            <?php if (!$achievementRows): ?><p class="text-muted mb-0">Hoàn thành bài học đầu tiên để nhận huy hiệu.</p><?php endif; ?>
            <?php foreach ($achievementRows as $achievement): ?><div class="admin-mini-row"><span><i class="bi <?= e($achievement['icon']) ?>"></i> <?= e($achievement['name']) ?></span></div><?php endforeach; ?>
        </section>
        <?php if ($leaderboardEnabled): ?>
            <section class="panel mt-4">
                <h2>Leaderboard</h2>
                <?php if (!$leaderboardRows): ?><p class="text-muted mb-0">Chưa có dữ liệu học tập để xếp hạng.</p><?php endif; ?>
                <?php foreach ($leaderboardRows as $index => $row): ?>
                    <div class="admin-mini-row">
                        <div><strong>#<?= $index + 1 ?> <?= e($row['name']) ?></strong><small>Lượt học</small></div>
                        <span class="badge text-bg-primary"><?= (int) $row['total'] ?></span>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
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
                <a class="btn btn-sm btn-outline-primary" href="<?= app_url('pages/sets.php') ?>">Xem tất cả</a>
            </div>

            <?php if (!$recentSets): ?>
                <div class="empty-state">
                    <i class="bi bi-journal-plus"></i>
                    <h3>Bạn chưa có bộ từ nào</h3>
                    <p>Tạo bộ từ đầu tiên để bắt đầu luyện tập.</p>
                    <a class="btn btn-primary" href="<?= app_url('pages/create_set.php') ?>">Tạo bộ từ mới</a>
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
                                <a class="btn btn-sm btn-primary" href="<?= app_url('pages/flashcards.php') ?>?set_id=<?= (int) $set['id'] ?>">Học</a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= app_url('pages/edit_set.php') ?>?id=<?= (int) $set['id'] ?>">Sửa</a>
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
                <a href="<?= app_url('pages/flashcards.php') ?>"><i class="bi bi-card-text"></i> Flashcards</a>
                <a href="<?= app_url('pages/learn.php') ?>"><i class="bi bi-mortarboard"></i> Learn</a>
                <a href="<?= app_url('pages/test.php') ?>"><i class="bi bi-ui-checks-grid"></i> Test</a>
                <a href="<?= app_url('pages/blast.php') ?>"><i class="bi bi-rocket-takeoff"></i> Blast</a>
            </div>
        </section>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
