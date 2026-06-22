<?php

declare(strict_types=1);

/**
 * P&S Test Probe — standalone state-validation backdoor.
 *
 * Lets a load test / CI assert that an action actually mutated backend state:
 *   - DB:      did "place order" create a row in spy_sales_order?
 *   - Redis:   did "add to cart" / publish write the KV key?
 *   - RabbitMQ: is an event sitting in a publish queue?
 *   - Jenkins:  did the scheduled publish job run green?
 *
 * Standalone: NO Spryker autoload, NO composer deps. Talks to DB/Redis/RMQ/Jenkins
 * directly. Deploy by copying this single file onto the env (e.g. into a web docroot
 * or run via `php -S` on the Jenkins node), set the env vars below, hit over HTTP.
 *
 * !! INTENTIONAL TEST BACKDOOR. Only on short-lived perf envs. Delete env after. !!
 *
 * --- Auth ---
 * Header:  X-Probe-Token: <token>
 * Expected token, in precedence order:
 *   1. PS_PROBE_TOKEN env var, OR
 *   2. `.probe-token` file next to this script (its trimmed contents), OR
 *   3. the bcrypt password hash of an admin row in spy_user (default).
 * Compared with hash_equals() (constant-time).
 *
 * OPEN MODE (no token): a present-but-EMPTY `.probe-token` file (and no
 * PS_PROBE_TOKEN) disables token auth entirely — access is then gated ONLY by the
 * env's nginx HTTP basic auth / network. Use only on a basic-auth-gated env.
 *   touch .probe-token            -> open (no token; relies on basic auth)
 *   echo 's3cret' > .probe-token  -> token = s3cret (no env var / DB needed)
 *
 * --- Enable flag ---
 * Enabled if PS_PROBE_ENABLED=1 OR a `.probe-enabled` file exists next to this
 * script. Otherwise => 404, probe invisible.
 *
 * --- Endpoints (GET) ---
 *   ?probe=db&sql=<urlencoded read-only query>
 *   ?probe=redis&op=exists|get|scan&key=<key|prefix>
 *   ?probe=rmq                      (overview: totals + rates + per-queue breakdown)
 *   ?probe=rmq&queue=<name>          (single queue detail)
 *   ?probe=jenkins&job=<name>
 *   ?probe=ping            (auth check only)
 *
 * --- Env vars (all overridable; fall back to Spryker's own SPRYKER_* where sensible) ---
 *   PS_PROBE_ENABLED          required "1"
 *   PS_PROBE_TOKEN            optional static token; else admin spy_user hash is used
 *   PS_PROBE_ADMIN_USERNAME   optional; which spy_user row supplies the token hash
 *
 *   DB:    PROBE_DB_DSN | (SPRYKER_DB_HOST, SPRYKER_DB_PORT, SPRYKER_DB_DATABASE), SPRYKER_DB_USERNAME, SPRYKER_DB_PASSWORD
 *   Redis: PROBE_REDIS_HOST, PROBE_REDIS_PORT, PROBE_REDIS_PASSWORD, PROBE_REDIS_DB
 *   RMQ:   PROBE_RMQ_HOST, PROBE_RMQ_MGMT_PORT(15672), PROBE_RMQ_VHOST, PROBE_RMQ_USER, PROBE_RMQ_PASSWORD
 *   Jenkins: PROBE_JENKINS_URL, PROBE_JENKINS_USER, PROBE_JENKINS_TOKEN
 */

// ============================================================
// Bootstrap / helpers
// ============================================================

header('Content-Type: application/json');

function out(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    exit;
}

function envv(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

// ============================================================
// Enable flag — if off, behave as if the script doesn't exist
// ============================================================

if (!probeEnabled()) {
    out(404, ['error' => 'not found']);
}

/**
 * Probe is enabled when PS_PROBE_ENABLED=1 (preferred — set in the env's deploy
 * config), OR when a `.probe-enabled` marker file sits next to this script.
 *
 * The marker file is a deliberate local opt-in: it lets the probe run on an
 * existing app FPM container via the normal app URL without injecting process env.
 * On real/prod envs: use the env flag and do NOT create the file.
 */
function probeEnabled(): bool
{
    if (envv('PS_PROBE_ENABLED') === '1') {
        return true;
    }

    return is_file(__DIR__ . '/.probe-enabled');
}

// ============================================================
// Auth — X-Probe-Token header, constant-time compare
// ============================================================

/**
 * Read the `.probe-token` file next to this script, if present.
 * Returns: null = no file; '' = present-but-empty (open mode); else the token.
 */
function probeTokenFile(): ?string
{
    $f = __DIR__ . '/.probe-token';
    if (!is_file($f)) {
        return null;
    }

    return trim((string) @file_get_contents($f));
}

function expectedToken(): string
{
    // Precedence: PS_PROBE_TOKEN env > .probe-token file > admin spy_user hash.
    $static = envv('PS_PROBE_TOKEN');
    if ($static !== null) {
        return $static;
    }
    $fileTok = probeTokenFile();
    if ($fileTok !== null && $fileTok !== '') {
        return $fileTok;
    }
    // Fall back to admin spy_user password hash.
    $pdo = db();
    $username = envv('PS_PROBE_ADMIN_USERNAME');
    if ($username !== null) {
        $stmt = $pdo->prepare('SELECT password FROM spy_user WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
    } else {
        $stmt = $pdo->query("SELECT password FROM spy_user WHERE status = 'active' ORDER BY id_user ASC LIMIT 1");
    }
    $hash = $stmt->fetchColumn();
    if ($hash === false || $hash === null || $hash === '') {
        out(500, ['error' => 'cannot derive token: no admin spy_user row found']);
    }
    return (string) $hash;
}

function requireAuth(): void
{
    // OPEN MODE: a present-but-empty `.probe-token` file (and no PS_PROBE_TOKEN)
    // disables token auth — access is then gated only by the env's nginx HTTP
    // basic auth / network. Use ONLY on a basic-auth-gated or otherwise locked env.
    if (probeTokenFile() === '' && envv('PS_PROBE_TOKEN') === null) {
        return;
    }

    $given = $_SERVER['HTTP_X_PROBE_TOKEN'] ?? '';
    if ($given === '') {
        out(401, ['error' => 'missing X-Probe-Token header']);
    }
    if (!hash_equals(expectedToken(), $given)) {
        out(403, ['error' => 'invalid token']);
    }
}

// ============================================================
// Backends (lazy singletons)
// ============================================================

$GLOBALS['__pdo'] = null;

function db(): PDO
{
    if ($GLOBALS['__pdo'] instanceof PDO) {
        return $GLOBALS['__pdo'];
    }
    $dsn = envv('PROBE_DB_DSN');
    if ($dsn === null) {
        $host = envv('SPRYKER_DB_HOST', '127.0.0.1');
        $port = envv('SPRYKER_DB_PORT', '3306');
        $name = envv('SPRYKER_DB_DATABASE', 'spryker');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    }
    try {
        $pdo = new PDO(
            $dsn,
            envv('SPRYKER_DB_USERNAME', 'spryker'),
            envv('SPRYKER_DB_PASSWORD', ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
    } catch (Throwable $e) {
        out(502, ['error' => 'db connect failed', 'detail' => $e->getMessage()]);
    }
    return $GLOBALS['__pdo'] = $pdo;
}

function redisConn(): Redis
{
    if (!class_exists('Redis')) {
        out(501, ['error' => 'phpredis extension not available']);
    }
    $r = new Redis();
    // Prefer Spryker's own env vars (present in the container), allow PROBE_* override.
    $host = envv('PROBE_REDIS_HOST', envv('SPRYKER_KEY_VALUE_STORE_HOST', '127.0.0.1'));
    $port = (int) envv('PROBE_REDIS_PORT', envv('SPRYKER_KEY_VALUE_STORE_PORT', '6379'));
    if (!@$r->connect($host, $port, 5.0)) {
        out(502, ['error' => 'redis connect failed', 'host' => $host, 'port' => $port]);
    }
    $pass = envv('PROBE_REDIS_PASSWORD', envv('SPRYKER_KEY_VALUE_STORE_PASSWORD'));
    if ($pass !== null) {
        $r->auth($pass);
    }
    // Redis logical DB index. Spryker stores it as the connection "namespace".
    // Precedence: PROBE_REDIS_DB > SPRYKER_KEY_VALUE_STORE_NAMESPACE > namespace from CONNECTIONS json.
    $dbIdx = envv('PROBE_REDIS_DB', envv('SPRYKER_KEY_VALUE_STORE_NAMESPACE', redisNamespaceFromConnections()));
    if ($dbIdx !== null && $dbIdx !== '') {
        $r->select((int) $dbIdx);
    }
    return $r;
}

/**
 * Parse the redis DB index from SPRYKER_KEY_VALUE_STORE_CONNECTIONS json.
 * Shape: {"EU":{"engine":"valkey","endpoints":{...},"namespace":1}}
 */
function redisNamespaceFromConnections(): ?string
{
    $raw = envv('SPRYKER_KEY_VALUE_STORE_CONNECTIONS');
    if ($raw === null) {
        return null;
    }
    $conns = json_decode($raw, true);
    if (!is_array($conns)) {
        return null;
    }
    foreach ($conns as $conn) {
        if (isset($conn['namespace'])) {
            return (string) $conn['namespace'];
        }
    }
    return null;
}

// ============================================================
// Probe: DB (read-only)
// ============================================================

function probeDb(): void
{
    // SQL sources, in precedence: base64 (WAF-safe) then plain; from POST body or
    // query string. Use sql_b64 (and POST) on envs whose WAF blocks SQL keywords
    // in the URL — keep ?probe=db in the query, send the SQL in the body as base64.
    $b64 = $_POST['sql_b64'] ?? $_GET['sql_b64'] ?? '';
    if ($b64 !== '') {
        $sql = base64_decode(strtr($b64, '-_', '+/'), true);  // tolerate url-safe base64
        if ($sql === false) {
            out(400, ['error' => 'invalid base64 in sql_b64']);
        }
    } else {
        $sql = $_POST['sql'] ?? $_GET['sql'] ?? '';
    }
    if ($sql === '') {
        out(400, ['error' => 'missing sql / sql_b64 param']);
    }
    $trimmed = ltrim($sql);
    // Read-only guard: must start with SELECT or SHOW, no write keywords, no stacking.
    if (!preg_match('/^(SELECT|SHOW)\b/i', $trimmed)) {
        out(400, ['error' => 'only SELECT/SHOW allowed']);
    }
    if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|RENAME|LOCK|CALL|INTO\s+OUTFILE)\b/i', $trimmed)) {
        out(400, ['error' => 'write/dangerous keyword rejected']);
    }
    // Reject statement stacking (allow a single optional trailing semicolon).
    if (preg_match('/;\s*\S/', $trimmed)) {
        out(400, ['error' => 'multiple statements rejected']);
    }
    try {
        $stmt = db()->query($trimmed);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        out(400, ['error' => 'query failed', 'detail' => $e->getMessage()]);
    }
    out(200, ['probe' => 'db', 'rowCount' => count($rows), 'rows' => $rows]);
}

// ============================================================
// Probe: Redis
// ============================================================

function probeRedis(): void
{
    $op = strtolower($_GET['op'] ?? 'exists');
    $key = $_GET['key'] ?? '';
    if ($key === '') {
        out(400, ['error' => 'missing key param']);
    }
    $r = redisConn();
    switch ($op) {
        case 'exists':
            out(200, ['probe' => 'redis', 'op' => 'exists', 'key' => $key, 'exists' => (bool) $r->exists($key)]);
            // no break — exit() in out()
        case 'get':
            $val = $r->get($key);
            out(200, ['probe' => 'redis', 'op' => 'get', 'key' => $key, 'found' => $val !== false, 'value' => $val === false ? null : $val]);
        case 'scan':
            // Count keys matching prefix* via non-blocking SCAN.
            $it = null;
            $count = 0;
            $sample = [];
            $pattern = $key . '*';
            while (($keys = $r->scan($it, $pattern, 1000)) !== false) {
                $count += count($keys);
                if (count($sample) < 20) {
                    $sample = array_merge($sample, array_slice($keys, 0, 20 - count($sample)));
                }
                if ($it === 0) {
                    break;
                }
            }
            out(200, ['probe' => 'redis', 'op' => 'scan', 'pattern' => $pattern, 'count' => $count, 'sample' => $sample]);
        default:
            out(400, ['error' => 'unknown op (use exists|get|scan)']);
    }
}

// ============================================================
// Probe: RabbitMQ (management API)
// ============================================================

function httpJson(string $url, ?string $user, ?string $pass): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($user !== null) {
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . ($pass ?? ''));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    }
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false) {
        out(502, ['error' => 'upstream request failed', 'url' => $url, 'detail' => $err]);
    }
    return ['status' => $status, 'json' => json_decode((string) $body, true)];
}

function probeRmq(): void
{
    // Prefer Spryker's broker env vars, allow PROBE_* override.
    // Note: SPRYKER_BROKER_API_* is the management API (15672); SPRYKER_BROKER_* is AMQP (5672).
    $host = envv('PROBE_RMQ_HOST', envv('SPRYKER_BROKER_API_HOST', envv('SPRYKER_BROKER_HOST', '127.0.0.1')));
    $port = envv('PROBE_RMQ_MGMT_PORT', envv('SPRYKER_BROKER_API_PORT', '15672'));
    // vhost precedence: ?vhost= query > PROBE_RMQ_VHOST > SPRYKER_BROKER_NAMESPACE > "/".
    // (SPRYKER_BROKER_NAMESPACE is often injected only as a fastcgi_param, so allow the query override.)
    $vhostRaw = $_GET['vhost'] ?? envv('PROBE_RMQ_VHOST', envv('SPRYKER_BROKER_NAMESPACE', '/'));
    $vhost = rawurlencode($vhostRaw);
    $user = envv('PROBE_RMQ_USER', envv('SPRYKER_BROKER_USERNAME', 'guest'));
    $pass = envv('PROBE_RMQ_PASSWORD', envv('SPRYKER_BROKER_PASSWORD', 'guest'));

    $queue = $_GET['queue'] ?? '';

    // --- Overview mode (no queue): cluster totals + rates + per-queue breakdown ---
    if ($queue === '') {
        $base = "http://{$host}:{$port}";
        $ov = httpJson("{$base}/api/overview", $user, $pass);
        $cols = 'name,messages,messages_ready,messages_unacknowledged,consumers,'
              . 'message_stats.publish_details.rate,message_stats.ack_details.rate,message_stats.deliver_get_details.rate';
        $qs = httpJson("{$base}/api/queues/{$vhost}?columns={$cols}", $user, $pass);
        if ($ov['status'] !== 200 || $qs['status'] !== 200) {
            out(502, ['probe' => 'rmq', 'op' => 'overview', 'overview_status' => $ov['status'], 'queues_status' => $qs['status']]);
        }
        $ovj = $ov['json'] ?? [];
        $qt  = $ovj['queue_totals'] ?? [];
        $ms  = $ovj['message_stats'] ?? [];
        $limit = (int) ($_GET['limit'] ?? 30);
        $queues = is_array($qs['json'] ?? null) ? $qs['json'] : [];
        // sort by depth desc, take top N
        usort($queues, static fn ($a, $b) => ($b['messages'] ?? 0) <=> ($a['messages'] ?? 0));
        $nonempty = 0;
        $rows = [];
        foreach ($queues as $q) {
            if (($q['messages'] ?? 0) > 0) {
                $nonempty++;
            }
            if (count($rows) < $limit) {
                $rows[] = [
                    'name' => $q['name'] ?? null,
                    'messages' => $q['messages'] ?? 0,
                    'ready' => $q['messages_ready'] ?? 0,
                    'unacked' => $q['messages_unacknowledged'] ?? 0,
                    'consumers' => $q['consumers'] ?? 0,
                    'publish_rate' => $q['message_stats']['publish_details']['rate'] ?? null,
                    'ack_rate' => $q['message_stats']['ack_details']['rate'] ?? null,
                    'deliver_rate' => $q['message_stats']['deliver_get_details']['rate'] ?? null,
                ];
            }
        }
        out(200, [
            'probe' => 'rmq',
            'op' => 'overview',
            'vhost' => $vhostRaw,
            'totals' => [
                'messages' => $qt['messages'] ?? null,
                'ready' => $qt['messages_ready'] ?? null,
                'unacked' => $qt['messages_unacknowledged'] ?? null,
            ],
            'rates_per_s' => [
                'publish' => $ms['publish_details']['rate'] ?? null,
                'ack' => $ms['ack_details']['rate'] ?? null,
                'deliver' => $ms['deliver_get_details']['rate'] ?? null,
            ],
            'queue_count' => count($queues),
            'nonempty_queues' => $nonempty,
            'top_queues' => $rows,
        ]);
    }

    // --- Single-queue detail ---
    $url = "http://{$host}:{$port}/api/queues/{$vhost}/" . rawurlencode($queue);
    $res = httpJson($url, $user, $pass);
    if ($res['status'] !== 200) {
        out($res['status'] === 404 ? 404 : 502, ['probe' => 'rmq', 'queue' => $queue, 'httpStatus' => $res['status'], 'body' => $res['json']]);
    }
    $j = $res['json'] ?? [];
    $stats = $j['message_stats'] ?? [];
    $ackRate = $stats['ack_details']['rate'] ?? null;          // msgs/s acknowledged (processed)
    $publishRate = $stats['publish_details']['rate'] ?? null;  // msgs/s published
    $depth = $j['messages'] ?? null;
    // Little's law estimate of average dwell time: avg_wait_s ≈ queue_depth / ack_rate.
    // Zero-instrumentation approximation of "how long a message sits in this queue".
    $estDwellSec = ($depth !== null && $ackRate !== null && $ackRate > 0) ? round($depth / $ackRate, 3) : null;
    out(200, [
        'probe' => 'rmq',
        'queue' => $queue,
        'messages' => $depth,
        'messages_ready' => $j['messages_ready'] ?? null,
        'messages_unacknowledged' => $j['messages_unacknowledged'] ?? null,
        'consumers' => $j['consumers'] ?? null,
        'ack_rate_per_s' => $ackRate,
        'publish_rate_per_s' => $publishRate,
        'est_avg_dwell_sec' => $estDwellSec,  // Little's law: depth / ack_rate
    ]);
}

// ============================================================
// Probe: Jenkins
// ============================================================

function probeJenkins(): void
{
    $job = $_GET['job'] ?? '';
    $base = rtrim(envv('PROBE_JENKINS_URL', ''), '/');
    if ($base === '') {
        out(400, ['error' => 'PROBE_JENKINS_URL not configured']);
    }
    $user = envv('PROBE_JENKINS_USER');
    $token = envv('PROBE_JENKINS_TOKEN');

    if ($job === '') {
        // No job: report running executors / queue depth.
        $res = httpJson($base . '/computer/api/json?tree=busyExecutors,totalExecutors', $user, $token);
        $q = httpJson($base . '/queue/api/json?tree=items[id]', $user, $token);
        out(200, [
            'probe' => 'jenkins',
            'busyExecutors' => $res['json']['busyExecutors'] ?? null,
            'totalExecutors' => $res['json']['totalExecutors'] ?? null,
            'queuedItems' => isset($q['json']['items']) ? count($q['json']['items']) : null,
        ]);
    }

    $res = httpJson($base . '/job/' . rawurlencode($job) . '/lastBuild/api/json?tree=number,result,building,timestamp,duration', $user, $token);
    if ($res['status'] !== 200) {
        out($res['status'] === 404 ? 404 : 502, ['probe' => 'jenkins', 'job' => $job, 'httpStatus' => $res['status']]);
    }
    $j = $res['json'] ?? [];
    out(200, [
        'probe' => 'jenkins',
        'job' => $job,
        'build' => $j['number'] ?? null,
        'result' => $j['result'] ?? null,   // SUCCESS|FAILURE|null(running)
        'building' => $j['building'] ?? null,
        'duration_ms' => $j['duration'] ?? null,
    ]);
}

// ============================================================
// Dispatch
// ============================================================

$probe = $_GET['probe'] ?? '';

requireAuth();

switch ($probe) {
    case 'ping':    out(200, ['probe' => 'ping', 'ok' => true]);
    case 'db':      probeDb();
    case 'redis':   probeRedis();
    case 'rmq':     probeRmq();
    case 'jenkins': probeJenkins();
    default:        out(400, ['error' => 'unknown probe (use ping|db|redis|rmq|jenkins)']);
}
