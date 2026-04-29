<?php
declare(strict_types=1);

/*
CLI:
  php tool/getvoca.php tool/source.html
  php tool/getvoca.php tool/source.html tool/vocab.json

Web:
  /tool/getvoca
  Chuyển HTML source thành vocab JSON, có thể dry-run hoặc lưu vào tool/vocab.json.
*/

ini_set('memory_limit', '512M');

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/csrf.php';
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
}

function getvoca_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getvoca_clean_text(string $text): string
{
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';
    return trim($text);
}

function getvoca_normalize_word(string $word): string
{
    $word = getvoca_clean_text($word);
    $word = mb_strtolower($word, 'UTF-8');
    $word = preg_replace('/\s+/u', ' ', $word) ?? '';
    return trim($word);
}

function getvoca_extract_title(string $html): string
{
    if (preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $m)) {
        return getvoca_clean_text($m[1]);
    }

    if (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $title = getvoca_clean_text($m[1]);
        $title = preg_replace('/\s*Flashcards\s*\|\s*Quizlet\s*$/iu', '', $title) ?? $title;
        return trim($title);
    }

    return '';
}

function getvoca_extract_term_text(string $block, string $lang): ?string
{
    $patterns = [
        '/<span\b[^>]*class=["\'][^"\']*\bTermText\b[^"\']*\bnotranslate\b[^"\']*\blang-' . preg_quote($lang, '/') . '\b[^"\']*["\'][^>]*>(.*?)<\/span>/is',
        '/<span\b[^>]*lang=["\']' . preg_quote($lang, '/') . '["\'][^>]*>(.*?)<\/span>/is',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $block, $m)) {
            $text = getvoca_clean_text($m[1]);
            return $text !== '' ? $text : null;
        }
    }

    return null;
}

function getvoca_extract_term_blocks(string $html): array
{
    $markers = [
        '<div aria-label="Term" class="SetPageTermsList-term">',
        'class="SetPageTermsList-term"',
    ];

    foreach ($markers as $marker) {
        if (!str_contains($html, $marker)) {
            continue;
        }

        if ($marker === $markers[0]) {
            $parts = explode($marker, $html);
            array_shift($parts);
            return array_map(static fn (string $part): string => $marker . $part, $parts);
        }

        preg_match_all('/<div\b[^>]*class=["\'][^"\']*\bSetPageTermsList-term\b[^"\']*["\'][^>]*>.*?(?=<div\b[^>]*class=["\'][^"\']*\bSetPageTermsList-term\b|$)/is', $html, $matches);
        return $matches[0] ?? [];
    }

    return [];
}

function getvoca_parse_source_html(string $html, string $title): array
{
    $blocks = getvoca_extract_term_blocks($html);
    $data = [];
    $missingMeaning = [];
    $missingWord = [];

    foreach ($blocks as $index => $block) {
        $word = getvoca_extract_term_text($block, 'en');
        $meaning = getvoca_extract_term_text($block, 'vi');

        if ($word === null && $meaning === null) {
            continue;
        }

        if ($word === null) {
            $missingWord[] = ['index' => $index + 1, 'meaning' => $meaning];
            continue;
        }

        if ($meaning === null) {
            $missingMeaning[] = ['index' => $index + 1, 'word' => $word];
        }

        $data[] = [
            'word' => $word,
            'meaning' => $meaning ?? '',
            'source_title' => $title,
        ];
    }

    return [
        'data' => $data,
        'missing_meaning' => $missingMeaning,
        'missing_word' => $missingWord,
        'block_count' => count($blocks),
    ];
}

function getvoca_load_existing_json(string $jsonFile): array
{
    if (!is_file($jsonFile)) {
        return [];
    }

    $content = file_get_contents($jsonFile);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return [];
    }

    if (isset($decoded['data']) && is_array($decoded['data'])) {
        return $decoded['data'];
    }

    return array_is_list($decoded) ? $decoded : [];
}

function getvoca_build_existing_word_map(array $items): array
{
    $map = [];
    foreach ($items as $item) {
        if (!is_array($item) || !isset($item['word'])) {
            continue;
        }
        $key = getvoca_normalize_word((string) $item['word']);
        if ($key !== '') {
            $map[$key] = true;
        }
    }
    return $map;
}

function getvoca_build_output(string $sourceFileLabel, string $html, array $oldData = [], bool $append = true): array
{
    $title = getvoca_extract_title($html);
    $parsed = getvoca_parse_source_html($html, $title);
    $existingWordMap = $append ? getvoca_build_existing_word_map($oldData) : [];
    $outputData = $append ? $oldData : [];
    $added = [];
    $skipped = [];

    foreach ($parsed['data'] as $item) {
        $key = getvoca_normalize_word((string) ($item['word'] ?? ''));
        if ($key === '') {
            continue;
        }
        if (isset($existingWordMap[$key])) {
            $skipped[] = ['word' => $item['word'], 'reason' => 'already_exists'];
            continue;
        }
        $outputData[] = $item;
        $existingWordMap[$key] = true;
        $added[] = $item;
    }

    $countEn = 0;
    $countVi = 0;
    foreach ($outputData as $item) {
        if (!empty($item['word'])) {
            $countEn++;
        }
        if (!empty($item['meaning'])) {
            $countVi++;
        }
    }

    return [
        'ok' => true,
        'updated_at' => date('c'),
        'last_source_file' => $sourceFileLabel,
        'last_source_title' => $title,
        'total' => count($outputData),
        'count_en' => $countEn,
        'count_vi' => $countVi,
        'last_run' => [
            'found_blocks' => $parsed['block_count'],
            'found_in_source' => count($parsed['data']),
            'added' => count($added),
            'skipped_existing' => count($skipped),
            'missing_meaning' => $parsed['missing_meaning'],
            'missing_word' => $parsed['missing_word'],
        ],
        'data' => $outputData,
    ];
}

function getvoca_json_encode(array $output): string
{
    $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Lỗi json_encode: ' . json_last_error_msg());
    }
    return $json;
}

function getvoca_resolve_tool_file(string $fileName, string $default): string
{
    $fileName = trim($fileName) !== '' ? basename(trim($fileName)) : $default;
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $fileName)) {
        throw new RuntimeException('Tên file không hợp lệ.');
    }
    return __DIR__ . '/' . $fileName;
}

if ($isCli) {
    global $argc, $argv;
    if ($argc < 2) {
        echo "Cách dùng:\n";
        echo "php tool/getvoca.php tool/source.html\n";
        echo "php tool/getvoca.php tool/source.html tool/vocab.json\n";
        exit(1);
    }

    $sourceFile = $argv[1];
    $jsonFile = $argv[2] ?? (__DIR__ . '/vocab.json');
    if (!is_file($sourceFile)) {
        fwrite(STDERR, "Lỗi: Không tìm thấy file source HTML: $sourceFile\n");
        exit(1);
    }

    $html = file_get_contents($sourceFile);
    if ($html === false || trim($html) === '') {
        fwrite(STDERR, "Lỗi: File source HTML rỗng hoặc không đọc được.\n");
        exit(1);
    }

    $oldData = getvoca_load_existing_json($jsonFile);
    $output = getvoca_build_output(basename($sourceFile), $html, $oldData, true);
    $json = getvoca_json_encode($output);
    file_put_contents($jsonFile, $json);
    echo $json . "\n\nĐã lưu vào file: " . $jsonFile . "\n";
    exit(0);
}

$result = null;
$error = '';
$jsonPreview = '';
$sourceMode = $_POST['source_mode'] ?? 'existing';
$jsonFileName = $_POST['json_file'] ?? 'vocab.json';
$appendMode = ($_POST['write_mode'] ?? 'append') === 'append';
$dryRun = isset($_POST['dry_run']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $sourceLabel = 'source.html';
        $html = '';

        if ($sourceMode === 'paste') {
            $html = (string) ($_POST['html_source'] ?? '');
            $sourceLabel = 'pasted-html';
        } elseif ($sourceMode === 'upload') {
            if (!isset($_FILES['html_file']) || $_FILES['html_file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Vui lòng upload file HTML hợp lệ.');
            }
            $name = $_FILES['html_file']['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['html', 'htm', 'txt'], true)) {
                throw new RuntimeException('Chỉ nhận file .html, .htm hoặc .txt.');
            }
            $html = file_get_contents($_FILES['html_file']['tmp_name']) ?: '';
            $sourceLabel = basename($name);
            if (!$dryRun) {
                file_put_contents(__DIR__ . '/source.html', $html);
            }
        } else {
            $sourcePath = __DIR__ . '/source.html';
            if (!is_file($sourcePath)) {
                throw new RuntimeException('Không tìm thấy tool/source.html.');
            }
            $html = file_get_contents($sourcePath) ?: '';
            $sourceLabel = 'source.html';
        }

        if (trim($html) === '') {
            throw new RuntimeException('Nguồn HTML đang rỗng.');
        }

        $jsonFile = getvoca_resolve_tool_file($jsonFileName, 'vocab.json');
        $oldData = $appendMode ? getvoca_load_existing_json($jsonFile) : [];
        $result = getvoca_build_output($sourceLabel, $html, $oldData, $appendMode);
        $jsonPreview = getvoca_json_encode($result);

        if (!$dryRun) {
            file_put_contents($jsonFile, $jsonPreview);
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$defaultOutput = is_file(__DIR__ . '/vocab.json') ? getvoca_load_existing_json(__DIR__ . '/vocab.json') : [];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HTML to Vocab JSON - Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body data-base-url="<?= BASE_URL ?>">
<main class="container py-4 tool-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="mb-1">Tool chuyển đổi HTML -> Vocab JSON</h1>
            <p class="text-muted mb-0">Dán hoặc upload HTML source, hệ thống cắt từ tiếng Anh và nghĩa tiếng Việt thành vocab.json.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= app_url('tool/index.php') ?>"><i class="bi bi-grid"></i> Tool Hub</a>
            <a class="btn btn-outline-primary" href="<?= app_url('tool/import_vocab_to_db.php') ?>"><i class="bi bi-database-add"></i> Import DB</a>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= getvoca_h($error) ?></div><?php endif; ?>
    <?php if ($result && !$dryRun): ?><div class="alert alert-success">Đã lưu JSON thành công.</div><?php endif; ?>
    <?php if ($result && $dryRun): ?><div class="alert alert-info">Dry-run: chỉ xem trước, chưa ghi file.</div><?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-5">
            <section class="panel">
                <h2>Cấu hình chuyển đổi</h2>
                <form method="post" enctype="multipart/form-data" class="stack-form">
                    <?= csrf_field() ?>
                    <div>
                        <label class="form-label">Nguồn HTML</label>
                        <select class="form-select" name="source_mode" id="sourceMode">
                            <option value="existing" <?= $sourceMode === 'existing' ? 'selected' : '' ?>>Dùng tool/source.html hiện có</option>
                            <option value="upload" <?= $sourceMode === 'upload' ? 'selected' : '' ?>>Upload file HTML mới</option>
                            <option value="paste" <?= $sourceMode === 'paste' ? 'selected' : '' ?>>Dán HTML trực tiếp</option>
                        </select>
                    </div>
                    <div class="source-upload">
                        <label class="form-label">Upload HTML</label>
                        <input class="form-control" type="file" name="html_file" accept=".html,.htm,.txt">
                    </div>
                    <div class="source-paste">
                        <label class="form-label">HTML source</label>
                        <textarea class="form-control" name="html_source" rows="8" placeholder="Paste HTML source ở đây..."><?= getvoca_h($_POST['html_source'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">File JSON output trong thư mục tool</label>
                        <input class="form-control" name="json_file" value="<?= getvoca_h($jsonFileName) ?>" placeholder="vocab.json">
                    </div>
                    <div>
                        <label class="form-label">Chế độ ghi</label>
                        <select class="form-select" name="write_mode">
                            <option value="append" <?= $appendMode ? 'selected' : '' ?>>Gộp vào JSON cũ và bỏ qua từ trùng</option>
                            <option value="replace" <?= !$appendMode ? 'selected' : '' ?>>Tạo lại JSON từ nguồn mới</option>
                        </select>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="dry_run" id="dryRun" <?= $dryRun ? 'checked' : '' ?>>
                        <label class="form-check-label" for="dryRun">Dry-run, chỉ xem trước không ghi file</label>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-repeat"></i> Chuyển đổi</button>
                </form>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="panel">
                <h2>Kết quả</h2>
                <?php if (!$result): ?>
                    <div class="empty-state compact">
                        <i class="bi bi-filetype-json"></i>
                        <h3>Chưa có kết quả chuyển đổi</h3>
                        <p>Chọn nguồn HTML rồi bấm Chuyển đổi.</p>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4"><div class="stat-card"><span>JSON hiện có</span><strong><?= count($defaultOutput) ?></strong></div></div>
                        <div class="col-md-8"><p class="text-muted mb-0">File mặc định: <code>tool/vocab.json</code></p></div>
                    </div>
                <?php else: ?>
                    <div class="stats-grid mb-3">
                        <div class="stat-card"><span>Block HTML</span><strong><?= (int) $result['last_run']['found_blocks'] ?></strong></div>
                        <div class="stat-card"><span>Tìm thấy</span><strong><?= (int) $result['last_run']['found_in_source'] ?></strong></div>
                        <div class="stat-card"><span>Thêm mới</span><strong><?= (int) $result['last_run']['added'] ?></strong></div>
                        <div class="stat-card"><span>Bỏ qua trùng</span><strong><?= (int) $result['last_run']['skipped_existing'] ?></strong></div>
                    </div>
                    <p><strong>Title:</strong> <?= getvoca_h($result['last_source_title'] ?: 'Không đọc được title') ?></p>
                    <?php if (!empty($result['last_run']['missing_meaning'])): ?>
                        <div class="alert alert-warning">
                            Thiếu nghĩa ở <?= count($result['last_run']['missing_meaning']) ?> item:
                            <?= getvoca_h(implode(', ', array_map(static fn ($item) => $item['word'], $result['last_run']['missing_meaning']))) ?>
                        </div>
                    <?php endif; ?>
                    <label class="form-label">JSON preview</label>
                    <textarea class="form-control font-monospace" rows="16" readonly><?= getvoca_h($jsonPreview) ?></textarea>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
