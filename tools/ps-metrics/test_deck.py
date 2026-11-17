#!/usr/bin/env python3
"""Self-test for render-deck.py. Run: python3 tools/ps-metrics/test_deck.py
(or: python3 tools/ps-metrics/render-deck.py --self-test). Stdlib only.

Fixtures come from the existing tests (test_campaign.write_campaign: a campaign without breakdown,
memory or import records; test_capacity_model.write_fixture: a campaign a model can be built from).
The campaign 20261028c checks run only when that directory and its model exist."""

import html.parser
import importlib.util
import json
import pathlib
import tempfile
import unittest

HERE = pathlib.Path(__file__).resolve().parent
CAMPAIGN_C = HERE.parent.parent / 'data' / 'metrics' / 'campaign-20261028c'
MODEL_C = CAMPAIGN_C / 'model-20261028-202234.json'


def _load(name, filename):
    spec = importlib.util.spec_from_file_location(name, HERE / filename)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


deck = _load('render_deck_under_test', 'render-deck.py')
fixtures_campaign = _load('test_campaign_fixtures', 'test_campaign.py')
fixtures_model = _load('test_capacity_model_fixtures', 'test_capacity_model.py')
cm = deck.cm

BLOCK_LIMIT = 40


class SlideParser(html.parser.HTMLParser):
    """Per slide: the h1 texts, every text block (the direct text of one element, notes excluded), the
    whole visible text and the data-pred values."""

    def __init__(self):
        super().__init__()
        self.slides = []
        self.stack = []
        self.cur = None
        self.in_notes = 0
        self.in_h1 = False
        self.in_script = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == 'script':
            self.in_script = True
        if tag == 'section' and 'slide' in (a.get('class') or '').split():
            self.cur = {'name': a.get('data-name'), 'h1': [], 'blocks': [], 'text': [], 'pred': {}}
            self.slides.append(self.cur)
        if self.cur is None:
            return
        if tag == 'aside' and 'notes' in (a.get('class') or ''):
            self.in_notes += 1
        if tag == 'h1':
            self.in_h1 = True
            self.cur['h1'].append('')
        if 'data-pred' in a:
            self.cur['pred'][a['data-pred']] = float(a['data-value'])
        self.stack.append([tag, ''])

    def handle_endtag(self, tag):
        if tag == 'script':
            self.in_script = False
        if self.cur is None or not self.stack:
            return
        open_tag, text = self.stack.pop()
        if text.strip() and not self.in_notes:
            self.cur['blocks'].append(text.strip())
        if tag == 'h1':
            self.in_h1 = False
        if tag == 'aside' and self.in_notes:
            self.in_notes -= 1
        if tag == 'section':
            self.cur = None

    def handle_data(self, data):
        if self.cur is None or self.in_script:
            return
        if self.stack:
            self.stack[-1][1] += data
        if self.in_h1:
            self.cur['h1'][-1] += data
        if not self.in_notes:
            self.cur['text'].append(data)


def parse(page):
    parser = SlideParser()
    parser.feed(page)
    return parser.slides


def text_of(slide):
    return ' '.join(' '.join(slide['text']).split())


class StructureMixin:
    """Rules every slide of every deck must keep."""

    def assert_slide_rules(self, page):
        slides = parse(page)
        self.assertGreaterEqual(len(slides), 10)
        for s in slides:
            self.assertEqual(len(s['h1']), 1, f"slide {s['name']}: exactly one headline")
            words = len(s['h1'][0].split())
            self.assertTrue(0 < words < 12, f"slide {s['name']}: headline of {words} words: {s['h1'][0]!r}")
            for block in s['blocks']:
                self.assertLessEqual(len(block.split()), BLOCK_LIMIT,
                                     f"slide {s['name']}: text block over {BLOCK_LIMIT} words: {block!r}")
        return slides


@unittest.skipUnless(MODEL_C.exists(), 'campaign 20261028c with its model is not on this machine')
class Campaign20261028cTest(unittest.TestCase, StructureMixin):

    @classmethod
    def setUpClass(cls):
        cls.page = deck.render([CAMPAIGN_C], MODEL_C)
        cls.model = json.loads(MODEL_C.read_text())

    def test_every_slide_has_one_short_headline_and_no_long_text_block(self):
        self.assert_slide_rules(self.page)

    def test_prediction_numbers_equal_the_model_predict_output(self):
        slides = {s['name']: s for s in parse(self.page)}
        checked = 0
        for name in ('prediction', 'resources'):
            for key, value in slides[name]['pred'].items():
                size, field, which = key.split('.')
                factor = dict((k, f) for k, _, f in deck.SIZES)[size]
                expected = cm.predict(self.model, deck.size_inputs(self.model, factor))[field]
                expected = expected[which] if isinstance(expected, dict) else expected
                self.assertAlmostEqual(value, expected, places=9, msg=key)
                checked += 1
        self.assertGreaterEqual(checked, 3 * 7, 'import, P&S and total per size, plus the resource panels')
        # The visible text is the formatted predict() value.
        p100 = cm.predict(self.model, deck.size_inputs(self.model, 100))
        text = text_of(slides['prediction'])
        for field in ('import_s', 'ps_s', 'total_s'):
            self.assertIn(deck.fmt_s(p100[field]['value']), text)

    def test_search_warning_is_on_the_slides_that_use_search_figures(self):
        slides = {s['name']: text_of(s) for s in parse(self.page)}
        for name in ('pipeline', 'breakdown', 'prediction'):
            self.assertIn('Search figures not valid', slides[name], name)

    def test_one_scale_gives_the_placeholder_slide(self):
        slides = {s['name']: s for s in parse(self.page)}
        self.assertEqual(slides['scaling']['h1'][0], 'One scale measured, so scaling is assumed linear.')


class MissingDataTest(unittest.TestCase, StructureMixin):
    """A campaign without breakdown, memory, listener or import records, so no model can be built."""

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = pathlib.Path(self.tmp.name) / 'campaign-bare'
        self.root.mkdir()
        task = fixtures_campaign.task
        fixtures_campaign.write_campaign(self.root, [
            ('a', True, (10, 1, 1, 5), [task('q', 1, 30, 50.0, {}), task('q', 2, 40, 60.0, {})], None),
            ('b', False, (11, 1, 1, 5), None, None),
        ])

    def tearDown(self):
        self.tmp.cleanup()

    def test_missing_figures_render_not_measured_never_zero(self):
        page = deck.render([self.root])
        slides = {s['name']: text_of(s) for s in self.assert_slide_rules(page)}
        for name in ('title', 'breakdown', 'listeners', 'queries', 'memory', 'prediction', 'resources', 'inputs'):
            self.assertIn(deck.NOT_MEASURED, slides[name], name)
        self.assertNotIn('0 ms worker time', slides['breakdown'])
        self.assertIn(deck.NOT_MEASURED, slides['pipeline'], 'events and storage keys are not in the records')
        self.assertNotIn('data-pred', page)


class ModelFixtureTest(unittest.TestCase, StructureMixin):

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = pathlib.Path(self.tmp.name) / 'campaign-fixture'
        self.root.mkdir()
        fixtures_model.write_fixture(self.root)

    def tearDown(self):
        self.tmp.cleanup()

    def test_in_memory_model_renders_and_matches_predict(self):
        page = deck.render([self.root])
        slides = {s['name']: s for s in self.assert_slide_rules(page)}
        model = cm.build_model([self.root])
        for key, value in slides['prediction']['pred'].items():
            size, field, which = key.split('.')
            factor = dict((k, f) for k, _, f in deck.SIZES)[size]
            self.assertAlmostEqual(value, cm.predict(model, deck.size_inputs(model, factor))[field][which], places=9)

    def test_scenarios_slide_only_with_companion_campaigns(self):
        names = [s['name'] for s in self.assert_slide_rules(deck.render([self.root]))]
        self.assertNotIn('scenarios', names)
        other = self.root.parent / 'campaign-other'
        other.mkdir()
        fixtures_model.write_fixture(other, campaign_id='other')
        for bundle in other.iterdir():
            if bundle.is_dir():
                meta = json.loads((bundle / 'meta.json').read_text())
                (bundle / 'meta.json').write_text(json.dumps(dict(meta, import_path='insert')))
        (self.root / 'companions.json').write_text(json.dumps({'campaigns': ['../campaign-other']}))
        slides = {s['name']: s for s in self.assert_slide_rules(deck.render([self.root]))}
        self.assertIn('scenarios', slides)
        self.assertIn('First load', slides['scenarios']['text'])


class NeverOverwriteTest(unittest.TestCase):

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = pathlib.Path(self.tmp.name) / 'campaign-out'
        self.root.mkdir()
        fixtures_model.write_fixture(self.root)

    def tearDown(self):
        self.tmp.cleanup()

    def test_explicit_existing_path_is_refused_and_left_unchanged(self):
        target = pathlib.Path(self.tmp.name) / 'deck.html'
        target.write_text('original')
        with self.assertRaises(FileExistsError):
            deck.write_deck([self.root], explicit=target)
        self.assertEqual(target.read_text(), 'original')
        self.assertEqual(deck.main(['render-deck.py', str(self.root), '--out', str(target)]), 2)
        self.assertEqual(target.read_text(), 'original')

    def test_default_names_are_versioned_and_never_reused(self):
        first = deck.write_deck([self.root])
        second = deck.write_deck([self.root], now=None)
        self.assertNotEqual(first, second)
        self.assertTrue(first.name.startswith('deck-') and first.suffix == '.html')
        self.assertTrue(first.exists() and second.exists())
        self.assertIn(first.name, first.read_text(), 'the deck names its own file')


if __name__ == '__main__':
    unittest.main(verbosity=2)
