#!/usr/bin/env python3
"""SOL-629 batch-size sweep: how one queue behaves at different chunk sizes.

A lab measurement, not a production one. The campaigns measure the system as it runs (scheduler never
paused, regular settings). This sweep needs one controlled consumer per queue, so it suspends the
scheduler for its duration and puts it back at the end, also after a failure.

For each selected queue and each chunk size:

    purge all work queues
    fill the queue with its regular producer   (publish:trigger-events for publish*, sync:data for sync.*)
    run `queue:task:start <queue>` one task at a time, chunk size set by QUEUE_MESSAGE_CHUNK_SIZE_MAP,
    until the queue is empty or --max-batches tasks ran
    purge again

Every task is a new PHP process, as under the regular worker. The metrics module writes one task
record (C2) and one direct-sync flush record (C6) per task; the sweep adds the wall time of the
process, bootstrap included.

Queue selection: the --top queues of the given campaigns by score = share of task time + share of
CPU time + peak memory increase over the largest peak. Each term is 0..1, so the score is 0..3.

Usage:
    batch-sweep.py select <campaign-dir> [...] [--top 10]
    batch-sweep.py run --out data/metrics/sweep-<id> <campaign-dir> [...] [--top 10]
                       [--sizes 10,25,...] [--max-batches 5] [--only <queue>]

Stdlib only. Run from the repository root.
"""

import argparse
import base64
import json
import os
import pathlib
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from collections import defaultdict

CONTAINER = os.environ.get('CLI_CONTAINER', 'spryker_cli_1')
# The management API is reached on the loopback address with the virtual host name as Host header:
# resolving `queue.spryker.local` costs 5 s per call on macOS (.local goes to mDNS first), which made
# one purge of ~40 queues take minutes.
BROKER_HOST = os.environ.get('RABBITMQ_HOST', 'queue.spryker.local')
API = f"http://{os.environ.get('RABBITMQ_ADDRESS', '127.0.0.1')}:{os.environ.get('RABBITMQ_MANAGEMENT_PORT', '80')}/api"
AUTH = base64.b64encode(f"{os.environ.get('RABBITMQ_USER', 'spryker')}:{os.environ.get('RABBITMQ_PASS', 'secret')}".encode()).decode()
DEFAULT_SIZES = (10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000)


def read_jsonl(path):
    records = []
    if not path.exists():
        return records
    for line in path.read_text().splitlines():
        try:
            records.append(json.loads(line))
        except ValueError:
            continue
    return records


def container_jsonl(path):
    """Records read inside the cli container. The host copy of /data is synced by mutagen and lags,
    so a record written a moment ago is not on the host yet."""
    out = sh(['docker', 'exec', CONTAINER, 'cat', path], check=False)
    records = []
    for line in out.stdout.splitlines():
        try:
            records.append(json.loads(line))
        except ValueError:
            continue
    return records


def producer_of(queue):
    """The regular command that fills the queue, or None when the sweep cannot fill it."""
    if queue == 'publish' or queue.startswith('publish.'):
        return 'publish:trigger-events'
    if queue.startswith('sync.'):
        return 'sync:data'
    return None


def select_queues(campaign_dirs, top):
    """Rank queues by time share + CPU share + peak memory over the largest peak, over instrumented runs."""
    acc = defaultdict(lambda: {'ms': 0.0, 'cpu': 0.0, 'mem': 0.0, 'messages': 0, 'batches': 0, 'chunk': set()})
    runs = 0
    for campaign in campaign_dirs:
        for task_file in sorted(pathlib.Path(campaign).glob('r*-instr/task-runs.jsonl')):
            runs += 1
            for r in read_jsonl(task_file):
                a = acc[r['queue']]
                a['ms'] += r.get('ms_process') or 0
                a['cpu'] += (r.get('cpu_user_ms') or 0) + (r.get('cpu_sys_ms') or 0)
                a['mem'] = max(a['mem'], r.get('mem_peak_incr_mb') or 0)
                a['messages'] += r.get('processed') or 0
                a['batches'] += 1
                a['chunk'].add(r.get('chunk_configured'))
    if not acc:
        return [], runs
    total_ms = sum(a['ms'] for a in acc.values()) or 1
    total_cpu = sum(a['cpu'] for a in acc.values()) or 1
    max_mem = max(a['mem'] for a in acc.values()) or 1
    rows = []
    for queue, a in acc.items():
        rows.append({'queue': queue, 'producer': producer_of(queue),
                     'time_share': a['ms'] / total_ms, 'cpu_share': a['cpu'] / total_cpu, 'mem_peak_mb': a['mem'],
                     'mem_term': a['mem'] / max_mem, 'score': a['ms'] / total_ms + a['cpu'] / total_cpu + a['mem'] / max_mem,
                     'messages_per_run': a['messages'] / runs if runs else 0, 'batches': a['batches'],
                     'chunk_configured': sorted(c for c in a['chunk'] if c is not None)})
    rows.sort(key=lambda r: -r['score'])
    return [r for r in rows if r['producer']][:top], runs


def api(path, method='GET'):
    request = urllib.request.Request(f'{API}{path}', method=method)
    request.add_header('Authorization', f'Basic {AUTH}')
    request.add_header('Host', BROKER_HOST)
    with urllib.request.urlopen(request, timeout=30) as response:
        body = response.read()
    return json.loads(body) if body else None


def queues():
    return api('/queues')


def queue_state(name):
    for q in queues():
        if q['name'] == name:
            return q
    return None


def work_queues(qs):
    return [q for q in qs if not q['name'].endswith('.error')]


def purge():
    """Empty every work queue. Error queues are left as they are, the sweep reports what it adds."""
    for q in work_queues(queues()):
        if q.get('messages'):
            vhost = urllib.parse.quote(q['vhost'], safe='')
            name = urllib.parse.quote(q['name'], safe='')
            api(f'/queues/{vhost}/{name}/contents', method='DELETE')
    for _ in range(30):
        if sum(q.get('messages', 0) for q in work_queues(queues())) == 0:
            return
        time.sleep(1)
    raise RuntimeError('work queues not empty after purge')


def consumers():
    return sum(q.get('consumers', 0) for q in queues())


def error_counts():
    return {q['name']: q.get('messages', 0) for q in queues() if q['name'].endswith('.error')}


def sh(args, check=True):
    result = subprocess.run(args, capture_output=True, text=True)
    if check and result.returncode != 0:
        raise RuntimeError(f"{' '.join(args)} failed: {result.stderr.strip()[-500:]}")
    return result


def console(*args):
    """A plain console call through docker/sdk, which sets the store and service variables a bare
    `docker exec` lacks. It exits 0 when the command inside failed, so the output is checked too."""
    out = sh(['docker/sdk', 'console', *args], check=False)
    text = out.stdout + out.stderr
    if out.returncode != 0 or '[critical]' in text or 'Fatal error' in text:
        raise RuntimeError(f"console {' '.join(args)} failed: {text.strip()[-500:]}")
    return out


# In dynamic store mode the Jenkins job runs `symfonymessenger:consume queue-worker-start --time-limit=900`,
# which starts `queue:worker:start` every minute. Suspending the scheduler stops new jobs only, so the
# running consumer is killed too, or it starts a worker that takes the sweep's backlog.
WORKER_PATTERNS = ('[s]ymfonymessenger:consume', '[q]ueue:worker:start', '[q]ueue:task:start')


def workers_alive():
    out = sh(['docker', 'exec', CONTAINER, 'bash', '-lc',
              ' ; '.join(f"pgrep -f '{p}' >/dev/null && echo '{p}'" for p in WORKER_PATTERNS) + ' ; true'], check=False)
    return [line for line in out.stdout.split() if line]


def stop_scheduler():
    sh(['docker/sdk', 'console', 'scheduler:suspend'], check=False)
    for _ in range(15):
        sh(['docker', 'exec', CONTAINER, 'bash', '-lc',
            ' ; '.join(f"pkill -f '{p}'" for p in WORKER_PATTERNS) + ' ; true'], check=False)
        time.sleep(2)
        if not workers_alive() and consumers() == 0:
            return
    raise RuntimeError(f'workers still alive after the scheduler was suspended: {workers_alive()}')


def resume_scheduler():
    sh(['docker/sdk', 'console', 'scheduler:resume'], check=False)


def wait_filled(queue, settle=2.0, limit=120):
    """Wait until the ready count of the queue stops growing. Returns the backlog.

    The management API refreshes its counts about every 5 s, so "stable" means three equal reads
    2 s apart, which spans one refresh."""
    last, stable, waited = -1, 0, 0.0
    while waited < limit:
        state = queue_state(queue) or {}
        ready = state.get('messages_ready', state.get('messages', 0)) or 0
        stable = stable + 1 if ready == last and ready > 0 else 0
        if stable >= 3:
            return ready
        last = ready
        time.sleep(settle)
        waited += settle
    return max(last, 0)


TASK_SCRIPT = """#!/usr/bin/env bash
# Written by batch-sweep.py. Arguments: queue, chunk size, step directory (container paths).
export QUEUE_METRICS_ENABLED=1 QUEUE_METRICS_BREAKDOWN=1
export QUEUE_METRICS_OUTPUT_FILE="$3/task-runs.jsonl"
export QUEUE_METRICS_SYNC_FLUSH_OUTPUT_FILE="$3/sync-flush-runs.jsonl"
export QUEUE_METRICS_IMPORT_OUTPUT_FILE="$3/import-runs.jsonl"
export QUEUE_MESSAGE_CHUNK_SIZE_MAP="{\\"$1\\":$2}"
s=$(date +%s%N)
vendor/bin/console queue:task:start "$1" >> "$3/task.log" 2>&1
rc=$?
e=$(date +%s%N)
echo "TIMING $s $e $rc"
"""


def run_task(script_in_container, queue, chunk, step_dir_in_container):
    """One `queue:task:start` process with the metrics module on. Returns (start_ns, end_ns, exit)."""
    out = sh(['docker/sdk', 'cli', f'bash {script_in_container} {queue} {chunk} {step_dir_in_container}'], check=False)
    for line in out.stdout.splitlines():
        parts = line.strip().split()
        if len(parts) == 4 and parts[0] == 'TIMING':
            return int(parts[1]), int(parts[2]), int(parts[3])
    raise RuntimeError(f'task on {queue} gave no timing: {(out.stdout + out.stderr).strip()[-300:]}')


def sizes_for(backlog, sizes):
    kept = [s for s in sizes if s <= backlog]
    if backlog and (not kept or kept[-1] < backlog):
        kept.append(backlog)
    return kept


def run(args):
    out = pathlib.Path(args.out)
    out.mkdir(parents=True, exist_ok=False)
    selection, runs = select_queues(args.campaigns, args.top)
    if args.only:
        selection = [r for r in selection if r['queue'] in args.only] or [
            {'queue': q, 'producer': producer_of(q), 'score': None} for q in args.only]
    if not selection:
        raise SystemExit('no queue to sweep')
    sizes = tuple(int(s) for s in args.sizes.split(','))
    errors_before = None
    meta = {'kind': 'batch_sweep', 'started': time.time(), 'campaigns': args.campaigns, 'runs_ranked': runs,
            'top': args.top, 'sizes': list(sizes), 'max_batches': args.max_batches, 'container': CONTAINER,
            'selection': selection, 'scheduler': 'suspended for the sweep, resumed at the end',
            'git': sh(['git', 'rev-parse', '--short=11', 'HEAD']).stdout.strip()}
    (out / 'selection.json').write_text(json.dumps(selection, indent=1))
    (out / 'run-task.sh').write_text(TASK_SCRIPT)
    script_in_container = f'/data/{out}/run-task.sh'
    steps = out / 'steps.jsonl'
    try:
        print('suspending the scheduler', flush=True)
        stop_scheduler()
        purge()
        errors_before = error_counts()
        for sel in selection:
            queue = sel['queue']
            # A probe fill sets the size list: chunks above the backlog cannot be measured here.
            console(sel['producer'])
            backlog = wait_filled(queue)
            print(f'{queue}: backlog {backlog} after {sel["producer"]}', flush=True)
            purge()
            for chunk in sizes_for(backlog, sizes):
                step_dir = out / queue / str(chunk)
                step_dir.mkdir(parents=True)
                in_container = f'/data/{step_dir}'
                started = time.time()
                console(sel['producer'])
                filled = wait_filled(queue)
                walls = []
                for i in range(args.max_batches):
                    if consumers() or workers_alive():
                        raise RuntimeError('another consumer or worker appeared during the sweep')
                    s, e, rc = run_task(script_in_container, queue, chunk, in_container)
                    walls.append({'i': i, 'start_ns': s, 'end_ns': e, 'wall_ms': (e - s) / 1e6, 'exit': rc})
                    # The broker counts lag about 5 s, so the stop rule reads the task records instead:
                    # a short or missing batch means the queue is empty.
                    records = container_jsonl(f'{in_container}/task-runs.jsonl')
                    if len(records) <= i or (records[-1].get('chunk_received') or 0) < chunk:
                        break
                left = max(0, filled - sum(r.get('processed') or 0 for r in container_jsonl(f'{in_container}/task-runs.jsonl')))
                purge()
                record = {'queue': queue, 'chunk': chunk, 'backlog': filled, 'probe_backlog': backlog,
                          'tasks': walls, 'left': left, 'started': started, 'ended': time.time()}
                with steps.open('a') as fh:
                    fh.write(json.dumps(record) + '\n')
                print(f'{queue:45s} chunk {chunk:6d} backlog {filled:6d} tasks {len(walls)} '
                      f'wall {sum(w["wall_ms"] for w in walls) / 1000:6.1f}s', flush=True)
    finally:
        try:
            purge()
        except Exception as error:  # noqa: BLE001 - the scheduler must come back whatever failed
            print(f'WARNING: final purge failed: {error}', file=sys.stderr)
        resume_scheduler()
        print('scheduler resumed', flush=True)
        meta['ended'] = time.time()
        after = error_counts()
        meta['error_messages_added'] = (sum(max(0, n - (errors_before or {}).get(q, 0)) for q, n in after.items())
                                        if errors_before is not None else None)
        (out / 'meta.json').write_text(json.dumps(meta, indent=1))


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    sub = parser.add_subparsers(dest='cmd', required=True)
    p_sel = sub.add_parser('select')
    p_sel.add_argument('campaigns', nargs='+')
    p_sel.add_argument('--top', type=int, default=10)
    p_run = sub.add_parser('run')
    p_run.add_argument('campaigns', nargs='+')
    p_run.add_argument('--out', required=True)
    p_run.add_argument('--top', type=int, default=10)
    p_run.add_argument('--sizes', default=','.join(str(s) for s in DEFAULT_SIZES))
    p_run.add_argument('--max-batches', type=int, default=5)
    p_run.add_argument('--only', action='append')
    args = parser.parse_args()
    if args.cmd == 'select':
        selection, runs = select_queues(args.campaigns, args.top)
        print(f'{runs} instrumented run(s)')
        for r in selection:
            print(f"{r['queue']:45s} score {r['score']:.2f}  time {100 * r['time_share']:4.1f}%  "
                  f"cpu {100 * r['cpu_share']:4.1f}%  mem {r['mem_peak_mb']:5.0f} MB  {r['producer']}")
        return
    run(args)


if __name__ == '__main__':
    main()
