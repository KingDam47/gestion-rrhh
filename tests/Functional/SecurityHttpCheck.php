<?php

declare(strict_types=1);

/**
 * Verificación funcional HTTP de la fase 2.
 *
 * Cubre cabeceras de seguridad, limitador de login por correo/IP e
 * invalidación de sesiones tras un cambio de contraseña.
 *
 * Datos ficticios. La base debe estar restaurada con database/seed.sql antes de
 * ejecutar la prueba. El guion modifica la contraseña del administrador y la
 * restaura al terminar; aun así, se recomienda reimportar el seed después.
 *
 * Uso:
 *   & "$env:TEMP\opencode\php84\php.exe" tests\Functional\SecurityHttpCheck.php
 *
 * Variables opcionales:
 *   APP_URL      URL base (por defecto http://127.0.0.1:8099)
 *   DB_DSN       DSN PDO (por defecto MariaDB local en el puerto 3307)
 *   DB_USER      usuario de base (por defecto root)
 *   DB_PASSWORD  contraseña de base (vacía por defecto)
 */

error_reporting(E_ALL);

define('BASE_URL', getenv('APP_URL') ?: 'http://127.0.0.1:8099');
define('DB_DSN', getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3307;dbname=gestion_rrhh;charset=utf8mb4');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('THROTTLE_DIR', dirname(__DIR__, 2) . '/storage/rate-limits');

$baseHost = parse_url(BASE_URL, PHP_URL_HOST);
$dbHost = preg_match('/host=([^;]+)/', DB_DSN, $matches) === 1 ? $matches[1] : '';
$dbName = preg_match('/dbname=([^;]+)/', DB_DSN, $matches) === 1 ? $matches[1] : '';

if (
    !in_array($baseHost, ['127.0.0.1', 'localhost', '::1'], true)
    || !in_array($dbHost, ['127.0.0.1', 'localhost', '::1'], true)
    || $dbName !== 'gestion_rrhh'
) {
    fwrite(STDERR, "Seguridad: esta prueba solo puede ejecutarse contra el entorno local (127.0.0.1/localhost y dbname=gestion_rrhh).\n");
    exit(2);
}

final class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    public function location(): string
    {
        return (string) $this->header('location');
    }
}

final class HttpClient
{
    private string $cookie = '';

    public function __construct(private readonly string $baseUrl)
    {
    }

    /**
     * @param array<string, scalar|null> $data
     * @param list<string> $extraHeaders
     */
    public function request(string $method, string $path, array $data = [], array $extraHeaders = []): HttpResponse
    {
        $headers = array_merge([
            'Accept: text/html,application/xhtml+xml',
            'User-Agent: security-functional-check/1.0',
        ], $extraHeaders);

        if ($this->cookie !== '') {
            $headers[] = 'Cookie: ' . $this->cookie;
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
                'timeout' => 30,
            ],
        ];

        if ($method === 'POST') {
            $options['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            $options['http']['content'] = http_build_query($data);
        }

        $body = @file_get_contents($this->baseUrl . $path, false, stream_context_create($options));
        $rawHeaders = $http_response_header ?? [];

        $status = 0;
        $parsed = [];

        foreach ($rawHeaders as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches) === 1) {
                $status = (int) $matches[1];
                $parsed = [];
                continue;
            }

            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $parsed[strtolower(trim($name))][] = trim($value);
            }
        }

        foreach ($parsed['set-cookie'] ?? [] as $cookieLine) {
            if (preg_match('/^(gestion_rrhh_session)=([^;]*)/', $cookieLine, $matches) === 1) {
                $this->cookie = $matches[1] . '=' . $matches[2];
            }
        }

        return new HttpResponse($status, $parsed, $body === false ? '' : $body);
    }

    /**
     * @param list<string> $extraHeaders
     */
    public function get(string $path, array $extraHeaders = []): HttpResponse
    {
        return $this->request('GET', $path, [], $extraHeaders);
    }

    /**
     * @param array<string, scalar|null> $data
     */
    public function post(string $path, array $data): HttpResponse
    {
        return $this->request('POST', $path, $data);
    }
}

final class Checker
{
    private int $passed = 0;

    /** @var list<string> */
    private array $failures = [];

    public function section(string $title): void
    {
        echo "\n== " . $title . " ==\n";
    }

    public function ok(string $label, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [OK]   " . $label . "\n";

            return;
        }

        $this->failures[] = $label . ($detail === '' ? '' : ' — ' . $detail);
        echo "  [FAIL] " . $label . ($detail === '' ? '' : ' — ' . $detail) . "\n";
    }

    public function status(string $label, HttpResponse $response, int $expected): void
    {
        $this->ok($label, $response->status === $expected, 'status=' . $response->status);
    }

    public function location(string $label, HttpResponse $response, string $expected): void
    {
        $this->ok(
            $label,
            $response->location() === $expected,
            'location=' . $response->location()
        );
    }

    public function containsText(string $label, HttpResponse $response, string $needle): void
    {
        $body = preg_replace('/\s+/u', ' ', $response->body) ?? $response->body;
        $this->ok($label, str_contains($body, $needle), 'missing: ' . $needle);
    }

    public function notContains(string $label, HttpResponse $response, string $needle): void
    {
        $this->ok($label, !str_contains($response->body, $needle), 'found: ' . $needle);
    }

    public function summary(): int
    {
        echo "\n----------------------------------------\n";
        echo 'Comprobaciones: ' . ($this->passed + count($this->failures)) . "\n";
        echo 'Correctas: ' . $this->passed . "\n";
        echo 'Fallidas: ' . count($this->failures) . "\n";

        if ($this->failures !== []) {
            echo "\nFallos:\n";
            foreach ($this->failures as $failure) {
                echo ' - ' . $failure . "\n";
            }
        }

        return $this->failures === [] ? 0 : 1;
    }
}

function csrfToken(string $html): string
{
    if (preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $matches) !== 1) {
        throw new RuntimeException('No se encontró el token CSRF en la página.');
    }

    return $matches[1];
}

function login(HttpClient $client, string $email, string $password = 'password123'): HttpResponse
{
    $page = $client->get('/login');

    return $client->post('/login', [
        '_token' => csrfToken($page->body),
        'email' => $email,
        'password' => $password,
    ]);
}

function db(): PDO
{
    return new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/**
 * @param array<string, scalar|null> $parameters
 */
function dbValue(PDO $pdo, string $sql, array $parameters = []): mixed
{
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchColumn();
}

function clearThrottle(): void
{
    foreach (glob(THROTTLE_DIR . '/*.json') ?: [] as $file) {
        @unlink($file);
    }
}

function restoreAdminPassword(PDO $pdo): void
{
    $statement = $pdo->prepare(
        'UPDATE users
         SET password_hash = :hash, password_changed_at = NULL
         WHERE email = :email'
    );
    $statement->execute([
        'hash' => password_hash('password123', PASSWORD_DEFAULT),
        'email' => 'admin@gestion-rrhh.test',
    ]);
}

$t = new Checker();
$pdo = db();
$exitCode = 1;

clearThrottle();

try {
    $hasPasswordChangedAt = (int) dbValue(
        $pdo,
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME = 'password_changed_at'"
    );

    if (
        (int) dbValue($pdo, 'SELECT COUNT(*) FROM users') !== 2
        || (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs') !== 5
        || (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees') !== 10
        || $hasPasswordChangedAt !== 1
    ) {
        fwrite(STDERR, "La base no está en el estado del seed demo o falta la migración. Restaura database/seed.sql y aplica las migraciones.\n");
        exit(2);
    }

    // -----------------------------------------------------------------------
    $t->section('Cabeceras de seguridad');

    $anonymous = new HttpClient(BASE_URL);
    $loginPage = $anonymous->get('/login');

    $t->status('GET /login responde 200', $loginPage, 200);
    $t->ok(
        'CSP incluye frame-ancestors none',
        str_contains((string) $loginPage->header('content-security-policy'), "frame-ancestors 'none'")
    );
    $t->ok(
        'CSP incluye object-src none',
        str_contains((string) $loginPage->header('content-security-policy'), "object-src 'none'")
    );
    $t->ok(
        'Cache-Control es no-store, private',
        str_contains((string) $loginPage->header('cache-control'), 'no-store')
        && str_contains((string) $loginPage->header('cache-control'), 'private')
    );
    $t->ok(
        'Permissions-Policy restringe cámara y micrófono',
        str_contains((string) $loginPage->header('permissions-policy'), 'camera=()')
        && str_contains((string) $loginPage->header('permissions-policy'), 'microphone=()')
    );
    $t->ok('X-Frame-Options es DENY', $loginPage->header('x-frame-options') === 'DENY');
    $t->ok('X-Content-Type-Options es nosniff', $loginPage->header('x-content-type-options') === 'nosniff');
    $t->ok(
        'Referrer-Policy es strict-origin-when-cross-origin',
        $loginPage->header('referrer-policy') === 'strict-origin-when-cross-origin'
    );

    $httpsPage = $anonymous->get('/login', ['X-Forwarded-Proto: https']);
    $t->ok(
        'HSTS se envía cuando la petición es HTTPS',
        str_contains((string) $httpsPage->header('strict-transport-security'), 'max-age=31536000')
    );

    // -----------------------------------------------------------------------
    $t->section('Limitador de login');

    $throttleClient = new HttpClient(BASE_URL);
    $throttlePage = $throttleClient->get('/login');
    $throttleToken = csrfToken($throttlePage->body);
    $throttleEmail = 'throttle-check@example.test';

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $response = $throttleClient->post('/login', [
            '_token' => $throttleToken,
            'email' => $throttleEmail,
            'password' => 'incorrecta123',
        ]);
        $t->status('intento fallido ' . $attempt . ' responde 422', $response, 422);
    }

    $blocked = $throttleClient->post('/login', [
        '_token' => $throttleToken,
        'email' => $throttleEmail,
        'password' => 'incorrecta123',
    ]);
    $t->status('sexto intento del mismo correo responde 422', $blocked, 422);
    $t->containsText('sexto intento muestra el bloqueo', $blocked, 'Demasiados intentos fallidos');

    $otherEmail = $throttleClient->post('/login', [
        '_token' => $throttleToken,
        'email' => 'other-throttle-check@example.test',
        'password' => 'incorrecta123',
    ]);
    $t->status('otro correo desde la misma IP responde 422', $otherEmail, 422);
    $t->notContains('otro correo no está bloqueado por el correo anterior', $otherEmail, 'Demasiados intentos fallidos');

    clearThrottle();

    // -----------------------------------------------------------------------
    $t->section('Invalidación de sesiones por cambio de contraseña');

    $adminA = new HttpClient(BASE_URL);
    $adminB = new HttpClient(BASE_URL);

    $t->status('admin A inicia sesión', login($adminA, 'admin@gestion-rrhh.test'), 302);
    $t->status('admin B inicia sesión', login($adminB, 'admin@gestion-rrhh.test'), 302);
    $t->status('admin A accede al dashboard', $adminA->get('/dashboard'), 200);
    $t->status('admin B accede al dashboard', $adminB->get('/dashboard'), 200);

    $passwordPage = $adminA->get('/account/password');
    $changed = $adminA->post('/account/password', [
        '_token' => csrfToken($passwordPage->body),
        'current_password' => 'password123',
        'password' => 'NuevaClave123!',
        'password_confirmation' => 'NuevaClave123!',
    ]);
    $t->status('cambio de contraseña responde 302', $changed, 302);
    $t->status('la sesión que cambió la contraseña sigue activa', $adminA->get('/account/password'), 200);

    $invalidated = $adminB->get('/dashboard');
    $t->status('la otra sesión se invalida', $invalidated, 302);
    $t->location('la otra sesión va al login', $invalidated, '/login');
    $t->containsText('el login avisa del cambio de contraseña', $adminB->get('/login'), 'Tu contraseña cambió');

    $passwordPage = $adminA->get('/account/password');
    $restored = $adminA->post('/account/password', [
        '_token' => csrfToken($passwordPage->body),
        'current_password' => 'NuevaClave123!',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $t->status('restauración de la contraseña demo responde 302', $restored, 302);

    $fresh = new HttpClient(BASE_URL);
    $t->status(
        'la contraseña demo restaurada permite iniciar sesión',
        login($fresh, 'admin@gestion-rrhh.test', 'password123'),
        302
    );

    $exitCode = $t->summary();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error durante la verificación: ' . $exception->getMessage() . "\n");
} finally {
    clearThrottle();
    restoreAdminPassword($pdo);
}

exit($exitCode);
