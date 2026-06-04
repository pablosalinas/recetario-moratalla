<?php
/**
 * api/recetas.php — CRUD API para recetas
 * GET    → listar todas (requiere login)
 * POST   → crear nueva (requiere admin)
 * PUT    → actualizar (requiere admin)
 * DELETE → eliminar   (requiere admin)
 */

require_once __DIR__ . '/../inc/auth.php';

define('RECETAS_FILE', __DIR__ . '/../data/recetas.json');

function loadRecetas(): array {
    if (!file_exists(RECETAS_FILE)) return [];
    return json_decode(file_get_contents(RECETAS_FILE), true) ?? [];
}

function saveRecetas(array $data): bool {
    return file_put_contents(RECETAS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Listar recetas ───────────────────────────────────────────────────────
if ($method === 'GET') {
    requireApiLogin();
    jsonResponse(loadRecetas());
}

// ── POST: Crear receta ────────────────────────────────────────────────────────
if ($method === 'POST') {
    requireApiAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['nombre'])) {
        jsonResponse(['error' => 'Nombre es obligatorio'], 400);
    }

    $recetas = loadRecetas();
    
    // Generar ID único desde el nombre
    $baseId = slugify($body['nombre']);
    $id = $baseId;
    $suffix = 2;
    $existingIds = array_column($recetas, 'id');
    while (in_array($id, $existingIds)) {
        $id = $baseId . '_' . $suffix++;
    }

    $nueva = [
        'id'             => $id,
        'nombre'         => trim($body['nombre']),
        'categoria'      => $body['categoria'] ?? 'Sin categoría',
        'pagina'         => (int)($body['pagina'] ?? 0),
        'imagen_origen'  => $body['imagen_origen'] ?? '',
        'facilitador'    => $body['facilitador'] ?? '',
        'ingredientes'   => array_values(array_filter(array_map('trim', $body['ingredientes'] ?? []))),
        'instrucciones'  => trim($body['instrucciones'] ?? ''),
        'nota_historica' => trim($body['nota_historica'] ?? ''),
        'libro'          => $body['libro'] ?? 'Cocina Tradicional de Moratalla (1992)',
        'visible'        => isset($body['visible']) ? (bool)$body['visible'] : true,
        'imagenes'       => is_array($body['imagenes'] ?? null) ? $body['imagenes'] : [],
    ];

    $recetas[] = $nueva;
    saveRecetas($recetas);
    jsonResponse(['ok' => true, 'receta' => $nueva], 201);
}

// ── PUT: Actualizar receta ────────────────────────────────────────────────────
if ($method === 'PUT') {
    requireApiAdmin();
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body || empty($body['id'])) {
        jsonResponse(['error' => 'ID es obligatorio'], 400);
    }

    $recetas = loadRecetas();
    $found = false;
    foreach ($recetas as &$r) {
        if ($r['id'] === $body['id']) {
            $r['nombre']         = trim($body['nombre'] ?? $r['nombre']);
            $r['categoria']      = $body['categoria'] ?? $r['categoria'];
            $r['pagina']         = (int)($body['pagina'] ?? $r['pagina']);
            $r['imagen_origen']  = $body['imagen_origen'] ?? $r['imagen_origen'];
            $r['facilitador']    = $body['facilitador'] ?? $r['facilitador'];
            $r['ingredientes']   = array_values(array_filter(array_map('trim', $body['ingredientes'] ?? $r['ingredientes'])));
            $r['instrucciones']  = trim($body['instrucciones'] ?? $r['instrucciones']);
            $r['nota_historica'] = trim($body['nota_historica'] ?? $r['nota_historica']);
            $r['libro']          = $body['libro'] ?? $r['libro'];
            if (isset($body['visible'])) $r['visible'] = (bool)$body['visible'];
            if (isset($body['imagenes']) && is_array($body['imagenes'])) $r['imagenes'] = $body['imagenes'];
            $found = true;
            $updated = $r;
            break;
        }
    }
    unset($r);

    if (!$found) jsonResponse(['error' => 'Receta no encontrada'], 404);
    saveRecetas($recetas);
    jsonResponse(['ok' => true, 'receta' => $updated]);
}

// ── DELETE: Eliminar receta ───────────────────────────────────────────────────
if ($method === 'DELETE') {
    requireApiAdmin();
    $id = $_GET['id'] ?? '';
    if (empty($id)) jsonResponse(['error' => 'ID es obligatorio'], 400);

    $recetas = loadRecetas();
    $newList = array_values(array_filter($recetas, fn($r) => $r['id'] !== $id));

    if (count($newList) === count($recetas)) {
        jsonResponse(['error' => 'Receta no encontrada'], 404);
    }

    saveRecetas($newList);
    jsonResponse(['ok' => true, 'deleted' => $id]);
}

jsonResponse(['error' => 'Método no permitido'], 405);
