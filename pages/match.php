<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Match';
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
        <h1>Match</h1>
        <p>Chọn một từ và một nghĩa để hoàn thành từng cặp.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= app_url('pages/match.php') ?>"><i class="bi bi-grid"></i> Chọn bài khác</a>
</div>

<?php if ($selectedSet): ?>
<section class="panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= app_url('pages/match.php') ?>">
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
            <button class="btn btn-primary w-100" type="submit">Chơi Match</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!$selectedSet): ?>
    <?php render_learning_set_picker($sets, 'pages/match.php', 'Match', 'Chọn bộ từ trước khi vào màn ghép cặp.'); ?>
<?php elseif (!$cards): ?>
    <div class="empty-state panel">
        <i class="bi bi-card-text"></i>
        <h3>Bộ từ này chưa có flashcard</h3>
        <p>Thêm flashcard trước khi chơi.</p>
        <a class="btn btn-primary" href="<?= app_url('pages/edit_set.php') ?>?id=<?= (int) $selectedSetId ?>">Thêm flashcard</a>
    </div>
<?php else: ?>
    <section class="panel game-panel" id="matchApp" data-set-id="<?= (int) $selectedSetId ?>">
        <div class="game-head">
            <div>
                <h2><?= e($selectedSet['title']) ?></h2>
                <p>Không thể click lại các thẻ đã hoàn thành.</p>
            </div>
            <div class="game-stats">
                <span><i class="bi bi-clock"></i> <strong id="matchTimer">0s</strong></span>
                <span><i class="bi bi-stars"></i> <strong id="matchScore">0</strong></span>
                <button class="btn btn-sm btn-outline-primary" id="matchRestart" type="button">Reset</button>
            </div>
        </div>
        <div class="match-board">
            <div>
                <h3>English</h3>
                <div id="matchTerms" class="match-column"></div>
            </div>
            <div>
                <h3>Vietnamese</h3>
                <div id="matchDefinitions" class="match-column"></div>
            </div>
        </div>
        <div id="matchResult" class="game-result" hidden></div>
    </section>
    <script type="application/json" id="matchData"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
