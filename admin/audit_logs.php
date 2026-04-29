<?php
require_once __DIR__ . '/_admin.php';
$q=admin_page_param('q'); $where=' WHERE 1=1 '; $params=[]; if($q!==''){ $where.=' AND (a.action LIKE ? OR a.target_type LIKE ? OR u.name LIKE ?) '; array_push($params,"%$q%","%$q%","%$q%");}
[$logs,$total,$pages,$page]=admin_paginate($pdo,"SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id $where","SELECT a.*,u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id $where ORDER BY a.created_at DESC",$params,max(1,(int)($_GET['page']??1)),20);
admin_header('Audit Logs','audit_logs');
?>
<section class="panel"><form class="admin-filters" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Admin/action/target"><button class="btn btn-primary">Tìm</button></form><div class="table-responsive mt-3"><table class="table"><thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>IP</th></tr></thead><tbody><?php foreach($logs as $log): ?><tr><td><?= e($log['created_at']) ?></td><td><?= e($log['user_name'] ?: '-') ?></td><td><?= e($log['action']) ?></td><td><?= e($log['target_type']) ?> #<?= e($log['target_id'] ?: '-') ?></td><td><?= e($log['ip_address']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php admin_pagination($page,$pages); ?></section>
<?php admin_footer(); ?>
