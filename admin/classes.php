<?php
require_once __DIR__ . '/_admin.php';
$classes = $pdo->query('SELECT c.*, u.name owner_name, COUNT(cm.id) member_count FROM learning_classes c JOIN users u ON u.id=c.owner_id LEFT JOIN class_members cm ON cm.class_id=c.id GROUP BY c.id ORDER BY c.created_at DESC')->fetchAll();
admin_header('Classes', 'classes');
?>
<section class="panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Lớp</th><th>Owner</th><th>Invite code</th><th>Members</th><th>Created</th></tr></thead><tbody><?php foreach ($classes as $class): ?><tr><td><strong><?= e($class['name']) ?></strong><br><small><?= e($class['description']) ?></small></td><td><?= e($class['owner_name']) ?></td><td><code><?= e($class['invite_code']) ?></code></td><td><?= (int)$class['member_count'] ?></td><td><?= e($class['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php admin_footer(); ?>
