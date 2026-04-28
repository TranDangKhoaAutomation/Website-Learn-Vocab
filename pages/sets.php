<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'My Sets';
$userId = current_user_id();
$query = trim($_GET['q'] ?? '');
$sets = [];
$sharedSets = [];

if ($pdo) {
    $sets = get_owned_sets($pdo, $userId, $query);
    $sharedSets = get_shared_sets($pdo, $userId, $query);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>My Sets</h1>
        <p>Quản lý các bộ từ vựng của riêng bạn.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>tool/import_vocab_to_db.php"><i class="bi bi-upload"></i> Import JSON</a>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>pages/classes.php"><i class="bi bi-people"></i> Classes</a>
        <a class="btn btn-primary" href="<?= BASE_URL ?>pages/create_set.php"><i class="bi bi-plus-circle"></i> Tạo bộ từ</a>
    </div>
</div>

<?php if (!empty($database_error)): ?>
    <div class="alert alert-danger"><?= e($database_error) ?></div>
<?php endif; ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Danh sách bộ từ</h2>
            <p><?= $query !== '' ? 'Kết quả tìm kiếm cho: ' . e($query) : 'Tất cả bộ từ bạn đã tạo.' ?></p>
        </div>
        <form class="inline-search" action="<?= BASE_URL ?>pages/sets.php" method="get">
            <input class="form-control" type="search" name="q" placeholder="Tìm bộ từ..." value="<?= e($query) ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <?php if (!$sets): ?>
        <div class="empty-state">
            <i class="bi bi-folder2-open"></i>
            <h3><?= $query !== '' ? 'Không tìm thấy bộ từ' : 'Bạn chưa có bộ từ nào' ?></h3>
            <p>Tạo bộ từ mới rồi thêm flashcard để bắt đầu học.</p>
            <a class="btn btn-primary" href="<?= BASE_URL ?>pages/create_set.php">Tạo bộ từ mới</a>
        </div>
    <?php else: ?>
        <div class="set-grid">
            <?php foreach ($sets as $set): ?>
                <article class="set-card">
                    <div class="set-card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <h3><?= e($set['title']) ?></h3>
                                <p><?= e($set['description'] ?: 'Không có mô tả') ?></p>
                            </div>
                            <span class="badge <?= e(set_visibility_badge($set)) ?>"><?= e(set_visibility_label($set)) ?></span>
                        </div>
                        <div class="set-meta">
                            <span><i class="bi bi-card-text"></i> <?= (int) $set['card_count'] ?> flashcards</span>
                            <span><i class="bi bi-clock"></i> <?= e(date('d/m/Y', strtotime($set['updated_at']))) ?></span>
                        </div>
                    </div>
                    <div class="set-card-actions">
                        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>pages/flashcards.php?set_id=<?= (int) $set['id'] ?>">Học nhanh</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>pages/edit_set.php?id=<?= (int) $set['id'] ?>">Sửa</a>
                        <form method="post" action="<?= BASE_URL ?>actions/delete_set.php" class="d-inline confirm-delete" data-confirm="Xóa bộ từ này và toàn bộ flashcard bên trong?">
                            <input type="hidden" name="id" value="<?= (int) $set['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel mt-4">
    <div class="panel-header">
        <div>
            <h2>Bộ từ được chia sẻ với tôi</h2>
            <p>Những bài học người khác cấp riêng cho bạn với quyền Viewer, Editor hoặc Admin.</p>
        </div>
    </div>

    <?php if (!$sharedSets): ?>
        <div class="empty-state compact">
            <i class="bi bi-share"></i>
            <h3>Chưa có bộ từ được chia sẻ riêng</h3>
            <p>Khi người khác cấp quyền bằng email của bạn, bộ từ sẽ xuất hiện tại đây.</p>
        </div>
    <?php else: ?>
        <div class="set-grid">
            <?php foreach ($sharedSets as $set): ?>
                <article class="set-card">
                    <div class="set-card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <h3><?= e($set['title']) ?></h3>
                                <p><?= e($set['description'] ?: 'Không có mô tả') ?></p>
                            </div>
                            <span class="badge <?= e(set_user_role_badge($set['shared_role'])) ?>"><?= e(set_user_role_label($set['shared_role'])) ?></span>
                        </div>
                        <div class="set-meta">
                            <span><i class="bi bi-card-text"></i> <?= (int) $set['card_count'] ?> flashcards</span>
                            <span><i class="bi bi-person"></i> <?= e($set['owner_name']) ?></span>
                        </div>
                    </div>
                    <div class="set-card-actions">
                        <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>pages/flashcards.php?set_id=<?= (int) $set['id'] ?>">Học nhanh</a>
                        <?php if (can_edit_set_row($set, $userId)): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>pages/edit_set.php?id=<?= (int) $set['id'] ?>">Sửa</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
