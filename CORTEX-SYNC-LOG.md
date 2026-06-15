# Cortex Sync Log — vigil

A per-project record of each sync from pendulum (the source of truth) and its verification.
The synced pendulum SHA is also recorded in `.cortex-version`. This file is vigil-owned
(never propagated back upstream).

---

## T0 — baseline · 2026-06-14

**Synced from:** pendulum @ `33ee3a932490f3879ea7aaaa86db28098f6a4c2b` (also in `.cortex-version`).

**What landed:** first-run baseline — OVERWRITE layer written, the `pendulum-upstream` vendor
branch seeded, merged with `--allow-unrelated-histories`, and the 19 "both added" conflicts
auto-settled by mode via `--resolve-t0` (16 upstream, 0 unresolved). The sync engine itself
landed **verbatim** (pristine), not specialized.

**Verification — all green:**

- **A.6 metadata gate** (`reviewVueConventions`) — **0 violations across 78 units**:
  - version == CHANGELOG top entry: 78/78
  - based-on drift (origin: pendulum only): 0 applicable — all units are `origin: vigil`
  - bonus: based-on == version regardless of origin: 78/78 (healthy T0 baseline)
- **Behavioral gate** (`make test-all`):
  - unit (vitest): 182 passed
  - e2e (Playwright): 24 passed
  - pest (PHP, in-memory sqlite): 72 passed (131 assertions)
- **Static checks:** lint (eslint) clean, type-check (vue-tsc) clean, build green.

The metadata vigil received is fully internally consistent; the gate — including the new
`based-on == version` rule added upstream — flags nothing.
