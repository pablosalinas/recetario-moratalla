<?php
/**
 * auth.php — Funciones de autenticación y gestión de sesión
 * Almacenamiento: data/users.json (sin base de datos)
 */

define('USERS_FILE', __DIR__ . '/../data/users.json');
define('SESSION_LIFETIME', 3600 * 8); // 8 horas

// ─── Iniciar sesión segura ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Guards ──────────────────────────────────────────────────────────────────

/** Redirige a login si no hay sesión activa */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/admin');
        header('Location: ' . $base . '/login.php');
        exit;
    }
}

/** Redirige al recetario si el usuario no es admin */
function requireAdmin(): void {
    requireLogin();
    if (($_SESSION['user_rol'] ?? '') !== 'admin') {
        header('Location: ../index.php');
        exit;
    }
}

// ─── Usuario en sesión ───────────────────────────────────────────────────────

function getCurrentUser(): array {
    return [
        'id'       => $_SESSION['user_id']     ?? null,
        'username' => $_SESSION['user_username'] ?? '',
        'nombre'   => $_SESSION['user_nombre']  ?? '',
        'rol'      => $_SESSION['user_rol']     ?? 'consulta',
    ];
}

function isAdmin(): bool {
    return ($_SESSION['user_rol'] ?? '') === 'admin';
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// ─── Operaciones con users.json ──────────────────────────────────────────────

function loadUsers(): array {
    if (!file_exists(USERS_FILE)) return [];
    $json = file_get_contents(USERS_FILE);
    return json_decode($json, true) ?? [];
}

function saveUsers(array $users): bool {
    return file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

function findUserByUsername(string $username): ?array {
    foreach (loadUsers() as $user) {
        if ($user['username'] === $username && ($user['activo'] ?? true)) {
            return $user;
        }
    }
    return null;
}

function findUserById(int $id): ?array {
    foreach (loadUsers() as $user) {
        if ($user['id'] === $id) return $user;
    }
    return null;
}

// ─── Autenticación ───────────────────────────────────────────────────────────

function attemptLogin(string $username, string $password): bool {
    $user = findUserByUsername($username);
    if (!$user) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_nombre']   = $user['nombre'];
    $_SESSION['user_rol']      = $user['rol'];
    session_regenerate_id(true);
    return true;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ─── Hash de contraseña ───────────────────────────────────────────────────────

function hashPassword(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Genera slug ID desde el nombre de la receta */
function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(['á','é','í','ó','ú','ü','ñ','à','è','ì','ò','ù'],
                        ['a','e','i','o','u','u','n','a','e','i','o','u'], $text);
    $text = preg_replace('/[^a-z0-9\s_]/', '', $text);
    $text = preg_replace('/[\s]+/', '_', trim($text));
    return $text;
}

/** Responde JSON y termina */
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Verifica sesión para endpoints API */
function requireApiLogin(): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'No autenticado'], 401);
    }
}

function requireApiAdmin(): void {
    requireApiLogin();
    if (($_SESSION['user_rol'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
}
