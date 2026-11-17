#!/usr/bin/env python3
"""SOL-629 scaled import subsets (contract C8).

Builds a referentially consistent subset of an import set at a given fraction of its abstract
products, plus a data:import config that points at it, so a campaign can run the same pipeline at
0.25, 0.5, 0.75 and 1.0 of the catalog and show whether cost is linear in size.

Commands:

    scale-import.py build --fraction 0.25 [--config data/import/local/full_EU.yml] [--out DIR] [--rebuild]
    scale-import.py verify DIR                      re-run the static reference check on a built subset
    scale-import.py check-log IMPORT_LOG [--config YML] [--json]
                                                    prove an import: every importer successful, no skipped rows

How the subset stays consistent:

1. The unit of selection is the abstract product. Abstract SKUs are ranked by a stable hash and taken
   in that order, so the 0.25 subset is contained in the 0.5 subset, and the same fraction always
   gives the same products.
2. Some abstracts are pinned because data that stays complete names them: product sets, content
   product lists, CMS slot conditions and discount promotion SKUs. A kept bundle pins the abstracts
   of its bundled items, and a kept packaging unit pins the abstract of its lead product. Pinning is a
   closure: it repeats until nothing new is pinned.
3. Every product-dependent row is kept only when the product it names is kept: concretes by their
   abstract, then images, prices, stock, store relations, offers by their concrete, then everything
   keyed by an offer reference, by a kept sales unit key, a kept relation key or a kept shopping list
   item key. List cells (label and option SKU lists, relation rule SKU lists) are filtered in place.
4. Everything else (stores, locales, currencies, categories, glossary, CMS, merchants, customers,
   companies, discounts, product lists) stays complete: the config points at the original file.
5. The built subset is checked statically: every product, offer and key reference in every written
   file must resolve inside the subset. The same check runs on the full set first, so a rule that
   misclassifies a column fails loudly instead of passing silently. A non-empty dangling list stops
   the build. Customer carts and quote requests carry SKUs only inside opaque JSON that no importer
   resolves; they stay complete and their references are counted in the manifest, not enforced.

Stdlib only. Writes nothing outside the output directory.
"""

import argparse
import csv
import hashlib
import io
import json
import pathlib
import re
import shutil
import sys
import time

SCRIPT_VERSION = 1
DEFAULT_CONFIG = 'data/import/local/full_EU.yml'
DEFAULT_OUT_ROOT = 'data/metrics/scaled'

csv.field_size_limit(sys.maxsize)

# Per data entity: how its rows depend on products. Keys:
#   abstract / concrete / offer / any_sku : column(s) naming a product or offer; the row is kept only when
#                                           every non-empty one is kept (any_sku: abstract or concrete)
#   either                               : (abstract column, concrete column); the row names one of them
#   list_abstract                        : columns holding comma-separated abstract SKUs, filtered in place
#   pin_abstract / pin_any               : columns of complete data that pin the products they name
#   key_in / key_out                     : (column, key space): row kept only if the key is kept / row
#                                          contributes its key to the kept set of that key space
#   drop_if_empty                        : list column that, once filtered to empty, drops the row
RULES = {
    'product-abstract': {'abstract': ['abstract_sku']},
    'product-concrete': {'abstract': ['abstract_sku']},
    'product-image': {'either': ('abstract_sku', 'concrete_sku'), 'key_out': ('product_image_set_key', 'image_set')},
    'product-attachment': {'abstract': ['abstract_sku']},
    'product-price': {'either': ('abstract_sku', 'concrete_sku')},
    'product-price-schedule': {'either': ('abstract_sku', 'concrete_sku')},
    'product-abstract-store': {'abstract': ['abstract_sku']},
    'product-stock': {'concrete': ['concrete_sku']},
    'product-approval-status': {'abstract': ['sku']},
    'product-configuration': {'concrete': ['concrete_sku']},
    'product-option': {'list_abstract': ['abstract_product_skus']},
    'gift-card-abstract-configuration': {'abstract': ['abstract_sku']},
    'gift-card-concrete-configuration': {'concrete': ['sku']},
    'product-measurement-base-unit': {'abstract': ['abstract_sku']},
    'product-measurement-sales-unit': {'concrete': ['concrete_sku'], 'key_out': ('sales_unit_key', 'sales_unit')},
    'product-measurement-sales-unit-store': {'key_in': ('sales_unit_key', 'sales_unit')},
    'product-packaging-unit': {'concrete': ['concrete_sku', 'lead_product_sku']},
    'product-list-product-concrete': {'concrete': ['concrete_sku']},
    'product-group': {'abstract': ['abstract_sku']},
    'product-relation': {'abstract': ['product'], 'relation_rule': 'rule', 'key_out': ('product_relation_key', 'relation')},
    'product-relation-store': {'key_in': ('product_relation_key', 'relation')},
    'product-review': {'abstract': ['abstract_product_sku']},
    'product-label': {'list_abstract': ['product_abstract_skus']},
    'product-set': {'pin_abstract': ['abstract_skus']},
    'product-discontinued': {'concrete': ['sku_concrete']},
    'product-alternative': {'concrete': ['concrete_sku', 'alternative_product_concrete_sku'],
                            'abstract': ['alternative_product_abstract_sku']},
    'product-quantity': {'concrete': ['concrete_sku']},
    'discount': {'pin_abstract': ['promotion_sku']},
    'content-product-abstract-list': {'pin_abstract': ['skus.*']},
    'cms-slot-block': {'pin_abstract': ['conditions.productCategory.skus']},
    'product-price-merchant-relationship': {'either': ('abstract_sku', 'concrete_sku')},
    'shopping-list-item': {'concrete': ['product_sku'], 'key_out': ('shopping_list_item_key', 'shopping_list_item')},
    'product-offer-shopping-list-item': {'offer': ['product_offer_reference'],
                                         'key_in': ('shopping_list_item_key', 'shopping_list_item')},
    'product-shipment-type': {'concrete': ['concrete_sku']},
    'merchant-product': {'abstract': ['sku']},
    'merchant-product-offer': {'concrete': ['concrete_sku']},
    'product-offer-validity': {'offer': ['product_offer_reference']},
    'price-product-offer': {'offer': ['product_offer_reference']},
    'merchant-product-offer-store': {'offer': ['product_offer_reference']},
    'product-offer-stock': {'offer': ['product_offer_reference']},
    'product-offer-shipment-type': {'offer': ['product_offer_reference']},
    'product-offer-service': {'offer': ['product_offer_reference']},
    'product-to-product-class': {'concrete': ['sku']},
    'configurable-bundle-template-image': {'check_key': ('product_image_set_key', 'image_set')},
}

# Entities whose files hold SKUs only inside opaque JSON that the importer stores as is.
OPAQUE_JSON = {'multi-cart': 'quote_data', 'quote-request-version': 'quote'}
_OPAQUE_SKU = re.compile(r'"(?:sku|abstractSku|abstract_sku)"\s*:\s*"([^"]+)"')

# A column name that looks like a product or offer reference, for entities without a rule: a config
# other than full_EU.yml may contain one, and a silent pass-through would break consistency.
_SUSPICIOUS = re.compile(r'(^|_)(sku|skus|abstract_sku|concrete_sku|product_offer_reference)($|_|\.)')
# Columns that match the pattern but name something else: an option value SKU, a merchant's own SKU.
NOT_PRODUCT_COLUMNS = {('product-option-price', 'product_option_sku'),
                       ('merchant-product-option-group', 'merchant_sku')}


def split_list(value):
    return [part.strip() for part in value.split(',') if part.strip()]


def stable_rank(sku):
    return hashlib.sha1(sku.encode('utf-8')).hexdigest()


def read_config(config_path):
    """Return the config text and its actions as [(data_entity, source)], in file order."""
    text = pathlib.Path(config_path).read_text(encoding='utf-8')
    actions = []
    entity = None
    for line in text.splitlines():
        match = re.match(r'^\s*-?\s*data_entity:\s*(\S+)\s*$', line)
        if match:
            entity = match.group(1)
            continue
        match = re.match(r'^\s*source:\s*(\S+)\s*$', line)
        if match and entity is not None:
            actions.append((entity, match.group(1)))
            entity = None
    return text, actions


class Table:
    """One CSV file read whole; keeps the BOM and the header so the written file matches the original."""

    def __init__(self, path):
        raw = pathlib.Path(path).read_bytes()
        self.bom = raw.startswith(b'\xef\xbb\xbf')
        text = raw.decode('utf-8-sig')
        self.lineterminator = '\r\n' if '\r\n' in text[:4096] else '\n'
        reader = csv.reader(io.StringIO(text, newline=''))
        self.header = next(reader, [])
        self.rows = [row for row in reader if any(cell.strip() for cell in row)]
        self.bytes = len(raw)

    def col(self, name):
        return self.header.index(name) if name in self.header else None

    def cols(self, pattern):
        if pattern.endswith('.*'):
            prefix = pattern[:-1]
            return [i for i, name in enumerate(self.header) if name.startswith(prefix)]
        index = self.col(pattern)
        return [] if index is None else [index]

    def cell(self, row, index):
        return row[index].strip() if index is not None and index < len(row) else ''

    def write(self, path, rows):
        buffer = io.StringIO(newline='')
        writer = csv.writer(buffer, lineterminator=self.lineterminator)
        writer.writerow(self.header)
        writer.writerows(rows)
        data = buffer.getvalue().encode('utf-8')
        if self.bom:
            data = b'\xef\xbb\xbf' + data
        pathlib.Path(path).parent.mkdir(parents=True, exist_ok=True)
        pathlib.Path(path).write_bytes(data)
        return len(data)


def relation_rule_skus(rule):
    """The SKU list inside a product relation rule, or None when the rule has no product_sku clause."""
    try:
        data = json.loads(rule)
    except ValueError:
        return None
    found = []

    def walk(node):
        if isinstance(node, dict):
            if node.get('field') == 'product_sku' and isinstance(node.get('value'), str):
                found.append(node)
            for value in node.values():
                walk(value)
        elif isinstance(node, list):
            for value in node:
                walk(value)

    walk(data)
    return data, found


def load(actions):
    tables = {}
    for entity, source in actions:
        if source not in tables:
            tables[source] = Table(source)
    return tables


def catalog(actions, tables):
    """Full product graph: abstracts, concretes by abstract, offers by concrete, bundle and lead edges."""
    abstracts, concrete_of, offers_of = set(), {}, {}
    edges = {}  # concrete sku -> concrete skus it needs (bundled items, packaging lead)
    for entity, source in actions:
        table = tables[source]
        if entity == 'product-abstract':
            index = table.col('abstract_sku')
            abstracts.update(table.cell(row, index) for row in table.rows if table.cell(row, index))
        elif entity == 'product-concrete':
            a, c, b = table.col('abstract_sku'), table.col('concrete_sku'), table.col('bundled')
            for row in table.rows:
                concrete = table.cell(row, c)
                concrete_of[concrete] = table.cell(row, a)
                for item in split_list(table.cell(row, b)):
                    if '/' in item:
                        edges.setdefault(concrete, set()).add(item.split('/', 1)[0].strip())
        elif entity == 'product-packaging-unit':
            c, lead = table.col('concrete_sku'), table.col('lead_product_sku')
            for row in table.rows:
                if table.cell(row, lead):
                    edges.setdefault(table.cell(row, c), set()).add(table.cell(row, lead))
        elif entity == 'merchant-product-offer':
            o, c = table.col('product_offer_reference'), table.col('concrete_sku')
            for row in table.rows:
                offers_of[table.cell(row, o)] = table.cell(row, c)
    return abstracts, concrete_of, offers_of, edges


def pins(actions, tables, abstracts, concrete_of):
    """Abstracts named by data that stays complete, with the reason for each."""
    pinned = {}
    for entity, source in actions:
        rule = RULES.get(entity, {})
        table = tables[source]
        for pattern in rule.get('pin_abstract', []):
            for index in table.cols(pattern):
                for row in table.rows:
                    for sku in split_list(table.cell(row, index)):
                        abstract = sku if sku in abstracts else concrete_of.get(sku)
                        if abstract:
                            pinned.setdefault(abstract, set()).add(entity)
    return pinned


def select(fraction, abstracts, concrete_of, edges, pinned):
    concretes_by_abstract = {}
    for concrete, abstract in concrete_of.items():
        concretes_by_abstract.setdefault(abstract, []).append(concrete)

    kept = set()

    def add(abstract):
        queue = [abstract]
        while queue:
            current = queue.pop()
            if current in kept or current not in abstracts:
                continue
            kept.add(current)
            for concrete in concretes_by_abstract.get(current, []):
                for needed in edges.get(concrete, ()):
                    if concrete_of.get(needed):
                        queue.append(concrete_of[needed])

    for abstract in sorted(pinned):
        add(abstract)
    pinned_closure = set(kept)
    target = round(fraction * len(abstracts))
    for abstract in sorted(abstracts, key=stable_rank):
        if len(kept) >= target:
            break
        add(abstract)
    return kept, pinned_closure, target


def filter_tables(actions, tables, kept_abstracts, concrete_of, offers_of, universe=None):
    """Apply the rules in config order. Key spaces fill as their producing entity is processed.

    `universe` is the full set of abstracts. A relation rule token outside it is not a product in
    either set (the demo data has `045.046`); it is kept as it is, since the importer stores the rule
    as a query and never resolves it.
    """
    universe = kept_abstracts if universe is None else universe
    kept_concretes = {c for c, a in concrete_of.items() if a in kept_abstracts}
    kept_offers = {o for o, c in offers_of.items() if c in kept_concretes}
    keys = {}
    produced = {}  # source -> (rows, changed)
    for entity, source in actions:
        rule = RULES.get(entity)
        table = tables[source]
        if rule is None:
            continue
        out_rows, changed = [], False
        for original in table.rows:
            row = list(original)
            keep = True
            for name in rule.get('abstract', []):
                value = table.cell(row, table.col(name))
                if value and value not in kept_abstracts:
                    keep = False
            for name in rule.get('concrete', []):
                value = table.cell(row, table.col(name))
                if value and value not in kept_concretes:
                    keep = False
            for name in rule.get('offer', []):
                value = table.cell(row, table.col(name))
                if value and value not in kept_offers:
                    keep = False
            if 'either' in rule:
                abstract = table.cell(row, table.col(rule['either'][0]))
                concrete = table.cell(row, table.col(rule['either'][1]))
                if abstract and abstract not in kept_abstracts:
                    keep = False
                if concrete and concrete not in kept_concretes:
                    keep = False
            if 'key_in' in rule:
                column, space = rule['key_in']
                if table.cell(row, table.col(column)) not in keys.get(space, set()):
                    keep = False
            if keep:
                for name in rule.get('list_abstract', []):
                    index = table.col(name)
                    value = table.cell(row, index)
                    if value:
                        filtered = [s for s in split_list(value) if s in kept_abstracts]
                        if filtered != split_list(value):
                            row[index] = ','.join(filtered)
                if 'relation_rule' in rule:
                    index = table.col(rule['relation_rule'])
                    parsed = relation_rule_skus(table.cell(row, index))
                    if parsed:
                        data, clauses = parsed
                        for clause in clauses:
                            skus = [s for s in split_list(clause['value'])
                                    if s in kept_abstracts or s not in universe]
                            if not any(s in kept_abstracts for s in skus):
                                keep = False
                            clause['value'] = ','.join(skus)
                        if keep and clauses:
                            row[index] = json.dumps(data, separators=(',', ':'), ensure_ascii=False)
            if keep and 'key_out' in rule:
                column, space = rule['key_out']
                key = table.cell(row, table.col(column))
                if key:
                    keys.setdefault(space, set()).add(key)
            if keep:
                out_rows.append(row)
            if not keep or row != original:
                changed = True
        if source in produced:
            continue
        produced[source] = (out_rows, changed)
    return produced, kept_concretes, kept_offers, keys


def validate(actions, tables, rows_by_source, abstracts, concretes, offers, keys, universe=None):
    """Every reference in the written rows must resolve inside the same set. Returns dangling refs.

    Relation rule tokens outside `universe` (the full set) are source anomalies, not references.
    """
    universe = abstracts if universe is None else universe
    dangling = []

    def miss(entity, source, kind, value):
        dangling.append({'data_entity': entity, 'source': source, 'kind': kind, 'value': value})

    concrete_set = set(concretes)
    for entity, source in actions:
        table = tables[source]
        rows = rows_by_source.get(source, table.rows)
        rule = RULES.get(entity)
        if rule is None:
            if entity not in OPAQUE_JSON:
                suspicious = [name for name in table.header
                              if _SUSPICIOUS.search(name) and (entity, name) not in NOT_PRODUCT_COLUMNS]
                if suspicious:
                    miss(entity, source, 'unclassified-column', ','.join(suspicious))
            continue
        for row in rows:
            for name in rule.get('abstract', []) + rule.get('list_abstract', []):
                for sku in split_list(table.cell(row, table.col(name))):
                    if sku not in abstracts:
                        miss(entity, source, 'abstract', sku)
            for name in rule.get('concrete', []):
                value = table.cell(row, table.col(name))
                if value and value not in concrete_set:
                    miss(entity, source, 'concrete', value)
            for name in rule.get('offer', []):
                value = table.cell(row, table.col(name))
                if value and value not in offers:
                    miss(entity, source, 'offer', value)
            if 'either' in rule:
                abstract = table.cell(row, table.col(rule['either'][0]))
                concrete = table.cell(row, table.col(rule['either'][1]))
                if abstract and abstract not in abstracts:
                    miss(entity, source, 'abstract', abstract)
                if concrete and concrete not in concrete_set:
                    miss(entity, source, 'concrete', concrete)
            for pattern in rule.get('pin_abstract', []):
                for index in table.cols(pattern):
                    for sku in split_list(table.cell(row, index)):
                        if sku not in abstracts and sku not in concrete_set:
                            miss(entity, source, 'pinned', sku)
            for key_rule in ('key_in', 'check_key'):
                if key_rule in rule:
                    column, space = rule[key_rule]
                    value = table.cell(row, table.col(column))
                    if value and value not in keys.get(space, set()):
                        miss(entity, source, space, value)
            if 'relation_rule' in rule:
                parsed = relation_rule_skus(table.cell(row, table.col(rule['relation_rule'])))
                for clause in (parsed[1] if parsed else []):
                    for sku in split_list(clause['value']):
                        if sku in universe and sku not in abstracts:
                            miss(entity, source, 'abstract', sku)
            if entity == 'product-concrete':
                for item in split_list(table.cell(row, table.col('bundled'))):
                    if '/' in item and item.split('/', 1)[0].strip() not in concrete_set:
                        miss(entity, source, 'bundled-item', item)
    return dangling


def opaque_refs(actions, tables, abstracts, concretes):
    total, unresolved = 0, set()
    for entity, source in actions:
        column = OPAQUE_JSON.get(entity)
        if not column:
            continue
        table = tables[source]
        index = table.col(column)
        for row in table.rows:
            for sku in _OPAQUE_SKU.findall(table.cell(row, index)):
                total += 1
                if sku not in abstracts and sku not in concretes:
                    unresolved.add(sku)
    return total, sorted(unresolved)


def relation_anomalies(actions, tables, abstracts):
    """Relation rule tokens that are no abstract in the full set: kept as they are, listed here."""
    found = set()
    for entity, source in actions:
        column = RULES.get(entity, {}).get('relation_rule')
        if not column:
            continue
        table = tables[source]
        for row in table.rows:
            parsed = relation_rule_skus(table.cell(row, table.col(column)))
            for clause in (parsed[1] if parsed else []):
                found.update(s for s in split_list(clause['value']) if s not in abstracts)
    return sorted(found)


def input_digest(config_path, actions):
    digest = hashlib.sha256(pathlib.Path(config_path).read_bytes())
    for _, source in actions:
        digest.update(source.encode())
        digest.update(pathlib.Path(source).read_bytes())
    digest.update(str(SCRIPT_VERSION).encode())
    return digest.hexdigest()


def build(args):
    fraction = args.fraction
    if not 0 < fraction <= 1:
        sys.exit('--fraction must be in (0, 1]')
    config_path = args.config
    config_text, actions = read_config(config_path)
    if not actions:
        sys.exit(f'no actions found in {config_path}')
    stem = pathlib.Path(config_path).stem
    out_dir = pathlib.Path(args.out or f'{DEFAULT_OUT_ROOT}/{stem}-{fraction:g}')
    digest = input_digest(config_path, actions)

    if out_dir.exists():
        manifest_path = out_dir / 'manifest.json'
        if manifest_path.exists() and not args.rebuild:
            manifest = json.loads(manifest_path.read_text())
            if manifest.get('input_digest') == digest and manifest.get('fraction') == fraction:
                print(f'up to date: {out_dir} (input digest unchanged)')
                print(json.dumps(summary(manifest), indent=2))
                return 0
            sys.exit(f'{out_dir} exists and was built from other input; pass --rebuild to replace it')
        if not args.rebuild:
            sys.exit(f'{out_dir} exists without a manifest; pass --rebuild to replace it')
        if not str(out_dir.resolve()).startswith(str(pathlib.Path(DEFAULT_OUT_ROOT).resolve())) and not args.out:
            sys.exit(f'refusing to remove {out_dir}: outside {DEFAULT_OUT_ROOT}')
        shutil.rmtree(out_dir)

    tables = load(actions)
    abstracts, concrete_of, offers_of, edges = catalog(actions, tables)

    # The rules must hold on the full set before they can prove anything about a subset.
    full_keys = filter_tables(actions, tables, abstracts, concrete_of, offers_of)[3]
    full_dangling = validate(actions, tables, {}, abstracts, set(concrete_of), set(offers_of), full_keys)
    if full_dangling:
        print(json.dumps(full_dangling[:50], indent=2), file=sys.stderr)
        sys.exit(f'the full set has {len(full_dangling)} unresolved reference(s); fix the rules or the data first')

    pinned = pins(actions, tables, abstracts, concrete_of)
    kept, pinned_closure, target = select(fraction, abstracts, concrete_of, edges, pinned)
    produced, kept_concretes, kept_offers, keys = filter_tables(actions, tables, kept, concrete_of, offers_of,
                                                                universe=abstracts)
    rows_by_source = {source: rows for source, (rows, _) in produced.items()}
    dangling = validate(actions, tables, rows_by_source, kept, kept_concretes, kept_offers, keys,
                        universe=abstracts)
    if dangling:
        print(json.dumps(dangling[:50], indent=2), file=sys.stderr)
        sys.exit(f'subset has {len(dangling)} unresolved reference(s); nothing was written')

    out_dir.mkdir(parents=True)
    files, new_source = [], {}
    rows_in = rows_out = product_rows_in = product_rows_out = 0
    for source, table in tables.items():
        rows, changed = produced.get(source, (table.rows, False))
        entry = {'source': source, 'rows_in': len(table.rows), 'rows_out': len(rows), 'bytes_in': table.bytes}
        rows_in += len(table.rows)
        rows_out += len(rows)
        if source in produced:
            product_rows_in += len(table.rows)
            product_rows_out += len(rows)
        if changed:
            relative = pathlib.Path(source)
            try:
                relative = relative.relative_to('data/import')
            except ValueError:
                relative = pathlib.Path(*relative.parts[1:]) if relative.is_absolute() else relative
            target_path = out_dir / relative
            entry['bytes_out'] = table.write(target_path, rows)
            entry['written'] = str(target_path)
            new_source[source] = str(target_path)
        else:
            entry['bytes_out'] = table.bytes
        files.append(entry)

    config_out = out_dir / 'import.yml'
    lines = []
    for line in config_text.splitlines():
        match = re.match(r'^(\s*source:\s*)(\S+)(\s*)$', line)
        if match and match.group(2) in new_source:
            line = f'{match.group(1)}{new_source[match.group(2)]}{match.group(3)}'
        lines.append(line)
    header = (f'# Generated by tools/ps-metrics/scale-import.py from {config_path} at fraction {fraction:g}.\n'
              f'# Do not edit: rebuild with `scale-import.py build --fraction {fraction:g} --config {config_path} --rebuild`.\n')
    config_out.write_text(header + '\n'.join(lines) + '\n', encoding='utf-8')

    opaque_total, opaque_unresolved = opaque_refs(actions, tables, kept, kept_concretes)
    manifest = {
        'script_version': SCRIPT_VERSION,
        'built': time.strftime('%Y-%m-%dT%H:%M:%S%z'),
        'source_config': config_path,
        'config': str(config_out),
        'input_digest': digest,
        'fraction': fraction,
        'abstracts': {'total': len(abstracts), 'target': target, 'kept': len(kept),
                      'pinned': len(pinned_closure), 'fraction_actual': round(len(kept) / len(abstracts), 4)},
        'concretes': {'total': len(concrete_of), 'kept': len(kept_concretes),
                      'fraction_actual': round(len(kept_concretes) / max(1, len(concrete_of)), 4)},
        'offers': {'total': len(offers_of), 'kept': len(kept_offers)},
        'product_rows': {'in': product_rows_in, 'out': product_rows_out,
                         'fraction_actual': round(product_rows_out / max(1, product_rows_in), 4)},
        'all_rows': {'in': rows_in, 'out': rows_out},
        'pinned_by': {entity: sorted(a for a, reasons in pinned.items() if entity in reasons)
                      for entity in sorted({r for reasons in pinned.values() for r in reasons})},
        'validation': {'full_set_dangling': 0, 'subset_dangling': 0,
                       'source_anomalies': relation_anomalies(actions, tables, abstracts),
                       'opaque_json_refs': opaque_total, 'opaque_json_unresolved': opaque_unresolved},
        'kept_abstracts': sorted(kept),
        'files': files,
    }
    (out_dir / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    print(f'built {out_dir}')
    print(json.dumps(summary(manifest), indent=2))
    return 0


def summary(manifest):
    result = {key: manifest[key] for key in ('config', 'fraction', 'abstracts', 'concretes', 'offers',
                                             'product_rows', 'validation')}
    validation = dict(result['validation'])
    validation['opaque_json_unresolved'] = len(validation.get('opaque_json_unresolved', []))
    result['validation'] = validation
    return result


def verify(args):
    out_dir = pathlib.Path(args.dir)
    manifest = json.loads((out_dir / 'manifest.json').read_text())
    _, actions = read_config(manifest['config'])
    tables = load(actions)
    # The generated config already points at the subset files, so the catalog is the subset itself.
    abstracts, concrete_of, offers_of, _ = catalog(actions, tables)
    keys = filter_tables(actions, tables, abstracts, concrete_of, offers_of)[3]
    dangling = validate(actions, tables, {}, abstracts, set(concrete_of), set(offers_of), keys)
    result = {'config': manifest['config'], 'abstracts': len(abstracts), 'concretes': len(concrete_of),
              'offers': len(offers_of), 'dangling': len(dangling), 'examples': dangling[:10]}
    print(json.dumps(result, indent=2))
    return 0 if not dangling else 1


_BLOCK = re.compile(r'Importer type:\s*(?P<type>\S+)\s*\n\s*Import source:\s*(?P<source>\S+)(?P<body>.*?)'
                    r'Import status:\s*(?P<status>[^\n]+)', re.S)


def parse_import_log(text, config=None):
    blocks = []
    for match in _BLOCK.finditer(text):
        body = match.group('body')
        expected = re.search(r'Importable DataSets:\s*(\d+)', body)
        imported = re.search(r'Imported DataSets:\s*(\d+)', body)
        blocks.append({'import_type': match.group('type'), 'source': match.group('source'),
                       'expected': int(expected.group(1)) if expected else None,
                       'imported': int(imported.group(1)) if imported else None,
                       'status': match.group('status').strip()})
    # A retried action (run-baseline.sh IMPORT_RETRIES) can leave a block for the failed attempt; the last
    # block per source is the outcome. Retries are counted, never hidden.
    retries = re.findall(r'^SPLIT_RETRY (\S+)', text, re.MULTILINE)
    if retries:
        last = {}
        for b in blocks:
            last[b['source']] = b
        blocks = list(last.values())
    failed = [b for b in blocks if b['status'].lower() != 'successful']
    skipped = [b for b in blocks if b['expected'] is not None and b['imported'] is not None
               and b['imported'] < b['expected']]
    result = {'importers': len(blocks), 'successful': len(blocks) - len(failed),
              'failed': [b['source'] for b in failed],
              'skipped_rows': sum(b['expected'] - b['imported'] for b in skipped),
              'with_skips': [f"{b['source']} ({b['imported']}/{b['expected']})" for b in skipped],
              'rows_imported': sum(b['imported'] or 0 for b in blocks),
              'retries': len(retries), 'retried_actions': sorted(set(retries))}
    if config:
        _, actions = read_config(config)
        result['actions_in_config'] = len(actions)
        result['missing_actions'] = len(actions) - len(blocks)
    result['ok'] = (not failed and not skipped and bool(blocks)
                    and result.get('missing_actions', 0) == 0)
    return result


def check_log(args):
    result = parse_import_log(pathlib.Path(args.log).read_text(errors='replace'), args.config)
    if args.json:
        print(json.dumps(result))
    else:
        print(json.dumps(result, indent=2))
    return 0 if result['ok'] else 1


def main():
    parser = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    sub = parser.add_subparsers(dest='command', required=True)
    b = sub.add_parser('build')
    b.add_argument('--fraction', type=float, required=True)
    b.add_argument('--config', default=DEFAULT_CONFIG)
    b.add_argument('--out')
    b.add_argument('--rebuild', action='store_true')
    v = sub.add_parser('verify')
    v.add_argument('dir')
    c = sub.add_parser('check-log')
    c.add_argument('log')
    c.add_argument('--config')
    c.add_argument('--json', action='store_true')
    args = parser.parse_args()
    return {'build': build, 'verify': verify, 'check-log': check_log}[args.command](args)


if __name__ == '__main__':
    sys.exit(main())
