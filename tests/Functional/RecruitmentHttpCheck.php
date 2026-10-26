<?php

declare(strict_types=1);

/**
 * Verificación funcional HTTP del módulo Reclutamiento y selección.
 *
 * Datos ficticios. La base debe estar restaurada con database/seed.sql antes de
 * ejecutar la prueba y debe restaurarse otra vez al terminar, porque el guion
 * crea y modifica vacantes, candidatos, postulaciones, empleados y auditoría.
 *
 * Uso:
 *   & "$env:TEMP\opencode\php84\php.exe" tests\Functional\RecruitmentHttpCheck.php
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
            'User-Agent: recruitment-functional-check/1.0',
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

        $context = stream_context_create($options);
        $body = @file_get_contents($this->baseUrl . $path, false, $context);
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

    /**
     * Comprueba texto que puede estar repartido en varias líneas HTML.
     */
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

function login(HttpClient $client, string $email): HttpResponse
{
    $page = $client->get('/login');
    $response = $client->post('/login', [
        '_token' => csrfToken($page->body),
        'email' => $email,
        'password' => 'password123',
    ]);

    return $response;
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

/**
 * @return list<string>
 */
function selectOptions(HttpResponse $response, string $id): array
{
    return texts(xpath($response->body), '//select[@id="' . $id . '"]/option');
}

/**
 * @return list<string>
 */
function selectedOptions(HttpResponse $response, string $id): array
{
    return texts(xpath($response->body), '//select[@id="' . $id . '"]/option[@selected]');
}

function inputValue(HttpResponse $response, string $name): ?string
{
    $values = texts(xpath($response->body), '//input[@name="' . $name . '"]/@value');

    return $values[0] ?? null;
}

function tableRows(HttpResponse $response, int $tableIndex = 0): int
{
    $xpath = xpath($response->body);
    $tables = $xpath->query('//table');

    if ($tables === false || $tables->item($tableIndex) === null) {
        return 0;
    }

    $rows = $xpath->query('.//tbody/tr', $tables->item($tableIndex));

    return $rows === false ? 0 : $rows->length;
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
 * @return array<string, mixed>|null
 */
function dbRow(PDO $pdo, string $sql, array $parameters = []): ?array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    $row = $statement->fetch();

    return $row === false ? null : $row;
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

/**
 * @param array<string, scalar|null> $parameters
 */
function dbExec(PDO $pdo, string $sql, array $parameters = []): void
{
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
}

/**
 * @return array<string, mixed>|null
 */
function lastAudit(PDO $pdo): ?array
{
    return dbRow($pdo, 'SELECT * FROM audit_logs ORDER BY id DESC LIMIT 1');
}

function auditMetadata(?array $audit): array
{
    if ($audit === null || $audit['metadata'] === null) {
        return [];
    }

    $decoded = json_decode((string) $audit['metadata'], true);

    return is_array($decoded) ? $decoded : [];
}

function setDepartmentStatus(PDO $pdo, int $departmentId, string $status): void
{
    dbExec(
        $pdo,
        'UPDATE departments SET is_active = :status WHERE id = :id',
        ['status' => $status === 'active' ? 1 : 0, 'id' => $departmentId]
    );
}

function setPositionStatus(PDO $pdo, int $positionId, string $status): void
{
    dbExec(
        $pdo,
        'UPDATE positions SET is_active = :status WHERE id = :id',
        ['status' => $status === 'active' ? 1 : 0, 'id' => $positionId]
    );
}

/**
 * @param array<string, scalar|null> $overrides
 */
function hirePost(HttpClient $client, string $token, int $applicationId, array $overrides = []): HttpResponse
{
    $base = [
        'document' => 'RRHH-9001',
        'first_name' => 'Ana',
        'last_name' => 'Torres',
        'email' => 'ana.torres.candidata@example.test',
        'phone' => '+1 555 0202',
        'hire_date' => date('Y-m-d'),
        'department_id' => '3',
        'position_id' => '4',
    ];

    return $client->post(
        '/applications/' . $applicationId . '/hire',
        array_merge(['_token' => $token], $base, $overrides)
    );
}

/**
 * Inserta datos temporales para comprobar la paginación de los tres listados.
 */
function insertPaginationData(PDO $pdo): void
{
    $vacancy = $pdo->prepare(
        'INSERT INTO vacancies
            (title, description, department_id, position_id, opening_date, closing_date, status, created_by, updated_by)
         VALUES
            (:title, :description, 3, 4, :opening_date, NULL, \'open\', 1, 1)'
    );

    for ($index = 1; $index <= 7; $index++) {
        $vacancy->execute([
            'title' => sprintf('Vacante de paginación %02d', $index),
            'description' => 'Dato temporal para la prueba de paginación.',
            'opening_date' => sprintf('2026-09-%02d', $index),
        ]);
    }

    $candidate = $pdo->prepare(
        'INSERT INTO candidates (first_name, last_name, email, phone, notes, created_by, updated_by)
         VALUES (:first_name, :last_name, :email, NULL, :notes, 1, 1)'
    );

    $candidateIds = [];
    for ($index = 1; $index <= 5; $index++) {
        $candidate->execute([
            'first_name' => 'Paginación ' . $index,
            'last_name' => 'Zúñiga',
            'email' => sprintf('pagina%02d@example.test', $index),
            'notes' => 'Dato temporal para la prueba de paginación.',
        ]);
        $candidateIds[] = (int) $pdo->lastInsertId();
    }

    $application = $pdo->prepare(
        'INSERT INTO applications
            (candidate_id, vacancy_id, applied_at, status, notes, created_by, updated_by)
         VALUES
            (:candidate_id, 3, :applied_at, \'received\', :notes, 1, 1)'
    );

    foreach ($candidateIds as $index => $candidateId) {
        $application->execute([
            'candidate_id' => $candidateId,
            'applied_at' => sprintf('2026-10-20 10:%02d:00', $index),
            'notes' => 'Postulación temporal para la prueba de paginación.',
        ]);
    }
}

$t = new Checker();
$pdo = db();

if (
    (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees') !== 10
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM vacancies') !== 3
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates') !== 5
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications') !== 6
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs') !== 5
) {
    fwrite(STDERR, "La base no está en el estado del seed demo. Restaura database/seed.sql antes de ejecutar la prueba.\n");
    exit(2);
}

$admin = new HttpClient(BASE_URL);
$hr = new HttpClient(BASE_URL);
$anonymous = new HttpClient(BASE_URL);

// ---------------------------------------------------------------------------
$t->section('Estado inicial y acceso anónimo');

$t->same('empleados demo', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('vacantes demo', 3, (int) dbValue($pdo, 'SELECT COUNT(*) FROM vacancies'));
$t->same('candidatos demo', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));
$t->same('postulaciones demo', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));
$t->same('auditorías demo', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

foreach (['/recruitment', '/vacancies', '/vacancies/1', '/candidates', '/candidates/1', '/applications', '/applications/1'] as $page) {
    $response = $anonymous->get($page);
    $t->status('anónimo GET ' . $page . ' redirige', $response, 302);
    $t->location('anónimo GET ' . $page . ' va a login', $response, '/login');
}

foreach (['/vacancies', '/candidates', '/applications'] as $page) {
    $response = $anonymous->post($page, []);
    $t->status('anónimo POST ' . $page . ' redirige', $response, 302);
    $t->location('anónimo POST ' . $page . ' va a login', $response, '/login');
}

// ---------------------------------------------------------------------------
$t->section('Inicio de sesión y resumen de reclutamiento');

$response = login($admin, 'admin@gestion-rrhh.test');
$t->status('login admin', $response, 302);
$t->location('login admin va al panel', $response, '/dashboard');

$response = $admin->get('/dashboard');
$t->status('panel admin', $response, 200);
$t->contains('panel admin saluda al usuario', $response, 'Administrador Demo');
$t->contains('panel enlaza reclutamiento', $response, 'href="/recruitment"');

$response = $admin->get('/recruitment');
$t->status('resumen de reclutamiento', $response, 200);
$t->contains('resumen título', $response, 'Reclutamiento y selección');
$t->same('resumen tarjetas demo', ['2', '5', '4', '1'], cards($response));
$t->contains('resumen vacante abierta backend', $response, 'Desarrollador Backend');
$t->contains('resumen vacante abierta ventas', $response, 'Ejecutivo de Ventas');
$t->contains('resumen candidata seleccionada', $response, 'Lucía Fernández');
$t->contains('resumen enlaza alta guiada', $response, 'href="/applications/4/hire"');
$t->notContains('resumen no enlaza vacante cerrada', $response, 'href="/vacancies/2"');

// ---------------------------------------------------------------------------
$t->section('Vacantes: lectura, filtros y formularios');

$response = $admin->get('/vacancies');
$t->status('listado de vacantes', $response, 200);
$t->same('resumen vacantes demo', ['3', '2', '1'], cards($response));
$t->same('listado vacantes filas', 3, tableRows($response));
$t->containsText('listado vacantes paginación', $response, 'Mostrando 1–3 de 3 vacantes');
$t->contains('listado vacantes backend', $response, 'Desarrollador Backend');
$t->contains('listado vacantes ventas', $response, 'Ejecutivo de Ventas');
$t->contains('listado vacantes cerrada', $response, 'Analista de RR. HH.');

$response = $admin->get('/vacancies?q=Desarrollador');
$t->status('filtro vacante por título', $response, 200);
$t->same('filtro vacante por título filas', 1, tableRows($response));
$t->same('filtro vacante por título resumen', ['1', '1', '0'], cards($response));

$response = $admin->get('/vacancies?status=open');
$t->status('filtro vacantes abiertas', $response, 200);
$t->same('filtro vacantes abiertas filas', 2, tableRows($response));
$t->same('filtro vacantes abiertas resumen', ['2', '2', '0'], cards($response));

$response = $admin->get('/vacancies?status=closed');
$t->status('filtro vacantes cerradas', $response, 200);
$t->same('filtro vacantes cerradas filas', 1, tableRows($response));
$t->same('filtro vacantes cerradas resumen', ['1', '0', '1'], cards($response));

$response = $admin->get('/vacancies?department_id=4');
$t->status('filtro vacantes por departamento', $response, 200);
$t->same('filtro vacantes por departamento filas', 1, tableRows($response));
$t->same('filtro vacantes por departamento resumen', ['1', '1', '0'], cards($response));
$t->contains('filtro departamento muestra ventas', $response, 'Ejecutivo de Ventas');

$response = $admin->get('/vacancies?department_id=999');
$t->status('filtro vacantes departamento inexistente', $response, 200);
$t->same('filtro departamento inexistente filas', 0, tableRows($response));
$t->contains('filtro departamento inexistente vacío', $response, 'No hay vacantes que coincidan');

$response = $admin->get('/vacancies?status=inventado');
$t->status('filtro vacante estado inválido', $response, 200);
$t->same('filtro vacante estado inválido se ignora', 3, tableRows($response));

$response = $admin->get('/vacancies/create');
$t->status('formulario nueva vacante', $response, 200);
$t->contains('formulario vacante título', $response, 'Nueva vacante');
$t->same('formulario vacante departamentos', 6, count(selectOptions($response, 'department_id')));
$t->same('formulario vacante cargos', 8, count(selectOptions($response, 'position_id')));

$response = $admin->get('/vacancies/1');
$t->status('detalle vacante abierta', $response, 200);
$t->contains('detalle vacante abierta título', $response, 'Desarrollador Backend');
$t->contains('detalle vacante abierta estado', $response, 'Abierta');
$t->same('detalle vacante abierta postulaciones', 2, tableRows($response));
$t->contains('detalle vacante abierta candidato 1', $response, 'Juan Pérez');
$t->contains('detalle vacante abierta candidata 2', $response, 'María López');

$response = $admin->get('/vacancies/2');
$t->status('detalle vacante cerrada', $response, 200);
$t->contains('detalle vacante cerrada aviso', $response, 'Esta vacante está cerrada');
$t->contains('detalle vacante cerrada estado', $response, 'Cerrada');
$t->same('detalle vacante cerrada postulaciones', 2, tableRows($response));

$response = $admin->get('/vacancies/1/edit');
$t->status('formulario editar vacante abierta', $response, 200);
$t->contains('editar vacante abierta título', $response, 'Editar vacante');
$t->contains('editar vacante abierta valor', $response, 'Desarrollador Backend');

$response = $admin->get('/vacancies/2/edit');
$t->status('editar vacante cerrada redirige', $response, 302);
$t->location('editar vacante cerrada vuelve al detalle', $response, '/vacancies/2');

$response = $admin->get('/vacancies/999');
$t->status('vacante inexistente 404', $response, 404);
$response = $admin->get('/vacancies/999/edit');
$t->status('editar vacante inexistente 404', $response, 404);

$response = $admin->get('/vacancies');
$t->ok('cabecera nosniff', $response->header('x-content-type-options') === 'nosniff');
$t->ok('cabecera frame deny', $response->header('x-frame-options') === 'DENY');
$t->ok('cabecera CSP', str_contains((string) $response->header('content-security-policy'), "default-src 'self'"));

// ---------------------------------------------------------------------------
$t->section('Protección CSRF');

$page = $admin->get('/vacancies/create');
$token = csrfToken($page->body);

$response = $admin->post('/vacancies', [
    'title' => 'Sin token',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('POST vacante sin CSRF', $response, 403);
$t->same('sin CSRF no crea vacante', 3, (int) dbValue($pdo, 'SELECT COUNT(*) FROM vacancies'));

$response = $admin->post('/candidates', [
    'first_name' => 'Sin',
    'last_name' => 'Token',
    'email' => 'sin.token@example.test',
]);
$t->status('POST candidato sin CSRF', $response, 403);
$t->same('sin CSRF no crea candidato', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));

$response = $admin->post('/applications', [
    'candidate_id' => '1',
    'vacancy_id' => '1',
]);
$t->status('POST postulación sin CSRF', $response, 403);
$t->same('sin CSRF no crea postulación', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));

$response = $admin->post('/vacancies/1/close', []);
$t->status('POST cierre sin CSRF', $response, 403);
$t->same('sin CSRF no cierra vacante', 'open', (string) dbValue($pdo, 'SELECT status FROM vacancies WHERE id = 1'));

$response = $admin->post('/applications/2/status', ['status' => 'interview', 'notes' => '']);
$t->status('POST cambio de estado sin CSRF', $response, 403);
$t->same('sin CSRF no cambia postulación', 'screening', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 2'));

$response = $admin->post('/applications/4/hire', []);
$t->status('POST alta guiada sin CSRF', $response, 403);
$t->same('sin CSRF no crea empleado', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('sin CSRF no vincula postulación', null, dbValue($pdo, 'SELECT employee_id FROM applications WHERE id = 4'));

// ---------------------------------------------------------------------------
$t->section('Vacantes: validaciones y ciclo de vida');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => '',
    'description' => '',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante sin título rechazada', $response, 422);
$t->contains('vacante sin título muestra error', $response, 'El campo título es obligatorio.');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante sin departamento',
    'description' => '',
    'department_id' => '',
    'position_id' => '',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante sin departamento rechazada', $response, 422);
$t->contains('vacante sin departamento muestra error', $response, 'El campo departamento es obligatorio.');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con departamento inexistente',
    'description' => '',
    'department_id' => '999',
    'position_id' => '',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con departamento inexistente', $response, 422);
$t->contains('departamento inexistente muestra error', $response, 'El departamento seleccionado no existe.');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con cargo cruzado',
    'description' => '',
    'department_id' => '3',
    'position_id' => '3',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con cargo de otro departamento', $response, 422);
$t->contains('cargo cruzado muestra error', $response, 'El cargo no pertenece al departamento seleccionado.');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con cargo inexistente',
    'description' => '',
    'department_id' => '3',
    'position_id' => '999',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con cargo inexistente', $response, 422);
$t->contains('cargo inexistente muestra error', $response, 'El cargo seleccionado no existe.');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con fechas invertidas',
    'description' => '',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-11-20',
    'closing_date' => '2026-11-01',
]);
$t->status('vacante con cierre anterior', $response, 422);
$t->contains('cierre anterior muestra error', $response, 'La fecha de cierre no puede ser anterior a la fecha de apertura.');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con fecha inválida',
    'description' => '',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-02-30',
    'closing_date' => '',
]);
$t->status('vacante con fecha inválida', $response, 422);
$t->contains('fecha inválida muestra error', $response, 'debe ser una fecha válida');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => str_repeat('x', 151),
    'description' => '',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con título demasiado largo', $response, 422);
$t->contains('título largo muestra error', $response, 'no debe superar los 150 caracteres');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con descripción larga',
    'description' => str_repeat('x', 2001),
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con descripción demasiado larga', $response, 422);
$t->contains('descripción larga muestra error', $response, 'no debe superar los 2000 caracteres');

setDepartmentStatus($pdo, 5, 'inactive');
$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con departamento inactivo',
    'description' => '',
    'department_id' => '5',
    'position_id' => '7',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con departamento inactivo', $response, 422);
$t->contains('departamento inactivo muestra error', $response, 'El departamento seleccionado está inactivo.');
setDepartmentStatus($pdo, 5, 'active');

setPositionStatus($pdo, 7, 'inactive');
$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => 'Vacante con cargo inactivo',
    'description' => '',
    'department_id' => '5',
    'position_id' => '7',
    'opening_date' => '2026-10-20',
    'closing_date' => '',
]);
$t->status('vacante con cargo inactivo', $response, 422);
$t->contains('cargo inactivo muestra error', $response, 'El cargo seleccionado está inactivo.');
setPositionStatus($pdo, 7, 'active');

$response = $admin->post('/vacancies', [
    '_token' => $token,
    'title' => '  Analista   de Datos  ',
    'description' => ' Búsqueda interna de prueba. ',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '2026-11-20',
]);
$t->status('crear vacante válida', $response, 302);
$t->location('crear vacante abre el detalle', $response, '/vacancies/4');

$vacancy = dbRow($pdo, 'SELECT * FROM vacancies WHERE id = 4');
$t->ok('vacante creada en base', $vacancy !== null);
$t->same('vacante título normalizado', 'Analista de Datos', $vacancy['title'] ?? null);
$t->same('vacante descripción normalizada', 'Búsqueda interna de prueba.', $vacancy['description'] ?? null);
$t->same('vacante departamento', 3, (int) ($vacancy['department_id'] ?? 0));
$t->same('vacante cargo', 4, (int) ($vacancy['position_id'] ?? 0));
$t->same('vacante apertura', '2026-10-20', $vacancy['opening_date'] ?? null);
$t->same('vacante cierre propuesto', '2026-11-20', $vacancy['closing_date'] ?? null);
$t->same('vacante nace abierta', 'open', $vacancy['status'] ?? null);
$t->same('vacante creador', 1, (int) ($vacancy['created_by'] ?? 0));
$t->same('vacante editor inicial', 1, (int) ($vacancy['updated_by'] ?? 0));
$t->same('crear vacante no audita', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/vacancies/4', [
    '_token' => $token,
    'title' => 'Analista de Datos Senior',
    'description' => 'Descripción actualizada.',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '2026-11-25',
]);
$t->status('editar vacante abierta', $response, 302);
$t->location('editar vacante vuelve al detalle', $response, '/vacancies/4');
$t->same('vacante editada título', 'Analista de Datos Senior', (string) dbValue($pdo, 'SELECT title FROM vacancies WHERE id = 4'));
$t->same('vacante editada cierre', '2026-11-25', (string) dbValue($pdo, 'SELECT closing_date FROM vacancies WHERE id = 4'));
$t->same('vacante editada editor', 1, (int) dbValue($pdo, 'SELECT updated_by FROM vacancies WHERE id = 4'));

$response = $admin->post('/vacancies/4', [
    '_token' => $token,
    'title' => 'No debe guardarse',
    'description' => '',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-11-20',
    'closing_date' => '2026-11-01',
]);
$t->status('editar vacante con fechas invertidas', $response, 422);
$t->same('edición inválida conserva título', 'Analista de Datos Senior', (string) dbValue($pdo, 'SELECT title FROM vacancies WHERE id = 4'));

$response = $admin->post('/vacancies/4/close', ['_token' => $token]);
$t->status('cerrar vacante', $response, 302);
$t->location('cerrar vacante vuelve al detalle', $response, '/vacancies/4');
$t->same('vacante cerrada en base', 'closed', (string) dbValue($pdo, 'SELECT status FROM vacancies WHERE id = 4'));
$t->same('cierre fija fecha real', date('Y-m-d'), (string) dbValue($pdo, 'SELECT closing_date FROM vacancies WHERE id = 4'));

$audit = lastAudit($pdo);
$t->same('auditoría cierre vacante', 'vacancy.closed', $audit['action'] ?? null);
$t->same('auditoría cierre entidad', 'vacancy', $audit['entity_type'] ?? null);
$t->same('auditoría cierre id', 4, (int) ($audit['entity_id'] ?? 0));
$t->same('auditoría cierre metadata', ['status' => 'closed'], auditMetadata($audit));
$t->same('auditoría total tras cierre', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$closedAt = dbValue($pdo, 'SELECT closing_date FROM vacancies WHERE id = 4');
$response = $admin->post('/vacancies/4/close', ['_token' => $token]);
$t->status('cerrar vacante ya cerrada redirige', $response, 302);
$t->same('segundo cierre no cambia fecha', $closedAt, dbValue($pdo, 'SELECT closing_date FROM vacancies WHERE id = 4'));
$t->same('segundo cierre no audita', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/vacancies/4/edit');
$t->status('editar vacante cerrada redirige', $response, 302);
$t->location('editar vacante cerrada vuelve al detalle', $response, '/vacancies/4');

$response = $admin->post('/vacancies/4', [
    '_token' => $token,
    'title' => 'Intento sobre vacante cerrada',
    'description' => '',
    'department_id' => '3',
    'position_id' => '4',
    'opening_date' => '2026-10-20',
    'closing_date' => '2026-11-30',
]);
$t->status('actualizar vacante cerrada redirige', $response, 302);
$t->same('vacante cerrada conserva título', 'Analista de Datos Senior', (string) dbValue($pdo, 'SELECT title FROM vacancies WHERE id = 4'));
$t->same('vacante cerrada no audita', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/vacancies/4');
$t->status('detalle de vacante cerrada', $response, 200);
$t->contains('detalle cerrado muestra aviso', $response, 'Esta vacante está cerrada');
$t->contains('detalle cerrado muestra estado', $response, 'Cerrada');

$response = $admin->post('/vacancies/999/close', ['_token' => $token]);
$t->status('cerrar vacante inexistente 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Candidatos: lectura, filtros y formularios');

$response = $admin->get('/candidates');
$t->status('listado de candidatos', $response, 200);
$t->same('resumen candidatos demo', ['5', '5', '1'], cards($response));
$t->same('listado candidatos filas', 5, tableRows($response));
$t->containsText('listado candidatos paginación', $response, 'Mostrando 1–5 de 5 candidatos');
$t->contains('listado candidatos Lucía', $response, 'Lucía');
$t->contains('listado candidatos Juan', $response, 'Juan');

$response = $admin->get('/candidates?q=' . rawurlencode('Lucía'));
$t->status('filtro candidato por nombre', $response, 200);
$t->same('filtro candidato por nombre filas', 1, tableRows($response));
$t->same('filtro candidato por nombre resumen', ['1', '1', '1'], cards($response));

$response = $admin->get('/candidates?q=maria.lopez%40example.test');
$t->status('filtro candidato por correo', $response, 200);
$t->same('filtro candidato por correo filas', 1, tableRows($response));

$response = $admin->get('/candidates?q=nadie');
$t->status('filtro candidato sin resultados', $response, 200);
$t->same('filtro candidato sin resultados filas', 0, tableRows($response));
$t->contains('filtro candidato sin resultados vacío', $response, 'No hay candidatos que coincidan');

$response = $admin->get('/candidates?q=' . rawurlencode('<script>alert(1)</script>'));
$t->status('búsqueda de candidato con HTML', $response, 200);
$t->contains('búsqueda de candidato escapa HTML', $response, '&lt;script&gt;alert(1)&lt;/script&gt;');
$t->notContains('búsqueda de candidato no ejecuta HTML', $response, '<script>alert(1)</script>');

$response = $admin->get('/candidates/create');
$t->status('formulario nuevo candidato', $response, 200);
$t->contains('formulario candidato título', $response, 'Nuevo candidato');

$response = $admin->get('/candidates/1');
$t->status('ficha candidato con postulaciones', $response, 200);
$t->contains('ficha candidato nombre', $response, 'Juan Pérez');
$t->same('ficha candidato postulaciones', 2, tableRows($response));
$t->contains('ficha candidato vacante backend', $response, 'Desarrollador Backend');
$t->contains('ficha candidato vacante ventas', $response, 'Ejecutivo de Ventas');

$response = $admin->get('/candidates/4');
$t->status('ficha candidata seleccionada', $response, 200);
$t->contains('ficha candidata nombre', $response, 'Lucía Fernández');
$t->contains('ficha candidata estado', $response, 'Seleccionada');

$response = $admin->get('/candidates/1/edit');
$t->status('formulario editar candidato', $response, 200);
$t->contains('editar candidato título', $response, 'Editar candidato');
$t->contains('editar candidato valor', $response, 'Juan');

$response = $admin->get('/candidates/999');
$t->status('candidato inexistente 404', $response, 404);
$response = $admin->get('/candidates/999/edit');
$t->status('editar candidato inexistente 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Candidatos: validaciones, altas y edición');

$response = $admin->post('/candidates', [
    '_token' => $token,
    'first_name' => '  Ana ',
    'last_name' => 'Torres',
    'email' => ' Ana.Torres@Example.Test ',
    'phone' => ' +1 555 0202 ',
    'notes' => ' Candidata de prueba. ',
]);
$t->status('crear candidato válido', $response, 302);
$t->location('crear candidato abre la ficha', $response, '/candidates/6');

$candidate = dbRow($pdo, 'SELECT * FROM candidates WHERE id = 6');
$t->ok('candidato creado en base', $candidate !== null);
$t->same('candidato nombre normalizado', 'Ana', $candidate['first_name'] ?? null);
$t->same('candidato apellido normalizado', 'Torres', $candidate['last_name'] ?? null);
$t->same('candidato correo en minúsculas', 'ana.torres@example.test', $candidate['email'] ?? null);
$t->same('candidato teléfono normalizado', '+1 555 0202', $candidate['phone'] ?? null);
$t->same('candidato observaciones', 'Candidata de prueba.', $candidate['notes'] ?? null);
$t->same('candidato creador', 1, (int) ($candidate['created_by'] ?? 0));
$t->same('crear candidato no audita', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/candidates', [
    '_token' => $token,
    'first_name' => 'Duplicada',
    'last_name' => 'Torres',
    'email' => 'ANA.TORRES@example.test',
    'phone' => '',
    'notes' => '',
]);
$t->status('correo de candidato duplicado', $response, 422);
$t->contains('correo duplicado muestra error', $response, 'Ya existe un candidato con ese correo electrónico.');
$t->same('correo duplicado no crece', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));

$response = $admin->post('/candidates', [
    '_token' => $token,
    'first_name' => '',
    'last_name' => 'Sin nombre',
    'email' => 'sin.nombre@example.test',
    'phone' => '',
    'notes' => '',
]);
$t->status('candidato sin nombre rechazado', $response, 422);
$t->contains('candidato sin nombre muestra error', $response, 'El campo nombres es obligatorio.');

$response = $admin->post('/candidates', [
    '_token' => $token,
    'first_name' => 'Correo',
    'last_name' => 'Inválido',
    'email' => 'no-es-correo',
    'phone' => '',
    'notes' => '',
]);
$t->status('candidato con correo inválido', $response, 422);
$t->contains('correo inválido muestra error', $response, 'debe ser un correo electrónico válido');

$response = $admin->post('/candidates', [
    '_token' => $token,
    'first_name' => 'Teléfono',
    'last_name' => 'Inválido',
    'email' => 'telefono.invalido@example.test',
    'phone' => 'abc',
    'notes' => '',
]);
$t->status('candidato con teléfono inválido', $response, 422);
$t->contains('teléfono inválido muestra error', $response, 'tiene un formato no válido');

$response = $admin->post('/candidates', [
    '_token' => $token,
    'first_name' => 'Notas',
    'last_name' => 'Largas',
    'email' => 'notas.largas@example.test',
    'phone' => '',
    'notes' => str_repeat('x', 1001),
]);
$t->status('candidato con observaciones largas', $response, 422);
$t->contains('observaciones largas muestran error', $response, 'no debe superar los 1000 caracteres');

$response = $admin->post('/candidates/6', [
    '_token' => $token,
    'first_name' => 'Ana',
    'last_name' => 'Torres',
    'email' => 'maria.lopez@example.test',
    'phone' => '+1 555 0202',
    'notes' => '',
]);
$t->status('editar candidato con correo duplicado', $response, 422);
$t->contains('editar correo duplicado muestra error', $response, 'Ya existe un candidato con ese correo electrónico.');
$t->same('edición duplicada conserva correo', 'ana.torres@example.test', (string) dbValue($pdo, 'SELECT email FROM candidates WHERE id = 6'));

$response = $admin->post('/candidates/6', [
    '_token' => $token,
    'first_name' => 'Ana',
    'last_name' => 'Torres',
    'email' => 'ana.torres@example.test',
    'phone' => '+1 555 0202',
    'notes' => '<script>alert("xss")</script>',
]);
$t->status('editar candidato con HTML', $response, 302);
$t->same('candidato conserva correo', 'ana.torres@example.test', (string) dbValue($pdo, 'SELECT email FROM candidates WHERE id = 6'));
$t->same('candidato guarda HTML sin escapar', '<script>alert("xss")</script>', (string) dbValue($pdo, 'SELECT notes FROM candidates WHERE id = 6'));
$t->same('candidato editor', 1, (int) dbValue($pdo, 'SELECT updated_by FROM candidates WHERE id = 6'));

$response = $admin->get('/candidates/6');
$t->status('ficha candidato con HTML', $response, 200);
$t->contains('observación XSS escapada', $response, '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
$t->notContains('observación XSS no se ejecuta', $response, '<script>alert("xss")</script>');

// ---------------------------------------------------------------------------
$t->section('Postulaciones: lectura, filtros y formularios');

$response = $admin->get('/applications');
$t->status('listado de postulaciones', $response, 200);
$t->same('resumen postulaciones demo', ['6', '4', '1', '1'], cards($response));
$t->same('listado postulaciones filas', 6, tableRows($response));
$t->containsText('listado postulaciones paginación', $response, 'Mostrando 1–6 de 6 postulaciones');

$response = $admin->get('/applications?status=received');
$t->status('filtro postulaciones recibidas', $response, 200);
$t->same('filtro recibidas filas', 2, tableRows($response));
$t->same('filtro recibidas resumen', ['2', '2', '0', '0'], cards($response));

$response = $admin->get('/applications?status=screening');
$t->status('filtro postulaciones en evaluación', $response, 200);
$t->same('filtro en evaluación filas', 1, tableRows($response));

$response = $admin->get('/applications?status=selected');
$t->status('filtro postulaciones seleccionadas', $response, 200);
$t->same('filtro seleccionadas filas', 1, tableRows($response));
$t->same('filtro seleccionadas resumen', ['1', '0', '1', '1'], cards($response));

$response = $admin->get('/applications?status=rejected');
$t->status('filtro postulaciones rechazadas', $response, 200);
$t->same('filtro rechazadas filas', 1, tableRows($response));

$response = $admin->get('/applications?vacancy_id=1');
$t->status('filtro postulaciones por vacante', $response, 200);
$t->same('filtro por vacante 1 filas', 2, tableRows($response));
$t->same('filtro por vacante 1 resumen', ['2', '2', '0', '0'], cards($response));

$response = $admin->get('/applications?vacancy_id=2');
$t->status('filtro postulaciones por vacante cerrada', $response, 200);
$t->same('filtro por vacante 2 filas', 2, tableRows($response));
$t->same('filtro por vacante 2 seleccionadas', ['2', '1', '1', '1'], cards($response));

$response = $admin->get('/applications?q=' . rawurlencode('Lucía'));
$t->status('filtro postulaciones por candidata', $response, 200);
$t->same('filtro por candidata filas', 1, tableRows($response));
$t->same('filtro por candidata resumen', ['1', '0', '1', '1'], cards($response));

$response = $admin->get('/applications?q=Desarrollador');
$t->status('filtro postulaciones por vacante en búsqueda', $response, 200);
$t->same('filtro por vacante en búsqueda filas', 2, tableRows($response));

$response = $admin->get('/applications?q=nadie');
$t->status('filtro postulaciones sin resultados', $response, 200);
$t->same('filtro postulaciones sin resultados filas', 0, tableRows($response));
$t->contains('filtro postulaciones sin resultados vacío', $response, 'No hay postulaciones que coincidan');

$response = $admin->get('/applications?status=inventado');
$t->status('estado de postulación inválido en filtro', $response, 200);
$t->same('estado inválido en filtro se ignora', 6, tableRows($response));

$response = $admin->get('/applications?vacancy_id=999');
$t->status('filtro de postulación por vacante inexistente', $response, 200);
$t->same('vacante inexistente en filtro no devuelve filas', 0, tableRows($response));
$t->contains('vacante inexistente en filtro muestra vacío', $response, 'No hay postulaciones que coincidan');

$response = $admin->get('/applications/create');
$t->status('formulario nueva postulación', $response, 200);
$t->same('formulario postulación candidatos', 7, count(selectOptions($response, 'candidate_id')));
$t->same('formulario postulación vacantes abiertas', 3, count(selectOptions($response, 'vacancy_id')));

$response = $admin->get('/applications/create?candidate_id=6&vacancy_id=1');
$t->status('formulario postulación con candidato preseleccionado', $response, 200);
$t->contains('formulario preselecciona candidata', $response, 'Torres, Ana');
$t->contains('formulario preselecciona vacante', $response, 'Desarrollador Backend');
$t->same('formulario preselecciona candidato', ['Torres, Ana · ana.torres@example.test'], selectedOptions($response, 'candidate_id'));
$t->ok('formulario preselecciona vacante 1', str_contains(implode('|', selectedOptions($response, 'vacancy_id')), 'Desarrollador Backend'));

$response = $admin->get('/applications/1');
$t->status('detalle postulación recibida', $response, 200);
$t->contains('postulación recibida estado', $response, 'Recibida');
$t->contains('postulación recibida transición evaluación', $response, 'Pasar a evaluación');
$t->contains('postulación recibida transición rechazo', $response, 'Rechazar');
$t->notContains('postulación recibida sin alta', $response, 'Dar de alta como empleado');

$response = $admin->get('/applications/2');
$t->status('detalle postulación en evaluación', $response, 200);
$t->contains('postulación en evaluación transición entrevista', $response, 'Pasar a entrevista');

$response = $admin->get('/applications/3');
$t->status('detalle postulación en entrevista', $response, 200);
$t->contains('postulación en entrevista transición selección', $response, 'Marcar como seleccionada');

$response = $admin->get('/applications/4');
$t->status('detalle postulación seleccionada', $response, 200);
$t->contains('postulación seleccionada estado', $response, 'Seleccionada');
$t->contains('postulación seleccionada alta', $response, 'Dar de alta como empleado');
$t->contains('postulación seleccionada estado final', $response, 'No admite más cambios');

$response = $admin->get('/applications/6');
$t->status('detalle postulación rechazada', $response, 200);
$t->contains('postulación rechazada estado', $response, 'Rechazada');
$t->contains('postulación rechazada cerrada', $response, 'no se puede reabrir');

$response = $admin->get('/applications/4/hire');
$t->status('formulario alta guiada', $response, 200);
$t->contains('alta guiada título', $response, 'Alta guiada de empleado');
$t->same('alta guiada precarga nombre', 'Lucía', inputValue($response, 'first_name'));
$t->same('alta guiada precarga apellido', 'Fernández', inputValue($response, 'last_name'));
$t->same('alta guiada precarga correo', 'lucia.fernandez@example.test', inputValue($response, 'email'));
$t->contains('alta guiada precarga departamento', $response, 'Recursos Humanos');
$t->contains('alta guiada precarga cargo', $response, 'Analista de RR. HH.');
$t->same('alta guiada departamentos activos', 6, count(selectOptions($response, 'department_id')));
$t->same('alta guiada cargos activos', 8, count(selectOptions($response, 'position_id')));

$response = $admin->get('/applications/1/hire');
$t->status('alta desde postulación no seleccionada redirige', $response, 302);
$t->location('alta no seleccionada vuelve al detalle', $response, '/applications/1');

$response = $admin->get('/applications/6/hire');
$t->status('alta desde postulación rechazada redirige', $response, 302);
$t->location('alta rechazada vuelve al detalle', $response, '/applications/6');

$response = $admin->get('/applications/999');
$t->status('postulación inexistente 404', $response, 404);
$response = $admin->get('/applications/999/hire');
$t->status('alta de postulación inexistente 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Postulaciones: altas y validaciones');

$page = $admin->get('/applications/create');
$token = csrfToken($page->body);

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => '6',
    'vacancy_id' => '1',
    'notes' => 'Postulación de prueba.',
]);
$t->status('crear postulación válida', $response, 302);
$t->location('crear postulación abre el detalle', $response, '/applications/7');

$application = dbRow($pdo, 'SELECT * FROM applications WHERE id = 7');
$t->ok('postulación creada en base', $application !== null);
$t->same('postulación candidato', 6, (int) ($application['candidate_id'] ?? 0));
$t->same('postulación vacante', 1, (int) ($application['vacancy_id'] ?? 0));
$t->same('postulación nace recibida', 'received', $application['status'] ?? null);
$t->same('postulación observaciones', 'Postulación de prueba.', $application['notes'] ?? null);
$t->same('postulación creador', 1, (int) ($application['created_by'] ?? 0));
$t->same('postulación editor inicial', 1, (int) ($application['updated_by'] ?? 0));
$t->same('postulación sin empleado', null, $application['employee_id'] ?? null);

$audit = lastAudit($pdo);
$t->same('auditoría alta postulación', 'application.created', $audit['action'] ?? null);
$t->same('auditoría alta postulación entidad', 'application', $audit['entity_type'] ?? null);
$t->same('auditoría alta postulación id', 7, (int) ($audit['entity_id'] ?? 0));
$t->same('auditoría alta postulación metadata', ['status' => 'received'], auditMetadata($audit));
$t->same('auditoría total tras alta postulación', 7, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => '6',
    'vacancy_id' => '1',
    'notes' => 'Duplicada.',
]);
$t->status('postulación duplicada rechazada', $response, 422);
$t->contains('postulación duplicada muestra error', $response, 'Ya existe una postulación de este candidato para esa vacante.');
$t->same('postulación duplicada no crece', 7, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => '6',
    'vacancy_id' => '4',
    'notes' => 'Vacante cerrada.',
]);
$t->status('postulación a vacante cerrada rechazada', $response, 422);
$t->contains('vacante cerrada muestra error', $response, 'Solo se aceptan postulaciones para vacantes abiertas.');
$t->same('postulación a cerrada no crece', 7, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => '999',
    'vacancy_id' => '1',
    'notes' => '',
]);
$t->status('postulación con candidato inexistente', $response, 422);
$t->contains('candidato inexistente muestra error', $response, 'El candidato seleccionado no existe.');

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => '6',
    'vacancy_id' => '999',
    'notes' => '',
]);
$t->status('postulación con vacante inexistente', $response, 422);
$t->contains('vacante inexistente muestra error', $response, 'La vacante seleccionada no existe.');

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => 'abc',
    'vacancy_id' => '1',
    'notes' => '',
]);
$t->status('postulación con candidato no entero', $response, 422);
$t->contains('candidato no entero muestra error', $response, 'El campo candidato debe ser un número entero.');

$response = $admin->post('/applications', [
    '_token' => $token,
    'candidate_id' => '6',
    'vacancy_id' => '1',
    'notes' => str_repeat('x', 1001),
]);
$t->status('postulación con observaciones largas', $response, 422);
$t->contains('postulación observaciones largas', $response, 'no debe superar los 1000 caracteres');

// ---------------------------------------------------------------------------
$t->section('Postulaciones: transiciones válidas y finales');

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'selected',
    'notes' => 'Salto inválido.',
]);
$t->status('salto de recibida a seleccionada redirige', $response, 302);
$t->location('salto inválido vuelve al detalle', $response, '/applications/7');
$t->same('salto inválido conserva estado', 'received', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 7'));
$t->same('salto inválido no audita', 7, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'screening',
    'notes' => 'Pasa a evaluación.',
]);
$t->status('recibida a evaluación', $response, 302);
$t->same('postulación pasa a evaluación', 'screening', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 7'));
$t->same('postulación guarda observación', 'Pasa a evaluación.', (string) dbValue($pdo, 'SELECT notes FROM applications WHERE id = 7'));
$audit = lastAudit($pdo);
$t->same('auditoría cambio a evaluación', 'application.status_changed', $audit['action'] ?? null);
$t->same('auditoría cambio a evaluación metadata', ['status' => 'screening'], auditMetadata($audit));
$t->same('auditoría total tras evaluación', 8, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'screening',
    'notes' => 'Repetido.',
]);
$t->status('repetir estado actual redirige', $response, 302);
$t->same('repetir estado no cambia observación', 'Pasa a evaluación.', (string) dbValue($pdo, 'SELECT notes FROM applications WHERE id = 7'));
$t->same('repetir estado no audita', 8, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'selected',
    'notes' => 'Salto inválido desde evaluación.',
]);
$t->status('salto de evaluación a seleccionada redirige', $response, 302);
$t->same('salto desde evaluación conserva estado', 'screening', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 7'));
$t->same('salto desde evaluación no audita', 8, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'interview',
    'notes' => 'Pasa a entrevista.',
]);
$t->status('evaluación a entrevista', $response, 302);
$t->same('postulación pasa a entrevista', 'interview', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 7'));
$t->same('auditoría cambio a entrevista', ['status' => 'interview'], auditMetadata(lastAudit($pdo)));
$t->same('auditoría total tras entrevista', 9, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'selected',
    'notes' => '<script>alert("app")</script>',
]);
$t->status('entrevista a seleccionada', $response, 302);
$t->same('postulación pasa a seleccionada', 'selected', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 7'));
$t->same('postulación seleccionada sin empleado', null, dbValue($pdo, 'SELECT employee_id FROM applications WHERE id = 7'));
$t->same('postulación guarda observación HTML', '<script>alert("app")</script>', (string) dbValue($pdo, 'SELECT notes FROM applications WHERE id = 7'));
$t->same('auditoría cambio a seleccionada', ['status' => 'selected'], auditMetadata(lastAudit($pdo)));
$t->same('auditoría total tras selección', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/applications/7');
$t->status('detalle postulación seleccionada', $response, 200);
$t->contains('postulación seleccionada ofrece alta', $response, 'Dar de alta como empleado');
$t->contains('observación de postulación escapada', $response, '&lt;script&gt;alert(&quot;app&quot;)&lt;/script&gt;');
$t->notContains('observación de postulación no se ejecuta', $response, '<script>alert("app")</script>');

$response = $admin->post('/applications/7/status', [
    '_token' => $token,
    'status' => 'rejected',
    'notes' => 'Intento posterior.',
]);
$t->status('seleccionada a rechazada redirige', $response, 302);
$t->same('seleccionada es final', 'selected', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 7'));
$t->same('cambio sobre final no audita', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/1/status', [
    '_token' => $token,
    'status' => 'rejected',
    'notes' => 'No cumple requisitos.',
]);
$t->status('recibida a rechazada', $response, 302);
$t->same('postulación 1 rechazada', 'rejected', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 1'));
$t->same('auditoría rechazo postulación', ['status' => 'rejected'], auditMetadata(lastAudit($pdo)));
$t->same('auditoría total tras rechazo', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/1/status', [
    '_token' => $token,
    'status' => 'screening',
    'notes' => 'Reabrir.',
]);
$t->status('rechazada a evaluación redirige', $response, 302);
$t->same('rechazada es final', 'rejected', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 1'));
$t->same('reapertura no audita', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/applications/5/status', [
    '_token' => $token,
    'status' => 'inventado',
    'notes' => '',
]);
$t->status('estado de postulación inválido redirige', $response, 302);
$t->same('estado inválido conserva recibida', 'received', (string) dbValue($pdo, 'SELECT status FROM applications WHERE id = 5'));
$t->same('estado inválido no audita', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/applications?status=selected');
$t->status('seleccionadas antes del alta', $response, 200);
$t->same('seleccionadas antes del alta filas', 2, tableRows($response));
$t->same('seleccionadas antes del alta pendientes', ['2', '0', '2', '2'], cards($response));

$response = $admin->get('/applications');
$t->status('listado tras transiciones', $response, 200);
$t->same('resumen tras transiciones', ['7', '3', '2', '2'], cards($response));

// ---------------------------------------------------------------------------
$t->section('Alta guiada: validaciones y creación de empleado');

$response = $admin->get('/applications/7/hire');
$t->status('formulario alta guiada de postulación 7', $response, 200);
$t->same('alta guiada 7 precarga nombre', 'Ana', inputValue($response, 'first_name'));
$t->same('alta guiada 7 precarga apellido', 'Torres', inputValue($response, 'last_name'));
$t->same('alta guiada 7 precarga correo', 'ana.torres@example.test', inputValue($response, 'email'));
$t->same('alta guiada 7 precarga teléfono', '+1 555 0202', inputValue($response, 'phone'));
$t->ok('alta guiada 7 precarga departamento', str_contains(implode('|', selectedOptions($response, 'department_id')), 'Tecnología'));
$t->ok('alta guiada 7 precarga cargo', str_contains(implode('|', selectedOptions($response, 'position_id')), 'Desarrollador de Software'));
$t->same('alta guiada 7 empleados antes', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));

$page = $admin->get('/applications/7/hire');
$token = csrfToken($page->body);

$response = hirePost($admin, $token, 7, ['document' => '']);
$t->status('alta sin documento', $response, 422);
$t->contains('alta sin documento muestra error', $response, 'El campo documento es obligatorio.');

$response = hirePost($admin, $token, 7, ['document' => 'DEMO-0005']);
$t->status('alta con documento duplicado', $response, 422);
$t->contains('documento duplicado muestra error', $response, 'Ya existe un empleado con ese documento.');

$response = hirePost($admin, $token, 7, ['email' => 'ana.torres@example.test']);
$t->status('alta con correo duplicado', $response, 422);
$t->contains('correo duplicado de empleado muestra error', $response, 'Ya existe un empleado con ese correo electrónico.');

$response = hirePost($admin, $token, 7, ['first_name' => '']);
$t->status('alta sin nombre', $response, 422);
$t->contains('alta sin nombre muestra error', $response, 'El campo nombres es obligatorio.');

$response = hirePost($admin, $token, 7, ['email' => 'no-es-correo']);
$t->status('alta con correo inválido', $response, 422);
$t->contains('alta correo inválido muestra error', $response, 'debe ser un correo electrónico válido');

$response = hirePost($admin, $token, 7, ['hire_date' => '2026-02-30']);
$t->status('alta con fecha inválida', $response, 422);
$t->contains('alta fecha inválida muestra error', $response, 'debe ser una fecha válida');

$response = hirePost($admin, $token, 7, ['department_id' => '999']);
$t->status('alta con departamento inexistente', $response, 422);
$t->contains('alta departamento inexistente', $response, 'El departamento seleccionado no existe.');

$response = hirePost($admin, $token, 7, ['position_id' => '3']);
$t->status('alta con cargo de otro departamento', $response, 422);
$t->contains('alta cargo cruzado', $response, 'El cargo no pertenece al departamento seleccionado.');

$response = hirePost($admin, $token, 7, ['department_id' => '', 'position_id' => '']);
$t->status('alta sin departamento', $response, 422);
$t->contains('alta sin departamento muestra error', $response, 'El campo departamento es obligatorio.');

setDepartmentStatus($pdo, 5, 'inactive');
$response = hirePost($admin, $token, 7, ['department_id' => '5', 'position_id' => '7']);
$t->status('alta con departamento inactivo', $response, 422);
$t->contains('alta departamento inactivo', $response, 'El departamento seleccionado está inactivo.');
setDepartmentStatus($pdo, 5, 'active');

setPositionStatus($pdo, 7, 'inactive');
$response = hirePost($admin, $token, 7, ['department_id' => '5', 'position_id' => '7']);
$t->status('alta con cargo inactivo', $response, 422);
$t->contains('alta cargo inactivo', $response, 'El cargo seleccionado está inactivo.');
setPositionStatus($pdo, 7, 'active');

$t->same('fallos de alta no crean empleados', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('fallos de alta no vinculan postulación', null, dbValue($pdo, 'SELECT employee_id FROM applications WHERE id = 7'));
$t->same('fallos de alta no auditan', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = hirePost($admin, $token, 7, ['document' => ' rrhh-9001 ']);
$t->status('alta guiada válida', $response, 302);
$t->location('alta guiada abre la ficha del empleado', $response, '/employees/11');

$employee = dbRow($pdo, 'SELECT * FROM employees WHERE id = 11');
$t->ok('empleado creado en base', $employee !== null);
$t->same('empleado documento normalizado', 'RRHH-9001', $employee['document'] ?? null);
$t->same('empleado nombre', 'Ana', $employee['first_name'] ?? null);
$t->same('empleado apellido', 'Torres', $employee['last_name'] ?? null);
$t->same('empleado correo', 'ana.torres.candidata@example.test', $employee['email'] ?? null);
$t->same('empleado teléfono', '+1 555 0202', $employee['phone'] ?? null);
$t->same('empleado fecha ingreso', date('Y-m-d'), $employee['hire_date'] ?? null);
$t->same('empleado departamento', 3, (int) ($employee['department_id'] ?? 0));
$t->same('empleado cargo', 4, (int) ($employee['position_id'] ?? 0));
$t->same('empleado activo', 'active', $employee['status'] ?? null);
$t->same('empleado creador', 1, (int) ($employee['created_by'] ?? 0));

$t->same('postulación vinculada al empleado', 11, (int) dbValue($pdo, 'SELECT employee_id FROM applications WHERE id = 7'));
$audit = lastAudit($pdo);
$t->same('auditoría alta guiada', 'application.hired', $audit['action'] ?? null);
$t->same('auditoría alta guiada entidad', 'application', $audit['entity_type'] ?? null);
$t->same('auditoría alta guiada id', 7, (int) ($audit['entity_id'] ?? 0));
$t->same('auditoría alta guiada metadata', ['status' => 'selected'], auditMetadata($audit));
$t->same('auditoría total tras alta guiada', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/employees/11');
$t->status('ficha del empleado creado', $response, 200);
$t->contains('empleado creado nombre', $response, 'Ana Torres');
$t->contains('empleado creado documento', $response, 'RRHH-9001');
$t->contains('empleado creado departamento', $response, 'Tecnología');
$t->contains('empleado creado estado', $response, 'Activo');

$response = $admin->get('/applications/7');
$t->status('detalle postulación tras alta', $response, 200);
$t->contains('postulación enlaza empleado', $response, 'href="/employees/11"');
$t->contains('postulación informa empleado asociado', $response, 'ya tiene un empleado asociado');

$response = $admin->get('/applications/7/hire');
$t->status('repetir alta redirige', $response, 302);
$t->location('repetir alta vuelve al detalle', $response, '/applications/7');

$response = hirePost($admin, $token, 7);
$t->status('repetir alta por POST redirige', $response, 302);
$t->same('repetir alta no crea empleado', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('repetir alta no audita', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = hirePost($admin, $token, 1);
$t->status('alta de postulación no seleccionada redirige', $response, 302);
$t->same('alta no seleccionada no crea empleado', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('alta no seleccionada no audita', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/applications');
$t->status('listado tras alta guiada', $response, 200);
$t->same('resumen tras alta guiada', ['7', '3', '2', '1'], cards($response));

$response = $admin->get('/candidates/6');
$t->status('ficha candidata tras alta', $response, 200);
$t->same('candidata tiene una postulación', 1, tableRows($response));
$t->contains('candidata postulación seleccionada', $response, 'Seleccionada');
$t->contains('candidata enlaza empleado', $response, 'href="/employees/11"');

$response = $admin->post('/candidates/6', [
    '_token' => $token,
    'first_name' => 'Ana',
    'last_name' => 'Torres',
    'email' => 'ana.torres@example.test',
    'phone' => '+1 555 0202',
    'notes' => 'Actualizada tras la postulación.',
]);
$t->status('editar candidata después de postular', $response, 302);
$t->same('edición posterior conserva postulación', 1, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications WHERE candidate_id = 6'));
$t->same('edición posterior conserva empleado', 11, (int) dbValue($pdo, 'SELECT employee_id FROM applications WHERE id = 7'));

$response = $admin->get('/candidates/6');
$t->contains('ficha candidata observación actualizada', $response, 'Actualizada tras la postulación.');

// ---------------------------------------------------------------------------
$t->section('Paginación de los tres listados');

insertPaginationData($pdo);

$t->same('vacantes tras datos de paginación', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM vacancies'));
$t->same('candidatos tras datos de paginación', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));
$t->same('postulaciones tras datos de paginación', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));

$response = $admin->get('/vacancies');
$t->status('vacantes página 1', $response, 200);
$t->same('vacantes página 1 filas', 10, tableRows($response));
$t->containsText('vacantes página 1 paginación', $response, 'Mostrando 1–10 de 11 vacantes');
$t->same('vacantes resumen con datos temporales', ['11', '9', '2'], cards($response));

$response = $admin->get('/vacancies?page=2');
$t->status('vacantes página 2', $response, 200);
$t->same('vacantes página 2 filas', 1, tableRows($response));
$t->containsText('vacantes página 2 paginación', $response, 'Mostrando 11–11 de 11 vacantes');
$t->contains('vacantes página 2 última vacante', $response, 'Vacante de paginación 01');

$response = $admin->get('/vacancies?page=999');
$t->status('vacantes página fuera de rango', $response, 200);
$t->same('vacantes página fuera de rango filas', 1, tableRows($response));
$t->containsText('vacantes página fuera de rango se ajusta', $response, 'Mostrando 11–11 de 11 vacantes');

$response = $admin->get('/vacancies?page=0');
$t->status('vacantes página cero', $response, 200);
$t->same('vacantes página cero filas', 10, tableRows($response));
$t->containsText('vacantes página cero se ajusta', $response, 'Mostrando 1–10 de 11 vacantes');

$response = $admin->get('/vacancies?q=' . rawurlencode('Paginación'));
$t->status('vacantes filtradas por datos temporales', $response, 200);
$t->same('vacantes filtradas por paginación filas', 7, tableRows($response));
$t->same('vacantes filtradas por paginación resumen', ['7', '7', '0'], cards($response));

$response = $admin->get('/candidates');
$t->status('candidatos página 1', $response, 200);
$t->same('candidatos página 1 filas', 10, tableRows($response));
$t->containsText('candidatos página 1 paginación', $response, 'Mostrando 1–10 de 11 candidatos');
$t->same('candidatos resumen con datos temporales', ['11', '11', '2'], cards($response));

$response = $admin->get('/candidates?page=2');
$t->status('candidatos página 2', $response, 200);
$t->same('candidatos página 2 filas', 1, tableRows($response));
$t->containsText('candidatos página 2 paginación', $response, 'Mostrando 11–11 de 11 candidatos');
$t->contains('candidatos página 2 última candidata', $response, 'Paginación 5');

$response = $admin->get('/candidates?page=999');
$t->status('candidatos página fuera de rango', $response, 200);
$t->same('candidatos página fuera de rango filas', 1, tableRows($response));

$response = $admin->get('/candidates?q=' . rawurlencode('Paginación'));
$t->status('candidatos filtrados por datos temporales', $response, 200);
$t->same('candidatos filtrados por paginación filas', 5, tableRows($response));
$t->same('candidatos filtrados por paginación resumen', ['5', '5', '0'], cards($response));

$response = $admin->get('/applications');
$t->status('postulaciones página 1', $response, 200);
$t->same('postulaciones página 1 filas', 10, tableRows($response));
$t->containsText('postulaciones página 1 paginación', $response, 'Mostrando 1–10 de 12 postulaciones');
$t->same('postulaciones resumen con datos temporales', ['12', '8', '2', '1'], cards($response));

$response = $admin->get('/applications?page=2');
$t->status('postulaciones página 2', $response, 200);
$t->same('postulaciones página 2 filas', 2, tableRows($response));
$t->containsText('postulaciones página 2 paginación', $response, 'Mostrando 11–12 de 12 postulaciones');

$response = $admin->get('/applications?page=999');
$t->status('postulaciones página fuera de rango', $response, 200);
$t->same('postulaciones página fuera de rango filas', 2, tableRows($response));

$response = $admin->get('/applications?status=received');
$t->status('postulaciones recibidas con datos temporales', $response, 200);
$t->same('postulaciones recibidas filas', 6, tableRows($response));
$t->same('postulaciones recibidas resumen', ['6', '6', '0', '0'], cards($response));

$response = $admin->get('/applications?vacancy_id=3');
$t->status('postulaciones de ventas con datos temporales', $response, 200);
$t->same('postulaciones de ventas filas', 7, tableRows($response));
$t->same('postulaciones de ventas resumen', ['7', '6', '0', '0'], cards($response));

$response = $admin->get('/recruitment');
$t->status('resumen con datos temporales', $response, 200);
$t->same('resumen final de tarjetas', ['9', '11', '8', '1'], cards($response));

// ---------------------------------------------------------------------------
$t->section('Rol de RR. HH.');

$response = login($hr, 'rrhh@gestion-rrhh.test');
$t->status('login RR. HH.', $response, 302);
$t->location('login RR. HH. va al panel', $response, '/dashboard');

foreach ([
    '/dashboard',
    '/recruitment',
    '/vacancies',
    '/vacancies/1',
    '/vacancies/4',
    '/candidates',
    '/candidates/1',
    '/candidates/6',
    '/applications',
    '/applications/4',
    '/applications/7',
    '/applications/4/hire',
    '/employees/11',
] as $page) {
    $response = $hr->get($page);
    $t->status('RR. HH. accede a ' . $page, $response, 200);
}

$page = $hr->get('/candidates/6/edit');
$token = csrfToken($page->body);
$response = $hr->post('/candidates/6', [
    '_token' => $token,
    'first_name' => 'Ana',
    'last_name' => 'Torres',
    'email' => 'ana.torres@example.test',
    'phone' => '+1 555 0202',
    'notes' => 'Actualizada por RR. H. H.',
]);
$t->status('RR. HH. edita candidata', $response, 302);
$t->same('RR. HH. queda como editor', 2, (int) dbValue($pdo, 'SELECT updated_by FROM candidates WHERE id = 6'));
$t->same('edición de RR. HH. no audita', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

// ---------------------------------------------------------------------------
$t->section('Auditoría final y estado de la base');

$newAudits = dbRows($pdo, 'SELECT * FROM audit_logs WHERE id > 5 ORDER BY id');
$t->same('siete auditorías nuevas', 7, count($newAudits));
$expectedActions = [
    'vacancy.closed',
    'application.created',
    'application.status_changed',
    'application.status_changed',
    'application.status_changed',
    'application.status_changed',
    'application.hired',
];
$t->same('secuencia de auditorías', $expectedActions, array_map(
    static fn (array $audit): string => (string) $audit['action'],
    $newAudits
));

$metadataOnlyStatus = true;
foreach ($newAudits as $audit) {
    if (array_keys(auditMetadata($audit)) !== ['status']) {
        $metadataOnlyStatus = false;
        break;
    }

    $encoded = (string) $audit['metadata'];
    if (
        str_contains($encoded, 'employee')
        || str_contains($encoded, 'document')
        || str_contains($encoded, 'name')
        || str_contains($encoded, 'email')
        || str_contains($encoded, 'notes')
    ) {
        $metadataOnlyStatus = false;
        break;
    }
}
$t->ok('auditorías solo guardan estado', $metadataOnlyStatus);

$t->same('total final empleados', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('total final vacantes', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM vacancies'));
$t->same('total final candidatos', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));
$t->same('total final postulaciones', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));
$t->same('total final auditorías', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/recruitment');
$t->status('resumen final', $response, 200);
$t->contains('resumen final enlaza empleado', $response, 'href="/employees/11"');
$t->contains('resumen final conserva alta pendiente', $response, 'href="/applications/4/hire"');

$response = $admin->get('/applications?status=selected');
$t->status('seleccionadas finales', $response, 200);
$t->same('seleccionadas finales filas', 2, tableRows($response));
$t->same('seleccionadas finales pendientes', ['2', '0', '2', '1'], cards($response));

exit($t->summary());
