"""Tests for render-sweep.py and the selection in batch-sweep.py. Stdlib unittest; no environment."""

import importlib.util
import json
import pathlib
import tempfile
import unittest

HERE = pathlib.Path(__file__).resolve().parent


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


rs = _load('render_sweep', 'render-sweep.py')
bs = _load('batch_sweep', 'batch-sweep.py')


def batch(n, wall, mem=None, db=None, bytes_per_msg=200):
    return {'chunk': n, 'n': n, 'ms_total': wall * 0.8, 'flush_ms': 0.0, 'wall_ms': wall, 'mem_mb': mem,
            'mem_start_mb': 4, 'cpu_ms': wall * 0.7, 'db_calls': db, 'search_calls': 0, 'kv_calls': 0,
            'bytes': bytes_per_msg * n}


class QueueModelTest(unittest.TestCase):

    def test_knee_is_where_the_fixed_cost_is_ten_percent_of_the_per_message_cost(self):
        # wall = 100 ms + 1 ms per message: fixed / (0.1 x marginal) = 1000
        model = rs.queue_model([batch(n, 100 + n, mem=2 + 0.01 * n) for n in (10, 100, 1000, 2000)])
        self.assertAlmostEqual(model['knee'], 1000, places=3)
        self.assertEqual(model['chunk'], 1000)
        self.assertEqual(model['reason'], 'knee')
        self.assertFalse(model['extrapolated'])

    def test_memory_limit_lowers_the_chunk_to_a_common_value_below_it(self):
        # 0.25 MB per message: (153.6 - 4 start - 2 fixed) / 0.25 = 590.4, knee 1000
        model = rs.queue_model([batch(n, 100 + n, mem=2 + 0.25 * n) for n in (10, 100, 300, 400)])
        self.assertAlmostEqual(model['limits']['memory'], 590.4, places=3)
        self.assertEqual(model['chunk'], 500)
        self.assertEqual(model['reason'], 'memory limit')

    def test_payload_limit_applies_at_one_megabyte(self):
        model = rs.queue_model([batch(n, 100 + n, mem=1, bytes_per_msg=5000) for n in (10, 100, 150)])
        self.assertAlmostEqual(model['limits']['payload'], 1024 * 1024 / 5000)
        self.assertEqual(model['chunk'], 100)
        self.assertEqual(model['reason'], 'payload limit')
        self.assertEqual(model['chunk_no_payload'], 1000, 'the knee when only memory and time apply')

    def test_chunk_above_the_largest_measured_batch_is_marked_extrapolated(self):
        model = rs.queue_model([batch(n, 1000 + n, bytes_per_msg=50) for n in (10, 50, 100)])
        self.assertEqual(model['chunk'], 10000)
        self.assertTrue(model['extrapolated'])

    def test_u_shape_in_the_measured_cost_lowers_the_chunk_to_the_measured_best(self):
        # fit knee is far above 500, but the time per message rises again above 500
        per_msg = {10: 3.0, 100: 1.2, 500: 1.0, 1000: 1.3, 2000: 1.6}
        model = rs.queue_model([batch(n, per_msg[n] * n, bytes_per_msg=50) for n in per_msg for _ in range(2)])
        self.assertEqual(model['measured']['shape'], 'U-shape')
        self.assertEqual(model['measured']['best_size'], 500)
        self.assertEqual(model['chunk'], 500)
        self.assertEqual(model['reason'], 'measured optimum')

    def test_falling_measured_cost_keeps_the_knee_and_reports_where_it_comes_near_the_best(self):
        model = rs.queue_model([batch(n, 100 + n, mem=2 + 0.01 * n) for n in (10, 100, 1000, 2000) for _ in range(2)])
        self.assertEqual(model['measured']['shape'], 'falls')
        self.assertEqual(model['measured']['best_size'], 2000)
        self.assertEqual(model['measured']['near_size'], 1000)
        self.assertEqual(model['reason'], 'knee')

    def test_one_batch_size_gives_no_recommendation(self):
        model = rs.queue_model([batch(30, 50), batch(30, 52), batch(30, 51)])
        self.assertIsNone(model['chunk'])
        self.assertEqual(model['reason'], 'not enough batch sizes')

    def test_common_values_round_up_and_down(self):
        self.assertEqual(rs.nice_up(101), 250)
        self.assertEqual(rs.nice_down(499), 250)
        self.assertIsNone(rs.nice_down(5))


class SelectionTest(unittest.TestCase):

    def test_score_adds_time_cpu_and_memory_terms_and_skips_queues_without_a_producer(self):
        with tempfile.TemporaryDirectory() as tmp:
            run = pathlib.Path(tmp) / 'r01-instr'
            run.mkdir()
            rows = [{'queue': 'publish', 'ms_process': 90, 'cpu_user_ms': 80, 'cpu_sys_ms': 0, 'mem_peak_incr_mb': 10,
                     'processed': 100, 'chunk_configured': 500},
                    {'queue': 'sync.storage.x', 'ms_process': 10, 'cpu_user_ms': 20, 'cpu_sys_ms': 0,
                     'mem_peak_incr_mb': 100, 'processed': 1000, 'chunk_configured': 10000},
                    {'queue': 'event', 'ms_process': 900, 'cpu_user_ms': 900, 'cpu_sys_ms': 0, 'mem_peak_incr_mb': 1,
                     'processed': 1, 'chunk_configured': 500}]
            (run / 'task-runs.jsonl').write_text(''.join(json.dumps(r) + '\n' for r in rows))
            selection, runs = bs.select_queues([tmp], 10)
        self.assertEqual(runs, 1)
        self.assertEqual([s['queue'] for s in selection], ['sync.storage.x', 'publish'])
        self.assertAlmostEqual(selection[0]['score'], 10 / 1000 + 20 / 1000 + 1.0)


class LoadTest(unittest.TestCase):

    def test_empty_task_writes_no_record_and_flush_joins_by_pid(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = pathlib.Path(tmp)
            step = root / 'q' / '10'
            step.mkdir(parents=True)
            (root / 'meta.json').write_text('{}')
            (root / 'selection.json').write_text('[]')
            (root / 'steps.jsonl').write_text(json.dumps({'queue': 'q', 'chunk': 10, 'tasks': [
                {'wall_ms': 120.0}, {'wall_ms': 110.0}, {'wall_ms': 90.0}]}) + '\n')
            records = [{'pid': 1, 'processed': 10, 'ms_total': 20.0, 'breakdown': {'db_calls': 5}},
                       {'pid': 2, 'processed': 4, 'ms_total': 8.0, 'breakdown': {'db_calls': 2}}]
            (step / 'task-runs.jsonl').write_text(''.join(json.dumps(r) + '\n' for r in records))
            (step / 'sync-flush-runs.jsonl').write_text(json.dumps({'pid': 2, 'ms': 3.0}) + '\n')
            _, _, _, batches = rs.load_sweep(root)
        self.assertEqual([(b['n'], b['wall_ms'], b['flush_ms']) for b in batches['q']], [(10, 120.0, 0.0), (4, 110.0, 3.0)])


if __name__ == '__main__':
    unittest.main()
