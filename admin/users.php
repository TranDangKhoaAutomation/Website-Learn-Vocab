<?php
require_once __DIR__ . '/_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = max(0, (int) ($_POST['id'] ?? 0));
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();
    if (!$target) {
        set_flash('danger', 'Không tìm thấy user.');
        redirect('admin/users.php');
    }
    if ($action === 'delete' && $id === current_user_id()) {
        set_flash('danger', 'Admin không thể tự xóa chính mình.');
        redirect('admin/users.php');
    }
    if ($action === 'update') {
        $role = in_array($_POST['role'] ?? '', ['user', 'teacher', 'admin'], true) ? $_POST['role'] : 'user';
        $status = in_array($_POST['status'] ?? '', ['active', 'locked'], true) ? $_POST['status'] : 'active';
        $stmt = $pdo->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?');
        $stmt->execute([$role, $status, $id]);
        audit_log($pdo, 'user_update', 'user', $id, ['role' => $target['role'], 'status' => $target['status']], ['role' => $role, 'status' => $status]);
        set_flash('success', 'Đã cập nhật user.');
    } elseif ($action === 'reset_password') {
        $tempPassword = 'Temp@123456';
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([password_hash($tempPassword, PASSWORD_DEFAULT), $id]);
        audit_log($pdo, 'user_reset_password', 'user', $id);
        set_flash('success', 'Mật khẩu tạm thời: Temp@123456');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND id <> ?');
        $stmt->execute([$id, current_user_id()]);
        audit_log($pdo, 'user_delete', 'user', $id, $target);
        set_flash('success', 'Đã xóa user.');
    }
    redirect('admin/users.php');
}

$q = admin_page_param('q');
$role = admin_page_param('role');
$status = admin_page_param('status');
$where = ' WHERE 1=1 ';
$params = [];
if ($q !== '') {
    $where .= ' AND (u.name LIKE ? OR u.email LIKE ?) ';
    array_push($params, "%$q%", "%$q%");
}
if (in_array($role, ['user', 'teacher', 'admin'], true)) {
    $where .= ' AND u.role = ? ';
    $params[] = $role;
}
if (in_array($status, ['active', 'locked'], true)) {
    $where .= ' AND u.status = ? ';
    $params[] = $status;
}
[$users, $total, $pages, $page] = admin_paginate($pdo,
    "SELECT COUNT(*) FROM users u $where",
    "SELECT u.*, COUNT(DISTINCT s.id) set_count, COUNT(DISTINCT up.id) progress_count, ROUND(AVG(tr.score / NULLIF(tr.total_questions, 0) * 100), 1) avg_score
     FROM users u
     LEFT JOIN vocabulary_sets s ON s.user_id = u.id
     LEFT JOIN user_progress up ON up.user_id = u.id
     LEFT JOIN test_results tr ON tr.user_id = u.id
     $where GROUP BY u.id ORDER BY u.created_at DESC",
    $params,
    max(1, (int) ($_GET['page'] ?? 1))
);

admin_header('Users', 'users');
?>
<section class="panel">
    <form class="admin-filters" method="get">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm tên hoặc email">
        <select class="form-select" name="role"><option value="">Tất cả role</option><?php foreach (['user','teacher','admin'] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
        <select class="form-select" name="status"><option value="">Tất cả trạng thái</option><?php foreach (['active','locked'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary">Lọc</button>
    </form>
    <div class="table-responsive mt-3">
        <table class="table align-middle">
            <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Ngày tạo</th><th>Login cuối</th><th>Sets</th><th>Lượt học</th><th>Điểm TB</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= e($user['name']) ?></strong><br><small><?= e($user['email']) ?></small></td>
                    <td><span class="badge <?= admin_badge($user['role']) ?>"><?= e($user['role']) ?></span></td>
                    <td><span class="badge <?= admin_badge($user['status']) ?>"><?= e($user['status']) ?></span></td>
                    <td><?= e($user['created_at']) ?></td><td><?= e($user['last_login_at'] ?: '-') ?></td>
                    <td><?= (int) $user['set_count'] ?></td><td><?= (int) $user['progress_count'] ?></td><td><?= e($user['avg_score'] ?? '-') ?>%</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= app_url('admin/user_edit.php') ?>?id=<?= (int) $user['id'] ?>">Sửa</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php admin_pagination($page, $pages); ?>
</section>
<?php admin_footer(); ?>
