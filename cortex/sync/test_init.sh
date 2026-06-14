#!/usr/bin/env bash
# Proof for the first-run in-place transform (Phase B3, `sync.py --init`).
#
# Clones vigil into a temp dir, copies in the WORKING-tree sync.py (so the test
# exercises uncommitted changes too), and transforms the clone into a fake project.
# Never touches the real vigil repo.
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

echo "== guard: refuses on vigil =="
printf 'PROJECT=vigil\nAPP=app\nAPI=api\n' > .vigil-conf
chk "refuses --init when PROJECT=vigil" \
  "cp .vigil-conf .cortex-sync.conf && ! python3 cortex/sync/sync.py --init >/dev/null 2>&1"
printf 'PROJECT=zion\nAPP=app.zion\nAPI=api.zion\n' > .cortex-sync.conf  # restore

echo "== dry-run =="
python3 cortex/sync/sync.py --init --dry-run > "$C/.dry.txt" 2>&1
chk "dry-run plans the renames" "grep -q 'app.vigil/ -> app.zion/' '$C/.dry.txt'"
chk "dry-run wrote nothing (app.vigil/ still there)" "test -d '$C/app.vigil'"

echo "== apply =="
python3 cortex/sync/sync.py --init > "$C/.apply.txt" 2>&1
chk "app.vigil -> app.zion"        "test -d '$C/app.zion'"
chk "api.vigil -> api.zion"        "test -d '$C/api.zion'"
chk "no app.vigil/ left"           "! test -d '$C/app.vigil'"
chk "no api.vigil/ left"           "! test -d '$C/api.vigil'"
chk "content transformed (README)"   "grep -q 'app.zion' '$C/app.zion/README.md'"
chk "no 'app.vigil' left in vite.config" "! grep -q 'app.vigil' '$C/app.zion/vite.config.ts'"
chk "rule filename tokens preserved" "test -f '$C/.cursor/rules/celer-01-vue-checklist.mdc'"

echo
if [ $fail -eq 0 ]; then echo "INIT PROOF PASS"; else echo "INIT PROOF FAIL"; fi
exit $fail
