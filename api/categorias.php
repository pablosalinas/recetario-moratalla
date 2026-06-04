<?php
/**
 * api/categorias.php — CRUD API para categorias
 */

require_once __DIR__ . '/../inc/auth.php';

define('CATEGORIAS_FILE', __DIR__ . '/../data/categorias.json');

function loadCategorias(): array {
    if (!file_exists(CATEGORIAS_FILE)) {
        // Inicializar por defecto
        $defaults = [
            ['id' => 'mojos-y-entrantes', 'nombre' => 'Mojos y Entrantes', 'visible' => true, 'imagen' => 'cat-entradas.png'],
            ['id' => 'sopas-y-caldos', 'nombre' => 'Sopas y Caldos', 'visible' => true, 'imagen' => 'cat-sopas.png'],
            ['id' => 'arroces-y-harinas', 'nombre' => 'Arroces y Harinas', 'visible' => true, 'imagen' => 'cat-arroces.png'],
            ['id' => 'potajes-y-guisos', 'nombre' => 'Potajes y Guisos', 'visible' => true, 'imagen' => 'cat-potajes.png'],
            ['id' => 'carnes', 'nombre' => 'Carnes', 'visible' => true, 'imagen' => 'cat-carnes.png'],
            ['id' => 'fritos-y-pescados', 'nombre' => 'Fritos y Pescados', 'visible' => true, 'imagen' => 'cat-pescados.png'],
            ['id' => 'reposteria', 'nombre' => 'Repostería', 'visible' => true, 'imagen' => 'cat-reposteria.png'],
        ];
        saveCategorias($defaults);
        return $defaults;
    }
    return json_decode(file_get_contents(CATEGORIAS_FILE), true) ?? [];
}

function saveCategorias(array $data): bool {
    return file_put_contents(CATEGORIAS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Listar ───────────────────────────────────────────────────────
if ($method === 'GET') {
    jsonResponse(loadCategorias());
}

// ── POST: Crear ────────────────────────────────────────────────────────
if ($method === 'POST') {
    requireApiAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['nombre'])) {
        jsonResponse(['error' => 'Nombre es obligatorio'], 400);
    }

    $items = loadCategorias();
    
    $id = slugify($body['nombre']);
    if (in_array($id, array_column($items, 'id'))) {
        $id .= '_' . time();
    }

    $nueva = [
        'id'      => $id,
        'nombre'  => trim($body['nombre']),
        'visible' => isset($body['visible']) ? (bool)$body['visible'] : true,
        'imagen'  => trim($body['imagen'] ?? ''),
    ];

    $items[] = $nueva;
    saveCategorias($items);
    jsonResponse(['ok' => true, 'categoria' => $nueva], 201);
}

// ── PUT: Actualizar ────────────────────────────────────────────────────
if ($method === 'PUT') {
    requireApiAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['id'])) {
        jsonResponse(['error' => 'ID es obligatorio'], 400);
    }

    $items = loadCategorias();
    $found = false;
    foreach ($items as &$r) {
        if ($r['id'] === $body['id']) {
            $r['nombre']  = trim($body['nombre'] ?? $r['nombre']);
            if (isset($body['visible'])) $r['visible'] = (bool)$body['visible'];
            if (isset($body['imagen'])) $r['imagen'] = trim($body['imagen']);
            $found = true;
            $updated = $r;
            break;
        }
    }
    unset($r);

    if (!$found) jsonResponse(['error' => 'Categoría no encontrada'], 404);
    saveCategorias($items);
    jsonResponse(['ok' => true, 'categoria' => $updated]);
}

// ── DELETE: Eliminar ───────────────────────────────────────────────────
if ($method === 'DELETE') {
    requireApiAdmin();
    $id = $_GET['id'] ?? '';
    if (empty($id)) jsonResponse(['error' => 'ID es obligatorio'], 400);

    $items = loadCategorias();
    $newList = array_values(array_filter($items, fn($r) => $r['id'] !== $id));

    if (count($newList) === count($items)) {
        jsonResponse(['error' => 'Categoría no encontrada'], 404);
    }

    saveCategorias($newList);
    jsonResponse(['ok' => true, 'deleted' => $id]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
