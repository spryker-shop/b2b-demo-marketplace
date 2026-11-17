#!/usr/bin/env python3
"""Render a SOL-629 campaign and its capacity model as one self-contained HTML slide deck.

Visual first: each slide has one headline that states the finding (a sentence of under 12 words),
one dominant chart, and at most 3 short labels or numbers. Interpretation beyond the headline goes
into the hidden speaker notes (<aside class='notes'>).

Every figure comes from the campaign records or the model JSON (contract C9). A figure that is not in
the data renders as "not measured", never as 0. Headlines are computed from the same figures.

Usage:
    render-deck.py <campaign dir> [more campaign dirs] [--model <model json>] [--out <new file.html>]
    render-deck.py --self-test                     runs test_deck.py beside this script

The default output is <first campaign dir>/deck-<UTC YYYYMMDD-HHMMSS>-<sha>.html, created exclusively.
An explicit --out path that exists is refused: a rendered deck is never replaced.

Navigation: arrow keys, Page Up/Down, space, Home/End, click (left third goes back), swipe; the slide
number is in the URL as #N, and ?slide=N opens slide N. Printing gives one slide per page.

Stdlib only. Imports the helpers of render-report.py, render-campaign.py and capacity-model.py
read-only.
"""

import importlib.util
import json
import math
import pathlib
import sys
from collections import defaultdict
from datetime import datetime, timezone

HERE = pathlib.Path(__file__).resolve().parent


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


rc = _load('render_campaign_for_deck', 'render-campaign.py')
rr = rc.rr
cm = rc.capacity_module()

esc, fmt_ms, fmt_int, fmt_num, fmt_pct, fmt_mb = rr.esc, rr.fmt_ms, rr.fmt_int, rr.fmt_num, rr.fmt_pct, rr.fmt_mb
NOT_MEASURED = rr.NOT_MEASURED
RENDERER_FILES = ['render-deck.py', 'render-campaign.py', 'render-report.py', 'capacity-model.py']
MAX_HEADLINE_WORDS = 11

W, H = 1600, 900          # slide canvas in CSS px; scaled to the viewport by the script
VW = 1456                 # visual width inside the 72 px side padding

# Palette: the validated reference instance, light only. Categorical slots in fixed order.
S = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948']
BLUE_RAMP = ['#cde2fb', '#b7d3f6', '#9ec5f4', '#86b6ef', '#6da7ec', '#5598e7', '#3987e5', '#2a78d6', '#256abf',
             '#1c5cab', '#184f95', '#104281', '#0d366b']
STAGE_COLOURS = rc.STAGE_COLOURS
INK, INK2, MUTED, GRID, AXIS = '#0b0b0b', '#52514e', '#898781', '#e1e0d9', '#c3c2b7'
CRIT, WARN, GOOD = '#d03b3b', '#fab219', '#0ca30c'
BREAKDOWN = [('db', 'database', S[0]), ('kv', 'key-value store', S[1]), ('search', 'search', S[2]),
             ('broker', 'broker', S[3]), ('other', 'PHP and other', S[4])]
CATALOG_KEYS = ['abstract_products', 'offers', 'categories', 'product_lists', 'cms_items', 'glossary_keys', 'merchants']
SIZES = [('demo', 'Demo catalog', 1), ('x10', '10x catalog', 10), ('x100', '100x catalog', 100)]


# ---------------------------------------------------------------------------------------------
# Small helpers.
# ---------------------------------------------------------------------------------------------

def na(value, formatter):
    return NOT_MEASURED if value is None else formatter(value)


def fmt_s(seconds):
    return na(seconds, lambda s: fmt_ms(s * 1000))


def fmt_about(seconds):
    """A rounded duration for a headline: whole minutes from 10 minutes on, whole seconds below."""
    if seconds is None:
        return NOT_MEASURED
    if seconds >= 600:
        return fmt_ms(round(seconds / 60) * 60_000)
    return fmt_ms(round(seconds) * 1000)


def words(text):
    return len(str(text).split())


def headline(*candidates):
    """The first candidate under 12 words. The last candidate is a short fallback that always fits."""
    for text in candidates:
        if text and words(text) <= MAX_HEADLINE_WORDS:
            return text
    raise ValueError(f"no headline under 12 words: {candidates!r}")


def event_short(event):
    """'ProductRelation.product_abstract_relation_store.publish' -> 'product_abstract_relation_store'."""
    parts = str(event).split('.')
    core = parts[1] if len(parts) >= 3 else str(event)
    return short(core, 26)


def short(name, limit=46):
    name = str(name)
    return name if len(name) <= limit else name[:limit - 1] + '…'


def t(x, y, text, size=18, fill=INK2, anchor='start', weight=400, extra=''):
    return (f"<text x='{x:.1f}' y='{y:.1f}' font-size='{size}' fill='{fill}' text-anchor='{anchor}' "
            f"font-weight='{weight}'{extra}>{esc(text)}</text>")


def rbar(x, y, w, h, fill, r=4, title=None, extra=''):
    """A bar whose data end (right) is rounded, anchored square at the baseline (left)."""
    w = max(w, 2.0)
    r = min(r, w / 2, h / 2)
    path = (f"M{x:.1f},{y:.1f}h{w - r:.1f}a{r},{r} 0 0 1 {r},{r}v{h - 2 * r:.1f}a{r},{r} 0 0 1 {-r},{r}"
            f"h{-(w - r):.1f}z")
    tip = f"<title>{esc(title)}</title>" if title else ''
    return f"<path d='{path}' fill='{fill}'{extra}>{tip}</path>"


def rect(x, y, w, h, fill, title=None, extra=''):
    tip = f"<title>{esc(title)}</title>" if title else ''
    return f"<rect x='{x:.1f}' y='{y:.1f}' width='{max(w, 2):.1f}' height='{h:.1f}' fill='{fill}'{extra}>{tip}</rect>"


def svg(width, height, body, label):
    return (f"<svg class='viz' viewBox='0 0 {width} {height}' width='{width}' height='{height}' role='img' "
            f"aria-label='{esc(label)}'>{body}</svg>")


def legend(entries):
    return "<div class='legend'>" + ''.join(f"<span><i style='background:{c}'></i>{esc(n)}</span>" for n, c in entries) + "</div>"


def callouts(items):
    """At most 3 short labels or numbers beside the visual: [(value, label)]."""
    items = items[:3]
    return "<div class='callouts'>" + ''.join(
        f"<div class='co'><div class='v'>{esc(v)}</div><div class='l'>{esc(lbl)}</div></div>" for v, lbl in items) + "</div>"


def empty_visual(text):
    return f"<div class='emptyviz'><span>{esc(text)}</span></div>"


def search_flag():
    return "<div class='flag'><b>!</b> Search figures not valid: broken search mappings</div>"


def nice_max(value):
    if not value or value <= 0:
        return 1.0
    exp = 10 ** math.floor(math.log10(value))
    for m in (1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10):
        if m * exp >= value:
            return m * exp
    return 10 * exp


def ticks(maximum, n=5):
    step = nice_max(maximum / n)
    out, v = [], 0.0
    while v <= maximum + 1e-9:
        out.append(v)
        v += step
    return out


# ---------------------------------------------------------------------------------------------
# Data.
# ---------------------------------------------------------------------------------------------

class Data:
    def __init__(self, campaign_dirs, model_path=None):
        self.dirs = [pathlib.Path(d) for d in campaign_dirs]
        self.campaigns, self.runs, self.search_invalid = [], [], []
        for d in self.dirs:
            campaign, runs, _ = rc.load_campaign(d)
            self.campaigns.append(campaign)
            self.runs += runs
            reason = cm.search_invalid_reason(campaign)
            if reason:
                self.search_invalid.append((campaign.get('id', d.name), reason))
        self.instr = [r for r in self.runs if r.ok and r.instrumented]
        self.plain = [r for r in self.runs if r.ok and not r.instrumented]
        self.failed = [r for r in self.runs if not r.ok]
        self.n_instr = max(len(self.instr), 1)
        self.queues = rc.aggregate_queues(self.instr)
        self.tasks = [t for r in self.instr for t in r.tasks]
        self.qmodels = rr.queue_cost_models(self.tasks) if self.tasks else {}
        self.model, self.model_file, self.model_error = self._model(model_path)

    def _model(self, model_path):
        if model_path:
            path = pathlib.Path(model_path)
            return json.loads(path.read_text()), path.name, None
        found = cm.latest_model(self.dirs[0])
        if found:
            return found[1], found[0].name, None
        if not self.instr:
            return None, None, 'no instrumented run'
        try:
            return cm.build_model(self.dirs), None, None
        except (ValueError, KeyError, ZeroDivisionError) as error:
            return None, None, str(error)

    @property
    def ids(self):
        return [str(c.get('id', d.name)) for c, d in zip(self.campaigns, self.dirs)]

    # ---- figures ----

    def e2e(self):
        return rc.spread([r.e2e_s for r in self.instr + self.plain])

    def messages_per_run(self):
        return rc.spread([r.messages for r in self.instr])['median']

    def rows_per_run(self):
        return rc.spread([r.rows_imported for r in self.instr])['median']

    def events_per_run(self):
        values = []
        for r in self.instr:
            emits = [x.get('events') for x in r.imports if x.get('kind') == 'publish_emit' and x.get('events') is not None]
            values.append(sum(emits) if emits else None)
        return rc.spread(values)['median']

    def kv_keys(self):
        return rc.spread([r.meta.get('kv_keys') for r in self.instr + self.plain])['median']

    def search_docs(self):
        return rc.spread([r.meta.get('es_docs') for r in self.instr + self.plain])['median']

    def breakdown(self):
        """Per queue and overall: database, key-value, search, broker and other ms, task runs plus their
        direct-sync flush. None when no record carries a C3 breakdown."""
        per_queue, any_bd = {}, False
        for name, q in self.queues.items():
            bd, fbd = q['breakdown'], q['flush_breakdown']
            if not bd and not fbd:
                continue
            any_bd = True
            row = {k: 0.0 for k, _, _ in BREAKDOWN}
            row.update({'total': 0.0, 'db_calls': 0, 'missing': set()})
            for part in (bd, fbd):
                if not part:
                    continue
                row['total'] += part['total']
                for key, _, _ in BREAKDOWN[:-1]:
                    if part[key] is None:
                        row['missing'].add(key)
                    else:
                        row[key] += part[key]
                row['other'] += max(part['other'], 0.0)
                row['db_calls'] += part.get('db_calls') or 0
            if q['flush_ms'] and not fbd:
                row['total'] += q['flush_ms']
                row['other'] += q['flush_ms']
            per_queue[name] = row
        if not any_bd:
            return None
        overall = {k: sum(r[k] for r in per_queue.values()) for k in ['total', 'db_calls'] + [k for k, _, _ in BREAKDOWN]}
        overall['missing'] = set().union(*(r['missing'] for r in per_queue.values()))
        return {'overall': overall, 'queues': per_queue}

    def concurrency(self):
        """Share of the drain window with 0, 1, ... cap processes running, per run; the median per level.
        Spans are task runs plus their direct-sync flush (as capacity-model.busy_profile)."""
        cap = (self.model or {}).get('pipeline', {}).get('worker_cap', {}).get('value') if self.model else None
        per_run = []
        for run in self.instr:
            drain = next((s for s in run.stages if s[0] == 'drain'), None)
            if not drain or not run.tasks:
                continue
            start, end = float(drain[1]), float(drain[2])
            if end <= start:
                continue
            by_queue, _ = rr.join_flushes(run.tasks, run.flushes)
            flush_by_pid = defaultdict(float)
            for flushes in by_queue.values():
                for f in flushes:
                    flush_by_pid[f.get('pid')] += f.get('ms') or 0.0
            events = []
            for task in run.tasks:
                if task.get('ts') is None:
                    continue
                a = task['ts']
                b = a + ((task.get('ms_total') or 0.0) + flush_by_pid.pop(task.get('pid'), 0.0)) / 1000
                a, b = max(a, start), min(b, end)
                if b > a:
                    events += [(a, 1), (b, -1)]
            events.sort()
            time_at = defaultdict(float)
            level, prev = 0, start
            for ts, step in events:
                time_at[level] += ts - prev
                level += step
                prev = ts
            time_at[level] += end - prev
            per_run.append({k: v / (end - start) for k, v in time_at.items()})
        if not per_run:
            return None
        top = max(max(p) for p in per_run)
        cap = int(cap or top)
        levels = list(range(0, max(cap, 1) + 1))
        out = {}
        for lv in levels:
            if lv == levels[-1]:
                vals = [sum(v for k, v in p.items() if k >= lv) for p in per_run]
            else:
                vals = [p.get(lv, 0.0) for p in per_run]
            out[lv] = rc.spread(vals)
        return {'levels': levels, 'share': out, 'runs': len(per_run), 'cap': cap}

    def memory_rows(self, min_per_run=100):
        rows = []
        for q, m in self.qmodels.items():
            if m['messages'] / self.n_instr < min_per_run:
                continue
            peak = m['memory']['peak']
            rows.append({'queue': q, 'p95': peak[95] if peak else None, 'per_100': m['memory']['per_100'],
                         'messages': m['messages'] / self.n_instr})
        return rows

    def fit_rows(self, min_per_run=100):
        rows = []
        for q, m in self.qmodels.items():
            fit = m['fit_total']
            if m['messages'] / self.n_instr < min_per_run or not fit.get('enough'):
                continue
            if fit['r2'] is None or fit['r2'] < 0.5 or fit['fixed'] < 0 or fit['marginal'] < 0:
                continue
            rows.append({'queue': q, 'fixed': fit['fixed'], 'marginal': fit['marginal'], 'r2': fit['r2'],
                         'n': fit['n'], 'ms_total': m['ms_total'], 'share_200': rr.fixed_share(fit, 200),
                         'share_1000': rr.fixed_share(fit, 1000),
                         'fixed_spent': fit['fixed'] * m['batches'] / m['ms_total'] if m['ms_total'] else None,
                         'fill': (self.queues.get(q) or {}).get('fill')})
        rows.sort(key=lambda r: -r['ms_total'])
        return rows

    def predictions(self):
        """predict() for the demo, 10x and 100x catalogs. None when there is no model, or when the model has
        no import rows: its import time would then be 0, which is not a measurement."""
        if not self.model:
            return None
        if not ((self.model.get('import') or {}).get('rows_demo') or {}).get('value'):
            self.model_error = self.model_error or 'the model has no import records, so import time is not measured'
            return None
        out = []
        for key, label, factor in SIZES:
            inputs = size_inputs(self.model, factor)
            out.append((key, label, factor, inputs, cm.predict(self.model, inputs)))
        return out


def size_inputs(model, factor):
    """Demo inputs with the catalog figures multiplied by `factor` (the calculator's x10 rule): products,
    offers, categories, product lists, CMS items, glossary rows and merchants. Stores, locales, currencies,
    per-product ratios and the non-catalog data stay at the demo value."""
    inputs = dict(model['demo_inputs'])
    for key in CATALOG_KEYS:
        if inputs.get(key) is not None:
            inputs[key] = inputs[key] * factor
    return inputs


# ---------------------------------------------------------------------------------------------
# Chart pieces.
# ---------------------------------------------------------------------------------------------

def hbar_rows(rows, width=VW, label_w=520, value_w=230, row_h=34, gap=18, scale=None, header=None):
    """Horizontal bars. rows: [(label, [(name, value, colour)], value text, tooltip)]. Stacked segments get a
    2 px surface gap; the last segment carries the rounded data end."""
    track = width - label_w - value_w - 24
    vmax = scale or max((sum(v for _, v, _ in segs) for _, segs, _, _ in rows), default=0) or 1
    height = len(rows) * (row_h + gap) + (30 if header else 0)
    body, y = [], (30 if header else 0)
    if header:
        body.append(t(label_w + 12, 18, header, 16, MUTED))
    for label, segs, text, tip in rows:
        x = label_w + 12
        body.append(t(label_w, y + row_h / 2 + 7, label if len(label) <= 80 else short(label, 80), 20, INK, 'end'))
        segs = [(n, v, c) for n, v, c in segs if v and v > 0]
        for i, (name, value, colour) in enumerate(segs):
            w = track * value / vmax
            last = i == len(segs) - 1
            draw_w = w - (0 if last else 2)
            if last:
                body.append(rbar(x, y, draw_w, row_h, colour, title=f"{label}: {name} {tip or ''}".strip()))
            else:
                body.append(rect(x, y, draw_w, row_h, colour, title=f"{label}: {name}"))
            x += w
        body.append(t(x + 12, y + row_h / 2 + 7, text, 20, INK2))
        y += row_h + gap
    return body, height


# ---------------------------------------------------------------------------------------------
# Slides. Each returns (headline, visual html, callouts html, notes text, flags html).
# ---------------------------------------------------------------------------------------------

def slide_title(d):
    e2e = d.e2e()
    msgs = d.messages_per_run()
    stages = []
    for name in ('import', 'publish', 'sync', 'drain'):
        stages.append((name, rc.spread([r.stage_s(name) for r in d.instr + d.plain])['median']))
    total = sum(v for _, v in stages if v) or 1
    hl = headline(
        f"The demo catalog publishes {fmt_int(msgs)} messages in {fmt_s(e2e['median'])}." if msgs and e2e['median'] else None,
        "Import, publish and synchronize: speed, bottlenecks and capacity.")
    x, last_end = 0, -1e9
    body = []
    bar_w = VW
    for i, (name, secs) in enumerate(stages):
        if not secs:
            continue
        w = bar_w * secs / total
        last = i == len(stages) - 1
        body.append(rbar(x, 70, w - (0 if last else 2), 90, STAGE_COLOURS[i], r=6, title=f"{name}: {fmt_s(secs)}")
                    if last else rect(x, 70, w - 2, 90, STAGE_COLOURS[i], title=f"{name}: {fmt_s(secs)}"))
        label = f"{name} {fmt_s(secs)}"
        if x < last_end + 12:      # label below would collide: put it above the bar
            body.append(f"<line x1='{x + 1:.1f}' x2='{x + 1:.1f}' y1='40' y2='68' stroke='{AXIS}' stroke-width='2'/>")
            body.append(t(x + 10, 50, label, 22, INK, weight=600))
        else:
            body.append(f"<line x1='{x + 1:.1f}' x2='{x + 1:.1f}' y1='162' y2='186' stroke='{AXIS}' stroke-width='2'/>")
            body.append(t(x + 10, 192, label, 22, INK, weight=600))
            last_end = x + 10 + 12.5 * len(label)
        x += w
    visual = (f"<div class='hero'><div class='eyebrow'>SOL-629 &middot; import, publish and synchronize</div>"
              f"<div class='big'>{esc(fmt_s(e2e['median']))}</div><div class='bigl'>end to end, median of {e2e['n']} runs</div></div>"
              + svg(VW, 240, ''.join(body), 'median stage times'))
    co = callouts([(', '.join(d.ids), 'campaign'), (f"{len(d.runs)} runs", f"{len(d.instr)} instrumented, {len(d.plain)} plain"),
                   (na(d.rows_per_run(), fmt_int), 'rows imported per run')])
    notes = ("Stage bar: median seconds per stage over all successful runs. Scenario of the model: "
             + ((d.model or {}).get('scenario') or NOT_MEASURED) + ".")
    return hl, visual, co, notes, ''


def slide_pipeline(d):
    rows, events, msgs, keys, docs = (d.rows_per_run(), d.events_per_run(), d.messages_per_run(), d.kv_keys(),
                                      d.search_docs())
    nodes = [('Rows imported', rows, S[0]), ('Events', events, S[0]), ('Queue messages', msgs, S[0]),
             ('Storage keys', keys, S[0])]
    hl = headline(f"One imported row becomes {msgs / rows:.1f} queue messages." if rows and msgs else None,
                  "Import rows fan out into events, messages and storage keys.")
    biggest = max((v for _, v, _ in nodes if v), default=1)
    rmax = 150
    cx = [150, 520, 890, 1260]
    cy = 260
    body = []
    for i, (label, value, colour) in enumerate(nodes):
        r = rmax * math.sqrt(value / biggest) if value else 0
        if value:
            body.append(f"<circle cx='{cx[i]}' cy='{cy}' r='{max(r, 6):.1f}' fill='{colour}' fill-opacity='0.88'>"
                        f"<title>{esc(label)}: {fmt_int(value)}</title></circle>")
        else:
            body.append(f"<circle cx='{cx[i]}' cy='{cy}' r='60' fill='none' stroke='{AXIS}' stroke-dasharray='6 6'/>")
        body.append(t(cx[i], cy + rmax + 58, na(value, fmt_int), 40, INK, 'middle', 650))
        body.append(t(cx[i], cy + rmax + 92, label, 22, INK2, 'middle'))
    ratios = [(rows, events, 'events per row'), (events, msgs, 'messages per event'), (msgs, keys, 'keys per message')]
    for i, (a, b, unit) in enumerate(ratios):
        x1, x2 = cx[i] + 40, cx[i + 1] - 40
        mid = (x1 + x2) / 2
        body.append(f"<line x1='{x1}' y1='{cy - rmax - 28}' x2='{x2 - 10}' y2='{cy - rmax - 28}' stroke='{INK2}' stroke-width='2'/>"
                    f"<path d='M{x2 - 12},{cy - rmax - 36} L{x2},{cy - rmax - 28} L{x2 - 12},{cy - rmax - 20}z' fill='{INK2}'/>")
        text = f"x {b / a:.2f}" if a and b else NOT_MEASURED
        body.append(t(mid, cy - rmax - 44, text, 26, INK, 'middle', 650))
        body.append(t(mid, cy - rmax - 6, unit, 17, MUTED, 'middle'))
    visual = svg(VW, 560, ''.join(body), 'pipeline counts per run')
    co = callouts([(na(docs, fmt_int), 'search documents (under-measured)' if d.search_invalid else 'search documents')])
    notes = ("Medians per instrumented run. Rows: importer records; events: publish_emit records; messages: task-run "
             "chunk_received; storage keys: meta.json kv_keys at drain end. Circle area is proportional to the count.")
    return hl, visual, co, notes, search_flag() if d.search_invalid else ''


def slide_flow(d):
    """The capacity model's flow chart: each driver group's share of rows, events, messages and worker time.
    Left out without a model."""
    if not d.model:
        return None
    cols = rc.chain_columns(d.model)
    shares = {}
    for i in (0, 3):
        total = sum(cols[i].values()) or 1
        shares[i] = {n: v / total for n, v in cols[i].items()}
    grow = max(shares[3], key=lambda n: shares[3][n] - shares[0].get(n, 0), default=None)
    hl = headline(f"{grow} is {shares[0].get(grow, 0):.0%} of rows but {shares[3][grow]:.0%} of worker time." if grow else None,
                  "Each part of the catalog grows or shrinks along the chain.")
    html = rc.flow_svg(d.model)
    flow = html[html.index('<svg'):html.index('</svg>') + len('</svg>')]
    flow = flow.replace("<svg class='flow'", "<svg class='flow' width='100%' style='max-height:600px'", 1)
    groups = [(n, c) for n, _, c in rc.DRIVER_GROUPS if any(col.get(n) for col in cols)]
    visual = legend(groups) + flow
    co = callouts([(f"{shares[3].get(n, 0):.0%}", f"of worker time: {n.lower()}")
                   for n in sorted(shares[3], key=lambda k: -shares[3][k])[:3]])
    notes = ("Each column is the demo data set split by driver group and scaled to its own total. A band that widens "
             "from left to right is a group whose cost grows faster than its row count. Groups come from name rules "
             "on import types, queues and event names (capacity model).")
    return hl, visual, co, notes, ''


def slide_e2e(d):
    runs = d.instr + d.plain
    runs.sort(key=lambda r: r.run_id)
    e2e = d.e2e()
    if not runs or e2e['median'] is None:
        return ("End-to-end time was not measured in these runs.", empty_visual(NOT_MEASURED), '', '', '')
    hl = headline(f"End to end takes {fmt_s(e2e['median'])}, within {e2e['spread_pct']:.0f}% across {e2e['n']} runs."
                  if e2e['spread_pct'] is not None else None,
                  f"End to end takes {fmt_s(e2e['median'])} per run.")
    vmax = max(r.e2e_s for r in runs)
    rows = []
    for r in runs:
        segs = [(name, r.stage_s(name) or 0, STAGE_COLOURS[i]) for i, name in enumerate(('import', 'publish', 'sync', 'drain'))]
        rows.append((f"{r.run_id} ({'instrumented' if r.instrumented else 'plain'})", segs, fmt_s(r.e2e_s), ''))
    body, height = hbar_rows(rows, label_w=360, value_w=120, row_h=44, gap=22, scale=vmax)
    visual = legend([(n, STAGE_COLOURS[i]) for i, n in enumerate(('import', 'publish', 'sync', 'drain'))]) + \
        svg(VW, height, ''.join(body), 'stage seconds per run')
    cost = rc.overhead([r.e2e_s for r in d.instr], [r.e2e_s for r in d.plain])
    over = ('not enough runs' if not cost['enough'] else
            ('within noise' if not cost['distinguishable'] else f"{cost['delta_pct']:+.0f}%"))
    co = callouts([(f"{fmt_s(e2e['min'])} to {fmt_s(e2e['max'])}", 'fastest to slowest run'),
                   (over, 'instrumentation cost end to end')])
    notes = "Stage seconds from stages.csv (whole seconds). Spread is (max - min) / median."
    return hl, visual, co, notes, ''


def slide_breakdown(d):
    bd = d.breakdown()
    if not bd:
        return ("Worker time split was not measured in these runs.", empty_visual(f"time breakdown {NOT_MEASURED}"), '', '', '')
    o = bd['overall']
    total = o['total'] or 1
    shares = {k: 100 * o[k] / total for k, _, _ in BREAKDOWN}
    top_key = max(shares, key=shares.get)
    top_name = dict((k, n) for k, n, _ in BREAKDOWN)[top_key]
    hl = headline(f"PHP code, not I/O, uses {shares['other']:.0f}% of worker time." if top_key == 'other' else None,
                  f"The {top_name} uses {shares[top_key]:.0f}% of worker time.")
    body = []
    x = 0
    for i, (key, name, colour) in enumerate(BREAKDOWN):
        if key in o['missing'] or not o[key]:
            continue
        w = VW * o[key] / total
        last = key == 'other'
        body.append(rbar(x, 40, w, 80, colour, r=6, title=f"{name}: {fmt_ms(o[key] / d.n_instr)} per run")
                    if last else rect(x, 40, w - 2, 80, colour, title=f"{name}: {fmt_ms(o[key] / d.n_instr)} per run"))
        if w > 90:
            body.append(t(x + 12, 30, f"{name} {shares[key]:.0f}%", 20, INK, weight=600))
        x += w
    small = ', '.join(f"{n} {shares[k]:.1f}%" for k, n, _ in BREAKDOWN if k not in o['missing'] and VW * o[k] / total <= 90)
    if small:
        body.append(t(0, 150, 'small: ' + small, 18, MUTED))
    top_bar = svg(VW, 160, ''.join(body), 'share of worker time by category')
    ranked = sorted(bd['queues'].items(), key=lambda kv: -kv[1]['total'])[:6]
    rows = [(q, [(n, v[k] / d.n_instr, c) for k, n, c in BREAKDOWN], fmt_ms(v['total'] / d.n_instr) + ' per run', '')
            for q, v in ranked]
    qbody, qh = hbar_rows(rows, label_w=440, value_w=170, row_h=30, gap=14)
    missing = [n for k, n, _ in BREAKDOWN if k in o['missing']]
    visual = (legend([(n, c) for _, n, c in BREAKDOWN]) + top_bar
              + "<div class='minihead'>Heaviest queues, per run</div>" + svg(VW, qh, ''.join(qbody), 'breakdown per queue')
              + (f"<div class='minihead'>{esc(', '.join(missing))} {NOT_MEASURED}</div>" if missing else ''))
    queries = o['db_calls'] / d.n_instr if o['db_calls'] else None
    co = callouts([(fmt_s(total / 1000 / d.n_instr), 'worker time per run'),
                   (na(queries, fmt_int), 'database queries per run'),
                   (na(o['db'] / o['db_calls'] if o['db_calls'] else None, fmt_ms), 'per query')])
    notes = ("C3 breakdown on task runs plus their direct-sync flush (C6). PHP and other is the span total minus the "
             "measured categories: ORM hydration, transfer building and event mapping.")
    return hl, visual, co, notes, search_flag() if d.search_invalid else ''


def slide_listeners(d):
    rows = rc.look_first(d.instr)[:8]
    if not rows:
        return ("Listener ranking was not measured in these runs.", empty_visual(NOT_MEASURED), '', '', '')
    top = rows[0]
    hl = headline(f"{top['key'][0]} is the first listener to fix.", "One listener leads the combined ranking.")
    metrics = [('time', 'time per run', lambda v: fmt_ms(v)), ('queries', 'queries per message', lambda v: f"{v:.3g}"),
               ('memory', 'largest memory per call', fmt_mb), ('cpu', 'CPU share', lambda v: fmt_pct(100 * v, 0))]
    counts = {m: sum(1 for r in rc.look_first(d.instr) if r[m] is not None) for m, _, _ in metrics}
    label_w, col_w, row_h, top_y = 640, 200, 58, 56
    body = []
    for j, (m, name, _) in enumerate(metrics):
        body.append(t(label_w + 20 + j * col_w + col_w / 2, 34, name, 18, INK2, 'middle', 600))
    for i, r in enumerate(rows):
        y = top_y + i * row_h
        body.append(t(0, y + 24, f"{r['rank']}.", 22, INK, weight=650))
        body.append(t(40, y + 24, short(r['key'][0], 48), 20, INK, weight=600))
        body.append(t(40, y + 46, short(r['key'][1], 60), 15, MUTED))
        for j, (m, _, fmt) in enumerate(metrics):
            x = label_w + 20 + j * col_w
            if r[m] is None:
                body.append(f"<rect x='{x + 4}' y='{y + 4}' width='{col_w - 8}' height='{row_h - 8}' rx='6' fill='none' "
                            f"stroke='{AXIS}' stroke-dasharray='4 4'/>")
                body.append(t(x + col_w / 2, y + row_h / 2 + 6, NOT_MEASURED, 16, MUTED, 'middle'))
                continue
            rank = r[f"{m}_rank"]
            step = 11 if rank <= 3 else 8 if rank <= 10 else 4 if rank <= 20 else 1
            fill = BLUE_RAMP[step]
            ink = '#ffffff' if step >= 7 else INK
            body.append(f"<rect x='{x + 4}' y='{y + 4}' width='{col_w - 8}' height='{row_h - 8}' rx='6' fill='{fill}'>"
                        f"<title>{esc(r['key'][0])}: rank {rank} of {counts[m]} on {m}</title></rect>")
            body.append(t(x + col_w / 2, y + row_h / 2 + 7, f"{fmt(r[m])}  #{rank}", 19, ink, 'middle', 600))
    height = top_y + len(rows) * row_h + 10
    visual = svg(VW, height, ''.join(body), 'combined listener ranking') + \
        "<div class='minihead'>Cell shade by rank on that measure: #1 to 3, 4 to 10, 11 to 20, above 20. #1 is the worst.</div>"
    co = callouts([(f"{top['score']:.1f}", 'mean rank of #1')])
    notes = ("Every listener and event pair with at least 20 messages per run, ranked on each measured figure; the "
             "combined rank is the mean of the measured ranks (render-campaign.py look_first). A figure that is not "
             "measured does not count.")
    return hl, visual, co, notes, ''


def slide_queries(d):
    rows = [r for r in rc.look_first(d.instr) if r['queries'] is not None]
    rows.sort(key=lambda r: -r['queries'])
    rows = rows[:9]
    if not rows:
        return ("Queries per message were not measured in these runs.", empty_visual(f"queries per message {NOT_MEASURED}"),
                '', '', '')
    top = rows[0]
    hl = headline(f"{top['key'][0]} sends {top['queries']:.0f} queries per message.",
                  f"The worst listener sends {top['queries']:.0f} queries per message.")
    vmax = nice_max(rows[0]['queries'])
    label_w, value_w = 760, 120
    bars = [(f"{short(r['key'][0], 44)} \u00b7 {event_short(r['key'][1])}", [('queries per message', r['queries'], S[0] if r['queries'] >= 1 else AXIS)],
             f"{r['queries']:.3g}", f"{fmt_int(r['messages'])} messages per run") for r in rows]
    body, height = hbar_rows(bars, label_w=label_w, value_w=value_w, row_h=36, gap=20, scale=vmax, header=None)
    track = VW - label_w - value_w - 24
    x1 = label_w + 12 + track * 1 / vmax
    body.append(f"<line x1='{x1:.1f}' y1='0' x2='{x1:.1f}' y2='{height - 10}' stroke='{INK}' stroke-width='2' stroke-dasharray='6 5'/>")
    body.append(t(x1 + 8, height + 14, '1 query per message', 17, INK2))
    visual = svg(VW, height + 24, ''.join(body), 'queries per message per listener')
    bd = d.breakdown()
    o = bd['overall'] if bd else None
    co = callouts([(na(o['db_calls'] / d.n_instr if o and o['db_calls'] else None, fmt_int), 'queries per run'),
                   (na(o['db'] / o['db_calls'] if o and o['db_calls'] else None, fmt_ms), 'mean query time, local'),
                   (f"{sum(1 for r in rows if r['queries'] >= 1)} of {len(rows)}", 'listeners above 1 per message')])
    notes = ("Database calls (C3 db_calls) over messages, per listener and event pair with 20 or more messages per run. "
             "Above 1 per message means a query per message instead of per batch (N+1). Local query time has no "
             "network latency; in a cloud environment each query costs more.")
    return hl, visual, co, notes, ''


def slide_parallelism(d):
    c = d.concurrency()
    if not c:
        return ("Drain parallelism was not measured in these runs.", empty_visual(NOT_MEASURED), '', '', '')
    share = {lv: (c['share'][lv]['median'] or 0.0) for lv in c['levels']}
    low = 100 * (share.get(0, 0) + share.get(1, 0))
    hl = headline(f"The drain runs one process or none {low:.0f}% of the time.",
                  "The drain rarely uses all worker processes.")
    levels = c['levels']
    ch, top, left = 470, 40, 80
    col = (VW - left) / len(levels)
    bw = col * 0.6
    body = []
    for v in (0, 25, 50, 75, 100):
        y = top + ch - ch * v / 100
        body.append(f"<line x1='{left}' x2='{VW}' y1='{y:.1f}' y2='{y:.1f}' stroke='{GRID}' stroke-width='1'/>")
        body.append(t(left - 12, y + 6, f"{v}%", 17, MUTED, 'end'))
    for i, lv in enumerate(levels):
        sp = c['share'][lv]
        v = 100 * share[lv]
        x = left + i * col + (col - bw) / 2
        h = ch * v / 100
        colour = S[0] if lv > 1 else S[1]
        body.append(f"<path d='M{x:.1f},{top + ch:.1f}v{-max(h - 4, 0):.1f}a4,4 0 0 1 4,-4h{bw - 8:.1f}a4,4 0 0 1 4,4"
                    f"v{max(h - 4, 0):.1f}z' fill='{colour}'><title>{lv} processes: {v:.1f}% (runs {100 * sp['min']:.0f}% to "
                    f"{100 * sp['max']:.0f}%)</title></path>")
        label_y = top + ch - h - 14
        if sp['min'] is not None and sp['max'] is not None and sp['max'] > sp['min']:
            y1, y2 = top + ch - ch * sp['max'], top + ch - ch * sp['min']
            body.append(f"<line x1='{x + bw / 2:.1f}' x2='{x + bw / 2:.1f}' y1='{y1:.1f}' y2='{y2:.1f}' stroke='{INK}' stroke-width='2'/>"
                        f"<line x1='{x + bw / 2 - 8:.1f}' x2='{x + bw / 2 + 8:.1f}' y1='{y1:.1f}' y2='{y1:.1f}' stroke='{INK}' stroke-width='2'/>")
            label_y = min(label_y, y1 - 12)
        body.append(t(x + bw / 2, label_y, f"{v:.0f}%", 24, INK, 'middle', 650))
        lab = f"{lv}+" if lv == levels[-1] and lv > 0 else str(lv)
        body.append(t(x + bw / 2, top + ch + 34, lab, 24, INK, 'middle', 600))
    body.append(t(left + (VW - left) / 2, top + ch + 72, 'processes running at the same time', 20, INK2, 'middle'))
    visual = svg(VW, top + ch + 84, ''.join(body), 'share of drain time by concurrent processes') + \
        legend([('0 or 1 process', S[1]), ('2 or more', S[0])])
    pipe = (d.model or {}).get('pipeline') or {}
    par = (pipe.get('parallelism') or {}).get('value')
    eff = (pipe.get('drain_efficiency') or {}).get('value')
    co = callouts([(f"{c['cap']}", 'worker process cap'), (na(par, lambda v: f"{v:.2f}"), 'mean busy processes'),
                   (na(eff, lambda v: fmt_pct(100 * v, 0)), 'drain efficiency')])
    notes = ("Drain window from stages.csv; spans are task runs plus their storage flush. Median share per level over "
             f"{c['runs']} runs, whisker min to max. Work is a chain: the sync queues fill only after publish ran.")
    return hl, visual, co, notes, ''


def slide_memory(d):
    rows = [r for r in d.memory_rows() if r['p95'] is not None]
    if not rows:
        return ("Memory per queue was not measured in these runs.", empty_visual(f"memory {NOT_MEASURED}"), '', '', '')
    rows.sort(key=lambda r: -r['p95'])
    rows = rows[:8]
    weighted = [r for r in rows if r['per_100'] is not None]
    if weighted:
        worst = max(weighted, key=lambda r: r['per_100'])
        hl = headline(f"{worst['queue']} adds {fmt_mb(worst['per_100'])} per 100 messages, the most.",
                      f"{worst['queue']} is the costliest queue per message.")
    else:
        hl = headline(f"{rows[0]['queue']} peaks highest at {fmt_mb(rows[0]['p95'])}.")
    half = (VW - 40) / 2
    left_rows = [(r['queue'], [('p95 peak', r['p95'], S[0])], fmt_mb(r['p95']), '') for r in rows]
    right_rows = [('', [('per 100 messages', r['per_100'] or 0, S[1])], na(r['per_100'], fmt_mb), '') for r in rows]
    lb, lh = hbar_rows(left_rows, width=half + 120, label_w=420, value_w=110, row_h=34, gap=18)
    rb, rh = hbar_rows(right_rows, width=half - 120, label_w=0, value_w=140, row_h=34, gap=18)
    visual = ("<div class='twin'><div><div class='minihead'>Absolute: batch peak, p95</div>"
              + svg(int(half + 120), lh, ''.join(lb), 'peak memory per queue') + "</div><div><div class='minihead'>"
              "Weighted: memory added per 100 messages</div>" + svg(int(half - 120), rh, ''.join(rb), 'memory per 100 messages')
              + "</div></div>")
    start = [t_.get('mem_start_mb') for t_ in d.tasks if t_.get('mem_start_mb') is not None]
    co = callouts([(na(rc.spread(start)['median'], fmt_mb), 'process start memory')])
    notes = ("Queues with 100 or more messages per run. Peak is task-run peak_mb p95 (includes the start memory). Per "
             "100 messages is the batch increment over messages, total over total (C7).")
    flag = search_flag() if d.search_invalid and any(r['queue'].startswith('sync.search.') for r in rows) else ''
    return hl, visual, co, notes, flag


def slide_fixed(d):
    rows = d.fit_rows()[:6]
    if not rows:
        return ("Fixed and per-message cost was not measured here.", empty_visual('not enough batches'), '', '', '')
    worst = max(rows, key=lambda r: r['fixed_spent'] or 0)
    hl = headline(f"{worst['queue']} spends {100 * worst['fixed_spent']:.0f}% of its time on fixed batch cost.",
                  f"{worst['queue']}: {100 * worst['fixed_spent']:.0f}% of its time is fixed batch cost.",
                  f"Fixed batch cost dominates {worst['queue']}.")
    bars = []
    for r in rows:
        for size in (200, 1000):
            fixed = r['fixed'] * 1000 / size
            marginal = r['marginal'] * 1000
            bars.append((f"{r['queue']}  @ {fmt_int(size)}" if size == 200 else f"@ {fmt_int(size)}",
                         [('fixed per batch', fixed, S[0]), ('per message', marginal, S[1])],
                         fmt_ms(fixed + marginal), f"R squared {r['r2']:.2f}, {r['n']} batches"))
    body, height = hbar_rows(bars, label_w=560, value_w=150, row_h=24, gap=10)
    visual = (legend([('fixed cost per batch, spread over its messages', S[0]), ('cost per message', S[1])])
              + "<div class='minihead'>Time per 1 000 messages at a batch of 200 and of 1 000</div>"
              + svg(VW, height, ''.join(body), 'fixed and per-message cost'))
    co = callouts([(fmt_ms(worst['fixed']), f"fixed per batch, {worst['queue']}"),
                   (na(worst['fill'], lambda v: fmt_pct(100 * v, 0)), 'mean batch fill'),
                   (f"{worst['r2']:.2f}", 'R squared of the fit')])
    notes = ("Least-squares fit of task-run ms_total = fixed + marginal x messages per queue (C7), queues with 100 or "
             "more messages per run and R squared 0.5 or more. Fixed share of time is fixed x batches over the measured "
             "time. A larger chunk helps only when batches are full; mean fill below 1 means the queue ran out of "
             "messages first.")
    flag = search_flag() if d.search_invalid and any(r['queue'].startswith('sync.search.') for r in rows) else ''
    return hl, visual, co, notes, flag


def slide_scaling(d):
    per_scale = ((d.model or {}).get('validity') or {}).get('per_scale') or []
    measured = [p for p in per_scale if p.get('e2e_s') is not None]
    left, top, ch, cw = 110, 30, 420, VW - 200
    body = []
    if len(measured) >= 2:
        hl = headline(f"Measured across {len(measured)} scales, end to end grows with size.")
        xs = [p['scale'] for p in measured]
        ymax = nice_max(max(max(p['e2e_s'] or 0, p['drain_s'] or 0, p['import_s'] or 0) for p in measured))
        xmax = max(xs)

        def px(x):
            return left + cw * x / xmax

        def py(y):
            return top + ch - ch * y / ymax
        for v in ticks(ymax):
            body.append(f"<line x1='{left}' x2='{left + cw}' y1='{py(v):.1f}' y2='{py(v):.1f}' stroke='{GRID}'/>")
            body.append(t(left - 12, py(v) + 6, fmt_s(v), 17, MUTED, 'end'))
        for key, name, colour in (('e2e_s', 'end to end', S[0]), ('import_s', 'import', S[1]), ('drain_s', 'drain', S[2])):
            pts = [(px(p['scale']), py(p[key])) for p in measured if p.get(key) is not None]
            body.append(f"<polyline points='{' '.join(f'{a:.1f},{b:.1f}' for a, b in pts)}' fill='none' stroke='{colour}' stroke-width='2'/>")
            for a, b in pts:
                body.append(f"<circle cx='{a:.1f}' cy='{b:.1f}' r='6' fill='{colour}' stroke='#fff' stroke-width='2'/>")
            body.append(t(pts[-1][0] + 12, pts[-1][1] + 6, name, 18, INK))
        for p in measured:
            body.append(t(px(p['scale']), top + ch + 32, f"{p['scale']:g}", 18, INK2, 'middle'))
        visual = svg(VW, top + ch + 70, ''.join(body), 'scaling curve')
        lin = (d.model.get('validity') or {}).get('linearity') or {}
        r2 = (lin.get('import_ms_vs_rows') or {}).get('r2')
        co = callouts([(na(r2, lambda v: f"{v:.2f}"), 'R squared, import against rows')])
        return hl, visual, co, 'Medians per scale from the model (validity.per_scale).', ''
    hl = headline("One scale measured, so scaling is assumed linear.")
    xmax = 1.0
    grid = [0.25, 0.5, 0.75, 1.0]
    e2e = measured[0]['e2e_s'] if measured else d.e2e()['median']
    ymax = nice_max((e2e or 1) * 1.2)

    def px(x):
        return left + cw * x / xmax

    def py(y):
        return top + ch - ch * y / ymax
    for v in ticks(ymax):
        body.append(f"<line x1='{left}' x2='{left + cw}' y1='{py(v):.1f}' y2='{py(v):.1f}' stroke='{GRID}'/>")
        body.append(t(left - 12, py(v) + 6, fmt_s(v), 17, MUTED, 'end'))
    if e2e:
        body.append(f"<line x1='{px(0):.1f}' y1='{py(0):.1f}' x2='{px(1):.1f}' y2='{py(e2e):.1f}' stroke='{AXIS}' "
                    f"stroke-width='2' stroke-dasharray='8 6'/>")
        body.append(t(px(0.62), py(e2e * 0.62) - 70, 'assumed linear', 20, MUTED, 'middle'))
    for g in grid:
        body.append(t(px(g), top + ch + 32, f"{g:g}", 18, INK2, 'middle'))
        if g < 1.0 and e2e:
            cy = py(e2e * g)
            body.append(f"<circle cx='{px(g):.1f}' cy='{cy:.1f}' r='16' fill='#ffffff' stroke='{MUTED}' stroke-width='2' "
                        f"stroke-dasharray='4 4'/>")
            body.append(t(px(g) + 4, cy + 48, NOT_MEASURED, 17, MUTED, 'middle'))
    if e2e:
        body.append(f"<circle cx='{px(1):.1f}' cy='{py(e2e):.1f}' r='10' fill='{S[0]}' stroke='#fff' stroke-width='2'>"
                    f"<title>scale 1.0: {fmt_s(e2e)}</title></circle>")
        body.append(t(px(1) - 16, py(e2e) - 18, f"{fmt_s(e2e)} measured", 20, INK, 'end', 650))
    body.append(t(left + cw / 2, top + ch + 66, 'scale of the import set', 18, INK2, 'middle'))
    visual = svg(VW, top + ch + 80, ''.join(body), 'scaling placeholder')
    co = callouts([(f"{len(measured) or 1}", 'scale measured')])
    notes = "Scaled campaigns (scale 0.25 to 1.0) replace this placeholder with a fitted curve."
    return hl, visual, co, notes, ''


def _pred_attr(key, field, which, value):
    return f" data-pred='{key}.{field}.{which}' data-value='{repr(float(value))}'" if value is not None else ''


def slide_prediction(d):
    preds = d.predictions()
    if not preds:
        return ("Capacity prediction was not measured: no model.", empty_visual(f"capacity model {NOT_MEASURED}"),
                '', d.model_error or '', '')
    big = preds[-1]
    hl = headline(f"A {big[2]}x catalog needs about {fmt_about(big[4]['total_s']['value'])} end to end.",
                  f"A {big[2]}x catalog needs about {fmt_about(big[4]['total_s']['value'])}.",
                  "Larger catalogs scale import and P&S time with size.")
    lo = min(min(p['import_s']['low'], p['ps_s']['low']) for *_, p in preds)
    hi = max(max(p['import_s']['high'], p['ps_s']['high']) for *_, p in preds)
    lo_dec = max(lo, 0.1) / 1.5
    hi_dec = hi * 1.3
    label_w, value_w = 330, 380
    track = VW - label_w - value_w - 40
    x0 = label_w + 20

    def px(v):
        return x0 + track * (math.log10(max(v, lo_dec)) - math.log10(lo_dec)) / (math.log10(hi_dec) - math.log10(lo_dec))
    body = ["<defs><pattern id='hatch' width='8' height='8' patternUnits='userSpaceOnUse' patternTransform='rotate(45)'>"
            f"<rect width='8' height='8' fill='{BLUE_RAMP[2]}'/><line x1='0' y1='0' x2='0' y2='8' stroke='{S[0]}' stroke-width='3'/>"
            "</pattern></defs>"]
    marks = [1, 10, 30, 60, 300, 600, 1800, 3600, 3 * 3600, 10 * 3600, 30 * 3600, 100 * 3600]
    marks = [m for m in marks if lo_dec <= m <= hi_dec]
    y_axis_top, y = 10, 30
    rows_h = []
    for key, label, factor, _, p in preds:
        rows_h.append((key, label, factor, p, y))
        y += 2 * 52 + 44
    plot_h = y
    for m in marks:
        body.append(f"<line x1='{px(m):.1f}' x2='{px(m):.1f}' y1='{y_axis_top}' y2='{plot_h - 20}' stroke='{GRID}'/>")
        body.append(t(px(m), plot_h + 6, fmt_s(m), 17, MUTED, 'middle'))
    for key, label, factor, p, y in rows_h:
        body.append(t(0, y + 30, label, 24, INK, weight=650))
        total = p['total_s']
        body.append(f"<text x='0' y='{y + 62}' font-size='20' fill='{INK2}'{_pred_attr(key, 'total_s', 'value', total['value'])}>"
                    f"total {esc(fmt_s(total['value']))}</text>")
        if p['extrapolated']:
            body.append(f"<text x='0' y='{y + 90}' font-size='17' fill='{CRIT}' font-weight='600'>extrapolated</text>")
        for j, (field, name) in enumerate((('import_s', 'import'), ('ps_s', 'publish + sync'))):
            b = p[field]
            yy = y + j * 52
            x1, x2 = px(b['low']), px(b['high'])
            fill = "url(#hatch)" if p['extrapolated'] else BLUE_RAMP[3]
            body.append(f"<rect x='{x1:.1f}' y='{yy + 8:.1f}' width='{max(x2 - x1, 4):.1f}' height='30' rx='6' fill='{fill}'>"
                        f"<title>{esc(label)} {name}: {fmt_s(b['low'])} to {fmt_s(b['high'])}</title></rect>")
            body.append(f"<circle cx='{px(b['value']):.1f}' cy='{yy + 23:.1f}' r='9' fill='{S[0]}' stroke='#fff' stroke-width='2'/>")
            body.append(t(x0 - 16, yy + 30, name, 18, INK2, 'end'))
            body.append(f"<text x='{VW - value_w + 20}' y='{yy + 30:.1f}' font-size='20' fill='{INK}' font-weight='600'"
                        f"{_pred_attr(key, field, 'value', b['value'])}>{esc(fmt_s(b['value']))}</text>")
            body.append(f"<text x='{VW - value_w + 150}' y='{yy + 30:.1f}' font-size='17' fill='{INK2}'>"
                        f"<tspan{_pred_attr(key, field, 'low', b['low'])}>{esc(fmt_s(b['low']))}</tspan> to "
                        f"<tspan{_pred_attr(key, field, 'high', b['high'])}>{esc(fmt_s(b['high']))}</tspan></text>")
    visual = (legend([('central value', S[0]), ('low to high', BLUE_RAMP[3]), ('extrapolated range', S[0] + "' class='hatch")])
              + svg(VW, plot_h + 20, ''.join(body), 'predicted time per catalog size'))
    workers = big[4]['workers']
    co = callouts([(f"{workers}", 'queue worker processes'),
                   ((d.model.get('scenario') or NOT_MEASURED).replace('_', ' '), 'scenario'),
                   (f"{len(d.model['validity']['scales'])} scale", 'measured, linear beyond it')])
    reasons = sorted({r for *_, p in preds for r in p['reasons']})
    notes = ("cm.predict(model, inputs) with the demo inputs, then products, offers, categories, product lists, CMS, "
             "glossary and merchants x10 and x100 (stores, locales, currencies and ratios unchanged). Log time axis. "
             "Extrapolated because: " + ('; '.join(reasons) or 'not extrapolated') + ". Model file: "
             + (d.model_file or 'built in memory, not saved') + '.')
    flag = search_flag() if d.search_invalid or not d.model.get('search_valid', True) else ''
    return hl, visual, co, notes, flag


def slide_resources(d):
    preds = d.predictions()
    if not preds:
        return ("Resource needs were not measured: no model.", empty_visual(f"capacity model {NOT_MEASURED}"), '', '', '')
    panels = [('messages', 'Queue messages', fmt_num), ('concurrent_peak_mb', 'Worker memory, busy processes together', fmt_mb),
              ('kv_keys', 'Key-value storage keys', fmt_num)]
    demo, big = preds[0][4], preds[-1][4]
    kv_k = big['kv_keys'] / demo['kv_keys'] if big['kv_keys'] and demo['kv_keys'] else None
    mem_flat = (demo['concurrent_peak_mb']['value'] == big['concurrent_peak_mb']['value'])
    hl = headline(f"At {preds[-1][2]}x, storage keys grow {kv_k:.0f}x; worker memory stays flat." if kv_k and mem_flat else None,
                  f"At {preds[-1][2]}x, storage keys grow {kv_k:.0f}x." if kv_k else None,
                  "Messages and storage grow with the catalog.")
    pw = (VW - 2 * 40) / 3
    html = ["<div class='triple'>"]
    for field, title, fmt in panels:
        vals = []
        for key, label, factor, _, p in preds:
            v = p[field]
            v = v['value'] if isinstance(v, dict) else v
            vals.append((key, label, factor, v))
        vmax = max((v for *_, v in vals if v), default=None)
        body = []
        ch, top = 330, 50
        bw = pw / 3 * 0.56
        for i, (key, label, factor, v) in enumerate(vals):
            x = i * pw / 3 + (pw / 3 - bw) / 2
            if v is None or not vmax:
                body.append(t(x + bw / 2, top + ch - 10, NOT_MEASURED, 16, MUTED, 'middle'))
            else:
                h = max(ch * v / vmax, 3)
                body.append(f"<path d='M{x:.1f},{top + ch}v{-(h - 4):.1f}a4,4 0 0 1 4,-4h{bw - 8:.1f}a4,4 0 0 1 4,4v{h - 4:.1f}z' "
                            f"fill='{S[0]}'><title>{esc(label)}: {esc(fmt(v))}</title></path>")
                body.append(f"<text x='{x + bw / 2:.1f}' y='{top + ch - h - 12:.1f}' font-size='20' fill='{INK}' "
                            f"text-anchor='middle' font-weight='650'{_pred_attr(key, field, 'value', v)}>{esc(fmt(v))}</text>")
            body.append(t(x + bw / 2, top + ch + 30, f"{factor}x" if factor != 1 else 'demo', 18, INK2, 'middle'))
        body.append(f"<line x1='0' x2='{pw:.1f}' y1='{top + ch}' y2='{top + ch}' stroke='{AXIS}'/>")
        html.append(f"<div><div class='minihead'>{esc(title)}</div>" + svg(int(pw), top + ch + 44, ''.join(body), title) + "</div>")
    html.append("</div>")
    co = callouts([(f"{demo['workers']}", 'worker processes, as measured'),
                   (na(demo['importer_peak_mb'], fmt_mb), 'import process peak')])
    notes = ("Central values of cm.predict(). Worker memory is the p95 batch peak times the busy processes; it depends on "
             "chunk sizes, not on the catalog. Search documents are left out: not valid in this campaign.")
    return hl, ''.join(html), co, notes, ''


ICONS = {
    'abstract_products': "<rect x='8' y='14' width='32' height='26' rx='3'/><path d='M8 22h32M20 14v8'/>",
    'concretes_per_abstract': "<rect x='6' y='18' width='22' height='22' rx='3'/><rect x='20' y='8' width='22' height='22' rx='3'/>",
    'stores': "<path d='M8 20l4-10h24l4 10z'/><rect x='10' y='20' width='28' height='20'/><path d='M20 40V28h8v12'/>",
    'locales_per_store': "<circle cx='24' cy='24' r='16'/><path d='M8 24h32M24 8c-6 6-6 26 0 32M24 8c6 6 6 26 0 32'/>",
    'currencies_per_store': "<circle cx='24' cy='24' r='16'/><path d='M29 17c-2-2-10-2-10 3s10 3 10 8-8 5-11 2M24 12v24'/>",
    'prices_per_product': "<path d='M8 24L24 8h14v14L22 38z'/><circle cx='31' cy='15' r='3'/>",
    'offers': "<path d='M6 26l10-10 8 6 8-6 10 10-18 14z'/>",
    'merchants': "<circle cx='24' cy='16' r='8'/><path d='M8 42c2-10 30-10 32 0'/>",
    'categories': "<rect x='18' y='6' width='12' height='10' rx='2'/><rect x='6' y='32' width='12' height='10' rx='2'/>"
                  "<rect x='30' y='32' width='12' height='10' rx='2'/><path d='M24 16v8M12 32v-8h24v8'/>",
    'product_lists': "<path d='M16 12h26M16 24h26M16 36h26'/><circle cx='8' cy='12' r='2'/><circle cx='8' cy='24' r='2'/>"
                     "<circle cx='8' cy='36' r='2'/>",
    'cms_items': "<path d='M12 6h18l8 8v28H12z'/><path d='M18 22h14M18 30h14'/>",
    'glossary_keys': "<path d='M6 38l9-28h4l9 28M10 28h14'/><path d='M32 26c4-4 10-2 10 4v8M42 32c-8-2-12 6-4 6 2 0 4-1 4-3'/>",
    'other_scale': "<circle cx='24' cy='24' r='16'/><path d='M24 16v16M16 24h16'/>",
}


def slide_inputs(d):
    if not d.model:
        return ("Catalog inputs were not measured: no model.", empty_visual(f"capacity model {NOT_MEASURED}"), '', '', '')
    inputs = d.model['inputs']
    hl = headline(f"Ask a customer for {len(inputs)} catalog figures to size a run.")
    tiles = []
    for i in inputs:
        icon = ICONS.get(i['key'], ICONS['other_scale'])
        demo = na(i['demo'], fmt_num)
        tiles.append(f"<div class='tl' title='{esc(i['why'])}'><svg viewBox='0 0 48 48' width='56' height='56' fill='none' "
                     f"stroke='{S[0]}' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'>{icon}</svg>"
                     f"<div class='tlv'>{esc(demo)}</div><div class='tll'>{esc(i['label'])}</div></div>")
    visual = "<div class='tiles'>" + ''.join(tiles) + "</div><div class='minihead'>Value shown: the demo catalog measured here</div>"
    notes = ' '.join(f"{i['label']}: {i['why']}." for i in inputs)
    return hl, visual, '', notes, ''


def slide_quality(d):
    items = []
    total, ok = len(d.runs), len([r for r in d.runs if r.ok])
    items.append(('good' if ok == total else 'crit', f"{ok} of {total} runs completed"))
    for cid, reason in d.search_invalid:
        items.append(('crit', f"Search not valid in {cid}: broken mappings"))
    scales = (d.model or {}).get('validity', {}).get('scales') if d.model else None
    if scales is None:
        items.append(('warn', f"Scales {NOT_MEASURED}: no model"))
    elif len(scales) < 2:
        items.append(('warn', "One scale measured; linear scaling is assumed"))
    else:
        items.append(('good', f"{len(scales)} scales measured"))
    items.append(('warn', "Local Docker timing, no network latency"))
    cost = rc.overhead([r.e2e_s for r in d.instr], [r.e2e_s for r in d.plain])
    if not cost['enough']:
        items.append(('warn', "Instrumentation cost: not enough plain runs"))
    else:
        items.append(('good' if not cost['distinguishable'] else 'warn',
                      f"Instrumentation adds {cost['delta_pct']:+.0f}% end to end"
                      + (', within noise' if not cost['distinguishable'] else '')))
    if d.model:
        rep = d.model.get('reproduction') or []
        inside = sum(1 for r in rep if r.get('within_run_range'))
        items.append(('good' if rep and inside == len(rep) else 'warn', f"Model reproduces {inside} of {len(rep)} measured figures"))
        cpu = any(e.get('cpu_share') for e in d.model.get('queues', {}).values())
        items.append(('good' if cpu else 'warn', 'CPU time measured' if cpu else f"CPU time {NOT_MEASURED} in this campaign"))
    bad = sum(1 for s, _ in items if s != 'good')
    hl = headline(f"{bad} limits apply; search and scaling are the largest." if d.search_invalid and bad > 1 else None,
                  f"{bad} limits apply to these figures." if bad else "The data passes every quality check.")
    icon = {'good': ('&#10003;', GOOD), 'warn': ('!', WARN), 'crit': ('&#215;', CRIT)}
    rows = ''.join(f"<li><span class='qi' style='background:{icon[s][1]};color:{'#0b0b0b' if s == 'warn' else '#fff'}'>{icon[s][0]}</span>"
                   f"<span class='qs'>{esc({'good': 'ok', 'warn': 'limit', 'crit': 'invalid'}[s])}</span>{esc(text)}</li>"
                   for s, text in items)
    visual = f"<ul class='quality'>{rows}</ul>"
    notes = ("; ".join(f"{cid}: {reason}" for cid, reason in d.search_invalid)
             + ". Times are from a local environment; database and search latency in a cloud environment raise the cost per "
               "message. All runs are the update path (DB already populated).")
    return hl, visual, '', notes, ''


def slide_next(d):
    steps = []
    q = [r for r in rc.look_first(d.instr) if r['queries'] is not None]
    if q:
        worst = max(q, key=lambda r: r['queries'])
        steps.append(('Fix N+1 queries', short(worst['key'][0], 50)))
    fits = d.fit_rows()
    if fits:
        f = max(fits, key=lambda r: r['fixed_spent'] or 0)
        steps.append(('Cut the fixed cost per batch', f"{f['queue']}, {fmt_pct(100 * f['fixed_spent'], 0)} of its time"))
    mem = [r for r in d.memory_rows() if r['p95'] is not None]
    if mem:
        m = max(mem, key=lambda r: r['p95'])
        steps.append(('Cap the chunk where memory peaks', f"{m['queue']}, {fmt_mb(m['p95'])}"))
    if d.search_invalid:
        steps.append(('Re-measure search with correct mappings', 'search queues and documents'))
    scales = (d.model or {}).get('validity', {}).get('scales') if d.model else None
    if not scales or len(scales) < 2:
        steps.append(('Run scaled campaigns', 'fit the scaling curve'))
    steps.append(('Measure on a cloud environment', 'real database and search latency'))
    steps = steps[:6]
    hl = headline(f"{len(steps)} steps turn these findings into faster runs.")
    cards = ''.join(f"<div class='step'><div class='num'>{i}</div><div class='st'>{esc(a)}</div><div class='sd'>{esc(b)}</div></div>"
                    for i, (a, b) in enumerate(steps, 1))
    visual = f"<div class='steps'>{cards}</div>"
    return hl, visual, '', 'Ordered by expected effect on the measured bottlenecks.', ''


def slide_scenarios(d):
    """The other scenarios of the round (companions.json), side by side at catalog x100. None without companions:
    the slide is then left out."""
    if not rc.load_companions(d.dirs[0]):
        return None
    rows = [r for r in (rc.scenario_row(x) for x in [d.dirs[0]] + rc.load_companions(d.dirs[0])) if r]
    if len(rows) < 2:
        return None
    rows.sort(key=lambda r: rc.SCENARIO_ORDER.index(r['scenario']) if r['scenario'] in rc.SCENARIO_ORDER else 99)
    title = rc.SCENARIO_TITLES
    slowest = max(rows, key=lambda r: r['preds'][100]['total_s']['value'])
    hl = headline(f"At 100x the catalog, {title.get(slowest['scenario'], slowest['scenario']).lower()} takes longest: "
                  f"{fmt_about(slowest['preds'][100]['total_s']['value'])}.",
                  f"{title.get(slowest['scenario'], slowest['scenario'])} takes longest at 100x.")
    bars = []
    for r in rows:
        tot = r['preds'][100]['total_s']
        bars.append((title.get(r['scenario'], r['scenario']),
                     [('import', r['preds'][100]['import_s']['value'], BLUE_RAMP[1]),
                      ('publish + sync', r['preds'][100]['ps_s']['value'], S[0])],
                     f"{fmt_s(tot['value'])} ({fmt_s(tot['low'])} to {fmt_s(tot['high'])})", ''))
    body, height = hbar_rows(bars, label_w=330, value_w=420)
    visual = legend([('import', BLUE_RAMP[1]), ('publish + sync', S[0])]) + svg(VW, height, ''.join(body),
                                                                                 'predicted time per scenario at catalog x100')
    measured = ', '.join(f"{title.get(r['scenario'], r['scenario']).lower()} {fmt_s(r['e2e']['median'])}"
                         for r in rows if r['e2e']['n'])
    co = callouts([(str(len(rows)), 'scenarios measured'), (rows[0]['source'], 'worker source'),
                   ('100x', 'catalog figures, stores and locales unchanged')])
    notes = ("One model per scenario campaign (" + ', '.join(r['id'] for r in rows) + "), each predicted with the catalog "
             "inputs x100. Measured at demo size: " + measured + ".")
    return hl, visual, co, notes, ''


SLIDES = [slide_title, slide_pipeline, slide_flow, slide_e2e, slide_breakdown, slide_listeners, slide_queries, slide_parallelism,
          slide_memory, slide_fixed, slide_scaling, slide_prediction, slide_scenarios, slide_resources, slide_inputs,
          slide_quality, slide_next]


# ---------------------------------------------------------------------------------------------
# Page.
# ---------------------------------------------------------------------------------------------

CSS = f"""
:root{{--ink:{INK};--ink2:{INK2};--muted:{MUTED};--grid:{GRID};--axis:{AXIS};--page:#eeeeea;--slide:#ffffff;
--s1:{S[0]};--crit:{CRIT};color-scheme:light}}
*{{box-sizing:border-box}}
html,body{{margin:0;height:100%;background:var(--page);color:var(--ink);
font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;overflow:hidden}}
#deck{{position:absolute;left:50%;top:50%;width:{W}px;height:{H}px;transform-origin:center center}}
.slide{{position:absolute;inset:0;background:var(--slide);padding:56px 72px 0;display:none;flex-direction:column;
overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.08)}}
.slide.on{{display:flex}}
.slide h1{{font-size:46px;line-height:1.12;font-weight:680;margin:0 0 6px;letter-spacing:-.01em;max-width:1300px}}
.slide .flag{{display:inline-flex;gap:10px;align-items:center;font-size:18px;color:var(--ink);background:#fff1d6;
border-radius:999px;padding:4px 16px 4px 6px;margin:4px 0 0;align-self:flex-start}}
.slide .flag b{{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:50%;background:{WARN};font-size:15px}}
.body{{flex:1;display:flex;flex-direction:column;justify-content:center;min-height:0;padding:12px 0}}
.callouts{{display:flex;gap:56px;margin:4px 0 0}}
.co .v{{font-size:34px;font-weight:680;line-height:1.1;font-variant-numeric:tabular-nums}}
.co .l{{font-size:17px;color:var(--ink2);margin-top:2px}}
.foot{{height:52px;display:flex;align-items:center;justify-content:space-between;color:var(--muted);font-size:15px;
border-top:1px solid var(--grid);margin-top:10px}}
svg.viz{{display:block;max-width:100%;height:auto;overflow:visible}}
svg.viz text{{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}}
.legend{{display:flex;flex-wrap:wrap;gap:6px 28px;font-size:18px;color:var(--ink2);margin:0 0 10px}}
.legend i{{display:inline-block;width:16px;height:16px;border-radius:3px;margin-right:8px;vertical-align:-2px}}
.legend i.hatch{{background:repeating-linear-gradient(45deg,{BLUE_RAMP[2]} 0 4px,{S[0]} 4px 6px)!important}}
.minihead{{font-size:18px;color:var(--ink2);margin:14px 0 8px;font-weight:600}}
.twin{{display:grid;grid-template-columns:{(VW - 40) / 2 + 120:.0f}px {(VW - 40) / 2 - 120:.0f}px;gap:40px}}
.triple{{display:grid;grid-template-columns:repeat(3,1fr);gap:40px}}
.hero .eyebrow{{font-size:18px;color:var(--ink2);text-transform:uppercase;letter-spacing:.08em;font-weight:600}}
.hero .big{{font-size:150px;font-weight:700;line-height:1;margin:18px 0 4px;color:var(--s1)}}
.hero .bigl{{font-size:24px;color:var(--ink2);margin-bottom:40px}}
.emptyviz{{display:grid;place-items:center;height:420px;border:2px dashed var(--axis);border-radius:16px;color:var(--muted);font-size:28px}}
.tiles{{display:grid;grid-template-columns:repeat(5,1fr);gap:18px}}
.tl{{background:#f7f7f4;border:1px solid rgba(11,11,11,.08);border-radius:14px;padding:16px 18px;min-height:150px}}
.tlv{{font-size:30px;font-weight:680;margin-top:6px;font-variant-numeric:tabular-nums}}
.tll{{font-size:17px;color:var(--ink2);margin-top:2px;line-height:1.25}}
ul.quality{{list-style:none;margin:0;padding:0;display:grid;gap:14px}}
ul.quality li{{display:flex;align-items:center;gap:18px;font-size:28px;padding:12px 20px;background:#f7f7f4;border-radius:12px}}
.qi{{display:inline-grid;place-items:center;width:40px;height:40px;border-radius:50%;font-weight:700;font-size:24px;flex:none}}
.qs{{font-size:16px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);width:90px;flex:none}}
.steps{{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}}
.step{{background:#f7f7f4;border-radius:16px;padding:26px 26px 30px;border-top:6px solid var(--s1);min-height:200px}}
.step .num{{font-size:56px;font-weight:700;color:var(--s1);line-height:1}}
.step .st{{font-size:28px;font-weight:650;margin-top:14px;line-height:1.2}}
.step .sd{{font-size:19px;color:var(--ink2);margin-top:10px;overflow-wrap:anywhere}}
aside.notes{{display:none}}
#nav{{position:fixed;right:16px;bottom:12px;display:flex;gap:8px;align-items:center;font-size:14px;color:var(--ink2);z-index:5}}
#nav button{{font:inherit;border:1px solid rgba(11,11,11,.15);background:#fff;border-radius:8px;padding:4px 12px;cursor:pointer}}
body.shot #nav{{display:none}}
/* Each slide footer already shows its number; the fixed counter would overlap it at the bottom right. */
#counter{{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}}
#nav{{bottom:56px}}
@page{{size:{W}px {H}px;margin:0}}
@media print{{
 html,body{{overflow:visible;height:auto;background:#fff}}
 #deck{{position:static;transform:none!important;width:{W}px;height:auto}}
 .slide{{display:flex!important;position:relative;width:{W}px;height:{H}px;box-shadow:none;break-after:page;page-break-after:always}}
 #nav{{display:none}}
}}
"""

JS = """
(function(){
var slides=[].slice.call(document.querySelectorAll('.slide'));var n=slides.length;var deck=document.getElementById('deck');
var counter=document.getElementById('counter');var cur=0;
function fromUrl(){var m=location.hash.match(/^#(\\d+)$/);var q=new URLSearchParams(location.search);
 var v=m?+m[1]:(q.get('slide')?+q.get('slide'):1);if(q.get('shot'))document.body.classList.add('shot');
 return Math.min(Math.max(v||1,1),n)-1;}
function show(i,push){cur=Math.min(Math.max(i,0),n-1);slides.forEach(function(s,k){s.classList.toggle('on',k===cur);});
 counter.textContent=(cur+1)+' / '+n;if(push&&history.replaceState)history.replaceState(null,'','#'+(cur+1));}
function fit(){var s=Math.min(window.innerWidth/%(W)d,window.innerHeight/%(H)d);
 deck.style.transform='translate(-50%%,-50%%) scale('+s+')';}
document.addEventListener('keydown',function(e){
 if(['ArrowRight','PageDown',' ','ArrowDown'].indexOf(e.key)>=0){show(cur+1,true);e.preventDefault();}
 else if(['ArrowLeft','PageUp','ArrowUp'].indexOf(e.key)>=0){show(cur-1,true);e.preventDefault();}
 else if(e.key==='Home'){show(0,true);}else if(e.key==='End'){show(n-1,true);}});
deck.addEventListener('click',function(e){if(e.target.closest('a,button'))return;
 show(e.clientX<window.innerWidth/3?cur-1:cur+1,true);});
document.getElementById('prev').addEventListener('click',function(){show(cur-1,true);});
document.getElementById('next').addEventListener('click',function(){show(cur+1,true);});
var x0=null;document.addEventListener('touchstart',function(e){x0=e.touches[0].clientX;},{passive:true});
document.addEventListener('touchend',function(e){if(x0===null)return;var dx=e.changedTouches[0].clientX-x0;
 if(Math.abs(dx)>50)show(dx<0?cur+1:cur-1,true);x0=null;});
window.addEventListener('hashchange',function(){show(fromUrl(),false);});
window.addEventListener('resize',fit);fit();show(fromUrl(),false);})();
""" % {'W': W, 'H': H}


def render(campaign_dirs, model_path=None, stamp=None):
    d = Data(campaign_dirs, model_path)
    now = stamp['time'] if stamp else datetime.now(timezone.utc)
    slides = []
    made = [(fn, out) for fn, out in ((fn, fn(d)) for fn in SLIDES) if out is not None]
    total = len(made)
    for i, (fn, out) in enumerate(made, 1):
        hl, visual, co, notes, flag = out
        foot_left = f"SOL-629 &middot; campaign {esc(', '.join(d.ids))}"
        if i == 1 and stamp:
            foot_left += (f" &middot; {esc(stamp['file'])} &middot; renderer {esc(stamp['version'])} &middot; "
                          f"{esc(now.strftime('%Y-%m-%d %H:%M UTC'))}")
        slides.append(f"<section class='slide' id='s{i}' data-slide='{i}' data-name='{fn.__name__[6:]}'>"
                      f"<h1>{esc(hl)}</h1>{flag}<div class='body'>{visual}</div>{co}"
                      f"<div class='foot'><span>{foot_left}</span><span>{i} / {total}</span></div>"
                      f"<aside class='notes'>{esc(notes)}</aside></section>")
    title = f"P&amp;S deck {esc(', '.join(d.ids))}"
    return ("<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'>"
            "<meta name='viewport' content='width=device-width, initial-scale=1'>"
            f"<title>{title}</title><style>{CSS}</style></head><body><div id='deck'>{''.join(slides)}</div>"
            "<div id='nav'><button id='prev' type='button' aria-label='previous slide'>&larr;</button>"
            "<span id='counter'></span><button id='next' type='button' aria-label='next slide'>&rarr;</button></div>"
            f"<script>{JS}</script></body></html>")


# ---------------------------------------------------------------------------------------------
# Output: never overwrite.
# ---------------------------------------------------------------------------------------------

def versioned_target(directory, now, version):
    base = 'deck-' + now.strftime('%Y%m%d-%H%M%S')
    sha = version.split('+')[0]
    if sha and sha != 'unknown':
        base += '-' + sha
    candidate, n = directory / f"{base}.html", 2
    while candidate.exists():
        candidate, n = directory / f"{base}-{n}.html", n + 1
    return candidate


def write_deck(campaign_dirs, model_path=None, explicit=None, now=None):
    """Render to a new file and return its path. FileExistsError for an explicit path that exists; the
    file is opened with exclusive create, so a race cannot overwrite either."""
    now = now or datetime.now(timezone.utc)
    version = rr.renderer_version(*RENDERER_FILES)
    if explicit is not None:
        target = pathlib.Path(explicit)
        if target.exists():
            raise FileExistsError(f"refusing to overwrite {target}: a rendered deck is never replaced; "
                                  "choose a new path or omit --out for a versioned name")
    else:
        target = versioned_target(pathlib.Path(campaign_dirs[0]), now, version)
    target.parent.mkdir(parents=True, exist_ok=True)
    html = render(campaign_dirs, model_path, rr.render_stamp(target, version, now))
    with open(target, 'x', encoding='utf-8') as handle:
        handle.write(html)
    return target


USAGE = 'usage: render-deck.py <campaign dir> [more campaign dirs] [--model <model json>] [--out <new.html>] | --self-test'


def main(argv):
    if len(argv) > 1 and argv[1] == '--self-test':
        import unittest
        suite = unittest.defaultTestLoader.discover(str(HERE), pattern='test_deck.py')
        return 0 if unittest.TextTestRunner(verbosity=2).run(suite).wasSuccessful() else 1
    dirs, model, out, args = [], None, None, argv[1:]
    i = 0
    while i < len(args):
        if args[i] in ('--model', '--out'):
            if i + 1 >= len(args):
                print(USAGE, file=sys.stderr)
                return 2
            if args[i] == '--model':
                model = args[i + 1]
            else:
                out = args[i + 1]
            i += 2
            continue
        dirs.append(args[i])
        i += 1
    if not dirs:
        print(USAGE, file=sys.stderr)
        return 2
    for d in dirs:
        if not pathlib.Path(d).is_dir():
            print(f'not a directory: {d}', file=sys.stderr)
            return 2
    if model and not pathlib.Path(model).is_file():
        print(f'not a file: {model}', file=sys.stderr)
        return 2
    try:
        target = write_deck(dirs, model, out)
    except FileExistsError as error:
        print(f'error: {error}', file=sys.stderr)
        return 2
    print(f'wrote {target}')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv))
