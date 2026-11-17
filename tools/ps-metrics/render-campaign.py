#!/usr/bin/env python3
"""Render a SOL-629 measurement campaign (contract C5) as one self-contained HTML story.

A campaign is several run bundles of the same input, some instrumented and some not, described by
campaign.json. The report opens with a short summary (verdict, top bottlenecks, story); every other
section is folded with its one-line result in the fold title. It answers, in this order: how fast is the pipeline and how sure are we
(verdict, spread), where does the time go (timeline, ranked bottlenecks with their evidence), what
the cost distributions look like, how much the instrumentation itself costs, and whether the data
can be trusted (data quality).

Generated text states computed facts only. Interpretation comes from an optional `story.md` beside
campaign.json, inserted after the headline.

Usage:
    render-campaign.py <campaign-dir> [output.html]    output defaults to a new <campaign-dir>/report-<UTC>-<sha>.html
    render-campaign.py --self-test                    runs test_campaign.py beside this script

Stdlib only. Shares formatting, histogram and chart helpers with render-report.py by importing it.
"""

import importlib.util
import json
import math
import pathlib
import re
import statistics
import sys
from collections import defaultdict
from datetime import datetime, timezone

HERE = pathlib.Path(__file__).resolve().parent
_spec = importlib.util.spec_from_file_location('render_report', HERE / 'render-report.py')
rr = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(rr)

esc, fmt_ms, fmt_int, fmt_num, fmt_pct = rr.esc, rr.fmt_ms, rr.fmt_int, rr.fmt_num, rr.fmt_pct
tile, table, section, hbars = rr.tile, rr.table, rr.section, rr.hbars

NOT_MEASURED = rr.NOT_MEASURED
NOT_ENOUGH = 'not enough runs'

# Colour by the job it does. Instrumented and uninstrumented runs: categorical slots 1 and 2. Stages
# are ordered, so they take one ordinal blue ramp (validated: monotone, light end 2.06:1). The time
# breakdown is categorical: slots 1 to 4 in the validated order, "other" in the neutral edge grey.
C_INSTR, C_PLAIN = rr.SERIES_1, rr.SERIES_2
STAGE_COLOURS = ['#86b6ef', '#3987e5', '#1c5cab', '#0d366b']
BREAKDOWN = [('db', 'database', '#2a78d6'), ('kv', 'key-value store', '#eb6834'),
             ('search', 'search', '#1baf7a'), ('broker', 'broker', '#eda100')]
OTHER_COLOUR = rr.EDGE
# Direct-sync flush (C6) continues the validated categorical order: slot 5.
FLUSH_COLOUR = '#e87ba4'

EXTRA_CSS = """
.muted{color:var(--muted)}
#tip{white-space:pre-line}
code{overflow-wrap:anywhere}
.verdict{font-size:15px;margin:14px 0 0;padding:0;list-style:none}
.verdict li{padding:6px 0;border-top:1px solid var(--grid)}
.verdict li:first-child{border-top:0}
.rank{display:inline-grid;place-items:center;min-width:22px;height:22px;border-radius:6px;background:var(--soft);
font-weight:650;margin-right:8px;font-size:12.5px}
.story{font-size:14.5px;max-width:860px}
.story h2,.story h3,.story h4{margin:16px 0 6px}
.story p{margin:8px 0}.story ul{margin:8px 0;padding-left:22px}
.story pre{background:var(--soft);padding:10px 12px;border-radius:8px;overflow-x:auto;font-size:12.5px}
.gantt{display:grid;grid-template-columns:minmax(0,110px) minmax(0,1fr) auto;gap:6px 10px;align-items:center;margin:12px 0 6px}
.gantt .track{display:flex;gap:2px;height:18px}
.gantt .track>div{height:18px;min-width:2px}
.gantt .track>div:first-child{border-radius:4px 0 0 4px}.gantt .track>div:last-child{border-radius:0 4px 4px 0}
.gantt .lab{font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gantt .val{font-size:12.5px;color:var(--ink2);font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap}
.kind{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;vertical-align:0}
svg.line{display:block;width:100%;height:auto;margin-top:6px}
svg.line text{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
svg.line .hc:hover{fill:rgba(11,11,11,.05)}
.strips{margin:12px 0}
.sr{display:grid;grid-template-columns:minmax(0,34%) minmax(0,1fr) 13em;gap:4px 12px;align-items:center;margin:8px 0}
.strip{position:relative;height:28px;border-radius:4px;background:linear-gradient(to right,transparent calc(50% - .5px),var(--axis) calc(50% - .5px),var(--axis) calc(50% + .5px),transparent calc(50% + .5px)),var(--surface);border:1px solid var(--grid)}
.strip .d{position:absolute;top:50%;width:10px;height:10px;margin:-5px 0 0 -5px;border-radius:50%;border:2px solid var(--card);box-sizing:content-box}
.strip .d:hover{transform:scale(1.3)}
.strips .axis{font-size:11.5px;color:var(--muted);display:flex;justify-content:space-between}
.strips .lab{font-size:13px;overflow-wrap:anywhere}
.strips .val{font-size:12.5px;color:var(--ink2);text-align:right;font-variant-numeric:tabular-nums}
.bd{margin:10px 0}
.bdr{display:grid;grid-template-columns:minmax(0,36%) minmax(0,1fr) 15em;gap:4px 10px;align-items:center;margin:6px 0}
.bdr .track{display:flex;gap:2px;height:16px}
.bdr .track>div{height:16px;min-width:2px}
.bdr .track>div:first-child{border-radius:4px 0 0 4px}.bdr .track>div:last-child{border-radius:0 4px 4px 0}
.bdr .lab{font-size:12.5px;overflow-wrap:anywhere}
.bdr .val{font-size:12.5px;color:var(--ink2);text-align:right}
.multiples{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,200px),1fr));gap:10px;margin-top:10px}
.mini{border:1px solid var(--border);border-radius:8px;padding:8px 8px 4px;background:var(--surface);min-width:0}
.mini .t{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mini .s{font-size:11px;color:var(--ink2);font-variant-numeric:tabular-nums}
.mini svg{display:block;width:100%;height:auto;margin-top:4px}
.badge{display:inline-block;border-radius:999px;padding:1px 8px;font-size:12px;font-weight:600}
.badge.ok{background:#e3f4e3;color:var(--good-text)}.badge.bad{background:#fbe5e5;color:var(--crit)}
.badge.na{background:var(--soft);color:var(--ink2)}
h3.sp{margin-top:20px}
td.name{min-width:190px}
@media (max-width:720px){
 .gantt{grid-template-columns:minmax(0,70px) minmax(0,1fr) auto}
 .sr,.bdr{grid-template-columns:minmax(0,1fr) auto}
 .sr .strip,.sr .axis,.bdr .track{grid-column:1 / -1;order:3}
 .sr .axis{order:0}
}
"""


# ---------------------------------------------------------------------------------------------
# Small statistics. Run-level figures only: a median over runs of a per-run total. Distributions
# are never summarised this way; their buckets are summed and percentiles read off the merge.
# ---------------------------------------------------------------------------------------------

def spread(values):
    """median, min, max, spread % ((max - min) / median) and n over the non-null values."""
    values = [v for v in values if v is not None]
    if not values:
        return {'n': 0, 'median': None, 'min': None, 'max': None, 'spread_pct': None}
    median = statistics.median(values)
    lo, hi = min(values), max(values)
    return {'n': len(values), 'median': median, 'min': lo, 'max': hi,
            'spread_pct': (100 * (hi - lo) / median) if median else None}


def overhead(instrumented, plain):
    """Instrumentation cost as a delta of medians, with the run-to-run noise beside it.

    Returns {'enough': False} when either side has fewer than 2 runs: one run per side cannot tell
    overhead from variance, so no number is produced at all. Noise is the larger of the two sides'
    min-to-max ranges; a delta inside it is reported as indistinguishable from noise.
    """
    instrumented = [v for v in instrumented if v is not None]
    plain = [v for v in plain if v is not None]
    if len(instrumented) < 2 or len(plain) < 2:
        return {'enough': False, 'n_instr': len(instrumented), 'n_plain': len(plain)}
    mi, mp = statistics.median(instrumented), statistics.median(plain)
    noise = max(max(instrumented) - min(instrumented), max(plain) - min(plain))
    delta = mi - mp
    return {'enough': True, 'n_instr': len(instrumented), 'n_plain': len(plain), 'median_instr': mi,
            'median_plain': mp, 'delta': delta, 'delta_pct': (100 * delta / mp) if mp else None,
            'noise': noise, 'distinguishable': abs(delta) > noise}


def merge_histograms(histograms):
    """Sum bucket counts of several histograms after label repair. Never averages percentiles."""
    return rr.merge_buckets([rr.normalize_histogram(h) for h in histograms if h])


def sum_or_none(records, field):
    """Sum a C3/C4 field; None (not measured) when no record carries a non-null value for it."""
    values = [r.get(field) for r in records if r.get(field) is not None]
    return sum(values) if values else None


# ---------------------------------------------------------------------------------------------
# Loading.
# ---------------------------------------------------------------------------------------------

class Run:
    def __init__(self, entry, campaign_dir):
        self.run_id = entry.get('run_id') or entry.get('bundle') or '?'
        bundle = entry.get('bundle') or self.run_id
        candidate = campaign_dir / bundle
        self.bundle = candidate if candidate.is_dir() else pathlib.Path(bundle)
        self.status = entry.get('status', 'ok')
        self.reason = {'ok': '', 'errors': 'messages ended in error queues; timings kept'}.get(
            self.status, f"runner exit code {entry.get('exit_code', '?')}")
        self.meta, self.stages = {}, []
        self.tasks, self.listeners, self.imports, self.samples, self.storage = [], [], [], [], []
        self.flushes, self.has_flush_file = [], False
        if not self.bundle.is_dir():
            self.status, self.reason = 'failed', 'bundle directory missing'
        else:
            meta_path = self.bundle / 'meta.json'
            if meta_path.exists():
                self.meta = json.loads(meta_path.read_text())
            stages_path = self.bundle / 'stages.csv'
            if stages_path.exists():
                for line in stages_path.read_text().splitlines()[1:]:
                    parts = line.split(',')
                    if len(parts) == 4:
                        self.stages.append((parts[0], int(parts[1]), int(parts[2]), int(parts[3])))
            self.tasks = rr.load_jsonl(self.bundle / 'task-runs.jsonl')
            self.listeners = rr.load_jsonl(self.bundle / 'listener-runs.jsonl')
            self.imports = rr.load_jsonl(self.bundle / (self.meta.get('import_runs_file') or 'import-runs.jsonl'))
            self.samples = [s for s in rr.load_jsonl(self.bundle / 'queue-samples.jsonl') if 'queue' in s]
            self.storage = rr.load_jsonl(self.bundle / 'storage-samples.jsonl')
            flush_path = self.bundle / (self.meta.get('sync_flush_runs_file') or 'sync-flush-runs.jsonl')
            self.has_flush_file = flush_path.exists()
            self.flushes = [f for f in rr.load_jsonl(flush_path) if f.get('kind', 'direct_sync_flush') == 'direct_sync_flush']
            if self.status in ('ok', 'errors') and not self.stages:
                self.status, self.reason = 'failed', 'no stage timings'
        self.instrumented = entry.get('instrumented', self.meta.get('instrumented', True))

    @property
    def ok(self):
        # A run with messages in error queues finished its stages, so its timings are valid measurements.
        return self.status in ('ok', 'errors')

    @property
    def label(self):
        return f"{self.run_id} ({'instrumented' if self.instrumented else 'plain'})"

    @property
    def t0(self):
        return self.stages[0][1] if self.stages else None

    @property
    def e2e_s(self):
        return sum(s[3] for s in self.stages) if self.stages else None

    def stage_s(self, name):
        return next((s[3] for s in self.stages if s[0] == name), None)

    @property
    def messages(self):
        return sum(t.get('chunk_received', 0) for t in self.tasks) if self.tasks else None

    @property
    def rows_imported(self):
        rows = [r.get('imported') or 0 for r in self.imports if r.get('kind') == 'importer']
        return sum(rows) if rows else None


def load_campaign(campaign_dir):
    campaign_dir = pathlib.Path(campaign_dir)
    path = campaign_dir / 'campaign.json'
    campaign = json.loads(path.read_text()) if path.exists() else {
        'id': campaign_dir.name, 'runs': [{'run_id': p.name, 'bundle': p.name}
                                          for p in sorted(campaign_dir.iterdir()) if (p / 'stages.csv').exists()]}
    runs = [Run(entry, campaign_dir) for entry in campaign.get('runs', [])]
    story_path = campaign_dir / 'story.md'
    story = story_path.read_text() if story_path.exists() else None
    return campaign, runs, story


# ---------------------------------------------------------------------------------------------
# story.md: a deliberately tiny Markdown subset (headings, paragraphs, bullets, bold, code, pipe tables).
# ---------------------------------------------------------------------------------------------

def _inline(text):
    parts = re.split(r'(`[^`]+`)', text)
    out = []
    for part in parts:
        if part.startswith('`') and part.endswith('`') and len(part) > 1:
            out.append(f"<code>{esc(part[1:-1])}</code>")
        else:
            html = esc(part)
            html = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', html)
            html = re.sub(r'(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])', r'<i>\1</i>', html)
            out.append(html)
    return ''.join(out)


_TABLE_RULE = re.compile(r'^\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)*\|?$')
_NUMERIC_CELL = re.compile(r'^[~<>]?[-+]?[\d.,]+\s*[%a-zµ]*(\s*\(.*\))?$', re.I)


def _cells(line):
    return [c.strip() for c in line.strip().strip('|').split('|')]


def _table_html(lines):
    """A pipe table: header row, rule row, body rows. A column is numeric (right aligned) when its rule
    ends in a colon, or when every body cell starts with a number."""
    head, rule, body = _cells(lines[0]), _cells(lines[1]), [_cells(l) for l in lines[2:]]
    width = len(head)
    body = [(row + [''] * width)[:width] for row in body]
    numeric = []
    for i in range(width):
        r = rule[i] if i < len(rule) else ''
        column = [row[i] for row in body if row[i]]
        numeric.append(r.endswith(':') or (i > 0 and bool(column) and all(_NUMERIC_CELL.match(c) for c in column)))
    return table([(h, n) for h, n in zip(head, numeric)], [[_inline(c) for c in row] for row in body])


def markdown_to_html(text):
    out, para, items, code = [], [], [], None
    lines = text.splitlines()

    def flush():
        if para:
            out.append(f"<p>{_inline(' '.join(para))}</p>")
            para.clear()
        if items:
            out.append('<ul>' + ''.join(f"<li>{_inline(i)}</li>" for i in items) + '</ul>')
            items.clear()

    i = 0
    while i < len(lines):
        line = lines[i]
        i += 1
        if code is not None:
            if line.strip().startswith('```'):
                out.append(f"<pre><code>{esc(chr(10).join(code))}</code></pre>")
                code = None
            else:
                code.append(line)
            continue
        stripped = line.strip()
        if stripped.startswith('|') and i < len(lines) and _TABLE_RULE.match(lines[i].strip()):
            flush()
            block = [stripped, lines[i].strip()]
            i += 1
            while i < len(lines) and lines[i].strip().startswith('|'):
                block.append(lines[i].strip())
                i += 1
            out.append(_table_html(block))
        elif stripped.startswith('```'):
            flush()
            code = []
        elif not stripped:
            flush()
        elif re.match(r'^#{1,4}\s', stripped):
            flush()
            level = len(stripped) - len(stripped.lstrip('#'))
            out.append(f"<h{level + 1}>{_inline(stripped[level:].strip())}</h{level + 1}>")
        elif re.match(r'^[-*]\s+', stripped):
            if para:
                out.append(f"<p>{_inline(' '.join(para))}</p>")
                para.clear()
            items.append(re.sub(r'^[-*]\s+', '', stripped))
        elif items and line.startswith((' ', '\t')):
            items[-1] += ' ' + stripped
        else:
            if items:
                flush()
            para.append(stripped)
    if code is not None:
        out.append(f"<pre><code>{esc(chr(10).join(code))}</code></pre>")
    flush()
    return '\n'.join(out)


# ---------------------------------------------------------------------------------------------
# Aggregation.
# ---------------------------------------------------------------------------------------------

join_flushes = rr.join_flushes
histogram_from_values = rr.histogram_from_values


def ledger(run):
    """Every measured span of work in one run, in milliseconds, keyed by a readable label.

    Import types (importer wall time), the import stage remainder no importer covered, the publish
    and sync console stages (no finer records), and worker time per queue (task-run ms_total).
    Queue workers can run in parallel, so the ledger total can exceed the run's wall clock; shares
    are of the ledger, which is stated wherever they are shown.
    """
    items = defaultdict(float)
    importers, _, _, _ = rr.aggregate_imports(run.imports)
    for import_type, b in importers.items():
        items[f"import: {import_type}"] += b['ms_wall']
    import_s = run.stage_s('import')
    if importers and import_s is not None:
        rest = import_s * 1000 - sum(b['ms_wall'] for b in importers.values())
        if rest > 0:
            items['import: unattributed remainder'] += rest
    elif import_s is not None:
        items['stage: import'] += import_s * 1000
    for name in ('publish', 'sync'):
        seconds = run.stage_s(name)
        if seconds:
            items[f"stage: {name}"] += seconds * 1000
    # Direct-sync flushes: a queue's flush is part of that queue's true cost; an import flush ran
    # inside the import stage but outside every importer, so it is carved out of the remainder.
    by_queue, elsewhere = join_flushes(run.tasks, run.flushes)
    import_flush = sum(f.get('ms') or 0.0 for f in elsewhere.get('import', []))
    source = 'import: unattributed remainder' if items.get('import: unattributed remainder') else 'stage: import'
    if import_flush and items.get(source):
        carved = min(import_flush, items[source])
        items['import: direct sync flush'] += carved
        items[source] -= carved
    worker_ms = 0.0
    for task in run.tasks:
        items[f"queue: {task['queue']}"] += task.get('ms_total', 0.0)
        worker_ms += task.get('ms_total', 0.0)
    for queue, flushes in by_queue.items():
        flush_ms = sum(f.get('ms') or 0.0 for f in flushes)
        items[f"queue: {queue}"] += flush_ms
        worker_ms += flush_ms
    drain_s = run.stage_s('drain')
    if drain_s is not None and drain_s * 1000 > worker_ms:
        items['drain: outside task runs'] += drain_s * 1000 - worker_ms
    return dict(items)


def rank_map(values):
    """{key: 1-based rank by value, largest first}."""
    return {key: i + 1 for i, (key, _) in enumerate(sorted(values.items(), key=lambda kv: -kv[1]))}


def aggregate_ledger(runs):
    per_run = {run.run_id: ledger(run) for run in runs}
    keys = set().union(*per_run.values()) if per_run else set()
    rows = []
    for key in keys:
        shares, totals, ranks = [], [], []
        for run_id, items in per_run.items():
            total = sum(items.values()) or 1
            shares.append(100 * items.get(key, 0.0) / total)
            totals.append(items.get(key, 0.0))
            ranks.append(rank_map(items).get(key, len(items) + 1))
        rows.append({'key': key, 'ms_sum': sum(totals), 'ms': spread(totals), 'share': spread(shares),
                     'ranks': ranks})
    grand = sum(r['ms_sum'] for r in rows) or 1
    for r in rows:
        r['share_all'] = 100 * r['ms_sum'] / grand
    rows.sort(key=lambda r: -r['ms_sum'])
    top3_by_run = [tuple(k for k, _ in sorted(items.items(), key=lambda kv: -kv[1])[:3]) for items in per_run.values()]
    stable = len(set(top3_by_run)) <= 1
    same_set = len({frozenset(t) for t in top3_by_run}) <= 1
    return rows, {'stable': stable, 'same_set': same_set, 'runs': len(per_run)}


def breakdown_of(records, total_field):
    """Sum C3 breakdowns over records. None when no record carries one (not measured, never 0).

    Only records with a breakdown contribute, to the total as well, so `other` is never inflated by
    records the hooks did not see. A category whose every value is null stays None.
    """
    with_bd = [r for r in records if isinstance(r.get('breakdown'), dict)]
    if not with_bd:
        return None
    total = sum(r.get(total_field) or 0.0 for r in with_bd)
    result = {'total': total, 'records': len(with_bd)}
    measured = 0.0
    for key, _, _ in BREAKDOWN:
        ms = [r['breakdown'].get(f"{key}_ms") for r in with_bd if r['breakdown'].get(f"{key}_ms") is not None]
        calls = [r['breakdown'].get(f"{key}_calls") for r in with_bd if r['breakdown'].get(f"{key}_calls") is not None]
        result[key] = sum(ms) if ms else None
        result[f"{key}_calls"] = sum(calls) if calls else None
        measured += result[key] or 0.0
    result['other'] = total - measured
    return result


def aggregate_queues(runs):
    """Per queue, merged over runs. Cost is task-run time plus the queue's direct-sync flush (C6).

    `flush_ms` is None when no run has a flush file (not measured); with a file and no flush for
    that queue it is 0, which under queue sync is the true value.
    """
    queues = defaultdict(lambda: {'tasks': [], 'flushes': [], 'per_run_ms': {}})
    flush_measured = any(run.has_flush_file for run in runs)
    for run in runs:
        by_queue, _ = join_flushes(run.tasks, run.flushes)
        for task in run.tasks:
            q = queues[task['queue']]
            q['tasks'].append(task)
            q['per_run_ms'][run.run_id] = q['per_run_ms'].get(run.run_id, 0.0) + task.get('ms_total', 0.0)
        for queue, flushes in by_queue.items():
            q = queues[queue]
            q['flushes'] += flushes
            q['per_run_ms'][run.run_id] = q['per_run_ms'].get(run.run_id, 0.0) + sum(f.get('ms') or 0.0 for f in flushes)
    rows_total = sum(run.rows_imported or 0 for run in runs)
    out = {}
    for name, q in queues.items():
        tasks = q['tasks']
        messages = sum(t.get('chunk_received', 0) for t in tasks)
        configured = sum(t.get('chunk_configured', 0) for t in tasks)
        ms_process = sum(t.get('ms_process', 0.0) for t in tasks)
        ms_total = sum(t.get('ms_total', 0.0) for t in tasks)
        flushes = q['flushes']
        flush_ms = sum(f.get('ms') or 0.0 for f in flushes) if flush_measured else None
        out[name] = {
            'flush_ms': flush_ms, 'flush_calls': len(flushes) if flush_measured else None,
            'flush_messages': sum_or_none(flushes, 'messages') if flush_measured else None,
            'flush_errors': sum(1 for f in flushes if f.get('error')) if flush_measured else None,
            'flush_breakdown': breakdown_of(flushes, 'ms'),
            'flush_hist': histogram_from_values(f.get('ms') for f in flushes),
            'cost_ms': ms_total + (flush_ms or 0.0),
            'batches': len(tasks), 'messages': messages, 'ms_process': ms_process, 'ms_total': ms_total,
            'ms_per_msg': ms_process / messages if messages else None,
            'msg_per_s': messages / (ms_process / 1000) if ms_process else None,
            'fanout': messages / rows_total if rows_total else None,
            'fill': messages / configured if configured else None,
            'age': merge_histograms(t.get('age_hist') for t in tasks),
            'attempt_age': merge_histograms(t.get('attempt_age_hist') for t in tasks),
            'retried': sum_or_none(tasks, 'retried'),
            'failed_after_retry': sum_or_none(tasks, 'failed_after_retry'),
            'breakdown': breakdown_of(tasks, 'ms_total'),
            'per_run_ms': q['per_run_ms'],
        }
    return out


def aggregate_listeners(runs):
    groups = defaultdict(lambda: {'records': [], 'per_run_ms': {}, 'queue': ''})
    for run in runs:
        for row in run.listeners:
            g = groups[(row.get('listener', '?'), row.get('event', '?'))]
            g['records'].append(row)
            g['queue'] = row.get('queue', '') or g['queue']
            g['per_run_ms'][run.run_id] = g['per_run_ms'].get(run.run_id, 0.0) + (row.get('ms') or 0.0)
    out = {}
    for key, g in groups.items():
        records = g['records']
        calls = sum(r.get('calls', 0) for r in records)
        messages = sum(r.get('messages', 0) for r in records)
        ms = sum(r.get('ms') or 0.0 for r in records)
        out[key] = {'queue': g['queue'], 'calls': calls, 'messages': messages, 'ms': ms,
                    'ms_per_msg': ms / messages if messages else None,
                    'msg_per_s': messages / (ms / 1000) if ms else None,
                    'per_call': messages / calls if calls else None,
                    'hist': merge_histograms(r.get('ms_per_msg_hist') for r in records),
                    'errors': sum_or_none(records, 'errors'),
                    'breakdown': breakdown_of(records, 'ms'),
                    'per_run_ms': g['per_run_ms']}
    return out


def rank_ranges(per_key_run_ms, run_ids):
    """For each key, (best rank, worst rank) across runs, ranking keys by time within each run."""
    ranges = defaultdict(list)
    for run_id in run_ids:
        values = {k: v['per_run_ms'].get(run_id, 0.0) for k, v in per_key_run_ms.items()}
        for k, r in rank_map(values).items():
            ranges[k].append(r)
    return {k: (min(v), max(v)) for k, v in ranges.items()}


def rank_text(rng):
    lo, hi = rng
    return f"{lo}, stable" if lo == hi else f"{lo} to {hi}, unstable"


na = rr.na


# ---------------------------------------------------------------------------------------------
# Chart pieces.
# ---------------------------------------------------------------------------------------------

def _nice_step(span, wanted=5):
    raw = span / wanted if span > 0 else 1
    exponent = 10 ** math.floor(math.log10(raw))
    for m in (1, 2, 2.5, 5, 10):
        if m * exponent >= raw:
            return m * exponent
    return 10 * exponent


def _time_step(span_s):
    for step in (1, 2, 5, 10, 15, 30, 60, 120, 300, 600, 900, 1800, 3600, 7200):
        if span_s / step <= 7:
            return step
    return 14400


def line_chart(series, y_label, markers=(), fmt_y=fmt_int):
    """Lines over time since run start. series: [(label, colour, [(t_seconds, value)])].

    One y-axis, one measure. Hover columns list every series' value at that time (last sample at or
    before it), which is the crosshair. Returns the SVG and a down-sampled table twin.
    """
    series = [(label, colour, sorted(p for p in points if p[1] is not None and p[0] >= 0))
              for label, colour, points in series]
    series = [s for s in series if s[2]]
    if not series:
        return "<div class='empty'>No samples recorded</div>"
    x_max = max(p[0] for _, _, pts in series for p in pts) or 1
    y_top = max(p[1] for _, _, pts in series for p in pts)
    y_max = rr._nice_max(y_top) if y_top > 0 else 1
    width, height = 640, 240
    left, right, top, bottom = 56, 14, 26, 30
    pw, ph = width - left - right, height - top - bottom

    def x_of(t):
        return left + pw * t / x_max

    def y_of(v):
        return top + ph - ph * v / y_max

    out = [f"<svg class='line' viewBox='0 0 {width} {height}' role='img' aria-label='{esc(y_label)} over time'>"]
    for i in range(5):
        v = y_max * i / 4
        y = y_of(v)
        out.append(f"<line x1='{left}' x2='{width - right}' y1='{y:.1f}' y2='{y:.1f}' "
                   f"stroke='{rr.AXIS if i == 0 else rr.GRID}'/>")
        out.append(f"<text x='{left - 6}' y='{y + 4:.1f}' text-anchor='end' font-size='13' fill='{rr.MUTED}'>"
                   f"{esc(rr.fmt_compact(v))}</text>")
    step = _time_step(x_max)
    t = 0
    while t <= x_max + 1e-9:
        x = x_of(t)
        out.append(f"<line x1='{x:.1f}' x2='{x:.1f}' y1='{top + ph}' y2='{top + ph + 4}' stroke='{rr.AXIS}'/>")
        anchor = 'start' if t == 0 else ('end' if x > width - right - 20 else 'middle')
        out.append(f"<text x='{x:.1f}' y='{top + ph + 17}' text-anchor='{anchor}' font-size='13' "
                   f"fill='{rr.MUTED}'>{'0' if t == 0 else esc(fmt_ms(t * 1000))}</text>")
        t += step
    label_end = -1e9
    for name, t_mark in markers:
        if 0 < t_mark < x_max:
            x = x_of(t_mark)
            out.append(f"<line x1='{x:.1f}' x2='{x:.1f}' y1='{top - 4}' y2='{top + ph}' stroke='{rr.INK_2}' "
                       f"stroke-dasharray='3 3' stroke-opacity='.55'/>")
            # A label that would run into the previous one drops below the plot top instead.
            y_label = top - 8 if x + 3 > label_end else top + 10
            out.append(f"<text x='{x + 3:.1f}' y='{y_label}' font-size='12.5' fill='{rr.INK_2}'>{esc(name)}</text>")
            if y_label == top - 8:
                label_end = x + 3 + 7.5 * len(name) + 6
    for label, colour, pts in series:
        path = ' '.join(f"{'M' if i == 0 else 'L'}{x_of(t):.1f},{y_of(v):.1f}" for i, (t, v) in enumerate(pts))
        out.append(f"<path d='{path}' fill='none' stroke='{colour}' stroke-width='2' stroke-linejoin='round' "
                   f"stroke-opacity='.85'/>")
        if len(pts) <= 40:
            out += [f"<circle cx='{x_of(t):.1f}' cy='{y_of(v):.1f}' r='2.5' fill='{colour}'/>" for t, v in pts]

    def value_at(pts, t):
        best = None
        for pt, v in pts:
            if pt <= t:
                best = v
            else:
                break
        return best

    columns = 64
    col_w = pw / columns
    for i in range(columns):
        t_mid = x_max * (i + 0.5) / columns
        lines = [f"{fmt_ms(t_mid * 1000)} after run start"]
        lines += [f"{label}: {fmt_y(value_at(pts, t_mid)) if value_at(pts, t_mid) is not None else '-'}"
                  for label, _, pts in series]
        out.append(f"<rect class='hc' x='{left + i * col_w:.1f}' y='{top}' width='{col_w:.2f}' height='{ph}' "
                   f"fill='transparent' data-tip='{esc(chr(10).join(lines))}'/>")
    out.append("</svg>")

    rows = []
    for k in range(11):
        t = x_max * k / 10
        rows.append([esc(fmt_ms(t * 1000))] + [esc(fmt_y(value_at(pts, t))) if value_at(pts, t) is not None else '-'
                                                for _, _, pts in series])
    twin = ("<details><summary>As a table, every tenth of the time span</summary>"
            + table([('since run start', False)] + [(label, True) for label, _, _ in series], rows) + "</details>")
    return ''.join(out) + twin


def series_legend(entries):
    return "<div class='legend'>" + ''.join(f"<span><i style='background:{c}'></i>{esc(l)}</span>"
                                           for l, c in entries) + "</div>"


def dot_strips(rows, axis_note):
    """Per-figure strips on a shared axis of percent deviation from a reference.

    rows: (label, [(pct, colour, tip)], right_text). The axis is symmetric around 0 and wide enough
    for the largest deviation, never narrower than 5 percent so a tight figure does not look wild.
    """
    if not rows:
        return ''
    extent = max([abs(p) for _, dots, _ in rows for p, _, _ in dots] + [5])
    extent = rr._nice_max(extent)
    out = [f"<div class='strips'><div class='sr'><div></div><div class='axis'><span>-{extent:g}%</span><span>0</span>"
           f"<span>+{extent:g}%</span></div><div></div></div>"]
    for label, dots, text in rows:
        # Two kinds in one strip get their own lane; dots at the same spot fan out vertically, so a
        # coincident run is never hidden behind another.
        colours = list(dict.fromkeys(c for _, c, _ in dots))
        seen = defaultdict(int)
        marks = []
        for p, c, tip in dots:
            lane = 50 if len(colours) == 1 else (32 if colours.index(c) == 0 else 68)
            spot = (c, round(p / extent * 40))
            nudge = (seen[spot] + 1) // 2 * (1 if seen[spot] % 2 else -1) * 5
            seen[spot] += 1
            marks.append(f"<span class='d' style='left:{50 + 50 * p / extent:.2f}%;top:calc({lane}% + {nudge}px);"
                         f"background:{c}' data-tip='{esc(tip)}'></span>")
        marks = ''.join(marks)
        out.append(f"<div class='sr'><div class='lab'>{label}</div><div class='strip'>{marks}</div>"
                   f"<div class='val'>{text}</div></div>")
    out.append("</div>")
    return ''.join(out) + f"<div class='sub'>{esc(axis_note)}</div>"


def stacked_rows(rows, scale=None):
    """rows: (label, [(name, ms, colour)], total_ms, right_text).

    Without `scale` every row fills the track (shares of its own total); with it, rows share one
    absolute scale so lengths compare across rows.
    """
    out = ["<div class='bd'>"]
    for label, parts, total, text in rows:
        base = scale or total
        segs = ''.join(f"<div style='flex:{max(ms, 0) / base * 100 if base else 0:.3f} 0 0;background:{c}' "
                       f"data-tip='{esc(label)}\n{esc(name)}: {esc(fmt_ms(ms))} ({100 * ms / total:.1f}%)'></div>"
                       for name, ms, c in parts if ms and ms > 0 and total)
        if scale and total < scale:
            segs += f"<div style='flex:{100 * (scale - total) / scale:.3f} 0 0;background:transparent'></div>"
        out.append(f"<div class='bdr'><div class='lab'>{esc(label)}</div><div class='track'>{segs}</div>"
                   f"<div class='val'>{text}</div></div>")
    out.append("</div>")
    return ''.join(out)


def mini_hist(buckets, lo, hi, per_octave=2):
    """A small histogram on a shared log axis [lo, hi]; edge bins hold everything outside, in grey."""
    n_bins = max(4, int(math.ceil(math.log2(hi / lo) * per_octave)))
    counts = [0] * (n_bins + 2)
    for key, count in buckets.items():
        value = rr.bucket_sort_key(key)
        if value == float('inf') or value > hi:
            counts[-1] += count
        elif value <= lo:
            counts[0] += count
        else:
            counts[1 + min(n_bins - 1, int(math.log2(value / lo) * per_octave))] += count
    width, height = 200, 56
    top_c = max(counts) or 1
    slot = width / len(counts)
    out = [f"<svg viewBox='0 0 {width} {height + 4}' aria-hidden='true'>"
           f"<line x1='0' x2='{width}' y1='{height}' y2='{height}' stroke='{rr.AXIS}'/>"]
    for i, c in enumerate(counts):
        if not c:
            continue
        h = max(1.5, (height - 2) * c / top_c)
        colour = rr.EDGE if i in (0, len(counts) - 1) else C_INSTR
        out.append(f"<path d='{rr._bar_path(i * slot + 0.5, height - h, max(slot - 1, 0.8), h, 2)}' fill='{colour}'/>")

    def x_of(v):
        return slot * (1 + math.log2(v / lo) * per_octave)

    for q, dash in ((50, ''), (95, " stroke-dasharray='2 2'")):
        v = rr.percentile_from_buckets(buckets, q / 100)
        if v and v != float('inf') and lo < v <= hi:
            x = x_of(v)
            out.append(f"<line x1='{x:.1f}' x2='{x:.1f}' y1='0' y2='{height}' stroke='{rr.INK}' "
                       f"stroke-opacity='.7'{dash}/>")
    out.append("</svg>")
    return ''.join(out)


def small_multiples(items, unit):
    """items: [(title, buckets)]. One shared axis so shapes and positions compare at a glance."""
    items = [(t, b) for t, b in items if sum(b.values())]
    if not items:
        return ''
    lows, highs = [], []
    for _, b in items:
        lo, hi = rr.percentile_from_buckets(b, 0.01), rr.percentile_from_buckets(b, 0.99)
        if lo and lo != float('inf'):
            lows.append(lo)
        if hi and hi != float('inf'):
            highs.append(hi)
    if not lows or not highs:
        return ''
    lo = 2 ** math.floor(math.log2(min(lows)))
    hi = 2 ** math.ceil(math.log2(max(max(highs), lo * 4)))
    cards = []
    for title, b in items:
        n = sum(b.values())
        p50, p95 = rr.percentile_from_buckets(b, 0.5), rr.percentile_from_buckets(b, 0.95)
        cards.append(f"<div class='mini' data-tip='{esc(title)}\n{fmt_int(n)} {unit}, p50 {esc(fmt_ms(p50))}, "
                     f"p95 {esc(fmt_ms(p95))}'><div class='t'>{esc(title)}</div><div class='s'>{fmt_int(n)} {unit} "
                     f"&middot; p50 {esc(fmt_ms(p50))} &middot; p95 {esc(fmt_ms(p95))}</div>{mini_hist(b, lo, hi)}</div>")
    axis = (f"<div class='sub'>Shared axis for every panel: {esc(fmt_ms(lo))} on the left to {esc(fmt_ms(hi))} on the "
            f"right, two bins per doubling; grey edge bars hold everything outside it. Heights are scaled per panel "
            f"so the shape shows; the solid line is p50, the dashed one p95.</div>")
    return axis + f"<div class='multiples'>{''.join(cards)}</div>"


def badge(ok, text=None):
    if ok is None:
        return f"<span class='badge na'>{esc(text or 'n/a')}</span>"
    return f"<span class='badge {'ok' if ok else 'bad'}'>{esc(text or ('passed' if ok else 'failed'))}</span>"


def kind_dot(run):
    return f"<span class='kind' style='background:{C_INSTR if run.instrumented else C_PLAIN}'></span>"


# ---------------------------------------------------------------------------------------------
# Sections.
# ---------------------------------------------------------------------------------------------

def render_headline(campaign, instr, plain, failed, ledger_rows, ledger_meta, queues, listeners):
    e2e = spread([r.e2e_s for r in instr])
    msgs = spread([r.messages for r in instr])
    thr = spread([(r.messages / r.e2e_s) if r.messages and r.e2e_s else None for r in instr])
    rows = spread([r.rows_imported for r in instr])
    hero_value = esc(fmt_ms(e2e['median'] * 1000)) if e2e['median'] is not None else '-'
    hero_sub = (f"median of {e2e['n']} instrumented run{'s' if e2e['n'] != 1 else ''}, "
                f"{esc(fmt_ms(e2e['min'] * 1000))} to {esc(fmt_ms(e2e['max'] * 1000))}, spread "
                f"{fmt_pct(e2e['spread_pct'], 0)}") if e2e['n'] else 'no successful instrumented run'
    hero = (f"<div class='hero-figure'><div class='l'>End to end, import start to drain end</div>"
            f"<div class='v'>{hero_value}</div><div class='sub'>{hero_sub}</div></div>")

    all_ok = instr + plain
    control = [bool(r.meta.get('kv_keys') and r.meta.get('es_docs')) for r in all_ok if 'kv_keys' in r.meta]
    def range_sub(st, fmt):
        if not st['n']:
            return 'not recorded'
        return 'median, the same in every run' if st['min'] == st['max'] else f"median, {fmt(st['min'])} to {fmt(st['max'])}"

    cost = overhead([r.e2e_s for r in instr], [r.e2e_s for r in plain])
    if not cost['enough']:
        overhead_tile = tile('Instrumentation overhead', NOT_ENOUGH, f"{cost['n_instr']} instrumented, {cost['n_plain']} plain")
    else:
        overhead_tile = tile('Instrumentation overhead', f"{'+' if cost['delta'] >= 0 else ''}{fmt_pct(cost['delta_pct'])}",
                             'not distinguishable from noise' if not cost['distinguishable'] else
                             f"larger than the {fmt_ms(cost['noise'] * 1000)} run-to-run range")
    tiles = [tile('Messages consumed', fmt_int(msgs['median']) if msgs['n'] else '-', range_sub(msgs, fmt_int)),
             tile('Throughput', (fmt_num(thr['median']) + ' msg/s') if thr['n'] else '-',
                  'messages over end-to-end time, median'),
             tile('Rows imported', fmt_int(rows['median']) if rows['n'] else '-', range_sub(rows, fmt_int)),
             tile('Runs', f"{len(instr)} + {len(plain)}", f"instrumented + plain, {len(failed)} failed"),
             tile('Positive control', badge(all(control) if control else None,
                                            f"{sum(control)} of {len(control)} passed" if control else 'not recorded'),
                  'key-value keys and search documents above 0'),
             overhead_tile]

    body = [f"<div class='hero'>{hero}<div class='tiles'>{''.join(tiles)}</div></div>"]
    top = ledger_rows[:3]
    if top:
        total_ms = sum(r['ms_sum'] for r in ledger_rows) or 1
        items = []
        for i, r in enumerate(top, 1):
            detail = ''
            if r['key'].startswith('queue: '):
                q = r['key'][7:]
                inside = sorted(((k, v) for k, v in listeners.items() if v['queue'] == q), key=lambda kv: -kv[1]['ms'])
                q_ms = sum(v['ms'] for _, v in inside)
                if inside and q_ms:
                    (lst, ev), v = inside[0]
                    detail = (f"; heaviest listener {esc(lst)} on <code>{esc(ev)}</code>, "
                              f"{fmt_pct(100 * v['ms'] / q_ms, 0)} of that queue's listener time, "
                              f"{esc(fmt_ms(v['ms_per_msg']))} per message")
                if queues.get(q, {}).get('ms_per_msg') is not None:
                    detail = f", {esc(fmt_ms(queues[q]['ms_per_msg']))} per message" + detail
            items.append(f"<li><span class='rank'>{i}</span><b>{esc(r['key'])}</b>: "
                         f"{fmt_pct(100 * r['ms_sum'] / total_ms)} of measured time, median "
                         f"{esc(fmt_ms(r['ms']['median']))} per run (share {fmt_pct(r['share']['min'])} to "
                         f"{fmt_pct(r['share']['max'])} across runs){detail}</li>")
        stability = ('the top 3 are in the same order in every run' if ledger_meta['stable'] else
                     'the top 3 are the same items in every run, in a different order' if ledger_meta['same_set'] else
                     'the top 3 differ between runs')
        body.append(f"<h3 class='sp'>Largest measured costs</h3><div class='sub'>Share of the measured-time ledger "
                    f"(import types, console stages, worker time per queue), summed over {ledger_meta['runs']} "
                    f"instrumented run{'s' if ledger_meta['runs'] != 1 else ''}; {stability}.</div>"
                    f"<ul class='verdict'>{''.join(items)}</ul>")
    modes = {r.meta.get('sync_mode') for r in all_ok}
    if len(modes) > 1:
        body.append("<div class='note warn'><b>Mixed sync modes.</b> These runs did not all run in the same sync "
                    f"mode ({esc(', '.join(sorted(str(m) for m in modes)))}), so their figures are not comparable.</div>")
    elif modes and None not in modes:
        body.append(f"<div class='meta' style='margin-top:12px'>{rr.sync_mode_chip(all_ok[0].meta)}</div>")
    return f"<section>{''.join(body)}</section>"


def render_story(story):
    if not story or not story.strip():
        return ''
    return (f"<section id='story'><div class='eyebrow'>The story, written after reading the numbers</div>"
            f"<div class='story'>{markdown_to_html(story)}</div></section>")


def stage_markers(runs):
    names = []
    for run in runs:
        for name, *_ in run.stages:
            if name not in names:
                names.append(name)
    marks = []
    for name in names[1:]:
        offsets = [next((s[1] - run.t0 for s in run.stages if s[0] == name), None) for run in runs]
        offsets = [o for o in offsets if o is not None]
        if offsets:
            marks.append((name, statistics.median(offsets)))
    return marks


def render_timeline(instr, plain):
    runs = sorted(instr + plain, key=lambda r: r.t0 or 0)
    if not runs:
        return ''
    names = []
    for run in runs:
        for name, *_ in run.stages:
            if name not in names:
                names.append(name)
    colours = {n: STAGE_COLOURS[min(i, len(STAGE_COLOURS) - 1)] for i, n in enumerate(names)}
    longest = max(r.e2e_s or 0 for r in runs) or 1
    gantt = ["<div class='gantt'>"]
    for run in runs:
        segs = ''.join(f"<div style='flex:{100 * secs / longest:.3f} 0 0;background:{colours[name]}' "
                       f"data-tip='{esc(run.label)}\n{esc(name)}: {esc(fmt_ms(secs * 1000))}'></div>"
                       for name, _, _, secs in run.stages if secs > 0)
        rest = 100 * (longest - (run.e2e_s or 0)) / longest
        segs += f"<div style='flex:{rest:.3f} 0 0;background:transparent'></div>" if rest > 0.01 else ''
        gantt.append(f"<div class='lab'>{esc(run.run_id)}</div><div class='track'>{segs}</div>"
                     f"<div class='val'>{esc(fmt_ms((run.e2e_s or 0) * 1000))}</div>")
    gantt.append("</div>")
    legend = series_legend([(n, colours[n]) for n in names])
    cells = [[esc(r.label)] + [esc(fmt_ms(r.stage_s(n) * 1000)) if r.stage_s(n) is not None else '-'
                                                for n in names] + [esc(fmt_ms((r.e2e_s or 0) * 1000))] for r in runs]
    stage_table = table([('run', False)] + [(n, True) for n in names] + [('end to end', True)], cells)
    body = [legend, ''.join(gantt),
            "<details><summary>Stage times as a table</summary>" + stage_table + "</details>"]

    markers = stage_markers(runs)

    def lines(attr):
        out = []
        for run in runs:
            pts = attr(run)
            if pts:
                out.append((run.run_id + (' (plain)' if not run.instrumented else ''),
                            C_INSTR if run.instrumented else C_PLAIN, pts))
        return out

    def depth_points(run):
        totals = defaultdict(int)
        for s in run.samples:
            totals[round(s['ts'], 1)] += s.get('depth', 0)
        return [(ts - run.t0, v) for ts, v in sorted(totals.items())] if run.t0 else []

    def storage_points(field):
        return lambda run: [(s['ts'] - run.t0, s.get(field)) for s in run.storage if run.t0 and s.get('ts')]

    body.append("<h3 class='sp'>Over time, every run aligned on its import start</h3>"
                + series_legend([('instrumented runs', C_INSTR), ('plain runs', C_PLAIN)])
                + "<div class='sub'>Dashed lines are the median stage starts. Hover a chart for every run's value at "
                  "that moment.</div>")
    cards = []
    depth = lines(depth_points)
    cards.append("<div class='card'><h3>Queue depth, all queues summed</h3><div class='sub'>Messages waiting in the "
                 "broker</div>" + (line_chart(depth, 'queue depth', markers) if depth else
                                   "<div class='empty'>No broker samples in these runs</div>") + "</div>")
    for field, title, sub in (('kv_keys', 'Key-value store fill', 'Keys across every database, from INFO keyspace'),
                              ('search_docs', 'Search index fill', 'Documents across all indices, from the _count API')):
        series = lines(storage_points(field))
        card = [f"<div class='card'><h3>{title}</h3><div class='sub'>{sub}, once a second from import start to drain "
                "end</div>"]
        if series:
            card.append(line_chart(series, title, markers))
            facts = []
            for label, _, pts in series:
                values = [v for _, v in pts if v is not None]
                if not values:
                    continue
                final = values[-1]
                reach = next((t for t, v in pts if v is not None and final and v >= 0.9 * final), None)
                facts.append([esc(label), fmt_int(final), fmt_int(max(values)),
                              esc(fmt_ms(reach * 1000)) if reach is not None else '-',
                              fmt_int(sum(1 for _, v in pts if v is None))])
            card.append("<details><summary>Fill facts per run</summary>" + table(
                [('run', False), ('final', True), ('peak', True), ('reached 90% of final at', True),
                 ('unreadable samples', True)], facts) + "</details>")
        else:
            card.append(f"<div class='empty'>{NOT_MEASURED}: no storage-samples.jsonl in these runs</div>")
        cards.append(''.join(card) + "</div>")
    body.append(f"<div class='grid2'>{''.join(cards)}</div>")
    lede = ("Every successful run on one time axis, aligned on its own import start, so the spread between runs is "
            "visible stage by stage.")
    return section('Pipeline timeline', lede, ''.join(body))


def render_bottlenecks(instr, ledger_rows, ledger_meta, queues, listeners):
    if not instr:
        return ''
    run_ids = [r.run_id for r in instr]
    total_ms = sum(r['ms_sum'] for r in ledger_rows) or 1
    body = []
    top = ledger_rows[:12]
    body.append(f"<h3>Measured-time ledger</h3><div class='sub'>Top {len(top)} of {len(ledger_rows)} items by time "
                f"summed over {len(instr)} run{'s' if len(instr) != 1 else ''}; the value is the share of the ledger "
                "with its min to max across runs.</div>")
    body.append(hbars([(r['key'], r['ms_sum'],
                        f"{fmt_pct(100 * r['ms_sum'] / total_ms)} ({fmt_pct(r['share']['min'])} to {fmt_pct(r['share']['max'])})",
                        f"{r['key']}: median {fmt_ms(r['ms']['median'])} per run, rank {min(r['ranks'])} to {max(r['ranks'])}")
                       for r in top]))
    body.append("<details><summary>Ledger as a table</summary>" + table(
        [('item', False), ('share', True), ('median per run', True), ('min', True), ('max', True), ('rank across runs', True)],
        [[esc(r['key']), fmt_pct(100 * r['ms_sum'] / total_ms), esc(fmt_ms(r['ms']['median'])), esc(fmt_ms(r['ms']['min'])),
          esc(fmt_ms(r['ms']['max'])), rank_text((min(r['ranks']), max(r['ranks'])))] for r in ledger_rows]) + "</details>")
    verdict = ('stable: the top 3 keep their order in every run' if ledger_meta['stable'] else
               'unstable: the top 3 change order or membership between runs')
    body.append(f"<div class='note'><b>Ranking {verdict}.</b> The ledger adds import types, the import remainder no "
                "importer covered, the publish and sync console stages and worker time per queue. Workers can run in "
                "parallel, so its total can exceed the wall clock; shares are of the ledger.</div>")

    if queues:
        q_total = sum(q['cost_ms'] for q in queues.values()) or 1
        ranks = rank_ranges(queues, run_ids)
        ranked = sorted(queues.items(), key=lambda kv: -kv[1]['cost_ms'])
        flush_measured = any(q['flush_ms'] is not None for q in queues.values())
        top = ranked[:12]
        body.append("<h3 class='sp'>True cost per queue: processing plus storage flush</h3><div class='sub'>"
                    + ("Task-run time and, under direct sync, the storage and search flush the same process ran at "
                       "console terminate (C6, joined by pid), summed over every instrumented run."
                       if flush_measured else f"Task-run time summed over every instrumented run. Storage flush (C6) "
                       f"{NOT_MEASURED}: no sync-flush-runs.jsonl in these runs.") + "</div>")
        body.append(series_legend([('processing (task run)', C_INSTR)]
                                  + ([('storage flush (direct sync)', FLUSH_COLOUR)] if flush_measured else [])))
        body.append(stacked_rows([(name, [('processing', q['ms_total'], C_INSTR), ('storage flush', q['flush_ms'] or 0.0, FLUSH_COLOUR)],
                                   q['cost_ms'], f"{esc(fmt_ms(q['cost_ms']))} &middot; {fmt_pct(100 * q['cost_ms'] / q_total, 0)}")
                                  for name, q in top], scale=top[0][1]['cost_ms'] if top else None))
        rows = []
        for name, q in ranked:
            rows.append([esc(name), fmt_pct(100 * q['cost_ms'] / q_total), esc(fmt_ms(q['ms_total'])),
                         esc(na(q['flush_ms'], fmt_ms)), esc(fmt_ms(q['cost_ms'])), esc(fmt_ms(q['ms_per_msg'])),
                         fmt_num(q['msg_per_s']), fmt_int(q['messages']),
                         '-' if q['fanout'] is None else f"{q['fanout']:.3g}",
                         '-' if q['fill'] is None else f"{q['fill']:.2f}",
                         esc(fmt_ms(rr.percentile_from_buckets(q['age'], 0.95))),
                         na(q['retried'], fmt_int), na(q['failed_after_retry'], fmt_int),
                         rank_text(ranks.get(name, (0, 0)))])
        body.append("<h3 class='sp'>Per queue</h3><div class='sub'>Merged over every instrumented run. Share is of the "
                    "true cost (processing plus flush). Per message and msg/s use processing time; fan-out is messages "
                    "per imported row; fill is messages received over chunk size configured; age p95 is read off "
                    "merged buckets.</div>")
        body.append(table([('queue', False), ('share of cost', True), ('processing', True), ('storage flush', True),
                           ('true cost', True), ('per message', True), ('msg/s', True),
                           ('messages', True), ('per imported row', True), ('batch fill', True), ('age p95', True),
                           ('retried', True), ('failed after retry', True), ('rank across runs', True)], rows))

    if listeners:
        l_total = sum(v['ms'] for v in listeners.values()) or 1
        ranks = rank_ranges(listeners, run_ids)
        ranked = sorted(listeners.items(), key=lambda kv: -kv[1]['ms'])
        body.append("<h3 class='sp'>Per listener and event</h3><div class='sub'>Top 12 pairs by time; the table "
                    "below has every pair.</div>")
        body.append(hbars([(f"{lst} / {ev}", v['ms'], f"{fmt_pct(100 * v['ms'] / l_total)}",
                            f"{lst} on {ev}: {fmt_ms(v['ms'])}, {fmt_int(v['messages'])} messages, "
                            f"{fmt_ms(v['ms_per_msg'])} per message")
                           for (lst, ev), v in ranked[:12]], C_INSTR))
        rows = [[esc(lst), esc(ev), esc(v['queue']), fmt_pct(100 * v['ms'] / l_total), esc(fmt_ms(v['ms_per_msg'])),
                 fmt_num(v['msg_per_s']), fmt_int(v['messages']), '-' if v['per_call'] is None else f"{v['per_call']:.3g}",
                 na(v['errors'], fmt_int), rank_text(ranks.get((lst, ev), (0, 0)))]
                for (lst, ev), v in ranked]
        body.append("<details><summary>All listener and event pairs</summary>" + table(
            [('listener', False), ('event', False), ('queue', False), ('share of listener time', True),
             ('per message', True), ('msg/s', True), ('messages', True), ('messages per call', True), ('errors', True),
             ('rank across runs', True)], rows) + "</details>")

    body.append(render_breakdown(queues, listeners))
    lede = ("Ranked by time spent, with the measurements that explain each rank: cost per message, rate, fan-out, "
            "batch fill and queue age, and where it was measured, the split into database, key-value store, search "
            "and broker time.")
    return section('Bottlenecks, ranked', lede, ''.join(body))


def render_breakdown(queues, listeners):
    """C3 stacked bars. A queue row adds its direct-sync flush (C6) as its own segment, because the
    flush runs after the task run's span closed and is in none of its categories; the flush's own
    split is shown on a separate row beneath it."""
    candidates = []
    for name, q in sorted(queues.items(), key=lambda kv: -kv[1]['cost_ms'])[:8]:
        candidates.append((f"queue: {name}", q['breakdown'], q['flush_ms']))
        if q['flush_breakdown']:
            candidates.append((f"flush: {name}", q['flush_breakdown'], None))
    candidates += [(f"{lst} / {ev}", v['breakdown'], None)
                   for (lst, ev), v in sorted(listeners.items(), key=lambda kv: -kv[1]['ms'])[:8]]
    measured = [(label, bd, flush) for label, bd, flush in candidates if bd]
    if not measured:
        return ("<h3 class='sp'>Where the time goes inside</h3><div class='empty'><span>Time breakdown "
                f"{NOT_MEASURED}: no task-run, listener or flush record carries a <code>breakdown</code> (C3)</span></div>")
    rows, cells = [], []
    any_flush = False
    for label, bd, flush in measured:
        parts = [(name, bd[key], colour) for key, name, colour in BREAKDOWN if bd[key] is not None]
        parts.append(('other', max(bd['other'], 0.0), OTHER_COLOUR))
        total = bd['total']
        if flush:
            any_flush = True
            parts.append(('storage flush (direct sync)', flush, FLUSH_COLOUR))
            total += flush
        missing = [name for key, name, _ in BREAKDOWN if bd[key] is None]
        text = esc(fmt_ms(total)) + (f" &middot; {esc(', '.join(missing))} {NOT_MEASURED}" if missing else '')
        rows.append((label, parts, total, text))
        cells.append([esc(label), esc(fmt_ms(total))]
                     + [esc(na(bd[key], fmt_ms)) + ('' if bd[f"{key}_calls"] is None else f" ({fmt_int(bd[f'{key}_calls'])} calls)")
                        for key, _, _ in BREAKDOWN] + [esc(fmt_ms(bd['other'])), esc(fmt_ms(flush) if flush else '-')])
    legend = series_legend([(name, colour) for _, name, colour in BREAKDOWN] + [('other (total minus the rest)', OTHER_COLOUR)]
                           + ([('storage flush (direct sync)', FLUSH_COLOUR)] if any_flush else []))
    return ("<h3 class='sp'>Where the time goes inside</h3><div class='sub'>Time breakdown (C3) for the heaviest queues "
            "and listener pairs, as a share of each one's own total. Other is the span total minus the measured "
            "categories, so it includes PHP work and any category that was not measured. A queue row adds its storage "
            "flush; the <i>flush:</i> row beneath splits that flush itself.</div>" + legend
            + stacked_rows(rows) + "<details><summary>Breakdown as a table</summary>"
            + table([('span', False), ('total', True)] + [(name, True) for _, name, _ in BREAKDOWN]
                    + [('other', True), ('storage flush', True)], cells)
            + "</details>")


def flush_records(runs):
    """All C6 flushes of the given runs, split into queue-joined and per-context lists."""
    joined, elsewhere = [], defaultdict(list)
    for run in runs:
        by_queue, other = join_flushes(run.tasks, run.flushes)
        for flushes in by_queue.values():
            joined += flushes
        for context, flushes in other.items():
            elsewhere[context] += flushes
    return joined, dict(elsewhere)


# Batches and listener calls with fewer messages than this are left out of the spread: their cost per
# message is mostly the fixed cost of the call, which says nothing about how heavy the messages are.
MIX_MIN_MESSAGES = 10
# A listener counts for "between listeners" from this share of the queue's listener messages or time:
# a cheap listener with many messages and a heavy one with few are both what makes a mix.
MIX_MIN_SHARE = 0.05
MIX_SPLIT_BETWEEN = 5.0
MIX_EVEN_WITHIN = 3.0


def _width(values):
    """max / min of the positive values (p90 / p10 from 10 values on, so one outlier does not decide)."""
    values = sorted(v for v in values if v and v > 0)
    if len(values) < 2:
        return None
    if len(values) >= 10:
        lo, hi = values[int(0.1 * (len(values) - 1))], values[int(round(0.9 * (len(values) - 1)))]
        return hi / lo
    return values[-1] / values[0]


def _weighted_median(pairs):
    """Median of the values with each value weighted, so one wide listener with little time does not decide."""
    pairs = sorted((v, w) for v, w in pairs if w > 0)
    total = sum(w for _, w in pairs)
    if not total:
        return None
    seen = 0.0
    for value, weight in pairs:
        seen += weight
        if seen >= total / 2:
            return value
    return pairs[-1][0]


def queue_mix(tasks, listener_records):
    """Per queue: how far the cost per message spreads over its batches, and whether the spread comes
    from mixing listeners of different cost (between) or from uneven data inside one listener (within).

    A wide queue with narrow listeners is a split candidate: a dedicated queue per heavy event
    (`addListenerQueued(..., $eventQueueName)`) separates the costs. A wide listener is not helped by a split.
    """
    batches = defaultdict(list)
    for t in tasks:
        n = t.get('processed') or 0
        if n >= MIX_MIN_MESSAGES and t.get('ms_process'):
            batches[t['queue']].append(t['ms_process'] / n)
    calls = defaultdict(lambda: defaultdict(list))
    for r in listener_records:
        calls[r.get('queue', '')][r.get('listener', '?')].append(r)
    out = {}
    for queue in set(batches) | {q for q in calls if q}:
        per_listener = {}
        for listener, records in calls.get(queue, {}).items():
            ms = sum(r.get('ms') or 0.0 for r in records)
            messages = sum(r.get('messages') or 0 for r in records)
            per_listener[listener] = {'ms': ms, 'messages': messages, 'ms_per_msg': ms / messages if messages else None,
                                      'width': _width(r.get('ms_per_msg') for r in records
                                                      if (r.get('messages') or 0) >= MIX_MIN_MESSAGES)}
        total_messages = sum(v['messages'] for v in per_listener.values()) or 1
        total_ms = sum(v['ms'] for v in per_listener.values()) or 1
        counted = [v for v in per_listener.values() if v['ms_per_msg'] and (
            v['messages'] / total_messages >= MIX_MIN_SHARE or v['ms'] / total_ms >= MIX_MIN_SHARE)]
        costs = [v['ms_per_msg'] for v in counted]
        between = max(costs) / min(costs) if len(costs) >= 2 else None
        within = _weighted_median([(v['width'], v['ms']) for v in per_listener.values() if v['width'] is not None])
        width = _width(batches.get(queue, []))
        if width is None:
            reading = 'not enough batches'
        elif within is not None and within > MIX_EVEN_WITHIN:
            reading = 'mixed and uneven' if between is not None and between >= MIX_SPLIT_BETWEEN else 'uneven data'
        elif between is not None and between >= MIX_SPLIT_BETWEEN:
            reading = 'split candidate'
        else:
            reading = 'even'
        out[queue] = {'batches': len(batches.get(queue, [])), 'batch_width': width, 'listeners': len(counted),
                      'between': between, 'within': within, 'reading': reading,
                      'batch_hist': histogram_from_values(batches.get(queue, []))}
    return out


def render_mix(queues, runs):
    tasks = [t for run in runs for t in run.tasks]
    records = [r for run in runs for r in run.listeners]
    mix = queue_mix(tasks, records)
    total = sum(q['ms_process'] for q in queues.values()) or 1
    ranked = sorted((n for n in mix if n in queues), key=lambda n: -queues[n]['ms_process'])[:15]
    if not ranked:
        return ''
    x = lambda v: '-' if v is None else (f"{fmt_int(v)}x" if v >= 100 else f"{v:.1f}x")
    rows = [[f"<code>{esc(n)}</code>", fmt_pct(100 * queues[n]['ms_process'] / total), fmt_int(mix[n]['batches']),
             x(mix[n]['batch_width']), fmt_int(mix[n]['listeners']), x(mix[n]['between']), x(mix[n]['within']),
             f"<b>{esc(mix[n]['reading'])}</b>" if mix[n]['reading'] == 'split candidate' else esc(mix[n]['reading'])]
            for n in ranked]
    head = [('queue', False), ('worker time', True), ('batches', True), ('batch spread', True),
            ('listeners', True), ('between listeners', True), ('within a listener', True), ('reading', False)]
    note = (f"<div class='sub'>Cost per message of each batch (task processing time over messages), and of each "
            f"listener call. Batches and calls under {MIX_MIN_MESSAGES} messages are left out: their cost is mostly "
            f"the fixed cost of the call. Spread is max over min, or p90 over p10 from 10 values on. "
            f"<b>Between listeners</b>: the most over the least expensive listener per message, listeners with "
            f"{fmt_pct(100 * MIX_MIN_SHARE, 0)} or more of the queue's listener messages or time. <b>Within a listener</b>: "
            f"the spread of each listener's calls, as the median over the listeners weighted by their time. <b>Split candidate</b>: between "
            f"{MIX_SPLIT_BETWEEN:.0f}x or more and within {MIX_EVEN_WITHIN:.0f}x or less, so the queue mixes cheap and "
            f"heavy events of even cost; a dedicated queue per heavy event separates them. <b>Uneven data</b>: one "
            f"listener alone spreads more than {MIX_EVEN_WITHIN:.0f}x, which a split does not fix. <b>Mixed and "
            f"uneven</b>: both; a split separates the heavy events, but their batches still vary.</div>")
    hists = small_multiples([(n, mix[n]['batch_hist']) for n in ranked if mix[n]['batches']], 'batches')
    return ("<h3 class='sp'>Cost spread per queue: mixed events or uneven data</h3>" + note
            + rr.table(head, rows) + "<h3 class='sp'>Cost per message per batch, per queue</h3>" + hists)


def render_distributions(queues, listeners, n_runs, runs=()):
    if not queues and not listeners:
        return ''
    body = []
    with_age = sorted(((n, q) for n, q in queues.items() if sum(q['age'].values())), key=lambda kv: -sum(kv[1]['age'].values()))
    if with_age:
        cards = []
        for name, q in with_age[:6]:
            cards.append(rr.hist_card(name, f"end-to-end age, {fmt_int(sum(q['age'].values()))} messages", q['age'], 'messages'))
            if sum(q['attempt_age'].values()):
                cards.append(rr.hist_card(name, f"attempt age, {fmt_int(sum(q['attempt_age'].values()))} messages",
                                          q['attempt_age'], 'messages'))
        has_attempt = any(sum(q['attempt_age'].values()) for q in queues.values())
        body.append(f"<h3>Message age per queue</h3><div class='sub'>Enqueue to consumption, the {min(6, len(with_age))} "
                    f"busiest queues, every batch of {n_runs} run{'s' if n_runs != 1 else ''} merged by summing buckets. "
                    + ("Attempt age (since the current retry attempt was enqueued) is shown beside end-to-end age where "
                       "it was recorded." if has_attempt else f"Attempt age (C4) {NOT_MEASURED} in these runs.")
                    + "</div>")
        body.append(f"<div class='grid2'>{''.join(cards)}</div>")
        body.append("<h3 class='sp'>Every queue at a glance</h3>")
        body.append(small_multiples([(n, q['age']) for n, q in with_age[:30]], 'messages'))
    with_cost = sorted(((k, v) for k, v in listeners.items() if sum(v['hist'].values())), key=lambda kv: -kv[1]['ms'])
    if with_cost:
        cards = [rr.hist_card(lst, f"{ev} · cost per message, {fmt_int(sum(v['hist'].values()))} calls", v['hist'], 'calls')
                 for (lst, ev), v in with_cost[:6]]
        body.append("<h3 class='sp'>Cost per message, per listener and event</h3><div class='sub'>Per-message cost of "
                    "each listener call, the six pairs that take the most time, merged across runs.</div>")
        body.append(f"<div class='grid2'>{''.join(cards)}</div>")
        body.append("<h3 class='sp'>Every listener at a glance</h3>")
        body.append(small_multiples([(f"{lst} / {ev}", v['hist']) for (lst, ev), v in with_cost[:30]], 'calls'))
    if runs:
        body.append(render_mix(queues, runs))
    if any(run.has_flush_file for run in runs):
        joined, elsewhere = flush_records(runs)
        cards = []
        if joined:
            cards.append(rr.hist_card('Storage flush, queue workers', f"time per flush call, {fmt_int(len(joined))} flushes "
                                      "joined to task runs by pid", histogram_from_values(f.get('ms') for f in joined), 'flushes'))
        for context, flushes in sorted(elsewhere.items()):
            cards.append(rr.hist_card(f"Storage flush, {context}", f"time per flush call, {fmt_int(len(flushes))} flushes",
                                      histogram_from_values(f.get('ms') for f in flushes), 'flushes'))
        body.append("<h3 class='sp'>Direct-sync storage flush</h3><div class='sub'>Each flush writes the buffered storage "
                    "and search entries at console terminate (C6). Built from the per-flush durations on the same "
                    "log grid as the stored histograms.</div>")
        body.append(f"<div class='grid2'>{''.join(cards)}</div>" if cards else
                    "<div class='empty'>Flush files present, but no flush was recorded</div>")
        with_flush = sorted(((n, q) for n, q in queues.items() if q['flush_hist']), key=lambda kv: -(kv[1]['flush_ms'] or 0))
        if len(with_flush) > 1:
            body.append("<h3 class='sp'>Flush time per queue at a glance</h3>")
            body.append(small_multiples([(n, q['flush_hist']) for n, q in with_flush[:30]], 'flushes'))
    else:
        body.append(f"<h3 class='sp'>Direct-sync storage flush</h3><div class='empty'>Flush time {NOT_MEASURED}: no "
                    "sync-flush-runs.jsonl (C6) in these runs</div>")
    lede = ("Per-batch percentiles cannot be combined, so every distribution here is the stored histogram buckets summed "
            "over all instrumented runs, with p50, p95 and p99 read off the merge. Display bins adapt to where the data is; "
            "grey edge bars hold the trimmed tails.")
    return section('Distributions', lede, ''.join(body))


def render_queue_service(instr):
    """Queue service cadence, backlog and broker traffic of one representative run: the run with the median
    end-to-end time. These are per-run views (gaps between task starts, depth over time), so merging runs
    would mix timelines."""
    runs = sorted((r for r in instr if r.e2e_s is not None), key=lambda r: r.e2e_s)
    if not runs:
        return ''
    run = runs[len(runs) // 2]
    parts = [rr.render_cadence(run.tasks), rr.render_backlog(run.samples), rr.render_broker(run.samples)]
    body = []
    for part in parts:
        if not part:
            continue
        part = part.replace('<section><h2>', "<h3 class='sp'>", 1).replace('</h2>', '</h3>', 1)
        body.append(part[:-len('</section>')] if part.endswith('</section>') else part)
    if not body:
        return ''
    source = run.meta.get('worker_source') or 'queue:worker:start --stop-when-empty'
    lede = (f"Run <code>{esc(run.run_id)}</code>, the median of {len(runs)} instrumented runs by end-to-end time "
            f"({fmt_ms(run.e2e_s * 1000)}). Workers started by: <b>{esc(source)}</b>. How often each queue is "
            "served, how deep it gets and how much the broker moved.")
    return section('Queue service', lede, ''.join(body))


SCENARIO_ORDER = ['ps_only', 'full_republish', 'import_update', 'first_load']
SCENARIO_TITLES = {'ps_only': 'P&S only', 'full_republish': 'Full republish', 'import_update': 'Import update',
                   'first_load': 'First load'}
# Catalog inputs multiplied by the catalog factor; stores, locales, currencies, concretes per abstract and
# prices per product stay at the demo values (the same set as the calculator's "Catalog x10" button).
CATALOG_INPUTS = ('abstract_products', 'offers', 'categories', 'product_lists', 'cms_items', 'glossary_keys', 'merchants')
CATALOG_FACTORS = (1, 10, 100)


def load_companions(campaign_dir):
    """Campaign directories listed in companions.json beside campaign.json: the other scenarios of the
    same round, shown side by side in the scenario section."""
    path = pathlib.Path(campaign_dir) / 'companions.json'
    if not path.exists():
        return []
    names = json.loads(path.read_text()).get('campaigns', [])
    return [(pathlib.Path(campaign_dir) / n).resolve() for n in names]


def scenario_row(campaign_dir):
    campaign, runs, _ = load_campaign(campaign_dir)
    instr = [r for r in runs if r.ok and r.instrumented]
    model, _, error = model_for(campaign_dir) if instr else (None, None, 'no instrumented run')
    if model is None:
        return None
    cm = capacity_module()
    ref = [r for r in instr if r.meta.get('scale') in (None, 1, 1.0)] or instr
    e2e = spread([r.e2e_s for r in ref])
    preds = {}
    for k in CATALOG_FACTORS:
        inputs = {key: (v * k if key in CATALOG_INPUTS and v is not None else v) for key, v in model['demo_inputs'].items()}
        preds[k] = cm.predict(model, inputs)
    return {'id': campaign.get('id', pathlib.Path(campaign_dir).name), 'scenario': model['scenario'], 'model': model,
            'runs': len(runs), 'failed': len([r for r in runs if not r.ok]),
            'errors': sum(int(r.meta.get('error_messages') or 0) > 0 for r in runs if r.ok),
            'source': model.get('worker_source', '-'), 'e2e': e2e, 'preds': preds,
            'scales': model['validity']['scales']}


def _span(b):
    v, lo, hi = b['value'], b['low'], b['high']
    return f"{fmt_ms(v * 1000)}" + (f" <span class='muted'>({fmt_ms(lo * 1000)} to {fmt_ms(hi * 1000)})</span>"
                                    if hi - lo > 0.05 * max(v, 1e-9) else '')


def render_scenarios(campaign_dir):
    """Every scenario of the round in one table: measured demo time and predictions for a larger catalog."""
    companions = load_companions(campaign_dir)
    if not companions:
        return '', ''
    rows = [r for r in (scenario_row(d) for d in [pathlib.Path(campaign_dir)] + companions) if r]
    if not rows:
        return '', ''
    rows.sort(key=lambda r: SCENARIO_ORDER.index(r['scenario']) if r['scenario'] in SCENARIO_ORDER else 99)
    head = [('scenario', False), ('campaign', False), ('runs', True), ('measured end to end, demo', True)] + \
        [(f"predicted, catalog x{k}", True) for k in CATALOG_FACTORS] + [('messages x100', True), ('key-value keys x100', True)]
    cells = []
    for r in rows:
        e = r['e2e']
        measured = (f"{fmt_ms(e['median'] * 1000)} <span class='muted'>({fmt_ms(e['min'] * 1000)} to {fmt_ms(e['max'] * 1000)})</span>"
                    if e['n'] else '-')
        p100 = r['preds'][100]
        cells.append([esc(SCENARIO_TITLES.get(r['scenario'], r['scenario'])), f"<code>{esc(r['id'])}</code>",
                      f"{r['runs']}" + (f", {r['errors']} with errors" if r['errors'] else ''), measured]
                     + [_span(r['preds'][k]['total_s']) for k in CATALOG_FACTORS]
                     + [fmt_int(p100['messages']), fmt_int(p100['kv_keys']) if p100['kv_keys'] is not None else '-'])
    chart = hbars([(SCENARIO_TITLES.get(r['scenario'], r['scenario']), r['preds'][100]['total_s']['value'] / 60,
                    f"{r['preds'][100]['total_s']['value'] / 60:.0f} min",
                    f"{SCENARIO_TITLES.get(r['scenario'], r['scenario'])}: catalog x100, "
                    f"{r['preds'][100]['total_s']['low'] / 60:.0f} to {r['preds'][100]['total_s']['high'] / 60:.0f} min")
                   for r in rows])
    sources = sorted({r['source'] for r in rows})
    lede = ("One row per scenario of this round. Catalog xN multiplies the products, offers, merchants, categories, "
            "product lists, CMS and glossary rows; stores, locales and currencies stay at the demo values. Every value "
            "above x1 is extrapolated from the measured scales "
            + ', '.join(f"{SCENARIO_TITLES.get(r['scenario'], r['scenario']).lower()} {', '.join(f'{s:g}' for s in r['scales'])}"
                        for r in rows)
            + f". Workers started by: {esc(', '.join(sources))}.")
    body = ("<h3 class='sp'>Predicted end to end for a catalog 100 times the demo</h3>" + chart
            + "<h3 class='sp'>Measured and predicted, every scenario</h3>" + table(head, cells))
    line = '; '.join(f"{SCENARIO_TITLES.get(r['scenario'], r['scenario']).lower()} x100 "
                     f"{r['preds'][100]['total_s']['value'] / 60:.0f} min" for r in rows)
    return section('Scenarios', lede, body), line


def render_variance(instr, queues):
    if not instr:
        return ''
    figures = [('End to end', 's', [r.e2e_s for r in instr])]
    names = []
    for r in instr:
        for name, *_ in r.stages:
            if name not in names:
                names.append(name)
    figures += [(f"Stage: {n}", 's', [r.stage_s(n) for r in instr]) for n in names]
    figures.append(('Messages consumed', 'n', [r.messages for r in instr]))
    figures.append(('Throughput, msg/s end to end', 'r', [(r.messages / r.e2e_s) if r.messages and r.e2e_s else None for r in instr]))
    figures.append(('Rows imported', 'n', [r.rows_imported for r in instr]))

    def import_rate(run):
        importers, _, _, _ = rr.aggregate_imports(run.imports)
        native = sum(b['ms_native'] for b in importers.values())
        return (run.rows_imported / (native / 1000)) if native and run.rows_imported else None

    def unattributed(run):
        importers, _, _, _ = rr.aggregate_imports(run.imports)
        stage = run.stage_s('import')
        if not importers or not stage:
            return None
        return 100 * (stage * 1000 - sum(b['ms_wall'] for b in importers.values())) / (stage * 1000)

    figures.append(('Import rows/s, native', 'r', [import_rate(r) for r in instr]))
    figures.append(('Import unattributed share', 'p', [unattributed(r) for r in instr]))
    figures.append(('Key-value keys', 'n', [r.meta.get('kv_keys') for r in instr]))
    figures.append(('Search documents', 'n', [r.meta.get('es_docs') for r in instr]))
    for name, q in sorted(queues.items(), key=lambda kv: -kv[1]['cost_ms'])[:3]:
        figures.append((f"Queue cost: {name}", 'ms', [q['per_run_ms'].get(r.run_id) for r in instr]))

    def fmt(kind, v):
        if v is None:
            return '-'
        return {'s': lambda x: fmt_ms(x * 1000), 'ms': fmt_ms, 'n': fmt_int, 'r': fmt_num,
                'p': lambda x: fmt_pct(x)}[kind](v)

    strips, cells = [], []
    for label, kind, values in figures:
        st = spread(values)
        if not st['n']:
            continue
        dots = [(100 * (v - st['median']) / st['median'] if st['median'] else 0, C_INSTR,
                 f"{run.run_id}: {fmt(kind, v)}") for run, v in zip(instr, values) if v is not None]
        strips.append((esc(label), dots, f"{esc(fmt(kind, st['median']))} &middot; &plusmn;{fmt_pct(st['spread_pct'] / 2 if st['spread_pct'] is not None else None, 0)}"))
        cells.append([esc(label), esc(fmt(kind, st['median'])), esc(fmt(kind, st['min'])), esc(fmt(kind, st['max'])),
                      fmt_pct(st['spread_pct']), fmt_int(st['n'])]
                     + [esc(fmt(kind, v)) for v in values])
    body = dot_strips(strips, "Each dot is one instrumented run, placed by its deviation from the median of that figure. "
                              "The value on the right is the median and half the min-to-max spread.")
    body += ("<details><summary>As a table</summary>" + table(
        [('figure', False), ('median', True), ('min', True), ('max', True), ('spread', True), ('runs', True)]
        + [(r.run_id, True) for r in instr], cells) + "</details>")
    body += ("<div class='note'><b>Reading this.</b> Spread is (max - min) / median. A difference between two runs "
             "smaller than the spread of that figure is not evidence of anything.</div>")
    return section('Run-to-run variance', f"Every headline figure across the {len(instr)} instrumented runs.", body)


def render_overhead(instr, plain):
    names = []
    for r in instr + plain:
        for name, *_ in r.stages:
            if name not in names:
                names.append(name)
    rows = [('End to end', [r.e2e_s for r in instr], [r.e2e_s for r in plain])]
    rows += [(f"Stage: {n}", [r.stage_s(n) for r in instr], [r.stage_s(n) for r in plain]) for n in names]
    strips, cells = [], []
    headline = None
    for label, vi, vp in rows:
        result = overhead(vi, vp)
        if label == 'End to end':
            headline = result
        reference = statistics.median([v for v in vp if v is not None]) if any(v is not None for v in vp) else None
        if result['enough']:
            verdict = ('not distinguishable from noise' if not result['distinguishable'] else
                       f"instrumented {'slower' if result['delta'] > 0 else 'faster'}")
            delta_text = ('no difference' if result['delta'] == 0 else
                          f"{'+' if result['delta'] > 0 else '-'}{fmt_ms(abs(result['delta']) * 1000)} "
                          f"({'+' if result['delta'] > 0 else ''}{fmt_pct(result['delta_pct'])})")
            cells.append([esc(label), esc(fmt_ms(result['median_instr'] * 1000)), esc(fmt_ms(result['median_plain'] * 1000)),
                          esc(delta_text), esc(fmt_ms(result['noise'] * 1000)), esc(verdict)])
            right = f"{esc(delta_text)}<br><span class='sub'>{esc(verdict)}</span>"
        else:
            cells.append([esc(label), fmt_int(result['n_instr']) + ' run(s)', fmt_int(result['n_plain']) + ' run(s)',
                          NOT_ENOUGH, NOT_ENOUGH, f"{NOT_ENOUGH}: needs 2 or more on each side"])
            right = NOT_ENOUGH
        if reference:
            dots = ([(100 * (v - reference) / reference, C_INSTR, f"instrumented: {fmt_ms(v * 1000)}") for v in vi if v is not None]
                    + [(100 * (v - reference) / reference, C_PLAIN, f"plain: {fmt_ms(v * 1000)}") for v in vp if v is not None])
            strips.append((esc(label), dots, right))
    body = []
    if headline is not None:
        if headline['enough']:
            text = (f"End to end, the instrumented median is {fmt_ms(headline['median_instr'] * 1000)} against "
                    f"{fmt_ms(headline['median_plain'] * 1000)} without instrumentation, a difference of "
                    f"{fmt_pct(headline['delta_pct'])}; the larger run-to-run range of the two sides is "
                    f"{fmt_ms(headline['noise'] * 1000)}. ")
            text += ("The overhead is not distinguishable from noise." if not headline['distinguishable'] else
                     "The difference exceeds that range.")
        else:
            text = (f"Overhead {NOT_ENOUGH}: {headline['n_instr']} instrumented and {headline['n_plain']} plain "
                    "successful run(s); at least 2 on each side are needed to tell overhead from variance.")
        body.append(f"<p class='lede' style='color:var(--ink)'>{esc(text)}</p>")
    body.append(series_legend([('instrumented run', C_INSTR), ('plain run', C_PLAIN)]))
    if strips:
        body.append(dot_strips(strips, "Each dot is one run, placed by its deviation from the plain runs' median."))
    else:
        body.append(f"<div class='empty'>No plain runs: overhead {NOT_ENOUGH}</div>")
    body.append(table([('figure', False), ('instrumented median', True), ('plain median', True), ('delta', True),
                       ('noise (larger range)', True), ('reading', False)], cells))
    body.append("<div class='note'><b>Method.</b> Delta is the difference of the medians. Noise is the larger of the two "
                "sides' min-to-max ranges. Stage times have whole-second resolution.</div>")
    return section('Instrumentation overhead', 'Instrumented runs against runs of the same stages without '
                   '<code>QUEUE_METRICS_ENABLED</code>.', ''.join(body))


def render_import(instr):
    per_run = [(run, rr.aggregate_imports(run.imports)) for run in instr]
    per_run = [(run, agg) for run, agg in per_run if agg[0]]
    if not per_run:
        return ''
    merged = defaultdict(lambda: {'calls': 0, 'imported': 0, 'ms_native': 0.0, 'ms_wall': 0.0, 'rates': [], 'failed': 0})
    emit = {'ms': 0.0, 'events': 0, 'messages': 0, 'unknown': 0, 'calls': 0}
    unattributed = []
    for run, (importers, run_emit, _, _) in per_run:
        for t, b in importers.items():
            m = merged[t]
            m['calls'] += b['calls']
            m['imported'] += b['imported']
            m['ms_native'] += b['ms_native']
            m['ms_wall'] += b['ms_wall']
            m['failed'] += b['failed']
            if b['ms_native']:
                m['rates'].append(b['imported'] / (b['ms_native'] / 1000))
        emit['ms'] += run_emit['ms']
        emit['events'] += run_emit['events']
        emit['messages'] += run_emit['messages']
        emit['unknown'] += run_emit['messages_unknown']
        emit['calls'] += run_emit['calls']
        stage = run.stage_s('import')
        if stage:
            unattributed.append(100 * (stage * 1000 - sum(b['ms_wall'] for b in importers.values())) / (stage * 1000))
    ua = spread(unattributed)
    total_wall = sum(m['ms_wall'] for m in merged.values()) or 1
    rows_total = sum(m['imported'] for m in merged.values())
    native_total = sum(m['ms_native'] for m in merged.values())
    if any(run.has_flush_file for run, _ in per_run):
        import_flushes = [f for run, _ in per_run for f in join_flushes(run.tasks, run.flushes)[1].get('import', [])]
        flush_tile = tile('Direct-sync flush', esc(fmt_ms(sum(f.get('ms') or 0.0 for f in import_flushes) / len(per_run))),
                          f"per run, mean; {fmt_int(len(import_flushes))} flushes in import processes, part of the "
                          "unattributed remainder")
    else:
        flush_tile = tile('Direct-sync flush', NOT_MEASURED, 'no sync-flush-runs.jsonl (C6)')
    tiles = ("<div class='tiles'>"
             + tile('Import types', fmt_int(len(merged)), f"over {len(per_run)} run{'s' if len(per_run) != 1 else ''}")
             + tile('Rows per second', fmt_num(rows_total / (native_total / 1000)) if native_total else '-', 'merged, over native time')
             + tile('Unattributed share', fmt_pct(ua['median'], 0) if ua['n'] else '-',
                    f"median; {fmt_pct(ua['min'], 0)} to {fmt_pct(ua['max'], 0)}" if ua['n'] else 'no import stage timing')
             + tile('Publish emission', esc(fmt_ms(emit['ms'] / len(per_run))), 'per run, mean of the summed publish_emit time')
             + tile('Emitted messages', NOT_MEASURED if emit['unknown'] == emit['calls'] else fmt_int(emit['messages'] // len(per_run)),
                    'per run, mean')
             + flush_tile + "</div>")
    ranked = sorted(merged.items(), key=lambda kv: -kv[1]['ms_wall'])
    chart = hbars([(t, m['ms_wall'], f"{fmt_num(m['imported'] / (m['ms_native'] / 1000)) if m['ms_native'] else '-'} rows/s",
                    f"{t}: {fmt_pct(100 * m['ms_wall'] / total_wall)} of importer wall time, {fmt_int(m['imported'])} rows")
                   for t, m in ranked[:15]])
    cells = [[esc(t), fmt_int(m['imported']), esc(fmt_ms(m['ms_native'])), esc(fmt_ms(m['ms_wall'])),
              fmt_num(m['imported'] / (m['ms_native'] / 1000)) if m['ms_native'] else '-',
              (f"{fmt_num(min(m['rates']))} to {fmt_num(max(m['rates']))}" if m['rates'] else '-'),
              fmt_pct(100 * m['ms_wall'] / total_wall), f"<b>{m['failed']}</b>" if m['failed'] else '0']
             for t, m in ranked]
    body = (tiles + f"<h3 class='sp'>Time by import type</h3><div class='sub'>Top {min(15, len(ranked))} of {len(ranked)} "
            "by importer wall time summed over runs; the value is the merged rows per second.</div>" + chart
            + table([('import type', False), ('rows', True), ('native', True), ('wall', True), ('rows/s', True),
                     ('rows/s per run, min to max', True), ('share of wall', True), ('failed', True)], cells))
    return section('Import', 'Per import type, merged across the instrumented runs: totals are summed, rates are total '
                   'rows over total native time, never a mean of per-run rates.', body)


def render_weighted(instr):
    """Weighted time (C7) over every batch of every instrumented run, merged per queue and listener."""
    tasks = [t for run in instr for t in run.tasks]
    listeners = [row for run in instr for row in run.listeners]
    return rr.render_weighted_time(tasks, listeners) if tasks else ''


def render_memory(instr):
    """Memory (C7) over every instrumented run. Flushes are joined to queues per run, since pids repeat."""
    tasks = [t for run in instr for t in run.tasks]
    listeners = [row for run in instr for row in run.listeners]
    groups = defaultdict(list)
    for run in instr:
        by_queue, elsewhere = join_flushes(run.tasks, run.flushes)
        for flushes in by_queue.values():
            groups['queue workers'] += flushes
        for context, flushes in elsewhere.items():
            groups[context] += flushes
    return rr.render_memory(tasks, listeners, dict(groups), any(run.has_flush_file for run in instr),
                            [run.imports for run in instr])


def render_quality(campaign, runs):
    expected_instr = [('task-runs', lambda r: bool(r.tasks)), ('listener-runs', lambda r: bool(r.listeners)),
                      ('import-runs', lambda r: bool(r.imports)), ('queue-samples', lambda r: bool(r.samples))]
    common = [('storage-samples', lambda r: bool(r.storage)), ('meta.json', lambda r: bool(r.meta)),
              ('stages', lambda r: len(r.stages) >= 4)]
    cells = []
    issues = []
    for run in runs:
        missing = [name for name, check in common if not check(run)]
        if run.instrumented:
            missing += [name for name, check in expected_instr if not check(run)]
        else:
            leaked = [name for name, check in expected_instr[:3] if check(run)]
            if leaked:
                issues.append(f"{run.run_id} is marked plain but holds {', '.join(leaked)}")
        c3 = any(isinstance(t.get('breakdown'), dict) for t in run.tasks + run.listeners)
        c4 = any(t.get('retried') is not None or t.get('attempt_age_hist') is not None for t in run.tasks)
        c6 = f"{fmt_int(len(run.flushes))} flushes" if run.has_flush_file else NOT_MEASURED
        kv, es = run.meta.get('kv_keys'), run.meta.get('es_docs')
        control = None if kv is None else bool(kv and es)
        if run.ok and missing:
            issues.append(f"{run.run_id} is missing {', '.join(missing)}")
        cells.append([f"{kind_dot(run)}{esc(run.run_id)}", 'instrumented' if run.instrumented else 'plain',
                      badge(run.ok, 'ok' if run.ok else f"failed: {run.reason}"),
                      esc(fmt_ms(run.e2e_s * 1000)) if run.e2e_s else '-', esc(run.meta.get('sync_mode', '-')),
                      badge(control, ('passed' if control else 'failed') if control is not None else 'not recorded')
                      + f"<div class='sub'>{fmt_int(kv)} keys, {fmt_int(es)} docs</div>",
                      'run failed' if not run.ok else (esc(', '.join(missing)) if missing else 'none'),
                      ('yes' if c3 else NOT_MEASURED) if run.instrumented else '-',
                      ('yes' if c4 else NOT_MEASURED) if run.instrumented else '-',
                      c6 if run.instrumented else '-'])
    body = table([('run', False), ('kind', False), ('status', False), ('end to end', True), ('sync mode', False),
                  ('positive control', False), ('missing record kinds', False), ('breakdown (C3)', False),
                  ('retries (C4)', False), ('sync flush (C6)', False)], cells)
    failed = [r for r in runs if not r.ok]
    facts = [f"{len(runs)} run(s) in campaign.json, {len(failed)} failed and excluded from every figure above."]
    facts += issues
    body += "<div class='note'>" + '<br>'.join(esc(f) for f in facts) + "</div>"
    if campaign.get('notes'):
        body += f"<div class='note'><b>Campaign notes.</b> {esc(campaign['notes'])}</div>"
    return section('Data quality', 'What each run produced, so every figure above can be traced to the runs behind it. '
                   'A plain run is expected to have only stages, storage samples and meta.', body)


# ---------------------------------------------------------------------------------------------
# Foldable sections. The summary (verdict, top bottlenecks, story) stays open; every other section
# is a <details> with a one-line fact in its <summary>, an anchor and an entry in the contents list.
# ---------------------------------------------------------------------------------------------

FOLD_CSS = """
.toc{display:flex;flex-wrap:wrap;gap:6px 8px;margin:4px 0 0;padding:0;list-style:none;font-size:12.5px}
.toc a{display:inline-block;border:1px solid var(--border);background:var(--card);border-radius:999px;padding:2px 10px;
color:var(--ink);text-decoration:none}
.toc a:hover{background:var(--soft)}
section.fold{padding:0}
section.fold>details{margin:0}
section.fold>details>summary{list-style:none;display:grid;grid-template-columns:auto minmax(0,1fr);gap:2px 12px;
align-items:baseline;padding:14px 20px;font-size:14px;color:var(--ink)}
section.fold>details>summary::-webkit-details-marker{display:none}
section.fold>details>summary::before{content:'';grid-row:1 / span 2;width:8px;height:8px;margin-top:2px;
border-right:2px solid var(--ink2);border-bottom:2px solid var(--ink2);transform:rotate(-45deg);transition:transform .15s}
section.fold>details[open]>summary::before{transform:rotate(45deg)}
section.fold>details>summary h2{margin:0}
section.fold>details>summary .one{color:var(--ink2);font-size:13px;overflow-wrap:anywhere}
section.fold>details>summary:hover{background:var(--surface);border-radius:12px}
section.fold>details>.inner{padding:0 20px 20px}
@media (max-width:720px){section.fold>details>summary{padding:12px 14px}section.fold>details>.inner{padding:0 14px 14px}}
.calc{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr);gap:16px;margin-top:12px}
.calc .fields{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,145px),1fr));gap:8px 12px;align-content:start}
.calc label{display:block;font-size:12px;color:var(--ink2)}
.calc input{width:100%;font:inherit;font-size:14px;padding:5px 8px;border:1px solid var(--axis);border-radius:6px;background:#fff;
color:var(--ink);font-variant-numeric:tabular-nums}
.calc input:focus{outline:2px solid var(--s1);outline-offset:0}
.calc .demo{font-size:11px;color:var(--muted)}
.calc .out .tiles{grid-template-columns:repeat(auto-fill,minmax(min(100%,170px),1fr))}
.calc .range{font-size:11.5px;color:var(--ink2);font-variant-numeric:tabular-nums}
.calc button{font:inherit;font-size:12.5px;border:1px solid var(--border);background:var(--card);border-radius:6px;padding:4px 10px;
cursor:pointer;color:var(--ink)}
.calc button:hover{background:var(--soft)}
.badge.warn{background:#fff1d6;color:#7a4b00}
.flowbox{overflow-x:auto;margin-top:8px}
.flow{display:block;width:100%;min-width:560px;height:auto}
.flow text{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
.flow [data-tip]:hover{filter:brightness(1.08)}
.chain{display:flex;flex-wrap:wrap;gap:6px;align-items:center;font-size:13px;margin:10px 0}
.chain .st{border:1px solid var(--border);background:var(--surface);border-radius:8px;padding:6px 10px}
.chain .st b{display:block;font-size:15px}
.chain .ar{color:var(--muted)}
td.rk{font-weight:650}
@media (max-width:720px){.calc{grid-template-columns:1fr}}
"""

FOLD_JS = """
(function(){function openTarget(){var id=location.hash.slice(1);if(!id)return;var s=document.getElementById(id);
if(!s)return;var d=s.querySelector(':scope>details');if(d&&!d.open)d.open=true;}
window.addEventListener('hashchange',openTarget);openTarget();})();
"""


def fold(section_html, anchor, line, open_=False):
    """Turn a section() block into a foldable one. section_html must start with <section><h2>."""
    if not section_html:
        return ''
    head = '<section><h2>'
    if not section_html.startswith(head):
        return f"<div id='{anchor}'>{section_html}</div>"
    end = section_html.index('</h2>')
    title = section_html[len(head):end]
    inner = section_html[end + len('</h2>'):-len('</section>')]
    return (f"<section id='{anchor}' class='fold'><details{' open' if open_ else ''}><summary><h2>{title}</h2>"
            f"<span class='one'>{line}</span></summary><div class='inner'>{inner}</div></details></section>")


def toc(entries):
    links = ''.join(f"<li><a href='#{a}'>{esc(t)}</a></li>" for a, t in entries)
    return f"<nav aria-label='Contents'><ul class='toc'>{links}</ul></nav>"


# ---------------------------------------------------------------------------------------------
# Capacity model (C9): chain diagram, coefficients, calculator.
# ---------------------------------------------------------------------------------------------

# Display groups for the drivers: at most 7, so the categorical slots 1 to 7 hold in fixed order.
DRIVER_GROUPS = [
    ('Products', ('abstract', 'concrete', 'products'), '#2a78d6'),
    ('Prices', ('prices',), '#eb6834'),
    ('Offers and merchants', ('offers', 'merchants'), '#1baf7a'),
    ('Translations', ('glossary',), '#eda100'),
    ('Categories and CMS', ('categories', 'cms'), '#e87ba4'),
    ('Product lists', ('product_lists',), '#008300'),
    ('Setup and other data', ('stores', 'other'), '#6250d6'),
]


def group_of(driver):
    for name, drivers, _ in DRIVER_GROUPS:
        if driver in drivers:
            return name
    return DRIVER_GROUPS[-1][0]


def chain_columns(model):
    """Demo totals per display group at each link of the chain: rows, events, messages, worker time."""
    cols = [defaultdict(float) for _ in range(4)]
    for e in model['import_types'].values():
        g = group_of(e['driver'])
        cols[0][g] += e['rows_demo'] or 0
        cols[1][g] += (e['rows_demo'] or 0) * (e['events_per_row']['value'] or 0) if e['rows_demo'] else \
            e['events_per_row'].get('events_fixed', 0)
    for e in model['queues'].values():
        m = e['messages_demo']['value'] or 0
        for d, share in e['composition'].items():
            g = group_of(d)
            cols[2][g] += m * share
            cols[3][g] += m * share * (e['ms_per_message']['value'] or 0) / 1000
    return cols


def flow_svg(model):
    """Sankey-like share flow: four stacked columns (rows, events, messages, worker seconds), each scaled to
    its own total, with bands joining the same driver group. Shows how each group's share grows or shrinks
    along the chain; the absolute totals are in the column heads."""
    cols = chain_columns(model)
    heads = [('Rows imported', fmt_int, 'rows'), ('Events from import', fmt_int, 'events'),
             ('Queue messages', fmt_int, 'messages'), ('Worker time', lambda v: fmt_ms(v * 1000), 'worker time')]
    groups = [(n, c) for n, _, c in DRIVER_GROUPS if any(col.get(n) for col in cols)]
    width, height = 760, 330
    top, bottom, bar = 52, 14, 22
    ph = height - top - bottom
    xs = [30 + i * (width - 60 - bar) / 3 for i in range(4)]
    gap = 2
    out = [f"<svg class='flow' viewBox='0 0 {width} {height}' role='img' aria-label='Share of each driver group along "
           "the chain: rows, events, messages, worker time'>"]
    spans = []
    for i, col in enumerate(cols):
        total = sum(col.values()) or 1
        present = [(n, c) for n, c in groups if col.get(n)]
        usable = ph - gap * max(len(present) - 1, 0)
        y = top
        pos = {}
        for n, c in groups:
            v = col.get(n, 0)
            if not v:
                pos[n] = None
                continue
            h = max(usable * v / total, 1.0)
            pos[n] = (y, h, v, total)
            y += h + gap
        spans.append(pos)
        label, fmt, unit = heads[i]
        anchor = 'start' if i == 0 else ('end' if i == 3 else 'middle')
        tx = xs[i] if i == 0 else (xs[i] + bar if i == 3 else xs[i] + bar / 2)
        out.append(f"<text x='{tx:.1f}' y='18' text-anchor='{anchor}' font-size='13' font-weight='600' fill='{rr.INK}'>"
                   f"{esc(label)}</text><text x='{tx:.1f}' y='35' text-anchor='{anchor}' font-size='12.5' "
                   f"fill='{rr.INK_2}'>{esc(fmt(sum(col.values())))}</text>")
    for i in range(3):
        for n, c in groups:
            a, b = spans[i].get(n), spans[i + 1].get(n)
            if not a or not b:
                continue
            x0, x1 = xs[i] + bar, xs[i + 1]
            mid = (x0 + x1) / 2
            out.append(f"<path d='M{x0:.1f},{a[0]:.1f} C{mid:.1f},{a[0]:.1f} {mid:.1f},{b[0]:.1f} {x1:.1f},{b[0]:.1f} "
                       f"L{x1:.1f},{b[0] + b[1]:.1f} C{mid:.1f},{b[0] + b[1]:.1f} {mid:.1f},{a[0] + a[1]:.1f} "
                       f"{x0:.1f},{a[0] + a[1]:.1f} Z' fill='{c}' fill-opacity='.22'/>")
    for i, pos in enumerate(spans):
        label, fmt, unit = heads[i]
        for n, c in groups:
            p = pos.get(n)
            if not p:
                continue
            y, h, v, total = p
            share = 100 * v / total
            out.append(f"<rect x='{xs[i]:.1f}' y='{y:.1f}' width='{bar}' height='{h:.1f}' rx='3' fill='{c}' "
                       f"data-tip='{esc(n)}\n{esc(label)}: {esc(fmt(v))} ({share:.1f}% of {esc(fmt(total))})'/>")
            if h >= 13 and share >= 4:
                if i == 3:
                    out.append(f"<text x='{xs[i] - 5:.1f}' y='{y + h / 2 + 4:.1f}' text-anchor='end' font-size='11.5' "
                               f"fill='{rr.INK_2}'>{share:.0f}%</text>")
                else:
                    out.append(f"<text x='{xs[i] + bar + 5:.1f}' y='{y + h / 2 + 4:.1f}' font-size='11.5' "
                               f"fill='{rr.INK_2}'>{share:.0f}%</text>")
    out.append("</svg>")
    legend = series_legend([(n, c) for n, c in groups])
    rows = [[esc(n)] + [f"{esc(heads[i][1](cols[i].get(n, 0)))} ({fmt_pct(100 * cols[i].get(n, 0) / (sum(cols[i].values()) or 1), 0)})"
                        for i in range(4)] for n, _ in groups]
    twin = ("<details><summary>The flow as a table</summary>" + table(
        [('driver group', False)] + [(h[0], True) for h in heads], rows) + "</details>")
    return legend + "<div class='flowbox'>" + ''.join(out) + '</div>' + twin


PREDICT_JS = r"""
function capacityQuantities(v){
  function n(k){var x=parseFloat(v[k]);return isFinite(x)?x:0;}
  var a=n('abstract_products'),c=a*n('concretes_per_abstract'),p=a+c;
  return {abstract:a,concrete:c,products:p,prices:p*n('prices_per_product'),offers:n('offers'),merchants:n('merchants'),
    categories:n('categories'),product_lists:n('product_lists'),cms:n('cms_items'),glossary:n('glossary_keys'),
    stores:n('stores'),other:n('other_scale'),_sl:n('stores')*n('locales_per_store'),_sc:n('stores')*n('currencies_per_store'),
    _s:n('stores')};
}
function capacityPredict(M,inputs,workers){
  var DR=['abstract','concrete','products','prices','offers','merchants','categories','product_lists','cms','glossary','stores','other'];
  var IN={abstract:'abstract_products',concrete:'concretes_per_abstract',products:'abstract_products',prices:'prices_per_product',
    offers:'offers',merchants:'merchants',categories:'categories',product_lists:'product_lists',cms:'cms_items',
    glossary:'glossary_keys',stores:'stores',other:'other_scale'};
  var q=capacityQuantities(inputs),d=capacityQuantities(M.demo_inputs),sc={};
  DR.concat(['_sl','_sc','_s']).forEach(function(k){sc[k]=d[k]?q[k]/d[k]:1;});
  function fan(dr){var k=M.fanout[dr];return k==='sl'?sc._sl:k==='sc'?sc._sc:k==='s'?sc._s:1;}
  var cap=M.pipeline.worker_cap.value||1;workers=workers||cap;
  var W=['value','low','high'];
  function msImport(e,rows,w){var c=e.ms;if(c.model==='fit')return rows?c.fixed+c.per_row*rows:0;
    if(c.model==='fixed')return c[w]||0;return rows*(c[w]||0);}
  var rows=0,imp={value:0,low:0,high:0},wallDemo=0,events=0;
  Object.keys(M.import_types).forEach(function(t){var e=M.import_types[t];
    var k=sc[e.driver]*(e.per_store?sc._s:1),r=e.rows_demo*k;rows+=r;
    W.forEach(function(w){imp[w]+=e.rows_demo?msImport(e,r,w):(e.ms[w]||0)*k;});
    wallDemo+=e.rows_demo?msImport(e,e.rows_demo,'value'):(e.ms.value||0);
    events+=e.rows_demo?r*e.events_per_row.value:(e.events_per_row.events_fixed||0)*k;});
  var ratio=wallDemo?imp.value/wallDemo:1,rem=M['import'].remainder_ms;
  W.forEach(function(w){imp[w]+=(rem[w]||0)*ratio;});
  var messages={},flushK={},work={value:0,low:0,high:0},pubDemo=0,pubPred=0,syncDemo=0,syncPred=0,memC=0,memH=0;
  Object.keys(M.queues).forEach(function(qn){var e=M.queues[qn],mDemo=e.messages_demo.value||0,msgK=0,workK=0;
    Object.keys(e.composition).forEach(function(dr){var s=e.composition[dr];
      msgK+=s*sc[dr]*(e.kind==='sync'?fan(dr):1);workK+=s*sc[dr]*fan(dr);});
    var m=mDemo*msgK;messages[qn]=m;flushK[qn]=workK;var per=e.ms_per_message,extra=msgK?workK/msgK:1;
    if(e.use_scale_fit&&m)work.value+=(e.scale_fit.fixed+e.scale_fit.marginal*m)*extra;else work.value+=m*per.value*extra;
    work.high+=m*per.high*extra;var low=m*per.low*extra;var f=e.fit||{};
    if(f.usable&&e.chunk&&m){var full=Math.ceil(m/e.chunk)*f.fixed+m*f.marginal*extra;low=Math.min(low,full);}
    work.low+=low;
    if(e.kind==='sync'){syncDemo+=mDemo;syncPred+=m;}else{pubDemo+=mDemo;pubPred+=m;}
    var pk=e.mem_peak_mb;if(pk){memC=Math.max(memC,pk.p95);memH=Math.max(memH,pk.max);var mf=e.mem_fit;
      if(mf&&e.chunk&&mf.marginal>0)memH=Math.max(memH,mf.start_mb+mf.fixed+mf.marginal*e.chunk);}});
  var P=M.pipeline,par=P.parallelism,over=P.drain_overhead_s;
  var pC=Math.min(workers,(par.value||1)*workers/cap),pF=workers,pS=Math.min(workers,par.low||par.value||1);
  var pM=Math.min(workers,(par.high||par.value||1)*workers/cap);
  var df=P.drain_fit,central=(df&&workers===cap)?df.fixed_s+df.per_work_s*work.value/1000:(over.value||0)+work.value/1000/pC;
  central=Math.max(central,(over.value||0)+work.value/1000/pM);
  var drain={value:central,low:(over.low||0)+work.low/1000/pF,high:(over.high||0)+work.high/1000/pS};
  var st=P.stages_s_demo,pubK=pubDemo?pubPred/pubDemo:1,syncK=syncDemo?syncPred/syncDemo:1;
  var o={publish_s:{},sync_s:{},ps_s:{},import_s:{},total_s:{},work_s:{},drain_s:drain};
  [work,drain].forEach(function(b){b.low=Math.min(b.low,b.value);b.high=Math.max(b.high,b.value);});
  W.forEach(function(w){o.publish_s[w]=((st.publish||{})[w]||0)*pubK;o.sync_s[w]=((st.sync||{})[w]||0)*syncK;
    o.ps_s[w]=o.publish_s[w]+o.sync_s[w]+drain[w];o.import_s[w]=imp[w]/1000;o.total_s[w]=o.import_s[w]+o.ps_s[w];
    o.work_s[w]=work[w]/1000;});
  var S=M.storage,kvDemo=S.kv_keys_demo.value,docsDemo=S.search_docs_demo.value,stD=0,stP=0,seD=0,seP=0;
  Object.keys(M.queues).forEach(function(qn){var v=M.queues[qn].messages_demo.value||0;
    if(qn.indexOf('sync.storage.')===0){stD+=v;stP+=messages[qn];}if(qn.indexOf('sync.search.')===0){seD+=v;seP+=messages[qn];}});
  var flD=0,flP=0;Object.keys(M.queues).forEach(function(qn){var f=M.queues[qn].flush_messages_demo||0;flD+=f;flP+=f*flushK[qn];});
  var kvK=stD+flD?(stP+flP)/(stD+flD):syncK,docsK=seD+flD?(seP+flP)/(seD+flD):syncK;
  var rowsDemo=M['import'].rows_demo.value||0,kRows=rowsDemo?rows/rowsDemo:1;
  var ds=DR.filter(function(dr){return dr!=='other'&&M.demo_inputs[IN[dr]]!==null&&M.demo_inputs[IN[dr]]!==undefined;})
    .map(function(dr){return sc[dr];});
  var kLo=ds.length?Math.min.apply(null,ds.concat([kRows])):kRows,kHi=ds.length?Math.max.apply(null,ds.concat([kRows])):kRows;
  var V=M.validity,tol=V.tolerance,reasons=[];
  if(kHi>1+tol)reasons.push('larger than the largest measured data set');
  if(kLo<V.scale_min/V.scale_max*(1-tol))reasons.push('smaller than the smallest measured data set');
  if(Math.abs(sc._sl-1)>tol||Math.abs(sc._sc-1)>tol)reasons.push('store, locale or currency count differs from the measured set (fan-out assumed)');
  if(workers!==cap)reasons.push('worker count differs from the measured worker cap');
  var msgTotal=0;Object.keys(messages).forEach(function(k){msgTotal+=messages[k];});
  o.scale_rows=kRows;o.scale_range=[kLo,kHi];o.extrapolated=reasons.length>0;o.reasons=reasons;o.rows=rows;o.events=events;
  o.messages=msgTotal;o.messages_by_queue=messages;o.workers=workers;
  o.worker_peak_mb={value:memC,low:memC,high:Math.max(memH,memC)};
  o.concurrent_peak_mb={value:memC*Math.min(workers,Math.ceil(pC)),low:memC,high:Math.max(memH,memC)*workers};
  o.importer_peak_mb=M.memory.importer_peak_mb_demo;
  o.kv_keys=kvDemo===null||kvDemo===undefined?null:kvDemo*kvK;
  o.search_docs=docsDemo===null||docsDemo===undefined?null:docsDemo*docsK;
  o.kv_bytes=S.kv_bytes_demo?S.kv_bytes_demo*kvK:null;o.search_bytes=S.search_bytes_demo?S.search_bytes_demo*docsK:null;
  return o;
}
"""

CALC_JS = r"""
(function(){
var el=document.getElementById('capacity-model'),root=document.getElementById('calc');if(!el||!root)return;
var M=JSON.parse(el.textContent);
function sig(x){var a=Math.abs(x);return a>=100?Math.round(x).toLocaleString('en-US'):a>=10?x.toFixed(1).replace(/\.0$/,''):x.toFixed(2).replace(/\.?0+$/,'');}
function dur(s){if(s===null||s===undefined||!isFinite(s))return '-';var ms=s*1000;if(ms<1000)return sig(ms)+' ms';
  if(ms<60000)return sig(ms/1000)+' s';if(ms<3600000){var m=Math.floor(Math.round(ms/1000)/60),r=Math.round(ms/1000)%60;return m+' min'+(r?' '+r+' s':'');}
  var h=Math.floor(Math.round(ms/60000)/60),mm=Math.round(ms/60000)%60;return h+' h'+(mm?' '+mm+' min':'');}
function num(x){return x===null||x===undefined?'-':Math.round(x).toLocaleString('en-US');}
function mb(x){return x===null||x===undefined?'-':x>=1024?sig(x/1024)+' GB':sig(x)+' MB';}
function bytes(x){return x===null||x===undefined?'-':x>=1073741824?sig(x/1073741824)+' GB':sig(x/1048576)+' MB';}
function read(){var v={};M.inputs.forEach(function(i){var f=document.getElementById('in-'+i.key);v[i.key]=f?f.value:i.demo;});return v;}
function set(id,html){var e=document.getElementById(id);if(e)e.innerHTML=html;}
function rng(b,f){return f(b.low)+' to '+f(b.high);}
function run(){var w=parseInt(document.getElementById('in-workers').value,10);if(!(w>0))w=M.pipeline.worker_cap.value||1;
  var p=capacityPredict(M,read(),w);
  set('o-import',dur(p.import_s.value));set('r-import',rng(p.import_s,dur));
  set('o-ps',dur(p.ps_s.value));set('r-ps',rng(p.ps_s,dur));
  set('o-total',dur(p.total_s.value));set('r-total',rng(p.total_s,dur));
  set('o-msg',num(p.messages));set('r-msg',num(p.events)+' events from import, '+num(p.rows)+' rows');
  set('o-mem',mb(p.worker_peak_mb.value));set('r-mem','up to '+mb(p.worker_peak_mb.high)+' per worker, '+mb(p.concurrent_peak_mb.high)+' for '+p.workers+' workers');
  set('o-kv',num(p.kv_keys));set('r-kv',num(p.search_docs)+' search documents'+(p.kv_bytes?', '+bytes(p.kv_bytes):''));
  set('o-scale',sig(p.scale_rows)+'x');set('r-scale','drivers '+sig(p.scale_range[0])+'x to '+sig(p.scale_range[1])+'x the measured set');
  var b=document.getElementById('o-badge');b.className='badge '+(p.extrapolated?'warn':'ok');
  b.textContent=p.extrapolated?'extrapolated':'within the measured range';
  set('o-why',p.extrapolated?'Outside the measurements: '+p.reasons.join('; ')+'.':'Inputs are inside the measured range.');}
root.addEventListener('input',run);
document.getElementById('calc-reset').addEventListener('click',function(){M.inputs.forEach(function(i){var f=document.getElementById('in-'+i.key);
  if(f)f.value=i.demo===null?0:+i.demo.toPrecision(6);});document.getElementById('in-workers').value=M.pipeline.worker_cap.value||1;run();});
document.getElementById('calc-x10').addEventListener('click',function(){['abstract_products','offers','categories','product_lists','cms_items','glossary_keys','merchants']
  .forEach(function(k){var f=document.getElementById('in-'+k);if(f)f.value=+(parseFloat(f.value)*10).toPrecision(6);});run();});
run();})();
"""


def _json_for_script(data):
    return json.dumps(data, separators=(',', ':')).replace('</', '<\\/').replace('<!--', '<\\!--')


def model_for(campaign_dir):
    """The newest saved model beside the campaign, else one built in memory (not saved). Returns
    (model, file name or None, error text or None)."""
    cm = capacity_module()
    found = cm.latest_model(campaign_dir)
    if found:
        return found[1], found[0].name, None
    try:
        return cm.build_model([campaign_dir]), None, None
    except (ValueError, KeyError, ZeroDivisionError) as error:
        return None, None, str(error)


_CM = None


def capacity_module():
    global _CM
    if _CM is None:
        spec = importlib.util.spec_from_file_location('capacity_model', HERE / 'capacity-model.py')
        _CM = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(_CM)
    return _CM


def _q(c):
    return c.get('quality', '-') if isinstance(c, dict) else '-'


def render_capacity(model, model_file, error):
    """Returns (section html, one-line summary)."""
    if model is None:
        body = f"<div class='empty'>Capacity model not available: {esc(error or 'no data')}</div>"
        return section('Capacity model', '', body), 'not available for this campaign'
    cm = capacity_module()
    demo = cm.predict(model, model['demo_inputs'])
    pipe = model['pipeline']
    source = (f"model file <code>{esc(model_file)}</code>" if model_file else
              "model built in memory at render time and not saved; run <code>capacity-model.py</code> to save a versioned one")
    body = [f"<p class='lede'>From catalog size to time. Each link of the chain is measured in these runs where the data "
            f"allows it; the rest is a stated assumption. Scenario: <b>{esc(model.get('scenario', '-'))}</b>, "
            f"{esc(model.get('scenario_text', ''))} Source: {source}. Scaling: "
            f"{esc(model['validity']['scaling'])}.</p>"]
    imp_rows = model['import']['rows_demo']['value']
    body.append("<div class='chain'>"
                + f"<div class='st'>Catalog inputs<b>{len(model['inputs'])} figures</b></div><span class='ar'>&rarr;</span>"
                + f"<div class='st'>Rows<b>{fmt_int(imp_rows)}</b></div><span class='ar'>&rarr;</span>"
                + f"<div class='st'>Import<b>{esc(fmt_ms(demo['import_s']['value'] * 1000))}</b></div><span class='ar'>&rarr;</span>"
                + f"<div class='st'>Events<b>{fmt_int(demo['events'])}</b></div><span class='ar'>&rarr;</span>"
                + f"<div class='st'>Messages<b>{fmt_int(demo['messages'])}</b></div><span class='ar'>&rarr;</span>"
                + f"<div class='st'>Worker time<b>{esc(fmt_ms(demo['work_s']['value'] * 1000))}</b></div><span class='ar'>&rarr;</span>"
                + f"<div class='st'>P&amp;S wall<b>{esc(fmt_ms(demo['ps_s']['value'] * 1000))}</b></div></div>")
    body.append("<h3 class='sp'>Where the volume comes from, link by link</h3><div class='sub'>Each column is the demo "
                "data set split by driver group and scaled to its own total, so a band that widens from left to right "
                "is a group whose cost grows faster than its row count. Import types, queues and event names are "
                "mapped to drivers by name rules (see the coefficients).</div>")
    body.append(flow_svg(model))

    # Calculator.
    fields = []
    for i in model['inputs']:
        demo_v = i['demo']
        value = 0 if demo_v is None else float(f"{demo_v:.6g}")
        fields.append(f"<div><label for='in-{esc(i['key'])}'>{esc(i['label'])}</label>"
                      f"<input id='in-{esc(i['key'])}' type='number' min='0' step='any' value='{value:g}' inputmode='decimal'>"
                      f"<div class='demo' title='{esc(i['why'])}'>demo {esc(fmt_num(demo_v) if demo_v is not None else 'not measured')}"
                      f" &middot; {esc(i['demo_source'])}</div></div>")
    cap = pipe['worker_cap']['value'] or 1
    fields.append(f"<div><label for='in-workers'>Queue worker processes</label><input id='in-workers' type='number' min='1' "
                  f"step='1' value='{cap}' inputmode='numeric'><div class='demo'>measured cap {cap} &middot; "
                  f"{esc(pipe['worker_cap']['source'] or '-')}</div></div>")

    def out_tile(label, oid, rid):
        return (f"<div class='tile'><div class='l'>{esc(label)}</div><div class='v' id='{oid}'>-</div>"
                f"<div class='range' id='{rid}'></div></div>")

    outputs = ("<div class='tiles'>" + out_tile('Import', 'o-import', 'r-import') + out_tile('P&S (publish, sync, drain)', 'o-ps', 'r-ps')
               + out_tile('Total, import to drain end', 'o-total', 'r-total') + out_tile('Queue messages', 'o-msg', 'r-msg')
               + out_tile('Worker peak memory, p95', 'o-mem', 'r-mem') + out_tile('Key-value keys', 'o-kv', 'r-kv')
               + out_tile('Size against measured', 'o-scale', 'r-scale') + "</div>")
    body.append("<h3 class='sp'>Calculator</h3><div class='sub'>Enter the customer's catalog figures. Every result shows "
                "the central value and a low to high range from the run-to-run spread and the parallelism bounds. The "
                "coefficients are the model JSON embedded in this page.</div>"
                f"<div class='calc' id='calc'><div class='fields'>{''.join(fields)}"
                "<div style='display:flex;gap:8px;align-items:end;grid-column:1 / -1'><button type='button' id='calc-reset'>Reset to demo</button>"
                "<button type='button' id='calc-x10'>Catalog x10</button></div></div>"
                f"<div class='out'><div style='margin-bottom:8px'><span class='badge ok' id='o-badge'>-</span> "
                f"<span class='sub' id='o-why'></span></div>{outputs}</div></div>"
                + ("" if model.get('search_valid', True) else
                   "<div class='note warn'><b>Search not valid.</b> The search documents and the sync.search queue "
                   "coefficients come from runs with broken search mappings, so search figures are too low. Build the "
                   "model from a campaign with correct mappings.</div>")
                + "<noscript><div class='note'>The calculator needs JavaScript; the demo prediction is in the reproduction "
                "table below.</div></noscript>")

    # Reproduction.
    rep_rows = []
    for r in model['reproduction']:
        inside = r['within_run_range']
        rep_rows.append([esc(r['figure']), fmt_num(r['predicted']), fmt_num(r['measured_median']),
                         f"{fmt_num(r['measured_low'])} to {fmt_num(r['measured_high'])}", fmt_int(r['n']),
                         badge(inside, 'inside' if inside else 'outside') if inside is not None else badge(None)])
    body.append("<h3 class='sp'>Does the model reproduce the runs?</h3><div class='sub'>The prediction for the demo inputs "
                "against what the instrumented runs measured.</div>"
                + table([('figure', False), ('predicted', True), ('measured median', True), ('measured range', True),
                         ('runs', True), ('check', False)], rep_rows))

    # Coefficients.
    in_rows = [[esc(i['label']), esc(fmt_num(i['demo'])) if i['demo'] is not None else NOT_MEASURED, esc(i['demo_source']),
                esc(i['why'])] for i in model['inputs']]
    coeff = ["<h3 class='sp'>Inputs to ask for</h3>"
             + table([('input', False), ('demo value', True), ('demo from', False), ('why it matters', False)], in_rows)]
    types = sorted(model['import_types'].items(), key=lambda kv: -((kv[1]['ms_demo'] or {}).get('value') or 0))
    t_rows = []
    for t, e in types[:20]:
        c = e['ms']
        per_row = (f"{esc(fmt_ms(c['per_row']))} + {esc(fmt_ms(c['fixed']))} fixed" if c.get('model') == 'fit' else
                   f"{esc(fmt_ms(c['value']))} ({esc(fmt_ms(c['low']))} to {esc(fmt_ms(c['high']))})" if c.get('model') == 'per_row'
                   else f"{esc(fmt_ms(c['value']))} fixed")
        t_rows.append([esc(t), esc(e['driver']) + (' x stores' if e['per_store'] else ''), fmt_int(e['rows_demo']), per_row,
                       f"{e['events_per_row']['value']:.3g}", '-' if e['cpu_share'] is None else fmt_pct(100 * e['cpu_share']['value'], 0),
                       esc(_q(c))])
    coeff.append(f"<h3 class='sp'>Import types</h3><div class='sub'>Top {len(t_rows)} of {len(types)} by importer wall time. "
                 f"Time per row is importer wall time over rows imported, total over total, with the per-run range. The "
                 f"import remainder outside every importer ({esc(fmt_ms(model['import']['remainder_ms']['value']))} per run) "
                 f"scales with the importer total. Emits without an importer were attributed to the containing collection: "
                 f"{esc(', '.join(f'{k} {v}' for k, v in model['import']['attribution']['by'].items()))}; unattributed "
                 f"{fmt_int(model['import']['attribution']['unattributed'])}.</div>"
                 + table([('import type', False), ('driver', False), ('rows', True), ('ms per row', True), ('events per row', True),
                          ('CPU share', True), ('quality', False)], t_rows))
    qs = sorted(model['queues'].items(), key=lambda kv: -(kv[1]['messages_demo']['value'] or 0) * kv[1]['ms_per_message']['value'])
    q_rows = []
    for q, e in qs[:20]:
        comp = ', '.join(f"{d} {100 * s:.0f}%" for d, s in list(e['composition'].items())[:3] if s >= 0.005)
        f = e['fit']
        fit_text = (f"{esc(fmt_ms(f['fixed']))} + {esc(fmt_ms(f['marginal'] * 1000))} / 1 000, R&sup2; {f['r2']:.2f}"
                    if f.get('fixed') is not None and f.get('r2') is not None else 'not enough batches')
        m = e['ms_per_message']
        q_rows.append([esc(q), esc(comp), fmt_int(e['messages_demo']['value']),
                       f"{esc(fmt_ms(m['value']))} ({esc(fmt_ms(m['low']))} to {esc(fmt_ms(m['high']))})", fit_text,
                       fmt_int(e['chunk']), '-' if not e['mem_peak_mb'] else esc(rr.fmt_mb(e['mem_peak_mb']['p95']))])
    coeff.append(f"<h3 class='sp'>Queues</h3><div class='sub'>Top {len(q_rows)} of {len(qs)} by worker time. Per message "
                 "is task-run time plus the queue's direct-sync flush over messages; the fit is per batch (fixed + marginal "
                 "per 1 000 messages) and gives the low bound with full batches when R&sup2; is 0.5 or more. Drivers come "
                 "from the event names the queue's listeners saw.</div>"
                 + table([('queue', False), ('drivers', False), ('messages', True), ('per message', True),
                          ('batch fit', True), ('chunk', True), ('peak p95', True)], q_rows))
    p = pipe
    p_rows = [['worker cap', fmt_int(p['worker_cap']['value']), esc(p['worker_cap']['source'] or '-')],
              ['highest measured concurrency', fmt_int(p['worker_cap']['measured_max_concurrency']), 'task-run spans'],
              ['parallelism (worker s per busy s)', f"{p['parallelism']['value']:.2f} ({p['parallelism']['low']:.2f} to "
               f"{p['parallelism']['high']:.2f})", esc(p['parallelism']['note'])],
              ['drain efficiency', fmt_pct(100 * p['drain_efficiency']['value']), esc(p['drain_efficiency']['note'])],
              ['busy share of drain', fmt_pct(100 * p['busy_share']['value']), esc(p['busy_share']['note'])],
              ['drain overhead', esc(fmt_ms(1000 * p['drain_overhead_s']['value'])), esc(p['drain_overhead_s']['note'])]] \
        + ([['drain fit across scales', f"{p['drain_fit']['fixed_s']:.2f} s + {p['drain_fit']['per_work_s']:.3f} x worker s, "
             f"R&sup2; {p['drain_fit']['r2']:.3f}", esc(p['drain_fit']['note'])]] if p.get('drain_fit') else []) \
        if p['parallelism']['value'] is not None else []
    coeff.append("<h3 class='sp'>Pipeline</h3>" + table([('coefficient', False), ('value', True), ('meaning', False)], p_rows))
    val = model['validity']
    lin = val.get('linearity')
    lin_text = ('; '.join(f"{esc(v['label'])}: R&sup2; {v['r2']:.3f} over {v['n']} runs" for v in lin.values() if v.get('enough'))
                if lin else 'one measured scale only, so linearity is assumed, not shown')
    if len(val.get('per_scale') or []) > 1:
        ps_rows = [[f"{r['scale']:g}", fmt_int(r['runs']), fmt_int(r['rows']), fmt_int(r['events']), fmt_int(r['messages']),
                    esc(fmt_ms((r['import_s'] or 0) * 1000)), esc(fmt_ms((r['work_s'] or 0) * 1000)),
                    esc(fmt_ms((r['drain_s'] or 0) * 1000)), esc(fmt_ms((r['e2e_s'] or 0) * 1000))] for r in val['per_scale']]
        body.append("<h3 class='sp'>Measured per scale</h3><div class='sub'>Medians per scale of the import set. A linear "
                    f"chain doubles each column when the rows double. {lin_text}.</div>"
                    + table([('scale', True), ('runs', True), ('rows', True), ('events', True), ('messages', True),
                             ('import', True), ('worker time', True), ('drain', True), ('end to end', True)], ps_rows))
    coeff.append(f"<div class='note'><b>Validity.</b> Measured scales {esc(', '.join(f'{s:g}' for s in val['scales']))}. "
                 f"Linearity: {lin_text}. A prediction more than {val['tolerance'] * 100:.0f}% outside the measured range, "
                 "or with other stores, locales, currencies or worker count, is marked extrapolated.</div>")
    coeff.append("<h3 class='sp'>Assumptions</h3><ul class='verdict'>" + ''.join(f"<li>{esc(a)}</li>" for a in model['assumptions'])
                 + "</ul>")
    if model.get('warnings'):
        coeff.append("<div class='note warn'>" + '<br>'.join(esc(w) for w in model['warnings']) + "</div>")
    body.append("<details><summary>Coefficients, validity and assumptions</summary>" + ''.join(coeff) + "</details>")
    body.append(f"<script type='application/json' id='capacity-model'>{_json_for_script(model)}</script>")
    html = section('Capacity model', '', ''.join(body))
    line = (f"{model.get('scenario', '').replace('_', ' ')}: demo catalog import {fmt_ms(demo['import_s']['value'] * 1000)}, P&S {fmt_ms(demo['ps_s']['value'] * 1000)}, "
            f"{fmt_int(demo['messages'])} messages; calculator for other catalog sizes")
    return html, esc(line)


# ---------------------------------------------------------------------------------------------
# Where to look first: one combined rank per listener over time, queries, memory and CPU.
# ---------------------------------------------------------------------------------------------

def look_first(runs, min_messages_per_run=20):
    """Per listener and event: time per run, queries per message, largest memory added in one call
    and CPU share, each ranked (1 = worst) where measured, and the mean of the measured ranks as the
    combined rank. Pairs under `min_messages_per_run` messages per run are left out: their per-message
    figures are noise."""
    n_runs = max(len(runs), 1)
    groups = defaultdict(lambda: {'ms': 0.0, 'messages': 0, 'db_calls': None, 'mem': None, 'cpu': None, 'cpu_wall': 0.0,
                                  'queue': ''})
    for run in runs:
        for row in run.listeners:
            g = groups[(row.get('listener', '?'), row.get('event', '?'))]
            g['queue'] = row.get('queue') or g['queue']
            g['ms'] += row.get('ms') or 0.0
            g['messages'] += row.get('messages') or 0
            bd = row.get('breakdown')
            if isinstance(bd, dict) and bd.get('db_calls') is not None:
                g['db_calls'] = (g['db_calls'] or 0) + bd['db_calls']
            if row.get('mem_peak_incr_mb') is not None:
                g['mem'] = max(g['mem'] or 0.0, row['mem_peak_incr_mb'])
            if row.get('cpu_user_ms') is not None or row.get('cpu_sys_ms') is not None:
                g['cpu'] = (g['cpu'] or 0.0) + (row.get('cpu_user_ms') or 0.0) + (row.get('cpu_sys_ms') or 0.0)
                g['cpu_wall'] += row.get('ms') or 0.0
    rows = []
    for key, g in groups.items():
        if g['messages'] / n_runs < min_messages_per_run:
            continue
        rows.append({'key': key, 'queue': g['queue'], 'time': g['ms'] / n_runs, 'messages': g['messages'] / n_runs,
                     'queries': None if g['db_calls'] is None or not g['messages'] else g['db_calls'] / g['messages'],
                     'memory': g['mem'], 'cpu': None if g['cpu'] is None or not g['cpu_wall'] else g['cpu'] / g['cpu_wall']})
    metrics = ['time', 'queries', 'memory', 'cpu']
    for m in metrics:
        measured = sorted((r for r in rows if r[m] is not None), key=lambda r: -r[m])
        for i, r in enumerate(measured, 1):
            r[f"{m}_rank"] = i
    for r in rows:
        ranks = [r[f"{m}_rank"] for m in metrics if r.get(f"{m}_rank")]
        r['score'] = sum(ranks) / len(ranks) if ranks else None
    rows = [r for r in rows if r['score'] is not None]
    rows.sort(key=lambda r: (r['score'], -r['time']))
    for i, r in enumerate(rows, 1):
        r['rank'] = i
    return rows


def render_look_first(instr):
    rows = look_first(instr)
    if not rows:
        return '', 'no listener records'
    cpu_measured = any(r['cpu'] is not None for r in rows)
    mem_measured = any(r['memory'] is not None for r in rows)
    q_measured = any(r['queries'] is not None for r in rows)

    def cell(r, m, text):
        if r[m] is None:
            return NOT_MEASURED
        return f"{text} <span class='sub'>#{r[m + '_rank']}</span>"

    cells = []
    for r in rows[:15]:
        lst, ev = r['key']
        cells.append([str(r['rank']), f"{esc(lst)}<div class='sub'>{esc(ev)} &middot; {esc(r['queue'])}</div>",
                      cell(r, 'time', esc(fmt_ms(r['time']))), fmt_int(r['messages']),
                      cell(r, 'queries', f"{r['queries']:.3g}" if r['queries'] is not None else ''),
                      cell(r, 'memory', esc(rr.fmt_mb(r['memory'])) if r['memory'] is not None else ''),
                      cell(r, 'cpu', fmt_pct(100 * r['cpu'], 0) if r['cpu'] is not None else ''),
                      f"{r['score']:.1f}"])
    body = ("<div class='sub'>Every listener and event pair with at least 20 messages per run, ranked on each measure "
            "(#1 is the worst) and then on the mean of its ranks. A pair high on several measures at once is the first "
            "place to look. Top 15 of " + fmt_int(len(rows)) + ".</div>"
            + table([('rank', True), ('listener / event / queue', False), ('time per run', True), ('messages per run', True),
                     ('queries per message', True), ('largest memory added in one call', True), ('CPU share', True),
                     ('mean rank', True)], cells)
            + "<div class='note'><b>Reading this.</b> Time is listener time per run; queries are database calls (C3) over "
              "messages; memory is the largest allocated-memory increase of one call (C7); CPU share is CPU time over "
              "wall time (C8)" + ('' if cpu_measured else f", {NOT_MEASURED} in these runs")
            + ('' if q_measured else '; queries ' + NOT_MEASURED) + ('' if mem_measured else '; memory ' + NOT_MEASURED)
            + ". A measure that is not measured does not count in the mean rank.</div>")
    top = rows[0]
    line = f"#1 {top['key'][0]} on {top['key'][1]}, {fmt_ms(top['time'])} per run"
    return section('Where to look first', 'Performance suspects ranked on time, queries, memory and CPU together.', body), esc(line)


# ---------------------------------------------------------------------------------------------
# Page.
# ---------------------------------------------------------------------------------------------

def summary_lines(instr, plain, runs, ledger_rows, queues, listeners):
    """One computed fact per folded section, shown in its <summary>."""
    lines = {}
    stages = []
    for name in ('import', 'publish', 'sync', 'drain'):
        st = spread([r.stage_s(name) for r in instr + plain])
        if st['n']:
            stages.append(f"{name} {fmt_ms(st['median'] * 1000)}")
    lines['timeline'] = 'median stage times: ' + ', '.join(stages) if stages else 'no stage timings'
    if ledger_rows:
        total = sum(r['ms_sum'] for r in ledger_rows) or 1
        r = ledger_rows[0]
        lines['bottlenecks'] = f"#1 {r['key']}, {fmt_pct(100 * r['ms_sum'] / total)} of measured time"
    lines['distributions'] = (f"{fmt_int(len(queues))} queues and {fmt_int(len(listeners))} listener pairs, buckets merged "
                              f"over {len(instr)} runs")
    tasks = [t for run in instr for t in run.tasks]
    models = rr.queue_cost_models(tasks) if tasks else {}
    busy = [(q, m) for q, m in models.items() if m['messages'] >= 100 * max(len(instr), 1) and m['total_per_1000'] is not None]
    if busy:
        q, m = max(busy, key=lambda kv: kv[1]['total_per_1000'])
        lines['weighted'] = f"costliest per message: {q}, {fmt_ms(m['total_per_1000'])} per 1 000 messages"
    peaks = [(q, m['memory']['peak'][95]) for q, m in models.items() if m['memory']['peak']]
    if peaks:
        q, v = max(peaks, key=lambda kv: kv[1])
        lines['memory'] = f"highest batch peak p95: {q}, {rr.fmt_mb(v)}"
    e2e = spread([r.e2e_s for r in instr])
    if e2e['n']:
        lines['variance'] = (f"end to end {fmt_ms(e2e['median'] * 1000)}, {fmt_ms(e2e['min'] * 1000)} to "
                             f"{fmt_ms(e2e['max'] * 1000)} over {e2e['n']} runs")
    cost = overhead([r.e2e_s for r in instr], [r.e2e_s for r in plain])
    lines['overhead'] = (NOT_ENOUGH if not cost['enough'] else
                         f"{'+' if cost['delta'] >= 0 else ''}{fmt_pct(cost['delta_pct'])} end to end, "
                         + ('not distinguishable from noise' if not cost['distinguishable'] else 'larger than the noise'))
    rows = spread([r.rows_imported for r in instr])
    types = {t for run in instr for t in rr.aggregate_imports(run.imports)[0]}
    if types:
        lines['import'] = f"{fmt_int(len(types))} import types, {fmt_int(rows['median'])} rows per run"
    failed = [r for r in runs if not r.ok]
    lines['quality'] = f"{len(runs)} runs, {len(failed)} failed"
    return {k: esc(v) for k, v in lines.items()}


def render(campaign_dir, stamp=None):
    campaign_dir = pathlib.Path(campaign_dir)
    now = stamp['time'] if stamp else datetime.now(timezone.utc)
    campaign, runs, story = load_campaign(campaign_dir)
    instr = [r for r in runs if r.ok and r.instrumented]
    plain = [r for r in runs if r.ok and not r.instrumented]
    failed = [r for r in runs if not r.ok]
    ledger_rows, ledger_meta = aggregate_ledger(instr)
    queues = aggregate_queues(instr)
    listeners = aggregate_listeners(instr)
    lines = summary_lines(instr, plain, runs, ledger_rows, queues, listeners)

    model, model_file, model_error = model_for(campaign_dir) if instr else (None, None, 'no instrumented run')
    capacity_html, capacity_line = render_capacity(model, model_file, model_error)
    look_html, look_line = render_look_first(instr)

    scenarios_html, scenarios_line = render_scenarios(campaign_dir)
    folded = [
        ('scenarios', 'Scenarios', scenarios_html, scenarios_line),
        ('capacity', 'Capacity model', capacity_html, capacity_line),
        ('look-first', 'Where to look first', look_html, look_line),
        ('timeline', 'Pipeline timeline', render_timeline(instr, plain), lines.get('timeline', '')),
        ('bottlenecks', 'Bottlenecks', render_bottlenecks(instr, ledger_rows, ledger_meta, queues, listeners),
         lines.get('bottlenecks', '')),
        ('distributions', 'Distributions', render_distributions(queues, listeners, len(instr), instr),
         lines.get('distributions', '')),
        ('weighted', 'Weighted time', render_weighted(instr), lines.get('weighted', 'ms per 1 000 messages and batch fits')),
        ('memory', 'Memory', render_memory(instr), lines.get('memory', 'peak and added memory per queue and listener')),
        ('service', 'Queue service', render_queue_service(instr),
         lines.get('service', 'task cadence, backlog and broker traffic of the median run')),
        ('variance', 'Run-to-run variance', render_variance(instr, queues), lines.get('variance', '')),
        ('overhead', 'Instrumentation overhead', render_overhead(instr, plain), lines.get('overhead', '')),
        ('import', 'Import', render_import(instr), lines.get('import', '')),
        ('quality', 'Data quality', render_quality(campaign, runs), lines.get('quality', '')),
    ]
    folded = [(a, t, h, l) for a, t, h, l in folded if h]

    cid = campaign.get('id', campaign_dir.name)
    out = ["<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'>",
           "<meta name='viewport' content='width=device-width, initial-scale=1'>",
           f"<title>P&amp;S campaign {esc(cid)}</title><style>{rr.CSS}{EXTRA_CSS}{FOLD_CSS}</style></head><body><div class='wrap'>"]
    shas = sorted({str(r.meta.get('git_sha', ''))[:11] for r in runs if r.meta.get('git_sha')})
    contents = [('summary', 'Summary')] + ([('story', 'Story')] if story and story.strip() else []) \
        + [(a, t) for a, t, _, _ in folded]
    out.append("<header class='top'><div class='eyebrow'>SOL-629 &middot; import, publish and synchronize &middot; campaign</div>"
               f"<h1>How fast is the pipeline, and where does the time go</h1><div class='meta'>"
               f"<span>campaign <code>{esc(cid)}</code></span>"
               f"<span>{len(runs)} runs, order {esc(','.join(campaign.get('order', [])) or '-')}</span>"
               f"<span>commit <code>{esc(', '.join(shas) or 'unknown')}</code></span>"
               f"<span>started {esc(campaign.get('started', '-'))}</span>"
               f"<span>rendered {now.strftime('%Y-%m-%d %H:%M UTC')}</span></div>" + toc(contents) + "</header>")
    search_reason = capacity_module().search_invalid_reason(campaign)
    headline = render_headline(campaign, instr, plain, failed, ledger_rows, ledger_meta, queues, listeners)
    if search_reason:
        headline = headline[:-len('</section>')] + (
            f"<div class='note warn'><b>Search figures in this campaign are not valid.</b> {esc(search_reason)}. "
            "Search queue cost, search write time and document counts below are under-measured; take them from a "
            "campaign with correct mappings.</div></section>")
    out.append(headline.replace('<section>', "<section id='summary'>", 1))
    out.append(render_story(story))
    for anchor, _, html, line in folded:
        out.append(fold(html, anchor, line))
    out.append("<div class='footer'>SOL-629 &middot; generated by <code>tools/ps-metrics/render-campaign.py</code> from "
               "the campaign directory. Generated text states computed facts only; interpretation lives in "
               "<code>story.md</code>." + rr.stamp_footer(stamp) + "</div>")
    out.append(f"</div><div id='tip' role='tooltip'></div><script>{rr.TOOLTIP_JS}{FOLD_JS}"
               + (f"{PREDICT_JS}{CALC_JS}" if model is not None else '') + "</script></body></html>")
    return '\n'.join(part for part in out if part)


def main():
    return rr.cli_main(sys.argv, render, ['render-campaign.py', 'render-report.py', 'capacity-model.py'], 'test_campaign.py',
                       'usage: render-campaign.py <campaign-dir> [new-output.html] | --self-test')


if __name__ == '__main__':
    sys.exit(main())
