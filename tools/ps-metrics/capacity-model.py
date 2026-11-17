#!/usr/bin/env python3
"""Capacity model for SOL-629 (contract C9): from catalog size to import time, events, queue messages,
P&S time, memory and storage.

Reads one or more measurement campaigns (contract C5) and writes a versioned
`model-<UTC stamp>-<sha>.json` beside the first campaign. The file is created exclusively and never
overwritten, like every report in this tool set.

The chain, each link measured where the data allows it:

    catalog inputs (what to ask a customer)
      -> rows per import type        (rows of the demo run x the scale of the type's driver)
      -> import ms                   (ms per row per import type, plus the unattributed remainder)
      -> events                      (per import type; an emit with importer null is attributed to the
                                      collection whose time span contains it)
      -> messages per queue          (measured messages per queue per run, split over drivers by the
                                      event names the queue's listeners saw)
      -> P&S work ms per queue       (ms per message, task run plus its direct-sync flush)
      -> wall time                   (work over measured parallelism, plus the fixed drain overhead)
      -> memory peaks, storage keys and search documents

Every coefficient carries `n`, the `source` fields it came from and a `quality` flag:
`measured` (directly from records), `derived` (a ratio of measured figures), `mapped` (a measured
figure assigned to a driver by a name rule), `assumed` (no measurement; a stated assumption).

Usage:
    capacity-model.py <campaign-dir> [more campaign dirs]   writes <first campaign>/model-<ts>-<sha>.json
    capacity-model.py --self-test                          runs test_capacity_model.py

Stdlib only.
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


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


rr = _load('render_report_for_model', 'render-report.py')
rc = _load('render_campaign_for_model', 'render-campaign.py')

SCHEMA = 'sol-629-capacity-model/1'
GENERATOR_FILES = ['capacity-model.py']

# ---------------------------------------------------------------------------------------------
# Inputs: the catalog figures to ask a customer for. Each one drives cost in the measured data;
# `demo_from` names the import types whose rows give the demo value (see demo_inputs()).
# ---------------------------------------------------------------------------------------------

INPUTS = [
    {'key': 'abstract_products', 'label': 'Abstract products', 'unit': 'products', 'demo_from': ['product-abstract'],
     'why': 'drives product pages, search documents, URLs and the product storage queues'},
    {'key': 'concretes_per_abstract', 'label': 'Concrete products per abstract', 'unit': 'ratio',
     'demo_from': ['product-concrete', '/', 'product-abstract'],
     'why': 'variants: concrete pages, stock, availability, images and product list membership'},
    {'key': 'stores', 'label': 'Stores', 'unit': 'stores', 'demo_from': ['store'],
     'why': 'every per-store row and every storage entry is written once per store'},
    {'key': 'locales_per_store', 'label': 'Locales per store', 'unit': 'ratio', 'demo_from': ['locale-store', '/', 'store'],
     'why': 'localized storage entries (pages, URLs, categories, CMS) multiply by locale'},
    {'key': 'currencies_per_store', 'label': 'Currencies per store', 'unit': 'ratio',
     'demo_from': ['currency-store', '/', 'store'],
     'why': 'price rows in the database are one per store and currency'},
    {'key': 'prices_per_product', 'label': 'Price rows per product', 'unit': 'ratio',
     'demo_from': ['product-price', '/', 'products'],
     'why': 'price types and volume prices per abstract or concrete product'},
    {'key': 'offers', 'label': 'Merchant offers', 'unit': 'offers', 'demo_from': ['merchant-product-offer'],
     'why': 'offer prices, offer stock and availability, the largest offer queues'},
    {'key': 'merchants', 'label': 'Merchants', 'unit': 'merchants', 'demo_from': ['merchant'],
     'why': 'merchant profiles, opening hours and merchant search'},
    {'key': 'categories', 'label': 'Categories', 'unit': 'categories', 'demo_from': ['category'],
     'why': 'category tree storage, navigation and category search'},
    {'key': 'product_lists', 'label': 'Product lists', 'unit': 'lists', 'demo_from': ['product-list'],
     'why': 'product list search is the most expensive queue per message'},
    {'key': 'cms_items', 'label': 'CMS pages and blocks', 'unit': 'items', 'demo_from': ['cms-page', '+', 'cms-block'],
     'why': 'CMS block storage has the highest queries per message'},
    {'key': 'glossary_keys', 'label': 'Translation rows (glossary)', 'unit': 'rows', 'demo_from': ['glossary'],
     'why': 'the largest import type and the largest storage queue by messages'},
    {'key': 'other_scale', 'label': 'Non-catalog data, multiple of the demo set', 'unit': 'x', 'demo_from': None,
     'why': 'customers, companies, shopping lists and setup data in the import config; 0 leaves them out of scope'},
]

# Drivers: the quantities that rows, messages and storage scale with. quantities() and the browser
# calculator compute them the same way.
DRIVERS = ['abstract', 'concrete', 'products', 'prices', 'offers', 'merchants', 'categories', 'product_lists',
           'cms', 'glossary', 'stores', 'other']

# Name rules, first match wins. Applied to import types, queue names and event names after
# normalisation (lower case, '-' and '.' to '_'). This is the `mapped` step of the chain.
RULES = [
    (r'glossary|translation', 'glossary'),
    (r'merchant_relationship|merchant_relation|company|customer|shopping|quote|cart|comment|ssp_|purchasing|acl_'
     r'|return|discount|shipment_method|payment|sales_order|order_source|workflow|gift_card|service_point'
     r'|health_check|configurable_bundle|customer_access', 'other'),
    (r'offer', 'offers'),
    (r'price', 'prices'),
    (r'merchant_product', 'abstract'),
    (r'merchant', 'merchants'),
    (r'product_list_product_concrete', 'concrete'),
    (r'product_list', 'product_lists'),
    (r'category|navigation', 'categories'),
    (r'cms|content', 'cms'),
    (r'concrete|availability|stock|image|discontinued|alternative|packaging|quantity|measurement|product_search\b'
     r'|spy_product_search', 'concrete'),
    (r'product|tax|url|page|attribute|option|label|review|relation|group|set', 'abstract'),
    (r'^(store|locale|currency|country|default_locale|context|stock_store|tax_set|shipment_type|service)', 'stores'),
]

# Fan-out per driver: storage entries per entity grow with store x locale (localized entities), with
# store x currency (prices) or with store (per-store entities). Assumed, not measured: one data set
# cannot separate it.
FANOUT = {'abstract': 'sl', 'concrete': 'sl', 'products': 'sl', 'categories': 'sl', 'cms': 'sl', 'product_lists': 'sl',
          'prices': 'sc', 'offers': 's', 'merchants': 's'}

ASSUMPTIONS = [
    'Cost is linear in size within a driver: rows, events, messages and work scale with the driver quantity. With '
    'one measured scale this is assumed; with scaled campaigns it is fitted and the R squared is reported.',
    'Stages run one after another as in run-baseline.sh: import, publish:trigger-events, sync:data, then one '
    'queue:worker:start --stop-when-empty drain. Overlapping import and P&S is not modelled.',
    'Drain wall time = fixed drain overhead + worker time / parallelism. Central: the measured parallelism per '
    'worker slot stays the same (parallelism grows with the worker count). Low (fast): every worker slot is busy. '
    'High (slow): parallelism stays at the measured value whatever the worker count.',
    'Batch fill stays as measured for the central figure. The low bound uses full batches (the chunk size) with '
    'the per-queue fit, which lowers the cost of queues with a high fixed cost per batch.',
    'Stores, locales and currencies: storage messages and listener work of localized entities scale with stores x '
    'locales, price work with stores x currencies, offer and merchant work with stores. Not measured: the demo '
    'data has one store and locale set.',
    'Import types with a name ending in -store scale with stores as well as their driver.',
    'Name rules map import types, queues and event names to drivers; the rule list is in `rules`.',
    'Times are from the measured environment (local Docker, no network latency). Database and search latency '
    'in a cloud environment raise the per-message cost; the model does not adjust for it.',
]


# Campaigns whose search figures are not valid. Main session, 2026-10-28: the search mappings were broken
# in these runs (1 130 search documents against 9 534 with correct mappings), so search writes, search
# queue cost and document counts are under-measured. A campaign can also mark itself with
# `"search_valid": false` in campaign.json.
SEARCH_INVALID = {
    '20261028b': 'broken search mappings: 1 130 search documents against 9 534 with correct mappings',
    '20261028c': 'broken search mappings: 1 130 search documents against 9 534 with correct mappings',
    '20261028d': 'broken search mappings; campaign stopped and declared invalid',
}


def search_invalid_reason(campaign):
    """Why a campaign's search figures are not valid, or None when they are."""
    if campaign.get('search_valid') is False:
        return campaign.get('search_invalid_reason') or 'campaign.json marks search figures as not valid'
    return SEARCH_INVALID.get(str(campaign.get('id')))


def norm(text):
    return re.sub(r'[-.\s]+', '_', str(text).lower())


def driver_of(text):
    t = norm(text)
    for pattern, driver in RULES:
        if re.search(pattern, t):
            return driver
    return 'other'


def quantities(inputs):
    """Driver quantities from the catalog inputs. Mirrored in the calculator JS (quantities())."""
    v = {k: float(inputs.get(k) or 0.0) for k in [i['key'] for i in INPUTS]}
    abstract = v['abstract_products']
    concrete = abstract * v['concretes_per_abstract']
    products = abstract + concrete
    return {'abstract': abstract, 'concrete': concrete, 'products': products,
            'prices': products * v['prices_per_product'], 'offers': v['offers'], 'merchants': v['merchants'],
            'categories': v['categories'], 'product_lists': v['product_lists'], 'cms': v['cms_items'],
            'glossary': v['glossary_keys'], 'stores': v['stores'], 'other': v['other_scale'],
            '_sl': v['stores'] * v['locales_per_store'], '_sc': v['stores'] * v['currencies_per_store'],
            '_s': v['stores']}


def scales(model, inputs):
    """Per driver: predicted quantity over the demo quantity. A driver with no demo value stays at 1."""
    q, d = quantities(inputs), quantities(model['demo_inputs'])
    out = {}
    for key in DRIVERS + ['_sl', '_sc', '_s']:
        out[key] = q[key] / d[key] if d[key] else 1.0
    return out


def fanout(sc, driver):
    kind = FANOUT.get(driver)
    return {'sl': sc['_sl'], 'sc': sc['_sc'], 's': sc['_s']}.get(kind, 1.0)


# ---------------------------------------------------------------------------------------------
# Small statistics.
# ---------------------------------------------------------------------------------------------

def med(values):
    values = [v for v in values if v is not None]
    return statistics.median(values) if values else None


def bound(values):
    values = [v for v in values if v is not None]
    if not values:
        return {'value': None, 'low': None, 'high': None, 'n': 0}
    return {'value': statistics.median(values), 'low': min(values), 'high': max(values), 'n': len(values)}


def coef(value, n, source, quality, low=None, high=None, **extra):
    out = {'value': value, 'low': value if low is None else low, 'high': value if high is None else high,
           'n': n, 'source': source, 'quality': quality}
    out.update(extra)
    return out


# ---------------------------------------------------------------------------------------------
# Attribution of publish emissions to import types.
# ---------------------------------------------------------------------------------------------

def attribute_emits(records):
    """[(emit, import_type or None, how)] for every publish_emit record of one run.

    Order of evidence: the C8 `import_type` field, then the emit's own `importer`, then the
    collection of the same pid whose span [ts, ts + ms_wall] contains the emit's start (the
    innermost, i.e. shortest, when several do). An emit outside every span stays None.
    """
    collections = defaultdict(list)
    for r in records:
        if r.get('kind') == 'collection' and r.get('ts') is not None:
            collections[r.get('pid')].append((r['ts'], r['ts'] + (r.get('ms_wall') or 0) / 1000, r.get('import_type')))
    out = []
    for r in records:
        if r.get('kind') != 'publish_emit':
            continue
        if r.get('import_type'):
            out.append((r, r['import_type'], 'import_type'))
        elif r.get('importer'):
            out.append((r, r['importer'], 'importer'))
        else:
            ts = r.get('ts')
            spans = [c for c in collections.get(r.get('pid'), []) if ts is not None and c[0] <= ts <= c[1]]
            if spans:
                out.append((r, min(spans, key=lambda c: c[1] - c[0])[2], 'collection'))
            else:
                out.append((r, None, 'none'))
    return out


# ---------------------------------------------------------------------------------------------
# Per-run facts.
# ---------------------------------------------------------------------------------------------

def read_json(path):
    try:
        return json.loads(pathlib.Path(path).read_text())
    except (OSError, ValueError):
        return None


def run_scale(run, campaign):
    for source, value in (('meta.json scale', run.meta.get('scale')),
                          ('campaign.json run scale', next((e.get('scale') for e in campaign.get('runs', [])
                                                            if e.get('run_id') == run.run_id), None)),
                          ('campaign.json scale', campaign.get('scale'))):
        if value is not None:
            return float(value), source
    return 1.0, 'assumed 1.0 (no scale recorded)'


def busy_profile(run):
    """Drain parallelism of one run from task-run spans (start ts, ms_total) plus their flushes."""
    spans = []
    by_queue, _ = rr.join_flushes(run.tasks, run.flushes)
    flush_by_pid = defaultdict(float)
    for flushes in by_queue.values():
        for f in flushes:
            flush_by_pid[f.get('pid')] += f.get('ms') or 0.0
    for t in run.tasks:
        if t.get('ts') is None:
            continue
        ms = (t.get('ms_total') or 0.0) + flush_by_pid.pop(t.get('pid'), 0.0)
        spans.append((t['ts'], t['ts'] + ms / 1000))
    if not spans:
        return None
    work_s = sum(b - a for a, b in spans)
    first, last = min(a for a, _ in spans), max(b for _, b in spans)
    events = sorted([(a, 1) for a, _ in spans] + [(b, -1) for _, b in spans])
    level = peak = 0
    union = 0.0
    prev = None
    for ts, step in events:
        if prev is not None and level > 0:
            union += ts - prev
        level += step
        peak = max(peak, level)
        prev = ts
    drain_s = run.stage_s('drain')
    span = max(last - first, 1e-6)
    return {'work_s': work_s, 'busy_span_s': span, 'union_s': union, 'max_concurrency': peak,
            'parallelism': work_s / span, 'drain_s': drain_s,
            'overhead_s': (drain_s - span) if drain_s is not None else None}


SCENARIOS = {
    'full_republish': 'Full republish: import, then publish:trigger-events of the whole catalog, sync:data and the drain. '
                      'Queue messages come mostly from the republish, so they scale with the catalog.',
    'import_update': 'Import update: import, sync:data and the drain of the events the import itself emitted '
                     '(no publish:trigger-events). Queue messages scale with what the import changed.',
    'ps_only': 'P&S only: no import; publish:trigger-events on the data already in the database (all resources or a '
               'listed subset) and the drain. Stands for data changed through the API or a custom importer.',
    'first_load': 'First load: import into an empty database, then the drain of the events the import emitted. '
                  'Every row is an insert, so the writers take the insert path.',
}


WORKER_SOURCE_RUNNER = 'queue:worker:start --stop-when-empty'


def scenario_of(run):
    """'first_load' when the import inserted into an empty database, 'full_republish' when
    publish:trigger-events ran in this run, else 'import_update'. meta.json
    publish_trigger (C8 runner) wins; older bundles are classified by their publish stage row."""
    if run.meta.get('import_ran') is False:
        return 'ps_only'
    if run.meta.get('import_path') == 'insert':
        return 'first_load'
    trigger = run.meta.get('publish_trigger')
    if trigger is None:
        trigger = run.stage_s('publish') is not None
    return 'full_republish' if trigger else 'import_update'


def run_facts(run, campaign):
    """Everything the model needs from one instrumented run, keyed for aggregation."""
    importers = defaultdict(lambda: {'rows': 0, 'ms_wall': 0.0, 'ms_native': 0.0, 'cpu_ms': None, 'peak_mb': None,
                                     'events_by_name': None, 'calls': 0})
    for r in run.imports:
        if r.get('kind') != 'importer':
            continue
        b = importers[r.get('import_type') or '?']
        b['calls'] += 1
        b['rows'] += r.get('imported') or 0
        b['ms_wall'] += r.get('ms_wall') or 0.0
        b['ms_native'] += r.get('ms_native') or 0.0
        if r.get('cpu_user_ms') is not None or r.get('cpu_sys_ms') is not None:
            b['cpu_ms'] = (b['cpu_ms'] or 0.0) + (r.get('cpu_user_ms') or 0.0) + (r.get('cpu_sys_ms') or 0.0)
        if r.get('peak_mb') is not None:
            b['peak_mb'] = max(b['peak_mb'] or 0, r['peak_mb'])
        if isinstance(r.get('events_by_name'), dict):
            names = b['events_by_name'] or {}
            for name, count in r['events_by_name'].items():
                names[name] = names.get(name, 0) + (count or 0)
            b['events_by_name'] = names
    events = defaultdict(lambda: {'events': 0, 'messages': 0, 'ms': 0.0, 'by_name': None})
    attribution = defaultdict(int)
    for emit, import_type, how in attribute_emits(run.imports):
        attribution[how] += 1
        e = events[import_type or '(unattributed)']
        e['events'] += emit.get('events') or 0
        e['messages'] += emit.get('messages') or 0
        e['ms'] += emit.get('ms') or 0.0
        if isinstance(emit.get('events_by_name'), dict):
            names = e['by_name'] or {}
            for name, count in emit['events_by_name'].items():
                names[name] = names.get(name, 0) + (count or 0)
            e['by_name'] = names
    by_queue, _ = rr.join_flushes(run.tasks, run.flushes)
    queues = defaultdict(lambda: {'messages': 0, 'batches': 0, 'ms_total': 0.0, 'flush_ms': 0.0, 'flush_messages': 0,
                                  'cpu_ms': None,
                                  'chunk': [], 'peak': [], 'points': [], 'mem_points': []})
    for t in run.tasks:
        q = queues[t['queue']]
        q['messages'] += t.get('chunk_received') or 0
        q['batches'] += 1
        q['ms_total'] += t.get('ms_total') or 0.0
        if t.get('chunk_configured'):
            q['chunk'].append(t['chunk_configured'])
        if t.get('peak_mb') is not None:
            q['peak'].append(t['peak_mb'])
        q['points'].append((t.get('chunk_received'), t.get('ms_total')))
        inc = rr.batch_increment(t)
        if inc is not None and t.get('mem_start_mb') is not None:
            q['mem_points'].append((t.get('chunk_received'), inc, t['mem_start_mb']))
        if t.get('cpu_user_ms') is not None or t.get('cpu_sys_ms') is not None:
            q['cpu_ms'] = (q['cpu_ms'] or 0.0) + (t.get('cpu_user_ms') or 0.0) + (t.get('cpu_sys_ms') or 0.0)
    for queue, flushes in by_queue.items():
        queues[queue]['flush_ms'] += sum(f.get('ms') or 0.0 for f in flushes)
        queues[queue]['flush_messages'] += sum(f.get('messages') or 0 for f in flushes)
    rows_total = sum(b['rows'] for b in importers.values())
    import_stage_ms = (run.stage_s('import') or 0) * 1000 if run.stage_s('import') is not None else None
    wall_total = sum(b['ms_wall'] for b in importers.values())
    scale, scale_source = run_scale(run, campaign)
    storage_final = [s for s in run.storage if s.get('kv_keys') is not None]
    search_final = [s for s in run.storage if s.get('search_docs') is not None]
    return {
        'run_id': run.run_id, 'scale': scale, 'scale_source': scale_source,
        'importers': dict(importers), 'events': dict(events), 'attribution': dict(attribution),
        'queues': dict(queues), 'rows_total': rows_total, 'wall_total_ms': wall_total,
        'import_stage_ms': import_stage_ms,
        'remainder_ms': (import_stage_ms - wall_total) if import_stage_ms is not None and importers else None,
        'stages': {name: secs for name, _, _, secs in run.stages},
        'busy': busy_profile(run),
        'kv_keys': run.meta.get('kv_keys') if run.meta.get('kv_keys') is not None else
        (storage_final[-1]['kv_keys'] if storage_final else None),
        'search_docs': run.meta.get('es_docs') if run.meta.get('es_docs') is not None else
        (search_final[-1]['search_docs'] if search_final else None),
        'storage_size': read_json(run.bundle / 'storage-size.json'),
        'inventory': read_json(run.bundle / 'source-inventory.json'),
        'services': rr.load_jsonl(run.bundle / 'service-samples.jsonl'),
        'sync_mode': run.meta.get('sync_mode'),
        'worker_cap': run.meta.get('worker_max_processes'),
        'scenario': scenario_of(run),
        'worker_source': run.meta.get('worker_source') or WORKER_SOURCE_RUNNER,
    }


# ---------------------------------------------------------------------------------------------
# Demo inputs.
# ---------------------------------------------------------------------------------------------

# A run without an import (P&S only) has no import rows; its catalog is what the database holds, so the
# inputs come from the table behind each import type.
DB_TABLE_OF_IMPORT_TYPE = {
    'product-abstract': 'product_abstract', 'product-concrete': 'product_concrete', 'product-price': 'price_product',
    'merchant-product-offer': 'product_offer', 'merchant': 'merchant', 'category': 'category',
    'product-list': 'product_list', 'cms-page': 'cms_page', 'cms-block': 'cms_block', 'glossary': 'glossary_key',
}


def demo_inputs(type_rows, inventory):
    """Demo value per input, with its source.

    Catalog figures come from the rows of the defining import types: that is what the run imported and
    processed. DB counts of the source inventory (C8) are not used for them, because on the update path
    the database holds the full catalog while a scaled run imports a subset. Stores, locales and
    currencies are configuration, not scaled, so the inventory's DB counts are used for them when present
    (spy_store, spy_locale_store and spy_currency_store rows).
    """
    inv = inventory if isinstance(inventory, dict) else {}
    db = inv.get('db_after_import') or inv.get('db_before_import') or {}
    db = db if isinstance(db, dict) else {}
    values, sources = {}, {}

    def rows(name):
        if name == 'products':
            a, c = rows('product-abstract'), rows('product-concrete')
            return None if a is None or c is None else a + c
        if not type_rows and isinstance(db.get(DB_TABLE_OF_IMPORT_TYPE.get(name)), (int, float)):
            return db[DB_TABLE_OF_IMPORT_TYPE[name]]
        return type_rows.get(name)

    stores_db = db.get('store') if isinstance(db.get('store'), (int, float)) and db.get('store') else None
    for spec in INPUTS:
        key, how = spec['key'], spec['demo_from']
        if how is None:
            values[key], sources[key] = 1.0, 'the demo set itself'
            continue
        if key == 'stores' and stores_db:
            values[key], sources[key] = float(stores_db), 'source-inventory.json spy_store rows'
            continue
        if key in ('locales_per_store', 'currencies_per_store') and stores_db:
            table = 'locale_store' if key == 'locales_per_store' else 'currency_store'
            if isinstance(db.get(table), (int, float)):
                values[key] = db[table] / stores_db
                sources[key] = f"source-inventory.json spy_{table} rows / spy_store rows"
                continue
        if len(how) == 1:
            v = rows(how[0])
            text = f"rows of import type {how[0]}"
        else:
            a, op, b = rows(how[0]), how[1], rows(how[2])
            v = None if a is None or b is None else (a / b if op == '/' and b else a + b if op == '+' else None)
            text = f"rows of {how[0]} {op} rows of {how[2]}"
        if not type_rows and v is not None:
            text = f"database rows behind {text.replace('rows of import type ', '').replace('rows of ', '')} (no import in these runs)"
        values[key] = None if v is None else float(v)
        sources[key] = text if v is not None else 'not in the measured import set'
    return values, sources


# ---------------------------------------------------------------------------------------------
# Building the model.
# ---------------------------------------------------------------------------------------------

def _ratio_bound(per_run):
    """Total over total for the central value, per-run min and max for the bounds.
    per_run: [(numerator, denominator)]."""
    pairs = [(a, b) for a, b in per_run if a is not None and b]
    if not pairs:
        return None
    central = sum(a for a, _ in pairs) / sum(b for _, b in pairs)
    ratios = [a / b for a, b in pairs]
    return {'value': central, 'low': min(ratios), 'high': max(ratios), 'n': len(pairs)}


def worker_cap_from_config():
    path = HERE.parent.parent / 'config' / 'Shared' / 'config_default.php'
    try:
        for i, line in enumerate(path.read_text().splitlines(), 1):
            m = re.search(r'QUEUE_WORKER_MAX_PROCESSES\]\s*=\s*(\d+)', line)
            if m:
                return int(m.group(1)), f"config/Shared/config_default.php:{i} QUEUE_WORKER_MAX_PROCESSES"
    except OSError:
        pass
    return None, None


def build_model(campaign_dirs):
    campaign_dirs = [pathlib.Path(d) for d in campaign_dirs]
    facts, campaigns, warnings = [], [], []
    for d in campaign_dirs:
        campaign, runs, _ = rc.load_campaign(d)
        instr = [r for r in runs if r.ok and r.instrumented]
        campaigns.append({'id': campaign.get('id', d.name), 'dir': d.name, 'runs_instrumented': len(instr),
                          'runs_total': len(runs), 'notes': campaign.get('notes')})
        reason = search_invalid_reason(campaign)
        campaigns[-1]['search_invalid'] = reason
        for r in instr:
            fact = run_facts(r, campaign)
            fact['search_valid'] = reason is None
            facts.append(fact)
        if reason:
            warnings.append(f"campaign {campaign.get('id', d.name)}: search figures not valid ({reason})")
    if not facts:
        raise ValueError('no successful instrumented run in ' + ', '.join(str(d) for d in campaign_dirs))
    scenarios = sorted({f['scenario'] for f in facts})
    if len(scenarios) > 1:
        raise ValueError(f"the campaigns mix scenarios {scenarios}; their coefficients differ, so build one model "
                         "per scenario")
    sources = sorted({f['worker_source'] for f in facts})
    if len(sources) > 1:
        raise ValueError(f"the campaigns mix worker sources {sources}; the scheduler adds its own start cadence, "
                         "so build one model per worker source")
    modes = {f['sync_mode'] for f in facts}
    if len(modes) > 1:
        warnings.append(f"runs mix sync modes {sorted(str(m) for m in modes)}; coefficients mix both")

    scales_seen = sorted({f['scale'] for f in facts})
    multi_scale = len(scales_seen) >= 2
    ref_scale = max(scales_seen)
    ref = [f for f in facts if f['scale'] == ref_scale]

    # ---- import types ----
    types = sorted({t for f in facts for t in f['importers']})
    type_rows_demo = {t: med([f['importers'][t]['rows'] for f in ref if t in f['importers']]) for t in types}
    inventory = next((f['inventory'] for f in ref if f['inventory']), None)
    demo, demo_source = demo_inputs(type_rows_demo, inventory)

    import_types = {}
    for t in types:
        per = [(f['importers'][t]['ms_wall'], f['importers'][t]['rows']) for f in facts if t in f['importers']]
        rows_demo = type_rows_demo[t] or 0
        driver = driver_of(t)
        per_store = t.endswith('-store') and driver != 'stores'
        ms_ref = [f['importers'][t]['ms_wall'] for f in ref if t in f['importers']]
        entry = {'driver': driver, 'per_store': per_store, 'rows_demo': rows_demo,
                 'ms_demo': bound(ms_ref)}
        fit = rr.linear_fit(per) if multi_scale else {'enough': False}
        if fit.get('enough') and fit.get('r2') is not None and fit['r2'] >= 0.8 and fit['marginal'] > 0:
            entry['ms'] = coef(None, fit['n'], ['importer.ms_wall', 'importer.imported'], 'measured',
                               model='fit', fixed=fit['fixed'], per_row=fit['marginal'], r2=fit['r2'])
        else:
            rb = _ratio_bound(per) if rows_demo else None
            if rb:
                entry['ms'] = coef(rb['value'], rb['n'], ['importer.ms_wall', 'importer.imported'],
                                   'measured' if not multi_scale else 'derived', low=rb['low'], high=rb['high'],
                                   model='per_row', **({'fit_r2': fit.get('r2')} if fit.get('enough') else {}))
            else:
                b = bound(ms_ref)
                entry['ms'] = coef(b['value'], b['n'], ['importer.ms_wall'], 'measured', low=b['low'], high=b['high'],
                                   model='fixed')
        ev = [(f['events'].get(t, {}).get('events', 0), f['importers'][t]['rows']) for f in facts if t in f['importers']]
        evb = _ratio_bound(ev) if rows_demo else None
        msgs = [(f['events'].get(t, {}).get('messages', 0), f['importers'][t]['rows']) for f in facts if t in f['importers']]
        msb = _ratio_bound(msgs) if rows_demo else None
        events_fixed = med([f['events'].get(t, {}).get('events', 0) for f in ref if t in f['importers']]) or 0
        entry['events_per_row'] = coef(evb['value'] if evb else 0.0, evb['n'] if evb else len(ev),
                                       ['publish_emit.events', 'publish_emit.import_type | importer | collection span'],
                                       'derived', low=evb['low'] if evb else 0.0, high=evb['high'] if evb else 0.0,
                                       **({} if rows_demo else {'events_fixed': events_fixed}))
        entry['emit_messages_per_row'] = coef(msb['value'] if msb else 0.0, msb['n'] if msb else len(msgs),
                                              ['publish_emit.messages'], 'derived')
        names = defaultdict(int)
        for f in facts:
            bn = f['importers'].get(t, {}).get('events_by_name') or f['events'].get(t, {}).get('by_name')
            for name, count in (bn or {}).items():
                names[name] += count
        rows_sum = sum(f['importers'][t]['rows'] for f in facts if t in f['importers'])
        entry['events_by_name_per_row'] = ({n: c / rows_sum for n, c in names.items()} if names and rows_sum else None)
        cpu = [(f['importers'][t]['cpu_ms'], f['importers'][t]['ms_wall']) for f in facts if t in f['importers']]
        cb = _ratio_bound(cpu)
        entry['cpu_share'] = None if cb is None else coef(cb['value'], cb['n'], ['importer.cpu_user_ms', 'importer.cpu_sys_ms',
                                                                                 'importer.ms_wall'], 'measured')
        peaks = [f['importers'][t]['peak_mb'] for f in facts if t in f['importers'] and f['importers'][t]['peak_mb'] is not None]
        entry['peak_mb'] = max(peaks) if peaks else None
        import_types[t] = entry

    remainder = bound([f['remainder_ms'] for f in ref])
    wall_ref = med([f['wall_total_ms'] for f in ref])
    import_stage = bound([f['import_stage_ms'] for f in ref])
    events_ref = bound([sum(e['events'] for e in f['events'].values()) for f in ref])
    unattributed = sum(f['attribution'].get('none', 0) for f in facts)
    attributed_by = defaultdict(int)
    for f in facts:
        for how, n in f['attribution'].items():
            attributed_by[how] += n

    # ---- queues ----
    listener_mix = defaultdict(lambda: defaultdict(float))
    listener_runs = 0
    for d in campaign_dirs:
        _, runs, _ = rc.load_campaign(d)
        for run in runs:
            if not (run.ok and run.instrumented):
                continue
            if run.listeners:
                listener_runs += 1
            for row in run.listeners:
                listener_mix[row.get('queue') or '?'][driver_of(row.get('event', ''))] += row.get('messages') or 0
    queue_names = sorted({q for f in facts for q in f['queues']})
    queues = {}
    for q in queue_names:
        search_q = q.startswith('sync.search.')
        pool_all = [f for f in facts if f['search_valid']] if search_q and any(f['search_valid'] for f in facts
                                                                               if q in f['queues']) else facts
        pool_ref = [f for f in pool_all if f['scale'] == ref_scale] or [f for f in ref]
        search_bad = search_q and not any(f['search_valid'] for f in pool_all)
        per_ref = [f['queues'][q] for f in pool_ref if q in f['queues']]
        per_all = [f['queues'][q] for f in pool_all if q in f['queues']]
        msgs = bound([x['messages'] for x in per_ref])
        work = [(x['ms_total'] + x['flush_ms'], x['messages']) for x in per_all]
        wb = _ratio_bound(work)
        mix = listener_mix.get(q)
        if mix and sum(mix.values()):
            total = sum(mix.values())
            composition = {k: v / total for k, v in sorted(mix.items(), key=lambda kv: -kv[1])}
            comp_source = 'listener records: messages per event name, event names mapped to drivers'
        else:
            name = re.sub(r'^(sync\.storage\.|sync\.search\.|publish\.)', '', q)
            composition = {driver_of(name) if name != q else 'other': 1.0}
            comp_source = 'queue name mapped to a driver (no listener records)'
        chunk = med([c for x in per_all for c in x['chunk']])
        batches = med([x['batches'] for x in per_ref])
        fit = rr.linear_fit(p for x in per_all for p in x['points'])
        mem_peaks = [p for x in per_all for p in x['peak']]
        mem_fit = rr.linear_fit((s, inc) for x in per_all for s, inc, _ in x['mem_points'])
        mem_start = med([st for x in per_all for _, _, st in x['mem_points']])
        pct = rr.value_percentiles(mem_peaks)
        cb = _ratio_bound([(x['cpu_ms'], x['ms_total']) for x in per_all])
        entry = {
            'kind': 'sync' if q.startswith('sync.') else 'publish',
            'messages_demo': msgs, 'batches_demo': batches, 'chunk': chunk,
            # Storage and search writes this queue's processes flushed under direct sync (C6), per run.
            'flush_messages_demo': med([x.get('flush_messages', 0) for x in per_ref]) or 0,
            'composition': composition, 'composition_source': comp_source,
            'ms_per_message': coef(wb['value'] if wb else 0.0, wb['n'] if wb else 0,
                                   ['task_run.ms_total', 'direct_sync_flush.ms (joined by pid)', 'task_run.chunk_received'],
                                   'invalid: search figures under-measured' if search_bad else 'measured',
                                   low=wb['low'] if wb else 0.0, high=wb['high'] if wb else 0.0),
            'fit': ({'fixed': fit['fixed'], 'marginal': fit['marginal'], 'r2': fit['r2'], 'n': fit['n'],
                     'usable': bool(fit['r2'] is not None and fit['r2'] >= 0.5 and fit['fixed'] >= 0 and fit['marginal'] >= 0)}
                    if fit.get('enough') else {'n': fit.get('n'), 'usable': False}),
            'mem_peak_mb': None if pct is None else {'p95': pct[95], 'max': pct['max'], 'n': pct['n']},
            'mem_fit': ({'fixed': mem_fit['fixed'], 'marginal': mem_fit['marginal'], 'r2': mem_fit['r2'],
                         'start_mb': mem_start} if mem_fit.get('enough') and mem_start is not None else None),
            'cpu_share': None if cb is None else coef(cb['value'], cb['n'], ['task_run.cpu_user_ms', 'task_run.cpu_sys_ms',
                                                                             'task_run.ms_total'], 'measured'),
        }
        if multi_scale:
            qf = rr.linear_fit((x['messages'], x['ms_total'] + x['flush_ms']) for x in per_all)
            entry['scale_fit'] = ({'fixed': qf['fixed'], 'marginal': qf['marginal'], 'r2': qf['r2'], 'n': qf['n']}
                                  if qf.get('enough') else None)
        queues[q] = entry

    # ---- pipeline ----
    busy = [f['busy'] for f in ref if f['busy']]
    cap_value, cap_source = worker_cap_from_config()
    meta_cap = med([f['worker_cap'] for f in facts])
    max_conc = max((b['max_concurrency'] for b in busy), default=None)
    if meta_cap:
        cap_value, cap_source = int(meta_cap), 'meta.json worker_max_processes'
    if cap_value is None:
        cap_value, cap_source = max_conc, 'assumed: the highest measured concurrency'
    par = bound([b['parallelism'] for b in busy])
    overhead = bound([b['overhead_s'] for b in busy])
    work_s = bound([b['work_s'] for b in busy])
    drain = bound([b['drain_s'] for b in busy])
    eff = bound([b['work_s'] / (b['drain_s'] * cap_value) if b['drain_s'] and cap_value else None for b in busy])
    union_share = bound([b['union_s'] / b['drain_s'] if b['drain_s'] else None for b in busy])
    stages = {name: bound([f['stages'].get(name) for f in ref]) for name in ('import', 'publish', 'sync', 'drain')}
    services = defaultdict(lambda: {'cpu': [], 'mem': []})
    for f in ref:
        for s in f['services']:
            name = s.get('service') or s.get('container')
            if name:
                services[name]['cpu'].append(s.get('cpu_pct'))
                services[name]['mem'].append(s.get('mem_mb'))
    service_summary = {c: {'cpu_pct_p95': (rr.value_percentiles(v['cpu']) or {}).get(95),
                           'mem_mb_max': max([m for m in v['mem'] if m is not None], default=None)}
                       for c, v in services.items()} or None

    # ---- storage ----
    kv = bound([f['kv_keys'] for f in ref])
    docs_pool = [f for f in ref if f['search_valid']] or ref
    docs = bound([f['search_docs'] for f in docs_pool])
    docs_valid = any(f['search_valid'] for f in docs_pool)
    kv_bytes = med([((f['storage_size'] or {}).get('key_value') or {}).get('used_memory') for f in ref])
    search_bytes = med([((f['storage_size'] or {}).get('search') or {}).get('store_bytes_total') for f in ref
                        if f['search_valid']])

    # ---- memory ----
    worker_peak = [(q, e['mem_peak_mb']) for q, e in queues.items() if e['mem_peak_mb']]
    importer_peak = max([e['peak_mb'] for e in import_types.values() if e['peak_mb'] is not None], default=None)

    # ---- linearity across scales ----
    linearity = None
    if multi_scale:
        def lin(points, label):
            fit = rr.linear_fit(points)
            return {'label': label, 'n': fit.get('n'), 'r2': fit.get('r2'), 'fixed': fit.get('fixed'),
                    'per_unit': fit.get('marginal'), 'enough': fit.get('enough')}
        linearity = {
            'import_ms_vs_rows': lin([(f['rows_total'], f['import_stage_ms']) for f in facts], 'import stage ms against rows'),
            'messages_vs_rows': lin([(f['rows_total'], sum(x['messages'] for x in f['queues'].values())) for f in facts],
                                    'queue messages against rows'),
            'drain_s_vs_work_s': lin([(f['busy']['work_s'], f['busy']['drain_s']) for f in facts if f['busy']
                                      and f['busy']['drain_s'] is not None], 'drain wall s against worker s'),
        }

    per_scale = []
    for sc_value in scales_seen:
        group = [f for f in facts if f['scale'] == sc_value]
        per_scale.append({'scale': sc_value, 'runs': len(group),
                          'rows': med([f['rows_total'] for f in group]),
                          'events': med([sum(e['events'] for e in f['events'].values()) for f in group]),
                          'messages': med([sum(x['messages'] for x in f['queues'].values()) for f in group]),
                          'import_s': med([f['import_stage_ms'] / 1000 for f in group if f['import_stage_ms'] is not None]),
                          'work_s': med([f['busy']['work_s'] for f in group if f['busy']]),
                          'drain_s': med([f['busy']['drain_s'] for f in group if f['busy']]),
                          'e2e_s': med([sum(f['stages'].values()) for f in group if f['stages']])})
    rows_ref = bound([f['rows_total'] for f in ref])
    msgs_ref = bound([sum(x['messages'] for x in f['queues'].values()) for f in ref])
    model = {
        'schema': SCHEMA,
        'scenario': scenarios[0],
        'scenario_text': SCENARIOS[scenarios[0]],
        'worker_source': sources[0],
        'campaigns': campaigns,
        'runs_used': [{'run_id': f['run_id'], 'scale': f['scale'], 'scale_source': f['scale_source']} for f in facts],
        'warnings': warnings,
        'assumptions': ASSUMPTIONS,
        'rules': [{'pattern': p, 'driver': d} for p, d in RULES],
        'fanout': FANOUT,
        'inputs': [{'key': s['key'], 'label': s['label'], 'unit': s['unit'], 'demo': demo[s['key']],
                    'demo_source': demo_source[s['key']], 'why': s['why']} for s in INPUTS],
        'demo_inputs': {k: v for k, v in demo.items()},
        'import_types': import_types,
        'import': {'remainder_ms': coef(remainder['value'], remainder['n'], ['stages.csv import', 'importer.ms_wall'],
                                        'derived', low=remainder['low'], high=remainder['high']),
                   'wall_total_ms_demo': wall_ref, 'stage_ms_demo': import_stage,
                   'events_demo': events_ref, 'rows_demo': rows_ref,
                   'attribution': {'by': dict(attributed_by), 'unattributed': unattributed},
                   'peak_mb': importer_peak},
        'queues': queues,
        'pipeline': {
            'worker_cap': {'value': cap_value, 'source': cap_source, 'measured_max_concurrency': max_conc},
            'parallelism': coef(par['value'], par['n'], ['task_run.ts', 'task_run.ms_total', 'direct_sync_flush.ms'],
                                'measured', low=par['low'], high=par['high'],
                                note='worker seconds over the span from the first task start to the last task end'),
            'drain_overhead_s': coef(overhead['value'], overhead['n'], ['stages.csv drain', 'task_run.ts'], 'derived',
                                     low=overhead['low'], high=overhead['high'],
                                     note='drain wall time outside the busy span: worker start, last empty polls'),
            'drain_efficiency': coef(eff['value'], eff['n'], ['task_run.ms_total', 'stages.csv drain'], 'derived',
                                     low=eff['low'], high=eff['high'],
                                     note='worker seconds over drain wall seconds x worker cap'),
            'busy_share': coef(union_share['value'], union_share['n'], ['task_run.ts', 'stages.csv drain'], 'derived',
                               note='share of drain wall time with at least one task running'),
            'work_s_demo': work_s, 'drain_s_demo': drain,
            'drain_fit': None,
            'stages_s_demo': stages,
            'messages_demo': msgs_ref,
            'services': service_summary,
        },
        'search_valid': docs_valid,
        'storage': {'kv_keys_demo': kv, 'search_docs_demo': docs, 'kv_bytes_demo': kv_bytes,
                    'search_docs_quality': 'measured' if docs_valid else 'invalid: search figures under-measured',
                    'search_bytes_demo': search_bytes,
                    'source': 'meta.json kv_keys and es_docs (positive control at drain end)'
                    + ('; storage-size.json key_value.used_memory and search.store_bytes_total' if kv_bytes else '')},
        'memory': {'worker_peak_mb_demo': max([p['p95'] for _, p in worker_peak], default=None),
                   'worker_peak_mb_max_demo': max([p['max'] for _, p in worker_peak], default=None),
                   'importer_peak_mb_demo': importer_peak,
                   'source': 'task_run.peak_mb (p95 per queue), importer.peak_mb'},
        'validity': {'scales': scales_seen, 'scale_min': min(scales_seen), 'scale_max': max(scales_seen),
                     'tolerance': 0.10, 'linearity': linearity, 'per_scale': per_scale,
                     'scaling': 'fitted across scales' if multi_scale else 'assumed linear (one measured scale)'},
    }
    d = (linearity or {}).get('drain_s_vs_work_s') or {}
    # Two scales always fit a line exactly, so a drain fit needs three.
    if len(scales_seen) >= 3 and d.get('enough') and d.get('r2') is not None and d['r2'] >= 0.8 and d['per_unit'] > 0:
        model['pipeline']['drain_fit'] = {'fixed_s': d['fixed'], 'per_work_s': d['per_unit'], 'r2': d['r2'], 'n': d['n'],
                                          'note': 'drain wall s = fixed + per_work_s x worker s, fitted across scales at '
                                                  'the measured worker cap; used for the central drain at that cap'}
    for e in model['queues'].values():
        f = e.get('scale_fit')
        e['use_scale_fit'] = bool(f and f['r2'] is not None and f['r2'] >= 0.8 and f['marginal'] > 0 and f['fixed'] >= 0)
    model['reproduction'] = reproduction(model, ref)
    return model


# ---------------------------------------------------------------------------------------------
# Prediction. Mirrored line by line in the calculator JS of render-campaign.py (CALC_JS).
# ---------------------------------------------------------------------------------------------

def _ms_import(entry, rows, which):
    c = entry['ms']
    if c.get('model') == 'fit':
        return c['fixed'] + c['per_row'] * rows if rows else 0.0
    if c.get('model') == 'fixed':
        return c[which] or 0.0
    return rows * (c[which] or 0.0)


def predict(model, inputs, workers=None):
    """Predicted figures for a catalog. Each timed figure is {'value', 'low', 'high'} in its unit."""
    sc = scales(model, inputs)
    workers = workers or model['pipeline']['worker_cap']['value'] or 1
    rows = 0.0
    imp = {'value': 0.0, 'low': 0.0, 'high': 0.0}
    wall_demo = 0.0
    events = 0.0
    events_by_name = defaultdict(float)
    for t, e in model['import_types'].items():
        k = sc[e['driver']] * (sc['_s'] if e['per_store'] else 1.0)
        r = e['rows_demo'] * k
        rows += r
        for which in ('value', 'low', 'high'):
            ms = _ms_import(e, r, which) if e['rows_demo'] else (e['ms'][which] or 0.0) * k
            imp[which] += ms
        wall_demo += _ms_import(e, e['rows_demo'], 'value') if e['rows_demo'] else (e['ms']['value'] or 0.0)
        if e['rows_demo']:
            events += r * e['events_per_row']['value']
        else:
            events += e['events_per_row'].get('events_fixed', 0) * k
        for name, per_row in (e.get('events_by_name_per_row') or {}).items():
            events_by_name[name] += r * per_row
    ratio = imp['value'] / wall_demo if wall_demo else 1.0
    rem = model['import']['remainder_ms']
    for which in ('value', 'low', 'high'):
        imp[which] += (rem[which] or 0.0) * ratio

    messages = {}
    flush_k = {}
    work = {'value': 0.0, 'low': 0.0, 'high': 0.0}
    pub_demo = pub_pred = sync_demo = sync_pred = 0.0
    mem_central = mem_high = 0.0
    for q, e in model['queues'].items():
        m_demo = e['messages_demo']['value'] or 0.0
        msg_k = sum(share * sc[d] * (fanout(sc, d) if e['kind'] == 'sync' else 1.0) for d, share in e['composition'].items())
        work_k = sum(share * sc[d] * fanout(sc, d) for d, share in e['composition'].items())
        m = m_demo * msg_k
        messages[q] = m
        flush_k[q] = work_k
        per = e['ms_per_message']
        # Work scales with the fan-out even where the message count does not (a publish message writes one
        # entry per store and locale); work_k / msg_k is that extra factor per message.
        extra = work_k / msg_k if msg_k else 1.0
        if e.get('use_scale_fit') and m:
            work['value'] += (e['scale_fit']['fixed'] + e['scale_fit']['marginal'] * m) * extra
        else:
            work['value'] += m * per['value'] * extra
        work['high'] += m * per['high'] * extra
        low = m * per['low'] * extra
        fit = e.get('fit') or {}
        if fit.get('usable') and e.get('chunk') and m:
            full = math.ceil(m / e['chunk']) * fit['fixed'] + m * fit['marginal'] * extra
            low = min(low, full)
        work['low'] += low
        if e['kind'] == 'sync':
            sync_demo += m_demo
            sync_pred += m
        else:
            pub_demo += m_demo
            pub_pred += m
        peak = e.get('mem_peak_mb')
        if peak:
            mem_central = max(mem_central, peak['p95'])
            mem_high = max(mem_high, peak['max'])
            mf = e.get('mem_fit')
            if mf and e.get('chunk') and mf['marginal'] > 0:
                mem_high = max(mem_high, mf['start_mb'] + mf['fixed'] + mf['marginal'] * e['chunk'])

    pipe = model['pipeline']
    cap = pipe['worker_cap']['value'] or 1
    par = pipe['parallelism']
    over = pipe['drain_overhead_s']
    p_central = min(workers, (par['value'] or 1.0) * workers / cap)
    p_fast = float(workers)
    p_slow = min(float(workers), par['low'] or par['value'] or 1.0)
    p_most = min(float(workers), (par['high'] or par['value'] or 1.0) * workers / cap)
    dfit = pipe.get('drain_fit')
    central = ((dfit['fixed_s'] + dfit['per_work_s'] * work['value'] / 1000) if dfit and workers == cap else
               (over['value'] or 0.0) + work['value'] / 1000 / p_central)
    # A drain shorter than the work at the highest measured parallelism needs parallelism no run showed.
    central = max(central, (over['value'] or 0.0) + work['value'] / 1000 / p_most)
    drain = {'value': central,
             'low': (over['low'] or 0.0) + work['low'] / 1000 / p_fast,
             'high': (over['high'] or 0.0) + work['high'] / 1000 / p_slow}
    st = pipe['stages_s_demo']
    pub_k = pub_pred / pub_demo if pub_demo else 1.0
    sync_k = sync_pred / sync_demo if sync_demo else 1.0
    publish_s = {w: ((st.get('publish') or {}).get(w) or 0.0) * pub_k for w in ('value', 'low', 'high')}
    sync_s = {w: ((st.get('sync') or {}).get(w) or 0.0) * sync_k for w in ('value', 'low', 'high')}
    for b in (work, drain):
        b['low'], b['high'] = min(b['low'], b['value']), max(b['high'], b['value'])
    ps = {w: publish_s[w] + sync_s[w] + drain[w] for w in ('value', 'low', 'high')}
    import_s = {w: imp[w] / 1000 for w in ('value', 'low', 'high')}
    total = {w: import_s[w] + ps[w] for w in ('value', 'low', 'high')}

    storage = model['storage']
    kv_demo = storage['kv_keys_demo']['value']
    docs_demo = storage['search_docs_demo']['value']
    st_demo = sum(e['messages_demo']['value'] or 0 for q, e in model['queues'].items() if q.startswith('sync.storage.'))
    st_pred = sum(messages[q] for q in messages if q.startswith('sync.storage.'))
    se_demo = sum(e['messages_demo']['value'] or 0 for q, e in model['queues'].items() if q.startswith('sync.search.'))
    se_pred = sum(messages[q] for q in messages if q.startswith('sync.search.'))
    # Under direct sync the writes skip the sync queues and leave in the flush of the publish queue that
    # produced them, so the flushed messages scale with that queue's work (fan-out included).
    fl_demo = sum(e.get('flush_messages_demo') or 0 for e in model['queues'].values())
    fl_pred = sum((e.get('flush_messages_demo') or 0) * flush_k[q] for q, e in model['queues'].items())
    kv_k = (st_pred + fl_pred) / (st_demo + fl_demo) if st_demo + fl_demo else sync_k
    docs_k = (se_pred + fl_pred) / (se_demo + fl_demo) if se_demo + fl_demo else sync_k
    rows_demo = model['import']['rows_demo']['value'] or 0
    k_rows = rows / rows_demo if rows_demo else 1.0
    val = model['validity']
    driver_scales = [sc[d] for d in DRIVERS if d != 'other' and model['demo_inputs'].get(_input_of(d)) is not None]
    k_lo = min(driver_scales + [k_rows]) if driver_scales else k_rows
    k_hi = max(driver_scales + [k_rows]) if driver_scales else k_rows
    tol = val['tolerance']
    reasons = []
    if k_hi > 1 + tol:
        reasons.append('larger than the largest measured data set')
    if k_lo < val['scale_min'] / val['scale_max'] * (1 - tol):
        reasons.append('smaller than the smallest measured data set')
    if abs(sc['_sl'] - 1) > tol or abs(sc['_sc'] - 1) > tol:
        reasons.append('store, locale or currency count differs from the measured set (fan-out assumed)')
    if workers != cap:
        reasons.append('worker count differs from the measured worker cap')
    extrapolated = bool(reasons)
    return {
        'scale_rows': k_rows, 'scale_range': [k_lo, k_hi], 'extrapolated': extrapolated, 'reasons': reasons,
        'rows': rows, 'events': events, 'events_by_name': dict(events_by_name) or None,
        'import_s': import_s, 'messages': sum(messages.values()), 'messages_by_queue': messages,
        'work_s': {w: work[w] / 1000 for w in work}, 'publish_s': publish_s, 'sync_s': sync_s,
        'drain_s': drain, 'ps_s': ps, 'total_s': total, 'workers': workers,
        'worker_peak_mb': {'value': mem_central, 'low': mem_central, 'high': max(mem_high, mem_central)},
        'concurrent_peak_mb': {'value': mem_central * min(workers, math.ceil(p_central)),
                               'low': mem_central, 'high': max(mem_high, mem_central) * workers},
        'importer_peak_mb': model['memory']['importer_peak_mb_demo'],
        'kv_keys': None if kv_demo is None else kv_demo * kv_k,
        'search_docs': None if docs_demo is None else docs_demo * docs_k,
        'kv_bytes': None if not storage.get('kv_bytes_demo') else storage['kv_bytes_demo'] * kv_k,
        'search_bytes': None if not storage.get('search_bytes_demo') else storage['search_bytes_demo'] * docs_k,
    }


def _input_of(driver):
    return {'abstract': 'abstract_products', 'concrete': 'concretes_per_abstract', 'products': 'abstract_products',
            'prices': 'prices_per_product', 'offers': 'offers', 'merchants': 'merchants', 'categories': 'categories',
            'product_lists': 'product_lists', 'cms': 'cms_items', 'glossary': 'glossary_keys', 'stores': 'stores',
            'other': 'other_scale'}[driver]


def reproduction(model, ref_facts):
    """predict(demo inputs) against what the reference runs measured: value and the run range."""
    p = predict(model, model['demo_inputs'])

    def row(label, predicted, measured):
        b = bound(measured)
        tol = 1e-6 * max(1.0, abs(b['high'] or 0.0))
        inside = None if b['value'] is None else (b['low'] - tol <= predicted <= b['high'] + tol)
        return {'figure': label, 'predicted': predicted, 'measured_median': b['value'], 'measured_low': b['low'],
                'measured_high': b['high'], 'n': b['n'], 'within_run_range': inside}

    out = [
        row('import stage s', p['import_s']['value'], [f['import_stage_ms'] / 1000 for f in ref_facts if f['import_stage_ms'] is not None]),
        row('rows imported', p['rows'], [f['rows_total'] for f in ref_facts]),
        row('events emitted by import', p['events'], [sum(e['events'] for e in f['events'].values()) for f in ref_facts]),
        row('queue messages', p['messages'], [sum(x['messages'] for x in f['queues'].values()) for f in ref_facts]),
        row('worker s (tasks + flushes)', p['work_s']['value'], [f['busy']['work_s'] for f in ref_facts if f['busy']]),
        row('drain s', p['drain_s']['value'], [f['busy']['drain_s'] for f in ref_facts if f['busy'] and f['busy']['drain_s'] is not None]),
        row('end to end s', p['total_s']['value'], [sum(f['stages'].values()) for f in ref_facts if f['stages']]),
    ]
    return out


# ---------------------------------------------------------------------------------------------
# Output.
# ---------------------------------------------------------------------------------------------

def versioned_model_path(directory, now, version):
    base = 'model-' + now.strftime('%Y%m%d-%H%M%S')
    sha = version.split('+')[0]
    if sha and sha != 'unknown':
        base += '-' + sha
    candidate, n = directory / f"{base}.json", 2
    while candidate.exists():
        candidate, n = directory / f"{base}-{n}.json", n + 1
    return candidate


def write_model(campaign_dirs, now=None):
    now = now or datetime.now(timezone.utc)
    version = rr.renderer_version(*GENERATOR_FILES)
    model = build_model(campaign_dirs)
    model['generated'] = {'time': now.strftime('%Y-%m-%dT%H:%M:%SZ'), 'generator': 'tools/ps-metrics/capacity-model.py',
                          'version': version}
    target = versioned_model_path(pathlib.Path(campaign_dirs[0]), now, version)
    model['generated']['file'] = target.name
    with open(target, 'x', encoding='utf-8') as handle:
        json.dump(model, handle, indent=1, sort_keys=False)
        handle.write('\n')
    return target, model


def latest_model(campaign_dir):
    """Newest model-*.json beside a campaign by name (names carry a UTC stamp), or None."""
    files = sorted(pathlib.Path(campaign_dir).glob('model-*.json'))
    for path in reversed(files):
        data = read_json(path)
        if isinstance(data, dict) and data.get('schema') == SCHEMA:
            return path, data
    return None


def main(argv):
    if len(argv) > 1 and argv[1] == '--self-test':
        import unittest
        suite = unittest.defaultTestLoader.discover(str(HERE), pattern='test_capacity_model.py')
        return 0 if unittest.TextTestRunner(verbosity=2).run(suite).wasSuccessful() else 1
    if len(argv) < 2:
        print('usage: capacity-model.py <campaign-dir> [more campaign dirs] | --self-test', file=sys.stderr)
        return 2
    dirs = [pathlib.Path(a) for a in argv[1:]]
    for d in dirs:
        if not d.is_dir():
            print(f'not a directory: {d}', file=sys.stderr)
            return 2
    target, model = write_model(dirs)
    print(f'wrote {target}')
    for r in model['reproduction']:
        flag = {True: 'inside', False: 'OUTSIDE', None: 'n/a'}[r['within_run_range']]
        print(f"  {r['figure']:28s} predicted {r['predicted']:12.1f}  measured {r['measured_median']}"
              f" [{r['measured_low']} .. {r['measured_high']}]  {flag}")
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv))
