<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Create Set';
$classes = $pdo ? get_user_classes($pdo, current_user_id()) : [];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>Tạo bộ từ mới</h1>
        <p>Nhập thông tin bộ từ và thêm nhiều flashcard trong một lần.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= app_url('pages/sets.php') ?>"><i class="bi bi-arrow-left"></i> My Sets</a>
</div>

<form method="post" action="<?= app_url('actions/add_set.php') ?>" class="panel">
    <div class="row g-3">
        <div class="col-lg-8">
            <label class="form-label" for="title">Tiêu đề bộ từ</label>
            <input class="form-control form-control-lg" id="title" name="title" type="text" value="<?= e(old('title')) ?>" required>
        </div>
        <div class="col-lg-4">
            <label class="form-label" for="visibility">Quyền học</label>
            <select class="form-select visibility-select" id="visibility" name="visibility" data-class-target="#classSelectWrap">
                <option value="private" <?= old('visibility', 'private') === 'private' ? 'selected' : '' ?>>Private - chỉ mình tôi</option>
                <option value="public" <?= old('visibility') === 'public' ? 'selected' : '' ?>>Public - mọi người có thể học</option>
            </select>
        </div>
        <div class="col-lg-4" id="classSelectWrap" <?= old('visibility') === 'class' ? '' : 'hidden' ?>>
            <label class="form-label" for="class_id">Lớp được cấp quyền</label>
            <select class="form-select" id="class_id" name="class_id">
                <option value="0">Chọn lớp</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id'] ?>" <?= (int) old('class_id') === (int) $class['id'] ? 'selected' : '' ?>>
                        <?= e($class['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!$classes): ?>
                <div class="form-text">Chỉ dùng lựa chọn Private hoặc Public cho bộ từ cá nhân.</div>
            <?php endif; ?>
        </div>
        <div class="col-12">
            <label class="form-label" for="description">Mô tả</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?= e(old('description')) ?></textarea>
        </div>
    </div>

    <hr class="my-4">

    <div class="panel-header px-0 pt-0">
        <div>
            <h2>Flashcards</h2>
            <p>Term và Definition là bắt buộc. Các trường còn lại có thể để trống.</p>
        </div>
        <button class="btn btn-outline-primary" type="button" id="addCardRow"><i class="bi bi-plus-circle"></i> Thêm thẻ</button>
    </div>

    <div id="cardRows" class="card-row-list">
        <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="card-row">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Từ tiếng Anh</label>
                        <input class="form-control" name="term[]" type="text" placeholder="apple">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nghĩa tiếng Việt</label>
                        <input class="form-control" name="definition[]" type="text" placeholder="quả táo">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phiên âm</label>
                        <input class="form-control" name="pronunciation[]" type="text" placeholder="/ˈæp.əl/">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Câu ví dụ</label>
                        <input class="form-control" name="example_sentence[]" type="text" placeholder="I eat an apple every day.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Link ảnh</label>
                        <input class="form-control" name="image_url[]" type="url" placeholder="https://...">
                    </div>
                </div>
                <button class="btn btn-sm btn-light remove-card-row" type="button" title="Xóa dòng"><i class="bi bi-x-lg"></i></button>
            </div>
        <?php endfor; ?>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a class="btn btn-outline-secondary" href="<?= app_url('pages/sets.php') ?>">Hủy</a>
        <button class="btn btn-primary" type="submit">Lưu bộ từ</button>
    </div>
</form>

<?php clear_old(); ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
