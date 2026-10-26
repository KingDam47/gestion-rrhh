<?php

declare(strict_types=1);

/**
 * Verificación funcional HTTP del módulo transversal Dashboard.
 *
 * Datos ficticios. La base debe estar restaurada con database/seed.sql antes de
 * ejecutar la prueba. El guion solo lee información: no crea ni modifica datos.
 *
 * Uso:
 *   & "$env:TEMP\opencode\php84\php.exe" tests\Functional\DashboardHttpCheck.php
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
            'User-Agent: dashboard-functional-check/1.0',
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

    return $client->post('/login', [
        '_token' => csrfToken($page->body),
        'email' => $email,
        'password' => 'password123',
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

/**
 * Enlaces internos únicos del documento, en orden de aparición.
 *
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

$t = new Checker();
$pdo = db();

if (date('Y-m') !== '2026-10') {
    fwrite(STDERR, "Este guion requiere que la fecha del servidor sea octubre de 2026.\n");
    exit(2);
}

if (
    (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees') !== 10
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records') !== 15
    || (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests') !== 4
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

$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

// ---------------------------------------------------------------------------
$t->section('Estado inicial y acceso anónimo');

$t->same('empleados activos demo', 8, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM employees WHERE status = :status',
    ['status' => 'active']
));
$t->same('empleados inactivos demo', 2, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM employees WHERE status = :status',
    ['status' => 'inactive']
));
$t->same('asistencias del mes demo', 15, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM attendance_records WHERE work_date BETWEEN :date_from AND :date_to',
    ['date_from' => $monthStart, 'date_to' => $monthEnd]
));
$t->same('tardanzas del mes demo', 3, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM attendance_records WHERE status = :status AND work_date BETWEEN :date_from AND :date_to',
    ['status' => 'late', 'date_from' => $monthStart, 'date_to' => $monthEnd]
));
$t->same('ausencias del mes demo', 2, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM attendance_records WHERE status = :status AND work_date BETWEEN :date_from AND :date_to',
    ['status' => 'absent', 'date_from' => $monthStart, 'date_to' => $monthEnd]
));
$t->same('permisos pendientes demo', 1, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM leave_requests WHERE status = :status',
    ['status' => 'pending']
));
$t->same('permisos aprobados demo', 2, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM leave_requests WHERE status = :status',
    ['status' => 'approved']
));
$t->same('permisos rechazados demo', 1, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM leave_requests WHERE status = :status',
    ['status' => 'rejected']
));
$t->same('vacantes abiertas demo', 2, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM vacancies WHERE status = :status',
    ['status' => 'open']
));
$t->same('vacantes cerradas demo', 1, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM vacancies WHERE status = :status',
    ['status' => 'closed']
));
$t->same('postulaciones demo', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));
$t->same('postulaciones en curso demo', 4, (int) dbValue(
    $pdo,
    "SELECT COUNT(*) FROM applications WHERE status IN ('received', 'screening', 'interview')"
));
$t->same('postulaciones seleccionadas demo', 1, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM applications WHERE status = :status',
    ['status' => 'selected']
));
$t->same('altas pendientes demo', 1, (int) dbValue(
    $pdo,
    'SELECT COUNT(*) FROM applications WHERE status = :status AND employee_id IS NULL',
    ['status' => 'selected']
));
$t->same('candidatos demo', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));

$response = $anonymous->get('/');
$t->status('anónimo / redirige', $response, 302);
$t->location('anónimo / va a login', $response, '/login');

$response = $anonymous->get('/dashboard');
$t->status('anónimo /dashboard redirige', $response, 302);
$t->location('anónimo /dashboard va a login', $response, '/login');

$response = $anonymous->get('/dashboard/inexistente');
$t->status('ruta inexistente del panel devuelve 404', $response, 404);

$response = $anonymous->post('/dashboard', []);
$t->status('POST /dashboard devuelve 404', $response, 404);

// ---------------------------------------------------------------------------
$t->section('Panel de administración');

$response = login($admin, 'admin@gestion-rrhh.test');
$t->status('login admin', $response, 302);
$t->location('login admin va al panel', $response, '/dashboard');

$response = $admin->get('/');
$t->status('/ con sesión redirige', $response, 302);
$t->location('/ con sesión va al panel', $response, '/dashboard');

$response = $admin->get('/dashboard');
$t->status('panel admin', $response, 200);
$t->contains('panel admin saluda al usuario', $response, 'Hola, Administrador Demo');
$t->contains('panel admin muestra rol', $response, 'Administrador');
$t->same(
    'panel admin insignia de rol',
    ['Administrador'],
    texts(xpath($response->body), '//span[contains(@class,"badge") and contains(@class,"fs-6")]')
);
$t->containsText('panel admin muestra fecha de corte', $response, 'Resumen de la operación al 26/10/2026.');
$t->same('panel admin título', ['Hola, Administrador Demo'], texts(xpath($response->body), '//h1'));
$t->same(
    'panel admin etiquetas de indicadores',
    [
        'Empleados activos',
        'Asistencia de octubre 2026',
        'Permisos pendientes',
        'Vacantes abiertas',
        'En curso',
        'Seleccionadas',
        'Pendientes de alta',
        'Candidatos',
    ],
    texts(xpath($response->body), '//p[contains(@class,"text-uppercase")]')
);
$t->same('panel admin cifras principales', ['8', '15', '1', '2'], cards($response));
$t->same(
    'panel admin detalles de indicadores',
    [
        'Inactivos: 2 · Total: 10',
        'Hoy: 0 · Tardanzas: 3 · Ausencias: 2',
        'Aprobados: 2 · Rechazados: 1 · Total: 4',
        'Cerradas: 1 · Total: 3',
    ],
    texts(xpath($response->body), '//p[contains(@class,"display-6")]/following-sibling::p[1]')
);
$t->same(
    'panel admin cifras de reclutamiento',
    ['4', '1', '1', '5'],
    texts(xpath($response->body), '//div[contains(@class,"card")]//p[contains(@class,"h2")]')
);
$t->same(
    'panel admin estados de postulaciones',
    [
        'Recibidas: 2',
        'En evaluación: 1',
        'Entrevista: 1',
        'Seleccionadas: 1',
        'Rechazadas: 1',
    ],
    texts(xpath($response->body), '//a[contains(@class,"badge")]')
);
$t->same(
    'panel admin accesos rápidos',
    [
        'Registrar empleado',
        'Registrar asistencia',
        'Crear solicitud de permiso',
        'Revisar permisos pendientes',
        'Crear vacante',
        'Ver candidatos seleccionados',
    ],
    texts(xpath($response->body), '//div[contains(@class,"list-group")]/a/span[not(@class)]')
);
$t->same(
    'panel admin destinos de accesos rápidos',
    [
        '/employees/create',
        '/attendance/create',
        '/leave-requests/create',
        '/leave-requests?status=pending',
        '/vacancies/create',
        '/applications?status=selected',
    ],
    texts(xpath($response->body), '//div[contains(@class,"list-group")]/a/@href')
);

$dashboardLinks = [
    '/dashboard',
    '/employees',
    '/attendance',
    '/recruitment',
    '/employees?status=active',
    '/attendance/report?month=2026-10',
    '/leave-requests?status=pending',
    '/vacancies?status=open',
    '/applications',
    '/applications?status=received',
    '/applications?status=screening',
    '/applications?status=interview',
    '/applications?status=selected',
    '/applications?status=rejected',
    '/vacancies',
    '/candidates',
];

$panelLinks = links($response);
foreach ($dashboardLinks as $path) {
    $t->ok('panel admin enlaza ' . $path, in_array($path, $panelLinks, true), 'no encontrado');
}

$t->notContains('panel admin sin error no controlado', $response, 'Error no controlado');
$t->notContains('panel admin sin Fatal error', $response, 'Fatal error');

// ---------------------------------------------------------------------------
$t->section('Enlaces operativos del panel');

foreach ($dashboardLinks as $path) {
    $response = $admin->get($path);
    $t->status('admin accede a ' . $path, $response, 200);
}

// ---------------------------------------------------------------------------
$t->section('Panel de RR. HH.');

$response = login($hr, 'rrhh@gestion-rrhh.test');
$t->status('login RR. HH.', $response, 302);
$t->location('login RR. HH. va al panel', $response, '/dashboard');

$response = $hr->get('/dashboard');
$t->status('panel RR. HH.', $response, 200);
$t->contains('panel RR. HH. saluda al usuario', $response, 'Hola, Ana López');
$t->contains('panel RR. HH. muestra rol', $response, 'Personal de RR. HH.');
$t->same(
    'panel RR. HH. insignia de rol',
    ['Personal de RR. HH.'],
    texts(xpath($response->body), '//span[contains(@class,"badge") and contains(@class,"fs-6")]')
);
$t->same('panel RR. HH. título', ['Hola, Ana López'], texts(xpath($response->body), '//h1'));
$t->same('panel RR. HH. cifras principales', ['8', '15', '1', '2'], cards($response));
$t->same(
    'panel RR. HH. accesos rápidos',
    [
        'Registrar empleado',
        'Registrar asistencia',
        'Crear solicitud de permiso',
        'Revisar permisos pendientes',
        'Crear vacante',
        'Ver candidatos seleccionados',
    ],
    texts(xpath($response->body), '//div[contains(@class,"list-group")]/a/span[not(@class)]')
);
$t->same(
    'panel RR. HH. destinos de accesos rápidos',
    [
        '/employees/create',
        '/attendance/create',
        '/leave-requests/create',
        '/leave-requests?status=pending',
        '/vacancies/create',
        '/applications?status=selected',
    ],
    texts(xpath($response->body), '//div[contains(@class,"list-group")]/a/@href')
);
$t->same(
    'panel RR. HH. estados de postulaciones',
    [
        'Recibidas: 2',
        'En evaluación: 1',
        'Entrevista: 1',
        'Seleccionadas: 1',
        'Rechazadas: 1',
    ],
    texts(xpath($response->body), '//a[contains(@class,"badge")]')
);

foreach ($dashboardLinks as $path) {
    $response = $hr->get($path);
    $t->status('RR. HH. accede a ' . $path, $response, 200);
}

// ---------------------------------------------------------------------------
$t->section('Estado final de la base');

$t->same('empleados sin cambios', 10, (int) dbValue($pdo, 'SELECT COUNT(*) FROM employees'));
$t->same('asistencias sin cambios', 15, (int) dbValue($pdo, 'SELECT COUNT(*) FROM attendance_records'));
$t->same('permisos sin cambios', 4, (int) dbValue($pdo, 'SELECT COUNT(*) FROM leave_requests'));
$t->same('vacantes sin cambios', 3, (int) dbValue($pdo, 'SELECT COUNT(*) FROM vacancies'));
$t->same('candidatos sin cambios', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM candidates'));
$t->same('postulaciones sin cambios', 6, (int) dbValue($pdo, 'SELECT COUNT(*) FROM applications'));
$t->same('auditorías sin cambios', 5, (int) dbValue($pdo, 'SELECT COUNT(*) FROM audit_logs'));

$response = $admin->get('/dashboard');
$t->status('panel admin segunda lectura', $response, 200);
$t->same('panel admin cifras estables', ['8', '15', '1', '2'], cards($response));

exit($t->summary());
