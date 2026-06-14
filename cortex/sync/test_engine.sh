#!/usr/bin/env bash
# End-to-end proof for the cortex MERGE engine (Phase B2a).
#
# Exercises sync.py against a DISPOSABLE target in a temp dir: dry-run, T0
# orphan-seed, OVERWRITE-to-worktree, the vendor branch, a baseline merge,
# idempotency, conflict surfacing, and deletion propagation. Reads the real
# vigil repo read-only; never touches any real project.
#
# Run:  make sync-test-engine   (or: bash cortex/sync/test_engine.sh)
#
# NB: no `pipefail` — `git … | grep -q` SIGPIPEs git on early match, which
# would false-fail the pipe.
set -u

PEND="$(cd "$(dirname "$0")/../.." && pwd)"
SYNC="$PEND/cortex/sync/sync.py"
T="$(mktemp -d "${TMPDIR:-/tmp}/sync-engine-test.XXXXXX")"
WT="$(mktemp -d "${TMPDIR:-/tmp}/sync-engine-wt.XXXXXX")"; rm -rf "$WT"
fail=0
chk(){ if eval "$2"; then echo "  ok  $1"; else echo "  XX  $1"; fail=1; fi; }
cleanup(){ rm -rf "$T" "$WT"; }
trap cleanup EXIT

cd "$T"
git init -q
git config user.email "test@sync.local"
git config user.name "Sync Test"
printf 'PROJECT=ziontest\nAPP=app.ziontest\nAPI=api.ziontest\n' > .cortex-sync.conf
mkdir -p vitrum   # this target opted INTO vitrum but NOT codelumen/liquen (subproject scoping)
git add .cortex-sync.conf && git commit -qm "init target"

echo "== dry-run =="
# --force short-circuits the B2b source-green gate so this proof stays hermetic
# (no gh/network). The gate's own decision logic is covered by test_sync.py.
python3 "$SYNC" --to "$T" --dry-run --force > "$T/.dry.txt" 2>&1
chk "dry-run plans orphan vendor branch" "grep -q 'create (orphan)' '$T/.dry.txt'"
chk "dry-run shows a name-mapped path"   "grep -q 'ziontest/' '$T/.dry.txt'"
chk "dry-run wrote no branch"            "! git show-ref --verify --quiet refs/heads/vigil-upstream"
chk "dry-run wrote no .claude"           "! test -e '$T/.claude'"

echo "== real T0 sync =="
python3 "$SYNC" --to "$T" --yes --force > "$T/.t0.txt" 2>&1
chk "vigil-upstream branch created"        "git show-ref --verify --quiet refs/heads/vigil-upstream"
chk "OVERWRITE applied to worktree (.claude)" "test -d '$T/.claude'"
chk "OVERWRITE applied to worktree (cortex)"  "test -d '$T/cortex'"
chk "MERGE not on main yet"                   "! test -e '$T/app.ziontest'"
chk "vendor branch holds mapped app.ziontest" "git ls-tree -r --name-only vigil-upstream | grep -q '^app.ziontest/'"
chk "vendor branch holds mapped api.ziontest" "git ls-tree -r --name-only vigil-upstream | grep -q '^api.ziontest/'"
chk "vendor branch has NO app.vigil/ path"        "! ( git ls-tree -r --name-only vigil-upstream | grep -q '^app.vigil/' )"
chk "vendor branch excludes OVERWRITE"        "! ( git ls-tree -r --name-only vigil-upstream | grep -q '^.claude/' )"
chk "opted-IN sub-project synced (vitrum)"    "git ls-tree -r --name-only vigil-upstream | grep -q '^vitrum/'"
chk "opted-OUT sub-project skipped (codelumen)" "! ( git ls-tree -r --name-only vigil-upstream | grep -q '^codelumen/' )"
chk "opted-OUT sub-project skipped (liquen)"  "! ( git ls-tree -r --name-only vigil-upstream | grep -q '^liquen/' )"

echo "== T0 baseline merge =="
git add -A && git commit -qm "T0 overwrite layer"
git merge --allow-unrelated-histories -q -m "T0 baseline" vigil-upstream
chk "merge brought app.ziontest into main" "test -d '$T/app.ziontest'"
chk "merge brought api.ziontest into main" "test -d '$T/api.ziontest'"
chk "content transformed in README"        "grep -q 'app.ziontest' '$T/app.ziontest/README.md'"
chk "no literal app.vigil/ dir on main"        "! test -d '$T/app.vigil'"
chk "lock files stayed LOCAL"              "! ( git ls-tree -r --name-only vigil-upstream | grep -q 'package-lock.json' )"

echo "== triage (B2c) =="
# Fresh sync: every unit's based-on == vigil version == project version -> all unchanged.
python3 "$SYNC" --triage --to "$T" > "$T/.triage0.txt" 2>&1
chk "fresh sync: 0 diverged"     "grep -qE 'diverged +0' '$T/.triage0.txt'"
chk "fresh sync: 0 clean-update" "grep -qE 'clean-update +0' '$T/.triage0.txt'"
# Drift two units: one pristine-but-old (clean-update), one locally edited (diverged).
setver(){ python3 - "$1" "$2" "$3" <<'PY'
import sys, re
path, based, ver = sys.argv[1:4]
t = open(path).read()
t = re.sub(r'(?m)^version: .*$', f'version: {ver}', t, count=1)
t = re.sub(r'(?m)^based-on: .*$', f'based-on: {based}', t, count=1)
open(path, 'w').write(t)
PY
}
setver "$T/app.ziontest/src/modules/User/views/Users/RULES.md" 0.0.1 0.0.1   # clean-update
setver "$T/app.ziontest/src/components/atoms/AFormError/RULES.md" 0.0.1 9.9.9 # diverged (ALogo is LOCAL now)
python3 "$SYNC" --triage --to "$T" > "$T/.triage1.txt" 2>&1
chk "drift: UsersView -> clean-update" "grep -q 'clean-update.*Users' '$T/.triage1.txt'"
chk "drift: AFormError -> diverged"    "grep -q 'diverged.*AFormError' '$T/.triage1.txt'"
chk "drift: clean-update shows a CHANGELOG excerpt" "grep -q '##' '$T/.triage1.txt'"

echo "== idempotent re-run =="
python3 "$SYNC" --to "$T" --yes --force > "$T/.idem.txt" 2>&1
chk "re-run makes no new vendor commit" "grep -q 'already at vigil' '$T/.idem.txt'"

echo "== B2b source-green gate blocks when CI can't be verified (no --force) =="
# Deterministic + offline: a gh stub that fails makes the gate resolve to UNKNOWN,
# so a no-force sync must refuse before writing anything.
GHSTUB="$(mktemp -d)"; printf '#!/bin/sh\nexit 1\n' > "$GHSTUB/gh"; chmod +x "$GHSTUB/gh"
PATH="$GHSTUB:$PATH" python3 "$SYNC" --to "$T" --yes > "$T/.gate.txt" 2>&1
gate_rc=$?
rm -rf "$GHSTUB"
chk "gate blocks (nonzero exit) when source CI is unverifiable" "[ $gate_rc -ne 0 ]"
chk "gate prints a refusal message"                            "grep -q 'Refusing to sync' '$T/.gate.txt'"

echo "== conflict surfacing (simulated upstream advance vs local edit) =="
printf '\nLOCAL PROJECT EDIT\n' >> "$T/app.ziontest/README.md"
git commit -q -am "local: customise README"
git worktree add -q "$WT" vigil-upstream
printf '# app.ziontest UPSTREAM CHANGE\n' > "$WT/app.ziontest/README.md"
git -C "$WT" commit -q -am "upstream: change README"
git worktree remove --force "$WT"
git merge vigil-upstream > "$T/.conflict.txt" 2>&1
chk "git merge reports a conflict"        "grep -qi 'conflict' '$T/.conflict.txt'"
chk "conflict markers present in file"    "grep -q '<<<<<<<' '$T/app.ziontest/README.md'"

echo "== T0 auto-resolve by mode (Phase C) =="
python3 "$SYNC" --resolve-t0 --to "$T" > "$T/.resolve.txt" 2>&1
git diff --name-only --diff-filter=U > "$T/.unresolved.txt"
chk "resolve-t0 leaves 0 unresolved"          "! [ -s '$T/.unresolved.txt' ]"
chk "resolve-t0 took upstream for MERGE file" "grep -q 'UPSTREAM CHANGE' '$T/app.ziontest/README.md'"
chk "resolve-t0 cleared conflict markers"     "! grep -q '<<<<<<<' '$T/app.ziontest/README.md'"
git merge --abort 2>/dev/null || git reset --hard -q HEAD

echo "== deletion propagation =="
python3 - "$T" "$PEND" <<'PY' > "$T/.del.txt" 2>&1
import sys
from pathlib import Path
sys.path.insert(0, str(Path(sys.argv[2]) / "cortex" / "sync"))
from sync import load_conf, load_manifest, files_by_mode, build_merge_plan, generate_upstream, run
target, pend = Path(sys.argv[1]), Path(sys.argv[2])
conf, rules = load_conf(target), load_manifest(pend)
plan = build_merge_plan(pend, files_by_mode(pend, rules)["MERGE"], conf)
reduced = [t for t in plan if t[0] != "app.ziontest/README.md"]
generate_upstream(target, reduced, run(["git", "rev-parse", "HEAD"], pend), dry_run=False)
PY
chk "removed file drops off the vendor branch" "! ( git ls-tree -r --name-only vigil-upstream | grep -q '^app.ziontest/README.md$' )"
chk "other files remain on the vendor branch"  "git ls-tree -r --name-only vigil-upstream | grep -q '^api.ziontest/'"

echo
if [ $fail -eq 0 ]; then echo "ENGINE PROOF PASS"; else echo "ENGINE PROOF FAIL"; fi
exit $fail
