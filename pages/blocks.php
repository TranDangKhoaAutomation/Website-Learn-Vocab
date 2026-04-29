<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Blocks';
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
        <h1>Blocks</h1>
        <p>Click một block tiếng Anh và một block nghĩa tiếng Việt để ghép cặp.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= app_url('pages/blocks.php') ?>"><i class="bi bi-grid"></i> Chọn bài khác</a>
</div>

<?php if ($selectedSet): ?>
<section class="panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= app_url('pages/blocks.php') ?>">
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
            <button class="btn btn-primary w-100" type="submit">Chơi Blocks</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!$selectedSet): ?>
    <?php render_learning_set_picker($sets, 'pages/blocks.php', 'Blocks', 'Chọn bộ từ trước khi vào trò chơi ghép block.'); ?>
<?php elseif (!$cards): ?>
    <div class="empty-state panel">
        <i class="bi bi-card-text"></i>
        <h3>Bộ từ này chưa có flashcard</h3>
        <p>Thêm flashcard trước khi chơi.</p>
        <a class="btn btn-primary" href="<?= app_url('pages/edit_set.php') ?>?id=<?= (int) $selectedSetId ?>">Thêm flashcard</a>
    </div>
<?php else: ?>
    <section class="panel game-panel" id="blocksApp" data-set-id="<?= (int) $selectedSetId ?>">
        <div class="game-head">
            <div>
                <h2><?= e($selectedSet['title']) ?></h2>
                <p>Ghép đúng tất cả cặp trong thời gian ngắn nhất.</p>
            </div>
            <div class="game-stats">
                <span><i class="bi bi-clock"></i> <strong id="blocksTimer">0s</strong></span>
                <span><i class="bi bi-stars"></i> <strong id="blocksScore">0</strong></span>
                <button class="btn btn-sm btn-outline-primary" id="blocksRestart" type="button">Chơi lại</button>
            </div>
        </div>
        <div id="blocksGrid" class="blocks-grid"></div>
        <div id="blocksResult" class="game-result" hidden></div>
    </section>
    <script type="application/json" id="blocksData"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
