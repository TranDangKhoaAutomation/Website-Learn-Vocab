<?php
require_once __DIR__ . '/_admin.php';
$rows = $pdo->query('SELECT f.*, u.name user_name FROM feedback f JOIN users u ON u.id=f.user_id ORDER BY f.created_at DESC LIMIT 50')->fetchAll();
admin_header('Reports', 'reports');
?>
<section class="panel"><p class="text-muted">Báo cáo nội dung và lỗi hệ thống gần đây.</p><div class="table-responsive"><table class="table"><thead><tr><th>Type</th><th>Title</th><th>User</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['type']) ?></td><td><?= e($row['title']) ?></td><td><?= e($row['user_name']) ?></td><td><span class="badge <?= admin_badge($row['status']) ?>"><?= e($row['status']) ?></span></td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php admin_footer(); ?>
