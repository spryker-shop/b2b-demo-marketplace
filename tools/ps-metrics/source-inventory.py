#!/usr/bin/env python3
"""SOL-629 source inventory (contract C8).

Writes `source-inventory.json` for one run bundle: what the import was given and what the catalog
held, so a run can be placed on a catalog-size axis.

    source-inventory.py before --config YML --out FILE [--container NAME]
        DB row counts before the import and the import path that follows from them:
        "update" when the abstract and concrete product tables already hold rows, else "insert".
        Prints the import path on stdout, so the runner can record it.

    source-inventory.py after --config YML --out FILE [--container NAME]
        Adds, to the same file: per import action of the config its data entity, source, data rows
        (CSV records minus the header) and bytes; DB row counts of the main catalog tables after the
        import; stores, locales and currencies counts (in the store relation tables, and in total).

    source-inventory.py sources --config YML       print the per-action part only, no database

DB counts come from `SELECT COUNT(*)` (exact; information_schema.table_rows is an estimate) through
one `docker exec` into the CLI container. A table that does not exist, or a count that could not be
read, is null, never 0. Read-only: only SELECT statements run. Connection: DB_HOST (database), DB_USER
(spryker), DB_PASSWORD (secret), DB_NAME (eu-docker).
"""

import argparse
import csv
import io
import json
import os
import pathlib
import re
import subprocess
import sys
import time

csv.field_size_limit(sys.maxsize)

# Main catalog tables (C8): products, prices, stock, offers, merchants, categories, product lists,
# stores, locales, currencies, CMS. Names verified against the Propel schemas of this project.
TABLES = {
    'product_abstract': 'spy_product_abstract',
    'product_concrete': 'spy_product',
    'product_abstract_store': 'spy_product_abstract_store',
    'product_category': 'spy_product_category',
    'product_image': 'spy_product_image',
    'product_image_set': 'spy_product_image_set',
    'price_product': 'spy_price_product',
    'price_product_store': 'spy_price_product_store',
    'price_product_offer': 'spy_price_product_offer',
    'stock': 'spy_stock',
    'stock_product': 'spy_stock_product',
    'product_offer': 'spy_product_offer',
    'product_offer_stock': 'spy_product_offer_stock',
    'merchant': 'spy_merchant',
    'merchant_product_abstract': 'spy_merchant_product_abstract',
    'category': 'spy_category',
    'category_node': 'spy_category_node',
    'product_list': 'spy_product_list',
    'product_list_product_concrete': 'spy_product_list_product_concrete',
    'product_label': 'spy_product_label',
    'product_label_product_abstract': 'spy_product_label_product_abstract',
    'product_set': 'spy_product_set',
    'product_relation': 'spy_product_relation',
    'url': 'spy_url',
    'glossary_key': 'spy_glossary_key',
    'glossary_translation': 'spy_glossary_translation',
    'cms_page': 'spy_cms_page',
    'cms_block': 'spy_cms_block',
    'store': 'spy_store',
    'locale': 'spy_locale',
    'locale_store': 'spy_locale_store',
    'currency': 'spy_currency',
    'currency_store': 'spy_currency_store',
    'customer': 'spy_customer',
}
# Distinct values, where a plain row count would count store pairs instead of things.
DISTINCT = {
    'locales_in_stores': ('spy_locale_store', 'fk_locale'),
    'currencies_in_stores': ('spy_currency_store', 'fk_currency'),
}
PATH_TABLES = ('product_abstract', 'product_concrete')


def read_actions(config_path):
    text = pathlib.Path(config_path).read_text(encoding='utf-8')
    actions, entity = [], None
    for line in text.splitlines():
        match = re.match(r'^\s*-?\s*data_entity:\s*(\S+)\s*$', line)
        if match:
            entity = match.group(1)
            continue
        match = re.match(r'^\s*source:\s*(\S+)\s*$', line)
        if match and entity is not None:
            actions.append((entity, match.group(1)))
            entity = None
    return actions


def host_path(source):
    """An import source as the host sees it: a config may name the container path (/data/...), which is
    the project root mounted in the container, so it is read relative to the current directory."""
    path = pathlib.Path(source)
    if source.startswith('/data/') and not path.exists():
        return pathlib.Path(source[len('/data/'):])
    return path


def csv_rows(path):
    """Data records (a quoted field may span lines, so records, not physical lines) and bytes."""
    raw = host_path(str(path)).read_bytes()
    reader = csv.reader(io.StringIO(raw.decode('utf-8-sig', errors='replace'), newline=''))
    next(reader, None)
    return sum(1 for row in reader if any(cell.strip() for cell in row)), len(raw)


def sources(config_path):
    result = []
    for entity, source in read_actions(config_path):
        entry = {'data_entity': entity, 'source': source, 'rows': None, 'bytes': None}
        try:
            entry['rows'], entry['bytes'] = csv_rows(source)
        except OSError as error:
            entry['error'] = f'{type(error).__name__}: {error}'
        result.append(entry)
    return result


def db_counts(container):
    """{logical name: exact row count or None}, one docker exec for every table."""
    host = os.environ.get('DB_HOST', 'database')
    user = os.environ.get('DB_USER', 'spryker')
    password = os.environ.get('DB_PASSWORD', 'secret')
    name = os.environ.get('DB_NAME', 'eu-docker')
    # Existing tables first, so a missing one is null rather than an aborted batch.
    listing = f"SELECT table_name FROM information_schema.tables WHERE table_schema = '{name}'"
    command = ['docker', 'exec', container, 'mariadb', '-h', host, '-u', user, f'-p{password}',
               '-D', name, '-N', '-B', '-e']
    try:
        done = subprocess.run(command + [listing], capture_output=True, text=True, timeout=60)
    except (subprocess.SubprocessError, OSError) as error:
        return {key: None for key in list(TABLES) + list(DISTINCT)}, f'{type(error).__name__}: {error}'
    if done.returncode != 0:
        return {key: None for key in list(TABLES) + list(DISTINCT)}, done.stderr.strip()[-300:]
    existing = set(done.stdout.split())
    selects = [f"SELECT '{key}', COUNT(*) FROM `{table}`" for key, table in TABLES.items() if table in existing]
    selects += [f"SELECT '{key}', COUNT(DISTINCT `{column}`) FROM `{table}`"
                for key, (table, column) in DISTINCT.items() if table in existing]
    counts = {key: None for key in list(TABLES) + list(DISTINCT)}
    if not selects:
        return counts, 'none of the catalog tables exist'
    query = ' UNION ALL '.join(selects)
    try:
        done = subprocess.run(command + [query], capture_output=True, text=True, timeout=120)
    except (subprocess.SubprocessError, OSError) as error:
        return counts, f'{type(error).__name__}: {error}'
    if done.returncode != 0:
        return counts, done.stderr.strip()[-300:]
    for line in done.stdout.splitlines():
        parts = line.split('\t')
        if len(parts) == 2 and parts[1].isdigit():
            counts[parts[0]] = int(parts[1])
    return counts, None


def import_path(counts):
    values = [counts.get(key) for key in PATH_TABLES]
    if any(v is None for v in values):
        return None
    return 'update' if all(v > 0 for v in values) else 'insert'


def main():
    parser = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    parser.add_argument('phase', choices=('before', 'after', 'sources'))
    parser.add_argument('--config', required=True)
    parser.add_argument('--out')
    parser.add_argument('--container', default=os.environ.get('CLI_CONTAINER', 'spryker_cli_1'))
    args = parser.parse_args()

    if args.phase == 'sources':
        print(json.dumps(sources(args.config), indent=2))
        return 0
    if not args.out:
        parser.error('--out is required for before and after')
    out = pathlib.Path(args.out)

    if args.phase == 'before':
        counts, error = db_counts(args.container)
        data = {'config': args.config, 'db_before_import': counts,
                'db_before_import_ts': round(time.time(), 3),
                'import_path': import_path(counts), 'import_path_rule':
                'update when spy_product_abstract and spy_product both hold rows before the import, else insert'}
        if error:
            data['db_before_import_error'] = error
        out.write_text(json.dumps(data, indent=2) + '\n')
        print(data['import_path'] or 'unknown')
        return 0

    data = json.loads(out.read_text()) if out.exists() else {'config': args.config}
    actions = sources(args.config)
    counts, error = db_counts(args.container)
    data['actions'] = actions
    data['totals'] = {'actions': len(actions),
                      'rows': sum(a['rows'] or 0 for a in actions),
                      'bytes': sum(a['bytes'] or 0 for a in actions),
                      'unreadable_sources': sum(1 for a in actions if a['rows'] is None)}
    data['db_after_import'] = counts
    data['db_after_import_ts'] = round(time.time(), 3)
    if error:
        data['db_after_import_error'] = error
    # Stores, locales and currencies in use are the ones related to a store; the totals include
    # every locale and currency the setup knows.
    data['stores'] = counts.get('store')
    data['locales'] = counts.get('locales_in_stores')
    data['currencies'] = counts.get('currencies_in_stores')
    data['locales_total'] = counts.get('locale')
    data['currencies_total'] = counts.get('currency')
    out.write_text(json.dumps(data, indent=2) + '\n')
    print(f"  sources: {data['totals']['actions']} actions, {data['totals']['rows']} rows, "
          f"{data['totals']['bytes']} bytes; abstracts {counts.get('product_abstract')}, "
          f"concretes {counts.get('product_concrete')}, offers {counts.get('product_offer')}")
    return 0


if __name__ == '__main__':
    sys.exit(main())
