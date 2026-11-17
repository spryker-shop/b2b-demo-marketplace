#!/usr/bin/env python3
"""Publish a SOL-629 page into the reports folder and keep its index page current.

The reports folder holds every report, deck and finding that was handed over. Reports are immutable:
a published file is never overwritten, a new version gets a new name. index.json lists what is there;
index.html is regenerated from it after every change, so the newest version of each kind and scenario
is always the first thing on the page.

Usage:
    publish-report.py add <file.html> --kind report|deck|finding|plan|analysis --title "..."
                      [--scenario full_republish|import_update|first_load|...] [--campaign <id>]
                      [--note "..."] [--name <published-name.html>]
    publish-report.py drop <published-name.html> --reason "..."   # removes the entry; the file must be gone
    publish-report.py rebuild                                      # regenerates index.html only

Folder: $PS_METRICS_REPORTS_DIR, default ~/Documents/SOL-629-pubsync-perf. Stdlib only.
"""

import argparse
import html
import json
import os
import pathlib
import shutil
import sys
from datetime import datetime, timezone

FOLDER = pathlib.Path(os.environ.get('PS_METRICS_REPORTS_DIR', '~/Documents/SOL-629-pubsync-perf')).expanduser()
KINDS = {
    'report': ('Reports', 'Campaign report: every measurement, folded by topic, with the calculator.'),
    'deck': ('Slide decks', 'Visual summary of one campaign, one finding per slide.'),
    'finding': ('Findings', 'One problem: description, evidence and how to reproduce it.'),
    'analysis': ('Analyses', 'One question answered from code and measurements.'),
    'plan': ('Plans', 'Approach, metrics and plan of the measurement.'),
    'sweep': ('Batch-size sweeps', 'Lab measurement: each queue at different chunk sizes, with a recommended chunk.'),
    'video': ('Videos', 'Narrated walk-through of a slide deck.'),
    'guide': ('Guides', 'How the measurement harness works and how to apply it to another project.'),
}
SINGULAR = {'report': 'Report', 'deck': 'Slide deck', 'finding': 'Finding', 'analysis': 'Analysis', 'plan': 'Plan', 'sweep': 'Batch-size sweep', 'video': 'Video', 'guide': 'Guide'}
SCENARIOS = {
    'full_republish': 'Full republish',
    'import_update': 'Import update',
    'first_load': 'First load',
    'ps_only': 'P&S only',
}

CSS = """
:root{--ink:#0b0b0b;--ink2:#52514e;--muted:#898781;--grid:#e1e0d9;--soft:#f4f4f1;--page:#ffffff;--accent:#2a78d6;
--new:#1baf7a;color-scheme:light}
*{box-sizing:border-box}
body{margin:0;background:var(--page);color:var(--ink);font:15px/1.5 system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
.wrap{max-width:1040px;margin:0 auto;padding:32px 16px 64px}
.eyebrow{color:var(--muted);font-size:12.5px;letter-spacing:.06em;text-transform:uppercase}
h1{font-size:28px;margin:6px 0 4px}h2{font-size:19px;margin:36px 0 4px}
.lede{color:var(--ink2);margin:0 0 14px}
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px}
.card{display:block;border:1px solid var(--grid);border-radius:10px;padding:14px 16px;background:var(--page);
color:inherit;text-decoration:none}
.card:hover{border-color:var(--accent)}
.card .t{font-weight:650}.card .m{color:var(--muted);font-size:12.5px;margin-top:6px}
.card .n{color:var(--ink2);font-size:13.5px;margin-top:6px}
.chip{display:inline-block;font-size:11.5px;font-weight:650;border-radius:999px;padding:1px 8px;margin-right:6px;
background:var(--soft);color:var(--ink2)}
.chip.new{background:#e3f6ee;color:#0f6b4a}
table{width:100%;border-collapse:collapse;font-size:13.5px}
th{background:var(--soft);text-align:left;font-weight:600;color:var(--ink2)}
th,td{padding:7px 10px;border-bottom:1px solid var(--grid);vertical-align:top}
td a{color:var(--accent);text-decoration:none;overflow-wrap:anywhere}
tr.old td{color:var(--muted)}tr.old td a{color:var(--muted)}
.scroll{overflow-x:auto}
.footer{margin-top:40px;color:var(--muted);font-size:12.5px}
"""


def now_text():
    return datetime.now(timezone.utc).strftime('%Y-%m-%d %H:%M UTC')


def load_index(folder):
    path = folder / 'index.json'
    if not path.exists():
        return {'entries': [], 'dropped': []}
    return json.loads(path.read_text())


def save_index(folder, index):
    (folder / 'index.json').write_text(json.dumps(index, indent=1) + '\n')
    (folder / 'index.html').write_text(render(index))


def group_key(entry):
    return entry['kind'], entry.get('scenario') or ''


def latest_ids(entries):
    """Newest entry per kind and scenario: the one to open first."""
    newest = {}
    for e in entries:
        key = group_key(e)
        if key not in newest or e['published'] > newest[key]['published']:
            newest[key] = e
    return {e['file'] for e in newest.values()}


def card(e):
    esc = html.escape
    scen = SCENARIOS.get(e.get('scenario') or '', e.get('scenario') or '')
    chips = f"<span class='chip new'>latest</span>" + (f"<span class='chip'>{esc(scen)}</span>" if scen else '')
    return (f"<a class='card' href='{esc(e['file'])}'><div>{chips}<span class='chip'>{esc(SINGULAR[e['kind']])}</span></div>"
            f"<div class='t' style='margin-top:8px'>{esc(e['title'])}</div>"
            + (f"<div class='n'>{esc(e['note'])}</div>" if e.get('note') else '')
            + f"<div class='m'>{esc(e['published'])}" + (f" &middot; campaign {esc(e['campaign'])}" if e.get('campaign') else '')
            + "</div></a>")


def render(index):
    esc = html.escape
    entries = index['entries']
    latest = latest_ids(entries)
    out = ["<!DOCTYPE html><html lang='en'><head><meta charset='utf-8'>",
           "<meta name='viewport' content='width=device-width, initial-scale=1'>",
           f"<title>SOL-629 reports</title><style>{CSS}</style></head><body><div class='wrap'>",
           "<div class='eyebrow'>SOL-629 &middot; import, publish and synchronize performance</div>",
           "<h1>Reports and decks</h1>",
           "<p class='lede'>Every page that was handed over, newest first. Reports never change after they are "
           "published: a correction is a new version, so versions can be compared. The cards are the latest version "
           "per kind and scenario; open those first.</p>"]
    top = sorted((e for e in entries if e['file'] in latest), key=lambda e: (list(KINDS).index(e['kind']), e.get('scenario') or ''))
    out.append("<h2>Open first</h2><div class='cards'>" + ''.join(card(e) for e in top) + "</div>")
    for kind, (title, lede) in KINDS.items():
        rows = sorted((e for e in entries if e['kind'] == kind), key=lambda e: e['published'], reverse=True)
        if not rows:
            continue
        out.append(f"<h2>{esc(title)}</h2><p class='lede'>{esc(lede)}</p><div class='scroll'><table><thead><tr>"
                   "<th>published</th><th>title</th><th>scenario</th><th>campaign</th><th>note</th></tr></thead><tbody>")
        for e in rows:
            old = e['file'] not in latest
            out.append(f"<tr class='{'old' if old else ''}'><td>{esc(e['published'])}</td>"
                       f"<td><a href='{esc(e['file'])}'>{esc(e['title'])}</a>{' (older version)' if old else ''}</td>"
                       f"<td>{esc(SCENARIOS.get(e.get('scenario') or '', e.get('scenario') or '-'))}</td>"
                       f"<td>{esc(e.get('campaign') or '-')}</td><td>{esc(e.get('note') or '')}</td></tr>")
        out.append("</tbody></table></div>")
    if index.get('dropped'):
        out.append("<h2>Removed</h2><p class='lede'>Older pages removed because a newer page holds everything they "
                   "held.</p><div class='scroll'><table><thead><tr><th>removed</th><th>file</th><th>reason</th></tr>"
                   "</thead><tbody>")
        for d in sorted(index['dropped'], key=lambda d: d['dropped'], reverse=True):
            out.append(f"<tr class='old'><td>{esc(d['dropped'])}</td><td>{esc(d['file'])}</td><td>{esc(d['reason'])}</td></tr>")
        out.append("</tbody></table></div>")
    out.append(f"<div class='footer'>Generated by <code>tools/ps-metrics/publish-report.py</code> from index.json, "
               f"{esc(now_text())}.</div></div></body></html>")
    return '\n'.join(out)


def cmd_add(args, folder):
    src = pathlib.Path(args.file)
    if not src.is_file():
        sys.exit(f'publish-report: {src} not found')
    name = args.name or src.name
    target = folder / name
    if target.exists():
        sys.exit(f'publish-report: {target} exists; a published page is never overwritten')
    index = load_index(folder)
    shutil.copyfile(src, target)
    index['entries'].append({'file': name, 'kind': args.kind, 'title': args.title, 'scenario': args.scenario,
                             'campaign': args.campaign, 'note': args.note, 'published': now_text()})
    save_index(folder, index)
    print(f'published {target}')


def cmd_drop(args, folder):
    if (folder / args.file).exists():
        sys.exit(f'publish-report: {args.file} still exists; delete it first, then drop the entry')
    index = load_index(folder)
    before = len(index['entries'])
    index['entries'] = [e for e in index['entries'] if e['file'] != args.file]
    index.setdefault('dropped', []).append({'file': args.file, 'reason': args.reason, 'dropped': now_text()})
    save_index(folder, index)
    print(f"dropped {args.file} ({before - len(index['entries'])} entry)")


def main(argv):
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    sub = parser.add_subparsers(dest='cmd', required=True)
    add = sub.add_parser('add')
    add.add_argument('file')
    add.add_argument('--kind', required=True, choices=list(KINDS))
    add.add_argument('--title', required=True)
    add.add_argument('--scenario')
    add.add_argument('--campaign')
    add.add_argument('--note')
    add.add_argument('--name')
    drop = sub.add_parser('drop')
    drop.add_argument('file')
    drop.add_argument('--reason', required=True)
    sub.add_parser('rebuild')
    args = parser.parse_args(argv)
    folder = FOLDER
    folder.mkdir(parents=True, exist_ok=True)
    if args.cmd == 'add':
        cmd_add(args, folder)
    elif args.cmd == 'drop':
        cmd_drop(args, folder)
    else:
        save_index(folder, load_index(folder))
        print(f'rebuilt {folder / "index.html"}')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
