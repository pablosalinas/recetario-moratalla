<?php
$ch = curl_init('http://localhost/Moratalla-Murcia-2026/recetario/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['username' => 'admin', 'password' => 'recetario2026']);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo "Login code: " . $info['http_code'] . "\n";
curl_close($ch);

$ch = curl_init('http://localhost/Moratalla-Murcia-2026/recetario/api/usuarios.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$payload = json_encode(['username' => 'test_web', 'password' => '123456', 'nombre' => 'Test Web', 'rol' => 'consulta']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
$res = curl_exec($ch);
echo "Create response: $res\n";
curl_close($ch);

$ch = curl_init('http://localhost/Moratalla-Murcia-2026/recetario/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['username' => 'test_web', 'password' => '123456']);
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie2.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie2.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo "New user login code: " . $info['http_code'] . "\n";
curl_close($ch);
