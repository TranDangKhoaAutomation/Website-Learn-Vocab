<?php
declare(strict_types=1);

/*
Cách chạy:
php getvoca.php source.html

Hoặc chỉ định file JSON output:
php getvoca.php source.html vocab.json

Chức năng:
- Đọc source HTML
- Lấy title bài
- Cắt từ bằng class TermText notranslate lang-en
- Cắt nghĩa bằng class TermText notranslate lang-vi
- Đọc vocab.json cũ nếu có
- Nếu word đã tồn tại trong vocab.json thì skip
- Nếu word chưa tồn tại thì thêm vào
*/

ini_set('memory_limit', '512M');

function cleanText(string $text): string
{
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

function normalizeWord(string $word): string
{
    $word = cleanText($word);
    $word = mb_strtolower($word, 'UTF-8');
    $word = preg_replace('/\s+/u', ' ', $word);
    return trim($word);
}

function extractTitle(string $html): string
{
    if (preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $m)) {
        return cleanText($m[1]);
    }

    if (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $title = cleanText($m[1]);
        $title = preg_replace('/\s*Flashcards\s*\|\s*Quizlet\s*$/iu', '', $title);
        return trim($title);
    }

    return '';
}

function extractTermText(string $block, string $lang): ?string
{
    $pattern = '/<span\b[^>]*class=["\'][^"\']*\bTermText\b[^"\']*\bnotranslate\b[^"\']*\blang-' . preg_quote($lang, '/') . '\b[^"\']*["\'][^>]*>(.*?)<\/span>/is';

    if (!preg_match($pattern, $block, $m)) {
        return null;
    }

    $text = cleanText($m[1]);

    return $text !== '' ? $text : null;
}

function extractTermBlocks(string $html): array
{
    $marker = '<div aria-label="Term" class="SetPageTermsList-term">';
    $parts = explode($marker, $html);

    array_shift($parts);

    $blocks = [];

    foreach ($parts as $part) {
        $blocks[] = $marker . $part;
    }

    return $blocks;
}

function parseSourceHtml(string $html, string $title): array
{
    $blocks = extractTermBlocks($html);

    $data = [];
    $missingMeaning = [];
    $missingWord = [];

    foreach ($blocks as $index => $block) {
        $word = extractTermText($block, 'en');
        $meaning = extractTermText($block, 'vi');

        if ($word === null && $meaning === null) {
            continue;
        }

        if ($word === null) {
            $missingWord[] = [
                'index' => $index + 1,
                'meaning' => $meaning
            ];
            continue;
        }

        if ($meaning === null) {
            $missingMeaning[] = [
                'index' => $index + 1,
                'word' => $word
            ];
        }

        $data[] = [
            'word' => $word,
            'meaning' => $meaning,
            'source_title' => $title
        ];
    }

    return [
        'data' => $data,
        'missing_meaning' => $missingMeaning,
        'missing_word' => $missingWord,
        'block_count' => count($blocks)
    ];
}

function loadExistingJson(string $jsonFile): array
{
    if (!is_file($jsonFile)) {
        return [];
    }

    $content = file_get_contents($jsonFile);

    if ($content === false || trim($content) === '') {
        return [];
    }

    $decoded = json_decode($content, true);

    if (!is_array($decoded)) {
        echo "Canh bao: File JSON cu khong hop le, se tao lai file moi.\n";
        return [];
    }

    // Trường hợp vocab.json có dạng {"data": [...]}
    if (isset($decoded['data']) && is_array($decoded['data'])) {
        return $decoded['data'];
    }

    // Trường hợp vocab.json là mảng trực tiếp: [...]
    if (array_is_list($decoded)) {
        return $decoded;
    }

    return [];
}

function buildExistingWordMap(array $items): array
{
    $map = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        if (!isset($item['word'])) {
            continue;
        }

        $key = normalizeWord((string)$item['word']);

        if ($key === '') {
            continue;
        }

        $map[$key] = true;
    }

    return $map;
}

function saveJson(string $jsonFile, array $output): void
{
    $json = json_encode(
        $output,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        echo "Loi json_encode: " . json_last_error_msg() . "\n";
        exit(1);
    }

    file_put_contents($jsonFile, $json);

    echo $json . "\n";
    echo "\nDa luu vao file: " . $jsonFile . "\n";
}

// =======================
// MAIN
// =======================

if ($argc < 2) {
    echo "Cach dung:\n";
    echo "php getvoca.php source.html\n";
    echo "php getvoca.php source.html vocab.json\n";
    exit(1);
}

$sourceFile = $argv[1];
$jsonFile = $argv[2] ?? 'vocab.json';

if (!is_file($sourceFile)) {
    echo "Loi: Khong tim thay file source HTML: $sourceFile\n";
    exit(1);
}

$html = file_get_contents($sourceFile);

if ($html === false || trim($html) === '') {
    echo "Loi: File source HTML rong hoac khong doc duoc.\n";
    exit(1);
}

$title = extractTitle($html);
$parsed = parseSourceHtml($html, $title);

$oldData = loadExistingJson($jsonFile);
$existingWordMap = buildExistingWordMap($oldData);

$added = [];
$skipped = [];

foreach ($parsed['data'] as $item) {
    $word = $item['word'] ?? '';
    $key = normalizeWord((string)$word);

    if ($key === '') {
        continue;
    }

    if (isset($existingWordMap[$key])) {
        $skipped[] = [
            'word' => $item['word'],
            'reason' => 'already_exists'
        ];
        continue;
    }

    $oldData[] = $item;
    $existingWordMap[$key] = true;
    $added[] = $item;
}

$countEn = 0;
$countVi = 0;

foreach ($oldData as $item) {
    if (!empty($item['word'])) {
        $countEn++;
    }

    if (!empty($item['meaning'])) {
        $countVi++;
    }
}

$output = [
    'ok' => true,
    'updated_at' => date('c'),
    'last_source_file' => basename($sourceFile),
    'last_source_title' => $title,
    'total' => count($oldData),
    'count_en' => $countEn,
    'count_vi' => $countVi,
    'last_run' => [
        'found_in_source' => count($parsed['data']),
        'added' => count($added),
        'skipped_existing' => count($skipped),
        'missing_meaning' => $parsed['missing_meaning'],
        'missing_word' => $parsed['missing_word']
    ],
    'data' => $oldData
];

saveJson($jsonFile, $output);