<?php
require_once __DIR__ . '/../config/database.php';

function get_setting(string $key, mixed $default = null): mixed
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        global $pdo;
        if ($pdo) {
            try {
                $stmt = $pdo->query('SELECT setting_key, setting_value, setting_type FROM site_settings');
                foreach ($stmt->fetchAll() as $row) {
                    $value = $row['setting_value'];
                    if ($row['setting_type'] === 'boolean') {
                        $value = in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
                    } elseif ($row['setting_type'] === 'number') {
                        $value = is_numeric($value) ? (int) $value : 0;
                    } elseif ($row['setting_type'] === 'json') {
                        $decoded = json_decode((string) $value, true);
                        $value = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    }
                    $settings[$row['setting_key']] = $value;
                }
            } catch (Throwable $exception) {
                $settings = [];
            }
        }
    }

    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function set_setting(PDO $pdo, string $key, mixed $value, string $type = 'text'): void
{
    if ($type === 'boolean') {
        $value = $value ? '1' : '0';
    } elseif ($type === 'json') {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
    } else {
        $value = (string) $value;
    }

    $stmt = $pdo->prepare('
        INSERT INTO site_settings (setting_key, setting_value, setting_type, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), updated_at = NOW()
    ');
    $stmt->execute([$key, $value, $type]);
}
