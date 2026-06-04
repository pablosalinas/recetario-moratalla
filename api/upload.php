<?php
/**
 * api/upload.php — Subida, optimización y marca de agua de imágenes
 */

require_once __DIR__ . '/../inc/auth.php';
requireApiAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Error al recibir el archivo']);
    exit;
}

$file = $_FILES['imagen'];
$tmpPath = $file['tmp_name'];
$mime = mime_content_type($tmpPath);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de imagen no soportado (solo JPG, PNG, WEBP)']);
    exit;
}

// Cargar imagen original
$sourceImage = null;
switch ($mime) {
    case 'image/jpeg':
        $sourceImage = @imagecreatefromjpeg($tmpPath);
        break;
    case 'image/png':
        $sourceImage = @imagecreatefrompng($tmpPath);
        break;
    case 'image/webp':
        $sourceImage = @imagecreatefromwebp($tmpPath);
        break;
}

if (!$sourceImage) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar la imagen']);
    exit;
}

// Dimensiones originales
$width = imagesx($sourceImage);
$height = imagesy($sourceImage);

// Optimización: Redimensionar si es muy grande (max 1200px de ancho)
$maxWidth = 1200;
$maxHeight = 1200;

if ($width > $maxWidth || $height > $maxHeight) {
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
} else {
    $newWidth = $width;
    $newHeight = $height;
}

$optimizedImage = imagecreatetruecolor($newWidth, $newHeight);

// Preservar transparencia para PNG/WEBP
if ($mime === 'image/png' || $mime === 'image/webp') {
    imagealphablending($optimizedImage, false);
    imagesavealpha($optimizedImage, true);
    $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
    imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
}

// Redimensionar
imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

// ── Añadir Marca de Agua ──────────────────────────────────────────────
$watermarkText = 'www.moratalla-murcia.com';
$fontSize = 5; // Tamaño de fuente interna de GD (1-5)

// Calcular posición (Abajo a la derecha con margen)
$margin = 15;
$charWidth = imagefontwidth($fontSize);
$charHeight = imagefontheight($fontSize);
$textWidth = strlen($watermarkText) * $charWidth;
$textHeight = $charHeight;

$x = $newWidth - $textWidth - $margin;
$y = $newHeight - $textHeight - $margin;

// Asegurarse de que el texto quepa, si la imagen es muy pequeña lo centramos
if ($x < 0) $x = 10;

// Colores para la marca de agua (Blanco con sombra negra para que sea visible en cualquier fondo)
$white = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 30); // 30 = 약간 transparente
$black = imagecolorallocatealpha($optimizedImage, 0, 0, 0, 50);

// Dibujar sombra
imagestring($optimizedImage, $fontSize, $x + 2, $y + 2, $watermarkText, $black);
// Dibujar texto principal
imagestring($optimizedImage, $fontSize, $x, $y, $watermarkText, $white);

// ── Guardar Imagen ──────────────────────────────────────────────────
$uploadDir = __DIR__ . '/../img/recetas/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'receta_' . time() . '_' . rand(1000, 9999) . '.jpg';
$destination = $uploadDir . $filename;

// Siempre guardamos como JPG optimizado por consistencia y tamaño, excepto si es PNG y queremos transparencia
// En este caso, para fotos de recetas, JPG calidad 80 es ideal.
$saved = imagejpeg($optimizedImage, $destination, 80);

imagedestroy($sourceImage);
imagedestroy($optimizedImage);

if ($saved) {
    echo json_encode(['ok' => true, 'filename' => 'img/recetas/' . $filename]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar la imagen procesada']);
}
