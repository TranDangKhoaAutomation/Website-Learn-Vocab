<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!get_setting('feedback_enabled', true)) {
    set_flash('warning', 'Feedback đang tạm tắt.');
    redirect('dashboard.php');
}

function feedback_upload_screenshot(string $field = 'screenshot'): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Không thể upload ảnh. Vui lòng thử lại.');
    }

    if ((int) $_FILES[$field]['size'] > 3 * 1024 * 1024) {
        throw new RuntimeException('Ảnh chụp màn hình tối đa 3MB.');
    }

    $tmpName = $_FILES[$field]['tmp_name'];
    $imageInfo = @getimagesize($tmpName);
    if (!$imageInfo) {
        throw new RuntimeException('File upload phải là ảnh hợp lệ.');
    }

    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $mime = $imageInfo['mime'] ?? '';
    if (!isset($mimeToExt[$mime])) {
        throw new RuntimeException('Chỉ hỗ trợ ảnh JPG, PNG, WEBP hoặc GIF.');
    }

    $uploadDir = __DIR__ . '/../assets/images/feedback';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = 'feedback_' . current_user_id() . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $mimeToExt[$mime];
    $target = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Không thể lưu ảnh upload.');
    }

    return 'assets/images/feedback/' . $filename;
}

$typeOptions = [
    'bug' => ['Lỗi chức năng', 'Trang bị lỗi, nút không chạy, hiển thị sai.', 'bi-bug'],
    'content_error' => ['Sai nội dung', 'Từ vựng, nghĩa, IPA hoặc ví dụ chưa đúng.', 'bi-card-text'],
    'suggestion' => ['Góp ý cải tiến', 'Đề xuất tính năng hoặc cách học dễ hơn.', 'bi-lightbulb'],
    'other' => ['Khác', 'Nội dung không thuộc các nhóm trên.', 'bi-chat-left-text'],
];
$targetOptions = [
    'system' => ['Toàn hệ thống', 'Không cần Target ID.'],
    'set' => ['Bộ từ', 'Nhập ID bộ từ nếu biết.'],
    'flashcard' => ['Flashcard', 'Nhập ID flashcard nếu biết.'],
    'class' => ['Lớp học', 'Nhập ID lớp nếu biết.'],
];
$feedbackBadge = ['open' => 'text-bg-info', 'reviewing' => 'text-bg-warning', 'resolved' => 'text-bg-success', 'rejected' => 'text-bg-secondary'];
$statusLabels = ['open' => 'Đã nhận', 'reviewing' => 'Đang xem', 'resolved' => 'Đã xử lý', 'rejected' => 'Từ chối'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = in_array($_POST['type'] ?? '', array_keys($typeOptions), true) ? $_POST['type'] : 'other';
    $targetType = in_array($_POST['target_type'] ?? '', array_keys($targetOptions), true) ? $_POST['target_type'] : 'system';
    $targetId = max(0, (int) ($_POST['target_id'] ?? 0)) ?: null;
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    try {
        if ($title === '' || $message === '') {
            throw new RuntimeException('Vui lòng nhập tiêu đề và mô tả chi tiết.');
        }

        $screenshotPath = feedback_upload_screenshot();
        $stmt = $pdo->prepare('
            INSERT INTO feedback (user_id, type, target_type, target_id, title, message, screenshot_path, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, "open", NOW(), NOW())
        ');
        $stmt->execute([current_user_id(), $type, $targetType, $targetId, $title, $message, $screenshotPath]);
        set_flash('success', 'Đã gửi feedback. Admin sẽ xem và phản hồi tại đây.');
    } catch (Throwable $exception) {
        set_flash('danger', $exception->getMessage());
    }

    redirect('pages/feedback.php');
}

$selectedType = in_array($_GET['type'] ?? '', array_keys($typeOptions), true) ? $_GET['type'] : 'bug';
$selectedTarget = in_array($_GET['target_type'] ?? '', array_keys($targetOptions), true) ? $_GET['target_type'] : 'system';
$targetIdFromQuery = max(0, (int) ($_GET['target_id'] ?? 0)) ?: '';

$pageTitle = 'Feedback';
$stmt = $pdo->prepare('SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([current_user_id()]);
$rows = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading feedback-heading">
    <div>
        <h1>Feedback</h1>
        <p>Gửi góp ý, báo lỗi nội dung hoặc đính kèm ảnh chụp màn hình để admin xử lý nhanh hơn.</p>
    </div>
</div>

<section class="feedback-guide panel mb-4">
    <div class="feedback-guide-visual">
        <div class="feedback-illustration">
            <i class="bi bi-chat-square-heart"></i>
            <span class="bubble one">Bug</span>
            <span class="bubble two">Content</span>
            <span class="bubble three">Idea</span>
        </div>
    </div>
    <div>
        <h2>Gửi feedback thế nào cho dễ xử lý?</h2>
        <div class="feedback-steps">
            <div><strong>1. Chọn loại</strong><span>Lỗi chức năng, sai nội dung, góp ý hoặc khác.</span></div>
            <div><strong>2. Mô tả rõ</strong><span>Nói bạn đang ở trang nào, thao tác gì, kết quả mong muốn.</span></div>
            <div><strong>3. Thêm ảnh</strong><span>Ảnh chụp màn hình giúp admin nhìn đúng vấn đề.</span></div>
        </div>
    </div>
</section>

<section class="panel mb-4">
    <form method="post" enctype="multipart/form-data" class="feedback-form">
        <?= csrf_field() ?>

        <div>
            <label class="form-label">Bạn muốn gửi loại feedback nào?</label>
            <div class="feedback-option-grid">
                <?php foreach ($typeOptions as $value => [$label, $desc, $icon]): ?>
                    <label class="feedback-option">
                        <input type="radio" name="type" value="<?= e($value) ?>" <?= $selectedType === $value ? 'checked' : '' ?>>
                        <span><i class="bi <?= e($icon) ?>"></i></span>
                        <strong><?= e($label) ?></strong>
                        <small><?= e($desc) ?></small>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label" for="target_type">Vấn đề nằm ở đâu?</label>
                <select class="form-select" id="target_type" name="target_type">
                    <?php foreach ($targetOptions as $value => [$label, $desc]): ?>
                        <option value="<?= e($value) ?>" <?= $selectedTarget === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Nếu không chắc, chọn “Toàn hệ thống”.</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="target_id">Target ID</label>
                <input class="form-control" id="target_id" name="target_id" inputmode="numeric" value="<?= e($targetIdFromQuery) ?>" placeholder="Có thể để trống">
                <div class="form-text">ID thường được tự điền khi bấm “Báo lỗi”.</div>
            </div>
            <div class="col-lg-5">
                <label class="form-label" for="title">Tiêu đề ngắn</label>
                <input class="form-control" id="title" name="title" maxlength="190" placeholder="Ví dụ: Từ painting bị thiếu ảnh minh họa" required>
                <div class="form-text">Viết một dòng ngắn để admin biết vấn đề chính.</div>
            </div>
        </div>

        <div>
            <label class="form-label" for="message">Mô tả chi tiết</label>
            <textarea class="form-control" id="message" name="message" rows="6" placeholder="Ví dụ: Trong bộ part 3, flashcard painting (n) đang hiển thị đúng từ nhưng tôi muốn bổ sung ảnh minh họa hoặc ví dụ..." required></textarea>
        </div>

        <div class="feedback-upload">
            <div>
                <label class="form-label" for="screenshot">Ảnh chụp màn hình minh họa</label>
                <input class="form-control" id="screenshot" name="screenshot" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
                <div class="form-text">Không bắt buộc. Hỗ trợ JPG, PNG, WEBP, GIF, tối đa 3MB.</div>
            </div>
            <div class="feedback-upload-preview">
                <i class="bi bi-image"></i>
                <span>Ảnh giúp admin hiểu lỗi nhanh hơn.</span>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Gửi feedback</button>
            <span class="text-muted">Bạn có thể xem trạng thái xử lý ngay bên dưới.</span>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Feedback của tôi</h2>
            <p>Theo dõi trạng thái và phản hồi từ admin.</p>
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="empty-state compact">
            <i class="bi bi-chat-dots"></i>
            <h3>Chưa có feedback nào</h3>
            <p>Khi bạn gửi feedback, trạng thái xử lý sẽ xuất hiện ở đây.</p>
        </div>
    <?php else: ?>
        <div class="feedback-list">
            <?php foreach ($rows as $row): ?>
                <article class="feedback-ticket">
                    <div class="feedback-ticket-main">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <strong><?= e($row['title']) ?></strong>
                            <span class="badge <?= e($feedbackBadge[$row['status']] ?? 'text-bg-light') ?>"><?= e($statusLabels[$row['status']] ?? $row['status']) ?></span>
                            <span class="badge text-bg-light"><?= e($typeOptions[$row['type']][0] ?? $row['type']) ?></span>
                        </div>
                        <p><?= e($row['message']) ?></p>
                        <small><?= e($targetOptions[$row['target_type']][0] ?? $row['target_type']) ?><?= $row['target_id'] ? ' #' . e($row['target_id']) : '' ?> - <?= e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></small>
                        <?php if (!empty($row['admin_reply'])): ?>
                            <div class="alert alert-info py-2 mt-3 mb-0"><strong>Admin:</strong> <?= e($row['admin_reply']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($row['screenshot_path'])): ?>
                        <a class="feedback-thumb" href="<?= BASE_URL . e($row['screenshot_path']) ?>" target="_blank" rel="noopener">
                            <img src="<?= BASE_URL . e($row['screenshot_path']) ?>" alt="Ảnh feedback">
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
