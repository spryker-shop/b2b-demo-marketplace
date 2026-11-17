# spryker-ps-metrics

Measures data import and Publish & Synchronize in a Spryker project. One place for the instrumentation and the tooling, installed into each project as a Composer dependency.

| Part | Path | What it does |
|---|---|---|
| Instrumentation | `src/`, `_register.php` | OpenTelemetry hooks on queue tasks, event listeners, imports, retries and direct-sync flushes. Loaded through `autoload.files`, off unless `QUEUE_METRICS_ENABLED` is set or a valid settings file exists |
| Tooling | `tools/ps-metrics/` | campaign runner, samplers, scaled imports, report and deck renderers. Detail in `tools/ps-metrics/README.md` |

Origin: the `PublishAndSynchronizeMetrics` module and `tools/ps-metrics` on the suite branch `feature/sol-629-ps-metrics-v2` (SOL-629), copied at `eea523fd9d6`.

## Requirements

- The `opentelemetry` PHP extension in the app image. Without it no hook registers.
- `spryker/data-import`, `spryker/event`, `spryker/queue`.

## Install into a project

The project runs in Docker, so the package must end up inside the project tree. A path repository with `symlink: false` copies it into `vendor/`. Run composer on the host:

```bash
composer config repositories.ps-metrics '{"type": "path", "url": "../spryker-ps-metrics", "options": {"symlink": false}}'
composer require 'ishakuta/spryker-ps-metrics:*@dev' --ignore-platform-reqs
ln -s ../vendor/ishakuta/spryker-ps-metrics/tools/ps-metrics tools/ps-metrics
```

The runner scripts expect `tools/ps-metrics/` under the project root, on the host and in the container. The relative symlink resolves in both.

After a change here, refresh the copy in the project:

```bash
composer reinstall ishakuta/spryker-ps-metrics --ignore-platform-reqs
```

## Tests

```bash
vendor/bin/codecept run -c vendor/ishakuta/spryker-ps-metrics/codeception.yml   # PHP unit tests, inside the app container
python3 tools/ps-metrics/test_render.py                                        # renderer tests, any of test_*.py
```
