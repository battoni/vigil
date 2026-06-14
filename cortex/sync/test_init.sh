#!/usr/bin/env bash
# Proof for the first-run in-place transform (Phase B3, `sync.py --init`).
#
# Clones pendulum into a temp dir, copies in the WORKING-tree sync.py (so the test
# exercises uncommitted changes too), and transforms the clone into a fake project.
# Never touches the real pendulum repo.
#
# Run:  make sync-test-init   (or: bash cortex/sync/test_init.sh)
set -u

PEND="$(cd "$(dirname "$0")/../.." && pwd)"
C="$(mktemp -d "${TMPDIR:-/tmp}/sync-init-test.XXXXXX")"
fail=0
chk(){ if eval "$2"; then echo "  ok  $1"; else echo "  XX  $1"; fail=1; fi; }
trap 'rm -rf "$C"' EXIT

git clone -q "$PEND" "$C"
cp "$PEND/cortex/sync/sync.py" "$C/cortex/sync/sync.py"   # exercise the working engine
cd "$C"
git config user.email "test@sync.local"; git config user.name "Sync Test"
printf 'PROJECT=zion\nAPP=app.zion\nAPI=api.zion\n' > .cortex-sync.conf

echo "== guard: refuses on pendulum =="
printf 'PROJECT=pendulum\nAPP=app\nAPI=api\n' > .pendulum-conf
chk "refuses --init when PROJECT=pendulum" \
  "cp .pendulum-conf .cortex-sync.conf && ! python3 cortex/sync/sync.py --init >/dev/null 2>&1"
printf 'PROJECT=zion\nAPP=app.zion\nAPI=api.zion\n' > .cortex-sync.conf  # restore

echo "== dry-run =="
python3 cortex/sync/sync.py --init --dry-run > "$C/.dry.txt" 2>&1
chk "dry-run plans the renames" "grep -q 'celer/ -> app.zion/' '$C/.dry.txt'"
chk "dry-run wrote nothing (celer/ still there)" "test -d '$C/celer'"

echo "== apply =="
python3 cortex/sync/sync.py --init > "$C/.apply.txt" 2>&1
chk "celer -> app.zion"        "test -d '$C/app.zion'"
chk "arcus -> api.zion"        "test -d '$C/api.zion'"
chk "no celer/ left"           "! test -d '$C/celer'"
chk "no arcus/ left"           "! test -d '$C/arcus'"
chk "content transformed (README)"   "grep -q 'app.zion' '$C/app.zion/README.md'"
chk "no 'celer' left in vite.config" "! grep -q 'celer' '$C/app.zion/vite.config.ts'"
chk "rule filename tokens preserved" "test -f '$C/.cursor/rules/celer-01-vue-checklist.mdc'"

echo "== engine self-exclusion (must not rewrite itself) =="
# NOTE: the engine legitimately MENTIONS the example project 'zion' in comments/docs, so
# 'grep zion' is not a transform signal. Pristine canaries: byte-identical source for the .py,
# and the surviving 'pendulum' source-name in the docs (a transform would rewrite it to 'zion').
chk "engine sync.py byte-identical to source (pristine)" \
  "diff -q '$PEND/cortex/sync/sync.py' '$C/cortex/sync/sync.py' >/dev/null"
chk "engine sync.py rewrite table intact (not self-mangled)" \
  "grep -q '(\"celer\", app)' '$C/cortex/sync/sync.py'"
chk "engine RUNBOOK keeps the 'pendulum' source name (not rewritten to project)" \
  "grep -q 'pendulum' '$C/cortex/sync/RUNBOOK.md'"
chk "manifest.tsv IS transformed (path tokens for local --audit)" \
  "grep -q 'app.zion' '$C/cortex/sync/manifest.tsv'"
chk "init reports the engine was left pristine" \
  "grep -q 'engine left pristine' '$C/.apply.txt'"

echo
if [ $fail -eq 0 ]; then echo "INIT PROOF PASS"; else echo "INIT PROOF FAIL"; fi
exit $fail
