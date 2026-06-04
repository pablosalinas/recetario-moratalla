<?php
/**
 * Genera el archivo users.json inicial con usuario admin por defecto
 * Ejecutar UNA VEZ: http://localhost/recetario/init_users.php
 * Borrar después de ejecutar.
 */

require_once __DIR__ . '/inc/auth.php';

if (file_exists(USERS_FILE)) {
    echo "<p>⚠️ El archivo users.json ya existe. No se ha sobrescrito.</p>";
    echo "<p>Contenido actual:</p><pre>";
    $users = loadUsers();
    // No mostrar hashes en producción
    foreach ($users as &$u) unset($u['password_hash']);
    echo json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    exit;
}

$initialUsers = [
    [
        'id'            => 1,
        'username'      => 'admin',
        'password_hash' => hashPassword('recetario2026'),
        'nombre'        => 'Administrador',
        'rol'           => 'admin',
        'activo'        => true,
        'created_at'    => date('Y-m-d'),
    ]
];

if (saveUsers($initialUsers)) {
    echo "<h2>✅ Usuarios inicializados correctamente</h2>";
    echo "<p><strong>Usuario:</strong> admin</p>";
    echo "<p><strong>Contraseña:</strong> recetario2026</p>";
    echo "<p style='color:red'><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo (init_users.php) después de inicializar.</p>";
    echo "<p><a href='login.php'>→ Ir al Login</a></p>";
} else {
    echo "<h2>❌ Error al crear users.json</h2>";
    echo "<p>Verifica que la carpeta <code>data/</code> tiene permisos de escritura.</p>";
}
