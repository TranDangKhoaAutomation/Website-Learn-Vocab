<?php
require_once __DIR__ . '/_admin.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = max(0, (int) ($_POST['id'] ?? 0));
    $status = in_array($_POST['status'] ?? '', ['open','reviewing','resolved','rejected'], true) ? $_POST['status'] : 'open';
    $reply = trim($_POST['admin_reply'] ?? '');
    $stmt = $pdo->prepare('UPDATE feedback SET status=?, admin_reply=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$status, $reply, $id]);
    audit_log($pdo, 'feedback_reply', 'feedback', $id, null, $_POST);
    set_flash('success', 'Đã cập nhật feedback.');
    redirect('admin/feedback.php');
}
$type = admin_page_param('type'); $status = admin_page_param('status'); $where=' WHERE 1=1 '; $params=[];
if (in_array($type,['bug','content_error','suggestion','other'],true)) { $where.=' AND f.type=? '; $params[]=$type; }
if (in_array($status,['open','reviewing','resolved','rejected'],true)) { $where.=' AND f.status=? '; $params[]=$status; }
[$rows,$total,$pages,$page]=admin_paginate($pdo,"SELECT COUNT(*) FROM feedback f $where","SELECT f.*,u.name user_name,u.email FROM feedback f JOIN users u ON u.id=f.user_id $where ORDER BY f.created_at DESC",$params,max(1,(int)($_GET['page']??1)),10);
admin_header('Feedback', 'feedback');
?>
<section class="panel"><form class="admin-filters" method="get"><select class="form-select" name="type"><option value="">Type</option><?php foreach(['bug','content_error','suggestion','other'] as $v): ?><option value="<?= $v ?>" <?= $type===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select><select class="form-select" name="status"><option value="">Status</option><?php foreach(['open','reviewing','resolved','rejected'] as $v): ?><option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select><button class="btn btn-primary">Lọc</button></form>
<?php foreach($rows as $r): ?><form method="post" class="admin-feedback-card"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><div class="feedback-admin-ticket"><div class="feedback-admin-body"><strong><?= e($r['title']) ?></strong> <span class="badge <?= admin_badge($r['status']) ?>"><?= e($r['status']) ?></span><p><?= e($r['message']) ?></p><small><?= e($r['user_name']) ?> - <?= e($r['email']) ?> - <?= e($r['type']) ?> - <?= e($r['target_type']) ?> #<?= e($r['target_id'] ?: '-') ?></small></div><?php if(!empty($r['screenshot_path'])): ?><a class="feedback-thumb" href="<?= BASE_URL . e($r['screenshot_path']) ?>" target="_blank" rel="noopener"><img src="<?= BASE_URL . e($r['screenshot_path']) ?>" alt="Ảnh feedback"></a><?php endif; ?></div><div class="row g-2 mt-2"><div class="col-md-3"><select class="form-select" name="status"><?php foreach(['open','reviewing','resolved','rejected'] as $v): ?><option value="<?= $v ?>" <?= $r['status']===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div><div class="col-md-7"><input class="form-control" name="admin_reply" value="<?= e($r['admin_reply']) ?>" placeholder="Phản hồi admin"></div><div class="col-md-2"><button class="btn btn-primary w-100">Lưu</button></div></div></form><?php endforeach; ?><?php admin_pagination($page,$pages); ?></section>
<?php admin_footer(); ?>
