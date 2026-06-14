#!/usr/bin/env python3
"""Unit tests for cortex sync classification (Phase B2a.1).

Pure tests over the manifest + classify() — no git writes, no target repo.
Run:  python3 cortex/sync/test_sync.py   (exit 0 = pass, 1 = fail)
"""
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from sync import (  # noqa: E402
    build_merge_plan,
    IDENT_COLLISION_RE,
    changelog_excerpt_since,
    classify,
    evaluate_source_checks,
    find_identifier_collisions,
    files_by_mode,
    load_manifest,
    parse_semver,
    severity,
    tracked_files,
    triage_state,
    union_json,
)

ROOT = Path(__file__).resolve().parents[2]
RULES = load_manifest(ROOT)

failures: list[str] = []


def check(name: str, cond: bool) -> None:
    print(f"  {'ok ' if cond else 'XX '} {name}")
    if not cond:
        failures.append(name)


# ---- coverage invariants (no hardcoded counts — those drift as files are added) ----
buckets = files_by_mode(ROOT, RULES)
check("no UNCLASSIFIED files", "UNCLASSIFIED" not in buckets)
check(
    "buckets cover every tracked file",
    sum(len(v) for v in buckets.values()) == len(tracked_files(ROOT)),
)

# ---- specific classifications (pure string matching; existence irrelevant) ----
cases = {
    "OVERWRITE": [
        "cortex/sync/DESIGN.md",
        ".claude/commands/reviewVueConventions.md",
        ".cursor/rules/celer-01-vue-checklist.mdc",
    ],
    "MERGE": [
        ".github/workflows/ci.yml",
        "celer/e2e/auth/login.spec.ts",
        "celer/src/modules/User/views/Users/UsersView.view.vue",
        "arcus/tests/Feature/Auth/RoleTest.php",
        "codelumen/package.json",
        "Makefile",
    ],
    "LOCAL": [
        "CLAUDE.md",
        "README.md",
        ".claude/settings.json",
        ".cursor/rules/celer-folder-theme.mdc",
        "arcus/composer.lock",
        "celer/package-lock.json",
        # brand / visual identity — must never propagate (overwrites a project's look)
        "celer/src/styles/theme/colors.css",
        "celer/src/components/atoms/ALogo/ALogo.vue",
        "celer/src/components/atoms/ALogo/logo-full-light.png",
        "celer/src/assets/logo/logo-raw.svg",
        "celer/public/favicon/favicon.ico",
        "celer/public/favicon/manifest.json",
        "celer/index.html",
    ],
}
for want, paths in cases.items():
    for path in paths:
        check(f"{path} -> {want}", classify(path, RULES) == want)

# ---- mode exclusion invariants (what the vendor-branch generator relies on) ----
merge = buckets.get("MERGE", [])
check("MERGE excludes the AI layer", not any(
    m.startswith((".claude/", ".cursor/", "cortex/")) for m in merge))
check("MERGE excludes lock files", not any(
    m.endswith(("package-lock.json", "composer.lock", "pnpm-lock.yaml", "yarn.lock")) for m in merge))

overwrite = buckets.get("OVERWRITE", [])
check("OVERWRITE lives only in the AI layer", all(
    o.startswith((".claude/", ".cursor/", "cortex/")) for o in overwrite))

# ---- B2a.2: build_merge_plan — path name-mapping + content transform ----
FIXTURE_CONF = {"PROJECT": "zion", "APP": "app.zion", "API": "api.zion"}

# text file: path mapped celer/ -> app.zion/, content transformed
text_rel, text_bytes, text_bin = build_merge_plan(ROOT, ["celer/README.md"], FIXTURE_CONF)[0]
text_body = text_bytes.decode("utf-8")
check("text MERGE path mapped celer/ -> app.zion/", text_rel == "app.zion/README.md")
check("text MERGE not flagged binary", text_bin is False)
check("text MERGE content: 'Celer' rewritten away", "Celer" not in text_body)
check("text MERGE content: project name applied", "app.zion" in text_body)

# arcus path mapping arcus/ -> api.zion/
arcus_rel = build_merge_plan(ROOT, ["arcus/tests/Feature/Auth/RoleTest.php"], FIXTURE_CONF)[0][0]
check("arcus MERGE path mapped arcus/ -> api.zion/", arcus_rel == "api.zion/tests/Feature/Auth/RoleTest.php")

# binary file: path mapped, bytes copied verbatim (no transform)
BIN = "celer/public/favicon/android-icon-144x144.png"
bin_rel, bin_bytes, bin_is_binary = build_merge_plan(ROOT, [BIN], FIXTURE_CONF)[0]
check("binary MERGE path mapped", bin_rel == "app.zion/public/favicon/android-icon-144x144.png")
check("binary MERGE flagged is_binary", bin_is_binary is True)
check("binary MERGE bytes verbatim", bin_bytes == (ROOT / BIN).read_bytes())

# ---- B2c: triage pure logic ----
check("parse_semver normal", parse_semver("1.4.2") == (1, 4, 2))
check("parse_semver short", parse_semver("2") == (2, 0, 0))
check("parse_semver garbage", parse_semver("x.y") == (0, 0, 0))
check("parse_semver ordering", parse_semver("1.10.0") > parse_semver("1.9.9"))

# triage_state: (pendulum_version, based_on, project_version)
check("state unchanged", triage_state("1.2.0", "1.2.0", "1.2.0") == "unchanged")
check("state clean-update", triage_state("1.3.0", "1.2.0", "1.2.0") == "clean-update")
check("state diverged", triage_state("1.3.0", "1.2.0", "1.4.0") == "diverged")
check("state project-ahead", triage_state("1.2.0", "1.2.0", "1.5.0") == "project-ahead")

# severity of pendulum's delta over based-on
check("severity major", severity("1.2.0", "2.0.0") == "major")
check("severity minor", severity("1.2.0", "1.3.0") == "minor")
check("severity patch", severity("1.2.0", "1.2.1") == "patch")
check("severity none", severity("1.2.0", "1.2.0") == "none")

# changelog excerpt: only entries newer than based-on
_CHANGELOG = """# Changelog — X

## 1.3.0 — 2026-06-01
### Added
- new thing in 1.3

## 1.2.0 — 2026-05-30
### Added
- old thing in 1.2
"""
_excerpt = changelog_excerpt_since(_CHANGELOG, "1.2.0")
check("excerpt includes newer entry (1.3.0)", "1.3.0" in _excerpt and "new thing in 1.3" in _excerpt)
check("excerpt excludes based-on entry (1.2.0)", "1.2.0" not in _excerpt and "old thing in 1.2" not in _excerpt)
check("excerpt empty when up to date", changelog_excerpt_since(_CHANGELOG, "1.3.0") == "")

# ---- B2b: source-green gate decision logic (pure; no gh/network) ----
_completed = lambda concl: {"name": "ci", "status": "completed", "conclusion": concl}  # noqa: E731

check("gate GREEN when every run succeeded",
      evaluate_source_checks([_completed("success")], [])[0] == "GREEN")
check("gate GREEN counts neutral/skipped as passing",
      evaluate_source_checks(
          [_completed("skipped"), _completed("neutral")], [])[0] == "GREEN")
check("gate RED on a failed run",
      evaluate_source_checks([_completed("failure")], [])[0] == "RED")
check("gate RED on a legacy status error",
      evaluate_source_checks([], [{"context": "cov", "state": "error"}])[0] == "RED")
check("gate PENDING while a run is in progress",
      evaluate_source_checks(
          [{"name": "ci", "status": "in_progress", "conclusion": None}], [])[0] == "PENDING")
check("gate RED outranks PENDING",
      evaluate_source_checks(
          [{"name": "a", "status": "in_progress", "conclusion": None},
           _completed("failure")], [])[0] == "RED")
check("gate NO_CHECKS when nothing is reported",
      evaluate_source_checks([], [])[0] == "NO_CHECKS")
check("gate detail names the failing check",
      "ci" in evaluate_source_checks([_completed("failure")], [])[1])

# ---- identifier-collision guard: project token starting a code identifier ----
hit = lambda s: bool(IDENT_COLLISION_RE.search(s))  # noqa: E731
check("collision: CelerPreset flagged", hit("import CelerPreset from './theme';"))
check("collision: lowercase celerConfig flagged", hit("const celerConfig = {}"))
check("collision: ArcusClient flagged", hit("class ArcusClient {}"))
check("collision: filename token celer-01 NOT flagged", not hit("@import './celer-01-rules'"))
check("collision: path 'celer/src' NOT flagged", not hit("from 'celer/src/foo'"))
check("collision: substring 'accelerate' NOT flagged", not hit("const accelerate = true"))
check("collision: bare word celer NOT flagged", not hit("// see the celer project"))
# live source must be clean (the CelerPreset -> themePreset fix landed)
check("source has zero identifier collisions", find_identifier_collisions(ROOT, RULES) == [])

# ---- T0 union_json: upstream base + project-only deps (pure) ----
import json as _json  # noqa: E402
_ours = _json.dumps({"name": "proj", "dependencies": {"axios": "^1.0.0", "@vueuse/core": "^11"}})
_theirs = _json.dumps({"name": "app.x", "dependencies": {"axios": "^1.2.0"},
                       "devDependencies": {"vitest": "^3", "msw": "^2"}})
_merged = _json.loads(union_json(_ours, _theirs, ("dependencies", "devDependencies")))
check("union: upstream wins on shared dep (axios)", _merged["dependencies"]["axios"] == "^1.2.0")
check("union: project-only dep preserved (@vueuse/core)", _merged["dependencies"]["@vueuse/core"] == "^11")
check("union: upstream test toolchain kept", _merged["devDependencies"] == {"msw": "^2", "vitest": "^3"})
check("union: upstream top-level keys win (name)", _merged["name"] == "app.x")
check("union: deps sorted", list(_merged["dependencies"]) == sorted(_merged["dependencies"]))

print(f"\n{'PASS' if not failures else 'FAIL'} — {len(failures)} failure(s)")
sys.exit(1 if failures else 0)
