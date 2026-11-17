#!/usr/bin/env python3
"""SOL-629 batch-size sweep report: per queue, how time, memory, queries and payload grow with the
chunk size, and the chunk size the measurements support.

Reads a sweep directory written by batch-sweep.py:

    meta.json, selection.json, steps.jsonl
    <queue>/<chunk>/task-runs.jsonl          one record per batch (C2, with the C3 breakdown)
    <queue>/<chunk>/sync-flush-runs.jsonl    the direct-sync flush of the same process (C6)

Per queue, each measure is fitted as fixed per batch + marginal per message (least squares over every
batch). The process wall time (bootstrap, task and flush) is the cost the worker pays per batch.

Recommendation, per queue:
    knee     the smallest chunk at which the fixed cost per batch is 10% or less of the cost per message:
             a larger chunk saves at most that 10%, and costs memory and latency
    limits   memory increase within the budget, process wall time within the budget, and payload per
             batch within 1 MB (the size the scheduler log warns about)
    chunk    the knee rounded up to a common value, lowered to the first limit it passes

Usage:
    render-sweep.py <sweep-dir> [new-output.html] [--memory-budget-mb 256] [--time-budget-s 30]
    render-sweep.py --self-test

Stdlib only. Never overwrites: the output name is new for every render.
"""

import importlib.util
import json
import math
import pathlib
import statistics
import sys
from collections import defaultdict

HERE = pathlib.Path(__file__).resolve().parent


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


rr = _load('render_report', 'render-report.py')
rc = _load('render_campaign', 'render-campaign.py')
esc, fmt_ms, fmt_int, fmt_pct, fmt_bytes = rr.esc, rr.fmt_ms, rr.fmt_int, rr.fmt_pct, rr.fmt_bytes

KNEE_SHARE = 0.10
# Below this R squared the process-time line explains less than a third of the batch-to-batch variation.
WEAK_R2 = 0.3
COMMON_CHUNKS = (10, 25, 50, 100, 250, 500, 1000, 2500, 5000, 10000, 25000, 50000)
# The limits the queue module itself warns at (TaskMemoryUsageChecker, QueueConfig defaults):
#   payload: the bodies of one chunk above MAX_QUEUE_TASK_MEMORY_CHUNK_SIZE, default 1024 KB
#   memory:  memory_get_usage(true) above 75% of MAX_QUEUE_TASK_MEMORY_SIZE (default 1024 MB) divided by
#            the queue's maximum worker processes (5 here), so 153.6 MB per task process
# The time budget is ours: half of the 60 s worker run (QUEUE_WORKER_MAX_THRESHOLD_SECONDS).
PAYLOAD_LIMIT_BYTES = 1024 * 1024
BUDGETS = {'memory_mb': 0.75 * 1024 / 5, 'time_s': 30.0}
# The measured check: a straight line cannot show a middle optimum, so the median time per message of each
# measured size is read too. A size counts when its batches are at least half full. "Near the best" is
# within 10% of the lowest median; a rise of more than 15% above it at the larger sizes is a U-shape.
MEASURED_NEAR = 0.10
MEASURED_RISE = 0.15
MEASURED_MIN_BATCHES = 2


def read_jsonl(path):
    return rr.load_jsonl(path) if path.exists() else []


def load_sweep(sweep_dir):
    """Batches per queue: one dict per task that received messages, with its flush and wall time."""
    sweep_dir = pathlib.Path(sweep_dir)
    meta = json.loads((sweep_dir / 'meta.json').read_text()) if (sweep_dir / 'meta.json').exists() else {}
    selection = json.loads((sweep_dir / 'selection.json').read_text()) if (sweep_dir / 'selection.json').exists() else []
    steps = read_jsonl(sweep_dir / 'steps.jsonl')
    batches = defaultdict(list)
    for step in steps:
        step_dir = sweep_dir / step['queue'] / str(step['chunk'])
        records = read_jsonl(step_dir / 'task-runs.jsonl')
        flushes = defaultdict(float)
        for f in read_jsonl(step_dir / 'sync-flush-runs.jsonl'):
            flushes[f.get('pid')] += f.get('ms') or 0.0
        # Records are written in task order and an empty task writes none, so record i is task i.
        for i, r in enumerate(records):
            n = r.get('processed') or 0
            if not n:
                continue
            b = r.get('breakdown') or {}
            wall = step['tasks'][i]['wall_ms'] if i < len(step['tasks']) else None
            batches[step['queue']].append({
                'chunk': step['chunk'], 'n': n, 'ms_total': r.get('ms_total') or 0.0,
                'flush_ms': flushes.get(r.get('pid'), 0.0), 'wall_ms': wall,
                'mem_mb': r.get('mem_peak_incr_mb'), 'mem_start_mb': r.get('mem_start_mb'),
                'cpu_ms': (r.get('cpu_user_ms') or 0) + (r.get('cpu_sys_ms') or 0),
                'db_calls': b.get('db_calls'), 'search_calls': b.get('search_calls'), 'kv_calls': b.get('kv_calls'),
                'bytes': r.get('bytes')})
    return meta, selection, steps, dict(batches)


def nice_up(value):
    for chunk in COMMON_CHUNKS:
        if chunk >= value:
            return chunk
    return COMMON_CHUNKS[-1]


def nice_down(value):
    kept = [c for c in COMMON_CHUNKS if c <= value]
    return kept[-1] if kept else None


def _apply_limits(chunk, limits):
    reason = 'knee'
    for name, limit in sorted(limits.items(), key=lambda kv: kv[1]):
        if chunk > limit:
            return nice_down(limit), f'{name} limit'
    return chunk, reason


def queue_model(batches, budgets=None):
    """Fits and the recommended chunk of one queue."""
    budgets = {**BUDGETS, **(budgets or {})}
    fit = lambda field: rr.linear_fit([(b['n'], b[field]) for b in batches if b.get(field) is not None])
    model = {'batches': len(batches), 'sizes': sorted({b['n'] for b in batches}),
             'wall': fit('wall_ms'), 'work': rr.linear_fit([(b['n'], b['ms_total'] + b['flush_ms']) for b in batches]),
             'mem': fit('mem_mb'), 'db': fit('db_calls'), 'search': fit('search_calls'), 'kv': fit('kv_calls')}
    with_bytes = [b['bytes'] / b['n'] for b in batches if b.get('bytes')]
    model['bytes_per_msg'] = statistics.median(with_bytes) if with_bytes else None
    starts = [b['mem_start_mb'] for b in batches if b.get('mem_start_mb') is not None]
    model['mem_start_mb'] = statistics.median(starts) if starts else 0.0
    model['measured_max'] = max(model['sizes']) if model['sizes'] else 0

    wall = model['wall']
    if not wall.get('enough') or wall['marginal'] <= 0:
        model.update({'knee': None, 'limits': {}, 'chunk': None,
                      'reason': 'not enough batch sizes' if not wall.get('enough') else 'cost does not grow with the batch'})
        return model
    fixed = max(wall['fixed'], 0.0)
    model['knee'] = fixed / (KNEE_SHARE * wall['marginal'])
    limits = {'time': (budgets['time_s'] * 1000 - fixed) / wall['marginal']}
    mem = model['mem']
    if mem.get('enough') and mem['marginal'] > 0:
        limits['memory'] = (budgets['memory_mb'] - model['mem_start_mb'] - max(mem['fixed'], 0.0)) / mem['marginal']
    if model['bytes_per_msg']:
        limits['payload'] = PAYLOAD_LIMIT_BYTES / model['bytes_per_msg']
    model['limits'] = limits
    model['chunk'], model['reason'] = _apply_limits(nice_up(max(model['knee'], 1)), limits)
    # The payload limit is a warning in the queue module, not a failure, so the chunk without it is
    # shown too: the gap is what the warning costs in fixed cost per message.
    model['chunk_no_payload'], _ = _apply_limits(nice_up(max(model['knee'], 1)),
                                                 {k: v for k, v in limits.items() if k != 'payload'})
    model['fixed_share_at'] = {n: fixed / (fixed + wall['marginal'] * n) for n in (500, 1000, 10000)}
    model['measured'] = measured_curve(batches, limits)
    best = model['measured']
    if best['shape'] == 'U-shape' and best['best_size'] != model['chunk']:
        model['chunk'], model['reason'] = best['best_size'], 'measured optimum'
    model['extrapolated'] = bool(model['chunk'] and model['chunk'] > model['measured_max'])
    return model


def measured_curve(batches, limits):
    """Median time per message of each measured size, and where the measured curve is lowest."""
    by_size = {}
    for b in batches:
        if b.get('wall_ms') is not None and b['n'] and b['n'] >= 0.5 * b['chunk']:
            by_size.setdefault(b['chunk'], []).append(b['wall_ms'] / b['n'])
    cap = min(limits.values()) if limits else float('inf')
    curve = [(size, statistics.median(v), len(v)) for size, v in sorted(by_size.items())
             if len(v) >= MEASURED_MIN_BATCHES]
    usable = [c for c in curve if c[0] <= cap]
    if len(usable) < 2:
        return {'curve': curve, 'best_size': None, 'near_size': None, 'shape': 'too few sizes'}
    best_size, best_ms, _ = min(usable, key=lambda c: c[1])
    near_size = min(size for size, ms, _ in usable if ms <= (1 + MEASURED_NEAR) * best_ms)
    larger = [ms for size, ms, _ in usable if size > best_size]
    rises = bool(larger) and larger[-1] > (1 + MEASURED_RISE) * best_ms
    shape = 'U-shape' if rises else ('falls' if best_size == usable[-1][0] else 'flat')
    return {'curve': curve, 'best_size': best_size, 'best_ms': best_ms, 'near_size': near_size, 'shape': shape}


# ---------------------------------------------------------------------------------------------------
# Rendering.
# ---------------------------------------------------------------------------------------------------

def _fx(value, digits=1):
    return '-' if value is None else (fmt_int(value) if abs(value) >= 100 else f"{value:.{digits}f}")


def per_1000(fit, fmt):
    return fmt(fit['marginal'] * 1000) if fit.get('enough') else '-'


def render_selection(selection, meta):
    rows = [[f"<code>{esc(s['queue'])}</code>", f"{s['score']:.2f}" if s.get('score') is not None else '-',
             fmt_pct(100 * s.get('time_share', 0)), fmt_pct(100 * s.get('cpu_share', 0)),
             f"{fmt_int(s.get('mem_peak_mb', 0))} MB", fmt_int(s.get('messages_per_run', 0)),
             esc(', '.join(fmt_int(c) for c in s.get('chunk_configured', [])) or '-'), f"<code>{esc(s['producer'])}</code>"]
            for s in selection]
    head = [('queue', False), ('score', True), ('task time', True), ('CPU', True), ('peak memory increase', True),
            ('messages per run', True), ('configured chunk', True), ('filled by', False)]
    lede = (f"The {len(selection)} queues with the highest score over the {meta.get('runs_ranked', '?')} instrumented "
            f"runs of the campaigns {', '.join(esc(pathlib.Path(c).name) for c in meta.get('campaigns', []))}. Score = "
            "share of task time + share of CPU time + peak memory increase over the largest peak, each 0 to 1.")
    return rr.section('Which queues and why', lede, rr.table(head, rows))


def render_recommendations(models, selection):
    configured = {s['queue']: s.get('chunk_configured') or [] for s in selection}
    rows = []
    for queue, m in models.items():
        current = ', '.join(fmt_int(c) for c in configured.get(queue, [])) or '-'
        if m['chunk'] is None:
            rows.append([f"<code>{esc(queue)}</code>", esc(current), esc(f"{fmt_int(m['measured_max'])}"),
                         '-', '-', '-', '-', '-', '-', '-', "<b>-</b>", '-', esc(m['reason'])])
            continue
        lim = m['limits']
        rows.append([
            f"<code>{esc(queue)}</code>", esc(current), esc(f"{fmt_int(m['measured_max'])}"),
            esc(fmt_ms(m['wall']['fixed'])),
            esc(fmt_ms(m['work']['fixed']) if m['work'].get('enough') else '-'),
            esc(fmt_ms(m['wall']['marginal'] * 1000)),
            esc(per_1000(m['mem'], lambda v: f"{_fx(v)} MB")),
            esc(f"{_fx(m['db']['marginal'], 1)}" if m['db'].get('enough') else '-'),
            esc(fmt_int(m['knee'])),
            render_measured(m['measured']),
            f"<b>{esc(fmt_int(m['chunk']))}</b>" + (" <span class='muted'>(beyond measured)</span>" if m['extrapolated'] else ''),
            esc(fmt_int(m['chunk_no_payload'])) if m['chunk_no_payload'] else '-',
            esc(m['reason'] + (f"; weak fit, R² {m['wall']['r2']:.2f}" if (m['wall']['r2'] or 0) < WEAK_R2 else '')
                + (f"; memory limit {fmt_int(lim['memory'])}" if 'memory' in lim and m['reason'] != 'memory limit' else '')
                + (f"; payload limit {fmt_int(lim['payload'])}" if 'payload' in lim and m['reason'] != 'payload limit' else ''))])
    head = [('queue', False), ('configured chunk', True), ('largest batch measured', True), ('fixed per batch', True),
            ('of it inside the task', True),
            ('per 1 000 messages', True), ('memory per 1 000', True), ('queries per message', True),
            ('knee', True), ('measured best', True), ('recommended chunk', True), ('without the payload limit', True), ('why', False)]
    return rr.table(head, rows)


def render_measured(measured):
    if measured['best_size'] is None:
        return f"<span class='muted'>{esc(measured['shape'])}</span>"
    near = (f", within 10% from {fmt_int(measured['near_size'])}" if measured['near_size'] < measured['best_size'] else '')
    return esc(f"{fmt_int(measured['best_size'])} ({fmt_ms(measured['best_ms'])} per message, {measured['shape']}{near})")


def render_queue_panels(models, batches):
    groups = [[(b['n'], b['wall_ms']) for b in batches[q]] for q in models]
    x_range = rr._shared_x(groups) or (1, 10)
    out = []
    for queue, m in models.items():
        bs = batches[queue]
        cards = []
        wall_pts = [(b['n'], b['wall_ms']) for b in bs if b['wall_ms'] is not None]
        cards.append(rr.scatter_panel('Process time per batch', 'wall time of the task process, bootstrap and flush '
                                      'included; line: fixed + marginal fit', wall_pts, x_range,
                                      max((y for _, y in wall_pts), default=1) * 1.1, fmt_ms, m['wall']))
        per_msg = [(b['n'], b['wall_ms'] / b['n']) for b in bs if b['wall_ms'] is not None]
        cards.append(rr.scatter_panel('Time per message', 'process time over messages; flat means the fixed cost '
                                      'per batch no longer matters', per_msg, x_range,
                                      max((y for _, y in per_msg), default=1) * 1.1, fmt_ms))
        mem_pts = [(b['n'], b['mem_mb']) for b in bs if b['mem_mb'] is not None]
        cards.append(rr.scatter_panel('Memory increase per batch', 'peak memory increase of the task, MB',
                                      mem_pts, x_range, max((y for _, y in mem_pts), default=1) * 1.1 or 1,
                                      lambda v: f"{_fx(v)} MB", m['mem']))
        db_pts = [(b['n'], b['db_calls']) for b in bs if b['db_calls'] is not None]
        cards.append(rr.scatter_panel('SQL queries per batch', 'a slope near the first batches means queries per '
                                      'message that batching does not remove', db_pts, x_range,
                                      max((y for _, y in db_pts), default=1) * 1.1 or 1, fmt_int, m['db']))
        if any(b['search_calls'] for b in bs):
            se_pts = [(b['n'], b['search_calls']) for b in bs]
            cards.append(rr.scatter_panel('Search calls per batch', 'search requests of the task', se_pts, x_range,
                                          max((y for _, y in se_pts), default=1) * 1.1 or 1, fmt_int, m['search']))
        if any(b['kv_calls'] for b in bs):
            kv_pts = [(b['n'], b['kv_calls']) for b in bs]
            cards.append(rr.scatter_panel('Key-value calls per batch', 'key-value store requests of the task and its '
                                          'flush', kv_pts, x_range, max((y for _, y in kv_pts), default=1) * 1.1 or 1,
                                          fmt_int, m['kv']))
        line = (f"{fmt_int(m['batches'])} batches, sizes {fmt_int(min(m['sizes']))} to {fmt_int(m['measured_max'])}; "
                + (f"recommended chunk {fmt_int(m['chunk'])} ({m['reason']})" if m['chunk'] else m['reason']))
        out.append(rc.fold(rr.section(queue, '', f"<div class='grid2'>{''.join(cards)}</div>"),
                           'q-' + queue.replace('.', '-'), esc(line)))
    return ''.join(out)


def render(sweep_dir, stamp=None, budgets=None):
    from datetime import datetime, timezone
    sweep_dir = pathlib.Path(sweep_dir)
    meta, selection, steps, batches = load_sweep(sweep_dir)
    order = [s['queue'] for s in selection if s['queue'] in batches] + sorted(q for q in batches if q not in {s['queue'] for s in selection})
    models = {q: queue_model(batches[q], budgets) for q in order}
    budgets = {**BUDGETS, **(budgets or {})}
    now = stamp['time'] if stamp else datetime.now(timezone.utc)
    sweep_id = sweep_dir.name
    out = ["<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'>",
           "<meta name='viewport' content='width=device-width, initial-scale=1'>",
           f"<title>Batch-size sweep</title><style>{rr.CSS}{rc.EXTRA_CSS}{rc.FOLD_CSS}</style></head><body><div class='wrap'>",
           "<header class='top'><div class='eyebrow'>SOL-629 &middot; lab measurement &middot; not production behavior</div>"
           f"<h1>Batch size per queue: {esc(sweep_id)}</h1><div class='meta'>"
           f"<span>commit <code>{esc(str(meta.get('git', 'unknown')))}</code></span>"
           f"<span>{fmt_int(sum(len(v) for v in batches.values()))} batches in {fmt_int(len(steps))} steps</span>"
           f"<span>rendered {now.strftime('%Y-%m-%d %H:%M UTC')}</span></div></header>"]
    story = sweep_dir / 'story.md'
    if story.exists():
        out.append(rc.render_story(story.read_text()))
    lede = (f"Knee: the chunk at which the fixed cost per batch is {fmt_pct(100 * KNEE_SHARE, 0)} of the cost per message "
            f"or less. Limits: process memory within {fmt_int(budgets['memory_mb'])} MB and chunk payload within "
            f"{fmt_bytes(PAYLOAD_LIMIT_BYTES)}, the two values the queue module warns at (75% of 1 024 MB over 5 "
            f"worker processes, and 1 024 KB, <code>TaskMemoryUsageChecker</code>), and process time within "
            f"{fmt_int(budgets['time_s'])} s, half of the 60 s worker run. "
            "The recommended chunk is the knee rounded up to a common value, lowered to the first limit it passes. "
            "Measured best: the measured size with the lowest median time per message inside the limits (sizes "
            f"with {MEASURED_MIN_BATCHES} or more batches at least half full). A straight line cannot show a middle "
            f"optimum, so when the time per message at the larger sizes rises more than {fmt_pct(100 * MEASURED_RISE, 0)} "
            "above that best (U-shape), the measured best replaces the knee: it is a measured optimum, the knee is not. "
            "Fits are least squares over every batch of the queue. Fixed per batch is the whole process; the part "
            "outside the task is the PHP bootstrap and the broker connection, which the regular worker pays for every "
            "batch too, because it starts one process per batch.")
    out.append(rr.section('Recommended chunk sizes', lede, render_recommendations(models, selection)))
    out.append(rr.section('Each queue', 'One fold per queue: every batch as a dot, chunk size on a log axis shared by '
                          'all panels.', ''))
    out.append(render_queue_panels(models, batches))
    out.append(render_selection(selection, meta))
    method = (f"<ul><li>Scheduler suspended for the sweep ({esc(meta.get('scheduler', ''))}); one consumer at a time, "
              "so no batch is split between two tasks.</li>"
              "<li>Per queue and chunk size: purge all work queues, fill the queue with its regular producer, then run "
              f"<code>queue:task:start</code> one process at a time, up to {fmt_int(meta.get('max_batches', 0))} batches, "
              "chunk set by <code>QUEUE_MESSAGE_CHUNK_SIZE_MAP</code>.</li>"
              "<li>Every task is a new PHP process, as under the regular worker; process time includes its bootstrap.</li>"
              "<li>The largest measurable chunk is the demo backlog of the queue. A recommendation above it is an "
              "extrapolation of the fit and is marked.</li>"
              "<li>Small chunks sample the head of the queue only (the first batches); for a queue with mixed events "
              "the head can differ from the rest.</li>"
              f"<li>Error messages added during the sweep: {esc(str(meta.get('error_messages_added', 'not recorded')))}.</li></ul>")
    out.append(rr.section('Method and limits', '', method))
    out.append("<div class='footer'>SOL-629 &middot; generated by <code>tools/ps-metrics/render-sweep.py</code> from the "
               "sweep directory." + rr.stamp_footer(stamp) + "</div>")
    out.append(f"</div><div id='tip' role='tooltip'></div><script>{rr.TOOLTIP_JS}{rc.FOLD_JS}</script></body></html>")
    return '\n'.join(part for part in out if part)


def main():
    argv = [a for a in sys.argv]
    budgets = {}
    for flag, key in (('--memory-budget-mb', 'memory_mb'), ('--time-budget-s', 'time_s')):
        if flag in argv:
            i = argv.index(flag)
            budgets[key] = float(argv[i + 1])
            del argv[i:i + 2]
    return rr.cli_main(argv, lambda source, stamp: render(source, stamp, budgets), ['render-sweep.py'],
                       'test_sweep.py', 'usage: render-sweep.py <sweep-dir> [new-output.html] | --self-test')


if __name__ == '__main__':
    sys.exit(main())
