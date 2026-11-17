#!/usr/bin/env python3
"""SOL-629 storage fill sampler (contract C5).

Appends one JSON object per tick to the output file:

    {"ts": <float unix seconds>, "kv_keys": <int or null>, "search_docs": <int or null>}

Each tick runs one `docker exec` into the CLI container and reads the same two sources as the
runner's positive control, so the fill curve and the final count can never disagree on method:

- key-value keys from `INFO keyspace`, summed over every database. Never DBSIZE (returns 0 on
  KVrocks until a background scan has run) and never a bare --scan (sees database 0 only, while the
  application writes to database 1).
- search documents from the `_count` API across all indices.

A source that cannot be read is written as null, never 0: a confident zero is exactly how the
positive control lied twice before. Runs until SIGTERM or SIGINT, or until --duration elapses.

Usage:
    storage-sampler.py --out FILE [--container NAME] [--interval SECONDS] [--duration SECONDS]
    storage-sampler.py --parse-only < captured-output     parse one probe output, for testing
"""

import argparse
import json
import re
import signal
import subprocess
import sys
import time

# One shell round trip per tick. The marker separates the two outputs so a failure of one source
# cannot shift the other into its place.
# On Kvrocks INFO keyspace reports the last finished background scan, so each tick starts one (a scan
# already running answers with an error, which is ignored). The Kvrocks curve lags by one scan.
PROBE = ("redis-cli -h key_value_store INFO server 2>/dev/null | grep -q '^kvrocks_version' "
         "&& redis-cli -h key_value_store DBSIZE SCAN >/dev/null 2>&1; "
         "redis-cli -h key_value_store INFO keyspace 2>/dev/null; echo '---SEARCH---'; "
         "curl -s --max-time 5 'http://search:9200/_count' 2>/dev/null")

_KEYSPACE_LINE = re.compile(r'^db\d+:keys=(\d+)', re.MULTILINE)
_COUNT_FIELD = re.compile(r'"count"\s*:\s*(\d+)')


def parse_probe(output):
    """Return (kv_keys, search_docs) from one probe output; a source that did not answer is None."""
    if '---SEARCH---' not in output:
        return None, None
    kv_part, search_part = output.split('---SEARCH---', 1)
    kv_keys = None
    if '# Keyspace' in kv_part or _KEYSPACE_LINE.search(kv_part):
        # An empty store answers with the "# Keyspace" header and no db lines: that is a real 0.
        kv_keys = sum(int(n) for n in _KEYSPACE_LINE.findall(kv_part))
    match = _COUNT_FIELD.search(search_part)
    if match:
        search_docs = int(match.group(1))
    elif '"index_not_found_exception"' in search_part or '"no such index' in search_part:
        search_docs = 0
    else:
        search_docs = None
    return kv_keys, search_docs


def sample(container):
    try:
        done = subprocess.run(['docker', 'exec', container, 'bash', '-lc', PROBE],
                              capture_output=True, text=True, timeout=15)
        return parse_probe(done.stdout)
    except (subprocess.SubprocessError, OSError):
        return None, None


def main():
    parser = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    parser.add_argument('--out')
    parser.add_argument('--container', default='spryker_cli_1')
    parser.add_argument('--interval', type=float, default=1.0)
    parser.add_argument('--duration', type=float, default=None)
    parser.add_argument('--parse-only', action='store_true')
    args = parser.parse_args()

    if args.parse_only:
        print(json.dumps(dict(zip(('kv_keys', 'search_docs'), parse_probe(sys.stdin.read())))))
        return 0
    if not args.out:
        parser.error('--out is required')

    stopping = []
    for signum in (signal.SIGTERM, signal.SIGINT):
        signal.signal(signum, lambda *_: stopping.append(True))

    started = time.time()
    with open(args.out, 'a', buffering=1) as handle:
        while not stopping:
            tick = time.time()
            kv_keys, search_docs = sample(args.container)
            handle.write(json.dumps({'ts': round(tick, 6), 'kv_keys': kv_keys, 'search_docs': search_docs}) + '\n')
            if args.duration is not None and time.time() - started >= args.duration:
                break
            # Fixed cadence: the probe's own duration is subtracted, so a slow tick does not drift.
            remaining = args.interval - (time.time() - tick)
            if remaining > 0 and not stopping:
                time.sleep(remaining)
    return 0


if __name__ == '__main__':
    sys.exit(main())
