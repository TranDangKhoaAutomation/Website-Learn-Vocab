<?php
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/../includes/set_access.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = max(0, (int) ($_POST['id'] ?? 0));
    $action = $_POST['action'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM vocabulary_sets WHERE id = ?');
    $stmt->execute([$id]);
    $old = $stmt->fetch();
    if (!$old) {
        set_flash('danger', 'Không tìm thấy bộ từ.');
        redirect('admin/sets.php');
    }
    if ($action === 'delete') {
        $stmt = $pdo->prepare('UPDATE vocabulary_sets SET status = "deleted", deleted_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    } elseif ($action === 'restore') {
        $stmt = $pdo->prepare('UPDATE vocabulary_sets SET status = "active", deleted_at = NULL WHERE id = ?');
        $stmt->execute([$id]);
    } elseif ($action === 'hide') {
        $stmt = $pdo->prepare('UPDATE vocabulary_sets SET status = "hidden" WHERE id = ?');
        $stmt->execute([$id]);
    } elseif ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE vocabulary_sets SET status = "active", approved_by = ?, approved_at = NOW() WHERE id = ?');
        $stmt->execute([current_user_id(), $id]);
    }
    audit_log($pdo, 'set_' . $action, 'set', $id, $old);
    set_flash('success', 'Đã cập nhật bộ từ.');
    redirect('admin/sets.php');
}
$q = admin_page_param('q'); $visibility = admin_page_param('visibility'); $status = admin_page_param('status');
$where = ' WHERE 1=1 '; $params = [];
if (in_array($visibility, ['private','public','class'], true)) { $where .= ' AND s.visibility = ? '; $params[] = $visibility; }
if (in_array($status, ['active','pending','hidden','deleted'], true)) { $where .= ' AND s.status = ? '; $params[] = $status; }
$dataSql = "SELECT s.*, u.name owner_name, c.name category_name, lc.name class_name, COUNT(f.id) card_count, GROUP_CONCAT(f.term SEPARATOR ' ') card_terms, GROUP_CONCAT(f.definition SEPARATOR ' ') card_definitions
     FROM vocabulary_sets s JOIN users u ON u.id=s.user_id
     LEFT JOIN categories c ON c.id=s.category_id
     LEFT JOIN learning_classes lc ON lc.id=s.class_id
     LEFT JOIN flashcards f ON f.set_id=s.id
     $where GROUP BY s.id ORDER BY s.updated_at DESC";
$page = max(1, (int) ($_GET['page'] ?? 1));
if ($q !== '') {
    $stmt = $pdo->prepare($dataSql);
    $stmt->execute($params);
    $ranked = rank_sets_by_query($stmt->fetchAll(), $q);
    $total = count($ranked);
    $pages = max(1, (int) ceil($total / 15));
    $page = min($page, $pages);
    $sets = array_slice($ranked, ($page - 1) * 15, 15);
} else {
    [$sets, $total, $pages, $page] = admin_paginate($pdo,
        "SELECT COUNT(*) FROM vocabulary_sets s JOIN users u ON u.id=s.user_id $where",
        $dataSql,
        $params,
        $page
    );
}
admin_header('Sets', 'sets');
?>
<section class="panel">
    <form class="admin-filters" method="get">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm title hoặc owner">
        <select class="form-select" name="visibility"><option value="">Visibility</option><?php foreach (['private','public','class'] as $v): ?><option value="<?= $v ?>" <?= $visibility===$v?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select>
        <select class="form-select" name="status"><option value="">Status</option><?php foreach (['active','pending','hidden','deleted'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary">Lọc</button>
    </form>
    <div class="table-responsive mt-3"><table class="table align-middle">
        <thead><tr><th>Bộ từ</th><th>Owner</th><th>Visibility</th><th>Status</th><th>Category</th><th>Level</th><th>Cards</th><th></th></tr></thead><tbody>
        <?php foreach ($sets as $set): ?><tr>
            <td><strong><?= e($set['title']) ?></strong><br><small><?= e($set['description']) ?></small></td>
            <td><?= e($set['owner_name']) ?></td><td><?= e($set['visibility']) ?></td><td><span class="badge <?= admin_badge($set['status']) ?>"><?= e($set['status']) ?></span></td>
            <td><?= e($set['category_name'] ?: '-') ?></td><td><?= e($set['level']) ?></td><td><?= (int) $set['card_count'] ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= app_url('admin/set_edit.php') ?>?id=<?= (int) $set['id'] ?>">Sửa</a></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php admin_pagination($page, $pages); ?>
</section>
<?php admin_footer(); ?>
