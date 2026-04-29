<?php
require_once __DIR__ . '/_admin.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM flashcards WHERE id IN ($in)");
            $stmt->execute($ids);
            audit_log($pdo, 'flashcard_bulk_delete', 'flashcard', null, null, $ids);
            set_flash('success', 'Đã xóa flashcards đã chọn.');
        }
    } elseif ($action === 'fill_examples') {
        $stmt = $pdo->prepare('UPDATE flashcards SET example_sentence = CONCAT("I practice the word ", term, " every day.") WHERE (example_sentence IS NULL OR example_sentence = "")');
        $stmt->execute();
        audit_log($pdo, 'flashcard_fill_examples', 'flashcard');
        set_flash('success', 'Đã tạo example sentence mẫu cho thẻ còn trống.');
    } else {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $stmt = $pdo->prepare('SELECT * FROM flashcards WHERE id = ?'); $stmt->execute([$id]); $old = $stmt->fetch();
        if ($old && $action === 'save') {
            $stmt = $pdo->prepare('UPDATE flashcards SET term=?, definition=?, pronunciation=?, example_sentence=?, image_url=? WHERE id=?');
            $stmt->execute([trim($_POST['term'] ?? ''), trim($_POST['definition'] ?? ''), trim($_POST['pronunciation'] ?? ''), trim($_POST['example_sentence'] ?? ''), trim($_POST['image_url'] ?? ''), $id]);
            audit_log($pdo, 'flashcard_update', 'flashcard', $id, $old, $_POST);
            set_flash('success', 'Đã cập nhật flashcard.');
        } elseif ($old && $action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM flashcards WHERE id = ?'); $stmt->execute([$id]);
            audit_log($pdo, 'flashcard_delete', 'flashcard', $id, $old);
            set_flash('success', 'Đã xóa flashcard.');
        }
    }
    redirect('admin/flashcards.php');
}
$q = admin_page_param('q'); $issue = admin_page_param('issue');
$where = ' WHERE 1=1 '; $params = [];
if ($q !== '') { $where .= ' AND (f.term LIKE ? OR f.definition LIKE ? OR s.title LIKE ? OR u.name LIKE ?) '; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%"); }
if ($issue === 'missing_definition') { $where .= ' AND (f.definition IS NULL OR f.definition = "") '; }
if ($issue === 'missing_pronunciation') { $where .= ' AND (f.pronunciation IS NULL OR f.pronunciation = "") '; }
if ($issue === 'missing_example') { $where .= ' AND (f.example_sentence IS NULL OR f.example_sentence = "") '; }
[$cards, $total, $pages, $page] = admin_paginate($pdo,
    "SELECT COUNT(*) FROM flashcards f JOIN vocabulary_sets s ON s.id=f.set_id JOIN users u ON u.id=s.user_id $where",
    "SELECT f.*, s.title set_title, u.name owner_name FROM flashcards f JOIN vocabulary_sets s ON s.id=f.set_id JOIN users u ON u.id=s.user_id $where ORDER BY f.id DESC",
    $params, max(1, (int) ($_GET['page'] ?? 1)), 10
);
admin_header('Flashcards', 'flashcards');
?>
<section class="panel">
<form class="admin-filters" method="get"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Tìm term, definition, set"><select class="form-select" name="issue"><option value="">Tất cả</option><?php foreach (['missing_definition'=>'Thiếu definition','missing_pronunciation'=>'Thiếu pronunciation','missing_example'=>'Thiếu example'] as $k=>$v): ?><option value="<?= $k ?>" <?= $issue===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select><button class="btn btn-primary">Lọc</button></form>
<form method="post" class="mt-3 d-flex gap-2" id="bulkFlashcards"><?= csrf_field() ?><button class="btn btn-outline-primary" name="action" value="fill_examples">Tạo example còn trống</button><button class="btn btn-outline-danger" name="action" value="bulk_delete" data-confirm-action="Xóa các flashcards đã chọn?">Xóa đã chọn</button></form>
<div class="table-responsive mt-3"><table class="table align-middle"><thead><tr><th><input type="checkbox" data-check-all=".card-check"></th><th>Flashcard</th><th>Set</th><th>Owner</th><th>Speech</th><th></th></tr></thead><tbody>
<?php foreach ($cards as $card): ?><tr>
<td><input class="card-check" type="checkbox" name="ids[]" value="<?= (int)$card['id'] ?>" form="bulkFlashcards"></td>
<td><strong><?= e($card['term']) ?></strong><br><small><?= e($card['definition']) ?> <?= $card['pronunciation'] ? ' - ' . e($card['pronunciation']) : '' ?></small><br><small><?= e($card['example_sentence']) ?></small></td>
<td><?= e($card['set_title']) ?></td><td><?= e($card['owner_name']) ?></td>
<td><button class="btn btn-sm btn-outline-primary js-speak" type="button" data-text="<?= e($card['term']) ?>"><i class="bi bi-volume-up"></i></button></td>
<td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editCard<?= (int)$card['id'] ?>">Sửa</button></td>
</tr><tr class="collapse" id="editCard<?= (int)$card['id'] ?>"><td colspan="6"><form method="post" class="row g-2"><?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$card['id'] ?>">
<div class="col-md-3"><input class="form-control" name="term" value="<?= e($card['term']) ?>"></div><div class="col-md-3"><input class="form-control" name="definition" value="<?= e($card['definition']) ?>"></div><div class="col-md-2"><input class="form-control" name="pronunciation" value="<?= e($card['pronunciation']) ?>"></div><div class="col-md-4"><input class="form-control" name="example_sentence" value="<?= e($card['example_sentence']) ?>"></div>
<div class="col-12"><button class="btn btn-sm btn-primary" name="action" value="save">Lưu thẻ đang mở</button><button class="btn btn-sm btn-outline-danger" name="action" value="delete">Xóa thẻ đang mở</button></div>
</form></td></tr><?php endforeach; ?></tbody></table></div><?php admin_pagination($page, $pages); ?></section>
<?php admin_footer(); ?>
