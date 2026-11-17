#!/usr/bin/env python3
"""Build a per-store import config for a multi-DB (one database per store) setup.

data/import/production/full_EU.yml imports DE and AT into one database. With one database
per store, every store needs its own config that imports only its own store data:

    split_import_by_store.py DE          -> data/import/production/full_DE.yml
    split_import_by_store.py FR --from AT -> copies data/import/common/AT to common/FR
                                            (store cells AT -> FR), then full_FR.yml

Shared data (data/import/common/common) is imported into every store database.
"""
import argparse
import csv
import io
import pathlib
import re
import shutil

ROOT = pathlib.Path(__file__).resolve().parents[2]
COMMON = ROOT / 'data/import/common'
SOURCE = ROOT / 'data/import/production/full_EU.yml'
REGION_STORES = ('DE', 'AT')


def clone_store_data(new: str, base: str, country: str) -> None:
    src, dst = COMMON / base, COMMON / new
    if dst.exists():
        shutil.rmtree(dst)
    shutil.copytree(src, dst)
    for path in dst.rglob('*.csv'):
        text = path.read_text(encoding='utf-8')
        rows = list(csv.reader(io.StringIO(text)))
        if not rows:
            continue
        header = rows[0]
        cols = [i for i, h in enumerate(header) if 'store' in h.lower() or (path.name == 'country_store.csv' and h == 'country')]
        for row in rows[1:]:
            for i in cols:
                if i >= len(row):
                    continue
                if path.name == 'country_store.csv' and header[i] == 'country':
                    row[i] = country
                else:
                    row[i] = ','.join(new if p.strip() == base else p for p in row[i].split(','))
        out = io.StringIO()
        csv.writer(out, lineterminator='\n').writerows(rows)
        path.write_text(out.getvalue(), encoding='utf-8')
    for yml in dst.rglob('*.yml'):
        yml.write_text(yml.read_text().replace(f'/{base}/', f'/{new}/'))
        yml.rename(yml.with_name(yml.name.replace(base, new)))


def write_config(store: str, base: str | None = None) -> pathlib.Path:
    (COMMON / store).mkdir(exist_ok=True)
    (COMMON / store / 'store.csv').write_text(f'name\n{store}\n')
    others = [s for s in REGION_STORES if s != (base or store)]
    out, skip = [], False
    for line in SOURCE.read_text().splitlines(keepends=True):
        m = re.match(r'\s*source:\s*(\S+)', line)
        if m:
            src = m.group(1)
            if any(f'/common/{o}/' in src for o in others):
                out.pop()  # drop the data_entity line of this action
                continue
            line = line.replace('data/import/common/EU/store.csv', f'data/import/common/{store}/store.csv')
            line = re.sub(r'/common/(DE|AT)/', f'/common/{store}/', line)
        out.append(line)
    target = ROOT / f'data/import/production/full_{store}.yml'
    target.write_text(''.join(out).replace("'full' data import", f"'full' data import, store {store} only"))
    return target


if __name__ == '__main__':
    ap = argparse.ArgumentParser()
    ap.add_argument('store')
    ap.add_argument('--from', dest='base', help='clone the store data of this store (new store)')
    ap.add_argument('--country', help='country of the new store (default: the store code)')
    a = ap.parse_args()
    if a.base:
        clone_store_data(a.store, a.base, a.country or a.store)
    print(write_config(a.store, a.base).relative_to(ROOT))
