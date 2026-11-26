<?php

declare(strict_types=1);

$delayMs         = isset($_GET['delay'])          ? max(0, (int) $_GET['delay'])          : 0;
$memMb           = isset($_GET['mem'])            ? max(0, (int) $_GET['mem'])            : 0;
$cpuMs           = isset($_GET['cpu'])            ? max(0, (int) $_GET['cpu'])            : 0;
$remoteTimeoutMs = isset($_GET['remote_timeout']) ? max(0, (int) $_GET['remote_timeout']) : 0;
$ioKb            = isset($_GET['io'])             ? max(0, (int) $_GET['io'])             : 0;
$memAlloc        = isset($_GET['mem_alloc'])      ? (string) $_GET['mem_alloc']           : 'fast';

if (!in_array($memAlloc, ['fast', 'slow'], true)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'error' => 'mem_alloc must be fast or slow']);
    exit;
}

$startTime = microtime(true);
$results   = [];

// 1. Memory — allocate and hold for the full request lifetime
//    fast: one block of mem MiB (one mmap of ~mem MiB).
//    slow: the same mem MiB as 2 MiB blocks, each its own mmap, held in an array.
//
//    Slow block size: Zend MM serves allocations up to 2 MiB - 4 KiB from
//    2 MiB chunks, and keeps freed chunks cached in the worker for later
//    requests (measured: an idle FPM worker grew 6 -> 211 MiB RSS over six
//    mem=200 requests with ~1 MiB blocks; gc_mem_caches() does not stop it).
//    Allocations above that are "huge": one mmap each, unmapped on free, never
//    cached — the same lifecycle as fast mode's single block. A string costs
//    24 B header + payload + 1 B NUL, so this payload makes each block exactly
//    2 MiB: RSS, virtual size and memory_limit accounting match fast mode, both
//    during the request and after it.
const SLOW_BLOCK_BYTES = 2 * 1048576 - 24 - 1; // 2,097,127 B payload

if ($memMb > 0) {
    if ($memAlloc === 'slow') {
        $memDummy  = [];
        $remaining = $memMb * 1048576;
        while ($remaining > 0) {
            // The tail goes into the last block (2-4 MiB), so no block falls
            // below the huge threshold; only mem=1 stays a single 1 MiB block.
            $block       = $remaining < 2 * SLOW_BLOCK_BYTES ? $remaining : SLOW_BLOCK_BYTES;
            $memDummy[]  = str_repeat('A', $block);
            $remaining  -= $block;
        }
    } else {
        $memDummy = str_repeat('A', $memMb * 1048576);
    }
}

// 2. CPU — spinloop burns CPU for the requested duration
if ($cpuMs > 0) {
    $cpuUntil = microtime(true) + ($cpuMs / 1000.0);
    while (microtime(true) < $cpuUntil) {
        sqrt(mt_rand());
    }
}

// 3. Remote hang — connects to a black-hole address that accepts TCP but never sends data.
//    After the TCP handshake succeeds, we attempt a read: the worker blocks until the
//    stream read-timeout fires, accurately simulating a stalled upstream HTTP call.
//    REMOTE_BLACKHOLE_ADDR defaults to 192.0.2.1:80 (RFC 5737 TEST-NET, unreachable on AWS).
//    docker-compose overrides this to the local blackhole service.
if ($remoteTimeoutMs > 0) {
    $addr    = getenv('REMOTE_BLACKHOLE_ADDR') ?: '192.0.2.1:80';
    $timeout = $remoteTimeoutMs / 1000.0;
    $sock    = @stream_socket_client(
        "tcp://{$addr}",
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT
    );
    if ($sock !== false) {
        // TCP handshake succeeded — attempt a read so the worker actually blocks.
        // The blackhole never sends data, so fread() stalls until the timeout fires.
        $secPart  = (int) $timeout;
        $usecPart = (int) (fmod($timeout, 1.0) * 1_000_000);
        stream_set_timeout($sock, $secPart, $usecPart);
        fread($sock, 1);
        $meta = stream_get_meta_data($sock);
        fclose($sock);
        $results['remote'] = $meta['timed_out'] ? 'timed_out' : 'connected';
    } else {
        // Connection failed outright (no route, refused, etc.)
        $results['remote'] = in_array($errno, [110, SOCKET_ETIMEDOUT], true)
            ? 'timed_out'
            : 'unreachable';
    }
}

// 4. I/O — write then read N KB on the configured directory.
//    IO_DIR defaults to /tmp (local). In ECS tasks it is set to /data (EBS-backed mount).
if ($ioKb > 0) {
    $dir     = rtrim((string) (getenv('IO_DIR') ?: '/tmp'), '/');
    $path    = "{$dir}/io_" . getmypid() . '_' . mt_rand() . '.tmp';
    $payload = str_repeat('B', $ioKb * 1024);
    file_put_contents($path, $payload);
    $read = file_get_contents($path);
    @unlink($path);
    $results['io'] = strlen($read) === strlen($payload) ? 'ok' : 'mismatch';
}

// 5. Generic delay — placed last so preceding operations are measured independently.
//    DELAY_VIA_BLACKHOLE=true: the worker holds a real TCP connection for the delay instead of
//    sleeping. It connects to the blackhole service (tools/blackhole/server.py, DELAY_BLACKHOLE_ADDR),
//    sends "HOLD <ms>\n" and blocks reading until the server answers "DONE\n" after <ms>, like a
//    call to a slow upstream. Any other value: usleep(), as before.
$delayVia = getenv('DELAY_VIA_BLACKHOLE') === 'true' ? 'blackhole' : 'sleep';
$status   = 'ok';

if ($delayMs > 0 && $delayVia === 'blackhole') {
    $results['delay'] = holdOnBlackhole((string) getenv('DELAY_BLACKHOLE_ADDR'), $delayMs);
    if ($results['delay'] !== 'done') {
        // The dependency failed: 500, so it can't pass for a held request (nginx's own errors are 502/504)
        http_response_code(500);
        $status = 'error';
    }
} elseif ($delayMs > 0) {
    usleep($delayMs * 1000);
}

/**
 * Hold a connection to the blackhole for $delayMs: "done" once it answers, else what went wrong.
 * The read waits up to $delayMs + 30 s, so only a lost or stuck blackhole ends it as "timed_out".
 */
function holdOnBlackhole(string $addr, int $delayMs): string
{
    if ($addr === '') {
        return 'unreachable: DELAY_BLACKHOLE_ADDR not set';
    }
    $sock = @stream_socket_client("tcp://{$addr}", $errno, $errstr, 5.0, STREAM_CLIENT_CONNECT);
    if ($sock === false) {
        return "unreachable: {$errno} {$errstr}";
    }
    stream_set_timeout($sock, intdiv($delayMs, 1000) + 30, ($delayMs % 1000) * 1000);
    if (fwrite($sock, "HOLD {$delayMs}\n") === false) {
        fclose($sock);
        return 'send_failed';
    }
    $line = fgets($sock);
    $meta = stream_get_meta_data($sock);
    fclose($sock);
    if ($line === "DONE\n") {
        return 'done';
    }
    return $meta['timed_out'] ? 'timed_out' : 'closed_early';
}

$actualDurationMs = (int) round((microtime(true) - $startTime) * 1000);

header('Content-Type: application/json');
echo json_encode([
    'status'            => $status,
    'params'            => [
        'delay_ms'          => $delayMs,
        'delay_via'         => $delayVia,
        'mem_mb'            => $memMb,
        'mem_alloc'         => $memAlloc,
        'cpu_ms'            => $cpuMs,
        'remote_timeout_ms' => $remoteTimeoutMs,
        'io_kb'             => $ioKb,
    ],
    'results'           => $results,
    'actual_duration_ms' => $actualDurationMs,
], JSON_PRETTY_PRINT);
