<?php

declare(strict_types=1);

/**
 * Verificación funcional HTTP del módulo Asistencia y permisos.
 *
 * Datos ficticios. La base debe estar restaurada con database/seed.sql antes de
 * ejecutar la prueba y debe restaurarse otra vez al terminar, porque el guion
 * crea y modifica registros. También requiere que la fecha del servidor sea
 * octubre de 2026, que es el período de los datos demo.
 *
 * Uso:
 *   & "$env:TEMP\opencode\php84\php.exe" tests\Functional\AttendanceHttpCheck.php
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
            'User-Agent: attendance-functional-check/1.0',
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
function tfootValues(HttpResponse $response): array
{
    return texts(xpath($response->body), '//tfoot//th');
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

function setEmployeeStatus(PDO $pdo, int $employeeId, string $status): void
{
    $statement = $pdo->prepare('UPDATE employees SET status = :status WHERE id = :id');
    $statement->execute(['status' => $status, 'id' => $employeeId]);
}

$t = new Checker();
$pdo = db();

if (date('Y-m') !== '2026-10') {
    fwrite(STDERR, "Este guion requiere que la fecha del servidor sea octubre de 2026.\n");
    exit(2);
}

if (
    (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records') !== 15
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests') !== 4
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

$t->same('asistencias demo', 15, (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records'));
$t->same('permisos demo', 4, (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests'));
$t->same('auditorías demo', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $anonymous->get('/attendance');
$t->status('anónimo /attendance redirige', $response, 302);
$t->location('anónimo /attendance va a login', $response, '/login');

$response = $anonymous->get('/attendance/report');
$t->status('anónimo /attendance/report redirige', $response, 302);
$t->location('anónimo reporte va a login', $response, '/login');

$response = $anonymous->get('/leave-requests');
$t->status('anónimo /leave-requests redirige', $response, 302);
$t->location('anónimo permisos va a login', $response, '/login');

$response = $anonymous->post('/leave-requests', []);
$t->status('anónimo POST /leave-requests redirige', $response, 302);
$t->location('anónimo POST permisos va a login', $response, '/login');

$response = $anonymous->post('/attendance', []);
$t->status('anónimo POST /attendance redirige', $response, 302);
$t->location('anónimo POST asistencia va a login', $response, '/login');

// ---------------------------------------------------------------------------
$t->section('Inicio de sesión y lectura de asistencia');

$response = login($admin, 'admin@gestion-rrhh.test');
$t->status('login admin', $response, 302);
$t->location('login admin va al panel', $response, '/dashboard');

$response = $admin->get('/dashboard');
$t->status('panel admin', $response, 200);
$t->contains('panel admin saluda al usuario', $response, 'Administrador Demo');
$t->contains('panel admin muestra rol', $response, 'Administrador');

$response = $admin->get('/attendance');
$t->status('listado de asistencia', $response, 200);
$t->contains('listado asistencia título', $response, 'Asistencia');
$t->contains('listado asistencia paginación inicial', $response, 'Mostrando 1–10');
$t->contains('listado asistencia total demo', $response, 'de 15 registros');
$t->same('listado asistencia filas página 1', 10, tableRows($response));
$cards = texts(xpath($response->body), '//p[contains(@class,"display-6")]');
$t->same('resumen asistencia demo', ['15', '10', '3', '2'], $cards);

$response = $admin->get('/attendance?page=2');
$t->status('asistencia página 2', $response, 200);
$t->contains('asistencia página 2 paginación', $response, 'Mostrando 11–15');
$t->same('asistencia página 2 filas', 5, tableRows($response));

$response = $admin->get('/attendance?page=999');
$t->status('asistencia página fuera de rango', $response, 200);
$t->contains('asistencia página fuera de rango se ajusta', $response, 'Mostrando 11–15');
$t->same('asistencia página fuera de rango filas', 5, tableRows($response));

$response = $admin->get('/attendance?page=0');
$t->status('asistencia página cero', $response, 200);
$t->contains('asistencia página cero se ajusta', $response, 'Mostrando 1–10');
$t->same('asistencia página cero filas', 10, tableRows($response));

$response = $admin->get('/attendance?status=late');
$t->status('filtro estado tardanza', $response, 200);
$t->same('filtro tardanza filas', 3, tableRows($response));
$t->same('filtro tardanza resumen', ['3', '0', '3', '0'], texts(xpath($response->body), '//p[contains(@class,"display-6")]'));

$response = $admin->get('/attendance?employee_id=1');
$t->status('filtro empleado 1', $response, 200);
$t->same('filtro empleado 1 filas', 3, tableRows($response));

$response = $admin->get('/attendance?date_from=2026-10-05&date_to=2026-10-06');
$t->status('filtro rango de fechas', $response, 200);
$t->same('filtro rango de fechas filas', 6, tableRows($response));

$response = $admin->get('/attendance?date_from=2026-10-06&date_to=2026-10-05');
$t->status('filtro rango invertido', $response, 200);
$t->same('filtro rango invertido se normaliza', 6, tableRows($response));

$response = $admin->get('/attendance?status=inventado');
$t->status('filtro estado inválido', $response, 200);
$t->contains('filtro estado inválido se ignora', $response, 'de 15 registros');

$response = $admin->get('/attendance?date_from=2026-02-30');
$t->status('filtro fecha inválida', $response, 200);
$t->contains('filtro fecha inválida se ignora', $response, 'de 15 registros');

$response = $admin->get('/attendance?employee_id=6');
$t->status('filtro empleado inactivo sin historial', $response, 200);
$t->same('empleado inactivo sin historial filas', 0, tableRows($response));
$t->contains('empleado inactivo sin historial vacío', $response, 'No hay registros que coincidan');
$t->contains('filtro ofrece empleados inactivos', $response, 'Ramírez, Luis');
$t->contains('filtro marca inactivo', $response, '(inactivo)');

$response = $admin->get('/attendance/create');
$t->status('formulario nueva asistencia', $response, 200);
$t->contains('formulario asistencia título', $response, 'Nuevo registro de asistencia');
$options = texts(xpath($response->body), '//select[@id="employee_id"]/option');
$t->same('formulario asistencia solo activos', 9, count($options));
$t->ok('formulario asistencia sin Luis inactivo', !str_contains(implode('|', $options), 'Luis'));
$t->ok('formulario asistencia sin Andrés inactivo', !str_contains(implode('|', $options), 'Andrés'));
$t->contains('formulario asistencia fecha máxima hoy', $response, 'max="' . date('Y-m-d') . '"');

$response = $admin->get('/attendance/13/edit');
$t->status('formulario editar ausencia', $response, 200);
$t->contains('editar asistencia muestra empleada', $response, 'Rodríguez, María');
$t->contains('editar asistencia no permite cambiar empleado', $response, 'El empleado no cambia al corregir un registro existente');
$selectedStatus = texts(xpath($response->body), '//select[@id="status"]/option[@selected]');
$t->same('editar asistencia conserva estado', ['Ausencia'], $selectedStatus);

$response = $admin->get('/attendance/999/edit');
$t->status('asistencia inexistente 404', $response, 404);

$response = $admin->get('/attendance');
$t->ok('cabecera nosniff', $response->header('x-content-type-options') === 'nosniff');
$t->ok('cabecera frame deny', $response->header('x-frame-options') === 'DENY');
$t->ok('cabecera CSP', str_contains((string) $response->header('content-security-policy'), "default-src 'self'"));

// ---------------------------------------------------------------------------
$t->section('Reporte mensual con datos demo');

$response = $admin->get('/attendance/report');
$t->status('reporte mensual por defecto', $response, 200);
$t->contains('reporte mes actual', $response, 'octubre 2026');
$t->contains('reporte rango del mes', $response, 'Del 01/10/2026 al 31/10/2026');
$t->same('reporte octubre totales demo', ['Totales', '15', '10', '3', '2', '3', '0', '2', '1'], tfootValues($response));
$t->same('reporte octubre filas resumen', 5, tableRows($response, 0));
$t->same('reporte octubre solicitudes', 3, tableRows($response, 1));
$t->contains('reporte muestra empleado del departamento', $response, 'Tecnología');

$response = $admin->get('/attendance/report?department_id=3');
$t->status('reporte filtrado por departamento', $response, 200);
$t->same('reporte Tecnología totales demo', ['Totales', '3', '1', '1', '1', '0', '0', '0', '0'], tfootValues($response));
$t->contains('reporte Tecnología empleado', $response, 'Pérez, Carlos');
$t->contains('reporte Tecnología sin permisos', $response, 'No hay solicitudes que inicien en el período seleccionado.');

$response = $admin->get('/attendance/report?month=2026-11');
$t->status('reporte noviembre', $response, 200);
$t->contains('reporte noviembre mes', $response, 'noviembre 2026');
$t->same('reporte noviembre totales demo', ['Totales', '0', '0', '0', '0', '1', '1', '0', '0'], tfootValues($response));
$t->same('reporte noviembre solicitudes', 1, tableRows($response, 1));

$response = $admin->get('/attendance/report?month=2026-09');
$t->status('reporte septiembre', $response, 200);
$t->contains('reporte septiembre sin actividad', $response, 'No hay actividad de asistencia ni permisos para el período y filtros seleccionados.');

$response = $admin->get('/attendance/report?month=2026-13');
$t->status('reporte mes inválido', $response, 200);
$t->contains('reporte mes inválido vuelve al actual', $response, 'octubre 2026');
$t->same('reporte mes inválido totales actuales', ['Totales', '15', '10', '3', '2', '3', '0', '2', '1'], tfootValues($response));

$response = $admin->get('/attendance/report?month=2026-10&department_id=999');
$t->status('reporte departamento inválido', $response, 200);
$t->same('reporte departamento inválido se ignora', ['Totales', '15', '10', '3', '2', '3', '0', '2', '1'], tfootValues($response));

// ---------------------------------------------------------------------------
$t->section('Permisos: lectura y filtros demo');

$response = $admin->get('/leave-requests');
$t->status('listado de permisos', $response, 200);
$t->same('resumen permisos demo', ['4', '1', '2', '1'], texts(xpath($response->body), '//p[contains(@class,"display-6")]'));
$t->same('listado permisos filas', 4, tableRows($response));
$t->contains('listado permisos muestra revisora', $response, 'Ana López');

$response = $admin->get('/leave-requests?status=pending');
$t->status('filtro permisos pendientes', $response, 200);
$t->same('filtro permisos pendientes filas', 1, tableRows($response));

$response = $admin->get('/leave-requests?employee_id=2');
$t->status('filtro permisos empleado 2', $response, 200);
$t->same('filtro permisos empleado 2 filas', 1, tableRows($response));

$response = $admin->get('/leave-requests?date_from=2026-10-19&date_to=2026-10-19');
$t->status('filtro permisos por solapamiento', $response, 200);
$t->same('filtro permisos por solapamiento filas', 1, tableRows($response));

$response = $admin->get('/leave-requests?date_from=2026-10-20&date_to=2026-10-21');
$t->status('filtro permisos por rango amplio', $response, 200);
$t->same('filtro permisos por rango amplio filas', 2, tableRows($response));

$response = $admin->get('/leave-requests/create');
$t->status('formulario nueva solicitud', $response, 200);
$options = texts(xpath($response->body), '//select[@id="employee_id"]/option');
$t->same('formulario solicitud solo activos', 9, count($options));
$t->contains('formulario solicitud informa estado pendiente', $response, 'La solicitud se crea en estado');

$response = $admin->get('/leave-requests/1');
$t->status('ficha solicitud pendiente', $response, 200);
$t->contains('ficha solicitud pendiente estado', $response, 'Pendiente');
$t->contains('ficha solicitud pendiente formulario revisión', $response, 'Registrar revisión');
$t->contains('ficha solicitud pendiente empleado', $response, 'Pérez, Carlos');

$response = $admin->get('/leave-requests/2');
$t->status('ficha solicitud aprobada', $response, 200);
$t->contains('ficha solicitud aprobada revisora', $response, 'Ana López');
$t->contains('ficha solicitud aprobada no reabre', $response, 'Las solicitudes resueltas no se reabren en el MVP.');

$response = $admin->get('/leave-requests/999');
$t->status('solicitud inexistente 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Protección CSRF');

$response = $admin->post('/attendance', [
    'employee_id' => '1',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('POST asistencia sin CSRF', $response, 403);
$t->same('sin CSRF no crea asistencia', 15, (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records'));

$response = $admin->post('/leave-requests', [
    'employee_id' => '5',
    'leave_type' => 'vacation',
    'start_date' => '2026-12-01',
    'end_date' => '2026-12-03',
    'reason' => 'Sin token.',
]);
$t->status('POST permiso sin CSRF', $response, 403);
$t->same('sin CSRF no crea permiso', 4, (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests'));

$response = $admin->post('/leave-requests/1/review', [
    'status' => 'approved',
    'review_notes' => 'Sin token.',
]);
$t->status('POST revisión sin CSRF', $response, 403);
$t->same('sin CSRF no revisa permiso', 'pending', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 1'));

// ---------------------------------------------------------------------------
$t->section('Asistencia: validaciones y altas');

$page = $admin->get('/attendance/create');
$token = csrfToken($page->body);

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '1',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('crear asistencia válida', $response, 302);
$t->location('crear asistencia vuelve al listado', $response, '/attendance');

$record = dbRow(
    $pdo,
    'SELECT * FROM attendance_records WHERE employee_id = 1 AND work_date = :date',
    ['date' => '2026-10-08']
);
$t->ok('asistencia creada en base', $record !== null);
$t->same('asistencia entrada normalizada', '08:00:00', $record['check_in'] ?? null);
$t->same('asistencia salida normalizada', '17:00:00', $record['check_out'] ?? null);
$t->same('asistencia estado guardado', 'present', $record['status'] ?? null);
$t->same('asistencia creador', 1, (int) ($record['created_by'] ?? 0));
$t->same('asistencia editor inicial', 1, (int) ($record['updated_by'] ?? 0));
$recordId = (int) ($record['id'] ?? 0);

$audit = lastAudit($pdo);
$t->same('auditoría alta asistencia', 'attendance.created', $audit['action'] ?? null);
$t->same('auditoría alta asistencia entidad', 'attendance_record', $audit['entity_type'] ?? null);
$t->same('auditoría alta asistencia id', $recordId, (int) ($audit['entity_id'] ?? 0));
$t->same('auditoría alta asistencia metadata', ['status' => 'present'], auditMetadata($audit));
$t->same('auditoría total tras alta', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '1',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('duplicado empleado y fecha', $response, 422);
$t->contains('duplicado muestra error', $response, 'Ya existe un registro de asistencia para ese empleado en esa fecha.');
$t->same('duplicado no crece', 16, (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records'));

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => date('Y-m-d', strtotime('+1 day')),
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('fecha futura rechazada', $response, 422);
$t->contains('fecha futura muestra error', $response, 'No se puede registrar asistencia para una fecha futura.');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '6',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('empleado inactivo rechazado en alta', $response, 422);
$t->contains('empleado inactivo muestra error', $response, 'Solo se puede registrar asistencia para empleados activos.');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '3',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'absent',
    'notes' => 'Cita médica.',
]);
$t->status('ausencia con horas rechazada', $response, 422);
$t->contains('ausencia con horas muestra error', $response, 'Una ausencia no debe registrar horas de entrada o salida.');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '3',
    'work_date' => '2026-10-08',
    'check_in' => '',
    'check_out' => '',
    'status' => 'absent',
    'notes' => '',
]);
$t->status('ausencia sin motivo rechazada', $response, 422);
$t->contains('ausencia sin motivo muestra error', $response, 'Describe el motivo de la ausencia.');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '3',
    'work_date' => '2026-10-08',
    'check_in' => '',
    'check_out' => '',
    'status' => 'absent',
    'notes' => 'Cita médica programada.',
]);
$t->status('crear ausencia válida', $response, 302);
$absent = dbRow(
    $pdo,
    'SELECT * FROM attendance_records WHERE employee_id = 3 AND work_date = :date',
    ['date' => '2026-10-08']
);
$t->ok('ausencia creada en base', $absent !== null);
$t->ok('ausencia sin entrada', $absent !== null && $absent['check_in'] === null);
$t->ok('ausencia sin salida', $absent !== null && $absent['check_out'] === null);
$t->same('ausencia estado', 'absent', $absent['status'] ?? null);
$t->same('auditoría ausencia', 'attendance.created', lastAudit($pdo)['action'] ?? null);
$t->same('auditoría ausencia metadata', ['status' => 'absent'], auditMetadata(lastAudit($pdo)));

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => '2026-10-08',
    'check_in' => '',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('presente sin entrada rechazado', $response, 422);
$t->contains('presente sin entrada muestra error', $response, 'La hora de entrada es obligatoria');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => '2026-10-08',
    'check_in' => '17:00',
    'check_out' => '08:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('salida anterior a entrada rechazada', $response, 422);
$t->contains('salida anterior muestra error', $response, 'La hora de salida no puede ser anterior a la hora de entrada.');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => '2026-02-30',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('fecha inválida rechazada', $response, 422);
$t->contains('fecha inválida muestra error', $response, 'debe ser una fecha válida');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => '2026-10-08',
    'check_in' => '25:99',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('hora inválida rechazada', $response, 422);
$t->contains('hora inválida muestra error', $response, 'debe ser una hora válida');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '999',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('empleado inexistente rechazado', $response, 422);
$t->contains('empleado inexistente muestra error', $response, 'El empleado seleccionado no existe.');

$response = $admin->post('/attendance', [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'inventado',
    'notes' => '',
]);
$t->status('estado inválido rechazado', $response, 422);
$t->contains('estado inválido muestra error', $response, 'no es válido');

// ---------------------------------------------------------------------------
$t->section('Asistencia: correcciones y edición');

$response = $admin->post('/attendance/' . $recordId, [
    '_token' => $token,
    'employee_id' => '2',
    'work_date' => '2026-10-08',
    'check_in' => '08:10',
    'check_out' => '17:05',
    'status' => 'late',
    'notes' => '<script>alert("xss")</script>',
]);
$t->status('corregir asistencia', $response, 302);
$t->location('corregir asistencia vuelve al listado', $response, '/attendance');

$updated = dbRow($pdo, 'SELECT * FROM attendance_records WHERE id = :id', ['id' => $recordId]);
$t->same('corrección no cambia empleado', 1, (int) ($updated['employee_id'] ?? 0));
$t->same('corrección guarda estado', 'late', $updated['status'] ?? null);
$t->same('corrección guarda entrada', '08:10:00', $updated['check_in'] ?? null);
$t->same('corrección guarda salida', '17:05:00', $updated['check_out'] ?? null);
$t->same('corrección guarda notas', '<script>alert("xss")</script>', $updated['notes'] ?? null);
$t->same('corrección editor', 1, (int) ($updated['updated_by'] ?? 0));
$t->same('auditoría corrección', 'attendance.updated', lastAudit($pdo)['action'] ?? null);
$t->same('auditoría corrección metadata', ['status' => 'late'], auditMetadata(lastAudit($pdo)));
$t->same('auditoría total tras corrección', 8, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/attendance?employee_id=1&date_from=2026-10-08&date_to=2026-10-08');
$t->status('listado con nota XSS', $response, 200);
$t->contains('nota XSS escapada', $response, '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
$t->notContains('nota XSS no se ejecuta', $response, '<script>alert("xss")</script>');

$response = $admin->post('/attendance/' . $recordId, [
    '_token' => $token,
    'employee_id' => '1',
    'work_date' => '2026-10-01',
    'check_in' => '08:10',
    'check_out' => '17:05',
    'status' => 'late',
    'notes' => '',
]);
$t->status('corrección a fecha duplicada', $response, 422);
$t->contains('corrección duplicada muestra error', $response, 'Ya existe un registro de asistencia para ese empleado en esa fecha.');
$t->same('corrección duplicada conserva fecha', '2026-10-08', (string) dbValue($pdo, 'SELECT work_date FROM attendance_records WHERE id = ' . $recordId));

$response = $admin->post('/attendance/999', [
    '_token' => $token,
    'employee_id' => '1',
    'work_date' => '2026-10-08',
    'check_in' => '08:00',
    'check_out' => '17:00',
    'status' => 'present',
    'notes' => '',
]);
$t->status('corrección inexistente 404', $response, 404);

$response = $admin->post('/attendance/15', [
    '_token' => $token,
    'employee_id' => '5',
    'work_date' => '2026-10-07',
    'check_in' => '08:00',
    'check_out' => '17:10',
    'status' => 'present',
    'notes' => 'Corrección administrativa.',
]);
$t->status('corregir registro de Ana', $response, 302);
$t->same('corrección Ana guarda salida', '17:10:00', (string) dbValue($pdo, 'SELECT check_out FROM attendance_records WHERE id = 15'));
$t->same('auditoría total tras segunda corrección', 9, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

setEmployeeStatus($pdo, 5, 'inactive');
$response = $admin->get('/attendance/15/edit');
$t->status('editar asistencia de empleado inactivo', $response, 200);
$t->contains('editar asistencia inactiva muestra empleada', $response, 'Torres, Ana');

$response = $admin->post('/attendance/15', [
    '_token' => $token,
    'employee_id' => '5',
    'work_date' => '2026-10-07',
    'check_in' => '08:00',
    'check_out' => '17:15',
    'status' => 'present',
    'notes' => 'Corrección con empleado inactivo.',
]);
$t->status('corregir asistencia de empleado inactivo', $response, 302);
$t->same('corrección inactiva guarda notas', 'Corrección con empleado inactivo.', (string) dbValue($pdo, 'SELECT notes FROM attendance_records WHERE id = 15'));
$t->same('corrección inactiva conserva empleado', 5, (int) dbValue($pdo, 'SELECT employee_id FROM attendance_records WHERE id = 15'));
$t->same('auditoría total tras corrección inactiva', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/attendance?employee_id=5');
$t->status('listar asistencia de empleado inactivo', $response, 200);
$t->contains('listado marca empleado inactivo', $response, 'Inactivo');
$t->same('listado empleado inactivo filas', 3, tableRows($response));

setEmployeeStatus($pdo, 5, 'active');

$response = $admin->get('/attendance');
$t->status('listado tras altas y correcciones', $response, 200);
$t->same('total asistencias tras cambios', 17, (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records'));
$t->same('resumen asistencias tras cambios', ['17', '10', '4', '3'], texts(xpath($response->body), '//p[contains(@class,"display-6")]'));
$t->contains('paginación tras cambios', $response, 'de 17 registros');

$response = $admin->get('/attendance?page=2');
$t->status('segunda página tras cambios', $response, 200);
$t->contains('segunda página tras cambios paginación', $response, 'Mostrando 11–17');
$t->same('segunda página tras cambios filas', 7, tableRows($response));

$response = $admin->get('/attendance/report');
$t->status('reporte tras cambios de asistencia', $response, 200);
$t->same('reporte octubre tras asistencia', ['Totales', '17', '10', '4', '3', '3', '0', '2', '1'], tfootValues($response));

// ---------------------------------------------------------------------------
$t->section('Permisos: validaciones y altas');

$page = $admin->get('/leave-requests/create');
$token = csrfToken($page->body);

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '5',
    'leave_type' => 'vacation',
    'start_date' => '2026-12-01',
    'end_date' => '2026-12-03',
    'reason' => 'Descanso de fin de año.',
]);
$t->status('crear permiso válido', $response, 302);
$t->location('crear permiso abre la ficha', $response, '/leave-requests/5');

$leave = dbRow($pdo, 'SELECT * FROM leave_requests WHERE id = 5');
$t->ok('permiso creado en base', $leave !== null);
$t->same('permiso nace pendiente', 'pending', $leave['status'] ?? null);
$t->ok('permiso sin revisor', $leave !== null && $leave['reviewed_by'] === null);
$t->same('permiso creador', 1, (int) ($leave['created_by'] ?? 0));
$t->same('auditoría no cambia al crear permiso', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '2',
    'leave_type' => 'personal',
    'start_date' => '2026-11-07',
    'end_date' => '2026-11-08',
    'reason' => 'Trámite posterior a las vacaciones.',
]);
$t->status('permiso contiguo permitido', $response, 302);
$t->same('permiso contiguo creado', 1, (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests WHERE employee_id = 2 AND start_date = :date', ['date' => '2026-11-07']));

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '2',
    'leave_type' => 'personal',
    'start_date' => '2026-11-05',
    'end_date' => '2026-11-07',
    'reason' => 'Se superpone.',
]);
$t->status('solapamiento con pendiente rechazado', $response, 422);
$t->contains('solapamiento pendiente muestra error', $response, 'Ya existe una solicitud pendiente o aprobada que se superpone');

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '1',
    'leave_type' => 'personal',
    'start_date' => '2026-10-15',
    'end_date' => '2026-10-15',
    'reason' => 'Se superpone con aprobada.',
]);
$t->status('solapamiento con aprobada rechazado', $response, 422);
$t->contains('solapamiento aprobada muestra error', $response, 'Ya existe una solicitud pendiente o aprobada que se superpone');

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '3',
    'leave_type' => 'other',
    'start_date' => '2026-10-21',
    'end_date' => '2026-10-23',
    'reason' => 'Traslado <b>autorizado</b>.',
]);
$t->status('solapamiento con rechazada permitido', $response, 302);
$t->same('solicitud sobre rechazada creada', 7, (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests'));
$t->same('solicitud sobre rechazada nace pendiente', 'pending', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 7'));

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '4',
    'leave_type' => 'sick',
    'start_date' => '2026-12-10',
    'end_date' => '2026-12-09',
    'reason' => 'Fechas invertidas.',
]);
$t->status('permiso con fin anterior rechazado', $response, 422);
$t->contains('permiso invertido muestra error', $response, 'La fecha final no puede ser anterior a la fecha inicial.');

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '6',
    'leave_type' => 'vacation',
    'start_date' => '2026-12-10',
    'end_date' => '2026-12-11',
    'reason' => 'Empleado inactivo.',
]);
$t->status('permiso de empleado inactivo rechazado', $response, 422);
$t->contains('permiso inactivo muestra error', $response, 'Solo se pueden crear solicitudes para empleados activos.');

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '4',
    'leave_type' => 'inventado',
    'start_date' => '2026-12-10',
    'end_date' => '2026-12-11',
    'reason' => 'Tipo inválido.',
]);
$t->status('tipo de permiso inválido rechazado', $response, 422);
$t->contains('tipo permiso inválido muestra error', $response, 'no es válido');

$response = $admin->post('/leave-requests', [
    '_token' => $token,
    'employee_id' => '4',
    'leave_type' => 'vacation',
    'start_date' => '2026-12-10',
    'end_date' => '2026-12-11',
    'reason' => '',
]);
$t->status('permiso sin motivo rechazado', $response, 422);
$t->contains('permiso sin motivo muestra error', $response, 'El campo motivo es obligatorio.');

$response = $admin->get('/leave-requests/7');
$t->status('ficha solicitud con motivo HTML', $response, 200);
$t->contains('motivo HTML escapado', $response, 'Traslado &lt;b&gt;autorizado&lt;/b&gt;.');
$t->notContains('motivo HTML no se interpreta', $response, 'Traslado <b>autorizado</b>.');

// ---------------------------------------------------------------------------
$t->section('Permisos: revisión única');

$response = $admin->post('/leave-requests/1/review', [
    '_token' => $token,
    'status' => 'approved',
    'review_notes' => 'Aprobado por disponibilidad.',
]);
$t->status('aprobar solicitud pendiente', $response, 302);
$t->location('aprobar solicitud vuelve a ficha', $response, '/leave-requests/1');
$t->same('solicitud aprobada en base', 'approved', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 1'));
$t->same('solicitud aprobada revisor', 1, (int) dbValue($pdo, 'SELECT reviewed_by FROM leave_requests WHERE id = 1'));
$t->ok('solicitud aprobada fecha revisión', dbValue($pdo, 'SELECT reviewed_at FROM leave_requests WHERE id = 1') !== null);
$t->same('solicitud aprobada observación', 'Aprobado por disponibilidad.', (string) dbValue($pdo, 'SELECT review_notes FROM leave_requests WHERE id = 1'));

$audit = lastAudit($pdo);
$t->same('auditoría aprobación', 'leave.approved', $audit['action'] ?? null);
$t->same('auditoría aprobación entidad', 'leave_request', $audit['entity_type'] ?? null);
$t->same('auditoría aprobación id', 1, (int) ($audit['entity_id'] ?? 0));
$t->same('auditoría aprobación metadata', ['status' => 'approved'], auditMetadata($audit));
$t->same('auditoría total tras aprobación', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$reviewedAt = dbValue($pdo, 'SELECT reviewed_at FROM leave_requests WHERE id = 1');
$response = $admin->post('/leave-requests/1/review', [
    '_token' => $token,
    'status' => 'rejected',
    'review_notes' => 'Intento de reapertura.',
]);
$t->status('revisión repetida redirige', $response, 302);
$t->location('revisión repetida vuelve a ficha', $response, '/leave-requests/1');
$t->same('revisión repetida no cambia estado', 'approved', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 1'));
$t->same('revisión repetida no cambia fecha', $reviewedAt, dbValue($pdo, 'SELECT reviewed_at FROM leave_requests WHERE id = 1'));
$t->same('revisión repetida no audita', 11, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/leave-requests/5/review', [
    '_token' => $token,
    'status' => 'rejected',
    'review_notes' => 'Carga de trabajo.',
]);
$t->status('rechazar solicitud pendiente', $response, 302);
$t->same('solicitud rechazada en base', 'rejected', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 5'));
$t->same('auditoría rechazo', 'leave.rejected', lastAudit($pdo)['action'] ?? null);
$t->same('auditoría rechazo metadata', ['status' => 'rejected'], auditMetadata(lastAudit($pdo)));
$t->same('auditoría total tras rechazo', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/leave-requests/2/review', [
    '_token' => $token,
    'status' => 'rejected',
    'review_notes' => 'No se debe permitir.',
]);
$t->status('revisar solicitud ya resuelta redirige', $response, 302);
$t->same('solicitud ya resuelta conserva estado', 'approved', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 2'));
$t->same('revisar solicitud resuelta no audita', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/leave-requests/6/review', [
    '_token' => $token,
    'status' => 'inventado',
    'review_notes' => 'Decisión inválida.',
]);
$t->status('decisión inválida redirige', $response, 302);
$t->same('decisión inválida conserva pendiente', 'pending', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 6'));
$t->same('decisión inválida no audita', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->post('/leave-requests/6/review', [
    '_token' => $token,
    'status' => 'approved',
    'review_notes' => str_repeat('x', 501),
]);
$t->status('observación de revisión demasiado larga', $response, 422);
$t->contains('observación larga muestra error', $response, 'La observación de revisión no debe superar los 500 caracteres.');
$t->same('observación larga conserva pendiente', 'pending', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 6'));
$t->same('observación larga no audita', 12, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

setEmployeeStatus($pdo, 2, 'inactive');
$response = $admin->get('/leave-requests/6');
$t->status('ficha solicitud de empleado inactivo', $response, 200);
$t->contains('ficha inactiva advierte', $response, 'El empleado está inactivo.');
$t->contains('ficha inactiva sigue pendiente', $response, 'Pendiente');

$response = $admin->post('/leave-requests/6/review', [
    '_token' => $token,
    'status' => 'approved',
    'review_notes' => 'Revisada pese a la baja del empleado.',
]);
$t->status('revisar solicitud de empleado inactivo', $response, 302);
$t->same('solicitud inactiva aprobada', 'approved', (string) dbValue($pdo, 'SELECT status FROM leave_requests WHERE id = 6'));
$t->same('auditoría aprobación inactiva', 'leave.approved', lastAudit($pdo)['action'] ?? null);
$t->same('auditoría total tras aprobación inactiva', 13, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));
setEmployeeStatus($pdo, 2, 'active');

$response = $admin->post('/leave-requests/999/review', [
    '_token' => $token,
    'status' => 'approved',
    'review_notes' => 'No existe.',
]);
$t->status('revisar solicitud inexistente 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Listados y reportes finales');

$response = $admin->get('/leave-requests');
$t->status('listado final de permisos', $response, 200);
$t->same('resumen final de permisos', ['7', '1', '4', '2'], texts(xpath($response->body), '//p[contains(@class,"display-6")]'));
$t->same('listado final de permisos filas', 7, tableRows($response));

$response = $admin->get('/leave-requests?status=approved');
$t->status('filtro final aprobadas', $response, 200);
$t->same('filtro final aprobadas filas', 4, tableRows($response));

$response = $admin->get('/leave-requests?status=rejected');
$t->status('filtro final rechazadas', $response, 200);
$t->same('filtro final rechazadas filas', 2, tableRows($response));

$response = $admin->get('/attendance/report?month=2026-10');
$t->status('reporte final octubre', $response, 200);
$t->same('reporte final octubre totales', ['Totales', '17', '10', '4', '3', '4', '1', '2', '1'], tfootValues($response));
$t->same('reporte final octubre solicitudes', 4, tableRows($response, 1));

$response = $admin->get('/attendance/report?month=2026-11');
$t->status('reporte final noviembre', $response, 200);
$t->same('reporte final noviembre totales', ['Totales', '0', '0', '0', '0', '2', '0', '2', '0'], tfootValues($response));

$response = $admin->get('/attendance/report?month=2026-12');
$t->status('reporte final diciembre', $response, 200);
$t->same('reporte final diciembre totales', ['Totales', '0', '0', '0', '0', '1', '0', '0', '1'], tfootValues($response));

$response = $admin->get('/attendance/report?month=2026-10&department_id=3');
$t->status('reporte final Tecnología', $response, 200);
$t->same('reporte final Tecnología totales', ['Totales', '3', '1', '1', '1', '0', '0', '0', '0'], tfootValues($response));

$response = $admin->get('/attendance/report?month=2026-11&department_id=3');
$t->status('reporte final Tecnología noviembre', $response, 200);
$t->same('reporte final Tecnología noviembre totales', ['Totales', '0', '0', '0', '0', '2', '0', '2', '0'], tfootValues($response));

$response = $admin->get('/attendance/report?month=2026-10&department_id=1');
$t->status('reporte final Administración', $response, 200);
$t->same('reporte final Administración totales', ['Totales', '3', '3', '0', '0', '0', '0', '0', '0'], tfootValues($response));

$response = $admin->get('/attendance/report?month=2026-10&department_id=5');
$t->status('reporte final Operaciones', $response, 200);
$t->same('reporte final Operaciones totales', ['Totales', '3', '2', '1', '0', '1', '0', '1', '0'], tfootValues($response));

// ---------------------------------------------------------------------------
$t->section('Rol de RR. HH. y auditoría final');

$response = login($hr, 'rrhh@gestion-rrhh.test');
$t->status('login RR. HH.', $response, 302);
$t->location('login RR. HH. va al panel', $response, '/dashboard');

$response = $hr->get('/dashboard');
$t->status('panel RR. HH.', $response, 200);
$t->contains('panel RR. HH. muestra rol', $response, 'Personal de RR. HH.');

foreach (['/attendance', '/attendance/create', '/attendance/report', '/leave-requests', '/leave-requests/create', '/leave-requests/1'] as $page) {
    $response = $hr->get($page);
    $t->status('RR. HH. accede a ' . $page, $response, 200);
}

$t->same('total final asistencias', 17, (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records'));
$t->same('total final permisos', 7, (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests'));
$t->same('total final auditorías', 13, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$newAudits = dbRows($pdo, 'SELECT * FROM audit_logs WHERE id > 5 ORDER BY id');
$t->same('ocho auditorías nuevas', 8, count($newAudits));
$expectedActions = [
    'attendance.created',
    'attendance.created',
    'attendance.updated',
    'attendance.updated',
    'attendance.updated',
    'leave.approved',
    'leave.rejected',
    'leave.approved',
];
$t->same('secuencia de auditorías', $expectedActions, array_map(
    static fn (array $audit): string => (string) $audit['action'],
    $newAudits
));

$metadataOnlyStatus = true;
foreach ($newAudits as $audit) {
    $metadata = auditMetadata($audit);
    if (array_keys($metadata) !== ['status']) {
        $metadataOnlyStatus = false;
        break;
    }
    $encoded = (string) $audit['metadata'];
    if (
        str_contains($encoded, 'employee')
        || str_contains($encoded, 'document')
        || str_contains($encoded, 'name')
        || str_contains($encoded, 'email')
    ) {
        $metadataOnlyStatus = false;
        break;
    }
}
$t->ok('auditorías sin datos personales', $metadataOnlyStatus);

$response = $admin->get('/attendance');
$t->status('listado final de asistencia', $response, 200);
$t->same('resumen final de asistencia', ['17', '10', '4', '3'], texts(xpath($response->body), '//p[contains(@class,"display-6")]'));

exit($t->summary());
