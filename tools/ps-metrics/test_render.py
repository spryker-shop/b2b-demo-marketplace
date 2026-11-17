#!/usr/bin/env python3
"""Self-test for render-report.py. Run: python3 tools/ps-metrics/test_render.py
(or: python3 tools/ps-metrics/render-report.py --self-test). Stdlib only."""

import importlib.util
import json
import pathlib
import tempfile
import unittest
import xml.etree.ElementTree as ElementTree

HERE = pathlib.Path(__file__).resolve().parent
_spec = importlib.util.spec_from_file_location('render_report', HERE / 'render-report.py')
rr = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(rr)


def fine_key(step):
    """The true label LatencyHistogram writes for a step, 0.001 ms * 2^(step/16)."""
    bound = 0.001 * 2 ** (step / 16)
    decimals = 4 if bound < 1 else (2 if bound < 100 else 0)
    text = f"{bound:.{decimals}f}"
    if decimals:
        text = text.rstrip('0').rstrip('.')
    return 'le=' + text


def shown(view):
    return (sum(b['count'] for b in view['bins']) + (view['under'] or {}).get('count', 0)
            + (view['over'] or {}).get('count', 0) + view['overflow'])


def bin_ratio(view):
    return 2 ** (view['k'] / view['steps'])


class DisplayBinsTest(unittest.TestCase):

    def test_narrow_distribution_gets_finer_bins_than_a_wide_one(self):
        narrow = {fine_key(s): 100 for s in range(300, 306)}           # 6 steps, under half an octave
        wide = {fine_key(s): 100 for s in range(100, 500, 3)}          # 25 octaves
        narrow_view, wide_view = rr.display_bins(narrow), rr.display_bins(wide)
        self.assertLess(bin_ratio(narrow_view), bin_ratio(wide_view))
        self.assertEqual(narrow_view['k'], 1, 'a narrow distribution keeps the stored resolution')
        self.assertLessEqual(len(wide_view['bins']), 30)
        self.assertGreaterEqual(len(wide_view['bins']), 15)

    def test_no_counts_are_lost(self):
        cases = [
            {fine_key(s): (s * 7) % 13 + 1 for s in range(50, 400, 2)},
            {fine_key(10): 1, fine_key(300): 10_000, fine_key(301): 5000, fine_key(620): 1, 'le=+Inf': 3},
            {'le=256': 4, 'le=1024': 90, 'le=16384': 6, 'le=+Inf': 2},
        ]
        for buckets in cases:
            view = rr.display_bins(buckets)
            self.assertEqual(shown(view), sum(buckets.values()))
            self.assertEqual(view['total'], sum(buckets.values()))

    def test_trimmed_tails_become_edge_bars(self):
        buckets = {fine_key(10): 1, fine_key(300): 10_000, fine_key(301): 5000, fine_key(620): 1}
        view = rr.display_bins(buckets)
        self.assertEqual(view['under']['count'], 1)
        self.assertEqual(view['over']['count'], 1)
        self.assertLess(view['bins'][-1]['hi'] / view['bins'][0]['lo'], 2, 'span centres on the mass')

    def test_single_bucket_histogram_renders_centred(self):
        view = rr.display_bins({fine_key(320): 42})
        self.assertEqual(shown(view), 42)
        self.assertEqual(len(view['bins']), 15)
        filled = [i for i, b in enumerate(view['bins']) if b['count']]
        self.assertEqual(len(filled), 1)
        self.assertIn(filled[0], (6, 7, 8))
        self.assertIn('<svg', rr.histogram_svg({fine_key(320): 42}))

    def test_empty_histogram_renders_a_placeholder(self):
        self.assertEqual(rr.display_bins({})['total'], 0)
        self.assertIn('No samples recorded', rr.histogram_svg({}))
        self.assertIn('No samples recorded', rr.histogram_svg(None))

    def test_overflow_is_kept_as_its_own_bar(self):
        buckets = {fine_key(200): 10, 'le=+Inf': 5}
        view = rr.display_bins(buckets)
        self.assertEqual(view['overflow'], 5)
        self.assertIn('overflow', rr.histogram_svg(buckets))
        only_overflow = rr.display_bins({'le=+Inf': 7})
        self.assertEqual(only_overflow['overflow'], 7)
        self.assertEqual(only_overflow['bins'], [])
        self.assertIn('<svg', rr.histogram_svg({'le=+Inf': 7}))

    def test_power_of_two_buckets_from_old_bundles_use_their_own_grid(self):
        view = rr.display_bins({'le=256': 4, 'le=1024': 90, 'le=16384': 6})
        self.assertEqual(view['steps'], 1)
        self.assertEqual(shown(view), 100)

    def test_format_1_labels_are_repaired_before_binning(self):
        histogram = rr.normalize_histogram({'buckets': {'le=284': 3}})   # format 1 label of 2840 ms
        self.assertIn('le=2840', histogram['buckets'])


class RenderTest(unittest.TestCase):

    def test_bundle_without_import_records_has_no_import_section(self):
        with tempfile.TemporaryDirectory() as tmp:
            bundle = pathlib.Path(tmp)
            (bundle / 'stages.csv').write_text('stage,started,ended,seconds\nimport,0,10,10\n')
            html = rr.render(bundle)
            self.assertNotIn('<h2>Import</h2>', html)

    def test_import_section_renders_from_c1_records(self):
        with tempfile.TemporaryDirectory() as tmp:
            bundle = pathlib.Path(tmp)
            (bundle / 'stages.csv').write_text('stage,started,ended,seconds\nimport,0,10,10\n')
            records = [
                {'kind': 'importer', 'ts': 1.0, 'pid': 1, 'import_type': 'glossary', 'source': 'x.csv',
                 'expected': 100, 'imported': 100, 'is_success': True, 'ms_native': 1000.5, 'ms_wall': 1500.0,
                 'peak_mb': 40},
                {'kind': 'publish_emit', 'ts': 2.0, 'pid': 1, 'ms': 12.5, 'events': 10, 'messages': None},
                {'kind': 'rows', 'ts': 1.0, 'pid': 1, 'import_type': 'glossary', 'n': 100,
                 'hist': {'n': 100, 'buckets': {fine_key(250): 100}}},
            ]
            (bundle / 'import-runs.jsonl').write_text('\n'.join(json.dumps(r) for r in records))
            html = rr.render(bundle)
            self.assertIn('<h2>Import</h2>', html)
            self.assertIn('8.5 s', html, 'unattributed is stage wall minus summed ms_wall')
            self.assertIn('not measured', html, 'a null message count is never shown as 0')


class EmissionSplitTest(unittest.TestCase):

    def test_flush_after_the_importer_counts_as_outside(self):
        records = [
            {'kind': 'importer', 'ts': 10.0, 'pid': 1, 'ms_wall': 1000.0},
            {'kind': 'publish_emit', 'ts': 10.5, 'pid': 1, 'ms': 30.0},              # inside by time
            {'kind': 'publish_emit', 'ts': 11.5, 'pid': 1, 'ms': 70.0},              # after the importer
            {'kind': 'publish_emit', 'ts': 10.2, 'pid': 1, 'ms': 5.0, 'importer': None},  # writer says outside
        ]
        self.assertEqual(rr.emission_split(records), (30.0, 75.0))


class FormatTest(unittest.TestCase):

    def test_durations_use_human_units(self):
        self.assertEqual(rr.fmt_ms(0.0123), '12.3 µs')
        self.assertEqual(rr.fmt_ms(2.5), '2.5 ms')
        self.assertEqual(rr.fmt_ms(15_877.339), '15.9 s')
        self.assertEqual(rr.fmt_ms(125_000), '2 min 5 s')
        self.assertEqual(rr.fmt_ms(float('inf')), 'overflow')

    def test_svg_has_a_sane_view_box(self):
        svg = rr.histogram_svg({fine_key(s): 5 for s in range(200, 260)})
        start = svg.index('<svg')
        root = ElementTree.fromstring(svg[start:svg.index('</svg>') + 6].replace('&', '&amp;'))
        x, y, w, h = map(float, root.get('viewBox').split())
        self.assertEqual((x, y), (0, 0))
        self.assertGreater(w, 0)
        self.assertGreater(h, 0)


def mem_task(queue, messages, ms_total, **memory):
    return {'ts': 1.0, 'queue': queue, 'pid': messages, 'chunk_received': messages, 'chunk_configured': 1000,
            'ms_total': ms_total, 'ms_process': ms_total * 0.8, **memory}


class WeightedCostTest(unittest.TestCase):

    def test_fit_on_exact_data_returns_the_fixed_and_marginal_cost(self):
        fit = rr.linear_fit([(10, 5 + 0.2 * 10), (100, 5 + 0.2 * 100), (500, 5 + 0.2 * 500), (1000, 5 + 0.2 * 1000)])
        self.assertTrue(fit['enough'])
        self.assertAlmostEqual(fit['fixed'], 5.0)
        self.assertAlmostEqual(fit['marginal'], 0.2)
        self.assertAlmostEqual(fit['r2'], 1.0)
        self.assertEqual(fit['n'], 4)
        self.assertAlmostEqual(rr.fit_predict(fit, 200), 45.0)
        self.assertAlmostEqual(rr.fixed_share(fit, 1000), 5 / 205)

    def test_under_three_batches_or_one_distinct_size_is_not_enough_batches(self):
        self.assertFalse(rr.linear_fit([(10, 1.0), (100, 2.0)])['enough'])
        self.assertFalse(rr.linear_fit([(500, 1.0), (500, 2.0), (500, 3.0), (500, 4.0)])['enough'])
        self.assertIsNone(rr.fit_predict(rr.linear_fit([(500, 1.0)] * 5), 200))
        html = rr.render_weighted_time([mem_task('q', 500, 10.0), mem_task('q', 500, 12.0)], [])
        self.assertIn(rr.NOT_ENOUGH_BATCHES.capitalize() + ' for 1 queue', html)
        self.assertIn('q (2 batches, 1 size)', html)

    def test_negative_fixed_part_gives_no_fixed_share(self):
        fit = rr.linear_fit([(100, 5.0), (200, 15.0), (300, 25.0)])
        self.assertAlmostEqual(fit['fixed'], -5.0)
        self.assertIsNone(rr.fixed_share(fit, 200))

    def test_time_per_1000_messages_is_total_over_total(self):
        model = rr.queue_cost_models([mem_task('q', 10, 100.0), mem_task('q', 990, 900.0)])['q']
        self.assertAlmostEqual(model['total_per_1000'], 1000.0, msg='1000 ms over 1000 messages, not the mean of 10 000 and 909')

    def test_memory_per_100_messages_is_total_over_total(self):
        tasks = [mem_task('q', 10, 1.0, mem_start_mb=20.0, peak_mb=21.0),          # 10 MB per 100 on its own
                 mem_task('q', 990, 1.0, mem_start_mb=20.0, mem_peak_incr_mb=9.0, peak_mb=29.0)]
        profile = rr.memory_profile(tasks, 'chunk_received')
        self.assertAlmostEqual(profile['per_100'], 1.0, msg='10 MB over 1 000 messages, not the mean of 10 and 0.91')
        self.assertAlmostEqual(profile['per_message'], 0.01)
        self.assertEqual(profile['per_100_batches']['max'], 10.0)

    def test_baseline_including_peak_is_never_divided_per_message(self):
        tasks = [mem_task('q', n, 1.0, peak_mb=30) for n in (10, 100, 500, 1000)]
        self.assertIsNone(rr.batch_increment(tasks[0]))
        profile = rr.memory_profile(tasks, 'chunk_received')
        self.assertEqual(profile['peak']['max'], 30)
        self.assertIsNone(profile['per_message'])
        self.assertIsNone(profile['per_100'])
        self.assertIsNone(profile['per_100_batches'])
        self.assertFalse(profile['fit']['enough'], 'no increment, no memory fit')
        html = rr.render_memory(tasks, [], {}, False)
        self.assertNotIn('>3 MB<', html, '30 MB over 1 000 messages per 100 must not appear')
        self.assertNotIn('>300 MB<', html)

    def test_missing_c7_fields_render_not_measured_never_zero(self):
        tasks = [mem_task('q', 100, 1.0, peak_mb=30), mem_task('q', 200, 1.0, peak_mb=32)]
        listeners = [{'queue': 'q', 'listener': 'L', 'event': 'e', 'calls': 1, 'messages': 100, 'ms': 4.0}]
        flushes = {'queue workers': [{'kind': 'direct_sync_flush', 'pid': 100, 'ms': 2.0, 'messages': 5}]}
        html = rr.render_memory(tasks, listeners, flushes, True)
        self.assertIn(f"<div class='v'>{rr.NOT_MEASURED}</div><div class='s'>needs C7 fields</div>", html)
        self.assertIn(f"Listener memory {rr.NOT_MEASURED}", html)
        self.assertIn(f"Flush memory {rr.NOT_MEASURED}", html)
        self.assertIn(f"Memory added per batch, per message and per 100 messages: {rr.NOT_MEASURED}", html)
        self.assertNotIn('>0 MB<', html)
        no_file = rr.render_memory(tasks, [], {}, False)
        self.assertIn(f"Flush memory {rr.NOT_MEASURED}: no", no_file)
        self.assertIsNone(rr.listener_cost_models(listeners)[('L', 'e')]['increment'])

    def test_present_c7_fields_render_weighted_memory(self):
        tasks = [mem_task('q', n, 1.0, mem_start_mb=20.0, peak_mb=20.0 + 2 + 0.01 * n) for n in (100, 200, 400, 800)]
        html = rr.render_memory(tasks, [], {}, False)
        self.assertIn('Fixed and marginal memory per batch', html)
        fit = rr.queue_cost_models(tasks)['q']['memory']['fit']
        self.assertAlmostEqual(fit['fixed'], 2.0)
        self.assertAlmostEqual(fit['marginal'], 0.01)

    def test_a_zero_increment_is_its_own_bucket_and_shows_as_zero(self):
        buckets = rr.histogram_from_values([0, 0, 2.0])
        self.assertEqual(buckets['le=0'], 2)
        self.assertEqual(rr.value_percentiles([0, 0, 2.0])[50], 0)
        self.assertIn('exactly 0', rr.histogram_svg(rr.histogram_from_values([0, 0]), 'batches', rr.fmt_mb))


class VersionedOutputTest(unittest.TestCase):

    def test_rendering_twice_writes_two_files_and_leaves_the_first_unchanged(self):
        with tempfile.TemporaryDirectory() as tmp:
            bundle = pathlib.Path(tmp)
            (bundle / 'meta.json').write_text(json.dumps({'run_id': 'x'}))
            first = rr.write_report(rr.render, bundle, None, ['render-report.py'])
            before = first.read_bytes()
            second = rr.write_report(rr.render, bundle, None, ['render-report.py'])
            self.assertNotEqual(first, second)
            self.assertEqual(first.read_bytes(), before)
            self.assertRegex(first.name, r'^report-\d{8}-\d{6}(-[0-9a-f]+)?(-\d+)?\.html$')
            self.assertIn(f"Report file <code>{first.name}</code>", before.decode())
            self.assertIn('renderer commit', before.decode())

    def test_an_explicit_path_that_exists_is_refused(self):
        with tempfile.TemporaryDirectory() as tmp:
            bundle = pathlib.Path(tmp)
            target = bundle / 'report.html'
            target.write_text('keep me')
            with self.assertRaises(FileExistsError):
                rr.write_report(rr.render, bundle, target, ['render-report.py'])
            self.assertEqual(target.read_text(), 'keep me')


if __name__ == '__main__':
    unittest.main(verbosity=2)
