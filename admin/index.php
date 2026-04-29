<?php
require_once __DIR__ . '/_admin.php';

$stats = [
    'Tổng user' => admin_count($pdo, 'SELECT COUNT(*) FROM users'),
    'Teacher' => admin_count($pdo, 'SELECT COUNT(*) FROM users WHERE role = "teacher"'),
    'Admin' => admin_count($pdo, 'SELECT COUNT(*) FROM users WHERE role = "admin"'),
    'Bộ từ' => admin_count($pdo, 'SELECT COUNT(*) FROM vocabulary_sets WHERE status <> "deleted"'),
    'Flashcards' => admin_count($pdo, 'SELECT COUNT(*) FROM flashcards'),
    'Lớp học' => admin_count($pdo, 'SELECT COUNT(*) FROM learning_classes'),
    'Lượt học' => admin_count($pdo, 'SELECT COALESCE(SUM(correct_count + wrong_count), 0) FROM user_progress'),
    'Bài test' => admin_count($pdo, 'SELECT COUNT(*) FROM test_results'),
];

$newUsers = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6')->fetchAll();
$newSets = $pdo->query('SELECT s.id, s.title, s.visibility, s.created_at, u.name owner_name FROM vocabulary_sets s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC LIMIT 6')->fetchAll();
$topSets = $pdo->query('
    SELECT s.title, COUNT(up.id) total
    FROM vocabulary_sets s
    LEFT JOIN user_progress up ON up.set_id = s.id
    GROUP BY s.id
    ORDER BY total DESC
    LIMIT 6
')->fetchAll();
$topUsers = $pdo->query('
    SELECT u.name, COUNT(up.id) total
    FROM users u
    LEFT JOIN user_progress up ON up.user_id = u.id
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 6
')->fetchAll();
$chartRows = $pdo->query('
    SELECT DATE(updated_at) day, SUM(correct_count + wrong_count) total
    FROM user_progress
    WHERE updated_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(updated_at)
    ORDER BY day ASC
')->fetchAll();

admin_header('Dashboard', 'index');
?>
<div class="admin-stat-grid">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat-card"><span><?= e($label) ?></span><strong><?= (int) $value ?></strong></div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <section class="panel">
            <div class="panel-header"><div><h2>Thống kê học tập 7 ngày</h2><p>Lượt trả lời đúng/sai được ghi nhận.</p></div></div>
            <canvas id="adminStudyChart" height="110"></canvas>
            <script type="application/json" id="adminStudyChartData"><?= json_encode($chartRows, JSON_UNESCAPED_UNICODE) ?></script>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="panel">
            <div class="panel-header"><div><h2>Top bộ từ được học</h2><p>Dựa trên user_progress.</p></div></div>
            <div class="list-group list-group-flush">
                <?php foreach ($topSets as $row): ?>
                    <div class="list-group-item d-flex justify-content-between px-0"><span><?= e($row['title']) ?></span><strong><?= (int) $row['total'] ?></strong></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-4">
        <section class="panel">
            <h2>User mới</h2>
            <?php foreach ($newUsers as $user): ?>
                <div class="admin-mini-row"><div><strong><?= e($user['name']) ?></strong><small><?= e($user['email']) ?></small></div><span class="badge <?= admin_badge($user['role']) ?>"><?= e($user['role']) ?></span></div>
            <?php endforeach; ?>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="panel">
            <h2>Bộ từ mới</h2>
            <?php foreach ($newSets as $set): ?>
                <div class="admin-mini-row"><div><strong><?= e($set['title']) ?></strong><small><?= e($set['owner_name']) ?></small></div><span class="badge text-bg-light"><?= e($set['visibility']) ?></span></div>
            <?php endforeach; ?>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="panel">
            <h2>Top user học nhiều</h2>
            <?php foreach ($topUsers as $row): ?>
                <div class="admin-mini-row"><strong><?= e($row['name']) ?></strong><span><?= (int) $row['total'] ?> lượt</span></div>
            <?php endforeach; ?>
        </section>
    </div>
</div>
<?php admin_footer(); ?>
