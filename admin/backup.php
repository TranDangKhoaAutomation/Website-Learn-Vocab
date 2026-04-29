<?php
require_once __DIR__ . '/_admin.php';

function csv_download(string $filename, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    if ($rows) {
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
    }
    exit;
}

if (isset($_GET['export'])) {
    $type = $_GET['export'];
    audit_log($pdo, 'backup_export_' . $type, 'backup');
    if ($type === 'users') {
        csv_download('users.csv', $pdo->query('SELECT id,name,email,role,status,last_login_at,created_at FROM users ORDER BY id')->fetchAll());
    } elseif ($type === 'sets') {
        csv_download('vocabulary_sets.csv', $pdo->query('SELECT id,user_id,class_id,title,description,visibility,status,level,created_at,updated_at FROM vocabulary_sets ORDER BY id')->fetchAll());
    } elseif ($type === 'flashcards') {
        csv_download('flashcards.csv', $pdo->query('SELECT id,set_id,term,definition,pronunciation,example_sentence,image_url,created_at FROM flashcards ORDER BY id')->fetchAll());
    } elseif ($type === 'test_results') {
        csv_download('test_results.csv', $pdo->query('SELECT * FROM test_results ORDER BY id')->fetchAll());
    } elseif ($type === 'sets_json') {
        $sets = $pdo->query('SELECT * FROM vocabulary_sets ORDER BY id')->fetchAll();
        $stmt = $pdo->prepare('SELECT * FROM flashcards WHERE set_id = ? ORDER BY id');
        foreach ($sets as &$set) {
            $stmt->execute([(int) $set['id']]);
            $set['flashcards'] = $stmt->fetchAll();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="sets_with_flashcards.json"');
        echo json_encode($sets, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

$report = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $dryRun = isset($_POST['dry_run']);
    $report = ['added' => 0, 'skipped' => 0, 'errors' => []];
    $setId = max(0, (int) ($_POST['set_id'] ?? 0));
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK || $setId <= 0) {
        $report['errors'][] = 'File hoặc set_id không hợp lệ.';
    } else {
        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'json'], true)) {
            $report['errors'][] = 'Chỉ hỗ trợ CSV hoặc JSON.';
        } elseif ($ext === 'csv') {
            $handle = fopen($_FILES['import_file']['tmp_name'], 'r');
            $stmt = $pdo->prepare('INSERT INTO flashcards (set_id, term, definition, pronunciation, example_sentence, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            while (($row = fgetcsv($handle)) !== false) {
                [$term, $definition, $pronunciation, $example, $image] = array_pad($row, 5, '');
                if (trim($term) === '' || trim($definition) === '') { $report['skipped']++; continue; }
                if (!$dryRun) { $stmt->execute([$setId, trim($term), trim($definition), trim($pronunciation), trim($example), trim($image)]); }
                $report['added']++;
            }
        } else {
            $json = json_decode(file_get_contents($_FILES['import_file']['tmp_name']), true);
            $cards = is_array($json) ? ($json['flashcards'] ?? $json) : [];
            $stmt = $pdo->prepare('INSERT INTO flashcards (set_id, term, definition, pronunciation, example_sentence, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            foreach ($cards as $card) {
                if (empty($card['term']) || empty($card['definition'])) { $report['skipped']++; continue; }
                if (!$dryRun) { $stmt->execute([$setId, trim($card['term']), trim($card['definition']), trim($card['pronunciation'] ?? ''), trim($card['example_sentence'] ?? ''), trim($card['image_url'] ?? '')]); }
                $report['added']++;
            }
        }
        audit_log($pdo, $dryRun ? 'backup_import_dry_run' : 'backup_import', 'backup', $setId, null, $report);
    }
}

$sets = $pdo->query('SELECT id,title FROM vocabulary_sets WHERE status <> "deleted" ORDER BY title')->fetchAll();
admin_header('Backup', 'backup');
?>
<section class="panel mb-4"><h2>Export dữ liệu</h2><div class="d-flex flex-wrap gap-2"><?php foreach(['users'=>'Users CSV','sets'=>'Sets CSV','flashcards'=>'Flashcards CSV','test_results'=>'Test results CSV','sets_json'=>'Sets + flashcards JSON'] as $k=>$v): ?><a class="btn btn-outline-primary" href="?export=<?= e($k) ?>"><?= e($v) ?></a><?php endforeach; ?></div><p class="text-muted mt-2 mb-0">CSV users không export password hash.</p></section>
<section class="panel"><h2>Import flashcards</h2><?php if($report): ?><div class="alert alert-info">Thêm: <?= (int)$report['added'] ?>, bỏ qua: <?= (int)$report['skipped'] ?>, lỗi: <?= e(implode('; ', $report['errors'])) ?></div><?php endif; ?><form method="post" enctype="multipart/form-data" class="row g-3"><?= csrf_field() ?><div class="col-md-5"><select class="form-select" name="set_id" required><?php foreach($sets as $set): ?><option value="<?= (int)$set['id'] ?>"><?= e($set['title']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><input class="form-control" type="file" name="import_file" accept=".csv,.json" required></div><div class="col-md-2 form-check pt-2"><input class="form-check-input" name="dry_run" type="checkbox" checked> Dry-run</div><div class="col-md-1"><button class="btn btn-primary">Import</button></div></form></section>
<?php admin_footer(); ?>
