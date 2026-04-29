<?php
require_once __DIR__ . '/_admin.php';
$checks = [
    'PHP PDO' => extension_loaded('pdo_mysql') ? 'OK' : 'Missing',
    'Database' => $pdo ? 'OK' : 'Error',
    'Upload max size' => ini_get('upload_max_filesize'),
    'Memory limit' => ini_get('memory_limit'),
    'Audit logs' => admin_count($pdo, 'SELECT COUNT(*) FROM audit_logs'),
    'Pending feedback' => admin_count($pdo, 'SELECT COUNT(*) FROM feedback WHERE status IN ("open","reviewing")'),
];
admin_header('System Health', 'system_health');
?>
<section class="panel"><p class="text-muted">Thông tin kỹ thuật chỉ hiển thị cho admin đã đăng nhập.</p><div class="table-responsive"><table class="table"><tbody><?php foreach($checks as $k=>$v): ?><tr><th><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php admin_footer(); ?>
