<?php
require_once __DIR__ . '/_admin.php';
$stats = [
'Study sessions'=>admin_count($pdo,'SELECT COUNT(*) FROM study_sessions'),
'Tổng phút học'=>admin_count($pdo,'SELECT COALESCE(ROUND(SUM(duration_seconds)/60),0) FROM study_sessions'),
'Phút/user'=>admin_count($pdo,'SELECT COALESCE(ROUND(SUM(duration_seconds)/60 / NULLIF(COUNT(DISTINCT user_id),0)),0) FROM study_sessions'),
'Goal records'=>admin_count($pdo,'SELECT COUNT(*) FROM user_goals')
];
$modes = $pdo->query('SELECT mode, COUNT(*) total FROM user_progress GROUP BY mode ORDER BY total DESC')->fetchAll();
admin_header('Analytics', 'analytics');
?>
<div class="admin-stat-grid"><?php foreach($stats as $k=>$v): ?><div class="stat-card"><span><?= e($k) ?></span><strong><?= (int)$v ?></strong></div><?php endforeach; ?></div>
<section class="panel mt-4"><h2>Mode được dùng nhiều nhất</h2><?php foreach($modes as $m): ?><div class="admin-mini-row"><strong><?= e($m['mode']) ?></strong><span><?= (int)$m['total'] ?></span></div><?php endforeach; ?></section>
<?php admin_footer(); ?>
