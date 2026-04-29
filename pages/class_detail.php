<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';

$classId = max(0, (int) ($_GET['id'] ?? $_POST['class_id'] ?? 0));
$stmt = $pdo->prepare('SELECT c.*, cm.role FROM learning_classes c JOIN class_members cm ON cm.class_id=c.id WHERE c.id=? AND cm.user_id=?');
$stmt->execute([$classId, current_user_id()]);
$class = $stmt->fetch();
if (!$class) { set_flash('danger', 'Không tìm thấy lớp hoặc bạn không có quyền.'); redirect('pages/classes.php'); }
$isTeacher = $class['role'] === 'owner' || in_array(current_user_role(), ['teacher','admin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isTeacher) {
    verify_csrf();
    $setId = max(0, (int) ($_POST['set_id'] ?? 0));
    $title = trim($_POST['title'] ?? '');
    if ($setId > 0 && $title !== '') {
        $stmt = $pdo->prepare('INSERT INTO assignments (class_id,set_id,title,description,due_at,created_by,created_at) VALUES (?,?,?,?,?,?,NOW())');
        $stmt->execute([$classId,$setId,$title,trim($_POST['description'] ?? ''),$_POST['due_at'] ?: null,current_user_id()]);
        $assignmentId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT IGNORE INTO assignment_submissions (assignment_id,user_id,status) SELECT ?, user_id, "not_started" FROM class_members WHERE class_id=?')->execute([$assignmentId,$classId]);
        set_flash('success','Đã giao bài cho lớp.');
    }
    redirect('pages/class_detail.php?id=' . $classId);
}

$members = $pdo->prepare('SELECT u.id,u.name,u.email,cm.role FROM class_members cm JOIN users u ON u.id=cm.user_id WHERE cm.class_id=? ORDER BY cm.role DESC,u.name');
$members->execute([$classId]); $members = $members->fetchAll();
$sets = $pdo->prepare('SELECT id,title FROM vocabulary_sets WHERE user_id=? OR class_id=? ORDER BY title');
$sets->execute([current_user_id(),$classId]); $sets = $sets->fetchAll();
$assignments = $pdo->prepare('SELECT a.*, s.title set_title, COUNT(asub.id) total_students, SUM(asub.status="completed") completed_count FROM assignments a JOIN vocabulary_sets s ON s.id=a.set_id LEFT JOIN assignment_submissions asub ON asub.assignment_id=a.id WHERE a.class_id=? GROUP BY a.id ORDER BY a.created_at DESC');
$assignments->execute([$classId]); $assignments = $assignments->fetchAll();
$pageTitle = 'Class Detail';
include __DIR__ . '/../includes/header.php'; include __DIR__ . '/../includes/sidebar.php'; include __DIR__ . '/../includes/navbar.php';
?>
<div class="page-heading"><div><h1><?= e($class['name']) ?></h1><p><?= e($class['description']) ?></p></div><a class="btn btn-outline-secondary" href="<?= app_url('pages/classes.php') ?>">Classes</a></div>
<?php if($isTeacher): ?><section class="panel mb-4"><h2>Giao bài</h2><form method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="class_id" value="<?= (int)$classId ?>"><div class="col-md-4"><input class="form-control" name="title" placeholder="Tên assignment" required></div><div class="col-md-3"><select class="form-select" name="set_id" required><?php foreach($sets as $set): ?><option value="<?= (int)$set['id'] ?>"><?= e($set['title']) ?></option><?php endforeach; ?></select></div><div class="col-md-3"><input class="form-control" type="datetime-local" name="due_at"></div><div class="col-md-2"><button class="btn btn-primary w-100">Giao bài</button></div><div class="col-12"><textarea class="form-control" name="description" rows="2" placeholder="Mô tả"></textarea></div></form></section><?php endif; ?>
<div class="row g-4"><div class="col-lg-7"><section class="panel"><h2>Assignments</h2><?php foreach($assignments as $a): ?><div class="admin-mini-row"><div><strong><?= e($a['title']) ?></strong><small><?= e($a['set_title']) ?> - Deadline: <?= e($a['due_at'] ?: '-') ?></small></div><span><?= (int)$a['completed_count'] ?>/<?= (int)$a['total_students'] ?> hoàn thành</span></div><?php endforeach; ?></section></div><div class="col-lg-5"><section class="panel"><h2>Học viên</h2><?php foreach($members as $m): ?><div class="admin-mini-row"><div><strong><?= e($m['name']) ?></strong><small><?= e($m['email']) ?></small></div><span class="badge text-bg-light"><?= e($m['role']) ?></span></div><?php endforeach; ?></section></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
