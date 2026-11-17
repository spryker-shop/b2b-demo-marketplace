#!/usr/bin/env python3
"""Self-test for capacity-model.py and the capacity section of render-campaign.py.
Run: python3 tools/ps-metrics/test_capacity_model.py (or capacity-model.py --self-test). Stdlib only."""

import importlib.util
import json
import pathlib
import re
import shutil
import subprocess
import tempfile
import unittest
from datetime import datetime, timezone

HERE = pathlib.Path(__file__).resolve().parent
REAL = HERE.parent.parent / 'data' / 'metrics' / 'campaign-20261028c'


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


cm = _load('capacity_model_under_test', 'capacity-model.py')
rc = _load('render_campaign_under_test', 'render-campaign.py')


def write_run(bundle, scale, t0, jitter=0.0, search_ms=1.0):
    """One instrumented bundle: two import types (abstract products and glossary), emits with importer
    null inside their collection, two queues, listeners with event names, one flush."""
    bundle.mkdir(parents=True)
    abstract, glossary, store = int(200 * scale), int(1000 * scale), 2
    recs = []
    ts = t0 + 1
    for import_type, rows, ms_per_row, events in (('store', store, 5.0, 0), ('product-abstract', abstract, 1.0, abstract),
                                                    ('glossary', glossary, 0.2, glossary // 2)):
        ms = rows * ms_per_row * (1 + jitter)
        recs.append({'kind': 'collection', 'ts': ts, 'pid': 7, 'import_type': import_type, 'ms_wall': ms + 50})
        recs.append({'kind': 'importer', 'ts': ts + 0.001, 'pid': 7, 'import_type': import_type, 'imported': rows,
                     'expected': rows, 'is_success': True, 'ms_native': ms, 'ms_wall': ms, 'peak_mb': 20})
        recs.append({'kind': 'publish_emit', 'ts': ts + ms / 1000 + 0.01, 'pid': 7, 'ms': 10.0, 'events': events,
                     'messages': events, 'importer': None})
        ts += (ms + 60) / 1000
    import_s = max(1, round(ts - t0 + 1))
    (bundle / 'import-runs.jsonl').write_text(''.join(json.dumps(r) + '\n' for r in recs))
    drain0 = t0 + import_s + 2
    tasks, listeners = [], []
    pid = 100
    cursor = drain0 + 1
    for queue, messages, event, chunk in (('publish', abstract + glossary // 2, None, 500),
                                          ('sync.storage.product', abstract * 2, None, 1000),
                                          ('sync.search.product', abstract, None, 1000)):
        left = messages
        while left > 0:
            n = min(chunk, left)
            per = {'publish': 0.5, 'sync.storage.product': 0.1}.get(queue, search_ms)
            ms = 20 + n * per * (1 + jitter)
            tasks.append({'ts': cursor, 'queue': queue, 'pid': pid, 'chunk_configured': chunk, 'chunk_received': n,
                          'ms_total': ms, 'ms_process': ms * 0.9, 'peak_mb': 30, 'mem_start_mb': 4,
                          'mem_peak_incr_mb': 26})
            if queue == 'publish':
                listeners.append({'queue': queue, 'pid': pid, 'listener': 'ProductAbstractListener',
                                  'event': 'Product.product_abstract.publish', 'calls': 1,
                                  'messages': round(n * abstract / messages), 'ms': ms * 0.4,
                                  'breakdown': {'db_calls': n * 3}, 'mem_peak_incr_mb': 5.0})
                listeners.append({'queue': queue, 'pid': pid, 'listener': 'GlossaryListener',
                                  'event': 'Glossary.key.publish', 'calls': 1,
                                  'messages': n - round(n * abstract / messages), 'ms': ms * 0.5,
                                  'breakdown': {'db_calls': n}, 'mem_peak_incr_mb': 2.0})
            cursor += ms / 1000 + 0.2
            pid += 1
            left -= n
    (bundle / 'task-runs.jsonl').write_text(''.join(json.dumps(t) + '\n' for t in tasks))
    (bundle / 'listener-runs.jsonl').write_text(''.join(json.dumps(r) + '\n' for r in listeners))
    (bundle / 'sync-flush-runs.jsonl').write_text(json.dumps(
        {'kind': 'direct_sync_flush', 'ts': cursor, 'pid': 100, 'context': 'queue', 'queue': 'publish', 'ms': 5.0,
         'messages': 10}) + '\n')
    drain_s = max(1, round(cursor - drain0 + 1))
    stages = [('import', t0, import_s), ('publish', t0 + import_s, 1), ('sync', t0 + import_s + 1, 1),
              ('drain', drain0, drain_s)]
    (bundle / 'stages.csv').write_text('stage,started,ended,seconds\n' + ''.join(
        f"{n},{int(s)},{int(s) + d},{d}\n" for n, s, d in stages))
    (bundle / 'meta.json').write_text(json.dumps({'kv_keys': int(1000 * scale), 'es_docs': abstract, 'sync_mode': 'direct',
                                                  'instrumented': True, 'scale': scale}))


def write_fixture(root, scales=(1.0, 1.0, 1.0), campaign_id='fixture', search_ms=1.0):
    entries = []
    for i, scale in enumerate(scales):
        run_id = f"r{i + 1:02d}"
        write_run(root / run_id, scale, 1000 + 1000 * i, jitter=0.05 * (i % 3), search_ms=search_ms)
        entries.append({'run_id': run_id, 'instrumented': True, 'bundle': run_id, 'status': 'ok'})
    (root / 'campaign.json').write_text(json.dumps({'id': campaign_id, 'runs': entries}))


class AttributionTest(unittest.TestCase):

    def test_null_importer_emit_goes_to_the_collection_whose_span_contains_it(self):
        records = [{'kind': 'collection', 'ts': 10.0, 'pid': 1, 'import_type': 'glossary', 'ms_wall': 2000},
                   {'kind': 'publish_emit', 'ts': 11.5, 'pid': 1, 'events': 3, 'importer': None}]
        (_, import_type, how), = cm.attribute_emits(records)
        self.assertEqual((import_type, how), ('glossary', 'collection'))

    def test_innermost_collection_wins_and_other_pids_and_outside_spans_do_not_count(self):
        records = [{'kind': 'collection', 'ts': 10.0, 'pid': 1, 'import_type': 'outer', 'ms_wall': 10000},
                   {'kind': 'collection', 'ts': 12.0, 'pid': 1, 'import_type': 'inner', 'ms_wall': 1000},
                   {'kind': 'collection', 'ts': 12.0, 'pid': 2, 'import_type': 'other-pid', 'ms_wall': 1000},
                   {'kind': 'publish_emit', 'ts': 12.5, 'pid': 1, 'events': 1, 'importer': None},
                   {'kind': 'publish_emit', 'ts': 30.0, 'pid': 1, 'events': 1, 'importer': None}]
        result = [(t, how) for _, t, how in cm.attribute_emits(records)]
        self.assertEqual(result, [('inner', 'collection'), (None, 'none')])

    def test_c8_import_type_then_importer_take_precedence_over_the_span(self):
        records = [{'kind': 'collection', 'ts': 10.0, 'pid': 1, 'import_type': 'span', 'ms_wall': 5000},
                   {'kind': 'publish_emit', 'ts': 11.0, 'pid': 1, 'importer': 'own', 'import_type': 'c8'},
                   {'kind': 'publish_emit', 'ts': 11.0, 'pid': 1, 'importer': 'own'}]
        self.assertEqual([(t, h) for _, t, h in cm.attribute_emits(records)], [('c8', 'import_type'), ('own', 'importer')])

    def test_name_rules_map_to_drivers(self):
        self.assertEqual(cm.driver_of('Glossary.key.publish'), 'glossary')
        self.assertEqual(cm.driver_of('price-product-offer'), 'offers')
        self.assertEqual(cm.driver_of('product-price'), 'prices')
        self.assertEqual(cm.driver_of('Product.product_concrete.publish'), 'concrete')
        self.assertEqual(cm.driver_of('product-abstract'), 'abstract')
        self.assertEqual(cm.driver_of('company-user'), 'other')


class ModelTest(unittest.TestCase):

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = pathlib.Path(self.tmp.name) / 'campaign-fixture'
        self.root.mkdir()

    def tearDown(self):
        self.tmp.cleanup()

    def test_demo_prediction_reproduces_the_fixture_runs(self):
        write_fixture(self.root)
        model = cm.build_model([self.root])
        checks = {r['figure']: r for r in model['reproduction']}
        for figure in ('rows imported', 'events emitted by import', 'queue messages', 'import stage s', 'drain s'):
            self.assertTrue(checks[figure]['within_run_range'], checks[figure])

    def test_every_coefficient_carries_n_source_and_quality(self):
        write_fixture(self.root)
        model = cm.build_model([self.root])
        for e in model['import_types'].values():
            for key in ('ms', 'events_per_row'):
                self.assertTrue({'n', 'source', 'quality', 'value', 'low', 'high'} <= set(e[key]), (key, e[key]))
        for e in model['queues'].values():
            self.assertTrue({'n', 'source', 'quality'} <= set(e['ms_per_message']))
        self.assertTrue({'n', 'source', 'quality'} <= set(model['pipeline']['parallelism']))

    def test_extrapolated_outside_the_measured_range_and_not_inside(self):
        write_fixture(self.root)
        model = cm.build_model([self.root])
        demo = dict(model['demo_inputs'])
        self.assertFalse(cm.predict(model, demo)['extrapolated'])
        big = dict(demo, abstract_products=demo['abstract_products'] * 10)
        p = cm.predict(model, big)
        self.assertTrue(p['extrapolated'])
        self.assertIn('larger than the largest measured data set', p['reasons'])
        self.assertTrue(cm.predict(model, demo, workers=10)['extrapolated'])

    def test_glossary_only_growth_scales_glossary_rows_and_not_products(self):
        write_fixture(self.root)
        model = cm.build_model([self.root])
        demo = dict(model['demo_inputs'])
        p = cm.predict(model, dict(demo, glossary_keys=demo['glossary_keys'] * 2))
        self.assertAlmostEqual(p['rows'], 2 + 200 + 2000)

    def test_scaled_campaign_fits_linearity_and_widens_the_valid_range(self):
        write_fixture(self.root, scales=(0.5, 0.5, 1.0, 1.0))
        model = cm.build_model([self.root])
        self.assertEqual(model['validity']['scales'], [0.5, 1.0])
        self.assertEqual(model['validity']['scaling'], 'fitted across scales')
        lin = model['validity']['linearity']['messages_vs_rows']
        self.assertTrue(lin['enough'])
        self.assertGreater(lin['r2'], 0.99)
        half = {k: (v * 0.5 if k in ('abstract_products', 'glossary_keys') else v) for k, v in model['demo_inputs'].items()}
        self.assertNotIn('smaller than the smallest measured data set', cm.predict(model, half)['reasons'])
        tiny = {k: (v * 0.1 if k in ('abstract_products', 'glossary_keys') else v) for k, v in model['demo_inputs'].items()}
        self.assertIn('smaller than the smallest measured data set', cm.predict(model, tiny)['reasons'])
        self.assertEqual([r['scale'] for r in model['validity']['per_scale']], [0.5, 1.0])
        self.assertIn('Measured per scale', rc.render(self.root))

    def test_import_update_runs_without_a_publish_stage_build_and_predict_no_publish_time(self):
        write_fixture(self.root)
        for bundle in self.root.iterdir():
            if bundle.is_dir():
                lines = (bundle / 'stages.csv').read_text().splitlines()
                (bundle / 'stages.csv').write_text('\n'.join(l for l in lines if not l.startswith('publish,')) + '\n')
        model = cm.build_model([self.root])
        self.assertEqual(model['scenario'], 'import_update')
        p = cm.predict(model, model['demo_inputs'])
        self.assertEqual(p['publish_s']['value'], 0.0)
        self.assertGreater(p['ps_s']['value'], 0.0)
        self.assertIn('Capacity model', rc.render(self.root))

    def test_campaigns_of_different_scenarios_are_not_mixed(self):
        other = pathlib.Path(self.tmp.name) / 'campaign-update'
        other.mkdir()
        write_fixture(self.root)
        write_fixture(other)
        for bundle in other.iterdir():
            if bundle.is_dir():
                meta = json.loads((bundle / 'meta.json').read_text())
                (bundle / 'meta.json').write_text(json.dumps(dict(meta, publish_trigger=False)))
        self.assertEqual(cm.build_model([self.root])['scenario'], 'full_republish')
        with self.assertRaisesRegex(ValueError, 'mix scenarios'):
            cm.build_model([self.root, other])

    def test_search_coefficients_come_from_valid_campaigns_only(self):
        good = pathlib.Path(self.tmp.name) / 'campaign-good'
        good.mkdir()
        write_fixture(self.root, campaign_id='20261028c', search_ms=0.1)
        write_fixture(good, campaign_id='good', search_ms=2.0)
        alone = cm.build_model([self.root])
        self.assertFalse(alone['search_valid'])
        self.assertTrue(alone['queues']['sync.search.product']['ms_per_message']['quality'].startswith('invalid'))
        self.assertTrue(any('search figures not valid' in w for w in alone['warnings']))
        both = cm.build_model([self.root, good])
        self.assertTrue(both['search_valid'])
        per = both['queues']['sync.search.product']['ms_per_message']
        self.assertEqual(per['quality'], 'measured')
        self.assertGreater(per['value'], 2.0, 'taken from the valid campaign (2 ms per message plus batch cost)')
        html = rc.render(self.root)
        self.assertIn('Search figures in this campaign are not valid', html)
        self.assertIn('Search not valid.', html)

    def test_model_file_is_versioned_and_never_overwritten(self):
        write_fixture(self.root)
        now = datetime(2026, 10, 28, 12, 0, 0, tzinfo=timezone.utc)
        first, _ = cm.write_model([self.root], now=now)
        before = first.read_text()
        second, _ = cm.write_model([self.root], now=now)
        self.assertNotEqual(first, second)
        self.assertEqual(first.read_text(), before)
        self.assertTrue(first.name.startswith('model-20261028-120000'))

    def test_report_has_folded_sections_and_embeds_the_saved_model_unchanged(self):
        write_fixture(self.root)
        target, _ = cm.write_model([self.root])
        saved = json.loads(target.read_text())
        html = rc.render(self.root)
        for anchor in ('capacity', 'look-first', 'timeline', 'bottlenecks', 'quality'):
            self.assertRegex(html, rf"<section id='{anchor}' class='fold'><details><summary><h2>")
        self.assertIn("<section id='summary'>", html)
        self.assertIn("href='#capacity'", html)
        embedded = json.loads(re.search(r"<script type='application/json' id='capacity-model'>(.*?)</script>", html,
                                        re.S).group(1))
        self.assertEqual(embedded, saved)
        self.assertIn(target.name, html)

    def test_look_first_ranks_on_the_mean_of_measured_ranks(self):
        write_fixture(self.root)
        _, runs, _ = rc.load_campaign(self.root)
        rows = rc.look_first([r for r in runs if r.ok])
        self.assertEqual([r['rank'] for r in rows], list(range(1, len(rows) + 1)))
        top = rows[0]
        self.assertEqual(top['key'][0], 'ProductAbstractListener')
        self.assertIsNone(top['cpu'])

    def test_storage_grows_with_the_flushed_writes_when_no_sync_queue_carries_them(self):
        write_fixture(self.root)
        model = cm.build_model([self.root])
        for q in ('sync.storage.product', 'sync.search.product'):
            del model['queues'][q]
        self.assertEqual(model['queues']['publish']['flush_messages_demo'], 10)
        demo = dict(model['demo_inputs'])
        big = dict(demo, abstract_products=demo['abstract_products'] * 10, glossary_keys=demo['glossary_keys'] * 10)
        p = cm.predict(model, big)
        self.assertAlmostEqual(p['kv_keys'], model['storage']['kv_keys_demo']['value'] * 10, places=6)
        self.assertAlmostEqual(p['search_docs'], model['storage']['search_docs_demo']['value'] * 10, places=6)

    def test_drain_fit_needs_three_scales(self):
        write_fixture(self.root, scales=(0.5, 0.5, 1.0, 1.0))
        self.assertIsNone(cm.build_model([self.root])['pipeline']['drain_fit'])

    def test_drain_is_never_shorter_than_the_work_at_the_highest_measured_parallelism(self):
        write_fixture(self.root)
        model = cm.build_model([self.root])
        model['pipeline']['drain_fit'] = {'fixed_s': 0.0, 'per_work_s': 0.01, 'r2': 1.0, 'n': 3, 'note': ''}
        big = dict(model['demo_inputs'], abstract_products=model['demo_inputs']['abstract_products'] * 100)
        p = cm.predict(model, big)
        pipe = model['pipeline']
        cap = pipe['worker_cap']['value']
        most = min(cap, pipe['parallelism']['high'] or pipe['parallelism']['value'])
        self.assertGreaterEqual(p['drain_s']['value'] + 1e-9,
                                (pipe['drain_overhead_s']['value'] or 0.0) + p['work_s']['value'] / most)

    def test_first_load_runs_are_their_own_scenario(self):
        write_fixture(self.root)
        for bundle in self.root.iterdir():
            if bundle.is_dir():
                meta = json.loads((bundle / 'meta.json').read_text())
                (bundle / 'meta.json').write_text(json.dumps(dict(meta, import_path='insert')))
        self.assertEqual(cm.build_model([self.root])['scenario'], 'first_load')

    def test_ps_only_runs_take_catalog_inputs_from_the_database(self):
        write_fixture(self.root)
        for bundle in self.root.iterdir():
            if bundle.is_dir():
                (bundle / 'import-runs.jsonl').unlink()
                (bundle / 'source-inventory.json').write_text(json.dumps(
                    {'db_after_import': {'product_abstract': 300, 'product_concrete': 600, 'glossary_key': 4000, 'store': 2}}))
                meta = json.loads((bundle / 'meta.json').read_text())
                (bundle / 'meta.json').write_text(json.dumps(dict(meta, import_ran=False)))
        model = cm.build_model([self.root])
        self.assertEqual(model['scenario'], 'ps_only')
        self.assertEqual(model['demo_inputs']['abstract_products'], 300)
        self.assertEqual(model['demo_inputs']['glossary_keys'], 4000)
        demo = dict(model['demo_inputs'])
        big = cm.predict(model, dict(demo, abstract_products=3000, glossary_keys=40000))
        self.assertGreater(big['messages'], cm.predict(model, demo)['messages'] * 5)

    def test_companion_campaigns_render_one_scenario_row_each_with_predictions(self):
        write_fixture(self.root)
        other = pathlib.Path(self.tmp.name) / 'campaign-other'
        other.mkdir()
        write_fixture(other, campaign_id='other')
        for bundle in other.iterdir():
            if bundle.is_dir():
                lines = (bundle / 'stages.csv').read_text().splitlines()
                (bundle / 'stages.csv').write_text('\n'.join(l for l in lines if not l.startswith('publish,')) + '\n')
        (self.root / 'companions.json').write_text(json.dumps({'campaigns': ['../campaign-other']}))
        html = rc.render(self.root)
        self.assertIn("<section id='scenarios' class='fold'>", html)
        self.assertIn('Full republish', html)
        self.assertIn('Import update', html)
        self.assertIn('predicted, catalog x100', html)

    def test_calculator_js_gives_the_same_prediction_as_python(self):
        node = shutil.which('node')
        if not node:
            self.skipTest('node not installed')
        for scales in ((1.0, 1.0, 1.0), (0.5, 0.5, 1.0, 1.0)):
            root = pathlib.Path(self.tmp.name) / f"c{len(scales)}"
            root.mkdir()
            write_fixture(root, scales=scales)
            self._parity(node, cm.build_model([root]))

    def _parity(self, node, model):
        demo = dict(model['demo_inputs'])
        cases = [(demo, 5), (dict(demo, abstract_products=demo['abstract_products'] * 7, stores=4), 8),
                 (dict(demo, glossary_keys=demo['glossary_keys'] * 3), 5)]
        script = rc.PREDICT_JS + "\nvar M=" + json.dumps(model) + ";var C=" + json.dumps(cases) + \
            ";console.log(JSON.stringify(C.map(function(c){return capacityPredict(M,c[0],c[1]);})));"
        out = subprocess.run([node, '-e', script], capture_output=True, text=True, timeout=30)
        self.assertEqual(out.returncode, 0, out.stderr)
        js = json.loads(out.stdout)
        for (inputs, workers), got in zip(cases, js):
            want = cm.predict(model, inputs, workers)
            for key in ('rows', 'events', 'messages', 'kv_keys', 'search_docs', 'scale_rows'):
                self.assertAlmostEqual(got[key], want[key], places=6, msg=key)
            for key in ('import_s', 'ps_s', 'total_s', 'drain_s', 'work_s', 'worker_peak_mb', 'concurrent_peak_mb'):
                for w in ('value', 'low', 'high'):
                    self.assertAlmostEqual(got[key][w], want[key][w], places=6, msg=(key, w))
            self.assertEqual(got['extrapolated'], want['extrapolated'])
            self.assertEqual(got['reasons'], want['reasons'])


@unittest.skipUnless((REAL / 'campaign.json').exists(), 'campaign 20261028c not on this machine')
class RealCampaignTest(unittest.TestCase):

    def test_model_reproduces_campaign_20261028c_within_the_run_spread(self):
        model = cm.build_model([REAL])
        checks = {r['figure']: r for r in model['reproduction']}
        for figure in ('import stage s', 'queue messages', 'drain s', 'events emitted by import', 'end to end s'):
            self.assertTrue(checks[figure]['within_run_range'], checks[figure])
        self.assertEqual(model['import']['attribution']['unattributed'], 0)


if __name__ == '__main__':
    unittest.main()
