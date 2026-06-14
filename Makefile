.PHONY: install app build preview docs-install docs docs-gen docs-build docs-preview unit integration e2e pest test test-all pre-commit sync-mirror sync-mirror-check handoff fresh clear sanitize sync-audit sync-test sync-test-engine sync-test-init

install:
	cd app.vigil && npm install

app:
	cd app.vigil && npm run dev

build:
	cd app.vigil && npm run build

preview:
	cd app.vigil && npm run build && npm run preview

# Codelumen: install the handbook's deps
docs-install:
	cd codelumen && npm install

# Codelumen: run the handbook dev server (regenerates rule pages first)
docs:
	cd codelumen && npm run docs:dev

# Codelumen: regenerate rule pages from .claude/rules
docs-gen:
	cd codelumen && npm run docs:gen

# Codelumen: build the handbook (regenerates rule pages first)
docs-build:
	cd codelumen && npm run docs:build

# Codelumen: build then preview the handbook
docs-preview:
	cd codelumen && npm run docs:build && npm run docs:preview

# app.vigil: unit + integration specs (Vitest)
unit:
	cd app.vigil && npm run test:unit

# Alias — unit suite includes integration specs
integration: unit

# app.vigil: e2e (Playwright — requires dev server + backend)
e2e:
	cd app.vigil && npm run test:e2e

# Arcus: Pest feature + unit tests
pest:
	cd api.vigil && vendor/bin/pest

# Compat alias kept so existing scripts that call `make test` still work
test: pest

# Run everything: app.vigil unit/integration + app.vigil e2e + api.vigil pest
test-all: unit e2e pest

# Eject a clean client-handoff copy: strip all vigil AI tooling, keep
# component RULES.md, fresh git history. Operates on a COPY, never this repo.
# Options: OUT=<dir> PROJECTS="app.vigil api.vigil" VERIFY=1 FORCE=1
handoff:
	python3 cortex/sync/handoff.py \
		$(if $(OUT),--out $(OUT)) \
		$(if $(PROJECTS),--projects "$(PROJECTS)") \
		$(if $(VERIFY),--verify) \
		$(if $(FORCE),--force)

# Regenerate .cursor/rules mirrors from their canonical .claude sources.
sync-mirror:
	python3 cortex/sync/cursor_mirror.py

# Fail if any .cursor mirror has drifted from .claude (no writes).
sync-mirror-check:
	python3 cortex/sync/cursor_mirror.py --check

# Mechanical pre-commit gate: cursor-mirror parity, then lint + format +
# locales, then the full test suite. The /pre-commit command runs this, then
# the AI convention reviews on top.
pre-commit: sync-mirror-check sanitize test-all

# Cortex sync: manifest coverage audit + classification unit tests
sync-audit:
	python3 cortex/sync/sync.py --audit

sync-test:
	python3 cortex/sync/test_sync.py

# Cortex sync: end-to-end engine proof against a disposable temp clone
sync-test-engine:
	bash cortex/sync/test_engine.sh

# Cortex sync: first-run in-place transform proof (--init)
sync-test-init:
	bash cortex/sync/test_init.sh

sanitize:
	cd app.vigil && npm run lint && npm run format && npm run sort-locales

fresh:
	cd api.vigil && php artisan migrate:fresh --seed

clear:
	cd api.vigil && php artisan optimize:clear
