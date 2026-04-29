<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'English Learning App');
define('BASE_URL', '/');

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . app_url($path));
    exit;
}

function app_url($path = '')
{
    $path = ltrim((string) $path, '/');
    $query = '';

    if (($position = strpos($path, '?')) !== false) {
        $query = substr($path, $position);
        $path = substr($path, 0, $position);
    }

    $path = preg_replace('/\.php$/', '', $path);
    if (str_starts_with($path, 'pages/')) {
        $path = substr($path, 6);
    }

    return BASE_URL . $path . $query;
}

function current_user_id()
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

function current_user_name()
{
    return $_SESSION['user_name'] ?? 'User';
}

function current_user_role()
{
    return $_SESSION['user_role'] ?? 'user';
}

function set_flash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function old($key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old()
{
    unset($_SESSION['old']);
}

function set_old(array $data)
{
    $_SESSION['old'] = $data;
}

function is_active($path)
{
    $current = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $needle = '/' . ltrim($path, '/');
    return substr($current, -strlen($needle)) === $needle ? 'active' : '';
}

function app_json_response(array $payload, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}
