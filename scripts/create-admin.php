<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Env;

/**
 * Uso: php scripts/create-admin.php
 *
 * Crea un administrador activo si el correo no existe.
 * Requiere PHP y MySQL local; no se ejecuta desde el navegador.
 */

require dirname(__DIR__) . '/src/autoload.php';

$root = dirname(__DIR__);

Env::load($root . '/.env');

Config::load([
    'database' => require $root . '/config/database.php',
    'paths' => ['storage' => $root . '/storage'],
]);

$email = trim((string) readline('Correo del administrador: '));
$name = trim((string) readline('Nombre completo: '));
$password = (string) readline('Contraseña (mínimo 10 caracteres): ');
$confirmation = (string) readline('Repite la contraseña: ');

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "El correo no es válido.\n");
    exit(1);
}

if ($name === '') {
    fwrite(STDERR, "El nombre es obligatorio.\n");
    exit(1);
}

if (mb_strlen($password) < 10) {
    fwrite(STDERR, "La contraseña debe tener al menos 10 caracteres.\n");
    exit(1);
}

if (!hash_equals($password, $confirmation)) {
    fwrite(STDERR, "Las contraseñas no coinciden.\n");
    exit(1);
}

$config = (array) Config::get('database', []);
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['database'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => mb_strtolower($email)]);

    if ($statement->fetch() !== false) {
        fwrite(STDERR, "Ya existe un usuario con ese correo.\n");
        exit(1);
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, is_active)
         VALUES (:name, :email, :hash, :role, 1)'
    );
    $insert->execute([
        'name' => $name,
        'email' => mb_strtolower($email),
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
    ]);

    fwrite(STDOUT, "Administrador creado correctamente.\n");
} catch (PDOException $exception) {
    fwrite(STDERR, "No se pudo crear el administrador. Revisa la conexión y el esquema.\n");
    exit(1);
}
