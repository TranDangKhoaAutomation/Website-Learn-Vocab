<?php
require_once __DIR__ . '/../config/config.php';

function get_user_classes(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('
        SELECT c.*, cm.role
        FROM learning_classes c
        INNER JOIN class_members cm ON cm.class_id = c.id
        WHERE cm.user_id = ?
        ORDER BY c.created_at DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function get_accessible_sets(PDO $pdo, int $userId, string $query = ''): array
{
    $sql = '
        SELECT s.*, u.name AS owner_name, c.name AS class_name, sp.role AS shared_role, COALESCE(fc.card_count, 0) AS card_count
        FROM vocabulary_sets s
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN class_members cm ON cm.class_id = s.class_id AND cm.user_id = ?
        LEFT JOIN vocabulary_set_permissions sp ON sp.set_id = s.id AND sp.user_id = ?
        LEFT JOIN (
            SELECT set_id, COUNT(*) AS card_count
            FROM flashcards
            GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE (
            s.user_id = ?
            OR s.is_public = 1
            OR s.visibility = "public"
            OR (s.visibility = "class" AND cm.user_id IS NOT NULL)
            OR sp.user_id IS NOT NULL
        )
    ';
    $params = [$userId, $userId, $userId];

    if ($query !== '') {
        $sql .= ' AND (s.title LIKE ? OR s.description LIKE ? OR u.name LIKE ? OR c.name LIKE ?)';
        $like = '%' . $query . '%';
        array_push($params, $like, $like, $like, $like);
    }

    $sql .= '
        ORDER BY
            CASE WHEN s.user_id = ? THEN 0 ELSE 1 END,
            CASE sp.role WHEN "admin" THEN 1 WHEN "editor" THEN 2 WHEN "viewer" THEN 3 ELSE 4 END,
            s.updated_at DESC,
            s.created_at DESC
    ';
    $params[] = $userId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_owned_sets(PDO $pdo, int $userId, string $query = ''): array
{
    $sql = '
        SELECT s.*, c.name AS class_name, COALESCE(fc.card_count, 0) AS card_count
        FROM vocabulary_sets s
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN (
            SELECT set_id, COUNT(*) AS card_count
            FROM flashcards
            GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE s.user_id = ?
    ';
    $params = [$userId];

    if ($query !== '') {
        $sql .= ' AND (s.title LIKE ? OR s.description LIKE ? OR c.name LIKE ?)';
        $like = '%' . $query . '%';
        array_push($params, $like, $like, $like);
    }

    $sql .= ' ORDER BY s.updated_at DESC, s.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_shared_sets(PDO $pdo, int $userId, string $query = ''): array
{
    $sql = '
        SELECT s.*, u.name AS owner_name, c.name AS class_name, sp.role AS shared_role, COALESCE(fc.card_count, 0) AS card_count
        FROM vocabulary_set_permissions sp
        INNER JOIN vocabulary_sets s ON s.id = sp.set_id
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN (
            SELECT set_id, COUNT(*) AS card_count
            FROM flashcards
            GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE sp.user_id = ?
    ';
    $params = [$userId];

    if ($query !== '') {
        $sql .= ' AND (s.title LIKE ? OR s.description LIKE ? OR u.name LIKE ? OR c.name LIKE ?)';
        $like = '%' . $query . '%';
        array_push($params, $like, $like, $like, $like);
    }

    $sql .= '
        ORDER BY
            CASE sp.role WHEN "admin" THEN 0 WHEN "editor" THEN 1 ELSE 2 END,
            s.updated_at DESC,
            s.created_at DESC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_learning_set(PDO $pdo, int $setId, int $userId): ?array
{
    if ($setId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT s.*, u.name AS owner_name, c.name AS class_name, sp.role AS shared_role
        FROM vocabulary_sets s
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN class_members cm ON cm.class_id = s.class_id AND cm.user_id = ?
        LEFT JOIN vocabulary_set_permissions sp ON sp.set_id = s.id AND sp.user_id = ?
        WHERE s.id = ?
          AND (
              s.user_id = ?
              OR s.is_public = 1
              OR s.visibility = "public"
              OR (s.visibility = "class" AND cm.user_id IS NOT NULL)
              OR sp.user_id IS NOT NULL
          )
        LIMIT 1
    ');
    $stmt->execute([$userId, $userId, $setId, $userId]);
    $set = $stmt->fetch();

    return $set ?: null;
}

function get_editable_set(PDO $pdo, int $setId, int $userId): ?array
{
    if ($setId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT s.*, sp.role AS shared_role
        FROM vocabulary_sets s
        LEFT JOIN vocabulary_set_permissions sp ON sp.set_id = s.id AND sp.user_id = ?
        WHERE s.id = ?
          AND (
              s.user_id = ?
              OR sp.role IN ("editor", "admin")
          )
        LIMIT 1
    ');
    $stmt->execute([$userId, $setId, $userId]);
    $set = $stmt->fetch();

    return $set ?: null;
}

function get_set_for_permission_management(PDO $pdo, int $setId, int $userId): ?array
{
    if ($setId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT s.*, sp.role AS shared_role
        FROM vocabulary_sets s
        LEFT JOIN vocabulary_set_permissions sp ON sp.set_id = s.id AND sp.user_id = ?
        WHERE s.id = ?
          AND (
              s.user_id = ?
              OR sp.role = "admin"
          )
        LIMIT 1
    ');
    $stmt->execute([$userId, $setId, $userId]);
    $set = $stmt->fetch();

    return $set ?: null;
}

function get_set_owner(PDO $pdo, int $setId, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM vocabulary_sets WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$setId, $userId]);
    $set = $stmt->fetch();

    return $set ?: null;
}

function can_edit_set_row(array $set, int $userId): bool
{
    if ((int) ($set['user_id'] ?? 0) === $userId) {
        return true;
    }

    return in_array($set['shared_role'] ?? '', ['editor', 'admin'], true);
}

function can_manage_set_row(array $set, int $userId): bool
{
    if ((int) ($set['user_id'] ?? 0) === $userId) {
        return true;
    }

    return ($set['shared_role'] ?? '') === 'admin';
}

function set_user_role_label(?string $role): string
{
    if ($role === 'admin') {
        return 'Admin';
    }

    if ($role === 'editor') {
        return 'Editor';
    }

    if ($role === 'viewer') {
        return 'Viewer';
    }

    return '';
}

function set_user_role_badge(?string $role): string
{
    if ($role === 'admin') {
        return 'text-bg-danger';
    }

    if ($role === 'editor') {
        return 'text-bg-warning';
    }

    if ($role === 'viewer') {
        return 'text-bg-info';
    }

    return 'text-bg-light';
}

function get_set_permissions(PDO $pdo, int $setId): array
{
    $stmt = $pdo->prepare('
        SELECT sp.*, u.name, u.email
        FROM vocabulary_set_permissions sp
        INNER JOIN users u ON u.id = sp.user_id
        WHERE sp.set_id = ?
        ORDER BY
            CASE sp.role WHEN "admin" THEN 0 WHEN "editor" THEN 1 ELSE 2 END,
            u.name ASC
    ');
    $stmt->execute([$setId]);
    return $stmt->fetchAll();
}

function get_set_cards(PDO $pdo, int $setId): array
{
    $stmt = $pdo->prepare('
        SELECT id, set_id, term, definition, example_sentence, pronunciation, image_url
        FROM flashcards
        WHERE set_id = ?
        ORDER BY id ASC
    ');
    $stmt->execute([$setId]);
    return $stmt->fetchAll();
}

function user_can_use_class(PDO $pdo, int $classId, int $userId): bool
{
    if ($classId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$classId, $userId]);
    return (bool) $stmt->fetchColumn();
}

function set_visibility_label(array $set): string
{
    $visibility = $set['visibility'] ?? ((int) ($set['is_public'] ?? 0) === 1 ? 'public' : 'private');

    if ($visibility === 'public') {
        return 'Public';
    }

    if ($visibility === 'class') {
        return 'Class' . (!empty($set['class_name']) ? ': ' . $set['class_name'] : '');
    }

    return 'Private';
}

function set_visibility_badge(array $set): string
{
    $visibility = $set['visibility'] ?? ((int) ($set['is_public'] ?? 0) === 1 ? 'public' : 'private');

    if ($visibility === 'public') {
        return 'text-bg-success';
    }

    if ($visibility === 'class') {
        return 'text-bg-primary';
    }

    return 'text-bg-secondary';
}

function render_learning_set_picker(array $sets, string $modePath, string $modeName, string $description): void
{
    ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Chọn bài học</h2>
                <p><?= e($description) ?></p>
            </div>
            <a class="btn btn-outline-primary" href="<?= BASE_URL ?>pages/sets.php"><i class="bi bi-collection"></i> My Sets</a>
        </div>

        <?php if (!$sets): ?>
            <div class="empty-state compact">
                <i class="bi bi-folder2-open"></i>
                <h3>Chưa có bộ từ nào có thể học</h3>
                <p>Bạn có thể tạo bộ từ riêng, học bộ public hoặc tham gia một lớp được cấp quyền.</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <a class="btn btn-primary" href="<?= BASE_URL ?>pages/create_set.php">Tạo bộ từ</a>
                    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>pages/classes.php">Lớp học</a>
                </div>
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
                                <span><i class="bi bi-person"></i> <?= e($set['owner_name'] ?? current_user_name()) ?></span>
                            </div>
                        </div>
                        <div class="set-card-actions">
                            <a class="btn btn-sm btn-primary" href="<?= BASE_URL . e($modePath) ?>?set_id=<?= (int) $set['id'] ?>">
                                Vào <?= e($modeName) ?>
                            </a>
                            <?php if (can_edit_set_row($set, current_user_id())): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>pages/edit_set.php?id=<?= (int) $set['id'] ?>">
                                    <?= (int) $set['card_count'] === 0 ? 'Thêm thẻ' : 'Sửa' ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($set['shared_role'])): ?>
                                <span class="badge <?= e(set_user_role_badge($set['shared_role'])) ?>"><?= e(set_user_role_label($set['shared_role'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php
}
