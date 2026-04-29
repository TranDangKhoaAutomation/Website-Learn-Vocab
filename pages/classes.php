<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

$pageTitle = 'Classes';
$userId = current_user_id();
$classes = [];

if ($pdo) {
    $classes = get_user_classes($pdo, $userId);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="page-heading">
    <div>
        <h1>Classes</h1>
        <p>Tạo lớp, tham gia lớp bằng mã mời và cấp quyền học bộ từ cho lớp.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= app_url('pages/create_set.php') ?>"><i class="bi bi-plus-circle"></i> Tạo bộ từ</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <section class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2>Tạo lớp mới</h2>
                    <p>Chủ lớp có thể gán bộ từ cho lớp trong trang tạo/sửa bộ từ.</p>
                </div>
            </div>
            <form class="stack-form" method="post" action="<?= app_url('actions/add_class.php') ?>">
                <div>
                    <label class="form-label" for="class_name">Tên lớp</label>
                    <input class="form-control" id="class_name" name="name" type="text" required>
                </div>
                <div>
                    <label class="form-label" for="class_description">Mô tả</label>
                    <textarea class="form-control" id="class_description" name="description" rows="3"></textarea>
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-people"></i> Tạo lớp</button>
            </form>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="panel h-100">
            <div class="panel-header">
                <div>
                    <h2>Tham gia lớp</h2>
                    <p>Nhập mã mời do chủ lớp cung cấp.</p>
                </div>
            </div>
            <form class="inline-search" method="post" action="<?= app_url('actions/join_class.php') ?>">
                <input class="form-control" name="invite_code" type="text" placeholder="Nhập mã mời của lớp" required>
                <button class="btn btn-outline-primary" type="submit">Tham gia</button>
            </form>
        </section>
    </div>
</div>

<section class="panel mt-4">
    <div class="panel-header">
        <div>
            <h2>Lớp của tôi</h2>
            <p>Các lớp bạn sở hữu hoặc đang tham gia.</p>
        </div>
    </div>

    <?php if (!$classes): ?>
        <div class="empty-state compact">
            <i class="bi bi-people"></i>
            <h3>Bạn chưa có lớp nào</h3>
            <p>Tạo lớp mới hoặc tham gia bằng mã mời để dùng quyền học theo lớp.</p>
        </div>
    <?php else: ?>
        <div class="set-grid">
            <?php foreach ($classes as $class): ?>
                <article class="set-card">
                    <div class="set-card-body">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <h3><?= e($class['name']) ?></h3>
                                <p><?= e($class['description'] ?: 'Không có mô tả') ?></p>
                            </div>
                            <span class="badge <?= $class['role'] === 'owner' ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                                <?= e($class['role'] === 'owner' ? 'Owner' : 'Member') ?>
                            </span>
                        </div>
                        <div class="set-meta">
                            <span><i class="bi bi-key"></i> Mã mời: <strong><?= e($class['invite_code']) ?></strong></span>
                            <span><i class="bi bi-clock"></i> <?= e(date('d/m/Y', strtotime($class['created_at']))) ?></span>
                        </div>
                    </div>
                    <div class="set-card-actions">
                        <a class="btn btn-sm btn-primary" href="<?= app_url('pages/class_detail.php') ?>?id=<?= (int) $class['id'] ?>">Chi tiết lớp</a>
                        <?php if ($class['role'] !== 'owner'): ?>
                            <form method="post" action="<?= app_url('actions/leave_class.php') ?>" class="confirm-delete" data-confirm="Rời khỏi lớp này?">
                                <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Rời lớp</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
