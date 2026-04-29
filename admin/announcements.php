<?php
require_once __DIR__ . '/_admin.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = max(0, (int) ($_POST['id'] ?? 0));
    $data = [trim($_POST['title'] ?? ''), trim($_POST['content'] ?? ''), $_POST['type'] ?? 'info', $_POST['target_role'] ?? 'all', isset($_POST['is_active']) ? 1 : 0, $_POST['start_at'] ?: null, $_POST['end_at'] ?: null];
    if ($data[0] === '' || $data[1] === '') { set_flash('danger', 'Tiêu đề và nội dung là bắt buộc.'); redirect('admin/announcements.php'); }
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE announcements SET title=?, content=?, type=?, target_role=?, is_active=?, start_at=?, end_at=? WHERE id=?');
        $stmt->execute([...$data, $id]);
        audit_log($pdo, 'announcement_update', 'announcement', $id, null, $_POST);
    } else {
        $stmt = $pdo->prepare('INSERT INTO announcements (title, content, type, target_role, is_active, start_at, end_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([...$data, current_user_id()]);
        audit_log($pdo, 'announcement_create', 'announcement', (int) $pdo->lastInsertId(), null, $_POST);
    }
    set_flash('success', 'Đã lưu thông báo.');
    redirect('admin/announcements.php');
}
$rows = $pdo->query('SELECT a.*, u.name creator FROM announcements a LEFT JOIN users u ON u.id=a.created_by ORDER BY a.created_at DESC')->fetchAll();
admin_header('Announcements', 'announcements');
?>
<section class="panel mb-4"><h2>Tạo thông báo</h2><form method="post" class="row g-3"><?= csrf_field() ?><div class="col-md-6"><input class="form-control" name="title" placeholder="Tiêu đề" required></div><div class="col-md-2"><select class="form-select" name="type"><?php foreach(['info','success','warning','danger'] as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select class="form-select" name="target_role"><?php foreach(['all','user','teacher','admin'] as $r): ?><option value="<?= $r ?>"><?= $r ?></option><?php endforeach; ?></select></div><div class="col-md-2 form-check pt-2"><input class="form-check-input" type="checkbox" name="is_active" checked> Active</div><div class="col-md-6"><input class="form-control" type="datetime-local" name="start_at"></div><div class="col-md-6"><input class="form-control" type="datetime-local" name="end_at"></div><div class="col-12"><textarea class="form-control" name="content" rows="3" placeholder="Nội dung" required></textarea></div><div class="col-12"><button class="btn btn-primary">Đăng thông báo</button></div></form></section>
<section class="panel"><div class="table-responsive"><table class="table"><thead><tr><th>Thông báo</th><th>Role</th><th>Type</th><th>Active</th><th>Thời gian</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><strong><?= e($r['title']) ?></strong><br><small><?= e($r['content']) ?></small></td><td><?= e($r['target_role']) ?></td><td><span class="badge text-bg-<?= e($r['type']) ?>"><?= e($r['type']) ?></span></td><td><?= (int)$r['is_active'] ? 'ON':'OFF' ?></td><td><?= e($r['start_at'] ?: '-') ?> - <?= e($r['end_at'] ?: '-') ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php admin_footer(); ?>
