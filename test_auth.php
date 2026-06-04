<?php
require 'inc/auth.php';

$users = loadUsers();
echo "Loaded users: " . count($users) . "\n";

$plain = '123456';
$hash = hashPassword($plain);
echo "Hash: $hash\n";
echo "Verify direct: " . (password_verify($plain, $hash) ? 'true' : 'false') . "\n";

$json = json_encode(['hash' => $hash], JSON_UNESCAPED_UNICODE);
echo "JSON: $json\n";
$decoded = json_decode($json, true);
echo "Verify decoded: " . (password_verify($plain, $decoded['hash']) ? 'true' : 'false') . "\n";

// Let's test the active user logic
$username = 'admin';
$user = findUserByUsername($username);
if ($user) {
    echo "Found admin: {$user['username']}, active: " . ($user['activo'] ? 'yes' : 'no') . "\n";
} else {
    echo "Admin not found\n";
}

// simulate what the API does for creating a user
$newUser = [
    'id' => 999,
    'username' => 'testuser',
    'password_hash' => hashPassword('mypass'),
    'nombre' => 'Test User',
    'rol' => 'consulta',
    'activo' => true,
    'created_at' => date('Y-m-d')
];
$users[] = $newUser;
saveUsers($users);
echo "Saved users\n";

$loaded = loadUsers();
$testUser = null;
foreach ($loaded as $u) {
    if ($u['username'] === 'testuser') $testUser = $u;
}

if ($testUser) {
    echo "Found testuser! Password verify: " . (password_verify('mypass', $testUser['password_hash']) ? 'true' : 'false') . "\n";
} else {
    echo "Test user not found after save\n";
}
