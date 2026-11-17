#!/usr/bin/env python3
"""Render a SOL-629 run bundle as a self-contained HTML report.

Reads a bundle produced by run-baseline.sh and writes a light-theme HTML page. Re-rendering is
always allowed and never needs the environment, which is the collection-versus-reporting split the
run contract is built on.

Usage:
    render-report.py <bundle-dir> [output.html]    output defaults to <bundle-dir>/report.html
    render-report.py --self-test                  runs test_render.py beside this script

Stdlib only, on purpose: the report must render on any machine that has Python 3.
"""

import json
import math
import pathlib
import subprocess
import sys
from collections import defaultdict
from datetime import datetime, timezone

# ---------------------------------------------------------------------------------------------
# Palette. Light theme only. Values from the validated reference palette; slot 1 and slot 2 pass
# the categorical checks together (CVD delta E 24.7, normal-vision 33.6, both above 3:1).
# ---------------------------------------------------------------------------------------------

SERIES_1 = '#2a78d6'
SERIES_2 = '#eb6834'
EDGE = '#b5b4ad'
INK = '#0b0b0b'
INK_2 = '#52514e'
MUTED = '#898781'
GRID = '#e1e0d9'
AXIS = '#c3c2b7'

CSS = """
:root{--page:#f6f6f3;--card:#ffffff;--surface:#fcfcfb;--ink:#0b0b0b;--ink2:#52514e;--muted:#898781;
--grid:#e1e0d9;--axis:#c3c2b7;--border:rgba(11,11,11,.10);--s1:#2a78d6;--s2:#eb6834;--edge:#b5b4ad;
--good:#0ca30c;--good-text:#006300;--warn:#fab219;--crit:#d03b3b;--soft:#f0efec;color-scheme:light}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:var(--ink);background:var(--page);
margin:0;padding:24px 16px 64px;line-height:1.5;font-size:14px}
.wrap{max-width:1180px;margin:0 auto}
header.top{margin-bottom:20px}
.eyebrow{font-size:12px;color:var(--ink2);text-transform:uppercase;letter-spacing:.06em;font-weight:600}
h1{font-size:26px;line-height:1.2;margin:4px 0 8px;font-weight:650;overflow-wrap:anywhere}
h2{font-size:19px;margin:0 0 4px;font-weight:650}
h3{font-size:14px;margin:0;font-weight:600;overflow-wrap:anywhere}
.lede{color:var(--ink2);margin:0 0 14px;max-width:820px}
.meta{color:var(--ink2);font-size:13px;display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center}
.chip{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--card);
border-radius:999px;padding:2px 10px;font-size:12.5px;color:var(--ink)}
.chip .dot{width:8px;height:8px;border-radius:50%;background:var(--s1)}
section{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px;margin:16px 0}
.hero{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,2fr);gap:16px;align-items:stretch}
.hero-figure{border-right:1px solid var(--grid);padding-right:16px}
.hero-figure .v{font-size:52px;font-weight:650;line-height:1.05;margin:6px 0 4px}
.hero-figure .l{font-size:13px;color:var(--ink2)}
.tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
.tile{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:10px 12px;min-width:0}
.tile .l{font-size:12px;color:var(--ink2)}
.tile .v{font-size:22px;font-weight:620;margin-top:2px;overflow-wrap:anywhere}
.tile .s{font-size:11.5px;color:var(--muted);margin-top:1px}
.status{display:inline-flex;align-items:center;gap:6px;font-weight:600}
.status .ic{display:inline-grid;place-items:center;width:16px;height:16px;border-radius:50%;color:#fff;font-size:11px;line-height:1}
.status.good{color:var(--good-text)} .status.good .ic{background:var(--good)}
.status.crit{color:var(--crit)} .status.crit .ic{background:var(--crit)}
.status.neutral{color:var(--ink2)} .status.neutral .ic{background:var(--muted)}
.note{background:var(--soft);border-radius:8px;padding:10px 14px;margin:12px 0 0;color:var(--ink2);font-size:13px}
.note b{color:var(--ink)}
.note.warn{background:#fff6e0}
.sub{color:var(--muted);font-size:12.5px}
.hbars{display:grid;grid-template-columns:minmax(0,36%) minmax(0,1fr) auto;gap:6px 10px;align-items:center;margin:12px 0 4px}
.hb-l{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hb-t{height:14px;position:relative}
.hb-b{height:14px;background:var(--s1);border-radius:0 4px 4px 0;min-width:2px}
.hb-v{font-size:12.5px;color:var(--ink2);font-variant-numeric:tabular-nums;text-align:right;white-space:nowrap}
.hbars [data-tip]:hover .hb-b{filter:brightness(1.12)}
.stack{display:flex;height:22px;gap:2px;margin:14px 0 8px}
.stack>div{height:22px;min-width:2px}
.stack>div:first-child{border-radius:4px 0 0 4px}.stack>div:last-child{border-radius:0 4px 4px 0}
.legend{display:flex;flex-wrap:wrap;gap:4px 16px;font-size:12.5px;color:var(--ink2)}
.legend i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px;vertical-align:-1px}
.grid2{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,460px),1fr));gap:14px;margin-top:12px}
.card{border:1px solid var(--border);border-radius:10px;padding:12px 12px 6px;background:var(--surface);min-width:0}
.card .pct{font-size:12.5px;color:var(--ink2);margin:2px 0 4px;font-variant-numeric:tabular-nums}
.card .pct b{color:var(--ink);font-weight:620}
svg.hist{display:block;width:100%;height:auto}
svg.hist text{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
svg.hist [data-tip]:hover{filter:brightness(1.12)}
.empty{display:grid;place-items:center;height:120px;color:var(--muted);font-size:13px;border:1px dashed var(--axis);border-radius:8px;margin:8px 0}
.tw{overflow-x:auto;margin-top:12px;border:1px solid var(--border);border-radius:8px}
.tw.tall{max-height:520px;overflow:auto}
table{width:100%;border-collapse:collapse;font-size:12.5px;background:var(--card)}
th,td{padding:7px 10px;border-bottom:1px solid var(--grid);text-align:left;vertical-align:top}
th{background:#f3f3f0;font-weight:600;color:var(--ink2);white-space:nowrap;position:sticky;top:0}
tr:last-child td{border-bottom:0}
td.n,th.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
td.name{overflow-wrap:anywhere;min-width:140px}
tr.total td{font-weight:600;background:#f9f9f7}
details{margin-top:6px}
summary{cursor:pointer;color:var(--ink2);font-size:12.5px;padding:4px 0}
code{background:var(--soft);padding:1px 5px;border-radius:4px;font-size:12px}
.grid3{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,330px),1fr));gap:12px;margin-top:10px}
h3.sp{margin-top:20px}
.footer{margin-top:28px;color:var(--muted);font-size:12px}
#tip{position:fixed;pointer-events:none;background:#fff;border:1px solid var(--border);box-shadow:0 4px 14px rgba(0,0,0,.12);
border-radius:6px;padding:6px 9px;font-size:12.5px;color:var(--ink);max-width:320px;display:none;z-index:10}
@media (max-width:720px){
 body{padding:16px 12px 48px}
 section{padding:14px}
 .hero{grid-template-columns:1fr}
 .hero-figure{border-right:0;border-bottom:1px solid var(--grid);padding:0 0 12px}
 .hero-figure .v{font-size:44px}
 .hbars{grid-template-columns:minmax(0,42%) minmax(0,1fr) auto}
}
"""

TOOLTIP_JS = """
(function(){var t=document.getElementById('tip');if(!t)return;
function show(e){var el=e.target.closest('[data-tip]');if(!el){t.style.display='none';return;}
t.textContent=el.getAttribute('data-tip');t.style.display='block';
var x=e.clientX+14,y=e.clientY+14,w=t.offsetWidth,h=t.offsetHeight;
if(x+w>window.innerWidth-8)x=e.clientX-w-14;if(y+h>window.innerHeight-8)y=e.clientY-h-14;
t.style.left=Math.max(8,x)+'px';t.style.top=Math.max(8,y)+'px';}
document.addEventListener('pointermove',show);
document.addEventListener('pointerleave',function(){t.style.display='none';});
document.addEventListener('scroll',function(){t.style.display='none';},true);})();
"""


# ---------------------------------------------------------------------------------------------
# Formatting: human units everywhere, never a raw 12-digit float.
# ---------------------------------------------------------------------------------------------

def esc(value):
    return (str(value).replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')
            .replace('"', '&quot;').replace("'", '&#39;'))


def _sig(value):
    """Three significant figures, no exponent, trailing zeros dropped."""
    magnitude = abs(value)
    if magnitude >= 100:
        return f"{value:,.0f}"
    text = f"{value:.1f}" if magnitude >= 10 else f"{value:.2f}"
    return text.rstrip('0').rstrip('.') if '.' in text else text


def fmt_ms(ms):
    """A duration given in milliseconds, in the unit that keeps it readable."""
    if ms is None:
        return '-'
    if ms == float('inf'):
        return 'overflow'
    sign = '-' if ms < 0 else ''
    ms = abs(ms)
    if ms == 0:
        return '0 ms'
    if ms < 1:
        return f"{sign}{_sig(ms * 1000)} µs"
    if ms < 1000:
        return f"{sign}{_sig(ms)} ms"
    if ms < 60_000:
        return f"{sign}{_sig(ms / 1000)} s"
    if ms < 3_600_000:
        minutes, seconds = divmod(round(ms / 1000), 60)
        return f"{sign}{minutes} min {seconds} s" if seconds else f"{sign}{minutes} min"
    if ms < 172_800_000:
        hours, minutes = divmod(round(ms / 60_000), 60)
        return f"{sign}{hours} h {minutes} min" if minutes else f"{sign}{hours} h"
    return f"{sign}{_sig(ms / 86_400_000)} days"


def fmt_int(value):
    return '-' if value is None else f"{int(value):,}"


def fmt_num(value):
    """A rate or count that is not necessarily whole: thousands separators, 3 significant figures."""
    if value is None:
        return '-'
    if abs(value) >= 1000:
        return f"{value:,.0f}"
    return _sig(value)


def fmt_pct(value, digits=1):
    return '-' if value is None else f"{value:.{digits}f}%"


def fmt_compact(value):
    if value >= 1_000_000:
        return _sig(value / 1_000_000) + 'M'
    if value >= 1000:
        return _sig(value / 1000) + 'K'
    return _sig(value)


def fmt_bytes(value):
    if value is None:
        return '-'
    if value >= 1024 * 1024:
        return f"{_sig(value / 1024 / 1024)} MB"
    if value >= 1024:
        return f"{_sig(value / 1024)} KB"
    return f"{value:,.0f} B"


def load_jsonl(path):
    rows = []
    if not path.exists():
        return rows
    for line in path.read_text().splitlines():
        line = line.strip()
        if not line:
            continue
        try:
            rows.append(json.loads(line))
        except json.JSONDecodeError:
            continue
    return rows


# ---------------------------------------------------------------------------------------------
# Histograms: label repair, percentiles, and adaptive display bins.
# ---------------------------------------------------------------------------------------------

def _histogram_labels():
    """Map each format 1 bucket label to its true label.

    Mirrors LatencyHistogram: bounds at 0.001 ms times 2 to the power step/16, steps 0 to 640.
    Format 1 stripped trailing zeros from whole-number bounds as well, so a 2840 ms bound was
    labelled le=284. Every format 1 label maps to exactly one true bound, so the repair is lossless.
    """
    def label(bound, strip_integers):
        decimals = 4 if bound < 1 else (2 if bound < 100 else 0)
        text = f"{bound:.{decimals}f}"
        if decimals or strip_integers:
            text = text.rstrip('0').rstrip('.')
        return text

    table = {}
    for step in range(641):
        bound = 0.001 * 2 ** (step / 16)
        table['le=' + label(bound, True)] = 'le=' + label(bound, False)
    return table


FORMAT1_LABELS = _histogram_labels()


def normalize_histogram(histogram):
    """Return a histogram whose bucket labels are true bounds, whatever format it was written in."""
    if not histogram or histogram.get('format', 1) >= 2:
        return histogram
    buckets = {}
    for key, count in (histogram.get('buckets') or {}).items():
        true_key = FORMAT1_LABELS.get(key, key)
        buckets[true_key] = buckets.get(true_key, 0) + count
    return {**histogram, 'buckets': buckets, 'format': 2}


def bucket_sort_key(key):
    """Order le= keys numerically; the overflow bucket always sorts last."""
    raw = key.replace('le=', '')
    if raw == '+Inf':
        return float('inf')
    try:
        return float(raw)
    except ValueError:
        return float('inf')


def percentile_from_buckets(buckets, quantile):
    """Read a percentile off merged bucket counts.

    Each observation sits in exactly one bucket, keyed by the upper bound it did not exceed, so the
    answer is the bound of the bucket the running count crosses. It is therefore an upper bound on
    the true percentile, accurate to the bucket width (about 4.4 percent on this scale). This is the
    only way to get a run-wide percentile at all: per-batch percentiles cannot be averaged.
    """
    total = sum(buckets.values())
    if not total:
        return None

    target = total * quantile
    seen = 0
    for key in sorted(buckets, key=bucket_sort_key):
        seen += buckets[key]
        if seen >= target:
            raw = key.replace('le=', '')
            return float('inf') if raw == '+Inf' else float(raw)

    return None


def merge_buckets(histograms):
    """Sum bucket counts. Callers pass histograms that already went through normalize_histogram()."""
    merged = defaultdict(int)
    for histogram in histograms:
        for key, count in ((histogram or {}).get('buckets') or {}).items():
            merged[key] += count
    return dict(merged)


FINE_BASE, FINE_STEPS = 0.001, 16      # LatencyHistogram: 0.001 ms * 2^(step/16)
COARSE_BASE, COARSE_STEPS = 1.0, 1     # pre-2026-10-22 bundles: one bucket per power of two


def _grid_for(bounds):
    """Pick the grid the stored bounds sit on: (base, steps per octave).

    Bundles from before the 16-steps-per-octave histogram store exact powers of two (le=16384).
    Everything else is mapped onto the fine grid; labels are rounded, so a bound is snapped to the
    nearest step, which is exact for every label LatencyHistogram writes at or above 0.01 ms.
    """
    if bounds and all(abs(math.log2(b) - round(math.log2(b))) < 1e-9 for b in bounds):
        return COARSE_BASE, COARSE_STEPS
    return FINE_BASE, FINE_STEPS


def display_bins(buckets, min_bins=15, max_bins=30, trim=0.005):
    """Re-bin a stored log histogram for display. Pure function; the stored data is never changed.

    The rule:
      1. Place every stored bucket on its grid step: bucket `le=b` covers (step(b) - 1, step(b)].
      2. Trim the tails: the span runs from the bucket holding the `trim` quantile to the bucket
         holding the `1 - trim` quantile of the finite counts. Buckets outside it are summed into
         a "< x" and a "> y" edge bar, so nothing is hidden. `le=+Inf` is its own overflow bar.
      3. Choose the bin width as the smallest power-of-two multiple of the stored step for which the
         span fits in `max_bins`, with bin edges aligned to multiples of that width on the global
         grid. Every display bin is therefore a whole number of stored buckets: no bucket is split
         and no count is interpolated. A wide distribution gets wide bins, a narrow one keeps the
         stored resolution.
      4. A span shorter than `min_bins` at the stored resolution is padded with empty bins on both
         sides, centred on the data, so a narrow distribution is shown in context rather than as
         one fat bar. On the coarse power-of-two grid it is padded by one bin each side only.

    Returns a dict with `bins` (lo, hi, count), `under`, `over` (dicts or None), `overflow` (int),
    `total`, `k` (stored steps per display bin), `steps` (stored steps per octave), `percentiles`.
    """
    finite, overflow = [], 0
    for key, count in (buckets or {}).items():
        if not count:
            continue
        value = bucket_sort_key(key)
        if value == float('inf'):
            overflow += count
        else:
            finite.append((value, count))

    total = overflow + sum(count for _, count in finite)
    result = {'total': total, 'bins': [], 'under': None, 'over': None, 'overflow': overflow,
              'k': None, 'steps': None, 'base': None, 'start': None, 'end': None,
              'percentiles': {q: percentile_from_buckets(buckets, q / 100) for q in (50, 95, 99)}
              if total else {}}
    if not finite:
        return result

    # Bounds at or below zero cannot sit on a log grid. LatencyHistogram never writes one; if a
    # hand-made file does, the count goes to the under bar rather than being lost.
    nonpositive = sum(count for value, count in finite if value <= 0)
    finite = [(value, count) for value, count in finite if value > 0]
    if not finite:
        result['under'] = {'hi': 0.0, 'count': nonpositive}
        return result

    base, steps = _grid_for([value for value, _ in finite])
    by_step = defaultdict(int)
    for value, count in finite:
        by_step[round(math.log2(value / base) * steps)] += count
    ordered = sorted(by_step.items())
    finite_total = sum(count for _, count in ordered)

    low_target, high_target = finite_total * trim, finite_total * (1 - trim)
    seen, first_step, last_step = 0, None, None
    for step, count in ordered:
        seen += count
        if first_step is None and seen > low_target:
            first_step = step
        if last_step is None and seen >= high_target:
            last_step = step
    span_low, span_high = first_step - 1, last_step

    k = 1
    while True:
        start = math.floor(span_low / k) * k
        end = math.ceil(span_high / k) * k
        if (end - start) // k <= max_bins:
            break
        k *= 2

    count_bins = (end - start) // k
    if k == 1:
        target = min_bins if steps == FINE_STEPS else count_bins + 2
        if count_bins < target:
            missing = target - count_bins
            start -= missing // 2 + missing % 2
            end += missing // 2
            count_bins = target

    bins = [0] * count_bins
    under = nonpositive
    over = 0
    for step, count in ordered:
        if step <= start:
            under += count
        elif step > end:
            over += count
        else:
            bins[(step - 1 - start) // k] += count

    def bound(step):
        return base * 2 ** (step / steps)

    result.update({
        'k': k, 'steps': steps, 'base': base, 'start': start, 'end': end,
        'bins': [{'lo': bound(start + i * k), 'hi': bound(start + (i + 1) * k), 'count': c}
                 for i, c in enumerate(bins)],
        'under': {'hi': bound(start), 'count': under} if under else None,
        'over': {'lo': bound(end), 'count': over} if over else None,
    })
    return result


def _nice_max(value):
    if value <= 0:
        return 1
    exponent = 10 ** math.floor(math.log10(value))
    for mantissa in (1, 2, 2.5, 5, 10):
        if mantissa * exponent >= value:
            return mantissa * exponent
    return 10 * exponent


_MINUTE, _HOUR, _DAY = 60_000, 3_600_000, 86_400_000
# Tick candidates per density, densest first. Below a minute they are 1, 2, 5 times a power of ten;
# from a minute up they follow the clock (1, 2, 5, 10, 30 min, 1 h ...) so labels stay round.
_TICK_LEVELS = [
    ((1, 2, 3, 4, 5, 6, 7, 8, 9), [_MINUTE * m for m in (1, 2, 3, 4, 5, 10, 15, 20, 30, 45)]
     + [_HOUR * h for h in (1, 2, 3, 6, 12)] + [_DAY * d for d in (1, 2, 5, 10)]),
    ((1, 2, 5), [_MINUTE * m for m in (1, 2, 5, 10, 30)] + [_HOUR * h for h in (1, 2, 6, 12)]
     + [_DAY * d for d in (1, 2, 5, 10)]),
    ((1,), [_MINUTE, 10 * _MINUTE, _HOUR, 10 * _HOUR, _DAY, 10 * _DAY]),
]


def _log_ticks(lo, hi, wanted=(3, 6)):
    """Round tick values between lo and hi (milliseconds): the densest level with at most 6."""
    if lo <= 0 or hi <= lo:
        return []
    levels = []
    for mantissas, clock in _TICK_LEVELS:
        ticks = []
        decade = math.floor(math.log10(lo)) - 1
        while 10 ** decade <= min(hi, _MINUTE) * 10:
            for mantissa in mantissas:
                value = mantissa * 10 ** decade
                if lo <= value <= hi and value < _MINUTE:
                    ticks.append(value)
            decade += 1
        ticks += [value for value in clock if lo <= value <= hi]
        levels.append(sorted(set(ticks)))
    fitting = [t for t in levels if len(t) <= wanted[1]]
    for ticks in fitting:
        if len(ticks) >= wanted[0]:
            return ticks
    if fitting:
        return max(fitting, key=len)
    sparse = levels[-1]
    while len(sparse) > wanted[1]:
        sparse = sparse[::2]
    return sparse


def _bar_path(x, y, width, height, radius=4.0):
    """A column with a rounded data end and a square foot on the baseline."""
    if height <= 0 or width <= 0:
        return ''
    r = min(radius, width / 2, height)
    base = y + height
    return (f"M{x:.2f},{base:.2f}V{y + r:.2f}Q{x:.2f},{y:.2f} {x + r:.2f},{y:.2f}"
            f"H{x + width - r:.2f}Q{x + width:.2f},{y:.2f} {x + width:.2f},{y + r:.2f}V{base:.2f}Z")


def histogram_svg(buckets, unit_label='observations', fmt=fmt_ms, percentiles=None):
    """One adaptive-bin histogram: percentile line, SVG chart, and a table twin.

    `fmt` formats a value on the x axis (durations by default; memory passes fmt_mb). `percentiles`
    overrides the bucket-read p50/p95/p99 when the exact values are known (raw per-batch values).
    """
    view = display_bins(buckets)
    total = view['total']
    if not total:
        return "<div class='empty'>No samples recorded</div>"

    pct = percentiles or view['percentiles']
    width, height = 480, 206
    left, right, top, bottom = 38, 8, 24, 52
    plot_w, plot_h = width - left - right, height - top - bottom
    base_y = top + plot_h

    slots = []                                      # (kind, label, count, tip)
    if view['under']:
        # An under bar that holds only zeros (a memory increment of exactly 0) is labelled 0.
        under_label = '0' if view['under']['hi'] <= 0 else f"< {fmt(view['under']['hi'])}"
        slots.append(('under', under_label, view['under']['count']))
    edge_after = []
    if view['over']:
        edge_after.append(('over', f"> {fmt(view['over']['lo'])}", view['over']['count']))
    if view['overflow']:
        edge_after.append(('overflow', 'overflow', view['overflow']))

    n_bins = len(view['bins'])
    edge_count = len(slots) + len(edge_after)
    gap = 8
    edge_w = 22
    main_w = plot_w - edge_count * (edge_w + gap) if n_bins else 0
    if not n_bins:
        edge_w = min(80, (plot_w - gap * (edge_count - 1)) / max(edge_count, 1))
    counts = [b['count'] for b in view['bins']] + [s[2] for s in slots + edge_after]
    y_max = _nice_max(max(counts))

    def y_of(count):
        return base_y - plot_h * count / y_max

    out = [f"<svg class='hist' viewBox='0 0 {width} {height}' role='img' "
           f"aria-label='Distribution of {fmt_int(total)} {esc(unit_label)}, p50 {esc(fmt(pct.get(50)))}, "
           f"p95 {esc(fmt(pct.get(95)))}'>"]
    for fraction in (0, 0.5, 1):
        y = base_y - plot_h * fraction
        stroke = AXIS if fraction == 0 else GRID
        out.append(f"<line x1='{left}' x2='{width - right}' y1='{y:.1f}' y2='{y:.1f}' stroke='{stroke}' stroke-width='1'/>")
        out.append(f"<text x='{left - 6}' y='{y + 4:.1f}' text-anchor='end' font-size='12' fill='{MUTED}'>"
                   f"{fmt_compact(y_max * fraction)}</text>")

    def bar(x, w, count, colour, tip):
        h = plot_h * count / y_max
        if count and h < 1.5:
            h = 1.5
        path = _bar_path(x, base_y - h, w, h)
        hit = (f"<rect x='{x - 1:.2f}' y='{top}' width='{w + 2:.2f}' height='{plot_h}' fill='transparent' "
               f"data-tip='{esc(tip)}'/>")
        return (f"<path d='{path}' fill='{colour}'/>" if path else '') + hit

    def share(count):
        return f"{fmt_int(count)} ({100 * count / total:.1f}%)"

    x = left
    positions = {}
    for kind, label, count in slots:
        out.append(bar(x, edge_w, count, EDGE, f"{label}: {share(count)}, trimmed tail"))
        out.append(f"<text x='{x:.1f}' y='{base_y + 32}' text-anchor='start' font-size='12' "
                   f"fill='{INK_2}'>{esc(label)}</text>")
        positions[kind] = x + edge_w / 2
        x += edge_w + gap

    main_x = x
    if n_bins:
        slot_w = main_w / n_bins
        for i, b in enumerate(view['bins']):
            bx = main_x + i * slot_w + 1
            out.append(bar(bx, max(slot_w - 2, 0.8), b['count'], SERIES_1,
                           f"{fmt(b['lo'])} to {fmt(b['hi'])}: {share(b['count'])}"))
        lo_edge, hi_edge = view['bins'][0]['lo'], view['bins'][-1]['hi']
        log_lo, log_hi = math.log(lo_edge), math.log(hi_edge)

        def x_of(value):
            return main_x + main_w * (math.log(value) - log_lo) / (log_hi - log_lo)

        ticks = _log_ticks(lo_edge, hi_edge)
        if len(ticks) < 2:
            ticks = [lo_edge, math.sqrt(lo_edge * hi_edge), hi_edge]
        last_x = -1e9
        for value in ticks:
            tx = x_of(value)
            if tx - last_x < 58:
                continue
            last_x = tx
            anchor = 'end' if tx > width - right - 24 else ('start' if tx < left + 24 else 'middle')
            out.append(f"<line x1='{tx:.1f}' x2='{tx:.1f}' y1='{base_y}' y2='{base_y + 4}' stroke='{AXIS}'/>")
            out.append(f"<text x='{tx:.1f}' y='{base_y + 16}' text-anchor='{anchor}' font-size='12' "
                       f"fill='{MUTED}'>{esc(fmt(value))}</text>")
        x = main_x + main_w + gap
    else:
        x_of = None

    for kind, label, count in edge_after:
        out.append(bar(x, edge_w, count, EDGE,
                       f"{label}: {share(count)}" + (', trimmed tail' if kind == 'over' else ', above the top bound')))
        # Right-hand edge labels end at their bar; with two of them the second drops a row.
        row = base_y + (46 if kind == 'overflow' and view['over'] else 32)
        out.append(f"<text x='{x + edge_w:.1f}' y='{row}' text-anchor='end' font-size='12' "
                   f"fill='{INK_2}'>{esc(label)}</text>")
        positions[kind] = x + edge_w / 2
        x += edge_w + gap

    # Percentile markers, grouped when they land within a few pixels of each other.
    marks = []
    for q in (50, 95, 99):
        value = pct.get(q)
        if value is None:
            continue
        # Classified by grid step, not by float comparison: a percentile is a rounded bucket label
        # (le=60.1 for a 60.08 ms bound), so it can sit a hair outside the edge it belongs to.
        step = (round(math.log2(value / view['base']) * view['steps'])
                if view['base'] and 0 < value < float('inf') else None)
        if value == float('inf'):
            mx = positions.get('overflow')
        elif step is None or not x_of:
            mx = positions.get('under') or positions.get('over') or positions.get('overflow')
        elif step > view['end']:
            mx = positions.get('over', positions.get('overflow'))
        elif step <= view['start']:
            mx = positions.get('under')
        else:
            mx = x_of(view['base'] * 2 ** (step / view['steps']))
        if mx is not None:
            marks.append((mx, q, value))
    # Every marker keeps its own line. Labels are grouped by their drawn extent (estimated width and
    # anchor), not by a fixed distance, so "p95 p99" pinned to the right edge cannot print over "p50".
    def label_width(qs):
        return 7.2 * len(' '.join(f"p{q}" for q in qs))

    def placed(mx, qs):
        w = label_width(qs)
        if mx > width - right - 30:
            return 'end', mx - w, mx
        if mx < left + 20:
            return 'start', mx, mx + w
        return 'middle', mx - w / 2, mx + w / 2

    for mx, q, value in marks:
        out.append(f"<line x1='{mx:.1f}' x2='{mx:.1f}' y1='{top - 4}' y2='{base_y}' stroke='{INK}' "
                   f"stroke-opacity='.6' stroke-width='1'/>")
    groups = [[mx, [q]] for mx, q, _ in marks]
    merged = True
    while merged:
        merged = False
        for i in range(len(groups) - 1):
            if placed(*groups[i])[2] + 4 > placed(*groups[i + 1])[1]:
                xs = groups[i][1] + groups[i + 1][1]
                groups[i] = [(groups[i][0] + groups[i + 1][0]) / 2, xs]
                del groups[i + 1]
                merged = True
                break
    for mx, qs in groups:
        anchor, _, _ = placed(mx, qs)
        out.append(f"<text x='{mx:.1f}' y='{top - 8}' text-anchor='{anchor}' font-size='12' font-weight='600' "
                   f"fill='{INK}'>{' '.join(f'p{q}' for q in qs)}</text>")
    out.append("</svg>")

    ratio = 2 ** (view['k'] / view['steps']) if view['k'] else None
    head = (f"<div class='pct'>p50 <b>{esc(fmt(pct.get(50)))}</b> &middot; p95 <b>{esc(fmt(pct.get(95)))}</b>"
            f" &middot; p99 <b>{esc(fmt(pct.get(99)))}</b></div>")
    rows = []
    if view['under']:
        rows.append(('exactly 0' if view['under']['hi'] <= 0 else f"below {fmt(view['under']['hi'])}",
                     view['under']['count']))
    rows += [(f"{fmt(b['lo'])} to {fmt(b['hi'])}", b['count']) for b in view['bins'] if b['count']]
    if view['over']:
        rows.append((f"above {fmt(view['over']['lo'])}", view['over']['count']))
    if view['overflow']:
        rows.append(('overflow (above the top bound)', view['overflow']))
    caption = (f"{n_bins} bins, each {ratio:.3g}x the width of the one before" if ratio else 'edge bars only')
    table = ("<details><summary>Bins as a table &middot; " + esc(caption) + "</summary><div class='tw'><table>"
             "<tr><th>range</th><th class='n'>count</th><th class='n'>share</th></tr>"
             + ''.join(f"<tr><td>{esc(r)}</td><td class='n'>{fmt_int(c)}</td>"
                       f"<td class='n'>{100 * c / total:.1f}%</td></tr>" for r, c in rows)
             + "</table></div></details>")
    return head + '\n'.join(out) + table


def hist_card(title, subtitle, buckets, unit_label, fmt=fmt_ms, percentiles=None):
    return (f"<div class='card'><h3>{esc(title)}</h3><div class='sub'>{esc(subtitle)}</div>"
            f"{histogram_svg(buckets, unit_label, fmt, percentiles)}</div>")


# ---------------------------------------------------------------------------------------------
# Small building blocks.
# ---------------------------------------------------------------------------------------------

def tile(label, value, sub=''):
    return (f"<div class='tile'><div class='l'>{esc(label)}</div><div class='v'>{value}</div>"
            f"<div class='s'>{esc(sub)}</div></div>")


def hbars(rows, colour=None):
    """Horizontal bars in plain HTML, so labels wrap and stay legible at phone width.

    rows: (label, value, value_text, tip). One series, so one colour and no legend box.
    """
    if not rows:
        return ''
    top = max(r[1] for r in rows) or 1
    fill = f"background:{colour};" if colour else ''
    out = ["<div class='hbars'>"]
    for label, value, text, tip in rows:
        width = max(0.4, 100 * value / top)
        out.append(f"<div class='hb-l' title='{esc(label)}'>{esc(label)}</div>"
                   f"<div class='hb-t' data-tip='{esc(tip)}'><div class='hb-b' style='{fill}width:{width:.2f}%'></div></div>"
                   f"<div class='hb-v'>{esc(text)}</div>")
    out.append("</div>")
    return ''.join(out)


def table(head, rows, total=None):
    """head: list of (label, numeric). rows: list of cell lists already escaped.

    Long tables scroll inside their own box with a sticky header, so detail never buries the charts.
    """
    out = [f"<div class='tw{' tall' if len(rows) > 16 else ''}'><table><tr>"]
    out += [f"<th class='n'>{esc(h)}</th>" if numeric else f"<th>{esc(h)}</th>" for h, numeric in head]
    out.append("</tr>")
    for row in rows + ([total] if total else []):
        cls = " class='total'" if row is total else ''
        out.append(f"<tr{cls}>" + ''.join(
            f"<td class='n'>{cell}</td>" if numeric else f"<td class='name'>{cell}</td>"
            for cell, (_, numeric) in zip(row, head)) + "</tr>")
    out.append("</table></div>")
    return ''.join(out)


def section(title, lede, body):
    lede_html = f"<p class='lede'>{lede}</p>" if lede else ''
    return f"<section><h2>{esc(title)}</h2>{lede_html}{body}</section>"


# ---------------------------------------------------------------------------------------------
# Sections.
# ---------------------------------------------------------------------------------------------

def aggregate_queues(runs):
    grouped = defaultdict(lambda: {
        'batches': 0, 'messages': 0, 'processed': 0, 'failed': 0, 'configured': 0,
        'ms_process': 0.0, 'ms_total': 0.0, 'bytes': 0, 'peak_mb': 0.0,
        'per_msg': [], 'buckets': defaultdict(int),
    })
    for run in runs:
        bucket = grouped[run['queue']]
        bucket['batches'] += 1
        bucket['messages'] += run.get('chunk_received', 0)
        bucket['processed'] += run.get('processed', 0)
        bucket['failed'] += run.get('failed', 0)
        bucket['configured'] += run.get('chunk_configured', 0)
        bucket['ms_process'] += run.get('ms_process', 0.0)
        bucket['ms_total'] += run.get('ms_total', 0.0)
        bucket['bytes'] += run.get('bytes', 0)
        bucket['peak_mb'] = max(bucket['peak_mb'], run.get('peak_mb', 0.0))
        if run.get('ms_per_msg'):
            bucket['per_msg'].append(run['ms_per_msg'])
        for key, count in ((run.get('age_hist') or {}).get('buckets') or {}).items():
            bucket['buckets'][key] += count
    return grouped


def render_queues(runs):
    """Aggregate every batch into one row per queue.

    Deliberately replaces a per-batch table. A run emits one record per batch and queues are polled
    continuously, so the per-batch view is mostly rows describing the same queue, and its length
    grows with run duration rather than with anything the reader wants to know.
    """
    grouped = aggregate_queues(runs)
    if not grouped:
        return ''

    ranked = sorted(grouped.items(), key=lambda kv: -kv[1]['ms_process'])
    chart = hbars([(queue, b['ms_process'], fmt_ms(b['ms_process']),
                    f"{queue}: {fmt_ms(b['ms_process'])} processing, {fmt_int(b['messages'])} messages")
                   for queue, b in ranked[:12] if b['ms_process'] > 0])

    rows = []
    for queue, b in sorted(grouped.items(), key=lambda kv: -kv[1]['messages']):
        messages = b['messages']
        # Total cost over total messages, never the mean of the per-batch rates: batches differ in
        # size by orders of magnitude, so averaging the averages weights a 2-message batch like a
        # 10000-message one.
        ms_per_msg = (b['ms_process'] / messages) if messages else 0.0
        msg_per_sec = (messages / (b['ms_process'] / 1000)) if b['ms_process'] else 0.0
        bytes_per_msg = (b['bytes'] / messages) if messages else 0.0
        fill = (messages / b['configured']) if b['configured'] else None

        spread = '-'
        if len(b['per_msg']) > 1:
            low, high = min(b['per_msg']), max(b['per_msg'])
            factor = (high / low) if low else 0
            spread = f"{esc(fmt_ms(low))} to {esc(fmt_ms(high))}"
            if factor >= 10:
                spread += f" <b>({factor:,.0f}x)</b>"

        p95 = percentile_from_buckets(b['buckets'], 0.95)
        failed = f"<b>{fmt_int(b['failed'])}</b>" if b['failed'] else '0'
        rows.append([esc(queue), fmt_int(b['batches']), fmt_int(messages),
                     '-' if fill is None else f"{fill:.2f}", failed,
                     esc(fmt_ms(b['ms_process'])), esc(fmt_ms(ms_per_msg)), spread,
                     fmt_num(msg_per_sec), esc(fmt_bytes(bytes_per_msg)), esc(fmt_ms(p95))])

    head = [('queue', False), ('batches', True), ('messages', True), ('fill', True), ('failed', True),
            ('processing', True), ('per message', True), ('per message, cheapest to dearest batch', True),
            ('msg/s', True), ('size/msg', True), ('age p95', True)]
    note = ("<div class='note'><b>Reading this.</b> <code>per message</code> is total processing time over "
            "total messages, not the mean of the per-batch rates, because batch sizes differ by orders of "
            "magnitude. The spread column is the cheapest and the dearest batch on that queue: where it spans "
            "10x or more the queue average describes no batch that actually ran, and the listener section "
            "below is where the cost lives. <code>fill</code> is messages received over chunk size configured; "
            "below 1 the run paid per-chunk overhead for fewer messages. <code>age p95</code> is enqueue to "
            "consumption, read off merged buckets, so it includes queue wait and is an upper bound accurate "
            "to the bucket width.</div>")
    return (f"<h3>Processing time by queue</h3><div class='sub'>Top {min(12, len(ranked))} of {len(grouped)} "
            f"queues by time spent processing</div>{chart}" + table(head, rows) + note)


def render_histograms(runs):
    """Merge the per-batch age histograms into one distribution per queue.

    This is the whole reason histograms are emitted rather than percentiles: bucket counts sum,
    percentiles do not, so a run-wide figure is only obtainable this way.
    """
    merged = defaultdict(list)
    for run in runs:
        if (run.get('age_hist') or {}).get('buckets'):
            merged[run['queue']].append(run['age_hist'])
    merged = {queue: merge_buckets(hists) for queue, hists in merged.items()}
    merged = {queue: b for queue, b in merged.items() if sum(b.values())}
    if not merged:
        return ''

    ranked = sorted(merged.items(), key=lambda kv: -sum(kv[1].values()))
    cards = [hist_card(queue, f"{fmt_int(sum(b.values()))} messages", b, 'messages')
             for queue, b in ranked[:10]]
    more = (f"<div class='sub' style='margin-top:10px'>Showing the {min(10, len(ranked))} busiest of "
            f"{len(ranked)} queues with age data.</div>")
    lede = ("Time from enqueue to consumption, so queue wait is included. Per-batch percentiles cannot be "
            "combined, but bucket counts can be summed, so each chart is every batch on that queue merged. "
            "Bins are re-cut for display around where the messages are; grey bars at the edges hold the "
            "trimmed tails and the overflow, so every message is counted. Hover a bar for its range.")
    return section('Message age, enqueue to consumption', lede, f"<div class='grid2'>{''.join(cards)}</div>{more}")


def render_listeners(listeners):
    """Per-listener, per-event cost. Answers whether one queue carries even or uneven work."""
    grouped = defaultdict(lambda: {'calls': 0, 'messages': 0, 'ms': 0.0, 'queue': '', 'hists': []})
    for row in listeners:
        key = (row.get('listener', '?'), row.get('event', '?'))
        bucket = grouped[key]
        bucket['calls'] += row.get('calls', 0)
        bucket['messages'] += row.get('messages', 0)
        bucket['ms'] += row.get('ms', 0.0)
        bucket['queue'] = row.get('queue', '')
        if row.get('ms_per_msg_hist'):
            bucket['hists'].append(row['ms_per_msg_hist'])

    if not grouped:
        return ''

    total_ms = sum(b['ms'] for b in grouped.values()) or 1.0
    total_msgs = sum(b['messages'] for b in grouped.values()) or 1

    rows = []
    for (listener, event), bucket in grouped.items():
        per_msg = bucket['ms'] / bucket['messages'] if bucket['messages'] else None
        rows.append((listener, event, bucket, per_msg))
    rows.sort(key=lambda r: -(r[3] or 0))

    costs = [r[3] for r in rows if r[3]]
    spread = (max(costs) / min(costs)) if len(costs) > 1 and min(costs) > 0 else None

    body = []
    if spread:
        body.append("<div class='tiles'>"
                    + tile('Spread across listeners', f"{spread:,.0f}x", 'lightest to heaviest per message')
                    + tile('Listener groups', fmt_int(len(rows)), 'distinct listener and event pairs')
                    + tile('Average per message', esc(fmt_ms(total_ms / total_msgs)), 'describes none of them')
                    + tile('Listener time', esc(fmt_ms(total_ms)), f"over {fmt_int(total_msgs)} messages")
                    + "</div>")

    by_time = sorted(rows, key=lambda r: -r[2]['ms'])
    body.append(f"<h3 style='margin-top:16px'>Share of listener time</h3><div class='sub'>Top "
                f"{min(12, len(by_time))} listener and event pairs</div>")
    body.append(hbars([(f"{listener} / {event}", b['ms'], fmt_pct(100 * b['ms'] / total_ms),
                        f"{listener} on {event}: {fmt_ms(b['ms'])}, {fmt_int(b['messages'])} messages, "
                        f"{fmt_ms(per_msg)} per message")
                       for listener, event, b, per_msg in by_time[:12]]))

    with_hist = [(listener, event, b) for listener, event, b, _ in by_time if b['hists']][:6]
    if with_hist:
        cards = [hist_card(f"{listener}", f"{event} \u00b7 {fmt_int(sum(h.get('n', 0) for h in b['hists']))} calls",
                           merge_buckets(b['hists']), 'calls')
                 for listener, event, b in with_hist]
        body.append("<h3 style='margin-top:18px'>Cost per message, per call</h3><div class='sub'>Distribution of "
                    "the per-message cost of each listener call, for the listeners that take the most time</div>")
        body.append(f"<div class='grid2'>{''.join(cards)}</div>")

    head = [('listener', False), ('event', False), ('calls', True), ('messages', True), ('time', True),
            ('per message', True), ('share of time', True)]
    table_rows = [[esc(listener), esc(event), fmt_int(b['calls']), fmt_int(b['messages']), esc(fmt_ms(b['ms'])),
                   esc(fmt_ms(per_msg)), fmt_pct(100 * b['ms'] / total_ms)]
                  for listener, event, b, per_msg in rows]
    body.append("<details><summary>All listener and event pairs, dearest per message first</summary>"
                + table(head, table_rows) + "</details>")
    body.append("<div class='note'><b>Reading this.</b> A queue-level average hides this entirely. Ranking by "
                "message volume and ranking by time spent give different answers, so optimising by volume can "
                "send you at the wrong listener.</div>")
    return section('Cost per listener and event', '', ''.join(body))


def render_cadence(runs):
    """How often each queue actually gets worked on, derived from task-run start times."""
    by_queue = defaultdict(list)
    for run in runs:
        by_queue[run['queue']].append((run['ts'], run.get('ms_total', 0.0)))

    rows = []
    for queue, entries in by_queue.items():
        entries.sort()
        if len(entries) < 2:
            continue
        gaps = [entries[i + 1][0] - entries[i][0] for i in range(len(entries) - 1)]
        gaps.sort()
        span = entries[-1][0] - entries[0][0]
        busy = sum(ms for _, ms in entries) / 1000.0
        rows.append((queue, len(entries), gaps[len(gaps) // 2], gaps[-1],
                     (100 * busy / span) if span > 0 else 0.0))

    if not rows:
        return ''

    rows.sort(key=lambda r: -r[1])
    head = [('queue', False), ('task runs', True), ('median gap', True), ('longest gap', True), ('duty cycle', True)]
    cells = [[esc(q), fmt_int(c), esc(fmt_ms(m * 1000)), esc(fmt_ms(x * 1000)), fmt_pct(d)]
             for q, c, m, x, d in rows[:15]]
    lede = ("A queue can be healthy per batch and still fall behind because the scheduler rarely reaches it. "
            "Derived from task start times, so it needs no extra collection.")
    return section('Queue service cadence', lede, table(head, cells))


def render_backlog(samples):
    """Peak depth, drain rate and projected time to empty, per queue."""
    series = defaultdict(list)
    for sample in samples:
        if 'queue' in sample:
            series[sample['queue']].append(sample)

    rows = []
    for queue, points in series.items():
        points.sort(key=lambda p: p['ts'])
        peak = max(p['depth'] for p in points)
        if peak == 0:
            continue
        span = points[-1]['ts'] - points[0]['ts']
        acked = points[-1]['acked'] - points[0]['acked']
        published = points[-1]['published'] - points[0]['published']
        ack_rate = acked / span if span > 0 else 0.0
        net = (acked - published) / span if span > 0 else 0.0
        tte = (peak / net) if net > 0 else None
        rows.append((queue, peak, points[-1]['depth'], ack_rate, tte))

    if not rows:
        return ''

    rows.sort(key=lambda r: -r[1])
    head = [('queue', False), ('peak depth', True), ('final depth', True), ('ack rate', True),
            ('time to empty at peak', True)]
    cells = [[esc(q), fmt_int(peak), (f"<b>{fmt_int(final)}</b>" if final else '0'), f"{fmt_num(ack)}/s",
              esc(fmt_ms(tte * 1000)) if tte is not None else 'undefined, diverging']
             for q, peak, final, ack, tte in rows[:15]]
    lede = ("Depth is a level, not a rate. Time to empty is depth divided by the net drain rate, and is "
            "undefined while a queue takes in more than it clears. A queue that ends above zero did not "
            "finish inside the window.")
    return section('Backlog and projected time to empty', lede, table(head, cells))


def render_broker(samples):
    first, last, peak = {}, {}, defaultdict(int)
    for s in samples:
        q = s.get('queue')
        if not q:
            continue
        if q not in first:
            first[q] = (s['published'], s['acked'])
        last[q] = (s['published'], s['acked'])
        peak[q] = max(peak[q], s['depth'])
    if not first:
        return ''
    active = [(q, last[q][0] - first[q][0], last[q][1] - first[q][1], peak[q])
              for q in first if last[q][0] - first[q][0] > 0 or peak[q] > 0]
    pub_total = sum(a[1] for a in active)
    tiles = ("<div class='tiles'>" + tile('Published', fmt_int(pub_total), 'messages, in the window')
             + tile('Queues touched', fmt_int(len(active)), f"of {fmt_int(len(first))} declared") + "</div>")
    ranked = sorted(active, key=lambda x: -x[1])
    chart = hbars([(q, p, fmt_int(p), f"{q}: {fmt_int(p)} published, {fmt_int(a)} acked, peak depth {fmt_int(pk)}")
                   for q, p, a, pk in ranked[:12] if p > 0])
    head = [('queue', False), ('published', True), ('acked', True), ('peak depth', True)]
    cells = [[esc(q), fmt_int(p), fmt_int(a), fmt_int(pk)] for q, p, a, pk in ranked[:25]]
    return section('Broker traffic in the measured window', '',
                   tiles + "<h3 style='margin-top:16px'>Messages published by queue</h3>" + chart
                   + "<details><summary>Top 25 queues as a table</summary>" + table(head, cells) + "</details>")


def render_stages(stages):
    total = sum(s for _, s in stages) or 1
    chart = hbars([(name, secs, f"{fmt_ms(secs * 1000)} ({100 * secs / total:.0f}%)",
                    f"{name}: {fmt_ms(secs * 1000)}, {100 * secs / total:.1f}% of the run")
                   for name, secs in stages])
    head = [('stage', False), ('wall clock', True), ('seconds', True), ('share', True)]
    cells = [[esc(name), esc(fmt_ms(secs * 1000)), fmt_int(secs), fmt_pct(100 * secs / total)] for name, secs in stages]
    cells_total = ['total', esc(fmt_ms(total * 1000)), fmt_int(total), '100%']
    return section('Stage timings', "Wall clock per stage of the scripted run, whole seconds as the runner records "
                   "them.", chart + "<details><summary>As a table</summary>" + table(head, cells, cells_total)
                   + "</details>")


def aggregate_imports(records):
    importers = defaultdict(lambda: {'calls': 0, 'imported': 0, 'expected': 0, 'expected_unknown': 0,
                                     'ms_native': 0.0, 'ms_wall': 0.0, 'failed': 0, 'peak_mb': None})
    emit = {'calls': 0, 'ms': 0.0, 'events': 0, 'messages': 0, 'messages_unknown': 0}
    collections, rows = [], defaultdict(list)
    for record in records:
        kind = record.get('kind')
        if kind == 'importer':
            b = importers[record.get('import_type') or '?']
            b['calls'] += 1
            b['imported'] += record.get('imported') or 0
            if record.get('expected') is None:
                b['expected_unknown'] += 1
            else:
                b['expected'] += record['expected']
            b['ms_native'] += record.get('ms_native') or 0.0
            b['ms_wall'] += record.get('ms_wall') or 0.0
            if record.get('is_success') is False:
                b['failed'] += 1
            if record.get('peak_mb') is not None:
                b['peak_mb'] = max(b['peak_mb'] or 0, record['peak_mb'])
        elif kind == 'publish_emit':
            emit['calls'] += 1
            emit['ms'] += record.get('ms') or 0.0
            emit['events'] += record.get('events') or 0
            if record.get('messages') is None:
                emit['messages_unknown'] += 1
            else:
                emit['messages'] += record['messages']
        elif kind == 'collection':
            collections.append(record)
        elif kind == 'rows' and record.get('hist'):
            rows[record.get('import_type') or '?'].append(normalize_histogram(record['hist']))
    return importers, emit, collections, rows


def emission_split(records):
    """Split publish emission time into flushes inside an importer span and flushes outside any.

    Uses the record's own `importer` field when the writer provides it (null means outside), and
    otherwise whether the flush started inside an importer span of the same process.
    """
    spans = defaultdict(list)
    for r in records:
        if r.get('kind') == 'importer' and r.get('ts') is not None:
            spans[r.get('pid')].append((r['ts'], r['ts'] + (r.get('ms_wall') or 0) / 1000))
    inside = outside = 0.0
    for r in records:
        if r.get('kind') != 'publish_emit':
            continue
        if 'importer' in r:
            within = r['importer'] is not None
        else:
            ts = r.get('ts')
            within = ts is not None and any(start <= ts <= end for start, end in spans[r.get('pid')])
        if within:
            inside += r.get('ms') or 0.0
        else:
            outside += r.get('ms') or 0.0
    return inside, outside


def render_imports(records, stage_seconds):
    """Import section. Absent, not empty, when the bundle holds no C1 importer records."""
    importers, emit, collections, row_hists = aggregate_imports(records)
    if not importers:
        return ''

    sum_native = sum(b['ms_native'] for b in importers.values())
    sum_wall = sum(b['ms_wall'] for b in importers.values())
    imported = sum(b['imported'] for b in importers.values())
    failed = sum(b['failed'] for b in importers.values())
    stage_ms = stage_seconds * 1000 if stage_seconds is not None else None
    stage_name = 'Import stage wall clock'
    from_stage = stage_ms is not None
    if stage_ms is None:
        # No runner stage timing (a bundle captured by hand): use the extent of every timed import
        # record, so emission or collection hooks after the last importer still count as outside it.
        spans = [(r['ts'], r['ts'] + (r.get('ms_wall') if r.get('ms_wall') is not None else r.get('ms') or 0) / 1000)
                 for r in records if r.get('ts') is not None and r.get('kind') in ('importer', 'collection', 'publish_emit')]
        if spans:
            stage_ms = 1000 * (max(end for _, end in spans) - min(start for start, _ in spans))
            stage_name = 'Import span, first record start to last record end'
    unattributed = (stage_ms - sum_wall) if stage_ms is not None else None

    runs_total = sum(b['calls'] for b in importers.values())
    body = ["<div class='tiles'>"]
    body.append(tile('Rows imported', fmt_int(imported), f"{fmt_int(len(importers))} import type{'s' if len(importers) != 1 else ''}, "
                     f"{fmt_int(runs_total)} importer run{'s' if runs_total != 1 else ''}"))
    body.append(tile('Rows per second', fmt_num(imported / (sum_native / 1000)) if sum_native else '-',
                     'over native import time'))
    body.append(tile('Native import time', esc(fmt_ms(sum_native)), 'sum of importTime'))
    if unattributed is not None and stage_ms:
        body.append(tile('Unattributed', esc(fmt_ms(unattributed)), f"{100 * unattributed / stage_ms:.0f}% of the stage"))
    status = ("<span class='status good'><span class='ic'>&#10003;</span>none</span>" if not failed else
              f"<span class='status crit'><span class='ic'>!</span>{fmt_int(failed)}</span>")
    body.append(tile('Failed importer runs', status, 'is_success false'))
    body.append("</div>")

    # Reconciliation: stage wall clock against what the hooks saw.
    if stage_ms:
        parts = [('Native import time', sum_native, SERIES_1),
                 ('After-import hooks and wrapper', max(sum_wall - sum_native, 0.0), SERIES_2),
                 ('Unattributed', max(unattributed, 0.0), EDGE)]
        scale = max(stage_ms, sum_wall) or 1
        bar = ''.join(f"<div style='flex:{100 * v / scale:.3f} 0 0;background:{c}' "
                      f"data-tip='{esc(name)}: {esc(fmt_ms(v))} ({100 * v / scale:.1f}%)'></div>"
                      for name, v, c in parts if v > 0)
        legend = ''.join(f"<span><i style='background:{c}'></i>{esc(name)} <b>{esc(fmt_ms(v))}</b></span>"
                         for name, v, c in parts)
        recon_rows = [[stage_name, esc(fmt_ms(stage_ms)), '100%'],
                      ['Summed importer wall time (<code>ms_wall</code>)', esc(fmt_ms(sum_wall)), fmt_pct(100 * sum_wall / stage_ms)],
                      ['&nbsp;&nbsp;of which native import time (<code>ms_native</code>)', esc(fmt_ms(sum_native)), fmt_pct(100 * sum_native / stage_ms)],
                      ['&nbsp;&nbsp;of which after-import hooks and wrapper', esc(fmt_ms(sum_wall - sum_native)), fmt_pct(100 * (sum_wall - sum_native) / stage_ms)],
                      [f'Unattributed: {stage_name.split(",")[0].lower()} minus summed importer wall time', esc(fmt_ms(unattributed)), fmt_pct(100 * unattributed / stage_ms)]]
        body.append("<h3 style='margin-top:18px'>Where the import stage went</h3>"
                    f"<div class='stack'>{bar}</div><div class='legend'>{legend}</div>")
        body.append(table([('', False), ('time', True), ('share of stage', True)], recon_rows))
        warn = ''
        if -1000 <= unattributed < 0 and from_stage:
            warn = (" The summed importer time exceeds the stage wall clock by less than that resolution here; "
                    "treat the remainder as zero.")
        elif unattributed < -1000 or (unattributed < 0 and not from_stage):
            warn = (" <b>The summed importer time exceeds the stage wall clock by more than the 1 s "
                    "resolution</b>, so importer spans overlap (nested or parallel importers) or the stage "
                    "timing is wrong. Check the bundle before trusting this split.")
        basis = ("The stage wall clock has whole-second resolution." if from_stage else
                 "This bundle has no stage timing, so the span runs from the first import record to the end of "
                 "the last; console bootstrap before it is not visible.")
        body.append("<div class='note'><b>Reading this.</b> Unattributed time is console bootstrap, configuration "
                    "parsing, collection after-import hooks and anything between importers. " + basis + warn + "</div>")

    # Per importer type.
    ranked = sorted(importers.items(), key=lambda kv: -kv[1]['ms_wall'])
    body.append(f"<h3 style='margin-top:18px'>Time by import type</h3><div class='sub'>Top {min(15, len(ranked))} "
                f"of {len(ranked)} by importer wall time</div>")
    body.append(hbars([(t, b['ms_wall'], fmt_ms(b['ms_wall']),
                        f"{t}: {fmt_ms(b['ms_wall'])} wall, {fmt_ms(b['ms_native'])} native, {fmt_int(b['imported'])} rows")
                       for t, b in ranked[:15]]))
    head = [('import type', False), ('runs', True), ('rows', True), ('expected', True), ('native', True),
            ('wall', True), ('rows/s', True), ('share of native', True), ('peak memory', True), ('failed', True)]
    cells = []
    for t, b in ranked:
        rate = b['imported'] / (b['ms_native'] / 1000) if b['ms_native'] else None
        expected = fmt_int(b['expected']) if not b['expected_unknown'] else (
            'unknown' if b['expected_unknown'] == b['calls'] else f"{fmt_int(b['expected'])}+")
        cells.append([esc(t), fmt_int(b['calls']), fmt_int(b['imported']), expected, esc(fmt_ms(b['ms_native'])),
                      esc(fmt_ms(b['ms_wall'])), fmt_num(rate),
                      fmt_pct(100 * b['ms_native'] / sum_native if sum_native else None),
                      '-' if b['peak_mb'] is None else f"{fmt_num(b['peak_mb'])} MB",
                      f"<b>{b['failed']}</b>" if b['failed'] else '0'])
    rates = [b['imported'] / (b['ms_native'] / 1000) for b in importers.values() if b['ms_native'] and b['imported']]
    if len(rates) > 1:
        body.append(f"<div class='sub'>Rows per second range from {fmt_num(min(rates))} to {fmt_num(max(rates))}, "
                    f"a {max(rates) / min(rates):,.0f}x spread.</div>")
    body.append(table(head, cells))

    # Publish emission.
    if emit['calls']:
        messages_sub = 'enqueued during the flushes'
        if emit['messages_unknown'] == emit['calls']:
            messages = 'not measured'
        else:
            messages = fmt_int(emit['messages'])
            if emit['messages_unknown']:
                messages_sub = (f"measured in {fmt_int(emit['calls'] - emit['messages_unknown'])} of "
                                f"{fmt_int(emit['calls'])} flushes")
        inside_ms, outside_ms = emission_split(records)
        body.append("<h3 style='margin-top:18px'>Publish emission</h3><div class='tiles'>"
                    + tile('Emission time', esc(fmt_ms(emit['ms'])), f"{fmt_int(emit['calls'])} flushes")
                    + tile('Inside importers', esc(fmt_ms(inside_ms)), 'part of importer wall time')
                    + tile('Outside importers', esc(fmt_ms(outside_ms)), 'part of the unattributed time')
                    + tile('Entity events', fmt_int(emit['events']), 'flushed to the event queue')
                    + tile('Queue messages', messages, messages_sub)
                    + "</div><div class='note'><b>Not additive.</b> Events are flushed when the buffer fills during "
                      "data set steps and from after-import hooks. A flush inside an importer is already in that "
                      "importer's wall time; one after it (a collection hook, or the console's final flush) is in "
                      "the unattributed remainder. Neither adds to the totals above.</div>")

    if collections:
        coll = defaultdict(lambda: [0, 0.0])
        for r in collections:
            coll[r.get('import_type') or '?'][0] += 1
            coll[r.get('import_type') or '?'][1] += r.get('ms_wall') or 0.0
        body.append("<details><summary>Collection calls</summary>" + table(
            [('requested type', False), ('calls', True), ('wall', True)],
            [[esc(t), fmt_int(c), esc(fmt_ms(ms))] for t, (c, ms) in sorted(coll.items(), key=lambda kv: -kv[1][1])])
            + "</details>")

    if row_hists:
        merged = sorted(((t, merge_buckets(h)) for t, h in row_hists.items()), key=lambda kv: -sum(kv[1].values()))
        cards = [hist_card(t, f"{fmt_int(sum(b.values()))} data sets", b, 'data sets') for t, b in merged[:8]]
        body.append("<h3 style='margin-top:18px'>Time per data set</h3><div class='sub'>Every step of the data set "
                    f"broker for one row, merged per import type</div><div class='grid2'>{''.join(cards)}</div>")

    lede = ("Per import type, aggregated across every run of that importer. Native time is the importer's own "
            "<code>importTime</code> at full precision; wall time adds its after-import hooks.")
    return section('Import', lede, ''.join(body))


# ---------------------------------------------------------------------------------------------
# Memory and weighted cost (contract C7). Shared by this report and render-campaign.py.
#
# Absent fields are "not measured", never 0. Weighted figures are total over total (sum of the
# cost over sum of the messages), never a mean of per-batch rates, for the same reason as the
# per-message time above: batch sizes differ by orders of magnitude.
# ---------------------------------------------------------------------------------------------

NOT_MEASURED = 'not measured'
NOT_ENOUGH_BATCHES = 'not enough batches'
FIT_SIZES = (200, 1000)
C7_HINT = ('needs <code>mem_start_mb</code> or <code>mem_peak_incr_mb</code> on the record (contract C7), '
           'which these runs predate')


def na(value, formatter):
    """Format a value that may not have been measured; None renders as 'not measured', never as 0."""
    return NOT_MEASURED if value is None else formatter(value)


def fmt_mb(mb):
    """Memory given in MB (MiB, as PHP reports it), in the unit that keeps it readable."""
    if mb is None:
        return '-'
    if mb == float('inf'):
        return 'overflow'
    sign = '-' if mb < 0 else ''
    mb = abs(mb)
    if mb == 0:
        return '0 MB'
    if mb < 1 / 1024:
        return f"{sign}{_sig(mb * 1024 * 1024)} B"
    if mb < 1:
        return f"{sign}{_sig(mb * 1024)} KB"
    if mb < 1024:
        return f"{sign}{_sig(mb)} MB"
    return f"{sign}{_sig(mb / 1024)} GB"


def batches(n):
    return f"{fmt_int(n)} batch{'' if n == 1 else 'es'}"


def fmt_kb(kb):
    return '-' if kb is None else fmt_mb(kb / 1024)


def histogram_from_values(values):
    """Bucket raw values onto the LatencyHistogram grid (format 2 labels), so the shared display
    binning and percentile code apply to records that carry one value each (a flush duration, a
    batch's peak memory). A value of exactly 0 or below gets its own `le=0` bucket, so a memory
    increment of 0 is shown as 0 rather than as the smallest bound."""
    buckets = defaultdict(int)
    for value in values:
        if value is None:
            continue
        if value <= 0:
            buckets['le=0'] += 1
            continue
        step = 0 if value <= 0.001 else math.ceil(math.log2(value / 0.001) * 16 - 1e-9)
        if step > 640:
            buckets['le=+Inf'] += 1
            continue
        bound = 0.001 * 2 ** (step / 16)
        decimals = 4 if bound < 1 else (2 if bound < 100 else 0)
        text = f"{bound:.{decimals}f}"
        if decimals:
            text = text.rstrip('0').rstrip('.')
        buckets['le=' + text] += 1
    return dict(buckets)


def join_flushes(tasks, flushes):
    """Attach direct-sync flush records (C6) to queues by pid.

    One `queue:task:start` process runs one task and flushes once at console terminate, so the pid
    of a flush names the task run it belongs to. The pid join is authoritative; the record's own
    `queue` field is the fallback for a flush whose process left no task run (an empty poll).
    Call it per run: pids repeat across runs. Returns ({queue: [flush]}, {context: [flush]} for
    flushes outside any queue).
    """
    pid_queue = {}
    for task in tasks:
        pid_queue.setdefault(task.get('pid'), task['queue'])
    by_queue, elsewhere = defaultdict(list), defaultdict(list)
    for flush in flushes:
        queue = pid_queue.get(flush.get('pid'))
        if queue is None and flush.get('context') == 'queue':
            queue = flush.get('queue')
        if queue is not None:
            by_queue[queue].append(flush)
        else:
            elsewhere[flush.get('context') or 'other'].append(flush)
    return dict(by_queue), dict(elsewhere)


def value_percentiles(values):
    """Exact nearest-rank p50, p95, p99 and max of raw values; None when there is no value."""
    ordered = sorted(v for v in values if v is not None)
    if not ordered:
        return None

    def rank(q):
        return ordered[max(0, math.ceil(q * len(ordered)) - 1)]

    return {50: rank(0.50), 95: rank(0.95), 99: rank(0.99), 'max': ordered[-1], 'n': len(ordered)}


def linear_fit(points):
    """Least-squares fit of cost = fixed + marginal x messages over (messages, cost) batches.

    Returns {'enough': False} under 3 batches or with a single distinct batch size: a line through
    one size cannot separate the per-batch cost from the per-message cost. R squared is 1 - SSres /
    SStot; with every cost equal it is 1 when the line hits them all and None otherwise.
    """
    points = [(float(x), float(y)) for x, y in points if x is not None and y is not None]
    n = len(points)
    sizes = len({x for x, _ in points})
    if n < 3 or sizes < 2:
        return {'enough': False, 'n': n, 'sizes': sizes}
    mean_x = sum(x for x, _ in points) / n
    mean_y = sum(y for _, y in points) / n
    sxx = sum((x - mean_x) ** 2 for x, _ in points)
    sxy = sum((x - mean_x) * (y - mean_y) for x, y in points)
    marginal = sxy / sxx
    fixed = mean_y - marginal * mean_x
    ss_res = sum((y - fixed - marginal * x) ** 2 for x, y in points)
    ss_tot = sum((y - mean_y) ** 2 for _, y in points)
    if ss_tot > 0:
        r2 = 1 - ss_res / ss_tot
    else:
        r2 = 1.0 if ss_res <= 1e-12 else None
    return {'enough': True, 'n': n, 'sizes': sizes, 'fixed': fixed, 'marginal': marginal, 'r2': r2}


def fit_predict(fit, messages):
    return fit['fixed'] + fit['marginal'] * messages if fit.get('enough') else None


def fixed_share(fit, messages):
    """Share of a batch's predicted cost that is fixed. None when the fit is missing, the fixed
    part is negative (the line does not describe small batches) or the prediction is not positive."""
    predicted = fit_predict(fit, messages)
    if predicted is None or predicted <= 0 or fit['fixed'] < 0:
        return None
    return fit['fixed'] / predicted


def batch_increment(record):
    """Memory a batch (or flush) added: `mem_peak_incr_mb`, else `peak_mb - mem_start_mb`.

    None when neither is recorded. `peak_mb` alone is never used: it includes the process baseline
    (framework bootstrap, container, autoloader), so it says nothing about the batch's own cost.
    """
    value = record.get('mem_peak_incr_mb')
    if value is not None:
        return value
    peak, start = record.get('peak_mb'), record.get('mem_start_mb')
    if peak is not None and start is not None:
        return peak - start
    return None


def memory_profile(records, size_field):
    """Memory of a group of batches (task runs: size_field 'chunk_received'; flushes: 'messages').

    Absolute: the distribution of `peak_mb` over batches. Weighted: memory added per message and
    per 100 messages, total over total, from batch increments only, over batches whose size is
    known and above 0. `peak_mb` is never divided by messages (see batch_increment).
    """
    increments = [(batch_increment(r), r.get(size_field)) for r in records]
    measured = [(inc, size) for inc, size in increments if inc is not None]
    sized = [(inc, size) for inc, size in measured if size]
    added, messages = sum(inc for inc, _ in sized), sum(size for _, size in sized)
    per_message = added / messages if sized and messages else None
    return {
        'batches': len(records),
        'peak': value_percentiles(r.get('peak_mb') for r in records),
        'increment': value_percentiles(inc for inc, _ in measured),
        'per_message': per_message,
        'per_100': None if per_message is None else 100 * per_message,
        'per_100_batches': value_percentiles(100 * inc / size for inc, size in sized),
        'fit': linear_fit((size, inc) for inc, size in sized),
    }


def queue_cost_models(tasks):
    """Per queue: totals, time per 1 000 messages, memory profile and the three linear fits."""
    grouped = defaultdict(list)
    for task in tasks:
        grouped[task['queue']].append(task)
    out = {}
    for queue, runs in grouped.items():
        messages = sum(r.get('chunk_received') or 0 for r in runs)
        ms_process = sum(r.get('ms_process') or 0.0 for r in runs)
        ms_total = sum(r.get('ms_total') or 0.0 for r in runs)
        out[queue] = {
            'batches': len(runs), 'messages': messages, 'ms_process': ms_process, 'ms_total': ms_total,
            'sizes': len({r.get('chunk_received') for r in runs}),
            'process_per_1000': 1000 * ms_process / messages if messages else None,
            'total_per_1000': 1000 * ms_total / messages if messages else None,
            'fit_process': linear_fit((r.get('chunk_received'), r.get('ms_process')) for r in runs),
            'fit_total': linear_fit((r.get('chunk_received'), r.get('ms_total')) for r in runs),
            'memory': memory_profile(runs, 'chunk_received'),
            'points_total': [(r.get('chunk_received'), r.get('ms_total')) for r in runs],
            'points_peak': [(r.get('chunk_received'), r.get('peak_mb')) for r in runs],
            'points_increment': [(r.get('chunk_received'), batch_increment(r)) for r in runs],
        }
    return out


def listener_cost_models(listeners):
    """Per listener and event: time per 1 000 messages and the C7 memory figures.

    A listener record's `mem_peak_incr_mb` is the largest call increment in that record, so it is
    summarised as a distribution over records, never summed. The per-message figure comes from
    `mem_peak_incr_hist` (KB per message, one observation per call), read off the merged buckets.
    """
    grouped = defaultdict(list)
    for row in listeners:
        grouped[(row.get('listener', '?'), row.get('event', '?'))].append(row)
    out = {}
    for key, rows in grouped.items():
        messages = sum(r.get('messages') or 0 for r in rows)
        ms = sum(r.get('ms') or 0.0 for r in rows)
        hists = [normalize_histogram(r['mem_peak_incr_hist']) for r in rows if r.get('mem_peak_incr_hist')]
        kb_hist = merge_buckets(hists)
        kb_n = sum(kb_hist.values())
        kb_p = {q: percentile_from_buckets(kb_hist, q / 100) for q in (50, 95, 99)} if kb_n else None
        out[key] = {
            'queue': next((r.get('queue') for r in rows if r.get('queue')), ''),
            'records': len(rows), 'calls': sum(r.get('calls') or 0 for r in rows), 'messages': messages, 'ms': ms,
            'per_1000': 1000 * ms / messages if messages else None,
            'increment': value_percentiles(r.get('mem_peak_incr_mb') for r in rows),
            'kb_hist': kb_hist if kb_n else None, 'kb_pct': kb_p,
            'per_100_p50': kb_p[50] * 100 / 1024 if kb_p and kb_p[50] is not None else None,
            'per_100_p95': kb_p[95] * 100 / 1024 if kb_p and kb_p[95] is not None else None,
        }
    return out


# ---- chart: small-multiple scatter of a cost against batch size, log x, optional fitted line ----

def _decade_ticks(lo, hi):
    ticks = []
    decade = math.floor(math.log10(lo))
    while 10 ** decade <= hi:
        for mantissa in (1, 2, 5):
            value = mantissa * 10 ** decade
            if lo <= value <= hi:
                ticks.append(value)
        decade += 1
    if len(ticks) > 6:
        ticks = [t for t in ticks if math.log10(t) == int(math.log10(t))] or ticks[::2]
    return ticks


def scatter_panel(title, sub, points, x_range, y_max, fmt_y, fit=None):
    """One panel: batches as dots (size on a log x axis, cost on a linear y axis from 0).

    Shared x range (and y range where the caller passes one) across panels, so position compares
    between queues. With `fit`, the fitted line is drawn over the batch-size range it was fitted on.
    """
    points = [(x, y) for x, y in points if x and x > 0 and y is not None]
    width, height = 340, 190
    left, right, top, bottom = 50, 12, 12, 30
    pw, ph = width - left - right, height - top - bottom
    lo, hi = x_range
    if hi <= lo:
        lo, hi = lo / 2, hi * 2
    log_lo, log_hi = math.log(lo), math.log(hi)
    y_top = y_max if y_max and y_max > 0 else 1

    def x_of(x):
        return left + pw * (math.log(x) - log_lo) / (log_hi - log_lo)

    def y_of(y):
        return top + ph - ph * max(min(y, y_top), min(0, y)) / y_top

    out = [f"<svg class='hist' viewBox='0 0 {width} {height}' role='img' aria-label='{esc(title)}: "
           f"{fmt_int(len(points))} batches by size'>"]
    for fraction in (0, 0.5, 1):
        y = top + ph - ph * fraction
        out.append(f"<line x1='{left}' x2='{width - right}' y1='{y:.1f}' y2='{y:.1f}' "
                   f"stroke='{AXIS if fraction == 0 else GRID}'/>")
        out.append(f"<text x='{left - 6}' y='{y + 4:.1f}' text-anchor='end' font-size='11.5' fill='{MUTED}'>"
                   f"{esc(fmt_y(y_top * fraction) if fraction else '0')}</text>")
    for tick in _decade_ticks(lo, hi):
        x = x_of(tick)
        anchor = 'start' if x < left + 12 else ('end' if x > width - right - 12 else 'middle')
        out.append(f"<line x1='{x:.1f}' x2='{x:.1f}' y1='{top + ph}' y2='{top + ph + 4}' stroke='{AXIS}'/>")
        out.append(f"<text x='{x:.1f}' y='{top + ph + 16}' text-anchor='{anchor}' font-size='11.5' "
                   f"fill='{MUTED}'>{esc(fmt_compact(tick))}</text>")
    for x, y in points:
        out.append(f"<circle cx='{x_of(x):.1f}' cy='{y_of(y):.1f}' r='4' fill='{SERIES_1}' fill-opacity='.7' "
                   f"stroke='#ffffff' stroke-width='1.5' data-tip='{esc(title)}: {fmt_int(x)} messages, "
                   f"{esc(fmt_y(y))}'/>")
    if fit and fit.get('enough'):
        xs = [x for x, _ in points]
        a, b = min(xs), max(xs)
        steps = [a * (b / a) ** (i / 24) for i in range(25)]
        path = ' '.join(f"{'M' if i == 0 else 'L'}{x_of(x):.1f},{y_of(fit_predict(fit, x)):.1f}"
                        for i, x in enumerate(steps))
        out.append(f"<path d='{path}' fill='none' stroke='{SERIES_2}' stroke-width='2'/>")
    out.append("</svg>")
    return (f"<div class='card'><h3>{esc(title)}</h3><div class='sub'>{sub}</div>{''.join(out)}</div>")


def _shared_x(groups):
    sizes = [x for points in groups for x, _ in points if x and x > 0]
    if not sizes:
        return None
    return 10 ** math.floor(math.log10(min(sizes))), 10 ** math.ceil(math.log10(max(sizes)))


def _fit_cells(fit, fmt_fixed, fmt_marginal_1000):
    """Cells: fixed, marginal per 1 000 messages, R squared and n, then (predicted, fixed share) at
    each FIT_SIZES size. One 'not enough batches' cell spans the meaning of the rest."""
    if not fit.get('enough'):
        why = f"{NOT_ENOUGH_BATCHES} ({fit['n']} batch{'es' if fit['n'] != 1 else ''}, {fit['sizes']} size{'s' if fit['sizes'] != 1 else ''})"
        return [esc(why), '-', '-'] + ['-', '-'] * len(FIT_SIZES)
    r2 = '-' if fit['r2'] is None else f"{fit['r2']:.2f}"
    cells = [esc(fmt_fixed(fit['fixed'])), esc(fmt_marginal_1000(fit['marginal'] * 1000)), f"{r2} &middot; n {fmt_int(fit['n'])}"]
    for size in FIT_SIZES:
        share = fixed_share(fit, size)
        cells += [esc(fmt_fixed(fit_predict(fit, size))),
                  'n/a, fixed &lt; 0' if fit['fixed'] < 0 else fmt_pct(None if share is None else 100 * share, 0)]
    return cells


def _fit_head(first):
    head = [(first, False), ('fixed per batch', True), ('marginal per 1 000 msgs', True), ('R² · batches', True)]
    for size in FIT_SIZES:
        head += [(f"batch of {size:,}".replace(',', ' '), True), ('fixed share', True)]
    return head


def render_weighted_time(tasks, listeners):
    """Weighted time: per 1 000 messages, and the per-queue fit that splits fixed from marginal cost."""
    models = queue_cost_models(tasks)
    if not models:
        return ''
    ranked = sorted(models.items(), key=lambda kv: -kv[1]['ms_total'])
    fitted = [(q, m) for q, m in ranked if m['fit_total']['enough']]
    body = ["<div class='tiles'>"
            + tile('Queues with a fit', f"{len(fitted)} of {len(models)}", 'at least 3 batches and 2 batch sizes')]
    if fitted:
        top_fixed = max(fitted, key=lambda kv: kv[1]['fit_total']['fixed'])
        top_marg = max(fitted, key=lambda kv: kv[1]['fit_total']['marginal'])
        body.append(tile('Largest fixed cost per batch', esc(fmt_ms(top_fixed[1]['fit_total']['fixed'])), top_fixed[0]))
        body.append(tile('Largest marginal cost', esc(fmt_ms(top_marg[1]['fit_total']['marginal'] * 1000)),
                         f"per 1 000 messages, {top_marg[0]}"))
    messages = sum(m['messages'] for m in models.values())
    total = sum(m['ms_total'] for m in models.values())
    if messages:
        body.append(tile('All queues, per 1 000 messages', esc(fmt_ms(1000 * total / messages)),
                         'task-run time over messages, total over total'))
    body.append("</div>")

    body.append("<h3 class='sp' style='margin-top:18px'>Time per 1 000 messages, by queue</h3><div class='sub'>Total time "
                "over total messages, dearest first, among queues that received at least 100 messages (below that "
                "the figure is mostly fixed cost); the bar is task-run time (<code>ms_total</code>).</div>")
    by_rate = sorted(((q, m) for q, m in models.items() if m['total_per_1000'] is not None and m['messages'] >= 100),
                     key=lambda kv: -kv[1]['total_per_1000'])
    body.append(hbars([(q, m['total_per_1000'], fmt_ms(m['total_per_1000']),
                        f"{q}: {fmt_ms(m['total_per_1000'])} per 1 000 messages over {fmt_int(m['messages'])} messages "
                        f"in {batches(m['batches'])}") for q, m in by_rate[:12]]))

    head = _fit_head('queue')
    rows = [[esc(q)] + _fit_cells(m['fit_total'], fmt_ms, fmt_ms) for q, m in fitted]
    body.append("<h3 style='margin-top:18px'>Fixed and marginal cost per batch, task-run time</h3><div class='sub'>"
                "<code>ms_total = fixed + marginal x messages</code>, least squares over every batch of the queue, "
                "busiest queue first. The two predictions compare a batch of 200 with a batch of 1 000 on the same "
                "queue.</div>")
    body.append(table(head, rows) if rows else f"<div class='empty'>{NOT_ENOUGH_BATCHES} on every queue</div>")
    body.append(_unfitted_note([(q, m['fit_total']) for q, m in ranked]))
    rows = [[esc(q)] + _fit_cells(m['fit_process'], fmt_ms, fmt_ms) for q, m in ranked if m['fit_process']['enough']]
    body.append("<details><summary>The same fit for processing time only (<code>ms_process</code>)</summary>"
                + table(head, rows) + "</details>")
    rate_rows = [[esc(q), fmt_int(m['batches']), fmt_int(m['sizes']), fmt_int(m['messages']),
                  esc(fmt_ms(m['process_per_1000'])), esc(fmt_ms(m['total_per_1000']))] for q, m in ranked]
    body.append("<details><summary>Time per 1 000 messages, every queue</summary>" + table(
        [('queue', False), ('batches', True), ('batch sizes', True), ('messages', True),
         ('processing per 1 000', True), ('task run per 1 000', True)], rate_rows) + "</details>")

    if fitted:
        top = fitted[:6]
        x_range = _shared_x([m['points_total'] for _, m in top])
        panels = [scatter_panel(q, f"{batches(m['batches'])} &middot; fixed {esc(fmt_ms(m['fit_total']['fixed']))}"
                                f" + {esc(fmt_ms(m['fit_total']['marginal'] * 1000))} per 1 000 msgs",
                                m['points_total'], x_range,
                                _nice_max(max(y for _, y in m['points_total'] if y is not None)), fmt_ms, m['fit_total'])
                  for q, m in top]
        body.append("<h3 style='margin-top:18px'>Batch time against batch size</h3><div class='sub'>The six busiest "
                    "queues with a fit. Batch size on a log axis shared by every panel; time from 0, scaled per "
                    "panel.</div>" + _scatter_legend('batch', 'fitted line')
                    + f"<div class='grid3'>{''.join(panels)}</div>")

    lmodels = listener_cost_models(listeners)
    if lmodels:
        lrows = sorted(lmodels.items(), key=lambda kv: -(kv[1]['per_1000'] or 0))
        heavy = [(k, m) for k, m in lrows if m['messages'] >= 100 and m['per_1000'] is not None]
        body.append("<h3 style='margin-top:18px'>Time per 1 000 messages, by listener and event</h3><div class='sub'>"
                    "Listener time over listener messages, dearest first, among pairs that handled at least 100 "
                    "messages; every pair is in the table.</div>")
        body.append(hbars([(f"{lst} / {ev}", m['per_1000'], fmt_ms(m['per_1000']),
                            f"{lst} on {ev}: {fmt_ms(m['per_1000'])} per 1 000 messages, {fmt_int(m['messages'])} "
                            f"messages, {fmt_ms(m['ms'])} in total") for (lst, ev), m in heavy[:12]]))
        cells = [[esc(lst), esc(ev), esc(m['queue']), fmt_int(m['calls']), fmt_int(m['messages']), esc(fmt_ms(m['ms'])),
                  esc(fmt_ms(m['per_1000']))] for (lst, ev), m in lrows]
        body.append("<details><summary>Every listener and event pair</summary>" + table(
            [('listener', False), ('event', False), ('queue', False), ('calls', True), ('messages', True),
             ('time', True), ('per 1 000 messages', True)], cells) + "</details>")
    body.append("<div class='note'><b>Reading this.</b> A queue whose batches pay a large fixed cost (bootstrap, "
                "connection, one query per batch) gets cheaper per message as batches grow; its fixed share at 200 "
                "is high and at 1 000 low. A queue with a small fixed share scales with messages, and a larger chunk "
                "size will not help it. The fit is only as good as its R squared and its range of batch sizes: a "
                "queue seen at two sizes has a line through two clusters, and a prediction outside the sizes it was "
                "seen at is an extrapolation. A negative fixed part means the line does not describe small "
                "batches, so no fixed share is given.</div>")
    lede = ("Time weighted by messages, so a batch of 1 000 compares with a batch of 200: per 1 000 messages as total "
            "over total, and per queue the split into a fixed cost per batch and a marginal cost per message.")
    return section('Weighted time: per 1 000 messages, fixed and marginal', lede, ''.join(body))


def _unfitted_note(fits):
    """One line naming the queues without a fit, instead of a table row of dashes for each."""
    missing = [(q, f) for q, f in fits if not f.get('enough')]
    if not missing:
        return ''
    names = ', '.join(f"{esc(q)} ({f['n']} batch{'es' if f['n'] != 1 else ''}, {f['sizes']} size{'s' if f['sizes'] != 1 else ''})"
                      for q, f in missing)
    return (f"<details><summary>{NOT_ENOUGH_BATCHES.capitalize()} for {len(missing)} queue{'s' if len(missing) != 1 else ''}"
            f": under 3 batches or a single batch size</summary><div class='sub'>{names}</div></details>")


def _scatter_legend(points_label, line_label):
    return (f"<div class='legend' style='margin-top:6px'><span><i style='background:{SERIES_1};border-radius:50%'></i>"
            f"{esc(points_label)}</span><span><i style='background:{SERIES_2};height:3px;vertical-align:3px'></i>"
            f"{esc(line_label)}</span></div>")


def _pct_cells(p, fmt, keys=(50, 95, 99, 'max')):
    return [NOT_MEASURED if p is None else esc(fmt(p[k])) for k in keys]


def importer_peak_growth(import_groups):
    """{import type: [rise of the process peak during that importer]}, per process, in record order.

    import_groups: one record list per run, because pids repeat across runs.
    """
    growth = defaultdict(list)
    for records in import_groups:
        by_pid = defaultdict(list)
        for r in records:
            if r.get('kind') == 'importer' and r.get('peak_mb') is not None:
                by_pid[r.get('pid')].append(r)
        for rows in by_pid.values():
            rows.sort(key=lambda r: r.get('ts') or 0)
            for previous, current in zip(rows, rows[1:]):
                growth[current.get('import_type') or '?'].append(max(0, current['peak_mb'] - previous['peak_mb']))
    return dict(growth)


def render_memory(tasks, listeners, flush_groups, flush_measured, import_groups=()):
    """Memory per queue, per listener and event, per flush context and per import type (C7).

    flush_groups: {label: [flush records]}, already joined per run. Absent when nothing anywhere
    carries a memory field.
    """
    models = queue_cost_models(tasks)
    lmodels = listener_cost_models(listeners)
    importer_peaks = any(r.get('kind') == 'importer' and r.get('peak_mb') is not None
                         for g in import_groups for r in g)
    flushes = [f for group in flush_groups.values() for f in group]
    any_peak = any(m['memory']['peak'] for m in models.values())
    any_incr = any(m['memory']['increment'] for m in models.values())
    any_listener = any(m['increment'] or m['kb_hist'] for m in lmodels.values())
    any_flush = any(f.get('peak_mb') is not None or batch_increment(f) is not None for f in flushes)
    if not (any_peak or any_incr or any_listener or any_flush or importer_peaks):
        return ''

    body = []
    all_peaks = value_percentiles(t.get('peak_mb') for t in tasks)
    tiles = ["<div class='tiles'>"]
    if all_peaks:
        top_q = max(models.items(), key=lambda kv: (kv[1]['memory']['peak'] or {}).get('max', -1))
        tiles.append(tile('Highest batch peak', esc(fmt_mb(all_peaks['max'])), f"process peak, {top_q[0]}"))
        tiles.append(tile('Batch peak, p95', esc(fmt_mb(all_peaks[95])), f"over {fmt_int(all_peaks['n'])} batches, every queue"))
    added = [(batch_increment(t), t.get('chunk_received')) for t in tasks]
    added = [(a, m) for a, m in added if a is not None and m]
    tiles.append(tile('Added per 100 messages', esc(fmt_mb(100 * sum(a for a, _ in added) / sum(m for _, m in added)))
                      if added else NOT_MEASURED, 'every queue, total over total' if added else 'needs C7 fields'))
    tiles.append(tile('Listener increment', 'measured' if any_listener else NOT_MEASURED,
                      'per call, C7' if any_listener else 'needs C7 fields'))
    flush_peak = value_percentiles(f.get('peak_mb') for f in flushes)
    tiles.append(tile('Flush peak, p95', esc(fmt_mb(flush_peak[95])) if flush_peak else NOT_MEASURED,
                      f"{fmt_int(flush_peak['n'])} direct-sync flushes" if flush_peak else
                      ('flush records carry no memory' if flush_measured else 'no flush file (C6)')))
    tiles.append("</div>")
    body += tiles
    body.append("<div class='note'><b>Absolute against weighted.</b> <code>peak_mb</code> is the process peak, so it "
                "includes the baseline every worker pays before its first message (framework bootstrap, container, "
                "autoloader). It ranks queues by how large their workers grow, but dividing it by messages would "
                "credit the baseline to the batch, so no per-message figure is derived from it. Per message and per "
                "100 messages use only the memory a batch added, <code>mem_peak_incr_mb</code> or "
                "<code>peak_mb - mem_start_mb</code>, summed over batches and divided by the summed messages."
                + ("" if any_incr else f" In these runs that is {NOT_MEASURED}: it {C7_HINT}.") + "</div>")

    # Rankings: absolute and weighted side by side.
    by_peak = sorted(((q, m) for q, m in models.items() if m['memory']['peak']),
                     key=lambda kv: -kv[1]['memory']['peak'][95])
    by_weight = sorted(((q, m) for q, m in models.items() if m['memory']['per_100'] is not None and m['messages'] >= 100),
                       key=lambda kv: -kv[1]['memory']['per_100'])
    left = (hbars([(q, m['memory']['peak'][95], fmt_mb(m['memory']['peak'][95]),
                    f"{q}: p95 peak {fmt_mb(m['memory']['peak'][95])}, max {fmt_mb(m['memory']['peak']['max'])}, "
                    f"{batches(m['batches'])}") for q, m in by_peak[:10]])
            if by_peak else f"<div class='empty'>Peak memory {NOT_MEASURED}</div>")
    right = (hbars([(q, m['memory']['per_100'], fmt_mb(m['memory']['per_100']),
                     f"{q}: {fmt_mb(m['memory']['per_100'])} added per 100 messages, {fmt_int(m['messages'])} messages")
                    for q, m in by_weight[:10]], SERIES_2)
             if by_weight else f"<div class='empty'>Added per 100 messages {NOT_MEASURED}</div>")
    body.append("<div class='grid2'><div class='card'><h3>Most memory hungry queues, absolute</h3><div class='sub'>p95 of "
                f"the batch peak</div>{left}</div><div class='card'><h3>Most memory hungry queues, weighted</h3>"
                f"<div class='sub'>memory added per 100 messages, total over total, queues with at least 100 messages</div>"
                f"{right}</div></div>")

    head = [('queue', False), ('batches', True), ('messages', True), ('peak p50', True), ('peak p95', True),
            ('peak p99', True), ('peak max', True)]
    if any_incr:
        head += [('added p50', True), ('added p95', True), ('added max', True), ('per message', True),
                 ('per 100 msgs', True), ('per 100, batch p50', True), ('per 100, batch p95', True)]
    rows = []
    for q, m in sorted(models.items(), key=lambda kv: -((kv[1]['memory']['peak'] or {}).get(95) or 0)):
        mem = m['memory']
        row = [esc(q), fmt_int(m['batches']), fmt_int(m['messages'])] + _pct_cells(mem['peak'], fmt_mb)
        if any_incr:
            row += _pct_cells(mem['increment'], fmt_mb, (50, 95, 'max'))
            row += [esc(na(mem['per_message'], fmt_mb)), esc(na(mem['per_100'], fmt_mb))]
            row += _pct_cells(mem['per_100_batches'], fmt_mb, (50, 95))
        rows.append(row)
    body.append("<h3 class='sp' style='margin-top:18px'>Memory per queue</h3><div class='sub'>Percentiles are exact "
                "over the batches (nearest rank), hungriest p95 peak first.</div>")
    body.append(table(head, rows))
    if not any_incr:
        body.append(f"<div class='sub' style='margin-top:6px'>Memory added per batch, per message and per 100 messages: "
                    f"{NOT_MEASURED}; it {C7_HINT}.</div>")

    # Distributions for the hungriest queues.
    cards = []
    for q, m in [(q, m) for q, m in by_peak if m['batches'] >= 5][:3]:
        mem = m['memory']
        peaks = [t.get('peak_mb') for t in tasks if t['queue'] == q]
        cards.append(hist_card(q, f"peak per batch, {batches(mem['peak']['n'])}, max {fmt_mb(mem['peak']['max'])}",
                               histogram_from_values(peaks), 'batches', fmt_mb, mem['peak']))
        if mem['per_100_batches']:
            values = [100 * batch_increment(t) / t['chunk_received'] for t in tasks
                      if t['queue'] == q and batch_increment(t) is not None and t.get('chunk_received')]
            cards.append(hist_card(q, f"added per 100 messages, per batch, {batches(len(values))}",
                                   histogram_from_values(values), 'batches', fmt_mb, mem['per_100_batches']))
    if cards:
        body.append("<h3 style='margin-top:18px'>Distribution over batches, hungriest queues</h3><div class='sub'>"
                    "The three hungriest queues with at least 5 batches. Raw per-batch values placed on the same log grid as the stored histograms, then re-binned for "
                    "display; percentiles marked are the exact ones from the table.</div>"
                    f"<div class='grid2'>{''.join(cards)}</div>")

    # Peak (and increment) against batch size.
    scatter_q = [(q, m) for q, m in by_peak if m['batches'] >= 3][:9]
    if scatter_q:
        x_range = _shared_x([m['points_peak'] for _, m in scatter_q])
        y_max = _nice_max(max(m['memory']['peak']['max'] for _, m in scatter_q))
        panels = [scatter_panel(q, f"{batches(m['batches'])}, {fmt_int(m['sizes'])} size{'s' if m['sizes'] != 1 else ''}", m['points_peak'],
                                x_range, y_max, fmt_mb) for q, m in scatter_q]
        body.append("<h3 style='margin-top:18px'>Peak memory against batch size</h3><div class='sub'>One dot per batch, "
                    "the nine hungriest queues with at least 3 batches. Batch size on a log axis and memory from 0, both shared by every panel, "
                    "so a queue whose workers grow with the chunk size slopes up to the right. The peak includes the "
                    "process baseline.</div>" + f"<div class='grid3'>{''.join(panels)}</div>")
    if any_incr:
        mfit = sorted(((q, m) for q, m in models.items() if m['memory']['increment']),
                      key=lambda kv: -kv[1]['memory']['increment'][95])
        rows = [[esc(q)] + _fit_cells(m['memory']['fit'], fmt_mb, fmt_mb) for q, m in mfit if m['memory']['fit']['enough']]
        body.append("<h3 style='margin-top:18px'>Fixed and marginal memory per batch</h3><div class='sub'><code>"
                    "added = fixed + marginal x messages</code>, least squares over the batches that recorded an "
                    "increment. PHP takes memory from the system in 2 MB segments, so small batches round to whole "
                    "segments.</div>" + (table(_fit_head('queue'), rows) if rows else
                                         f"<div class='empty'>{NOT_ENOUGH_BATCHES} on every queue</div>")
                    + _unfitted_note([(q, m['memory']['fit']) for q, m in mfit]))
        fitted = [(q, m) for q, m in mfit if m['memory']['fit']['enough']][:6]
        if fitted:
            x_range = _shared_x([m['points_increment'] for _, m in fitted])
            panels = [scatter_panel(q, f"fixed {esc(fmt_mb(m['memory']['fit']['fixed']))} + "
                                    f"{esc(fmt_mb(m['memory']['fit']['marginal'] * 1000))} per 1 000 msgs",
                                    m['points_increment'], x_range,
                                    _nice_max(m['memory']['increment']['max']), fmt_mb, m['memory']['fit'])
                      for q, m in fitted]
            body.append("<h3 style='margin-top:18px'>Memory added against batch size</h3>"
                        + _scatter_legend('batch', 'fitted line') + f"<div class='grid3'>{''.join(panels)}</div>")

    # Listener and event.
    if lmodels:
        body.append("<h3 class='sp' style='margin-top:20px'>Memory per listener and event</h3>")
        if any_listener:
            by_abs = sorted(((k, m) for k, m in lmodels.items() if m['increment']), key=lambda kv: -kv[1]['increment'][95])
            by_w = sorted(((k, m) for k, m in lmodels.items() if m['per_100_p95'] is not None),
                          key=lambda kv: -kv[1]['per_100_p95'])
            left = hbars([(f"{lst} / {ev}", m['increment'][95], fmt_mb(m['increment'][95]),
                           f"{lst} on {ev}: p95 increment {fmt_mb(m['increment'][95])}, max {fmt_mb(m['increment']['max'])}")
                          for (lst, ev), m in by_abs[:10]]) or f"<div class='empty'>{NOT_MEASURED}</div>"
            right = hbars([(f"{lst} / {ev}", m['per_100_p95'], fmt_mb(m['per_100_p95']),
                            f"{lst} on {ev}: p95 {fmt_mb(m['per_100_p95'])} per 100 messages, "
                            f"p50 {fmt_mb(m['per_100_p50'])}") for (lst, ev), m in by_w[:10]], SERIES_2) \
                or f"<div class='empty'>{NOT_MEASURED}</div>"
            body.append("<div class='card'><h3>Most memory hungry listeners, absolute</h3><div class='sub'>"
                        f"p95 over records of the largest call increment</div>{left}</div><div class='card' "
                        "style='margin-top:12px'><h3>Most memory hungry listeners, weighted</h3><div class='sub'>p95 per "
                        f"call of memory added per 100 messages</div>{right}</div>")
            with_hist = sorted(((k, m) for k, m in lmodels.items() if m['kb_hist']), key=lambda kv: -(kv[1]['kb_pct'][95] or 0))[:4]
            if with_hist:
                cards = [hist_card(lst, f"{ev} \u00b7 memory added per message, per call, "
                                   f"{fmt_int(sum(m['kb_hist'].values()))} calls", m['kb_hist'], 'calls', fmt_kb)
                         for (lst, ev), m in with_hist]
                body.append(f"<div class='grid2'>{''.join(cards)}</div>")
        else:
            body.append(f"<div class='sub'>Listener memory {NOT_MEASURED}: it needs <code>mem_peak_incr_mb</code> and "
                        "<code>mem_peak_incr_hist</code> on the listener record (contract C7), which these runs predate."
                        "</div>")
        lhead = [('listener', False), ('event', False), ('queue', False), ('calls', True), ('messages', True),
                 ('increment p50', True), ('increment p95', True), ('increment max', True),
                 ('per message, call p50', True), ('per message, call p95', True), ('per 100 msgs, call p95', True)]
        lcells = [[esc(lst), esc(ev), esc(m['queue']), fmt_int(m['calls']), fmt_int(m['messages'])]
                  + _pct_cells(m['increment'], fmt_mb, (50, 95, 'max'))
                  + ([NOT_MEASURED] * 2 if not m['kb_pct'] else [esc(fmt_kb(m['kb_pct'][50])), esc(fmt_kb(m['kb_pct'][95]))])
                  + [esc(na(m['per_100_p95'], fmt_mb))]
                  for (lst, ev), m in sorted(lmodels.items(), key=lambda kv: -(((kv[1]['increment'] or {}).get(95)) or 0))]
        if any_listener:
            body.append("<details><summary>Every listener and event pair</summary>" + table(lhead, lcells) + "</details>")
        body.append("<div class='sub' style='margin-top:6px'>A listener record's increment is its largest call: "
                    "peak during the call minus memory at call start, with the peak reset at call start. It is a "
                    "maximum, so it is shown as a distribution and never summed; the per-message figure is per call, "
                    "from the call histogram.</div>")

    # Direct-sync flush.
    body.append("<h3 class='sp' style='margin-top:20px'>Direct-sync flush</h3>")
    if not flush_measured:
        body.append(f"<div class='sub'>Flush memory {NOT_MEASURED}: no <code>sync-flush-runs.jsonl</code> (C6).</div>")
    elif not any_flush:
        body.append(f"<div class='sub'>Flush memory {NOT_MEASURED}: the flush records carry no memory field; it needs "
                    "<code>peak_mb</code> and <code>mem_start_mb</code> on the flush record (contract C7).</div>")
    else:
        fcells = []
        for label, group in sorted(flush_groups.items(), key=lambda kv: -len(kv[1])):
            prof = memory_profile(group, 'messages')
            fcells.append([esc(label), fmt_int(len(group)), fmt_int(sum(f.get('messages') or 0 for f in group))]
                          + _pct_cells(prof['peak'], fmt_mb, (50, 95, 'max'))
                          + _pct_cells(prof['increment'], fmt_mb, (50, 95, 'max'))
                          + [esc(na(prof['per_100'], fmt_mb))])
        body.append(table([('flushed by', False), ('flushes', True), ('messages', True), ('peak p50', True),
                           ('peak p95', True), ('peak max', True), ('added p50', True), ('added p95', True),
                           ('added max', True), ('added per 100 msgs', True)], fcells))
        body.append("<div class='sub' style='margin-top:6px'>The flush runs at console terminate, after the task run "
                    "ended, so its memory is not in the task-run peak.</div>")

    growth = importer_peak_growth(import_groups)
    if growth:
        ranked = sorted(growth.items(), key=lambda kv: -max(kv[1]))
        body.append("<h3 class='sp' style='margin-top:20px'>Import processes</h3><div class='sub'><code>peak_mb</code> on "
                    "an importer record is the peak of the whole <code>data:import</code> process so far, so it only "
                    "rises. The bar is how far the process peak rose during that import type (its peak minus the "
                    "previous importer's in the same process), the largest rise over runs. A rise of 0 means the "
                    "importer stayed under the existing high-water mark, not that it used no memory; the first "
                    "importer of a process has no previous peak and is left out.</div>")
        body.append(hbars([(t, max(v), fmt_mb(max(v)), f"{t}: process peak rose by up to {fmt_mb(max(v))}, "
                            f"{fmt_int(len(v))} runs") for t, v in ranked[:10] if max(v) > 0])
                    or "<div class='sub'>No import type raised the process peak.</div>")
        top = max((r.get('peak_mb') for g in import_groups for r in g if r.get('kind') == 'importer'
                   and r.get('peak_mb') is not None), default=None)
        body.append(f"<div class='sub'>Highest import process peak: {esc(fmt_mb(top))}.</div>")

    lede = ("How much memory each queue, listener and flush uses, as distributions over batches with p50, p95 and p99, "
            "and weighted per message and per 100 messages wherever the memory a batch added is recorded.")
    return section('Memory', lede, ''.join(body))


def sync_mode_chip(meta):
    mode = meta.get('sync_mode')
    if mode is None:
        return "<span class='chip'>sync mode not recorded</span>"
    label = 'direct sync, storage written from the buffer' if mode == 'direct' else 'queue sync, storage written by workers'
    return f"<span class='chip'><span class='dot'></span>{esc(label)}</span>"


# ---------------------------------------------------------------------------------------------
# Output: a rendered report is never overwritten. The default name is new for every render, an
# explicit path that exists is refused, and each page names its own file, renderer commit and time.
# ---------------------------------------------------------------------------------------------

def renderer_version(*files):
    """Short sha of the last commit touching the renderer files, with '+local' when any of them has
    uncommitted changes. 'unknown' outside a git checkout. Read-only git calls."""
    here = pathlib.Path(__file__).resolve().parent
    names = [pathlib.Path(f).name for f in files]
    try:
        sha = subprocess.run(['git', '-C', str(here), 'log', '-1', '--format=%h', '--'] + names,
                             capture_output=True, text=True, timeout=5).stdout.strip()
        dirty = subprocess.run(['git', '-C', str(here), 'status', '--porcelain', '--'] + names,
                               capture_output=True, text=True, timeout=5).stdout.strip()
    except (OSError, subprocess.SubprocessError):
        return 'unknown'
    if not sha:
        return 'unknown'
    return sha + ('+local' if dirty else '')


def versioned_target(directory, now, version):
    """`<dir>/report-<YYYYMMDD-HHMMSS>[-<sha>].html` in UTC; a numeric suffix if that name is taken."""
    base = 'report-' + now.strftime('%Y%m%d-%H%M%S')
    sha = version.split('+')[0]
    if sha and sha != 'unknown':
        base += '-' + sha
    candidate, n = directory / f"{base}.html", 2
    while candidate.exists():
        candidate, n = directory / f"{base}-{n}.html", n + 1
    return candidate


def render_stamp(target, version, now):
    return {'file': pathlib.Path(target).name, 'version': version, 'time': now}


def stamp_footer(stamp):
    if not stamp:
        return ''
    return (f" Report file <code>{esc(stamp['file'])}</code>, renderer commit <code>{esc(stamp['version'])}</code>, "
            f"rendered {esc(stamp['time'].strftime('%Y-%m-%d %H:%M:%S UTC'))}.")


def write_report(render_fn, source, explicit, renderer_files):
    """Render `source` to a new file and return its path. Raises FileExistsError for an explicit
    path that already exists; the file is created exclusively, so a race cannot overwrite either."""
    now = datetime.now(timezone.utc)
    version = renderer_version(*renderer_files)
    if explicit is not None:
        target = pathlib.Path(explicit)
        if target.exists():
            raise FileExistsError(f"refusing to overwrite {target}: a rendered report is never replaced; "
                                  "choose a new path or omit it for a versioned name")
    else:
        target = versioned_target(pathlib.Path(source), now, version)
    target.parent.mkdir(parents=True, exist_ok=True)
    html = render_fn(pathlib.Path(source), render_stamp(target, version, now))
    with open(target, 'x', encoding='utf-8') as handle:
        handle.write(html)
    return target


def cli_main(argv, render_fn, renderer_files, test_pattern, usage):
    if len(argv) > 1 and argv[1] == '--self-test':
        import unittest
        here = pathlib.Path(__file__).resolve().parent
        suite = unittest.defaultTestLoader.discover(str(here), pattern=test_pattern)
        return 0 if unittest.TextTestRunner(verbosity=2).run(suite).wasSuccessful() else 1
    if len(argv) < 2:
        print(usage, file=sys.stderr)
        return 2
    source = pathlib.Path(argv[1])
    if not source.is_dir():
        print(f'not a directory: {source}', file=sys.stderr)
        return 2
    try:
        target = write_report(render_fn, source, argv[2] if len(argv) > 2 else None, renderer_files)
    except FileExistsError as error:
        print(f'error: {error}', file=sys.stderr)
        return 2
    print(f'wrote {target}')
    return 0


def render(bundle: pathlib.Path, stamp=None) -> str:
    now = stamp['time'] if stamp else datetime.now(timezone.utc)
    meta = {}
    meta_path = bundle / 'meta.json'
    if meta_path.exists():
        meta = json.loads(meta_path.read_text())

    runs = load_jsonl(bundle / 'task-runs.jsonl')
    for run in runs:
        if run.get('age_hist'):
            run['age_hist'] = normalize_histogram(run['age_hist'])
    samples = load_jsonl(bundle / 'queue-samples.jsonl')
    listeners = load_jsonl(bundle / 'listener-runs.jsonl')
    for row in listeners:
        if row.get('ms_per_msg_hist'):
            row['ms_per_msg_hist'] = normalize_histogram(row['ms_per_msg_hist'])
    imports = load_jsonl(bundle / (meta.get('import_runs_file') or 'import-runs.jsonl'))
    flush_path = bundle / (meta.get('sync_flush_runs_file') or 'sync-flush-runs.jsonl')
    flushes = [f for f in load_jsonl(flush_path) if f.get('kind', 'direct_sync_flush') == 'direct_sync_flush']

    stages = []
    stages_path = bundle / 'stages.csv'
    if stages_path.exists():
        for line in stages_path.read_text().splitlines()[1:]:
            parts = line.split(',')
            if len(parts) == 4:
                stages.append((parts[0], int(parts[3])))

    run_id = meta.get('run_id', bundle.name)
    out = ["<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'>",
           "<meta name='viewport' content='width=device-width, initial-scale=1'>",
           f"<title>P&amp;S run {esc(run_id)}</title><style>{CSS}</style></head><body><div class='wrap'>"]

    out.append("<header class='top'><div class='eyebrow'>SOL-629 &middot; import, publish and synchronize</div>"
               f"<h1>Run {esc(run_id)}</h1><div class='meta'>{sync_mode_chip(meta)}"
               f"<span>branch <code>{esc(meta.get('branch', 'unknown'))}</code></span>"
               f"<span>commit <code>{esc(str(meta.get('git_sha', '') or 'unknown')[:11])}</code></span>"
               f"<span>rendered {now.strftime('%Y-%m-%d %H:%M UTC')}</span></div></header>")

    # ---- headline ------------------------------------------------------------
    total_stage = sum(s for _, s in stages)
    stage_text = ' &middot; '.join(f"{esc(n)} {esc(fmt_ms(s * 1000))}" for n, s in stages)
    hero = (f"<div class='hero-figure'><div class='l'>End to end, all stages</div>"
            f"<div class='v'>{esc(fmt_ms(total_stage * 1000)) if stages else '-'}</div>"
            f"<div class='sub'>{stage_text or 'no stage timings in this bundle'}</div></div>")
    tiles = []
    if runs:
        msgs = sum(r.get('chunk_received', 0) for r in runs)
        proc_ms = sum(r.get('ms_process', 0.0) for r in runs)
        tot_ms = sum(r.get('ms_total', 0.0) for r in runs)
        per_msg = [r['ms_per_msg'] for r in runs if r.get('ms_per_msg')]
        tiles.append(tile('Messages consumed', fmt_int(msgs), 'measured end to end'))
        tiles.append(tile('Task runs', fmt_int(len(runs)), 'batches recorded'))
        tiles.append(tile('Processing share', fmt_pct(100 * proc_ms / tot_ms if tot_ms else 0),
                          'the rest is receive and bootstrap'))
        if per_msg:
            tiles.append(tile('Cost per message', f"{esc(fmt_ms(min(per_msg)))} to {esc(fmt_ms(max(per_msg)))}",
                              'cheapest to dearest batch'))
    if 'kv_keys' in meta or 'es_docs' in meta:
        kv, es = meta.get('kv_keys', 0), meta.get('es_docs', 0)
        good = bool(kv and es)
        status = (f"<span class='status {'good' if good else 'crit'}'><span class='ic'>{'&#10003;' if good else '!'}"
                  f"</span>{'passed' if good else 'failed'}</span>")
        tiles.append(tile('Positive control', status, f"{fmt_int(kv)} keys, {fmt_int(es)} search documents"))
    else:
        tiles.append(tile('Positive control', "<span class='status neutral'><span class='ic'>?</span>not recorded</span>",
                          'bundle has no meta.json counts'))
    out.append(f"<section><div class='hero'>{hero}<div class='tiles'>{''.join(tiles)}</div></div>")
    notes = ["<div class='note'><b>Positive control.</b> Key-value keys counted from <code>INFO keyspace</code> "
             "across every database, never <code>DBSIZE</code>, which returns 0 on KVrocks even when the store is "
             "full. Scheduler suspended and storages emptied before the run.</div>"]
    if meta.get('sync_mode') == 'direct':
        notes.append("<div class='note'><b>Direct sync.</b> Storage and search entries are written from an in-process "
                     "buffer when the publishing console ends, so the sync queues carry only the fallback for failed "
                     "writes. Compare runs only with runs of the same mode.</div>")
    if meta.get('sync_mode_stale'):
        notes.append("<div class='note warn'><b>Stale generated code.</b> The configuration and the generated "
                     "entities disagree on the sync mode; the report shows the mode of the generated entities, which "
                     "is what ran.</div>")
    out.append(''.join(notes) + "</section>")

    if stages:
        out.append(render_stages(stages))

    import_seconds = next((s for n, s in stages if n == 'import'), None)
    out.append(render_imports(imports, import_seconds))

    if runs:
        out.append(section('Queues', 'One row per queue, every batch aggregated.', render_queues(runs)))
        out.append(render_histograms(runs))
    if listeners:
        out.append(render_listeners(listeners))
    if runs:
        out.append(render_weighted_time(runs, listeners))
    by_queue, elsewhere = join_flushes(runs, flushes)
    flush_groups = {**({'queue workers': [f for g in by_queue.values() for f in g]} if by_queue else {}), **elsewhere}
    out.append(render_memory(runs, listeners, flush_groups, flush_path.exists(), [imports]))
    if runs:
        out.append(render_cadence(runs))
    if samples:
        out.append(render_backlog(samples))
        out.append(render_broker(samples))

    out.append("<div class='footer'>SOL-629 &middot; generated by <code>tools/ps-metrics/render-report.py</code> from "
               "the run bundle. Re-rendering never needs the environment." + stamp_footer(stamp) + "</div>")
    out.append(f"</div><div id='tip' role='tooltip'></div><script>{TOOLTIP_JS}</script></body></html>")
    return '\n'.join(part for part in out if part)


def main():
    return cli_main(sys.argv, render, ['render-report.py'], 'test_render.py',
                    'usage: render-report.py <bundle-dir> [new-output.html] | --self-test')


if __name__ == '__main__':
    sys.exit(main())
