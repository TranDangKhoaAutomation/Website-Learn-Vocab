<?php
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user_id'])) {
    set_flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
    redirect('login.php');
}

require_once __DIR__ . '/../config/database.php';

if (!empty($pdo)) {
    $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $authUser = $stmt->fetch();

    if ($authUser) {
        $_SESSION['user_name'] = $authUser['name'];
        $_SESSION['user_email'] = $authUser['email'];
    } else {
        session_unset();
        session_destroy();
        redirect('login.php');
    }
}
