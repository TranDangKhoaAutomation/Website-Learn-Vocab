<?php
require_once __DIR__ . '/../config/database.php';

function audit_log(PDO $pdo, string $action, string $targetType, ?int $targetId = null, mixed $oldValue = null, mixed $newValue = null): void
{
    try {
        $stmt = $pdo->prepare('
            INSERT INTO audit_logs (user_id, action, target_type, target_id, old_value, new_value, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            current_user_id() ?: null,
            $action,
            $targetType,
            $targetId,
            $oldValue === null ? null : json_encode($oldValue, JSON_UNESCAPED_UNICODE),
            $newValue === null ? null : json_encode($newValue, JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable $exception) {
        // Audit failure must not break the admin action.
    }
}
