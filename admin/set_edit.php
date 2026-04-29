<?php
require_once __DIR__ . '/_admin.php';
$id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$stmt = $pdo->prepare('SELECT * FROM vocabulary_sets WHERE id = ?'); $stmt->execute([$id]); $set = $stmt->fetch();
if (!$set) { set_flash('danger', 'Không tìm thấy bộ từ.'); redirect('admin/sets.php'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? ''); $description = trim($_POST['description'] ?? '');
    $userId = max(1, (int) ($_POST['user_id'] ?? $set['user_id']));
    $classId = max(0, (int) ($_POST['class_id'] ?? 0)) ?: null;
    $categoryId = max(0, (int) ($_POST['category_id'] ?? 0)) ?: null;
    $visibility = in_array($_POST['visibility'] ?? '', ['private','public','class'], true) ? $_POST['visibility'] : 'private';
    $status = in_array($_POST['status'] ?? '', ['active','pending','hidden','deleted'], true) ? $_POST['status'] : 'active';
    $level = in_array($_POST['level'] ?? '', ['beginner','elementary','intermediate','upper_intermediate','advanced'], true) ? $_POST['level'] : 'beginner';
    if ($title === '') { set_flash('danger', 'Tiêu đề không được trống.'); redirect('admin/set_edit.php?id=' . $id); }
    $stmt = $pdo->prepare('UPDATE vocabulary_sets SET user_id=?, class_id=?, title=?, description=?, is_public=?, visibility=?, status=?, category_id=?, level=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$userId, $classId, $title, $description, $visibility === 'public' ? 1 : 0, $visibility, $status, $categoryId, $level, $id]);
    audit_log($pdo, 'set_update', 'set', $id, $set, $_POST);
    set_flash('success', 'Đã lưu bộ từ.');
    redirect('admin/set_edit.php?id=' . $id);
}
$users = $pdo->query('SELECT id,name,email FROM users ORDER BY name')->fetchAll();
$classes = $pdo->query('SELECT id,name FROM learning_classes ORDER BY name')->fetchAll();
$categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
admin_header('Sửa bộ từ', 'sets');
?>
<section class="panel"><form method="post" class="stack-form">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>">
<div class="row g-3">
<div class="col-md-8"><label class="form-label">Title</label><input class="form-control" name="title" value="<?= e($set['title']) ?>" required></div>
<div class="col-md-4"><label class="form-label">Owner</label><select class="form-select" name="user_id"><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (int)$set['user_id']===(int)$u['id']?'selected':'' ?>><?= e($u['name']) ?> - <?= e($u['email']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e($set['description']) ?></textarea></div>
<div class="col-md-3"><label class="form-label">Visibility</label><select class="form-select" name="visibility"><?php foreach (['private','public','class'] as $v): ?><option value="<?= $v ?>" <?= $set['visibility']===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['active','pending','hidden','deleted'] as $s): ?><option value="<?= $s ?>" <?= $set['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Level</label><select class="form-select" name="level"><?php foreach (['beginner','elementary','intermediate','upper_intermediate','advanced'] as $l): ?><option value="<?= $l ?>" <?= $set['level']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label">Category</label><select class="form-select" name="category_id"><option value="0">Không chọn</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($set['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Class</label><select class="form-select" name="class_id"><option value="0">Không gán lớp</option><?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($set['class_id']??0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
</div><button class="btn btn-primary mt-3">Lưu</button></form></section>
<?php admin_footer(); ?>
