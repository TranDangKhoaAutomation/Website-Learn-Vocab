<?php
declare(strict_types=1);

/*
Web:
  Mở /tool/import_vocab_to_db.php, bật "Tách bộ từ theo source_title" rồi bấm Import.

CLI:
  php tool/import_vocab_to_db.php vocab.json
  php tool/import_vocab_to_db.php vocab.json --dry-run=1
  php tool/import_vocab_to_db.php --set-id=1
  php tool/import_vocab_to_db.php --set-id=1 --json=tool/vocab.json --dry-run=1
  php tool/import_vocab_to_db.php --user-id=1 --by-title=1 --dry-run=1
  php tool/import_vocab_to_db.php vocab.json --no-online-ipa=1

File này đọc vocab.json rồi thêm vào bảng flashcards.
Trước khi thêm, hệ thống kiểm tra trùng từ trong toàn bộ bảng flashcards.
Nếu bật by-title, mỗi item sẽ được đưa vào bộ từ có title bằng source_title.
Nếu thiếu IPA, tool tự bỏ phần trong ngoặc như (n), (adj), tách cụm từ và tra IPA từng từ.
*/

ini_set('memory_limit', '512M');

$isCli = PHP_SAPI === 'cli';

if ($isCli) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/set_access.php';
} else {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/set_access.php';

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
}

const DEFAULT_JSON_FILE = __DIR__ . '/vocab.json';
const IPA_CACHE_FILE = __DIR__ . '/ipa_cache.json';
const IPA_LOOKUP_TIMEOUT = 5;

function import_h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function import_clean_text(mixed $value): string
{
    $text = trim((string) ($value ?? ''));

    if ($text === '') {
        return '';
    }

    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim((string) $text);
}

function import_limit_text(string $value, int $maxLength): string
{
    if (mb_strlen($value, 'UTF-8') <= $maxLength) {
        return $value;
    }

    return mb_substr($value, 0, $maxLength, 'UTF-8');
}

function import_normalize_key(string $term): string
{
    $term = import_clean_text($term);
    $term = mb_strtolower($term, 'UTF-8');
    $term = preg_replace('/\s+/u', ' ', $term);

    return trim((string) $term);
}

function import_duplicate_keys(string $term): array
{
    $keys = [];
    $exact = import_normalize_key($term);

    if ($exact !== '') {
        $keys[] = $exact;
    }

    $withoutTrailingNote = preg_replace('/\s*\([^)]{1,35}\)\s*$/u', '', $term);
    $base = import_normalize_key((string) $withoutTrailingNote);

    if ($base !== '' && $base !== $exact) {
        $keys[] = $base;
    }

    return array_values(array_unique($keys));
}

function import_pick_value(array $item, array $keys): string
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $item)) {
            return import_clean_text($item[$key]);
        }
    }

    return '';
}

function import_load_json_items(string $jsonFile): array
{
    if (!is_file($jsonFile)) {
        throw new RuntimeException('Không tìm thấy file JSON: ' . $jsonFile);
    }

    $content = file_get_contents($jsonFile);

    if ($content === false || trim($content) === '') {
        throw new RuntimeException('File JSON rỗng hoặc không đọc được.');
    }

    $decoded = json_decode($content, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('File JSON không hợp lệ: ' . json_last_error_msg());
    }

    if (isset($decoded['data']) && is_array($decoded['data'])) {
        return $decoded['data'];
    }

    if (array_is_list($decoded)) {
        return $decoded;
    }

    throw new RuntimeException('File JSON phải có dạng {"data":[...]} hoặc là một mảng trực tiếp.');
}

function import_normalize_item(array $item): array
{
    $term = import_pick_value($item, ['word', 'term', 'english', 'en']);
    $definition = import_pick_value($item, ['meaning', 'definition', 'vietnamese', 'vi']);
    $pronunciation = import_pick_value($item, ['pronunciation', 'ipa']);
    $example = import_pick_value($item, ['example_sentence', 'example', 'sentence']);
    $imageUrl = import_pick_value($item, ['image_url', 'image', 'thumbnail']);

    return [
        'term' => import_limit_text($term, 190),
        'definition' => import_limit_text($definition, 255),
        'pronunciation' => import_limit_text($pronunciation, 120),
        'example_sentence' => $example,
        'image_url' => import_limit_text($imageUrl, 500),
        'source_title' => import_pick_value($item, ['source_title', 'title', 'source']),
    ];
}

function import_strip_term_notes(string $term): string
{
    $term = import_clean_text($term);
    $term = preg_replace('/\s*\([^)]*\)/u', ' ', $term);
    $term = preg_replace('/\s+/u', ' ', (string) $term);

    return trim((string) $term);
}

function import_ipa_key(string $term): string
{
    $term = import_strip_term_notes($term);
    $term = mb_strtolower($term, 'UTF-8');
    $term = preg_replace('/[^\p{L}\p{N}\s\'’.-]+/u', ' ', $term);
    $term = preg_replace('/\s+/u', ' ', (string) $term);

    return trim((string) $term, " \t\n\r\0\x0B'’.-");
}

function import_term_parts_for_ipa(string $term): array
{
    $base = import_ipa_key($term);

    if ($base === '') {
        return [];
    }

    $rawParts = preg_split('/\s+/u', $base) ?: [];
    $parts = [];
    $ignored = [
        'n' => true,
        'v' => true,
        'adj' => true,
        'adv' => true,
        'prep' => true,
        'pron' => true,
        'conj' => true,
        'det' => true,
        'sbd' => true,
        'sb' => true,
        'sth' => true,
        'smth' => true,
    ];

    foreach ($rawParts as $part) {
        $part = trim((string) $part, " \t\n\r\0\x0B'’.-");

        if ($part === '' || isset($ignored[$part]) || preg_match('/^\d+$/', $part)) {
            continue;
        }

        $parts[] = $part;
    }

    return $parts;
}

function import_clean_ipa(mixed $value): string
{
    $ipa = import_clean_text($value);

    if ($ipa === '') {
        return '';
    }

    $ipa = str_replace(['[', ']'], ['/', '/'], $ipa);
    $ipa = preg_replace('/\s+/u', ' ', (string) $ipa);
    $ipa = trim((string) $ipa);

    if ($ipa !== '' && $ipa[0] !== '/') {
        $ipa = '/' . $ipa . '/';
    }

    return $ipa;
}

function import_add_ipa_to_map(array &$map, string $term, string $ipa): void
{
    $ipa = import_clean_ipa($ipa);
    $key = import_ipa_key($term);

    if ($key !== '' && $ipa !== '' && !isset($map[$key])) {
        $map[$key] = $ipa;
    }
}

function import_builtin_ipa_map(): array
{
    return [
        'a' => '/ə/',
        'an' => '/ən/',
        'and' => '/ænd/',
        'are' => '/ɑːr/',
        'as' => '/æz/',
        'at' => '/æt/',
        'be' => '/biː/',
        'by' => '/baɪ/',
        'for' => '/fɔːr/',
        'from' => '/frʌm/',
        'in' => '/ɪn/',
        'is' => '/ɪz/',
        'of' => '/əv/',
        'on' => '/ɑːn/',
        'or' => '/ɔːr/',
        'the' => '/ðə/',
        'to' => '/tuː/',
        'with' => '/wɪð/',
    ];
}

function import_load_existing_ipa_map(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT term, pronunciation
        FROM flashcards
        WHERE pronunciation IS NOT NULL AND pronunciation <> ""
    ');

    $map = [];

    foreach ($stmt->fetchAll() as $row) {
        import_add_ipa_to_map($map, (string) $row['term'], (string) $row['pronunciation']);
    }

    return $map;
}

function import_add_json_ipa_to_map(array &$map, array $items): void
{
    foreach ($items as $rawItem) {
        if (!is_array($rawItem)) {
            continue;
        }

        $item = import_normalize_item($rawItem);

        if ($item['term'] !== '' && $item['pronunciation'] !== '') {
            import_add_ipa_to_map($map, $item['term'], $item['pronunciation']);
        }
    }
}

function import_load_ipa_cache(): array
{
    if (!is_file(IPA_CACHE_FILE)) {
        return ['entries' => [], 'misses' => []];
    }

    $content = file_get_contents(IPA_CACHE_FILE);
    $decoded = $content !== false ? json_decode($content, true) : null;

    if (!is_array($decoded)) {
        return ['entries' => [], 'misses' => []];
    }

    return [
        'entries' => is_array($decoded['entries'] ?? null) ? $decoded['entries'] : [],
        'misses' => is_array($decoded['misses'] ?? null) ? $decoded['misses'] : [],
    ];
}

function import_save_ipa_cache(array $cache): void
{
    $payload = [
        'updated_at' => date(DATE_ATOM),
        'source' => 'https://api.dictionaryapi.dev, https://api.datamuse.com',
        'entries' => $cache['entries'] ?? [],
        'misses' => $cache['misses'] ?? [],
    ];

    file_put_contents(
        IPA_CACHE_FILE,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

function import_http_get_json(string $url): ?array
{
    $body = false;

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => IPA_LOOKUP_TIMEOUT,
            CURLOPT_TIMEOUT => IPA_LOOKUP_TIMEOUT,
            CURLOPT_USERAGENT => 'EnglishLearningApp IPA Importer',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $status < 200 || $status >= 300) {
            return null;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => IPA_LOOKUP_TIMEOUT,
                'header' => "Accept: application/json\r\nUser-Agent: EnglishLearningApp IPA Importer\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            return null;
        }
    }

    $decoded = json_decode((string) $body, true);

    return is_array($decoded) ? $decoded : null;
}

function import_extract_ipa_from_dictionary(array $decoded): string
{
    if (!array_is_list($decoded)) {
        return '';
    }

    foreach ($decoded as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        foreach (($entry['phonetics'] ?? []) as $phonetic) {
            if (!is_array($phonetic)) {
                continue;
            }

            $ipa = import_clean_ipa($phonetic['text'] ?? '');

            if ($ipa !== '') {
                return $ipa;
            }
        }

        $ipa = import_clean_ipa($entry['phonetic'] ?? '');

        if ($ipa !== '') {
            return $ipa;
        }
    }

    return '';
}

function import_fetch_word_ipa_online(string $word): string
{
    $word = import_ipa_key($word);

    if ($word === '' || str_contains($word, ' ')) {
        return '';
    }

    $url = 'https://api.dictionaryapi.dev/api/v2/entries/en/' . rawurlencode($word);
    $decoded = import_http_get_json($url);

    if (!$decoded) {
        usleep(180000);
        $decoded = import_http_get_json($url);
    }

    $ipa = $decoded ? import_extract_ipa_from_dictionary($decoded) : '';

    return $ipa !== '' ? $ipa : import_fetch_word_ipa_datamuse($word);
}

function import_arpabet_phone_to_ipa(string $phone, string $stress): string
{
    $map = [
        'AA' => 'ɑ',
        'AE' => 'æ',
        'AH' => $stress === '0' ? 'ə' : 'ʌ',
        'AO' => 'ɔː',
        'AW' => 'aʊ',
        'AY' => 'aɪ',
        'B' => 'b',
        'CH' => 'tʃ',
        'D' => 'd',
        'DH' => 'ð',
        'EH' => 'ɛ',
        'ER' => $stress === '0' ? 'ɚ' : 'ɝ',
        'EY' => 'eɪ',
        'F' => 'f',
        'G' => 'ɡ',
        'HH' => 'h',
        'IH' => 'ɪ',
        'IY' => 'iː',
        'JH' => 'dʒ',
        'K' => 'k',
        'L' => 'l',
        'M' => 'm',
        'N' => 'n',
        'NG' => 'ŋ',
        'OW' => 'oʊ',
        'OY' => 'ɔɪ',
        'P' => 'p',
        'R' => 'r',
        'S' => 's',
        'SH' => 'ʃ',
        'T' => 't',
        'TH' => 'θ',
        'UH' => 'ʊ',
        'UW' => 'uː',
        'V' => 'v',
        'W' => 'w',
        'Y' => 'j',
        'Z' => 'z',
        'ZH' => 'ʒ',
    ];

    return $map[$phone] ?? '';
}

function import_arpabet_to_ipa(string $arpabet): string
{
    $tokens = preg_split('/\s+/u', trim($arpabet)) ?: [];
    $ipa = '';

    foreach ($tokens as $token) {
        if (!preg_match('/^([A-Z]+)([012])?$/', $token, $matches)) {
            continue;
        }

        $phone = $matches[1];
        $stress = $matches[2] ?? '';
        $converted = import_arpabet_phone_to_ipa($phone, $stress);

        if ($converted === '') {
            continue;
        }

        if ($stress === '1') {
            $converted = 'ˈ' . $converted;
        } elseif ($stress === '2') {
            $converted = 'ˌ' . $converted;
        }

        $ipa .= $converted;
    }

    return $ipa !== '' ? '/' . $ipa . '/' : '';
}

function import_extract_ipa_from_datamuse(array $decoded, string $word): string
{
    if (!array_is_list($decoded)) {
        return '';
    }

    foreach ($decoded as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $entryWord = import_ipa_key((string) ($entry['word'] ?? ''));

        if ($entryWord !== '' && $entryWord !== $word) {
            continue;
        }

        foreach (($entry['tags'] ?? []) as $tag) {
            if (!is_string($tag) || !str_starts_with($tag, 'pron:')) {
                continue;
            }

            $ipa = import_arpabet_to_ipa(substr($tag, 5));

            if ($ipa !== '') {
                return $ipa;
            }
        }
    }

    return '';
}

function import_fetch_word_ipa_datamuse(string $word): string
{
    $word = import_ipa_key($word);

    if ($word === '' || str_contains($word, ' ')) {
        return '';
    }

    $url = 'https://api.datamuse.com/words?sp=' . rawurlencode($word) . '&md=r&max=1';
    $decoded = import_http_get_json($url);

    return $decoded ? import_extract_ipa_from_datamuse($decoded, $word) : '';
}

function import_lookup_ipa_part(string $part, array &$ipaMap, array &$ipaCache, bool &$cacheChanged, bool $onlineLookup): string
{
    $key = import_ipa_key($part);

    if ($key === '') {
        return '';
    }

    if (isset($ipaMap[$key])) {
        return import_clean_ipa($ipaMap[$key]);
    }

    if (isset($ipaCache['entries'][$key])) {
        $ipa = import_clean_ipa($ipaCache['entries'][$key]);
        import_add_ipa_to_map($ipaMap, $key, $ipa);
        return $ipa;
    }

    if (!$onlineLookup) {
        return '';
    }

    $ipa = import_fetch_word_ipa_online($key);

    if ($ipa !== '') {
        $ipaCache['entries'][$key] = $ipa;
        unset($ipaCache['misses'][$key]);
        import_add_ipa_to_map($ipaMap, $key, $ipa);
    } else {
        unset($ipaCache['misses'][$key]);
    }

    $cacheChanged = true;

    return $ipa;
}

function import_generate_pronunciation(string $term, array &$ipaMap, array &$ipaCache, bool &$cacheChanged, bool $onlineLookup): string
{
    $key = import_ipa_key($term);

    if ($key === '') {
        return '';
    }

    if (isset($ipaMap[$key])) {
        return import_limit_text(import_clean_ipa($ipaMap[$key]), 120);
    }

    if (isset($ipaCache['entries'][$key])) {
        $ipa = import_clean_ipa($ipaCache['entries'][$key]);
        import_add_ipa_to_map($ipaMap, $key, $ipa);
        return import_limit_text($ipa, 120);
    }

    $parts = import_term_parts_for_ipa($term);

    if (!$parts) {
        return '';
    }

    $ipaParts = [];

    foreach ($parts as $part) {
        $ipa = import_lookup_ipa_part($part, $ipaMap, $ipaCache, $cacheChanged, $onlineLookup);

        if ($ipa === '') {
            return '';
        }

        $ipaParts[] = $ipa;
    }

    $generated = import_limit_text(implode(' ', $ipaParts), 120);

    if ($generated !== '') {
        $ipaCache['entries'][$key] = $generated;
        unset($ipaCache['misses'][$key]);
        import_add_ipa_to_map($ipaMap, $key, $generated);
        $cacheChanged = true;
    }

    return $generated;
}

function import_load_existing_term_map(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT f.id, f.set_id, f.term, f.pronunciation, s.title AS set_title
        FROM flashcards f
        INNER JOIN vocabulary_sets s ON s.id = f.set_id
    ');

    $map = [];

    foreach ($stmt->fetchAll() as $row) {
        foreach (import_duplicate_keys((string) $row['term']) as $key) {
            if (!isset($map[$key])) {
                $map[$key] = [
                    'card_id' => (int) $row['id'],
                    'set_id' => (int) $row['set_id'],
                    'set_title' => (string) $row['set_title'],
                    'term' => (string) $row['term'],
                    'pronunciation' => (string) ($row['pronunciation'] ?? ''),
                ];
            }
        }
    }

    return $map;
}

function import_find_duplicate(array $map, array $keys): ?array
{
    foreach ($keys as $key) {
        if (isset($map[$key])) {
            return $map[$key];
        }
    }

    return null;
}

function import_add_keys_to_map(array &$map, array $keys, array $info): void
{
    foreach ($keys as $key) {
        if (!isset($map[$key])) {
            $map[$key] = $info;
        }
    }
}

function import_get_set(PDO $pdo, int $setId): ?array
{
    $stmt = $pdo->prepare('SELECT id, user_id, title FROM vocabulary_sets WHERE id = ? LIMIT 1');
    $stmt->execute([$setId]);
    $set = $stmt->fetch();

    return $set ?: null;
}

function import_get_default_user_id(PDO $pdo): int
{
    $stmt = $pdo->query('SELECT id FROM users ORDER BY id ASC LIMIT 1');
    $userId = (int) $stmt->fetchColumn();

    if ($userId <= 0) {
        throw new RuntimeException('Chưa có user nào trong database để làm chủ sở hữu bộ từ import.');
    }

    return $userId;
}

function import_get_owned_set_by_title(PDO $pdo, int $userId, string $title): ?array
{
    $stmt = $pdo->prepare('
        SELECT id, user_id, title
        FROM vocabulary_sets
        WHERE user_id = ? AND title = ?
        LIMIT 1
    ');
    $stmt->execute([$userId, $title]);
    $set = $stmt->fetch();

    return $set ?: null;
}

function import_create_set_from_title(PDO $pdo, int $userId, string $title): array
{
    $stmt = $pdo->prepare('
        INSERT INTO vocabulary_sets (user_id, class_id, title, description, is_public, visibility, created_at, updated_at)
        VALUES (?, NULL, ?, ?, 0, "private", NOW(), NOW())
    ');
    $stmt->execute([$userId, $title, 'Imported from vocab.json.']);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'user_id' => $userId,
        'title' => $title,
    ];
}

function import_get_target_set(PDO $pdo, array $item, int $fallbackSetId, bool $groupByTitle, ?int $userId, bool $dryRun, array &$setCache, array &$createdSets): array
{
    if (!$groupByTitle) {
        $set = $setCache['fallback'] ?? import_get_set($pdo, $fallbackSetId);

        if (!$set) {
            throw new RuntimeException('Bộ từ không tồn tại.');
        }

        $setCache['fallback'] = $set;
        return $set;
    }

    if (!$userId) {
        throw new RuntimeException('Import theo source_title cần user_id.');
    }

    $title = import_clean_text($item['source_title'] ?? '');

    if ($title === '') {
        if ($fallbackSetId > 0) {
            return import_get_target_set($pdo, $item, $fallbackSetId, false, $userId, $dryRun, $setCache, $createdSets);
        }

        $title = 'Imported Vocabulary';
    }

    $title = import_limit_text($title, 190);
    $cacheKey = mb_strtolower($title, 'UTF-8');

    if (isset($setCache[$cacheKey])) {
        return $setCache[$cacheKey];
    }

    $set = import_get_owned_set_by_title($pdo, $userId, $title);

    if ($set) {
        $setCache[$cacheKey] = $set;
        return $set;
    }

    if ($dryRun) {
        $set = [
            'id' => 0,
            'user_id' => $userId,
            'title' => $title,
        ];
    } else {
        $set = import_create_set_from_title($pdo, $userId, $title);
    }

    $createdSets[$cacheKey] = $set;
    $setCache[$cacheKey] = $set;

    return $set;
}

function import_run(PDO $pdo, string $jsonFile, int $setId, bool $dryRun, bool $groupByTitle = false, ?int $userId = null, bool $autoIpa = true, bool $onlineIpa = true): array
{
    if (!$groupByTitle && $setId <= 0) {
        throw new RuntimeException('Vui lòng chọn bộ từ cần import.');
    }

    $set = $setId > 0 ? import_get_set($pdo, $setId) : null;

    if (!$groupByTitle && !$set) {
        throw new RuntimeException('Bộ từ không tồn tại.');
    }

    $items = import_load_json_items($jsonFile);
    $dbMap = import_load_existing_term_map($pdo);
    $ipaMap = array_replace(import_builtin_ipa_map(), import_load_existing_ipa_map($pdo));
    import_add_json_ipa_to_map($ipaMap, $items);
    $ipaCache = import_load_ipa_cache();
    $ipaCacheChanged = false;
    $jsonMap = [];
    $setCache = [];
    $createdSets = [];
    $report = [
        'set' => $set,
        'json_file' => $jsonFile,
        'dry_run' => $dryRun,
        'group_by_title' => $groupByTitle,
        'total_json' => count($items),
        'created_sets' => [],
        'auto_ipa' => $autoIpa,
        'online_ipa' => $onlineIpa,
        'ipa_generated' => [],
        'ipa_updated_existing' => [],
        'ipa_missing' => [],
        'inserted' => [],
        'skipped_db' => [],
        'skipped_json' => [],
        'skipped_invalid' => [],
    ];

    $insert = $pdo->prepare('
        INSERT INTO flashcards (set_id, term, definition, example_sentence, pronunciation, image_url, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');
    $updateExistingIpa = $pdo->prepare('
        UPDATE flashcards
        SET pronunciation = ?
        WHERE id = ? AND (pronunciation IS NULL OR pronunciation = "")
    ');

    if (!$dryRun) {
        $pdo->beginTransaction();
    }

    try {
        foreach ($items as $index => $rawItem) {
            if (!is_array($rawItem)) {
                $report['skipped_invalid'][] = [
                    'index' => $index + 1,
                    'reason' => 'Dòng JSON không phải object.',
                ];
                continue;
            }

            $item = import_normalize_item($rawItem);
            $keys = import_duplicate_keys($item['term']);

            if ($item['term'] === '' || $item['definition'] === '' || !$keys) {
                $report['skipped_invalid'][] = [
                    'index' => $index + 1,
                    'word' => $item['term'],
                    'source_title' => $item['source_title'],
                    'reason' => 'Thiếu word hoặc meaning.',
                ];
                continue;
            }

            $duplicateInJson = import_find_duplicate($jsonMap, $keys);

            if ($duplicateInJson) {
                $report['skipped_json'][] = [
                    'index' => $index + 1,
                    'word' => $item['term'],
                    'source_title' => $item['source_title'],
                    'duplicate_of' => $duplicateInJson['term'],
                ];
                continue;
            }

            $duplicateInDb = import_find_duplicate($dbMap, $keys);

            if ($duplicateInDb) {
                $targetSetTitle = import_clean_text($item['source_title'] ?? '');
                if ($targetSetTitle === '') {
                    $targetSetTitle = (string) ($set['title'] ?? 'Imported Vocabulary');
                }

                if ($autoIpa && trim((string) ($duplicateInDb['pronunciation'] ?? '')) === '') {
                    $generatedIpa = import_generate_pronunciation($item['term'], $ipaMap, $ipaCache, $ipaCacheChanged, $onlineIpa);

                    if ($generatedIpa !== '') {
                        if (!$dryRun) {
                            $updateExistingIpa->execute([$generatedIpa, (int) $duplicateInDb['card_id']]);
                        }

                        import_add_ipa_to_map($ipaMap, $duplicateInDb['term'], $generatedIpa);
                        $report['ipa_updated_existing'][] = [
                            'index' => $index + 1,
                            'card_id' => (int) $duplicateInDb['card_id'],
                            'word' => $duplicateInDb['term'],
                            'ipa' => $generatedIpa,
                        ];
                    }
                }

                $report['skipped_db'][] = [
                    'index' => $index + 1,
                    'word' => $item['term'],
                    'source_title' => $item['source_title'],
                    'target_set_title' => $targetSetTitle,
                    'existing_term' => $duplicateInDb['term'],
                    'set_title' => $duplicateInDb['set_title'],
                    'card_id' => $duplicateInDb['card_id'],
                ];
                import_add_keys_to_map($jsonMap, $keys, ['term' => $item['term']]);
                continue;
            }

            $targetSet = import_get_target_set($pdo, $item, $setId, $groupByTitle, $userId, $dryRun, $setCache, $createdSets);
            $targetSetId = (int) $targetSet['id'];
            $targetSetTitle = (string) $targetSet['title'];

            if ($autoIpa && $item['pronunciation'] === '') {
                $generatedIpa = import_generate_pronunciation($item['term'], $ipaMap, $ipaCache, $ipaCacheChanged, $onlineIpa);

                if ($generatedIpa !== '') {
                    $item['pronunciation'] = $generatedIpa;
                    $report['ipa_generated'][] = [
                        'index' => $index + 1,
                        'word' => $item['term'],
                        'ipa' => $generatedIpa,
                    ];
                } else {
                    $report['ipa_missing'][] = [
                        'index' => $index + 1,
                        'word' => $item['term'],
                    ];
                }
            } elseif ($item['pronunciation'] !== '') {
                import_add_ipa_to_map($ipaMap, $item['term'], $item['pronunciation']);
            }

            if (!$dryRun) {
                $insert->execute([
                    $targetSetId,
                    $item['term'],
                    $item['definition'],
                    $item['example_sentence'],
                    $item['pronunciation'],
                    $item['image_url'],
                ]);
                $item['card_id'] = (int) $pdo->lastInsertId();
            }

            $item['target_set_id'] = $targetSetId;
            $item['target_set_title'] = $targetSetTitle;
            $report['inserted'][] = $item;
            import_add_keys_to_map($jsonMap, $keys, ['term' => $item['term']]);
            import_add_keys_to_map($dbMap, $keys, [
                'card_id' => $item['card_id'] ?? 0,
                'set_id' => $targetSetId,
                'set_title' => $targetSetTitle,
                'term' => $item['term'],
            ]);
        }

        $report['created_sets'] = array_values($createdSets);

        if (!$dryRun && $report['inserted']) {
            $updatedSetIds = array_values(array_unique(array_filter(array_map(
                static fn(array $item): int => (int) ($item['target_set_id'] ?? 0),
                $report['inserted']
            ))));
            $stmt = $pdo->prepare('UPDATE vocabulary_sets SET updated_at = NOW() WHERE id = ?');

            foreach ($updatedSetIds as $updatedSetId) {
                $stmt->execute([$updatedSetId]);
            }
        }

        if (!$dryRun) {
            $pdo->commit();
        }

        if ($ipaCacheChanged) {
            import_save_ipa_cache($ipaCache);
        }
    } catch (Throwable $exception) {
        if (!$dryRun && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }

    return $report;
}

function import_render_report(array $report): string
{
    ob_start();
    ?>
    <section class="import-report">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="metric"><span>Tổng JSON</span><strong><?= (int) $report['total_json'] ?></strong></div></div>
            <div class="col-md-3"><div class="metric success"><span><?= $report['dry_run'] ? 'Có thể thêm' : 'Đã thêm' ?></span><strong><?= count($report['inserted']) ?></strong></div></div>
            <div class="col-md-3"><div class="metric warning"><span>Trùng DB</span><strong><?= count($report['skipped_db']) ?></strong></div></div>
            <div class="col-md-3"><div class="metric muted"><span>Bỏ qua</span><strong><?= count($report['skipped_json']) + count($report['skipped_invalid']) ?></strong></div></div>
        </div>

        <?php if (!empty($report['created_sets'])): ?>
            <div class="alert alert-info">
                <?= $report['dry_run'] ? 'Sẽ tạo' : 'Đã tạo' ?> <?= count($report['created_sets']) ?> bộ từ theo <code>source_title</code>:
                <?= import_h(implode(', ', array_map(static fn(array $set): string => (string) $set['title'], $report['created_sets']))) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($report['auto_ipa'])): ?>
            <div class="alert alert-secondary">
                IPA tự động: đã thêm <?= count($report['ipa_generated'] ?? []) ?> mục, cập nhật <?= count($report['ipa_updated_existing'] ?? []) ?> mục đã có<?= !empty($report['ipa_missing']) ? ', chưa tìm được ' . count($report['ipa_missing']) . ' mục' : '' ?>.
                Tool bỏ qua phần trong ngoặc như <code>(n)</code>, <code>(adj)</code> trước khi tra IPA.
            </div>
        <?php endif; ?>

        <?php if ($report['inserted']): ?>
            <h2><?= $report['dry_run'] ? 'Danh sách sẽ được thêm' : 'Đã thêm vào database' ?></h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>#</th><th>Bài / bộ từ</th><th>Từ</th><th>Nghĩa</th><th>IPA</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['inserted'] as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= import_h($item['target_set_title'] ?? $item['source_title'] ?? '') ?></td>
                            <td><?= import_h($item['term']) ?></td>
                            <td><?= import_h($item['definition']) ?></td>
                            <td><?= import_h($item['pronunciation']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($report['skipped_db']): ?>
            <h2>Từ đã có trong hệ thống</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>#</th><th>Bài trong JSON</th><th>Từ trong JSON</th><th>Từ đã có</th><th>Bộ từ đã có</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['skipped_db'] as $item): ?>
                        <tr>
                            <td><?= (int) $item['index'] ?></td>
                            <td><?= import_h($item['source_title'] ?? $item['target_set_title'] ?? '') ?></td>
                            <td><?= import_h($item['word']) ?></td>
                            <td><?= import_h($item['existing_term']) ?></td>
                            <td><?= import_h($item['set_title']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($report['skipped_json'] || $report['skipped_invalid']): ?>
            <h2>Dòng bị bỏ qua</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>#</th><th>Bài trong JSON</th><th>Từ</th><th>Lý do</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['skipped_json'] as $item): ?>
                        <tr>
                            <td><?= (int) $item['index'] ?></td>
                            <td><?= import_h($item['source_title'] ?? '') ?></td>
                            <td><?= import_h($item['word']) ?></td>
                            <td>Trùng trong chính file JSON với: <?= import_h($item['duplicate_of']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($report['skipped_invalid'] as $item): ?>
                        <tr>
                            <td><?= (int) $item['index'] ?></td>
                            <td><?= import_h($item['source_title'] ?? '') ?></td>
                            <td><?= import_h($item['word'] ?? '') ?></td>
                            <td><?= import_h($item['reason']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <?php
    return (string) ob_get_clean();
}

if (defined('IMPORT_VOCAB_TO_DB_LIBRARY_ONLY') && IMPORT_VOCAB_TO_DB_LIBRARY_ONLY) {
    return;
}

if ($isCli) {
    $options = [];
    $positionalJson = null;

    $cliArgs = array_slice($argv, 1);
    for ($i = 0, $count = count($cliArgs); $i < $count; $i++) {
        $arg = $cliArgs[$i];

        if (str_starts_with($arg, '--')) {
            $raw = substr($arg, 2);
            $equalsAt = strpos($raw, '=');

            if ($equalsAt !== false) {
                $name = substr($raw, 0, $equalsAt);
                $value = substr($raw, $equalsAt + 1);
            } else {
                $name = $raw;
                $next = $cliArgs[$i + 1] ?? null;
                if ($next !== null && !str_starts_with($next, '-')) {
                    $value = $next;
                    $i++;
                } else {
                    $value = '1';
                }
            }

            $options[$name] = $value;
            continue;
        }

        if ($arg !== '' && $positionalJson === null) {
            $positionalJson = $arg;
        }
    }

    $setId = max(0, (int) ($options['set-id'] ?? 0));
    $userId = max(0, (int) ($options['user-id'] ?? 0));
    $jsonFile = (string) ($options['json'] ?? $positionalJson ?? DEFAULT_JSON_FILE);
    $dryRun = filter_var($options['dry-run'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $autoIpa = !filter_var($options['no-ipa'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $onlineIpa = !filter_var($options['no-online-ipa'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$pdo) {
        fwrite(STDERR, "Khong ket noi duoc database.\n");
        exit(1);
    }

    try {
        $groupByTitle = filter_var($options['by-title'] ?? false, FILTER_VALIDATE_BOOLEAN) || $setId <= 0;

        if ($groupByTitle && $userId <= 0) {
            $userId = import_get_default_user_id($pdo);
        }

        $report = import_run($pdo, $jsonFile, $setId, $dryRun, $groupByTitle, $userId ?: null, $autoIpa, $onlineIpa);
        echo json_encode([
            'ok' => true,
            'mode' => $report['group_by_title'] ? 'by_source_title' : 'single_set',
            'owner_user_id' => $userId ?: null,
            'set_id' => (int) ($report['set']['id'] ?? 0),
            'set_title' => $report['set']['title'] ?? null,
            'dry_run' => $report['dry_run'],
            'total_json' => $report['total_json'],
            'created_sets' => count($report['created_sets']),
            'auto_ipa' => $report['auto_ipa'],
            'online_ipa' => $report['online_ipa'],
            'ipa_generated' => count($report['ipa_generated']),
            'ipa_updated_existing' => count($report['ipa_updated_existing']),
            'ipa_missing' => count($report['ipa_missing']),
            'inserted' => count($report['inserted']),
            'skipped_db' => count($report['skipped_db']),
            'skipped_json' => count($report['skipped_json']),
            'skipped_invalid' => count($report['skipped_invalid']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } catch (Throwable $exception) {
        fwrite(STDERR, "Loi: " . $exception->getMessage() . "\n");
        exit(1);
    }

    exit(0);
}

$userId = current_user_id();
$editableSets = [];
$message = null;
$reportHtml = '';
$jsonFile = DEFAULT_JSON_FILE;
$autoRun = isset($_POST['auto_run']);
$dryRun = !$autoRun && isset($_POST['dry_run']);
$groupByTitle = $autoRun || ($_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['group_by_title']) : true);
$selectedSetId = $autoRun ? 0 : max(0, (int) ($_POST['set_id'] ?? ($_GET['set_id'] ?? 0)));

if ($pdo) {
    $editableSets = array_values(array_filter(
        get_accessible_sets($pdo, $userId),
        static fn(array $set): bool => can_edit_set_row($set, $userId)
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$pdo) {
            throw new RuntimeException('Không kết nối được database.');
        }

        $set = $selectedSetId > 0 ? get_editable_set($pdo, $selectedSetId, $userId) : null;

        if (!$groupByTitle && !$set) {
            throw new RuntimeException('Bạn không có quyền thêm flashcard vào bộ từ này.');
        }

        if ($groupByTitle && $selectedSetId > 0 && !$set) {
            throw new RuntimeException('Bạn không có quyền dùng bộ từ fallback này.');
        }

        $report = import_run($pdo, $jsonFile, $selectedSetId, $dryRun, $groupByTitle, $userId);
        $reportHtml = import_render_report($report);
        $message = [
            'type' => 'success',
            'text' => $dryRun ? 'Đã kiểm tra file JSON, chưa ghi vào database.' : 'Import hoàn tất.',
        ];
        $message['text'] = $autoRun ? 'Tự động chạy hoàn tất.' : ($dryRun ? 'Đã kiểm tra file JSON, chưa ghi vào database.' : 'Import hoàn tất.');
    } catch (Throwable $exception) {
        $message = [
            'type' => 'danger',
            'text' => $exception->getMessage(),
        ];
    }
}

$jsonCount = 0;
$jsonError = '';

try {
    $jsonCount = count(import_load_json_items($jsonFile));
} catch (Throwable $exception) {
    $jsonError = $exception->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import vocab JSON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7fb;
            color: #172033;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .import-page {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 18px;
        }

        .import-panel {
            background: #fff;
            border: 1px solid #dce5f2;
            border-radius: 22px;
            box-shadow: 0 20px 45px rgba(33, 52, 83, .08);
            padding: 28px;
        }

        .metric {
            border: 1px solid #dce5f2;
            border-radius: 18px;
            padding: 18px;
            background: #f8fbff;
        }

        .metric span {
            display: block;
            color: #65748b;
            font-size: .9rem;
        }

        .metric strong {
            display: block;
            font-size: 2rem;
            line-height: 1.1;
        }

        .metric.success { background: #ecfdf5; border-color: #bbf7d0; }
        .metric.warning { background: #fffbeb; border-color: #fde68a; }
        .metric.muted { background: #f8fafc; }
        .table { --bs-table-bg: transparent; }
        .small-note { color: #65748b; }
    </style>
</head>
<body>
<main class="import-page">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h1 class="h3 mb-1">Import vocab JSON</h1>
            <p class="small-note mb-0">Đọc <code>tool/vocab.json</code>, kiểm tra trùng từ rồi đưa mỗi từ về đúng bài theo <code>source_title</code>.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>pages/sets.php">Quay lại My Sets</a>
    </div>

    <section class="import-panel">
        <?php if ($message): ?>
            <div class="alert alert-<?= import_h($message['type']) ?>"><?= import_h($message['text']) ?></div>
        <?php endif; ?>

        <?php if ($jsonError): ?>
            <div class="alert alert-danger"><?= import_h($jsonError) ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3 align-items-end mb-4">
            <div class="col-lg-7">
                <label class="form-label" for="set_id">Bộ từ fallback</label>
                <select class="form-select" id="set_id" name="set_id">
                    <option value="">Tự tạo/chọn theo source_title</option>
                    <?php foreach ($editableSets as $set): ?>
                        <option value="<?= (int) $set['id'] ?>" <?= (int) $set['id'] === $selectedSetId ? 'selected' : '' ?>>
                            <?= import_h($set['title']) ?> (<?= (int) $set['card_count'] ?> thẻ)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="group_by_title" name="group_by_title" <?= $groupByTitle ? 'checked' : '' ?>>
                    <label class="form-check-label" for="group_by_title">Tách bộ từ theo source_title</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="dry_run" name="dry_run" <?= $dryRun ? 'checked' : '' ?>>
                    <label class="form-check-label" for="dry_run">Chỉ kiểm tra, chưa ghi DB</label>
                </div>
                <div class="small-note mt-1">File hiện có <?= (int) $jsonCount ?> mục.</div>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-success w-100 mb-2" type="submit" name="auto_run" value="1">Tự động chạy</button>
                <button class="btn btn-primary w-100" type="submit">Import</button>
            </div>
        </form>

        <?php if (!$editableSets): ?>
            <div class="alert alert-warning mb-0">Bạn chưa có bộ từ fallback để chọn. Nút Tự động chạy vẫn có thể tự tạo bộ từ theo <code>source_title</code>.</div>
        <?php endif; ?>

        <?= $reportHtml ?>
    </section>
</main>
</body>
</html>
