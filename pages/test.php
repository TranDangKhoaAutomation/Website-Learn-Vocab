<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Test';
$userId = current_user_id();
$sets = [];
$cards = [];
$selectedSetId = max(0, (int) ($_GET['set_id'] ?? 0));
$selectedSet = null;

if ($pdo) {
    $sets = get_accessible_sets($pdo, $userId);
    if ($selectedSetId > 0) {
        $selectedSet = get_learning_set($pdo, $selectedSetId, $userId);
        if ($selectedSet) {
            $cards = get_set_cards($pdo, $selectedSetId);
        } else {
            set_flash('danger', 'Bạn không có quyền học bộ từ này hoặc bộ từ không tồn tại.');
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>Test</h1>
        <p>Làm bài kiểm tra gồm multiple choice, true/false, fill blank và written answer.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>pages/test.php"><i class="bi bi-grid"></i> Chọn bài khác</a>
</div>

<?php if ($selectedSet): ?>
<section class="panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= BASE_URL ?>pages/test.php">
        <div class="col-md-8">
            <label class="form-label" for="set_id">Chọn bộ từ</label>
            <select class="form-select" id="set_id" name="set_id" onchange="this.form.submit()">
                <?php foreach ($sets as $set): ?>
                    <option value="<?= (int) $set['id'] ?>" <?= (int) $set['id'] === $selectedSetId ? 'selected' : '' ?>>
                        <?= e($set['title']) ?> (<?= (int) $set['card_count'] ?> thẻ)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary w-100" type="submit">Tạo bài test</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!$selectedSet): ?>
    <?php render_learning_set_picker($sets, 'pages/test.php', 'Test', 'Chọn bộ từ trước, sau đó hệ thống mới tạo bài kiểm tra ngẫu nhiên.'); ?>
<?php elseif (!$cards): ?>
    <div class="empty-state panel">
        <i class="bi bi-card-text"></i>
        <h3>Bộ từ này chưa có flashcard</h3>
        <p>Thêm flashcard trước khi tạo bài test.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>pages/edit_set.php?id=<?= (int) $selectedSetId ?>">Thêm flashcard</a>
    </div>
<?php else: ?>
    <section class="panel" id="testApp" data-set-id="<?= (int) $selectedSetId ?>">
        <div class="panel-header">
            <div>
                <h2><?= e($selectedSet['title']) ?></h2>
                <p id="testMeta">Bài test sẽ được tạo ngẫu nhiên từ <?= count($cards) ?> flashcards.</p>
            </div>
            <button class="btn btn-outline-primary" id="testRestart" type="button">Làm lại</button>
        </div>
        <div id="testQuestions" class="test-list"></div>
        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-primary" id="testSubmit" type="button">Nộp bài</button>
        </div>
        <div id="testResult" class="review-area mt-4" hidden></div>
    </section>
    <script type="application/json" id="testData"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
