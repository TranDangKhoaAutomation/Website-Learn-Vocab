<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/set_access.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json_response(['success' => false, 'message' => 'Method không hợp lệ.'], 405);
}

if (!$pdo) {
    app_json_response(['success' => false, 'message' => 'Không thể kết nối database.'], 500);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = [];
    }
} else {
    $payload = $_POST;
}

$action = $payload['action'] ?? 'progress';
$userId = current_user_id();
$setId = max(0, (int) ($payload['set_id'] ?? 0));

if ($setId <= 0) {
    app_json_response(['success' => false, 'message' => 'Thiếu bộ từ.'], 422);
}

$learningSet = get_learning_set($pdo, $setId, $userId);
if (!$learningSet) {
    app_json_response(['success' => false, 'message' => 'Bạn không có quyền với bộ từ này.'], 403);
}

if ($action === 'test_result') {
    $score = max(0, (int) ($payload['score'] ?? 0));
    $totalQuestions = max(1, (int) ($payload['total_questions'] ?? 1));
    $mode = trim($payload['mode'] ?? 'test');

    $stmt = $pdo->prepare('
        INSERT INTO test_results (user_id, set_id, score, total_questions, mode, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$userId, $setId, min($score, $totalQuestions), $totalQuestions, $mode]);

    app_json_response(['success' => true, 'message' => 'Đã lưu kết quả test.']);
}

$cardId = max(0, (int) ($payload['card_id'] ?? 0));
$mode = trim($payload['mode'] ?? 'learn');
$result = $payload['result'] ?? '';
$lastAnswer = trim((string) ($payload['last_answer'] ?? ''));

if ($cardId <= 0 || !in_array($result, ['correct', 'wrong'], true)) {
    app_json_response(['success' => false, 'message' => 'Dữ liệu tiến độ không hợp lệ.'], 422);
}

$stmt = $pdo->prepare('SELECT id FROM flashcards WHERE id = ? AND set_id = ? LIMIT 1');
$stmt->execute([$cardId, $setId]);
if (!$stmt->fetch()) {
    app_json_response(['success' => false, 'message' => 'Flashcard không thuộc bộ từ này.'], 403);
}

$correctIncrement = $result === 'correct' ? 1 : 0;
$wrongIncrement = $result === 'wrong' ? 1 : 0;

$stmt = $pdo->prepare('
    INSERT INTO user_progress (user_id, set_id, card_id, mode, correct_count, wrong_count, last_answer, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        correct_count = correct_count + VALUES(correct_count),
        wrong_count = wrong_count + VALUES(wrong_count),
        last_answer = VALUES(last_answer),
        updated_at = NOW()
');
$stmt->execute([$userId, $setId, $cardId, $mode, $correctIncrement, $wrongIncrement, $lastAnswer]);

app_json_response(['success' => true, 'message' => 'Đã lưu tiến độ.']);
