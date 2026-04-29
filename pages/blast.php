<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';
require_once __DIR__ . '/../includes/settings.php';

$pageTitle = 'Blast';
$userId = current_user_id();
$sets = [];
$cards = [];
$selectedSetId = max(0, (int) ($_GET['set_id'] ?? 0));
$selectedSet = null;
$blastSeconds = max(10, (int) get_setting('blast_default_seconds', 60));

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
        <h1>Blast</h1>
        <p>Chọn thật nhanh nghĩa đúng của từ đang hiện ở giữa màn hình.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= app_url('pages/blast.php') ?>"><i class="bi bi-grid"></i> Chọn bài khác</a>
</div>

<?php if ($selectedSet): ?>
<section class="panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= app_url('pages/blast.php') ?>">
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
            <button class="btn btn-primary w-100" type="submit">Chơi Blast</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!$selectedSet): ?>
    <?php render_learning_set_picker($sets, 'pages/blast.php', 'Blast', 'Chọn bộ từ trước khi vào mini game tốc độ.'); ?>
<?php elseif (!$cards): ?>
    <div class="empty-state panel">
        <i class="bi bi-card-text"></i>
        <h3>Bộ từ này chưa có flashcard</h3>
        <p>Thêm flashcard trước khi chơi.</p>
        <a class="btn btn-primary" href="<?= app_url('pages/edit_set.php') ?>?id=<?= (int) $selectedSetId ?>">Thêm flashcard</a>
    </div>
<?php else: ?>
    <section class="panel blast-panel" id="blastApp" data-set-id="<?= (int) $selectedSetId ?>" data-duration="<?= (int) $blastSeconds ?>">
        <div class="game-head">
            <div>
                <h2><?= e($selectedSet['title']) ?></h2>
                <p>Thời gian <?= (int) $blastSeconds ?> giây. Trả lời đúng để cộng điểm.</p>
            </div>
            <div class="game-stats">
                <span><i class="bi bi-clock"></i> <strong id="blastTimer"><?= (int) $blastSeconds ?>s</strong></span>
                <span><i class="bi bi-check2-circle"></i> <strong id="blastAnswered">0</strong></span>
                <span><i class="bi bi-stars"></i> <strong id="blastScore">0</strong></span>
                <button class="btn btn-sm btn-outline-primary" id="blastRestart" type="button">Chơi lại</button>
            </div>
        </div>
        <div class="blast-word" id="blastWord"></div>
        <div class="blast-options" id="blastOptions"></div>
        <div id="blastFeedback" class="feedback-box mt-3" hidden></div>
        <div id="blastResult" class="game-result" hidden></div>
    </section>
    <script type="application/json" id="blastData"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
