<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Flashcards';
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
        <h1>Flashcards</h1>
        <p>Lật thẻ, nghe phát âm và đánh dấu mức độ ghi nhớ.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>pages/flashcards.php"><i class="bi bi-grid"></i> Chọn bài khác</a>
</div>

<?php if (!empty($database_error)): ?>
    <div class="alert alert-danger"><?= e($database_error) ?></div>
<?php endif; ?>

<?php if ($selectedSet): ?>
<section class="panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= BASE_URL ?>pages/flashcards.php">
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
            <button class="btn btn-primary w-100" type="submit">Mở bộ từ</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!$selectedSet): ?>
    <?php render_learning_set_picker($sets, 'pages/flashcards.php', 'Flashcards', 'Chọn một bộ từ rồi mới bắt đầu lật thẻ và luyện phát âm.'); ?>
<?php elseif (!$cards): ?>
    <div class="empty-state panel">
        <i class="bi bi-card-text"></i>
        <h3>Bộ từ này chưa có flashcard</h3>
        <p>Thêm flashcard trước khi học.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>pages/edit_set.php?id=<?= (int) $selectedSetId ?>">Thêm flashcard</a>
    </div>
<?php else: ?>
    <div id="flashcardApp" class="learning-layout" data-set-id="<?= (int) $selectedSetId ?>">
        <section class="panel study-panel">
            <div class="study-topline">
                <div>
                    <h2><?= e($selectedSet['title']) ?></h2>
                    <p id="flashcardCounter">1/<?= count($cards) ?></p>
                </div>
                <div class="progress mini-progress" role="progressbar" aria-label="Progress">
                    <div class="progress-bar" id="flashcardProgress" style="width: 0%"></div>
                </div>
            </div>

            <div class="flashcard-stage">
                <div class="flashcard" id="flashcard" tabindex="0" role="button" aria-label="Lật flashcard">
                    <div class="flashcard-face flashcard-front">
                        <button class="btn icon-btn speaker-btn fc-speak-term" type="button" title="Đọc từ"><i class="bi bi-volume-up"></i></button>
                        <span class="card-label">Term</span>
                        <h3 id="fcTerm"></h3>
                        <p class="pronunciation" id="fcPronunciationFront"></p>
                    </div>
                    <div class="flashcard-face flashcard-back">
                        <button class="btn icon-btn speaker-btn fc-speak-term" type="button" title="Đọc từ"><i class="bi bi-volume-up"></i></button>
                        <span class="card-label">Definition</span>
                        <h3 id="fcDefinition"></h3>
                        <p class="pronunciation" id="fcPronunciationBack"></p>
                        <p class="example-text" id="fcExample"></p>
                        <button class="btn btn-sm btn-outline-primary fc-speak-example" type="button"><i class="bi bi-volume-up"></i> Đọc câu ví dụ</button>
                    </div>
                </div>
            </div>

            <div class="study-actions">
                <button class="btn btn-outline-secondary" id="fcPrev" type="button"><i class="bi bi-chevron-left"></i> Previous</button>
                <div class="d-flex gap-2 flex-wrap justify-content-center">
                    <button class="btn btn-outline-danger" id="fcWrong" type="button">I don't know</button>
                    <button class="btn btn-success" id="fcKnow" type="button">I know</button>
                </div>
                <button class="btn btn-outline-secondary" id="fcNext" type="button">Next <i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="flashcard-auto-actions">
                <button class="btn btn-warning" id="fcAutoRun" type="button"><i class="bi bi-play-fill"></i> Tự động chạy</button>
                <span>Tự đọc từ, lật thẻ và chuyển sang thẻ tiếp theo chậm hơn.</span>
            </div>
            <div class="small text-muted mt-3" id="speechNotice" hidden>Trình duyệt của bạn không hỗ trợ đọc văn bản.</div>
        </section>

        <aside class="panel settings-panel">
            <div class="panel-header">
                <div>
                    <h2>Flashcard Settings</h2>
                    <p>Tùy chỉnh phát âm bằng Web Speech API.</p>
                </div>
            </div>
            <div class="settings-list">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="flashcardAutoSpeak">
                    <label class="form-check-label" for="flashcardAutoSpeak">Auto speak</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="flashcardSpeakerEnabled">
                    <label class="form-check-label" for="flashcardSpeakerEnabled">Speaker button</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="flashcardSpeakExample">
                    <label class="form-check-label" for="flashcardSpeakExample">Speak example sentence</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="flashcardShowIpa">
                    <label class="form-check-label" for="flashcardShowIpa">Hiển thị IPA</label>
                </div>
            </div>
        </aside>
    </div>
    <script type="application/json" id="flashcardData"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
