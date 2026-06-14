#!/usr/bin/env python3
"""
Cortex sync — propagate pendulum's tooling and code to a downstream project.

Pendulum is the source of truth. Downstream projects (impressao, etc.) keep the
same rule FILENAMES (celer-07-view-patterns.mdc, arcus-api-architecture.mdc) but
their CONTENT refers to the project's own folder names (app.impressao / api.impressao).

This tool copies shared files and rewrites the project WORDS in their content,
while protecting the rule filename TOKENS (celer-NN, celer-folder-, arcus-).

Classification comes from cortex/sync/manifest.tsv, which assigns each path a MODE:
    OVERWRITE  write straight to the project worktree (the AI-tooling layer)
    MERGE      land on the `pendulum-upstream` vendor branch + git-merge (B2a; not yet built)
    LOCAL      project owns it; never touched
    SKIP       never synced
First match wins; there is no implicit default (the manifest's final `*` catch-all
guarantees every tracked file resolves to a mode).

Per-target name mapping comes from the target's .cortex-sync.conf.
The applied source version is stamped into the target's .cortex-version.

Usage:
    python3 cortex/sync/sync.py --to ../impressao [--dry-run] [--yes]
    python3 cortex/sync/sync.py --audit          # classify every tracked file; verify coverage

    --audit     classify all git-tracked files, report mode coverage, exit 1 on any gap. No sync.
    --dry-run   show what would change; write nothing (default behaviour is to PROMPT)
    --yes       apply without the interactive confirmation
    --force     skip the source-green CI gate (B2b) — e.g. an unpushed local commit you trust

Before a real sync, the source-green gate (B2b) checks pendulum's own CI for the SHA
being synced and refuses to propagate from a red/pending/unverified source unless --force.

A run applies OVERWRITE files straight to the target worktree and regenerates the
`pendulum-upstream` vendor branch for the MERGE set, then stops for the human to merge.
"""
from __future__ import annotations

import argparse
import fnmatch
import json
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

UPSTREAM_BRANCH = "pendulum-upstream"

# Pendulum's OPTIONAL sibling sub-projects (see the monorepo CLAUDE.md table). celer/arcus are
# CORE — they become the project's app/api and always sync. These extras are only propagated to a
# target that bootstrapped them; a project that deleted one ("clone → delete folders I won't use")
# must not have it forced back on every sync. Their target-side path equals their pendulum name
# (no celer/arcus token), so existence is checked by the literal directory.
OPTIONAL_SUBPROJECTS = ("codelumen", "liquen", "vitrum")

# Filename-token guards: substrings that are rule FILENAMES, not project words.
# Protected before the word-rewrite, restored after, so 'celer-07' never mutates
# while 'celer/src' (a path) does.
TOKEN_GUARDS = ("celer-", "arcus-")


def run(cmd: list[str], cwd: Path) -> str:
    return subprocess.run(
        cmd, cwd=cwd, capture_output=True, text=True
    ).stdout.strip()


def load_conf(target: Path) -> dict[str, str]:
    conf_path = target / ".cortex-sync.conf"
    if not conf_path.exists():
        sys.exit(
            f"error: {conf_path} not found.\n"
            "Create it with: PROJECT=<name>  APP=<app-dir>  API=<api-dir>\n"
            "Example for impressao:\n"
            "  PROJECT=impressao\n  APP=app.impressao\n  API=api.impressao"
        )
    conf: dict[str, str] = {}
    for line in conf_path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, value = line.partition("=")
        conf[key.strip()] = value.strip()

    for required in ("PROJECT", "APP", "API"):
        if required not in conf:
            sys.exit(f"error: {conf_path} missing required key {required}")
    return conf


def load_manifest(pendulum: Path) -> list[tuple[str, str]]:
    manifest_path = pendulum / "cortex" / "sync" / "manifest.tsv"
    rules: list[tuple[str, str]] = []
    for line in manifest_path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "\t" not in line:
            continue
        cls, _, pattern = line.partition("\t")
        rules.append((cls.strip(), pattern.strip()))
    return rules


def classify(rel: str, rules: list[tuple[str, str]]) -> str:
    """Return the MODE for a path. First match wins. No implicit default — an
    unmatched path returns UNCLASSIFIED (a manifest gap the audit flags)."""
    for mode, pattern in rules:
        if pattern.endswith("/") and rel.startswith(pattern):
            return mode
        if "*" in pattern and fnmatch.fnmatch(rel, pattern):
            return mode
        if rel == pattern:
            return mode
    return "UNCLASSIFIED"


def tracked_files(root: Path) -> list[str]:
    """Every git-tracked path in the repo, relative to root. The only file source
    the engine considers — build artifacts are gitignored and never seen."""
    return [line for line in run(["git", "ls-files"], root).splitlines() if line]


def files_by_mode(root: Path, rules: list[tuple[str, str]]) -> dict[str, list[str]]:
    """Classify every tracked file into its MODE bucket (OVERWRITE / MERGE / LOCAL /
    SKIP / UNCLASSIFIED). The shared enumeration primitive the audit and the
    vendor-branch generator both build on."""
    buckets: dict[str, list[str]] = {}
    for rel in tracked_files(root):
        buckets.setdefault(classify(rel, rules), []).append(rel)
    return buckets


# A project folder token (celer/arcus) glued to the START of a code identifier.
# specialize() rewrites celer->app.X / arcus->api.X (DOTTED), so such a token inside an
# identifier becomes invalid syntax downstream — e.g. `CelerPreset` -> `app.zionPreset`.
# Excludes the hyphen filename tokens (celer-01, arcus-api) via the `-` in the lookbehind
# and the identifier-char lookahead (which a hyphen fails). Caught by e2e, but far too late.
IDENT_COLLISION_RE = re.compile(r"(?<![\w-])([Cc]eler|[Aa]rcus)(?=[A-Za-z0-9_])")
CODE_SUFFIXES = (".ts", ".tsx", ".js", ".mjs", ".cjs", ".vue", ".php")


def find_identifier_collisions(
    pendulum: Path, rules: list[tuple[str, str]]
) -> list[tuple[str, int, str]]:
    """MERGE-classified code files where a project folder token starts a code identifier —
    name transform would produce invalid syntax. Returns [(rel, line_no, line)]."""
    hits: list[tuple[str, int, str]] = []
    for rel in files_by_mode(pendulum, rules).get("MERGE", []):
        if not rel.endswith(CODE_SUFFIXES):
            continue
        try:
            text = (pendulum / rel).read_text(encoding="utf-8")
        except (UnicodeDecodeError, OSError):
            continue
        for line_no, line in enumerate(text.splitlines(), 1):
            if IDENT_COLLISION_RE.search(line):
                hits.append((rel, line_no, line.strip()[:100]))
    return hits


def audit(pendulum: Path, rules: list[tuple[str, str]]) -> int:
    """Classify every git-tracked file; report mode coverage; fail on any gap."""
    by_mode = files_by_mode(pendulum, rules)
    total = sum(len(v) for v in by_mode.values())

    print(f"Classified {total} tracked files against the manifest:\n")
    for mode in ("OVERWRITE", "MERGE", "LOCAL", "SKIP"):
        print(f"  {mode:11} {len(by_mode.get(mode, [])):>4}")

    # LOCAL + SKIP are small and worth eyeballing — print them in full.
    for mode in ("LOCAL", "SKIP"):
        for rel in by_mode.get(mode, []):
            print(f"    {mode:9} {rel}")

    unclassified = by_mode.get("UNCLASSIFIED", [])
    if unclassified:
        print(f"\n  UNCLASSIFIED {len(unclassified)}  — manifest gap, every file must classify:")
        for rel in unclassified:
            print(f"    {rel}")
        return 1

    collisions = find_identifier_collisions(pendulum, rules)
    if collisions:
        print(f"\n  IDENTIFIER COLLISIONS {len(collisions)}  — project token starts a code "
              "identifier; name transform would emit invalid syntax (rename in source):")
        for rel, line_no, line in collisions:
            print(f"    {rel}:{line_no}  {line}")
        return 1

    print("\nCoverage complete — every tracked file resolves to a mode; "
          "no project-token identifier collisions.")
    return 0


def specialize(text: str, conf: dict[str, str]) -> str:
    """Rewrite project WORDS, preserving celer-/arcus- filename tokens."""
    app, api, project = conf["APP"], conf["API"], conf["PROJECT"]
    # 1. hide filename tokens
    for i, guard in enumerate(TOKEN_GUARDS):
        text = text.replace(guard, f"\x00{i}\x00")
    # 2. rewrite words (longest / most-specific first)
    for src, dst in (
        ("celer", app),
        ("arcus", api),
        ("Pendulum", project),
        ("pendulum", project),
        (" (Celer)", ""),
        ("(Celer)", ""),
        ("Celer", app),
    ):
        text = text.replace(src, dst)
    # 3. restore filename tokens
    for i, guard in enumerate(TOKEN_GUARDS):
        text = text.replace(f"\x00{i}\x00", guard)
    return text


def build_merge_plan(
    pendulum: Path,
    merge_files: list[str],
    conf: dict[str, str],
) -> list[tuple[str, bytes, bool]]:
    """Compute what the `pendulum-upstream` branch should contain for the MERGE set.

    For each MERGE file: name-map its PATH (celer/ -> app.X/, arcus/ -> api.X/) and
    transform its CONTENT (text only; binaries copy verbatim). Returns a list of
    (target_rel_path, new_bytes, is_binary). Pure — reads pendulum and computes the
    desired branch content; performs no writes and no git. The vendor-branch
    generator (B2a.4) executes this plan; --dry-run reports it.
    """
    plan: list[tuple[str, bytes, bool]] = []
    for rel in merge_files:
        raw = (pendulum / rel).read_bytes()
        try:
            text = raw.decode("utf-8")
            is_binary = False
        except UnicodeDecodeError:
            is_binary = True
        new_bytes = raw if is_binary else specialize(text, conf).encode("utf-8")
        target_rel = specialize(rel, conf)  # name-map the path itself
        plan.append((target_rel, new_bytes, is_binary))
    return plan


def git(repo: Path, *args: str, stdin: str | None = None,
        check: bool = True) -> subprocess.CompletedProcess[str]:
    """Run a git command in `repo`. Exits with the stderr on failure when check=True."""
    proc = subprocess.run(
        ["git", "-C", str(repo), *args],
        capture_output=True, text=True, input=stdin,
    )
    if check and proc.returncode != 0:
        sys.exit(f"error: git {' '.join(args)} failed in {repo}:\n{proc.stderr.strip()}")
    return proc


def branch_exists(repo: Path, branch: str) -> bool:
    return git(repo, "show-ref", "--verify", "--quiet",
               f"refs/heads/{branch}", check=False).returncode == 0


def generate_upstream(target: Path, plan: list[tuple[str, bytes, bool]],
                      src_sha: str, dry_run: bool) -> int:
    """Regenerate the `pendulum-upstream` vendor branch in `target` to hold exactly
    `plan` (the transformed + name-mapped MERGE set), via a throwaway `git worktree`
    so the user's checkout is never disturbed. Commits and STOPS — the human merges.

    First run seeds an orphan branch (plumbing: empty tree -> root commit, no main-tree
    checkout, git-2.39 compatible). Later runs check it out, clear it, rewrite the plan,
    and commit — so adds, edits, and deletions all flow. Does NOT advance .cortex-version
    (that happens post-merge, per DESIGN lifecycle step 5).
    """
    exists = branch_exists(target, UPSTREAM_BRANCH)
    count = len(plan)

    if dry_run:
        action = "update" if exists else "create (orphan)"
        print(f"\n[dry-run] MERGE: would {action} '{UPSTREAM_BRANCH}' in {target.name} "
              f"with {count} file(s):")
        for target_rel, _bytes, is_bin in plan[:8]:
            print(f"    {'BIN ' if is_bin else '    '}{target_rel}")
        if count > 8:
            print(f"    … and {count - 8} more")
        return 0

    # Seed an orphan branch on first run, without checking out in the main worktree.
    if not exists:
        empty_tree = git(target, "mktree", stdin="").stdout.strip()
        root = git(target, "commit-tree", empty_tree, "-m",
                   "chore: seed pendulum-upstream vendor branch").stdout.strip()
        git(target, "branch", UPSTREAM_BRANCH, root)

    parent = Path(tempfile.mkdtemp(prefix="pendulum-sync-"))
    worktree = parent / "upstream"  # git worktree add creates this
    try:
        git(target, "worktree", "add", "--quiet", str(worktree), UPSTREAM_BRANCH)

        # Clear tracked content (so deletions propagate), keep .git.
        for child in worktree.iterdir():
            if child.name == ".git":
                continue
            shutil.rmtree(child) if child.is_dir() else child.unlink()

        for target_rel, new_bytes, _is_bin in plan:
            dst = worktree / target_rel
            dst.parent.mkdir(parents=True, exist_ok=True)
            dst.write_bytes(new_bytes)

        git(worktree, "add", "-A")
        if git(worktree, "diff", "--cached", "--quiet", check=False).returncode == 0:
            print(f"  MERGE: '{UPSTREAM_BRANCH}' already at pendulum@{src_sha[:12]} — no new commit.")
        else:
            git(worktree, "commit", "--quiet", "-m",
                f"sync: pendulum@{src_sha[:12]} -> {UPSTREAM_BRANCH} ({count} files)")
            print(f"  MERGE: committed {count} file(s) to '{UPSTREAM_BRANCH}' (pendulum@{src_sha[:12]}).")
    finally:
        git(target, "worktree", "remove", "--force", str(worktree), check=False)
        shutil.rmtree(parent, ignore_errors=True)
    return 0


# ---- Source-green gate (Phase B2b): don't propagate from a red/unverified source ----

# GitHub check-run conclusions that mean "this check did not pass".
CHECK_FAIL_CONCLUSIONS = {
    "failure", "timed_out", "cancelled", "action_required", "startup_failure", "stale",
}


def evaluate_source_checks(
    runs: list[dict], statuses: list[dict]
) -> tuple[str, str]:
    """Pure rollup of a commit's CI signal — no network, so it is unit-tested directly.

    `runs`     = GitHub check-run objects   (each {name, status, conclusion}).
    `statuses` = legacy commit-status objects (each {context, state}).
    Returns (state, detail) with state in GREEN / RED / PENDING / NO_CHECKS.
    A failure outranks a pending; a pending outranks success; no signal at all is NO_CHECKS.
    """
    if not runs and not statuses:
        return "NO_CHECKS", "no CI checks reported for this commit"

    failing = [run.get("name", "?") for run in runs
               if run.get("conclusion") in CHECK_FAIL_CONCLUSIONS]
    failing += [status.get("context", "?") for status in statuses
                if status.get("state") in ("failure", "error")]
    if failing:
        return "RED", "failing: " + ", ".join(sorted(set(failing))[:6])

    pending = [run.get("name", "?") for run in runs if run.get("status") != "completed"]
    pending += [status.get("context", "?") for status in statuses
                if status.get("state") == "pending"]
    if pending:
        return "PENDING", "still running: " + ", ".join(sorted(set(pending))[:6])

    return "GREEN", f"{len(runs) + len(statuses)} check(s) passed"


def source_status(pendulum: Path, sha: str) -> tuple[str, str]:
    """Query the source repo's CI rollup for `sha` via `gh`, then `evaluate_source_checks`.

    Adds two states the pure evaluator can't see: UNPUSHED (the SHA is not on the remote —
    GitHub 422/404, the common 'committed but not pushed' case) and UNKNOWN (no gh / no
    remote — verification impossible). Network-bound; isolated so the decision logic stays pure.
    """
    if shutil.which("gh") is None:
        return "UNKNOWN", "gh CLI not installed — cannot verify source CI"

    nwo = run(["gh", "repo", "view", "--json", "nameWithOwner", "-q", ".nameWithOwner"], pendulum)
    if not nwo:
        return "UNKNOWN", "no GitHub remote — cannot verify source CI"

    runs_proc = subprocess.run(
        ["gh", "api", f"repos/{nwo}/commits/{sha}/check-runs",
         "--paginate", "--jq", ".check_runs[] | {name, status, conclusion}"],
        cwd=pendulum, capture_output=True, text=True,
    )
    if runs_proc.returncode != 0:
        err = (runs_proc.stderr or "").strip()
        if "No commit found" in err or "(HTTP 422)" in err or "(HTTP 404)" in err:
            return "UNPUSHED", f"commit {sha[:12]} is not on {nwo} — push it before syncing"
        return "UNKNOWN", f"gh error: {err.splitlines()[0] if err else 'unknown'}"
    runs = [json.loads(line) for line in runs_proc.stdout.splitlines() if line.strip()]

    status_proc = subprocess.run(
        ["gh", "api", f"repos/{nwo}/commits/{sha}/status",
         "--jq", ".statuses[] | {context, state}"],
        cwd=pendulum, capture_output=True, text=True,
    )
    statuses: list[dict] = []
    if status_proc.returncode == 0:
        statuses = [json.loads(line) for line in status_proc.stdout.splitlines() if line.strip()]

    return evaluate_source_checks(runs, statuses)


def enforce_source_green(pendulum: Path, sha: str, force: bool, dry_run: bool) -> None:
    """B2b precondition: refuse to sync from a source whose CI isn't green.

    GREEN proceeds; every other state BLOCKS unless --force. --force short-circuits the
    network call entirely (keeps the engine proof hermetic). On --dry-run the gate is
    informational — it reports the state but never blocks (a dry run writes nothing).
    """
    if force:
        print("Source CI gate:     skipped (--force)")
        return

    state, detail = source_status(pendulum, sha)
    glyph = {"GREEN": "✓", "RED": "✗", "PENDING": "…",
             "NO_CHECKS": "?", "UNPUSHED": "✗", "UNKNOWN": "?"}.get(state, "?")
    print(f"Source CI gate:     {glyph} {state} — {detail}")

    if state == "GREEN":
        return
    if dry_run:
        print("  [dry-run] gate is informational; a real run would BLOCK here without --force.")
        return

    sys.exit(
        f"error: source CI is not green ({state}). Refusing to sync.\n"
        "  Wait for / fix CI at the synced SHA, or pass --force to override\n"
        "  (e.g. an unpushed local commit you trust)."
    )


# ---- T0 conflict auto-resolution (Phase C follow-up): mechanical by-mode merge ----

DEP_SECTIONS = {
    "package.json": ("dependencies", "devDependencies"),
    "composer.json": ("require", "require-dev"),
}


def union_json(ours_text: str, theirs_text: str, sections: tuple[str, ...]) -> str:
    """Union two JSON dependency manifests. Upstream (theirs) is the base — it carries the
    test toolchain — then any project-only keys from ours are added back (theirs wins on a
    shared key). Other top-level keys come from theirs. Raises on unparseable input."""
    ours, theirs = json.loads(ours_text), json.loads(theirs_text)
    merged = dict(theirs)
    for section in sections:
        combined = dict(theirs.get(section, {}))
        for key, value in ours.get(section, {}).items():
            combined.setdefault(key, value)
        if combined:
            merged[section] = dict(sorted(combined.items()))
    return json.dumps(merged, indent=2) + "\n"


def project_classify(rel: str, rules: list[tuple[str, str]], conf: dict[str, str]) -> str:
    """Classify a PROJECT-side path by reverse-mapping it to its pendulum path first
    (app.X -> celer, api.X -> arcus before project -> pendulum, so the dotted names unwind)."""
    pend_rel = rel.replace(conf["APP"], "celer").replace(conf["API"], "arcus").replace(conf["PROJECT"], "pendulum")
    return classify(pend_rel, rules)


def resolve_t0(pendulum: Path, target: Path, conf: dict[str, str],
               rules: list[tuple[str, str]], dry_run: bool) -> int:
    """Mechanically resolve the conflicts of a T0 baseline merge, by MODE:
        MERGE                         -> upstream (theirs): T0 adopts pendulum's code/tests/config
        OVERWRITE / LOCAL             -> project  (ours):  AI layer is already current; locks/brand stay
        package.json / composer.json  -> union:            upstream base + project-only deps

    Stages the resolutions; never commits (you run the suite, then commit). **T0 only.** At Tn a
    MERGE conflict means both sides edited the same lines — an intentional-divergence judgement
    call — so this refuses unless a merge is actually in progress, and is documented for the
    baseline merge alone.
    """
    if not dry_run and not (target / ".git" / "MERGE_HEAD").exists():
        sys.exit(f"error: --resolve-t0 expects a merge in progress (run `git merge {UPSTREAM_BRANCH}` first).")

    conflicted = git(target, "diff", "--name-only", "--diff-filter=U").stdout.split()
    if not conflicted:
        print("No conflicts to resolve.")
        return 0

    plan: dict[str, list[str]] = {"theirs": [], "ours": [], "union": []}
    for rel in conflicted:
        base = rel.rsplit("/", 1)[-1]
        if base in DEP_SECTIONS:
            plan["union"].append(rel)
        elif project_classify(rel, rules, conf) == "MERGE":
            plan["theirs"].append(rel)
        else:
            plan["ours"].append(rel)

    print(f"T0 auto-resolve ({'dry-run' if dry_run else 'applied'}) — {len(conflicted)} conflict(s):")
    print(f"  upstream (theirs): {len(plan['theirs'])}   project (ours): {len(plan['ours'])}   union: {len(plan['union'])}")
    if dry_run:
        for side in ("theirs", "ours", "union"):
            for rel in plan[side][:5]:
                print(f"    {side:7} {rel}")
            if len(plan[side]) > 5:
                print(f"    {side:7} … and {len(plan[side]) - 5} more")
        print("\n[dry-run] nothing staged.")
        return 0

    for side in ("theirs", "ours"):
        for rel in plan[side]:
            if git(target, "checkout", f"--{side}", "--", rel, check=False).returncode == 0:
                git(target, "add", "--", rel)
                continue
            # one side absent (UD/DU): keep our file for 'ours', drop it for 'theirs'
            if side == "ours":
                git(target, "add", "--", rel, check=False)
            else:
                git(target, "rm", "-q", "--", rel, check=False)

    for rel in plan["union"]:
        base = rel.rsplit("/", 1)[-1]
        ours_text = git(target, "show", f":2:{rel}", check=False).stdout
        theirs_text = git(target, "show", f":3:{rel}", check=False).stdout
        try:
            (target / rel).write_text(union_json(ours_text, theirs_text, DEP_SECTIONS[base]), encoding="utf-8")
        except (json.JSONDecodeError, ValueError):
            git(target, "checkout", "--theirs", "--", rel, check=False)  # one side absent -> upstream
        git(target, "add", "--", rel)

    remaining = git(target, "diff", "--name-only", "--diff-filter=U").stdout.split()
    print(f"\nStaged. Unresolved remaining: {len(remaining)}")
    print("Next: `make test-all` (green = safe), then commit the merge and advance .cortex-version.")
    return 0 if not remaining else 1


# ---- First-run in-place transform (Phase B3): turn a fresh clone into the project ----

# The engine must NOT rewrite ITSELF. cortex/sync/ holds the executable sync engine and its
# docs, authored in the upstream celer/arcus/pendulum vocabulary that every sync depends on:
# specialize()-ing the .py files corrupts the engine (a self-modifying-code footgun — the
# rewrite table eats its own ("celer", app) entries), and rewriting the docs would mislabel
# the source repo (RUNBOOK: "run from pendulum -> your project"). manifest.tsv is the one
# exception — it maps project PATHS, so it must carry the renamed app.X/api.X folders for the
# downstream --audit to classify them.
ENGINE_DIR = "cortex/sync/"
ENGINE_TRANSFORM_ALLOW = {"cortex/sync/manifest.tsv"}


def is_engine_internal(rel: str) -> bool:
    """True for sync-engine files that init must leave pristine (everything under
    cortex/sync/ except manifest.tsv)."""
    return rel.startswith(ENGINE_DIR) and rel not in ENGINE_TRANSFORM_ALLOW


def init_in_place(root: Path, conf: dict[str, str], dry_run: bool) -> int:
    """One-time setup: transform a fresh pendulum clone INTO the project, in place.

    Renames celer/ -> app.X and arcus/ -> api.X, and `specialize()`s every tracked text
    file's content. No vendor branch — the clone *is* pendulum at fork time, there is
    nothing to sync from yet (that starts at T0, after this). Refuses to run on pendulum
    itself. The /setup-project command wraps this (conf creation, unused-module pruning).
    """
    if conf.get("PROJECT", "").lower() == "pendulum":
        sys.exit("error: --init refuses to transform pendulum itself (PROJECT=pendulum).")

    app, api = conf["APP"], conf["API"]
    tracked = tracked_files(root)

    content_changes = 0
    engine_skipped = 0
    for rel in tracked:
        if is_engine_internal(rel):
            engine_skipped += 1
            continue  # never rewrite the engine into the project's vocabulary
        path = root / rel
        raw = path.read_bytes()
        try:
            text = raw.decode("utf-8")
        except UnicodeDecodeError:
            continue  # binary — left as-is
        new = specialize(text, conf)
        if new != text:
            content_changes += 1
            if not dry_run:
                path.write_text(new, encoding="utf-8")

    renames = [(src, dst) for src, dst in (("celer", app), ("arcus", api))
               if (root / src).is_dir() and src != dst]

    print(f"Init transform ({'dry-run' if dry_run else 'applied'}) for project '{conf['PROJECT']}':")
    print(f"  content rewritten in {content_changes} file(s)")
    print(f"  engine left pristine: {engine_skipped} file(s) under {ENGINE_DIR} (manifest.tsv excepted)")
    for src, dst in renames:
        print(f"  rename  {src}/ -> {dst}/")
    if dry_run:
        print("\n[dry-run] no files written, no renames.")
        return 0

    for src, dst in renames:
        git(root, "mv", src, dst)
    print(f"\nDone. Next: delete unused modules, then `git commit` the initial project state.")
    return 0


# ---- Triage (Phase B2c): what changed per unit, and how hard to look ----

def parse_semver(version: str) -> tuple[int, int, int]:
    """'1.4.2' -> (1, 4, 2). Missing or non-numeric parts become 0."""
    parts = (version or "").strip().split(".")
    nums: list[int] = []
    for index in range(3):
        try:
            nums.append(int(parts[index]))
        except (IndexError, ValueError):
            nums.append(0)
    return nums[0], nums[1], nums[2]


def read_frontmatter(rules_path: Path) -> dict[str, str]:
    """Parse the leading `--- ... ---` block of a RULES.md into a dict of simple
    `key: value` lines (version, based-on, origin). Empty dict if absent/malformed."""
    fm: dict[str, str] = {}
    if not rules_path.exists():
        return fm
    text = rules_path.read_text(encoding="utf-8")
    if not text.startswith("---"):
        return fm
    end = text.find("\n---", 3)
    if end == -1:
        return fm
    for line in text[3:end].splitlines():
        if ":" in line:
            key, _, value = line.partition(":")
            fm[key.strip()] = value.strip()
    return fm


def changelog_excerpt_since(changelog_text: str, based_on: str) -> str:
    """Return the CHANGELOG `## x.y.z` entries newer than `based_on`, as text.
    Entries are delimited by `## ` headers; the version is the first token after it."""
    based = parse_semver(based_on)
    out: list[str] = []
    keeping = False
    for line in changelog_text.splitlines():
        if line.startswith("## "):
            header = line[3:].split()
            keeping = bool(header) and parse_semver(header[0]) > based
        if keeping:
            out.append(line)
    return "\n".join(out).strip()


def triage_state(pendulum_version: str, based_on: str, project_version: str) -> str:
    """Classify a unit by comparing pendulum's current version against the project's
    `based-on` (last synced) and the project's own current `version`."""
    pendulum_changed = parse_semver(pendulum_version) > parse_semver(based_on)
    project_edited = parse_semver(project_version) > parse_semver(based_on)
    if not pendulum_changed and not project_edited:
        return "unchanged"
    if pendulum_changed and not project_edited:
        return "clean-update"   # git fast-forwards; safe
    if pendulum_changed and project_edited:
        return "diverged"       # both sides moved; review the merge
    return "project-ahead"      # project has local edits; pendulum has nothing new


def severity(based_on: str, pendulum_version: str) -> str:
    """How big is pendulum's delta over the project's based-on — how hard to look."""
    based = parse_semver(based_on)
    pend = parse_semver(pendulum_version)
    if pend <= based:
        return "none"
    if pend[0] != based[0]:
        return "major"
    if pend[1] != based[1]:
        return "minor"
    return "patch"


def find_units(root: Path, rules: list[tuple[str, str]], mode: str = "MERGE") -> list[str]:
    """Relative dirs (under root) holding both RULES.md and CHANGELOG.md whose RULES.md
    classifies as `mode`. Uses tracked files only (no node_modules etc.)."""
    units: list[str] = []
    for rel in tracked_files(root):
        if not rel.endswith("/RULES.md") or classify(rel, rules) != mode:
            continue
        unit_dir = rel[: -len("/RULES.md")]
        if (root / unit_dir / "CHANGELOG.md").exists():
            units.append(unit_dir)
    return units


def triage(pendulum: Path, project: Path, conf: dict[str, str],
           rules: list[tuple[str, str]]) -> list[dict[str, str]]:
    """Build per-unit triage rows comparing pendulum (source) to the project (based-on)."""
    rows: list[dict[str, str]] = []
    for unit in find_units(pendulum, rules):
        pend_version = read_frontmatter(pendulum / unit / "RULES.md").get("version", "0.0.0")
        proj_unit = specialize(unit, conf)  # name-map to the project's path
        proj_rules = project / proj_unit / "RULES.md"

        if not proj_rules.exists():
            rows.append({"unit": proj_unit, "state": "new", "severity": severity("0.0.0", pend_version),
                         "pendulum": pend_version, "based_on": "—", "project": "—", "excerpt": ""})
            continue

        proj_fm = read_frontmatter(proj_rules)
        based = proj_fm.get("based-on", "0.0.0")
        proj_version = proj_fm.get("version", based)
        state = triage_state(pend_version, based, proj_version)

        excerpt = ""
        changelog = pendulum / unit / "CHANGELOG.md"
        if state in ("clean-update", "diverged") and changelog.exists():
            excerpt = changelog_excerpt_since(changelog.read_text(encoding="utf-8"), based)

        rows.append({"unit": proj_unit, "state": state, "severity": severity(based, pend_version),
                     "pendulum": pend_version, "based_on": based, "project": proj_version, "excerpt": excerpt})
    return rows


def render_triage(rows: list[dict[str, str]]) -> int:
    """Print the triage dashboard: a summary then per-unit detail for actionable states."""
    states = ("diverged", "clean-update", "new", "project-ahead", "unchanged")
    flags = {"major": "‼ ", "minor": "+ ", "patch": "· ", "none": "  "}

    counts = {state: 0 for state in states}
    for row in rows:
        counts[row["state"]] = counts.get(row["state"], 0) + 1

    print("Triage — pendulum (source) vs project (based-on):\n")
    for state in states:
        print(f"  {state:13} {counts[state]:>4}")
    print()

    ordered = sorted(rows, key=lambda r: (states.index(r["state"]), r["unit"]))
    for row in ordered:
        if row["state"] in ("unchanged", "project-ahead"):
            continue
        flag = flags.get(row["severity"], "  ")
        print(f"  [{row['state']:11}] {flag}{row['unit']}  "
              f"({row['based_on']} → {row['pendulum']}; project {row['project']})")
        for line in row["excerpt"].splitlines()[:6]:
            print(f"        {line}")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--to", help="path to target project root (required for sync)")
    parser.add_argument("--audit", action="store_true",
                        help="classify every tracked file, report coverage, exit 1 on gaps")
    parser.add_argument("--triage", action="store_true",
                        help="report per-unit version state vs the project's based-on (needs --to); no sync")
    parser.add_argument("--init", action="store_true",
                        help="first-run: transform THIS fresh clone into the project in place (needs .cortex-sync.conf)")
    parser.add_argument("--resolve-t0", action="store_true", dest="resolve_t0",
                        help="auto-resolve a T0 baseline merge by mode (needs --to; run after `git merge`)")
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--yes", action="store_true")
    parser.add_argument("--force", action="store_true",
                        help="skip the source-green CI gate (e.g. an unpushed local commit you trust)")
    args = parser.parse_args()

    pendulum = Path(__file__).resolve().parents[2]
    rules = load_manifest(pendulum)

    if args.init:
        return init_in_place(pendulum, load_conf(pendulum), args.dry_run)

    if args.audit:
        return audit(pendulum, rules)

    if not args.to:
        sys.exit("error: --to is required for sync/triage (or use --audit)")

    target = Path(args.to).resolve()
    if args.triage:
        if not target.is_dir():
            sys.exit(f"error: target {target} is not a directory")
        return render_triage(triage(pendulum, target, load_conf(target), rules))

    if args.resolve_t0:
        if not target.is_dir():
            sys.exit(f"error: target {target} is not a directory")
        return resolve_t0(pendulum, target, load_conf(target), rules, args.dry_run)

    if not target.is_dir():
        sys.exit(f"error: target {target} is not a directory")

    conf = load_conf(target)
    src_version = run(["git", "rev-parse", "HEAD"], pendulum) or "unknown"

    stamp = target / ".cortex-version"
    prev_version = stamp.read_text(encoding="utf-8").strip() if stamp.exists() else "(none)"

    buckets = files_by_mode(pendulum, rules)

    unclassified = buckets.get("UNCLASSIFIED", [])
    if unclassified:
        print(f"⚠️  {len(unclassified)} UNCLASSIFIED file(s) — manifest gap, run --audit:")
        for rel in unclassified:
            print(f"    {rel}")
        sys.exit("error: refusing to sync with unclassified files; fix the manifest first")

    # ---- OVERWRITE: transform -> write straight to the target worktree ----
    # (the AI layer; paths carry no celer/arcus dir tokens, only content is rewritten)
    overwrite_planned: list[tuple[Path, bytes, bool]] = []  # (dst, new_bytes, is_text)
    for rel in buckets.get("OVERWRITE", []):
        raw = (pendulum / rel).read_bytes()
        try:
            text = raw.decode("utf-8")
            is_text = True
        except UnicodeDecodeError:
            is_text = False
        # The sync engine itself is OVERWRITE, but must land VERBATIM, not specialized:
        # the project runs `make sync-test` against this copy, and specialize() would mangle
        # the engine's own rewrite table + identifiers. (manifest.tsv is excepted by
        # is_engine_internal — it maps project paths and must carry app.X/api.X.)
        if is_engine_internal(rel):
            new_bytes = raw
        elif is_text:
            new_bytes = specialize(text, conf).encode("utf-8")
        else:
            new_bytes = raw
        dst = target / rel
        if dst.exists() and dst.read_bytes() == new_bytes:
            continue
        overwrite_planned.append((dst, new_bytes, is_text))

    # ---- MERGE: build the vendor-branch plan (executed by generate_upstream) ----
    merge_plan = build_merge_plan(pendulum, buckets.get("MERGE", []), conf)

    # ---- scope: drop optional sub-projects this target opted out of at bootstrap ----
    opted_out = {root for root in OPTIONAL_SUBPROJECTS if not (target / root).is_dir()}
    skipped_optional = 0
    if opted_out:
        before = len(merge_plan)
        merge_plan = [entry for entry in merge_plan if entry[0].split("/", 1)[0] not in opted_out]
        skipped_optional = before - len(merge_plan)

    # ---- report ----
    print(f"Source (pendulum):  {pendulum}   @ {src_version[:12]}")
    print(f"Target:             {target}")
    print(f"Project mapping:    celer->{conf['APP']}  arcus->{conf['API']}  pendulum->{conf['PROJECT']}")
    print(f"Target version:     {prev_version[:12]} -> {src_version[:12]}")
    print()
    print(f"  OVERWRITE (direct write):  {len(overwrite_planned)} changed / {len(buckets.get('OVERWRITE', []))} total")
    print(f"  MERGE (vendor branch):     {len(merge_plan)}")
    print(f"  LOCAL (untouched):         {len(buckets.get('LOCAL', []))}")
    print(f"  SKIP:                      {len(buckets.get('SKIP', []))}")
    if opted_out:
        print(f"  opted-out sub-projects:    {skipped_optional} file(s) skipped "
              f"({', '.join(sorted(opted_out))} — not in this project)")

    # ---- B2b: source-green gate — verify pendulum's own CI before propagating ----
    enforce_source_green(pendulum, src_version, args.force, args.dry_run)

    if args.dry_run:
        for dst, _bytes, is_text in overwrite_planned[:20]:
            print(f"  OW   {dst.relative_to(target)}{'' if is_text else '  [binary]'}")
        if len(overwrite_planned) > 20:
            print(f"  … and {len(overwrite_planned) - 20} more OVERWRITE")
        generate_upstream(target, merge_plan, src_version, dry_run=True)
        print("\n[dry-run] no files written, no commits.")
        return 0

    if not args.yes:
        reply = input(
            f"\nApply OVERWRITE ({len(overwrite_planned)}) + regenerate "
            f"'{UPSTREAM_BRANCH}' ({len(merge_plan)}) in {target.name}? [y/N] "
        ).strip().lower()
        if reply != "y":
            print("aborted.")
            return 1

    for dst, new_bytes, _is_text in overwrite_planned:
        dst.parent.mkdir(parents=True, exist_ok=True)
        dst.write_bytes(new_bytes)
        if dst.suffix == ".sh":  # preserve exec bit on hook scripts
            dst.chmod(0o755)
    print(f"\nOVERWRITE: wrote {len(overwrite_planned)} file(s) to {target.name}.")

    generate_upstream(target, merge_plan, src_version, dry_run=False)

    # The engine stops here — it does NOT advance .cortex-version. That happens after
    # the human merges and the suite is green (DESIGN lifecycle step 5).
    print("\nNext steps (vendor branch committed; finish the sync by hand):")
    print(f"  1. cd {target}")
    print(f"  2. git merge {UPSTREAM_BRANCH}            # resolve any conflicts")
    print("  3. make test-all                          # green = safe to keep")
    print(f"  4. echo {src_version} > .cortex-version   # advance the marker only after green")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
