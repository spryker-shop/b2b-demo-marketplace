#!/usr/bin/env python3
"""Self-test for render-campaign.py and storage-sampler.py. Run: python3 tools/ps-metrics/test_campaign.py
(or: python3 tools/ps-metrics/render-campaign.py --self-test). Stdlib only."""

import importlib.util
import json
import re
import pathlib
import tempfile
import unittest

HERE = pathlib.Path(__file__).resolve().parent


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


rc = _load('render_campaign', 'render-campaign.py')
sampler = _load('storage_sampler', 'storage-sampler.py')


def fine_key(step):
    bound = 0.001 * 2 ** (step / 16)
    decimals = 4 if bound < 1 else (2 if bound < 100 else 0)
    text = f"{bound:.{decimals}f}"
    if decimals:
        text = text.rstrip('0').rstrip('.')
    return 'le=' + text


def task(queue, pid, messages, ms_total, buckets, **extra):
    return {'ts': 100.0 + pid, 'queue': queue, 'pid': pid, 'chunk_configured': 100, 'chunk_received': messages,
            'processed': messages, 'failed': 0, 'ms_total': ms_total, 'ms_process': ms_total * 0.9,
            'age_hist': {'n': sum(buckets.values()), 'format': 2, 'buckets': buckets}, **extra}


def write_campaign(root, runs, story=None):
    """runs: list of (run_id, instrumented, stage_seconds, tasks, flushes or None)."""
    entries = []
    for i, (run_id, instrumented, seconds, tasks, flushes) in enumerate(runs):
        bundle = root / run_id
        bundle.mkdir()
        start = 1000 + 1000 * i
        lines, cursor = ['stage,started,ended,seconds'], start
        for name, secs in zip(('import', 'publish', 'sync', 'drain'), seconds):
            lines.append(f"{name},{cursor},{cursor + secs},{secs}")
            cursor += secs
        (bundle / 'stages.csv').write_text('\n'.join(lines) + '\n')
        (bundle / 'meta.json').write_text(json.dumps({'run_id': run_id, 'kv_keys': 10, 'es_docs': 5,
                                                      'sync_mode': 'direct', 'instrumented': instrumented}))
        if tasks:
            (bundle / 'task-runs.jsonl').write_text(''.join(json.dumps(t) + '\n' for t in tasks))
        if flushes is not None:
            (bundle / 'sync-flush-runs.jsonl').write_text(''.join(json.dumps(f) + '\n' for f in flushes))
        entries.append({'run_id': run_id, 'instrumented': instrumented, 'bundle': run_id, 'status': 'ok'})
    (root / 'campaign.json').write_text(json.dumps({'id': 'test', 'runs': entries}))
    if story is not None:
        (root / 'story.md').write_text(story)


class MergeTest(unittest.TestCase):

    def test_buckets_are_merged_by_sum_so_counts_equal_the_total(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [
                ('a', True, (10, 1, 1, 5), [task('q', 1, 30, 50.0, {fine_key(200): 10, fine_key(210): 20}),
                                            task('q', 2, 5, 10.0, {fine_key(200): 5})], None),
                ('b', True, (10, 1, 1, 5), [task('q', 3, 40, 60.0, {fine_key(220): 40, 'le=+Inf': 0})], None),
            ])
            _, runs, _ = rc.load_campaign(root)
            queues = rc.aggregate_queues(runs)
            self.assertEqual(sum(queues['q']['age'].values()), 10 + 20 + 5 + 40)
            self.assertEqual(queues['q']['age'][fine_key(200)], 15)
            self.assertEqual(queues['q']['messages'], 75)

    def test_percentiles_come_from_merged_buckets_not_from_averaging_per_run_percentiles(self):
        run_a = {fine_key(100): 99, fine_key(400): 1}       # p50 at step 100
        run_b = {fine_key(400): 1}                          # p50 at step 400
        merged = rc.merge_histograms([{'format': 2, 'buckets': run_a}, {'format': 2, 'buckets': run_b}])
        p50_merged = rc.rr.percentile_from_buckets(merged, 0.5)
        averaged = (rc.rr.percentile_from_buckets(run_a, 0.5) + rc.rr.percentile_from_buckets(run_b, 0.5)) / 2
        self.assertEqual(p50_merged, float(fine_key(100)[3:]), '100 of 101 observations sit at step 100')
        self.assertNotAlmostEqual(p50_merged, averaged, places=3)

    def test_format_1_labels_are_repaired_before_merging(self):
        merged = rc.merge_histograms([{'buckets': {'le=284': 2}}, {'format': 2, 'buckets': {'le=2840': 3}}])
        self.assertEqual(merged, {'le=2840': 5})


class QueueMixTest(unittest.TestCase):
    """Spread of cost per message: over a queue's batches, inside each listener, between listeners."""

    @staticmethod
    def call(listener, messages, ms_per_msg, queue='q'):
        return {'queue': queue, 'listener': listener, 'event': 'e', 'calls': 1, 'messages': messages,
                'ms': messages * ms_per_msg, 'ms_per_msg': ms_per_msg}

    @staticmethod
    def batch(messages, ms, queue='q'):
        return {'queue': queue, 'processed': messages, 'ms_process': ms}

    def test_mixed_cheap_and_heavy_listeners_make_a_split_candidate(self):
        tasks = [self.batch(100, 10.0), self.batch(100, 400.0), self.batch(100, 30.0)]
        calls = [self.call('Cheap', 50, 0.1), self.call('Cheap', 50, 0.12),
                 self.call('Heavy', 50, 8.0), self.call('Heavy', 50, 9.0)]
        row = rc.queue_mix(tasks, calls)['q']
        self.assertAlmostEqual(row['batch_width'], 40.0)
        self.assertAlmostEqual(row['within'], 9 / 8, msg='weighted median: Heavy holds most of the time')
        self.assertGreater(row['between'], 50)
        self.assertEqual(row['reading'], 'split candidate')

    def test_one_listener_with_uneven_data_is_not_a_split_candidate(self):
        tasks = [self.batch(100, 10.0), self.batch(100, 200.0)]
        calls = [self.call('Only', 100, 0.1), self.call('Only', 100, 2.0)]
        row = rc.queue_mix(tasks, calls)['q']
        self.assertIsNone(row['between'], 'one listener has nothing to compare against')
        self.assertEqual(row['reading'], 'uneven data')

    def test_small_batches_are_left_out_because_the_fixed_cost_dominates(self):
        tasks = [self.batch(1, 500.0), self.batch(100, 10.0), self.batch(100, 12.0)]
        calls = [self.call('A', 1, 500.0), self.call('A', 100, 0.1), self.call('A', 100, 0.12)]
        row = rc.queue_mix(tasks, calls)['q']
        self.assertEqual(row['batches'], 2)
        self.assertAlmostEqual(row['batch_width'], 1.2)
        self.assertEqual(row['reading'], 'even')

    def test_mixed_listeners_that_are_also_uneven_are_named_both(self):
        tasks = [self.batch(100, 10.0), self.batch(100, 400.0)]
        calls = [self.call('Cheap', 100, 0.1), self.call('Heavy', 50, 2.0), self.call('Heavy', 50, 10.0)]
        self.assertEqual(rc.queue_mix(tasks, calls)['q']['reading'], 'mixed and uneven')

    def test_fewer_than_two_batches_has_no_width(self):
        row = rc.queue_mix([self.batch(100, 10.0)], [self.call('A', 100, 0.1)])['q']
        self.assertIsNone(row['batch_width'])
        self.assertEqual(row['reading'], 'not enough batches')


class OverheadTest(unittest.TestCase):

    def test_fewer_than_two_runs_on_a_side_reports_not_enough_runs(self):
        self.assertFalse(rc.overhead([10, 11, 12], [9])['enough'])
        self.assertFalse(rc.overhead([10], [9, 9])['enough'])
        self.assertNotIn('delta', rc.overhead([10], [9, 9]))
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [task('q', 1, 1, 1.0, {fine_key(200): 1})], None),
                                  ('i2', True, (12, 1, 1, 5), [task('q', 2, 1, 1.0, {fine_key(200): 1})], None),
                                  ('u1', False, (9, 1, 1, 5), [], None)])
            html = rc.render(root)
            overhead_html = html[html.index('<h2>Instrumentation overhead</h2>'):html.index('<h2>Data quality</h2>')]
            self.assertIn(rc.NOT_ENOUGH, overhead_html)
            self.assertNotIn('not distinguishable', overhead_html)
            self.assertNotIn('%)', overhead_html, 'no delta percentage is printed')

    def test_delta_inside_the_spread_is_not_distinguishable_from_noise(self):
        result = rc.overhead([10, 14, 12], [11, 13])
        self.assertTrue(result['enough'])
        self.assertEqual(result['delta'], 0)
        self.assertFalse(result['distinguishable'])
        self.assertTrue(rc.overhead([20, 21], [10, 11])['distinguishable'])


class NotMeasuredTest(unittest.TestCase):

    def test_missing_c3_and_c4_fields_render_as_not_measured_never_zero(self):
        self.assertIsNone(rc.breakdown_of([{'ms_total': 5.0}], 'ms_total'))
        self.assertIsNone(rc.sum_or_none([{'retried': None}, {}], 'retried'))
        self.assertEqual(rc.sum_or_none([{'retried': 0}, {}], 'retried'), 0, 'a measured 0 stays 0')
        bd = rc.breakdown_of([{'ms_total': 10.0, 'breakdown': {'db_ms': 4.0, 'db_calls': 2, 'kv_ms': None,
                                                               'search_ms': None, 'broker_ms': 1.0}}], 'ms_total')
        self.assertIsNone(bd['kv'])
        self.assertEqual(bd['other'], 5.0, 'other is the total minus the measured categories')
        self.assertEqual(rc.na(None, rc.fmt_int), rc.NOT_MEASURED)
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [task('q', 1, 3, 2.0, {fine_key(200): 3})], None)])
            html = rc.render(root)
            bottlenecks = html[html.index("<section id='bottlenecks'"):]
            row = bottlenecks[bottlenecks.index("<td class='name'>q</td>"):]
            row = row[:row.index('</tr>')]
            self.assertEqual(row.count(rc.NOT_MEASURED), 3, 'flush, retried and failed after retry')
            self.assertIn(f"Time breakdown {rc.NOT_MEASURED}", html)
            self.assertIn(f"Flush time {rc.NOT_MEASURED}", html)

    def test_sections_fold_with_a_summary_line_and_the_summary_stays_open(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [task('q', 1, 3, 2.0, {fine_key(200): 3})], None)],
                           story='The story.')
            html = rc.render(root)
            self.assertIn("<section id='summary'>", html)
            self.assertIn("<section id='story'>", html)
            folds = re.findall(r"<section id='([a-z-]+)' class='fold'><details><summary><h2>[^<]+</h2>"
                               r"<span class='one'>([^<]*)</span>", html)
            anchors = [a for a, _ in folds]
            for anchor in ('timeline', 'bottlenecks', 'quality', 'capacity'):
                self.assertIn(anchor, anchors)
                self.assertIn(f"href='#{anchor}'", html)
            self.assertTrue(all(line for _, line in folds), folds)

    def test_queue_service_shows_the_cadence_of_the_median_run(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            tasks = [task('q', 1, 3, 2.0, {fine_key(200): 3}), task('q', 2, 3, 2.0, {fine_key(200): 3})]
            tasks[1]['ts'] = tasks[0]['ts'] + 5
            write_campaign(root, [('i1', True, (10, 1, 1, 5), tasks, None)])
            html = rc.render(root)
            self.assertIn("<section id='service' class='fold'>", html)
            self.assertIn('Queue service cadence', html)
            self.assertIn('Run <code>i1</code>', html)

    def test_breakdown_with_a_null_category_names_it_as_not_measured(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            bd = {'db_ms': 1.0, 'db_calls': 1, 'kv_ms': 0.5, 'kv_calls': 1, 'search_ms': None, 'search_calls': None,
                  'broker_ms': 0.2, 'broker_calls': 1}
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [task('q', 1, 3, 2.0, {fine_key(200): 3}, breakdown=bd)], None)])
            html = rc.render(root)
            self.assertIn(f"search {rc.NOT_MEASURED}", html)


class FlushJoinTest(unittest.TestCase):

    def test_flushes_join_to_task_runs_by_pid(self):
        tasks = [task('sync.storage.product', 41, 10, 100.0, {}), task('publish', 42, 10, 50.0, {})]
        flushes = [
            {'kind': 'direct_sync_flush', 'pid': 41, 'context': 'queue', 'queue': None, 'ms': 30.0},
            {'kind': 'direct_sync_flush', 'pid': 42, 'context': 'queue', 'queue': 'stale-name', 'ms': 5.0},
            {'kind': 'direct_sync_flush', 'pid': 7, 'context': 'queue', 'queue': 'sync.search.cms', 'ms': 2.0},
            {'kind': 'direct_sync_flush', 'pid': 9, 'context': 'import', 'queue': None, 'ms': 400.0},
        ]
        by_queue, elsewhere = rc.join_flushes(tasks, flushes)
        self.assertEqual([f['ms'] for f in by_queue['sync.storage.product']], [30.0], 'pid join, record queue is null')
        self.assertEqual([f['ms'] for f in by_queue['publish']], [5.0], 'the pid wins over the record field')
        self.assertEqual([f['ms'] for f in by_queue['sync.search.cms']], [2.0], 'no task run: record field is the fallback')
        self.assertEqual([f['ms'] for f in elsewhere['import']], [400.0])
        self.assertNotIn('stale-name', by_queue)

    def test_true_cost_is_processing_plus_flush_and_absent_file_is_not_measured(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            flushes = [{'kind': 'direct_sync_flush', 'pid': 1, 'context': 'queue', 'queue': 'q', 'ms': 30.0,
                        'messages': 3, 'breakdown': None, 'error': None},
                       {'kind': 'direct_sync_flush', 'pid': 99, 'context': 'import', 'queue': None, 'ms': 500.0,
                        'messages': 9, 'breakdown': None, 'error': None}]
            write_campaign(root, [('a', True, (10, 1, 1, 5), [task('q', 1, 3, 20.0, {fine_key(200): 3})], flushes),
                                  ('b', True, (10, 1, 1, 5), [task('q', 1, 3, 20.0, {fine_key(200): 3})], None)])
            _, runs, _ = rc.load_campaign(root)
            queues = rc.aggregate_queues(runs)
            self.assertEqual(queues['q']['flush_ms'], 30.0)
            self.assertEqual(queues['q']['cost_ms'], 70.0)
            items = rc.ledger(runs[0])
            self.assertEqual(items['queue: q'], 50.0, 'ledger counts the flush in the queue cost')
            self.assertEqual(items['import: direct sync flush'], 500.0, 'import flush carved out of the remainder')
            self.assertNotIn('import: direct sync flush', rc.ledger(runs[1]))
            self.assertIsNone(rc.aggregate_queues(runs[1:])['q']['flush_ms'], 'no file: not measured, not 0')


class StoryTest(unittest.TestCase):

    def test_absent_story_renders_without_the_section(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [], None)])
            html = rc.render(root)
            self.assertNotIn("id='story'", html)
            self.assertIn('<h2>Data quality</h2>', html)

    def test_present_story_is_converted_and_escaped(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [], None)],
                           story="# Verdict\n\nSlow because **publish** and `sync`.\n\n- one\n- two <b>\n")
            html = rc.render(root)
            self.assertIn("id='story'", html)
            self.assertIn('<h2>Verdict</h2>', html)
            self.assertIn('<b>publish</b>', html)
            self.assertIn('<code>sync</code>', html)
            self.assertIn('<li>two &lt;b&gt;</li>', html)

    def test_markdown_pipe_table_becomes_a_table_with_numeric_columns_right_aligned(self):
        html = rc.markdown_to_html("Intro\n\n| Scenario | Campaign | 100x |\n|---|---|---|\n"
                                   "| Full republish | `20261028f` | 59 min (31 to 71 min) |\n| First load | `g` | 30 min |\n\nAfter")
        self.assertNotIn('|', html)
        self.assertIn("<th>Scenario</th><th>Campaign</th><th class='n'>100x</th>", html)
        self.assertIn("<td class='name'><code>20261028f</code></td>", html)
        self.assertIn("<td class='n'>59 min (31 to 71 min)</td>", html)
        self.assertTrue(html.startswith('<p>Intro</p>') and html.endswith('<p>After</p>'))

    def test_pipe_line_without_a_rule_row_stays_text(self):
        self.assertEqual(rc.markdown_to_html('| not a table |'), '<p>| not a table |</p>')

    def test_markdown_code_fence_and_wrapped_bullet(self):
        html = rc.markdown_to_html("- first\n  continued\n\n```\n<raw> **not bold**\n```\n")
        self.assertIn('<li>first continued</li>', html)
        self.assertIn('<pre><code>&lt;raw&gt; **not bold**</code></pre>', html)


class FailedRunTest(unittest.TestCase):

    def test_failed_run_is_listed_and_excluded(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('i1', True, (10, 1, 1, 5), [], None)])
            campaign = json.loads((root / 'campaign.json').read_text())
            campaign['runs'].append({'run_id': 'gone', 'instrumented': True, 'bundle': 'gone', 'status': 'failed',
                                     'exit_code': 1})
            (root / 'campaign.json').write_text(json.dumps(campaign))
            _, runs, _ = rc.load_campaign(root)
            self.assertEqual([r.ok for r in runs], [True, False])
            html = rc.render(root)
            self.assertIn('1 failed and excluded', html)


class CampaignMemoryTest(unittest.TestCase):

    def test_campaign_without_c7_shows_peak_and_not_measured_weighted_memory(self):
        tasks = [task('q', p, n, 10.0 + n / 10, {fine_key(200): 1}, peak_mb=30 + p) for p, n in ((1, 100), (2, 200), (3, 400))]
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('r1', True, (5, 1, 1, 3), tasks, None)])
            html = rc.render(root)
            memory = html[html.index('<h2>Memory'):]
            self.assertIn('Highest batch peak', memory)
            self.assertIn(f"Added per 100 messages</div><div class='v'>{rc.NOT_MEASURED}", memory)
            weighted = html[html.index('<h2>Weighted time'):html.index('<h2>Memory')]
            self.assertIn('Fixed and marginal cost per batch', weighted)

    def test_campaign_with_c7_merges_increments_over_runs_total_over_total(self):
        run_a = [task('q', 1, 100, 10.0, {fine_key(200): 1}, mem_start_mb=20.0, mem_peak_incr_mb=1.0, peak_mb=21.0)]
        run_b = [task('q', 1, 900, 10.0, {fine_key(200): 1}, mem_start_mb=20.0, mem_peak_incr_mb=3.0, peak_mb=23.0)]
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('r1', True, (5, 1, 1, 3), run_a, None), ('r2', True, (5, 1, 1, 3), run_b, None)])
            html = rc.render(root)
            self.assertIn("Added per 100 messages</div><div class='v'>410 KB", html, '4 MB over 1 000 messages = 409.6 KB')

    def test_campaign_render_never_overwrites(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            write_campaign(root, [('r1', True, (5, 1, 1, 3), [], None)])
            first = rc.rr.write_report(rc.render, root, None, ['render-campaign.py'])
            before = first.read_bytes()
            second = rc.rr.write_report(rc.render, root, None, ['render-campaign.py'])
            self.assertNotEqual(first, second)
            self.assertEqual(first.read_bytes(), before)
            with self.assertRaises(FileExistsError):
                rc.rr.write_report(rc.render, root, first, ['render-campaign.py'])


class StorageSamplerTest(unittest.TestCase):

    def test_keys_are_summed_over_every_database(self):
        out = "# Keyspace\r\ndb0:keys=3,expires=0\r\ndb1:keys=17113,expires=0\r\n---SEARCH---\n{\"count\":1130,\"_shards\":{}}"
        self.assertEqual(sampler.parse_probe(out), (17116, 1130))

    def test_an_empty_store_is_zero_and_an_unreadable_one_is_null(self):
        self.assertEqual(sampler.parse_probe("# Keyspace\n---SEARCH---\n{\"count\":0}"), (0, 0))
        self.assertEqual(sampler.parse_probe("---SEARCH---\n"), (None, None))
        self.assertEqual(sampler.parse_probe(""), (None, None))


if __name__ == '__main__':
    unittest.main(verbosity=2)
