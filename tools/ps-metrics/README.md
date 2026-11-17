# Publish and Synchronize metrics (SOL-629)

Instrumentation for measuring the import, publish and synchronize pipeline. This is the base-layer
slice: enough to run a demo-data measurement end to end and get real numbers out.

Everything is off unless `QUEUE_METRICS_ENABLED` is set, so a system that does not want the
instrumentation pays nothing for it.

## What is collected, and from where

Two independent sources, because they answer different questions.

| Source | Produces | Needs |
|---|---|---|
| **Task-run records** (application) | Per batch: wall time, configured vs effective chunk size, processed and failed counts, peak memory, and the enqueue-to-consumption age distribution | `QUEUE_METRICS_ENABLED=1` |
| **Broker samples** (`queue-sampler.sh`) | Per queue over time: depth, publish and ack counters, consumer count, redelivers | RabbitMQ management API reachable |

The application side answers "what does a message cost". The broker side answers "are we keeping
up". Backlog metrics such as projected time to empty are derived from the broker counters.

## Where the instrumentation sits, and why there

Both queue workers, the classic `Worker` and the `ResourceAwareQueueWorker`, spawn
`vendor/bin/console queue:task:start <queue>`, which reaches `TaskManager::run()`. That is the one
execution point they share, so instrumenting it means the records do not depend on which worker
scheduled the run. Anything built on `WorkerStats` would only work under the resource-aware worker,
and anything on `WorkerProgressBar` only under the classic one.

The publish side is stamped in `QueueProxy`, the single choke point every producer goes through, so
no producer module needed changing.

## Running a base-layer measurement

```bash
# 1. Start the broker sampler, in its own shell, before doing anything else.
tools/ps-metrics/queue-sampler.sh --out data/metrics/queue-samples.jsonl

# 2. Run the workload with metrics on.
docker/sdk cli "QUEUE_METRICS_ENABLED=1 vendor/bin/console data:import --config=data/import/local/full_EU.yml"
docker/sdk cli "QUEUE_METRICS_ENABLED=1 vendor/bin/console queue:worker:start --stop-when-empty"

# 3. Stop the sampler with Ctrl-C.
```

Both files land in `data/metrics/`, one JSON object per line.

### Positive control, before trusting any number

An empty or under-built environment produces a fast, meaningless run. Before reading the results,
confirm the pipeline actually did something:

```bash
# Key count, summed over every database.
docker/sdk cli "redis-cli -h key_value_store INFO keyspace | sed -n 's/^db[0-9]*:keys=\([0-9]*\).*/\1/p' | awk '{s+=\$1} END {print s+0}'"
# Elasticsearch document counts.
docker/sdk cli "curl -s http://search:9200/_cat/indices?h=index,docs.count"
```

**Two ways this control has silently lied, both hit for real:**

- **`DBSIZE` returns 0 on KVrocks** even when the store holds hundreds of thousands of keys, because
  it needs a background scan first. On 2026-09-29 it reported an empty store while the data was
  there, and very nearly produced the conclusion that storage sync was broken.
- **`--scan` without `-n` only ever sees database 0**, while the application writes to database 1,
  because `deploy.dev.yml` sets `key_value_store: namespace: 1`. On 2026-10-22 that reported 0 keys
  against a store holding 17 113, and failed the run. Not engine-specific: that run was Valkey,
  while the `DBSIZE` trap above was hit on KVrocks.

Both failures look identical from the outside: a confident zero. Count with `tools/ps-metrics/kv-keys.sh`,
and treat any zero as a claim to verify rather than a result.

**Correction, 2026-11-04:** `INFO keyspace` is not safe on KVrocks either. On `apache/kvrocks:2.10.1`
one key was written and `INFO keyspace` still said `keys=0`, with `WARN: DBSIZE SCAN never performed
yet`; after `DBSIZE SCAN` it said 1. Both figures report the last finished background scan. KVrocks
also ignores `SELECT`: database 0 and 1 see the same keys. `kv-keys.sh` sums `INFO keyspace` on
Valkey/Redis, and on KVrocks starts a scan first (`--settle` waits until two counts 5 s apart agree).
The runner, `storage-size.sh` and `reset-to-snapshot.sh` use it; the 1 Hz storage sampler only starts
scans, so its KVrocks curve lags by one scan.

A drained queue is not proof of delivery either: messages that exhaust their retries leave the
pipeline and the storage entry is never written. Check key and document counts, not queue depth.

### Suspend the scheduler first

Jenkins launches `queue:worker:start` on its own schedule. Those workers consume the messages
without the metrics environment variable, so the run drains and produces no records:

```bash
docker/sdk console scheduler:suspend
docker/sdk cli "ps aux | grep -E 'queue:(task|worker)' | grep -v grep"   # must be empty
```

Stray workers killed mid-flight leave messages unacknowledged; the broker takes up to a minute to
requeue them, and until it does a new consumer receives nothing.

### Getting the files out on macOS

`docker/sdk` syncs host to container, not reliably container to host, so a metrics file written
inside the container may never appear in the host working copy:

```bash
docker cp spryker_cli_1:/data/data/metrics/task-runs.jsonl ./data/metrics/
```

## Record shapes

Task run, one per `queue:task:start` that received at least one message. Idle polls are not
recorded, since they would dominate the file.

```json
{"ts":1758531200.412345,"queue":"sync.storage.product","pid":4412,
 "chunk_configured":100,"chunk_received":40,"processed":38,"failed":2,
 "ms_total":28.321,"ms_process":28.3,"peak_mb":118,
 "msg_per_sec":1413.425,"ms_per_msg":0.708,"fill_ratio":0.4,
 "age_ms":{"n":40,"min":150.001,"avg":150.012,"p50":150.011,"p95":150.022,"max":150.024}}
```

Notes on reading it:

- `ms_per_msg` is the cost of a message, `msg_per_sec` the capacity of the queue. Under backlog they
  answer different questions, so both are emitted rather than left to be derived.
- `fill_ratio` below 1 means the batch never filled. A queue that is persistently far below 1 is
  paying per-chunk overhead for nothing.
- `age_ms` is enqueue-to-consumption, so it includes queue wait, unlike `ms_per_msg`. `n` states how
  many messages in the batch carried a timestamp; messages without one are excluded rather than
  counted as zero.
- A negative age means the publishing and consuming clocks disagree. It is reported rather than
  clamped, so the skew is visible instead of hidden.

Broker sample, one per queue per tick:

```json
{"ts":1758531200.412345,"queue":"sync.storage.product","vhost":"/","depth":1200,
 "ready":1180,"unacked":20,"consumers":2,"published":50000,"acked":48800,"redelivered":12}
```

`published` and `acked` are cumulative counters. Rates come from differencing consecutive samples,
and projected time to empty is `depth / (ack_rate - publish_rate)`, undefined when the queue is
diverging. A broker that cannot be reached writes an explicit `{"error":"broker_unreachable"}` line
rather than a silent gap.

## Configuration

| Setting | Default | Meaning |
|---|---|---|
| `QUEUE_METRICS_ENABLED` | off | Master switch. Off means no timing, no header, no writes |
| `QUEUE_METRICS_OUTPUT_FILE` | `data/metrics/queue-task-runs.jsonl` | Where task-run records are appended |

## Known limits of this slice

- Enqueue-to-consumption needs the publish-side stamp, so it is only populated for messages
  published after the instrumentation was switched on. Messages already sitting in a queue have no
  timestamp and are excluded from `age_ms`.
- The stamp survives a retry, because a re-published message keeps the header it already carries.
  That makes `age_ms` a true end-to-end age rather than a per-attempt one. A separate per-attempt
  measurement is not part of this slice.
- Only the RabbitMQ adapter propagates message headers back to the consumer. Under the Symfony
  Messenger adapter the age is absent rather than wrong, and every other metric still works.
- If `processMessages()` throws, the record for that batch is not written. A crashed batch is
  currently visible only in the logs.

## Database snapshots and resets

`db-snapshot.sh` keeps copies of the logical database inside the same MariaDB server: `create <name>`
copies every table into `snap__<name>` (4 tables at a time) and verifies exact row counts; `restore
<name>` first moves the current tables into a `trash__<timestamp>` database with `RENAME TABLE`
(instant, nothing dropped) and then copies the snapshot back. `reset-to-snapshot.sh <name>` does the
restore, empties the key-value store, recreates the search indices with their mappings, rebuilds both
from the restored database with `sync:data`, and waits until the search count settles. Snapshot and
trash databases cost disk in the database volume; `db-snapshot.sh list` shows them, `drop <db> --yes`
removes one. Measured on b2b demo data: 470 tables, 129k rows, create 14 s, restore 20 s.

### Sets that add stores

A set that creates stores (the ICP sets add FR, NL and CH to the demo's DE and AT) needs those stores set
up before its catalog is synchronized. Without it the 2026-11-04 s010 load created the new stores' search
indices by dynamic mapping and left their store storage without currency and country; Yves and Glue
answered 500 on every request, and the broken state went into `snap__s010_loaded`.
`store-setup.sh prepare --import-config <yml> --work-dir <dir>` imports only the store actions of a
config, runs `queue:setup` and `search:setup:sources`, republishes the stores and drains; `run-baseline.sh`
runs it as stage 5c, outside the measured window. `store-setup.sh check` fails when a store has no
currency or country in storage, misses an index other stores have, has an index without analysis
settings, or has a page index whose `integer-facet` is not nested. The runner's positive control and
`reset-to-snapshot.sh` run it; take a snapshot only after it passes.

## Rendering the report

```bash
python3 tools/ps-metrics/render-report.py data/metrics/<run-id>              # writes <run-id>/report-<UTC stamp>-<sha>.html
python3 tools/ps-metrics/render-report.py data/metrics/<run-id> /tmp/r.html  # a new output path; refused if it exists
python3 tools/ps-metrics/render-report.py --self-test                        # binning, weighted cost and render tests
```

A rendered report is never overwritten. Without an output path both renderers write a new file
`report-<YYYYMMDD-HHMMSS>-<sha>.html` (UTC; `<sha>` is the last commit that touched the renderer, and a
numeric suffix is added if that name is taken). An explicit path that already exists is refused with
exit code 2; there is no force flag. Each page names its own file, renderer commit (`+local` when the
renderer has uncommitted changes) and render time in the footer, so two versions can be compared.
Older `report.html` files are left as they are.

Both reports carry two sections from contract C7:

- **Weighted time.** Per queue and per listener and event, time per 1 000 messages (total time over
  total messages, never a mean of per-batch rates). Per queue, a least-squares fit
  `cost = fixed + marginal x messages` over the batches, for `ms_total` and `ms_process`, with n and
  R squared, the predicted cost of a batch of 200 and of 1 000, and the fixed share at each size.
  Under 3 batches or with a single batch size the queue has "not enough batches". A negative fixed
  part gets no fixed share. Scatter panels show batch time against batch size (log x) with the line.
- **Memory.** Per queue, the batch peak (`peak_mb`) as exact p50, p95, p99 and max over batches, an
  adaptive-bin distribution and a peak-against-batch-size scatter; the queues ranked by p95 peak
  (absolute) and by memory added per 100 messages (weighted, total over total). Memory added per batch
  is `mem_peak_incr_mb`, or `peak_mb - mem_start_mb`. `peak_mb` alone is never divided by messages,
  because it includes the process baseline. Per listener and event: the record increment as a
  distribution (it is a maximum over calls, so it is never summed) and `mem_peak_incr_hist` (KB per
  message per call). Direct-sync flushes (C6) per context, and the rise of the import process peak
  per import type. A field that is absent is shown as "not measured", never 0.

Stdlib only, one self-contained light-theme HTML file. Timing distributions are stored as a fixed
log histogram (16 steps per octave, mergeable) and re-binned for display only: tails outside the
0.5th to 99.5th percentile become "< x" and "> y" edge bars, overflow (`le=+Inf`) is its own bar,
and the bin width is the smallest power-of-two multiple of the stored step that fits the span in
30 bins, so no stored bucket is ever split. The import section appears only when the bundle holds
`import-runs.jsonl` records.

## Sync mode

`run-baseline.sh` records `sync_mode` (`direct` or `queue`) in `meta.json`, resolved inside the CLI
container by `tools/ps-metrics/sync-mode.php`. The effective mode is what the generated Propel
entities return from `isDirectSyncEnabled()`, which the synchronization behaviour bakes in at
`propel:build` from `SynchronizationBehaviorConfig::isDirectSynchronizationEnabled()` and
`SPRYKER_DYNAMIC_STORE_MODE`. The config is reported beside it, and a disagreement is flagged as
stale generated code. `--control` re-runs both checks where they must answer `queue`; the runner
fails if they do not. Read-only: `docker exec -w /data spryker_cli_1 php tools/ps-metrics/sync-mode.php --control`.

## Measurement campaigns (C5)

A single run cannot separate a real change from run-to-run variance (2.3x has been observed), so
numbers are taken as a campaign: N instrumented and M uninstrumented runs of the same input,
interleaved, rendered as one report.

```bash
tools/ps-metrics/run-campaign.sh --instrumented 4 --uninstrumented 2 --dry-run   # print the plan only
tools/ps-metrics/run-campaign.sh --instrumented 4 --uninstrumented 2 --id 20261029 --notes "full_EU, direct sync"
python3 tools/ps-metrics/render-campaign.py data/metrics/campaign-20261029     # re-render any time, new versioned file
python3 tools/ps-metrics/render-campaign.py --self-test
```

- Layout: `data/metrics/campaign-<id>/campaign.json` (`id`, `started`, `notes`, `order`, `runs`:
  `{run_id, instrumented, bundle, status, exit_code, started, ended, scale, import_config}`, plus `scales`
  when `--scales` was given) and one bundle per run. A failed
  run is recorded with `status: failed` and the campaign carries on; the report lists and excludes it.
- `INSTRUMENTED=0 tools/ps-metrics/run-baseline.sh` runs the same stages with no metrics variable at
  all; `meta.json` records `instrumented: false`, and the runner fails if the task-run file appears anyway.
- Every run writes `storage-samples.jsonl` (`{ts, kv_keys, search_docs}`, 1 Hz, import start to drain
  end) through `storage-sampler.py`, which uses the positive control's own sources: `INFO keyspace`
  summed over every database and the search `_count` API. A source that cannot be read is `null`, never 0.
- Instrumented runs set `QUEUE_METRICS_BREAKDOWN=1` (C3, override with 0) and collect
  `sync-flush-runs.jsonl` (C6, direct-sync flushes at console terminate, joined to task runs by pid).
- Interpretation goes in an optional `story.md` beside `campaign.json` (headings, paragraphs, bullets,
  bold, inline code, code fences). The generated text states computed facts only.

Report sections: verdict and headline (end to end median with min to max, messages, throughput, top 3
costs, overhead), story, pipeline timeline (stage bars per run, queue depth and storage fill over
time), bottlenecks ranked (measured-time ledger with rank stability, true cost per queue as processing
plus storage flush, per queue and per listener tables, C3 stacked bars), distributions (merged
histograms with p50/p95/p99, attempt age beside end-to-end age where C4 exists, small multiples,
flush time), run-to-run variance, instrumentation overhead (delta of medians against the larger
range; fewer than 2 runs on a side reports "not enough runs"), import, and data quality.

## Capacity inputs (C8)

Every `run-baseline.sh` bundle also holds the inputs a capacity model needs: what was imported, how
big the catalog was, what the services used, and how big the storages ended up.

| File | Written | Content |
|---|---|---|
| `source-inventory.json` | before the import, completed after drain | per import action `data_entity`, `source`, `rows` (CSV records minus the header), `bytes`; `db_before_import` and `db_after_import` exact row counts of the main catalog tables (products, prices, stock, offers, merchants, categories, product lists, labels, sets, relations, URLs, glossary, CMS, stores, locales, currencies); `stores`, `locales`, `currencies` (in use by a store) and the totals |
| `service-samples.jsonl` | 1 Hz, import start to drain end | `{ts, container, service, cpu_pct, mem_mb, pids}` for the database, key-value, search, broker and cli containers |
| `storage-size.json` | at drain end | key-value `used_memory` bytes and keys per database; search document count, store size and primary store size per index |
| `import-check.json` | after drain | from the import log: importers, successful, failed sources, skipped rows, actions missing from the log, `ok` |

`meta.json` gains `scale` (requested fraction, 1.0 for the full set), `import_config`, `import_path`
(`update` when `spy_product_abstract` and `spy_product` hold rows before the import, else `insert`),
`publish_trigger`, `scale_subset` (the built fractions, from the subset manifest) and `import_check`.

Notes:

- The service sampler holds one streaming `docker stats --format '{{json .}}'` child and keeps one
  frame per container per second. A one-shot `docker stats --no-stream` takes about 2 s, so polling it
  cannot reach 1 Hz. `cpu_pct` is percent of one CPU (docker's figure).
- Catalog row counts are taken after drain: publish and sync do not write catalog tables, so they
  equal the counts right after the import, and the queries stay out of the measured window.
- All three probes are read-only and write null, never 0, for a source that does not answer.

```bash
python3 tools/ps-metrics/source-inventory.py sources --config data/import/local/full_EU.yml   # per action, no database
python3 tools/ps-metrics/service-sampler.py --list                                            # containers it would sample
python3 tools/ps-metrics/service-sampler.py --out /tmp/s.jsonl --duration 5
tools/ps-metrics/storage-size.sh --out /tmp/storage-size.json
```

### Scaled import subsets

`scale-import.py` builds a referentially consistent subset of an import set, so one campaign can run
at 0.25, 0.5, 0.75 and 1.0 of the catalog and show whether cost is linear in size.

```bash
python3 tools/ps-metrics/scale-import.py build --fraction 0.25        # data/metrics/scaled/full_EU-0.25/{import.yml,manifest.json,...}
python3 tools/ps-metrics/scale-import.py verify data/metrics/scaled/full_EU-0.25
python3 tools/ps-metrics/scale-import.py check-log <bundle>/stage-import.log --config <yml>
SCALE=0.25 tools/ps-metrics/run-baseline.sh                           # uses the subset's default location
tools/ps-metrics/run-campaign.sh --instrumented 2 --uninstrumented 1 --scales 0.25,0.5,0.75,1.0 --dry-run
```

- The unit is the abstract product. Abstracts are ranked by a stable hash of the SKU, so the same
  fraction always gives the same products and a smaller subset is contained in a larger one.
- Abstracts named by data that stays complete are pinned: product sets, content product lists, CMS
  slot conditions, discount promotion SKUs. A kept bundle pins its bundled items, a kept packaging unit
  its lead product. The fraction is met by adding hashed abstracts after the pins.
- Product-dependent rows are kept only when the product they name is kept: concretes by abstract;
  images, prices, schedules, stock, store relations, approvals, reviews, groups, attachments,
  measurement and packaging units, alternatives, discontinued, quantities, shipment types, merchant
  products, product list members, merchant relationship prices, shopping list items; offers by
  concrete, then everything keyed by an offer reference; sales unit stores, relation stores and offer
  shopping list items by their kept key. Label and option SKU lists and relation rule SKU lists are
  filtered in place; a relation left without SKUs is dropped with its store rows.
- Stores, locales, currencies, categories, glossary, CMS, merchants, customers, companies, discounts
  and product lists stay complete: the generated config points at the original file. Only filtered
  files are written. `data/metrics/` is git-ignored.
- Proof, statically: every product, offer and key reference in the written files must resolve in the
  subset, and the same check must pass on the full set first, so a misclassified column fails the
  build. A column that looks like a SKU in an entity without a rule also fails it. Customer carts and
  quote requests hold SKUs only inside JSON that no importer resolves; they stay complete and the
  manifest counts those references. Proof, at run time: `import-check.json` must show every importer
  successful and no skipped rows.
- Scale is the fraction of abstract products; the manifest also gives the fraction of concretes,
  offers and product rows actually built. Reference data does not scale, so total import rows do not
  fall in proportion.
- On the update path (catalog tables already full) the database still holds the full catalog, and
  `publish:trigger-events` republishes every entity in it. `PUBLISH_TRIGGER=0` skips that stage so a
  scaled run processes only the events its own import emitted.
