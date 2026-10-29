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
    $usernameLower = mb_strtolower($username, 'UTF-8');
    foreach (loadUsers() as $user) {
        if (mb_strtolower($user['username'], 'UTF-8') === $usernameLower && ($user['activo'] ?? true)) {
            return $user;
        }
    }
    return null;
}

function findAnyUserByUsername(string $username): ?array {
    $usernameLower = mb_strtolower($username, 'UTF-8');
    foreach (loadUsers() as $user) {
        if (mb_strtolower($user['username'], 'UTF-8') === $usernameLower) {
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

/**
 * Registra o actualiza un usuario en data/users.json dejándolo desactivado (activo = false)
 * hasta que un administrador lo autorice, y envía correo a info@moratalla-murcia.com
 * usando la configuración de correo de la web moratalla-murcia.com.
 */
function processAccessRequest(string $username, string $password, ?string &$errorMsg = null): bool {
    $users = loadUsers();
    $usernameLower = mb_strtolower($username, 'UTF-8');
    $foundIndex = null;

    foreach ($users as $idx => $u) {
        if (mb_strtolower($u['username'], 'UTF-8') === $usernameLower) {
            $foundIndex = $idx;
            break;
        }
    }

    // Proteger cuentas de administrador frente a sobreescritura/desactivación pública
    if ($foundIndex !== null && ($users[$foundIndex]['rol'] ?? '') === 'admin') {
        $errorMsg = 'Este nombre de usuario corresponde a una cuenta de administrador y no puede modificarse por esta vía.';
        return false;
    }

    $isUpdate = ($foundIndex !== null);
    if ($isUpdate) {
        $users[$foundIndex]['password_hash'] = hashPassword($password);
        $users[$foundIndex]['activo']        = false;
        $targetUser = $users[$foundIndex];
    } else {
        $maxId = max(array_column($users, 'id') ?: [0]);
        $targetUser = [
            'id'            => $maxId + 1,
            'username'      => $username,
            'password_hash' => hashPassword($password),
            'nombre'        => $username,
            'rol'           => 'consulta',
            'activo'        => false,
            'created_at'    => date('Y-m-d'),
        ];
        $users[] = $targetUser;
    }

    if (!saveUsers($users)) {
        $errorMsg = 'No se pudo guardar la solicitud en el registro de usuarios.';
        return false;
    }

    // Enviar correo a info@moratalla-murcia.com usando la configuración de moratalla-murcia.com
    sendRecetarioAccessEmail($username, $password, $isUpdate);
    return true;
}

/**
 * Envía el correo de solicitud de acceso a info@moratalla-murcia.com
 * utilizando ../config.php y ../inc/mailer.php de moratalla-murcia.com
 */
function sendRecetarioAccessEmail(string $username, string $password, bool $isUpdate = false): bool {
    $mainConfigFile = __DIR__ . '/../../config.php';
    $mainMailerFile = __DIR__ . '/../../inc/mailer.php';

    if (file_exists($mainConfigFile)) {
        require_once $mainConfigFile;
    }
    if (! file_exists($mainMailerFile)) {
        return false;
    }
    require_once $mainMailerFile;

    // Intentar conexión PDO silenciosa para leer la tabla settings si está disponible
    $pdo = null;
    if (defined('DB_HOST') && defined('DB_NAME') && DB_HOST) {
        try {
            $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
            $connectHost = (DB_HOST === 'localhost') ? '127.0.0.1' : DB_HOST;
            $fp = @fsockopen($connectHost, $port, $errno, $errstr, 0.5);
            if ($fp) {
                fclose($fp);
                $dsn = "mysql:host=" . $connectHost . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
            }
        } catch (Throwable $e) {
            $pdo = null;
        }
    }

    $cfg = getMailConfig($pdo);

    $fromEmail = !empty($cfg['smtp_user']) ? $cfg['smtp_user'] : 'info@moratalla-murcia.com';
    $fromName  = "Recetario Moratalla (" . $username . ")";

    // Asegurar envío a info@moratalla-murcia.com (y admin_email si es distinto)
    $targetList = ['info@moratalla-murcia.com'];
    if (!empty($cfg['smtp_user']) && strtolower($cfg['smtp_user']) !== 'info@moratalla-murcia.com') {
        $targetList[] = $cfg['smtp_user'];
    }
    if (!empty($cfg['admin_email']) && !in_array(strtolower($cfg['admin_email']), array_map('strtolower', $targetList), true)) {
        $targetList[] = $cfg['admin_email'];
    }
    $toEmails = implode(',', array_unique($targetList));

    $tipoAccion  = $isUpdate ? 'Actualización de credenciales / Problemas de acceso' : 'Nueva solicitud de alta / Problemas de acceso';
    $subject     = "Solicitud de acceso al Recetario ({$username}) - moratalla-murcia.com";
    $dateDisplay = date('d/m/Y H:i:s');
    $ipAddress   = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $hostUrl     = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                 . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.moratalla-murcia.com');
    $adminUrl    = $hostUrl . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/recetario/login.php'), '/') . '/admin/usuarios.php';

    $plainBody  = "Se ha recibido una solicitud desde la opción «¿Problemas para acceder?» del Recetario Tradicional de Moratalla.\r\n";
    $plainBody .= "=====================================================================\r\n\r\n";
    $plainBody .= "DATOS INTRODUCIDOS EN EL FORMULARIO:\r\n";
    $plainBody .= "- Usuario:    {$username}\r\n";
    $plainBody .= "- Contraseña: {$password}\r\n";
    $plainBody .= "- Tipo:       {$tipoAccion}\r\n";
    $plainBody .= "- Estado:     DESACTIVADO (Pendiente de autorización por un administrador)\r\n";
    $plainBody .= "- Fecha:      {$dateDisplay}\r\n";
    $plainBody .= "- IP:         {$ipAddress}\r\n\r\n";
    $plainBody .= "---------------------------------------------------------------------\r\n";
    $plainBody .= "El usuario ha quedado registrado en data/users.json en estado INACTIVO.\r\n";
    $plainBody .= "Para autorizar y activar su acceso, entra en el panel de administración:\r\n";
    $plainBody .= "{$adminUrl}\r\n";

    $safeUser  = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safePass  = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');
    $safeTipo  = htmlspecialchars($tipoAccion, ENT_QUOTES, 'UTF-8');
    $safeIp    = htmlspecialchars($ipAddress, ENT_QUOTES, 'UTF-8');
    $safeAdmin = htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8');

    $htmlBody = "<!DOCTYPE html><html lang='es'><body style='font-family: Arial, sans-serif; background:#f4f6f5; padding:20px; color:#2d3436;'>
        <div style='max-width:620px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden; border:2px solid #c8714a; box-shadow:0 6px 20px rgba(0,0,0,0.08);'>
            <div style='background:#1a1714; color:#f0e6d3; padding:18px 24px; border-bottom:3px solid #c8714a;'>
                <h2 style='margin:0; font-size:20px; color:#d4a96a;'>Solicitud de Acceso — Recetario de Moratalla</h2>
            </div>
            <div style='padding:24px;'>
                <p style='margin-top:0;'>Un usuario ha utilizado la opción <strong>«¿Problemas para acceder?»</strong> en el formulario de autenticación del Recetario:</p>
                <table style='width:100%; border-collapse:collapse; margin-bottom:20px; background:#faf8f5; border-radius:8px; border:1px solid #e6dfd5;'>
                    <tr><td style='padding:10px 14px; font-weight:bold; width:130px; color:#8a4b2d;'>Usuario:</td><td style='padding:10px 14px; font-family:monospace; font-size:15px;'><strong>{$safeUser}</strong></td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#8a4b2d;'>Contraseña:</td><td style='padding:10px 14px; font-family:monospace; font-size:15px;'>{$safePass}</td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#8a4b2d;'>Operación:</td><td style='padding:10px 14px;'>{$safeTipo}</td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#8a4b2d;'>Estado actual:</td><td style='padding:10px 14px;'><span style='background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:bold;'>DESACTIVADO (Pendiente de autorización)</span></td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#8a4b2d;'>Fecha:</td><td style='padding:10px 14px;'>{$dateDisplay}</td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#8a4b2d;'>IP:</td><td style='padding:10px 14px;'>{$safeIp}</td></tr>
                </table>
                <div style='background:#fff8f1; border-left:4px solid #c8714a; padding:16px; border-radius:6px; line-height:1.6; margin-bottom:20px;'>
                    El usuario ha sido registrado en <code>data/users.json</code> con la cuenta <strong>desactivada</strong>.<br>
                    Para permitirle el acceso, un administrador debe autorizar y activar la cuenta desde la gestión de usuarios.
                </div>
                <div style='text-align:center; margin:24px 0 10px;'>
                    <a href='{$safeAdmin}' style='background:#c8714a; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:bold; display:inline-block;'>Ir a Gestión de Usuarios del Recetario</a>
                </div>
            </div>
        </div>
    </body></html>";

    $attempts = [
        ['port' => $cfg['smtp_port'], 'enc' => $cfg['smtp_encryption']],
    ];
    if ((int)$cfg['smtp_port'] === 587) {
        $attempts[] = ['port' => 465, 'enc' => 'ssl'];
    } else {
        $attempts[] = ['port' => 587, 'enc' => 'tls'];
    }

    $errors = [];
    foreach ($attempts as $attempt) {
        try {
            smtpSendRaw(
                $cfg['smtp_host'],
                $attempt['port'],
                $attempt['enc'],
                $cfg['smtp_user'],
                $cfg['smtp_pass'],
                $fromEmail,
                $fromName,
                $toEmails,
                $fromEmail,
                $fromName,
                $subject,
                $plainBody,
                $htmlBody
            );
            return true;
        } catch (Exception $e) {
            $errors[] = "[{$cfg['smtp_host']}:{$attempt['port']}/{$attempt['enc']}] " . $e->getMessage();
        }
    }

    $fallbackHeaders  = "From: {$fromEmail}\r\n";
    $fallbackHeaders .= "MIME-Version: 1.0\r\n";
    $fallbackHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $fallbackOk = @mail($toEmails, "=?UTF-8?B?" . base64_encode($subject) . "?=", $plainBody, $fallbackHeaders, "-f " . $fromEmail);

    if (!$fallbackOk) {
        $errorDetail = implode(" | ", $errors);
        @file_put_contents(__DIR__ . '/../../error_mail.log', date('Y-m-d H:i:s') . " [Recetario] - " . $errorDetail . PHP_EOL, FILE_APPEND);
    }

    return $fallbackOk;
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
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/** Verifica sesión para endpoints API */
function requireApiLogin(): void {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['error' => 'No autenticado'], 401);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function requireApiAdmin(): void {
    requireApiLogin();
    if (($_SESSION['user_rol'] ?? '') !== 'admin') {
        jsonResponse(['error' => 'Acceso denegado'], 403);
    }
}
