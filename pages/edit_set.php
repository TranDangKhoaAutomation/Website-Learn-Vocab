<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Edit Set';
$userId = current_user_id();
$setId = max(0, (int) ($_GET['id'] ?? 0));
$set = null;
$cards = [];
$classes = [];
$setPermissions = [];
$canManagePermissions = false;
$canDeleteSet = false;
$canChangeVisibility = false;

if ($pdo && $setId > 0) {
    $set = get_editable_set($pdo, $setId, $userId);

    if ($set) {
        $cards = get_set_cards($pdo, $setId);
        $classes = get_user_classes($pdo, $userId);
        $setPermissions = get_set_permissions($pdo, $setId);
        $canManagePermissions = can_manage_set_row($set, $userId);
        $canDeleteSet = (int) $set['user_id'] === $userId;
        $canChangeVisibility = $canDeleteSet;
    }
}

if (!$set) {
    set_flash('danger', 'Không tìm thấy bộ từ hoặc bạn không có quyền truy cập.');
    redirect('pages/sets.php');
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>Sửa bộ từ</h1>
        <p>Cập nhật thông tin bộ từ và quản lý flashcard.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="<?= app_url('pages/flashcards.php') ?>?set_id=<?= (int) $set['id'] ?>"><i class="bi bi-play-circle"></i> Học</a>
        <a class="btn btn-outline-secondary" href="<?= app_url('pages/sets.php') ?>"><i class="bi bi-arrow-left"></i> My Sets</a>
    </div>
</div>

<section class="panel mb-4">
    <form method="post" action="<?= app_url('actions/update_set.php') ?>">
        <input type="hidden" name="id" value="<?= (int) $set['id'] ?>">
        <div class="row g-3">
            <div class="col-lg-8">
                <label class="form-label" for="title">Tiêu đề</label>
                <input class="form-control" id="title" name="title" type="text" value="<?= e($set['title']) ?>" required>
            </div>
            <?php $visibility = $set['visibility'] ?? ($set['is_public'] ? 'public' : 'private'); ?>
            <?php if ($canChangeVisibility): ?>
                <div class="col-lg-4">
                    <label class="form-label" for="visibility">Quyền học</label>
                    <select class="form-select visibility-select" id="visibility" name="visibility" data-class-target="#classSelectWrap">
                        <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Private - chỉ mình tôi</option>
                        <option value="public" <?= $visibility === 'public' ? 'selected' : '' ?>>Public - mọi người có thể học</option>
                    </select>
                </div>
                <div class="col-lg-4" id="classSelectWrap" <?= $visibility === 'class' ? '' : 'hidden' ?>>
                    <label class="form-label" for="class_id">Lớp được cấp quyền</label>
                    <select class="form-select" id="class_id" name="class_id">
                        <option value="0">Chọn lớp</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>" <?= (int) ($set['class_id'] ?? 0) === (int) $class['id'] ? 'selected' : '' ?>>
                                <?= e($class['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$classes): ?>
                        <div class="form-text">Chỉ dùng lựa chọn Private hoặc Public cho bộ từ cá nhân.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="col-lg-4">
                    <label class="form-label"><?= (int) $set['user_id'] === $userId ? 'Quyền học' : 'Vai trò của bạn' ?></label>
                    <div class="form-control bg-light">
                        <?= (int) $set['user_id'] === $userId ? e(set_visibility_label($set)) : e(set_user_role_label($set['shared_role'] ?? 'editor') ?: 'Editor') ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-12">
                <label class="form-label" for="description">Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= e($set['description']) ?></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" type="submit">Cập nhật bộ từ</button>
        </div>
    </form>
</section>

<?php if ($canManagePermissions): ?>
    <section class="panel mb-4">
        <div class="panel-header">
            <div>
                <h2>Quyền truy cập bài học</h2>
                <p>Chia sẻ riêng bộ từ này cho người khác bằng email. Private vẫn có thể chia sẻ riêng theo vai trò.</p>
            </div>
        </div>
        <form class="row g-3 align-items-end mb-3" method="post" action="<?= app_url('actions/grant_set_permission.php') ?>">
            <input type="hidden" name="set_id" value="<?= (int) $set['id'] ?>">
            <div class="col-lg-5">
                <label class="form-label" for="share_email">Email người dùng</label>
                <input class="form-control" id="share_email" name="email" type="email" placeholder="student@example.com" required>
            </div>
            <div class="col-lg-4">
                <label class="form-label" for="share_role">Vai trò</label>
                <select class="form-select" id="share_role" name="role">
                    <option value="viewer">Viewer - chỉ học</option>
                    <option value="editor">Editor - học và sửa thẻ</option>
                    <option value="admin">Admin - sửa và cấp quyền</option>
                </select>
            </div>
            <div class="col-lg-3">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-person-plus"></i> Cấp quyền</button>
            </div>
        </form>

        <?php if (!$setPermissions): ?>
            <div class="empty-state compact">
                <i class="bi bi-shield-lock"></i>
                <h3>Chưa chia sẻ riêng cho ai</h3>
                <p>Dùng form phía trên để thêm Viewer, Editor hoặc Admin.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($setPermissions as $permission): ?>
                        <tr>
                            <td><?= e($permission['name']) ?></td>
                            <td><?= e($permission['email']) ?></td>
                            <td><span class="badge <?= e(set_user_role_badge($permission['role'])) ?>"><?= e(set_user_role_label($permission['role'])) ?></span></td>
                            <td class="text-end">
                                <form method="post" action="<?= app_url('actions/remove_set_permission.php') ?>" class="confirm-delete d-inline" data-confirm="Gỡ quyền người dùng này khỏi bộ từ?">
                                    <input type="hidden" name="set_id" value="<?= (int) $set['id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $permission['user_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Gỡ quyền</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <section class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2>Thêm flashcard</h2>
                    <p>Term và Definition là bắt buộc.</p>
                </div>
            </div>
            <form method="post" action="<?= app_url('actions/add_card.php') ?>" class="stack-form">
                <input type="hidden" name="set_id" value="<?= (int) $set['id'] ?>">
                <div>
                    <label class="form-label" for="term">Từ tiếng Anh</label>
                    <input class="form-control" id="term" name="term" type="text" required>
                </div>
                <div>
                    <label class="form-label" for="definition">Nghĩa tiếng Việt</label>
                    <input class="form-control" id="definition" name="definition" type="text" required>
                </div>
                <div>
                    <label class="form-label" for="pronunciation">Phiên âm</label>
                    <input class="form-control" id="pronunciation" name="pronunciation" type="text">
                </div>
                <div>
                    <label class="form-label" for="example_sentence">Câu ví dụ</label>
                    <textarea class="form-control" id="example_sentence" name="example_sentence" rows="3"></textarea>
                </div>
                <div>
                    <label class="form-label" for="image_url">Link ảnh</label>
                    <input class="form-control" id="image_url" name="image_url" type="url">
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Thêm flashcard</button>
            </form>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Danh sách flashcards</h2>
                    <p><?= count($cards) ?> thẻ trong bộ từ này.</p>
                </div>
            </div>

            <?php if (!$cards): ?>
                <div class="empty-state compact">
                    <i class="bi bi-card-text"></i>
                    <h3>Bộ từ chưa có flashcard</h3>
                    <p>Thêm thẻ đầu tiên bằng form bên trái.</p>
                </div>
            <?php else: ?>
                <div class="accordion card-accordion" id="cardsAccordion">
                    <?php foreach ($cards as $index => $card): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#card<?= (int) $card['id'] ?>">
                                    <span class="me-2 fw-semibold"><?= e($card['term']) ?></span>
                                    <span class="text-muted"><?= e($card['definition']) ?></span>
                                </button>
                            </h3>
                            <div id="card<?= (int) $card['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#cardsAccordion">
                                <div class="accordion-body">
                                    <form method="post" action="<?= app_url('actions/update_card.php') ?>" class="stack-form" id="updateCard<?= (int) $card['id'] ?>">
                                        <input type="hidden" name="id" value="<?= (int) $card['id'] ?>">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Từ tiếng Anh</label>
                                                <input class="form-control" name="term" type="text" value="<?= e($card['term']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Nghĩa tiếng Việt</label>
                                                <input class="form-control" name="definition" type="text" value="<?= e($card['definition']) ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Phiên âm</label>
                                                <input class="form-control" name="pronunciation" type="text" value="<?= e($card['pronunciation']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Câu ví dụ</label>
                                                <input class="form-control" name="example_sentence" type="text" value="<?= e($card['example_sentence']) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Link ảnh</label>
                                                <input class="form-control" name="image_url" type="url" value="<?= e($card['image_url']) ?>">
                                            </div>
                                        </div>
                                    </form>
                                    <div class="d-flex justify-content-between gap-2 mt-3">
                                            <button class="btn btn-outline-primary" type="submit" form="updateCard<?= (int) $card['id'] ?>">Lưu thẻ</button>
                                            <form method="post" action="<?= app_url('actions/delete_card.php') ?>" class="confirm-delete" data-confirm="Xóa flashcard này?">
                                                <input type="hidden" name="id" value="<?= (int) $card['id'] ?>">
                                                <button class="btn btn-outline-danger" type="submit">Xóa</button>
                                            </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
