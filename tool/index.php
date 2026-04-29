<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tool Hub - English Learning App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body data-base-url="<?= BASE_URL ?>">
<main class="container py-4 tool-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="mb-1">Tool chuyển đổi dữ liệu</h1>
            <p class="text-muted mb-0">Các công cụ xử lý vocab cho website học tiếng Anh.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= app_url('dashboard.php') ?>"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <article class="panel h-100 tool-card">
                <div class="tool-icon"><i class="bi bi-filetype-html"></i></div>
                <h2>HTML -> Vocab JSON</h2>
                <p>Dán hoặc upload HTML source, tự tách term tiếng Anh và nghĩa tiếng Việt thành <code>vocab.json</code>.</p>
                <a class="btn btn-primary" href="<?= app_url('tool/getvoca.php') ?>">Mở Get Voca</a>
            </article>
        </div>
        <div class="col-md-6">
            <article class="panel h-100 tool-card">
                <div class="tool-icon"><i class="bi bi-database-add"></i></div>
                <h2>Vocab JSON -> Database</h2>
                <p>Import vocab JSON vào MySQL, hỗ trợ dry-run, tách bộ từ theo source_title và tự bổ sung IPA.</p>
                <a class="btn btn-primary" href="<?= app_url('tool/import_vocab_to_db.php') ?>">Mở Import DB</a>
            </article>
        </div>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
