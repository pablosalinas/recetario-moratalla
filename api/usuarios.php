<?php
/**
 * api/usuarios.php — CRUD API para usuarios
 * GET    → listar (solo admin)
 * POST   → crear  (solo admin)
 * PUT    → editar (solo admin)
 * DELETE → borrar (solo admin, no puede borrarse a sí mismo)
 */

require_once __DIR__ . '/../inc/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Listar usuarios ─────────────────────────────────────────────────────
if ($method === 'GET') {
    requireApiAdmin();
    $users = array_map(function($u) {
        unset($u['password_hash']);
        return $u;
    }, loadUsers());
    jsonResponse(array_values($users));
}

// ── POST: Crear usuario ──────────────────────────────────────────────────────
if ($method === 'POST') {
    requireApiAdmin();
    $body = json_decode(file_get_contents('php://input'), true);

    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';
    $nombre   = trim($body['nombre'] ?? '');
    $rol      = in_array($body['rol'] ?? '', ['admin','consulta']) ? $body['rol'] : 'consulta';

    if (empty($username) || empty($password) || empty($nombre)) {
        jsonResponse(['error' => 'Usuario, nombre y contraseña son obligatorios'], 400);
    }

    if (findUserByUsername($username)) {
        jsonResponse(['error' => 'El nombre de usuario ya existe'], 409);
    }

    $users  = loadUsers();
    $maxId  = max(array_column($users, 'id') ?: [0]);
    $newUser = [
        'id'            => $maxId + 1,
        'username'      => $username,
        'password_hash' => hashPassword($password),
        'nombre'        => $nombre,
        'rol'           => $rol,
        'activo'        => true,
        'created_at'    => date('Y-m-d'),
    ];
    $users[] = $newUser;
    saveUsers($users);

    unset($newUser['password_hash']);
    jsonResponse(['ok' => true, 'usuario' => $newUser], 201);
}

// ── PUT: Editar usuario ──────────────────────────────────────────────────────
if ($method === 'PUT') {
    requireApiAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID es obligatorio'], 400);

    $users = loadUsers();
    $found = false;
    $me    = getCurrentUser();

    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            $u['nombre'] = trim($body['nombre'] ?? $u['nombre']);
            $u['activo'] = isset($body['activo']) ? (bool)$body['activo'] : $u['activo'];

            // No se puede cambiar el rol propio
            if ($id !== $me['id']) {
                $u['rol'] = in_array($body['rol'] ?? '', ['admin','consulta']) ? $body['rol'] : $u['rol'];
            }

            // Cambiar contraseña solo si se proporciona
            if (!empty($body['password'])) {
                $u['password_hash'] = hashPassword($body['password']);
            }

            // Cambiar username solo si es nuevo y único
            if (!empty($body['username']) && $body['username'] !== $u['username']) {
                if (findUserByUsername($body['username'])) {
                    jsonResponse(['error' => 'El nombre de usuario ya existe'], 409);
                }
                $u['username'] = $body['username'];
            }

            $found   = true;
            $updated = $u;
            break;
        }
    }
    unset($u);

    if (!$found) jsonResponse(['error' => 'Usuario no encontrado'], 404);
    saveUsers($users);
    unset($updated['password_hash']);
    jsonResponse(['ok' => true, 'usuario' => $updated]);
}

// ── DELETE: Eliminar usuario ─────────────────────────────────────────────────
if ($method === 'DELETE') {
    requireApiAdmin();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'ID es obligatorio'], 400);

    $me = getCurrentUser();
    if ($id === $me['id']) {
        jsonResponse(['error' => 'No puedes eliminar tu propio usuario'], 403);
    }

    $users   = loadUsers();
    $newList = array_values(array_filter($users, fn($u) => $u['id'] !== $id));

    if (count($newList) === count($users)) {
        jsonResponse(['error' => 'Usuario no encontrado'], 404);
    }

    saveUsers($newList);
    jsonResponse(['ok' => true, 'deleted' => $id]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
