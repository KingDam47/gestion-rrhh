<?php

declare(strict_types=1);

/**
 * Verificación funcional HTTP de la separación de roles admin / RR. HH.
 *
 * Cubre administración de usuarios, protección del último administrador,
 * cambio de contraseña propia, cierre de sesión por cambio de rol y auditoría.
 *
 * Datos ficticios. La base debe estar restaurada con database/seed.sql antes de
 * ejecutar la prueba. El guion modifica datos de forma controlada; al terminar,
 * restaura database/seed.sql antes de volver a ejecutarlo.
 *
 * Uso:
 *   & "$env:TEMP\opencode\php84\php.exe" tests\Functional\RolesHttpCheck.php
 *
 * Variables opcionales:
 *   APP_URL      URL base (por defecto http://127.0.0.1:8099)
 *   DB_DSN       DSN PDO (por defecto MariaDB local en el puerto 3307)
 *   DB_USER      usuario de base (por defecto root)
 *   DB_PASSWORD  contraseña de base (vacía por defecto)
 *
 * Requiere `allow_url_fopen`, `pdo_mysql`, `dom` y `mbstring`.
 */

error_reporting(E_ALL);

define('BASE_URL', getenv('APP_URL') ?: 'http://127.0.0.1:8099');
define('DB_DSN', getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3307;dbname=gestion_rrhh;charset=utf8mb4');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');

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
     */
    public function request(string $method, string $path, array $data = []): HttpResponse
    {
        $headers = [
            'Accept: text/html,application/xhtml+xml',
            'User-Agent: roles-functional-check/1.0',
        ];

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

    public function get(string $path): HttpResponse
    {
        return $this->request('GET', $path);
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

    public function same(string $label, mixed $expected, mixed $actual): void
    {
        $this->ok(
            $label,
            $expected === $actual,
            'expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true)
        );
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

    public function contains(string $label, HttpResponse $response, string $needle): void
    {
        $this->ok($label, str_contains($response->body, $needle), 'missing: ' . $needle);
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

function xpath(string $html): DOMXPath
{
    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8" ?>' . $html);
    libxml_clear_errors();

    return new DOMXPath($document);
}

/**
 * @return list<string>
 */
function texts(DOMXPath $xpath, string $query, ?DOMNode $context = null): array
{
    $nodes = $context === null ? $xpath->query($query) : $xpath->query($query, $context);

    if ($nodes === false) {
        return [];
    }

    $result = [];
    foreach ($nodes as $node) {
        $result[] = preg_replace('/\s+/u', ' ', trim($node->textContent)) ?? '';
    }

    return $result;
}

/**
 * @return list<string>
 */
function cards(HttpResponse $response): array
{
    return texts(xpath($response->body), '//p[contains(@class,"display-6")]');
}

function tableRows(HttpResponse $response): int
{
    return count(xpath($response->body)->query('//table//tbody/tr'));
}

/**
 * @return list<string>
 */
function links(HttpResponse $response): array
{
    $hrefs = [];
    $nodes = xpath($response->body)->query('//a[@href]');

    if ($nodes === false) {
        return [];
    }

    foreach ($nodes as $node) {
        $href = (string) $node->getAttribute('href');

        if (str_starts_with($href, '/')) {
            $hrefs[$href] = true;
        }
    }

    return array_keys($hrefs);
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

/**
 * @param array<string, scalar|null> $parameters
 * @return list<array<string, mixed>>
 */
function dbRows(PDO $pdo, string $sql, array $parameters = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

function userId(PDO $pdo, string $email): int
{
    return (int) dbValue($pdo, 'SELECT id FROM users WHERE email = :email', ['email' => $email]);
}

$t = new Checker();
$pdo = db();

if (
    (int) dbValue($pdo, 'SELECT COUNT(*) FROM users') !== 2
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs') !== 5
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees') !== 10
) {
    fwrite(STDERR, "La base no está en el estado del seed demo. Restaura database/seed.sql antes de ejecutar la prueba.\n");
    exit(2);
}

$anonymous = new HttpClient(BASE_URL);
$hr = new HttpClient(BASE_URL);
$admin = new HttpClient(BASE_URL);

// ---------------------------------------------------------------------------
$t->section('Estado inicial y acceso anónimo');

$t->same('usuarios demo', 2, (int) dbValue($pdo, 'SELECT COUNT(*) FROM users'));
$t->same('auditorías demo', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));
$t->same('administrador demo activo', 1, (int) dbValue(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1"
));

foreach (['/users', '/users/create', '/users/1/edit', '/users/1/password', '/audit', '/account/password'] as $page) {
    $response = $anonymous->get($page);
    $t->status('anónimo GET ' . $page . ' redirige', $response, 302);
    $t->location('anónimo GET ' . $page . ' va a login', $response, '/login');
}

foreach (['/users', '/users/1/status', '/users/1/password', '/account/password'] as $page) {
    $response = $anonymous->post($page, []);
    $t->status('anónimo POST ' . $page . ' redirige', $response, 302);
    $t->location('anónimo POST ' . $page . ' va a login', $response, '/login');
}

$response = $anonymous->get('/users/1/status');
$t->status('GET de estado de usuario devuelve 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Personal de RR. HH.: operación sí, administración no');

$response = login($hr, 'rrhh@gestion-rrhh.test');
$t->status('login RR. HH.', $response, 302);
$t->location('login RR. HH. va al panel', $response, '/dashboard');

$response = $hr->get('/dashboard');
$t->status('panel RR. HH.', $response, 200);
$t->contains('panel RR. HH. saluda', $response, 'Hola, Ana López');
$t->notContains('menú RR. HH. sin Usuarios', $response, 'href="/users"');
$t->notContains('menú RR. HH. sin Auditoría', $response, 'href="/audit"');
$t->contains('menú RR. HH. con Mi cuenta', $response, 'href="/account/password"');

foreach (['/users', '/users/create', '/users/1/edit', '/users/1/password', '/audit'] as $page) {
    $response = $hr->get($page);
    $t->status('RR. HH. recibe 403 en ' . $page, $response, 403);
}

$response = $hr->get('/account/password');
$t->status('RR. HH. accede a Mi cuenta', $response, 200);
$t->contains('Mi cuenta pide contraseña actual', $response, 'name="current_password"');
$t->contains('Mi cuenta pide contraseña nueva', $response, 'name="password_confirmation"');
$hrToken = csrfToken($response->body);

$response = $hr->post('/users', [
    '_token' => $hrToken,
    'name' => 'Intruso',
    'email' => 'intruso@example.test',
    'role' => 'admin',
    'password' => 'Intruso12345',
    'password_confirmation' => 'Intruso12345',
]);
$t->status('RR. HH. no puede crear usuarios ni con CSRF', $response, 403);
$t->same('no se creó el usuario intruso', 0, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM users WHERE email = :email',
    ['email' => 'intruso@example.test']
));

$response = $hr->post('/users/1/status', ['_token' => $hrToken, 'status' => 'inactive']);
$t->status('RR. HH. no puede desactivar cuentas', $response, 403);
$t->same('administrador sigue activo', 1, (int) dbValue(
    $pdo,
    'SELECT is_active FROM users WHERE id = 1'
));

$response = $hr->post('/account/password', [
    '_token' => $hrToken,
    'current_password' => 'incorrecta',
    'password' => 'ClaveNueva123',
    'password_confirmation' => 'ClaveNueva123',
]);
$t->status('RR. HH. valida su contraseña actual', $response, 422);
$t->contains('error de contraseña actual', $response, 'La contraseña actual no es correcta.');

foreach (['/employees', '/attendance', '/leave-requests', '/vacancies', '/candidates', '/applications'] as $page) {
    $response = $hr->get($page);
    $t->status('RR. HH. conserva acceso a ' . $page, $response, 200);
}

// ---------------------------------------------------------------------------
$t->section('Administrador: listado y formularios de usuarios');

$response = login($admin, 'admin@gestion-rrhh.test');
$t->status('login admin', $response, 302);
$t->location('login admin va al panel', $response, '/dashboard');

$response = $admin->get('/dashboard');
$t->status('panel admin', $response, 200);
$t->contains('menú admin con Usuarios', $response, 'href="/users"');
$t->contains('menú admin con Auditoría', $response, 'href="/audit"');
$t->contains('menú admin con Mi cuenta', $response, 'href="/account/password"');

$response = $admin->get('/users');
$t->status('listado de usuarios', $response, 200);
$t->same('listado demo tiene 2 usuarios', 2, tableRows($response));
$t->same('tarjetas de usuarios demo', ['2', '2', '1', '1'], cards($response));
$t->contains('listado muestra administrador', $response, 'Administrador Demo');
$t->contains('listado muestra RR. HH.', $response, 'Ana López');
$t->containsText('listado indica paginación', $response, 'Mostrando 1–2 de 2 usuarios');

$response = $admin->get('/users?q=Ana');
$t->status('filtro de usuarios por texto', $response, 200);
$t->same('filtro por texto deja una fila', 1, tableRows($response));
$t->contains('filtro por texto encuentra a Ana', $response, 'Ana López');

$response = $admin->get('/users?role=admin');
$t->status('filtro de usuarios por rol', $response, 200);
$t->same('filtro por admin deja una fila', 1, tableRows($response));

$response = $admin->get('/users?status=inactive');
$t->status('filtro de usuarios inactivos', $response, 200);
$t->same('no hay usuarios inactivos', 0, tableRows($response));
$t->contains('estado vacío de usuarios', $response, 'No hay usuarios que coincidan');

$response = $admin->get('/users?role=superadmin');
$t->status('rol de filtro inválido se ignora', $response, 200);
$t->same('filtro inválido muestra todos', 2, tableRows($response));

$response = $admin->get('/users/create');
$t->status('formulario de nuevo usuario', $response, 200);
$t->contains('formulario nuevo usuario', $response, 'Nuevo usuario');
$t->contains('formulario pide contraseña', $response, 'name="password_confirmation"');

$response = $admin->get('/users/1/edit');
$t->status('formulario de edición del admin', $response, 200);
$t->contains('edición muestra advertencia de último admin', $response, 'única cuenta de administrador activa');
$adminToken = csrfToken($response->body);

// ---------------------------------------------------------------------------
$t->section('Protección del último administrador activo');

$response = $admin->post('/users/1', [
    '_token' => $adminToken,
    'name' => 'Administrador Demo',
    'email' => 'admin@gestion-rrhh.test',
    'role' => 'hr',
]);
$t->status('último admin no puede degradarse', $response, 422);
$t->contains('mensaje de último admin', $response, 'No puedes quitar el rol de administrador al último administrador activo.');
$t->same('rol del admin intacto', 'admin', (string) dbValue(
    $pdo,
    'SELECT role FROM users WHERE id = 1'
));

$response = $admin->post('/users/1/status', ['_token' => $adminToken, 'status' => 'inactive']);
$t->status('admin no puede desactivarse a sí mismo', $response, 302);
$t->location('redirección tras intento de autodesactivación', $response, '/users');

$response = $admin->get('/users');
$t->contains('aviso de autodesactivación', $response, 'No puedes desactivar tu propia cuenta.');
$t->same('admin sigue activo', 1, (int) dbValue(
    $pdo,
    'SELECT is_active FROM users WHERE id = 1'
));

// ---------------------------------------------------------------------------
$t->section('Alta y edición de usuarios');

$response = $admin->post('/users', [
    '_token' => $adminToken,
    'name' => 'Duplicado',
    'email' => 'admin@gestion-rrhh.test',
    'role' => 'hr',
    'password' => 'Temporal12345',
    'password_confirmation' => 'Temporal12345',
]);
$t->status('correo repetido devuelve 422', $response, 422);
$t->contains('error de correo repetido', $response, 'Ya existe una cuenta con ese correo electrónico.');

$response = $admin->post('/users', [
    '_token' => $adminToken,
    'name' => 'Clave Corta',
    'email' => 'corta@example.test',
    'role' => 'hr',
    'password' => 'corta',
    'password_confirmation' => 'corta',
]);
$t->status('contraseña corta devuelve 422', $response, 422);
$t->containsText('error de longitud de contraseña', $response, '10 caracteres');

$response = $admin->post('/users', [
    '_token' => $adminToken,
    'name' => 'Rol Inválido',
    'email' => 'rol.invalido@example.test',
    'role' => 'superadmin',
    'password' => 'Temporal12345',
    'password_confirmation' => 'Temporal12345',
]);
$t->status('rol inválido devuelve 422', $response, 422);
$t->same('no se crearon usuarios inválidos', 2, (int) dbValue($pdo, 'SELECT COUNT(*) FROM users'));

$response = $admin->post('/users', [
    '_token' => $adminToken,
    'name' => 'María Temporal',
    'email' => 'maria.rrhh@example.test',
    'role' => 'hr',
    'password' => 'Temporal12345',
    'password_confirmation' => 'Temporal12345',
]);
$t->status('alta de usuario RR. HH.', $response, 302);
$t->location('alta de usuario va al listado', $response, '/users');
$t->same('usuarios tras el alta', 3, (int) dbValue($pdo, 'SELECT COUNT(*) FROM users'));

$tempId = userId($pdo, 'maria.rrhh@example.test');
$t->ok('usuario temporal tiene ID', $tempId > 0);

$response = $admin->get('/users');
$t->same('listado tras el alta', 3, tableRows($response));
$t->contains('listado muestra correo nuevo', $response, 'maria.rrhh@example.test');

$createdAudit = dbRows(
    $pdo,
    "SELECT * FROM audit_logs WHERE action = 'user.created' ORDER BY id DESC LIMIT 1"
)[0] ?? [];
$t->same('auditoría de alta', 'user.created', (string) ($createdAudit['action'] ?? ''));
$t->same('metadata de alta solo rol', ['role' => 'hr'], json_decode((string) ($createdAudit['metadata'] ?? ''), true));

$response = $admin->get('/users/' . $tempId . '/edit');
$t->status('edición de usuario RR. HH.', $response, 200);
$t->contains('edición muestra correo', $response, 'maria.rrhh@example.test');
$timerToken = csrfToken($response->body);

$response = $admin->post('/users/' . $tempId, [
    '_token' => $timerToken,
    'name' => 'María Temporal Actualizada',
    'email' => 'maria.temporal@example.test',
    'role' => 'hr',
]);
$t->status('actualización de usuario', $response, 302);
$t->same('nombre actualizado', 'María Temporal Actualizada', (string) dbValue(
    $pdo,
    'SELECT name FROM users WHERE id = :id',
    ['id' => $tempId]
));
$t->same('correo actualizado', 'maria.temporal@example.test', (string) dbValue(
    $pdo,
    'SELECT email FROM users WHERE id = :id',
    ['id' => $tempId]
));

$updatedAudit = dbRows(
    $pdo,
    "SELECT * FROM audit_logs WHERE action = 'user.updated' ORDER BY id DESC LIMIT 1"
)[0] ?? [];
$t->same('metadata de edición solo rol', ['role' => 'hr'], json_decode((string) ($updatedAudit['metadata'] ?? ''), true));

// ---------------------------------------------------------------------------
$t->section('Restablecimiento de contraseña por administrador');

$response = $admin->get('/users/' . $tempId . '/password');
$t->status('formulario de restablecimiento', $response, 200);
$t->contains('restablecimiento pide confirmación', $response, 'name="password_confirmation"');
$resetToken = csrfToken($response->body);

$response = $admin->post('/users/' . $tempId . '/password', [
    '_token' => $resetToken,
    'password' => 'ClaveNueva123',
    'password_confirmation' => 'ClaveNueva123',
]);
$t->status('restablecimiento de contraseña', $response, 302);
$t->location('restablecimiento vuelve al listado', $response, '/users');

$temp = new HttpClient(BASE_URL);
$response = login($temp, 'maria.temporal@example.test', 'Temporal12345');
$t->status('contraseña anterior ya no sirve', $response, 422);
$t->contains('error de credenciales tras restablecer', $response, 'Las credenciales no son válidas.');

$response = login($temp, 'maria.temporal@example.test', 'ClaveNueva123');
$t->status('contraseña restablecida permite entrar', $response, 302);
$t->location('login con contraseña nueva va al panel', $response, '/dashboard');

$resetAudit = dbRows(
    $pdo,
    "SELECT * FROM audit_logs WHERE action = 'user.password_reset' ORDER BY id DESC LIMIT 1"
)[0] ?? [];
$t->same('auditoría de restablecimiento sin metadata', null, $resetAudit['metadata'] ?? null);

// ---------------------------------------------------------------------------
$t->section('Cambio de contraseña propia y CSRF');

$response = $temp->get('/account/password');
$t->status('usuario temporal entra a Mi cuenta', $response, 200);
$ownToken = csrfToken($response->body);

$response = $temp->post('/account/password', [
    '_token' => $ownToken,
    'current_password' => 'ClaveNueva123',
    'password' => 'ClavePropia123',
    'password_confirmation' => 'OtraClave123',
]);
$t->status('confirmación distinta devuelve 422', $response, 422);

$response = $temp->post('/account/password', [
    '_token' => $ownToken,
    'current_password' => 'ClaveNueva123',
    'password' => 'ClavePropia123',
    'password_confirmation' => 'ClavePropia123',
]);
$t->status('cambio de contraseña propia', $response, 302);
$t->location('cambio propio vuelve a Mi cuenta', $response, '/account/password');

$response = $temp->get('/account/password');
$t->status('sigue autenticado tras cambiar su clave', $response, 200);

$tempLogin = new HttpClient(BASE_URL);
$response = login($tempLogin, 'maria.temporal@example.test', 'ClavePropia123');
$t->status('contraseña propia permite iniciar sesión', $response, 302);

$response = $temp->post('/account/password', [
    'current_password' => 'ClavePropia123',
    'password' => 'SinToken12345',
    'password_confirmation' => 'SinToken12345',
]);
$t->status('cambio propio sin CSRF devuelve 403', $response, 403);

$ownAudit = dbRows(
    $pdo,
    "SELECT * FROM audit_logs WHERE action = 'user.password_changed' ORDER BY id DESC LIMIT 1"
)[0] ?? [];
$t->same('auditoría de cambio propio sin metadata', null, $ownAudit['metadata'] ?? null);

// ---------------------------------------------------------------------------
$t->section('Desactivación, sesión y reactivación');

$response = $admin->post('/users/' . $tempId . '/status', [
    '_token' => $adminToken,
    'status' => 'inactive',
]);
$t->status('desactivación de usuario', $response, 302);
$t->same('usuario desactivado en base', 0, (int) dbValue(
    $pdo,
    'SELECT is_active FROM users WHERE id = :id',
    ['id' => $tempId]
));

$response = $tempLogin->get('/dashboard');
$t->status('sesión de usuario desactivado se corta', $response, 302);
$t->location('sesión cortada vuelve a login', $response, '/login');

$response = $tempLogin->get('/login');
$t->contains('aviso de cuenta inactiva', $response, 'Tu cuenta ya no está activa.');

$inactiveLogin = new HttpClient(BASE_URL);
$response = login($inactiveLogin, 'maria.temporal@example.test', 'ClavePropia123');
$t->status('cuenta inactiva no puede iniciar sesión', $response, 422);

$response = $admin->post('/users/' . $tempId . '/status', [
    '_token' => $adminToken,
    'status' => 'active',
]);
$t->status('reactivación de usuario', $response, 302);
$t->same('usuario reactivado en base', 1, (int) dbValue(
    $pdo,
    'SELECT is_active FROM users WHERE id = :id',
    ['id' => $tempId]
));

$reactivated = new HttpClient(BASE_URL);
$response = login($reactivated, 'maria.temporal@example.test', 'ClavePropia123');
$t->status('usuario reactivado puede entrar', $response, 302);

$deactivatedAudit = dbRows(
    $pdo,
    "SELECT * FROM audit_logs WHERE action = 'user.deactivated' ORDER BY id DESC LIMIT 1"
)[0] ?? [];
$t->same(
    'metadata de desactivación limitada',
    ['status' => 'inactive', 'role' => 'hr'],
    json_decode((string) ($deactivatedAudit['metadata'] ?? ''), true)
);

// ---------------------------------------------------------------------------
$t->section('Cambio de rol y cierre de sesión');

$response = $admin->post('/users', [
    '_token' => $adminToken,
    'name' => 'Segundo Administrador',
    'email' => 'admin2@example.test',
    'role' => 'admin',
    'password' => 'Temporal12345',
    'password_confirmation' => 'Temporal12345',
]);
$t->status('alta de segundo administrador', $response, 302);
$admin2Id = userId($pdo, 'admin2@example.test');

$admin2 = new HttpClient(BASE_URL);
$response = login($admin2, 'admin2@example.test', 'Temporal12345');
$t->status('segundo admin inicia sesión', $response, 302);

$response = $admin2->get('/users');
$t->status('segundo admin accede a Usuarios', $response, 200);

$response = $admin->post('/users/' . $admin2Id, [
    '_token' => $adminToken,
    'name' => 'Segundo Administrador',
    'email' => 'admin2@example.test',
    'role' => 'hr',
]);
$t->status('admin degrada al segundo admin', $response, 302);

$response = $admin2->get('/dashboard');
$t->status('cambio de rol corta la sesión', $response, 302);
$t->location('cambio de rol vuelve a login', $response, '/login');

$response = $admin2->get('/login');
$t->contains('aviso de permisos cambiados', $response, 'Tus permisos cambiaron.');

$response = login($admin2, 'admin2@example.test', 'Temporal12345');
$t->status('usuario degradado puede entrar como RR. HH.', $response, 302);
$response = $admin2->get('/users');
$t->status('usuario degradado ya no administra', $response, 403);
$response = $admin2->get('/employees');
$t->status('usuario degradado conserva operación', $response, 200);
$t->same('solo queda un administrador activo', 1, (int) dbValue(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1"
));

// ---------------------------------------------------------------------------
$t->section('Auditoría solo para administración');

$response = $hr->get('/audit');
$t->status('RR. HH. no consulta auditoría', $response, 403);
$response = $admin2->get('/audit');
$t->status('RR. HH. degradado tampoco consulta auditoría', $response, 403);

$response = $admin->get('/audit');
$t->status('admin consulta auditoría', $response, 200);
$t->same('auditoría muestra todos los registros', (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'), tableRows($response));
$t->contains('auditoría muestra usuario creado', $response, 'Usuario creado');
$t->contains('auditoría muestra usuario actualizado', $response, 'Usuario actualizado');
$t->contains('auditoría muestra contraseña restablecida', $response, 'Contraseña restablecida');
$t->contains('auditoría muestra cambio propio', $response, 'Contraseña propia cambiada');
$t->contains('auditoría muestra desactivación', $response, 'Usuario desactivado');
$t->contains('auditoría muestra reactivación', $response, 'Usuario reactivado');
$t->contains('auditoría muestra el nombre del revisor', $response, 'María Temporal Actualizada');
$auditTable = implode(' ', texts(xpath($response->body), '//table'));
$t->ok('auditoría no muestra correos en la tabla', !str_contains($auditTable, '@'));

$response = $admin->get('/audit?entity_type=user');
$t->status('filtro de auditoría por entidad', $response, 200);
$t->same(
    'filtro por entidad usuario',
    (int) dbValue($pdo, "SELECT COUNT(*) FROM audit_logs WHERE entity_type = 'user'"),
    tableRows($response)
);
$t->contains('filtro por entidad muestra usuarios', $response, 'Usuario');

$response = $admin->get('/audit?q=user.created');
$t->status('filtro de auditoría por acción', $response, 200);
$t->same(
    'filtro por acción encuentra las altas',
    (int) dbValue($pdo, "SELECT COUNT(*) FROM audit_logs WHERE action = 'user.created'"),
    tableRows($response)
);

$response = $admin->get('/audit?date_from=2020-01-01&date_to=2020-12-31');
$t->status('filtro de auditoría por fecha', $response, 200);
$t->same('rango sin registros', 0, tableRows($response));
$t->contains('estado vacío de auditoría', $response, 'No hay registros que coincidan');

$newAudits = dbRows($pdo, 'SELECT * FROM audit_logs WHERE id > 5 ORDER BY id');
$metadataLimited = true;
foreach ($newAudits as $audit) {
    if ($audit['metadata'] === null) {
        continue;
    }

    $decoded = json_decode((string) $audit['metadata'], true);
    if (!is_array($decoded)) {
        $metadataLimited = false;
        break;
    }

    foreach (array_keys($decoded) as $key) {
        if (!in_array($key, ['status', 'role'], true)) {
            $metadataLimited = false;
            break 2;
        }
    }

    if (str_contains((string) $audit['metadata'], '@')) {
        $metadataLimited = false;
        break;
    }
}
$t->ok('auditorías nuevas solo guardan estado o rol', $metadataLimited);

// ---------------------------------------------------------------------------
$t->section('CSRF, 404 y estado final');

foreach ([
    ['/users', ['name' => 'Sin token', 'email' => 'sintoken@example.test', 'role' => 'hr']],
    ['/users/1/status', ['status' => 'inactive']],
    ['/users/1/password', ['password' => 'SinToken12345', 'password_confirmation' => 'SinToken12345']],
    ['/account/password', ['current_password' => 'password123', 'password' => 'SinToken12345', 'password_confirmation' => 'SinToken12345']],
] as [$path, $data]) {
    $response = $admin->post($path, $data);
    $t->status('POST sin CSRF en ' . $path . ' devuelve 403', $response, 403);
}
$t->same('ningún usuario sin token', 4, (int) dbValue($pdo, 'SELECT COUNT(*) FROM users'));

foreach (['/users/999/edit', '/users/999/password'] as $path) {
    $response = $admin->get($path);
    $t->status('usuario inexistente en ' . $path, $response, 404);
}

$response = $admin->post('/users/999/status', ['_token' => $adminToken, 'status' => 'inactive']);
$t->status('estado de usuario inexistente', $response, 404);

foreach (['/employees', '/attendance', '/leave-requests', '/vacancies', '/candidates', '/applications', '/audit'] as $page) {
    $response = $admin->get($page);
    $t->status('admin conserva acceso a ' . $page, $response, 200);
}

$t->same('usuarios finales', 4, (int) dbValue($pdo, 'SELECT COUNT(*) FROM users'));
$t->same('administrador final activo', 1, (int) dbValue(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1"
));
$t->same('RR. HH. final', 3, (int) dbValue($pdo, "SELECT COUNT(*) FROM users WHERE role = 'hr'"));
$t->same('usuarios finales activos', 4, (int) dbValue($pdo, 'SELECT COUNT(*) FROM users WHERE is_active = 1'));

exit($t->summary());
