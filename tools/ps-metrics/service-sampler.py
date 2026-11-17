#!/usr/bin/env python3
"""SOL-629 service resource sampler (contract C8).

Appends one JSON object per container per tick to the output file:

    {"ts": <float unix seconds>, "container": "spryker_database_1", "service": "database",
     "cpu_pct": <float or null>, "mem_mb": <float or null>, "pids": <int or null>}

Containers: database, key-value store, search, broker and cli, found by their compose service name
(`<project>_<service>_<n>`) in `docker ps`, or given with --containers.

One process and one `docker stats --format '{{json .}}'` child for the whole run. A one-shot
`docker stats --no-stream` call takes about 2 s (it waits for two CPU readings), so polling it cannot
reach 1 Hz; the streaming form prints a frame every 0.5 s from the same counters, and the sampler
keeps the first frame per container in each interval. `cpu_pct` is docker's figure: percent of one
CPU, so above 100 on several cores. `mem_mb` is the container's memory usage in MiB, as docker shows
it (page cache excluded on cgroup v2). A value docker cannot read (`--`) is null, never 0.

Runs until SIGTERM or SIGINT, or until --duration elapses.

Usage:
    service-sampler.py --out FILE [--interval SECONDS] [--duration SECONDS] [--containers a,b,c]
    service-sampler.py --list          print the containers it would sample, and exit
"""

import argparse
import json
import re
import signal
import subprocess
import sys
import threading
import time

SERVICES = ('database', 'key_value_store', 'search', 'broker', 'cli')
_NAME = re.compile(r'^(?P<project>.+?)[_-](?P<service>' + '|'.join(SERVICES) + r')[_-]\d+$')
_ANSI = re.compile(r'\x1b\[[0-9;]*[A-Za-z]')
_UNITS = {'b': 1 / 1048576, 'kib': 1 / 1024, 'kb': 1 / 1024, 'mib': 1, 'mb': 1,
          'gib': 1024, 'gb': 1024, 'tib': 1048576, 'tb': 1048576}


def discover():
    """{container name: service} for the five services, from the running containers."""
    done = subprocess.run(['docker', 'ps', '--format', '{{.Names}}'], capture_output=True, text=True, timeout=30)
    found = {}
    for name in done.stdout.split():
        match = _NAME.match(name)
        if match:
            found[name] = match.group('service')
    return found


def parse_mem_mb(value):
    match = re.match(r'^\s*([0-9.]+)\s*([A-Za-z]+)', value or '')
    if not match:
        return None
    factor = _UNITS.get(match.group(2).lower())
    return round(float(match.group(1)) * factor, 3) if factor else None


def parse_frame(line):
    """One `docker stats` JSON line, ANSI cursor codes stripped, or None."""
    line = _ANSI.sub('', line).strip()
    if not line.startswith('{'):
        return None
    try:
        data = json.loads(line)
    except ValueError:
        return None
    cpu = (data.get('CPUPerc') or '').rstrip('%')
    try:
        cpu_pct = float(cpu)
    except ValueError:
        cpu_pct = None
    pids = data.get('PIDs')
    return {'container': data.get('Name') or data.get('Container'),
            'cpu_pct': cpu_pct,
            'mem_mb': parse_mem_mb((data.get('MemUsage') or '').split('/')[0]),
            'pids': int(pids) if isinstance(pids, str) and pids.isdigit() else None}


def main():
    parser = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    parser.add_argument('--out')
    parser.add_argument('--interval', type=float, default=1.0)
    parser.add_argument('--duration', type=float, default=None)
    parser.add_argument('--containers', help='comma-separated names; default: discovered')
    parser.add_argument('--list', action='store_true')
    args = parser.parse_args()

    if args.containers:
        services = {}
        for name in args.containers.split(','):
            match = _NAME.match(name.strip())
            services[name.strip()] = match.group('service') if match else None
    else:
        services = discover()
    if args.list:
        print(json.dumps(services, indent=2))
        return 0
    if not args.out:
        parser.error('--out is required')
    if not services:
        print('no database, key-value, search, broker or cli container is running', file=sys.stderr)
        return 1
    print(f'sampling {", ".join(sorted(services))} every {args.interval}s into {args.out}', file=sys.stderr)

    stopping = threading.Event()
    for signum in (signal.SIGTERM, signal.SIGINT):
        signal.signal(signum, lambda *_: stopping.set())

    process = subprocess.Popen(['docker', 'stats', '--format', '{{json .}}', *sorted(services)],
                               stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, text=True, bufsize=1)

    def watchdog():
        # The read loop blocks on the pipe, so the stop signal and the duration end the child here.
        started = time.time()
        while not stopping.wait(0.2):
            if args.duration is not None and time.time() - started >= args.duration:
                stopping.set()
        process.terminate()

    threading.Thread(target=watchdog, daemon=True).start()
    last_slot = {}
    with open(args.out, 'a', buffering=1) as handle:
        for line in process.stdout:
            now = time.time()
            frame = parse_frame(line)
            if frame is None or frame['container'] not in services:
                continue
            # One sample per container per interval: the stream prints a frame every 0.5 s. A gap of
            # three quarters of the interval keeps the cadence at the interval without skipping one.
            previous = last_slot.get(frame['container'])
            if previous is not None and now - previous < 0.75 * args.interval:
                continue
            last_slot[frame['container']] = now
            frame['ts'] = round(now, 6)
            frame['service'] = services[frame['container']]
            handle.write(json.dumps({key: frame[key] for key in
                                     ('ts', 'container', 'service', 'cpu_pct', 'mem_mb', 'pids')}) + '\n')
            if stopping.is_set():
                break
    stopping.set()
    try:
        process.wait(timeout=5)
    except subprocess.TimeoutExpired:
        process.kill()
    return 0


if __name__ == '__main__':
    sys.exit(main())
