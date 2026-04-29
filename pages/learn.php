<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Learn';
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
        <h1>Learn</h1>
        <p>Trả lời câu hỏi trộn ngẫu nhiên và học lại các câu sai.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= app_url('pages/learn.php') ?>"><i class="bi bi-grid"></i> Chọn bài khác</a>
</div>

<?php if ($selectedSet): ?>
<section class="panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= app_url('pages/learn.php') ?>">
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
            <button class="btn btn-primary w-100" type="submit">Mở bài học</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!$selectedSet): ?>
    <?php render_learning_set_picker($sets, 'pages/learn.php', 'Learn', 'Chọn một bài học trước, sau đó hệ thống mới tạo câu hỏi Learn cho bộ từ đó.'); ?>
<?php elseif (!$cards): ?>
    <div class="empty-state panel">
        <i class="bi bi-card-text"></i>
        <h3>Bộ từ này chưa có flashcard</h3>
        <p>Thêm flashcard trước khi bắt đầu bài học.</p>
        <a class="btn btn-primary" href="<?= app_url('pages/edit_set.php') ?>?id=<?= (int) $selectedSetId ?>">Thêm flashcard</a>
    </div>
<?php else: ?>
    <div id="learnApp" class="learning-layout" data-set-id="<?= (int) $selectedSetId ?>">
        <section class="panel study-panel">
            <div class="study-topline">
                <div>
                    <h2><?= e($selectedSet['title']) ?></h2>
                    <p id="learnProgressText">Chuẩn bị bài học</p>
                </div>
                <div class="progress mini-progress">
                    <div class="progress-bar" id="learnProgressBar" style="width: 0%"></div>
                </div>
            </div>

            <div id="learnQuestionArea" class="question-area"></div>
            <div id="learnFeedback" class="feedback-box" hidden></div>
            <div class="study-actions justify-content-end">
                <button class="btn btn-primary" id="learnNext" type="button">Bắt đầu</button>
            </div>
            <div id="learnReview" class="review-area" hidden></div>
        </section>

        <aside class="panel settings-panel">
            <div class="panel-header">
                <div>
                    <h2>Learn Settings</h2>
                    <p>Điều chỉnh cách hiển thị đáp án và học lại.</p>
                </div>
            </div>
            <div class="settings-list">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="learnShowCorrectImmediately">
                    <label class="form-check-label" for="learnShowCorrectImmediately">Hiện đáp án đúng ngay khi sai</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="learnShowCorrectInReview">
                    <label class="form-check-label" for="learnShowCorrectInReview">Hiện đáp án đúng ở cuối bài</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="learnRetryWrongAnswers">
                    <label class="form-check-label" for="learnRetryWrongAnswers">Cho phép học lại câu sai</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="learnAutoRetryWrongAnswers">
                    <label class="form-check-label" for="learnAutoRetryWrongAnswers">Tự động học lại câu sai</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="learnShowIpa">
                    <label class="form-check-label" for="learnShowIpa">Hiển thị IPA</label>
                </div>
            </div>
        </aside>
    </div>
    <script type="application/json" id="learnData"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
