<?php
require_once __DIR__ . '/../config/config.php';

function search_normalize_text(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii !== false) {
        $value = $ascii;
    }
    $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
}

function search_match_score(string $query, array $weightedFields): float
{
    $query = search_normalize_text($query);
    if ($query === '') {
        return 0;
    }

    $best = 0.0;
    foreach ($weightedFields as $field => $weight) {
        $field = search_normalize_text((string) $field);
        if ($field === '') {
            continue;
        }

        $score = 0.0;
        if ($field === $query) {
            $score = 100;
        } elseif (str_starts_with($field, $query)) {
            $score = 96;
        } elseif (str_contains($field, $query)) {
            $score = 92;
        }

        similar_text($query, $field, $fullPercent);
        $score = max($score, (float) $fullPercent * 0.82);

        foreach (array_unique(explode(' ', $field)) as $token) {
            if (mb_strlen($token, 'UTF-8') < 2) {
                continue;
            }
            if ($token === $query) {
                $score = max($score, 100);
                continue;
            }
            if (str_starts_with($token, $query) || str_starts_with($query, $token)) {
                $score = max($score, 90);
            }
            similar_text($query, $token, $tokenPercent);
            $distance = levenshtein($query, $token);
            $maxLength = max(strlen($query), strlen($token), 1);
            $distanceScore = max(0, (1 - ($distance / $maxLength)) * 100);
            $score = max($score, (float) $tokenPercent, $distanceScore);
        }

        $best = max($best, $score * (float) $weight);
    }

    return round($best, 2);
}

function rank_sets_by_query(array $sets, string $query): array
{
    if (trim($query) === '') {
        return $sets;
    }

    $ranked = [];
    foreach ($sets as $set) {
        $score = search_match_score($query, [
            $set['title'] ?? '' => 1.0,
            $set['description'] ?? '' => 0.74,
            $set['owner_name'] ?? '' => 0.68,
            $set['class_name'] ?? '' => 0.62,
            $set['visibility'] ?? '' => 0.5,
            $set['level'] ?? '' => 0.5,
            $set['category_name'] ?? '' => 0.6,
            $set['card_terms'] ?? '' => 0.95,
            $set['card_definitions'] ?? '' => 0.92,
        ]);
        if ($score >= 70) {
            $set['_search_score'] = $score;
            $ranked[] = $set;
        }
    }

    usort($ranked, static function (array $a, array $b): int {
        $scoreCompare = ($b['_search_score'] ?? 0) <=> ($a['_search_score'] ?? 0);
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        return strcmp((string) ($b['updated_at'] ?? $b['created_at'] ?? ''), (string) ($a['updated_at'] ?? $a['created_at'] ?? ''));
    });

    return $ranked;
}

function get_matching_cards_for_sets(PDO $pdo, array $sets, string $query, int $limitPerSet = 4): array
{
    if (trim($query) === '' || !$sets) {
        return [];
    }

    $setIds = array_values(array_unique(array_map(static fn (array $set): int => (int) $set['id'], $sets)));
    $setIds = array_values(array_filter($setIds, static fn (int $id): bool => $id > 0));
    if (!$setIds) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($setIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, set_id, term, definition, pronunciation, example_sentence
        FROM flashcards
        WHERE set_id IN ($placeholders)
        ORDER BY set_id ASC, id ASC
    ");
    $stmt->execute($setIds);

    $matches = [];
    foreach ($stmt->fetchAll() as $card) {
        $score = search_match_score($query, [
            $card['term'] ?? '' => 1.0,
            $card['definition'] ?? '' => 1.0,
            $card['pronunciation'] ?? '' => 0.35,
            $card['example_sentence'] ?? '' => 0.62,
        ]);

        if ($score < 70) {
            continue;
        }

        $card['_search_score'] = $score;
        $matches[(int) $card['set_id']][] = $card;
    }

    foreach ($matches as &$cards) {
        usort($cards, static function (array $a, array $b): int {
            $scoreCompare = ($b['_search_score'] ?? 0) <=> ($a['_search_score'] ?? 0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }
            return (int) $a['id'] <=> (int) $b['id'];
        });
        $cards = array_slice($cards, 0, $limitPerSet);
    }
    unset($cards);

    return $matches;
}

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
        SELECT s.*, u.name AS owner_name, c.name AS class_name, sp.role AS shared_role,
               COALESCE(fc.card_count, 0) AS card_count,
               COALESCE(fc.card_terms, "") AS card_terms,
               COALESCE(fc.card_definitions, "") AS card_definitions
        FROM vocabulary_sets s
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN class_members cm ON cm.class_id = s.class_id AND cm.user_id = ?
        LEFT JOIN vocabulary_set_permissions sp ON sp.set_id = s.id AND sp.user_id = ?
        LEFT JOIN (
            SELECT
                set_id,
                COUNT(*) AS card_count,
                GROUP_CONCAT(term SEPARATOR " ") AS card_terms,
                GROUP_CONCAT(definition SEPARATOR " ") AS card_definitions
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
    return rank_sets_by_query($stmt->fetchAll(), $query);
}

function get_owned_sets(PDO $pdo, int $userId, string $query = ''): array
{
    $sql = '
        SELECT s.*, c.name AS class_name,
               COALESCE(fc.card_count, 0) AS card_count,
               COALESCE(fc.card_terms, "") AS card_terms,
               COALESCE(fc.card_definitions, "") AS card_definitions
        FROM vocabulary_sets s
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN (
            SELECT
                set_id,
                COUNT(*) AS card_count,
                GROUP_CONCAT(term SEPARATOR " ") AS card_terms,
                GROUP_CONCAT(definition SEPARATOR " ") AS card_definitions
            FROM flashcards
            GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE s.user_id = ?
    ';
    $params = [$userId];

    $sql .= ' ORDER BY s.updated_at DESC, s.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return rank_sets_by_query($stmt->fetchAll(), $query);
}

function get_shared_sets(PDO $pdo, int $userId, string $query = ''): array
{
    $sql = '
        SELECT s.*, u.name AS owner_name, c.name AS class_name, sp.role AS shared_role,
               COALESCE(fc.card_count, 0) AS card_count,
               COALESCE(fc.card_terms, "") AS card_terms,
               COALESCE(fc.card_definitions, "") AS card_definitions
        FROM vocabulary_set_permissions sp
        INNER JOIN vocabulary_sets s ON s.id = sp.set_id
        INNER JOIN users u ON u.id = s.user_id
        LEFT JOIN learning_classes c ON c.id = s.class_id
        LEFT JOIN (
            SELECT
                set_id,
                COUNT(*) AS card_count,
                GROUP_CONCAT(term SEPARATOR " ") AS card_terms,
                GROUP_CONCAT(definition SEPARATOR " ") AS card_definitions
            FROM flashcards
            GROUP BY set_id
        ) fc ON fc.set_id = s.id
        WHERE sp.user_id = ?
    ';
    $params = [$userId];

    $sql .= '
        ORDER BY
            CASE sp.role WHEN "admin" THEN 0 WHEN "editor" THEN 1 ELSE 2 END,
            s.updated_at DESC,
            s.created_at DESC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return rank_sets_by_query($stmt->fetchAll(), $query);
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
        SELECT s.*, NULL AS shared_role
        FROM vocabulary_sets s
        WHERE s.id = ?
          AND s.user_id = ?
        LIMIT 1
    ');
    $stmt->execute([$setId, $userId]);
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
    return (int) ($set['user_id'] ?? 0) === $userId;
}

function can_manage_set_row(array $set, int $userId): bool
{
    return (int) ($set['user_id'] ?? 0) === $userId;
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
            <a class="btn btn-outline-primary" href="<?= app_url('pages/sets.php') ?>"><i class="bi bi-collection"></i> My Sets</a>
        </div>

        <?php if (!$sets): ?>
            <div class="empty-state compact">
                <i class="bi bi-folder2-open"></i>
                <h3>Chưa có bộ từ nào có thể học</h3>
                <p>Bạn có thể tạo bộ từ riêng, học bộ public hoặc tham gia một lớp được cấp quyền.</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <a class="btn btn-primary" href="<?= app_url('pages/create_set.php') ?>">Tạo bộ từ</a>
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
                            <a class="btn btn-sm btn-primary" href="<?= app_url($modePath) ?>?set_id=<?= (int) $set['id'] ?>">
                                Vào <?= e($modeName) ?>
                            </a>
                            <?php if (can_edit_set_row($set, current_user_id())): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= app_url('pages/edit_set.php') ?>?id=<?= (int) $set['id'] ?>">
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
