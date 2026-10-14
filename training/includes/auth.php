<?php
// includes/auth.php - Session Management & Role Authentication

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function getAuthUser(): ?array {
    return $_SESSION['auth_user'] ?? null;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['auth_user']['id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(array $allowedRoles): void {
    requireLogin();
    $user = getAuthUser();
    if (!in_array($user['role'], $allowedRoles, true)) {
        header('HTTP/1.1 403 Forbidden');
        die('<h3>403 - Bạn không có quyền truy cập trang này.</h3><p><a href="index.php">Quay lại trang chủ</a></p>');
    }
}

function loginUser(string $employeeCode, string $password): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE employee_code = :code AND is_active = 1 LIMIT 1');
    $stmt->execute(['code' => trim($employeeCode)]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Mã nhân viên không tồn tại hoặc đã bị vô hiệu hóa.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Mật khẩu không chính xác.'];
    }

    // Set Session
    $_SESSION['auth_user'] = [
        'id'            => (int)$user['id'],
        'employee_code' => $user['employee_code'],
        'full_name'     => $user['full_name'],
        'role'          => $user['role'],
        'department'    => $user['department'],
        'line_station'  => $user['line_station']
    ];

    return ['success' => true, 'user' => $_SESSION['auth_user']];
}

function quickLoginById(int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['auth_user'] = [
            'id'            => (int)$user['id'],
            'employee_code' => $user['employee_code'],
            'full_name'     => $user['full_name'],
            'role'          => $user['role'],
            'department'    => $user['department'],
            'line_station'  => $user['line_station']
        ];
        return true;
    }
    return false;
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

