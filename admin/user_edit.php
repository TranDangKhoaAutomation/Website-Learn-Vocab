<?php
require_once __DIR__ . '/_admin.php';

$id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    set_flash('danger', 'Không tìm thấy user.');
    redirect('admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'update';
    if ($action === 'delete') {
        if ($id === current_user_id()) {
            set_flash('danger', 'Admin không thể tự xóa chính mình.');
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            audit_log($pdo, 'user_delete', 'user', $id, $user);
            set_flash('success', 'Đã xóa user.');
            redirect('admin/users.php');
        }
    } elseif ($action === 'reset_password') {
        $tempPassword = 'Temp@123456';
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($tempPassword, PASSWORD_DEFAULT), $id]);
        audit_log($pdo, 'user_reset_password', 'user', $id);
        set_flash('success', 'Mật khẩu tạm thời: Temp@123456');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['user', 'teacher', 'admin'], true) ? $_POST['role'] : 'user';
        $status = in_array($_POST['status'] ?? '', ['active', 'locked'], true) ? $_POST['status'] : 'active';
        $bio = trim($_POST['bio'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Tên hoặc email không hợp lệ.');
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, status = ?, bio = ? WHERE id = ?');
            $stmt->execute([$name, $email, $role, $status, $bio, $id]);
            audit_log($pdo, 'user_update', 'user', $id, $user, ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status]);
            set_flash('success', 'Đã cập nhật user.');
            redirect('admin/user_edit.php?id=' . $id);
        }
    }
    redirect('admin/user_edit.php?id=' . $id);
}

admin_header('Sửa user', 'users');
?>
<section class="panel">
    <form method="post" class="stack-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Tên</label><input class="form-control" name="name" value="<?= e($user['name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Role</label><select class="form-select" name="role"><?php foreach (['user','teacher','admin'] as $role): ?><option value="<?= $role ?>" <?= $user['role'] === $role ? 'selected' : '' ?>><?= $role ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach (['active','locked'] as $status): ?><option value="<?= $status ?>" <?= $user['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label">Bio</label><textarea class="form-control" name="bio" rows="4"><?= e($user['bio'] ?? '') ?></textarea></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <button class="btn btn-primary" name="action" value="update">Lưu thay đổi</button>
            <button class="btn btn-outline-warning" name="action" value="reset_password" data-confirm-action="Reset mật khẩu user này?">Reset mật khẩu</button>
            <button class="btn btn-outline-danger ms-auto" name="action" value="delete" data-confirm-action="Xóa user này?">Xóa user</button>
        </div>
    </form>
</section>
<?php admin_footer(); ?>
