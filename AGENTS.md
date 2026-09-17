# AGENTS.md — extension-tao-proctoring (taoProctoring)

> Shared pillars (standards, quality / `pr-ready-gate`, Make, commit/PR):
> [nextgen-stack `tao/AGENTS.md`](https://github.com/oat-sa/nextgen-stack/blob/main/tao/AGENTS.md)
> · local: [`../AGENTS.md`](../AGENTS.md).

## 01 — Project Context

**What / why:** `oat-sa/extension-tao-proctoring` (id `taoProctoring`) provides
**proctored delivery monitoring**: roles, session monitor UI, start authorization,
irregularities, history.

**Not:** delivery publish/authoring or the test-runner engine.

**Key directories / stack / constraints:**

```text
manifest.php
controller/Monitor.php, DeliverySelection.php, DeliveryServer.php, …
model/ProctorService.php, model/authorization/, …
views/templates/layout.tpl          # dedicated app shell
views/js/controller/app.js
views/js/controller/Delivery/
views/js/component/activityMonitoring/
views/js/loader/app.min.js
views/js/loader/taoProctoring.min.js
test/
```

- Stack: delivery-rdf/delivery/eventlog/outcome deps; FE in-repo; **dedicated app
  layout** (not only standard BO).
- Versions from composer/CI.

**Docs:** [`README.md`](README.md). Shared docs / decision-log rules → parent AGENTS.

## 02 — Standards & Conventions

Package-only below. Family patterns, quality SoT, `pr-ready-gate`, polar-star →
**parent AGENTS**.

**Patterns / structure:**

- Keep proctor app shell vs BO fragments distinct; preserve pause/terminate/finish
  contracts; rebuild **both** app and extension bundles.

**Never do (this package):**

- Assume standard BO layout for all screens; implement publish/assembly.
- Hand-edit dual loaders; merge awaiting-authorization with monitor casually.

**Ownership**

| Surface | Own? |
|---------|------|
| Proctor app / monitor / selection | **Yes** |
| Awaiting authorization | **Yes** |
| Delivery publish / Test runner | **No** |

## 03 — Build & Test Commands

Shared Make / CI / readiness / commit policy → **parent AGENTS**
([commit/PR policy](https://oat-sa.atlassian.net/wiki/x/_oXmqQ)).

**This package** (from Composer platform root):

```bash
./vendor/bin/phpunit -c phpunit.xml.dist taoProctoring/test
npx grunt eslint:extensionreport --extension=taoProctoring --force
npx grunt taobundle --extension=taoProctoring
```
