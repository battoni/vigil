#!/bin/bash
# Runs the appropriate formatter after Claude edits a file.
# Receives file path via CLAUDE_TOOL_INPUT (JSON).

FILE=$(echo "$CLAUDE_TOOL_INPUT" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('file_path',''))" 2>/dev/null)

if [[ -z "$FILE" ]]; then exit 0; fi

# Arcus: run Pint on edited PHP files
if [[ "$FILE" == api.vigil/*.php ]] || [[ "$FILE" == api.vigil/**/*.php ]]; then
  cd api.vigil && vendor/bin/pint --dirty --format agent 2>/dev/null
  exit 0
fi

# app.vigil: run ESLint on edited Vue/TS files
if [[ "$FILE" == app.vigil/src/*.vue ]] || [[ "$FILE" == app.vigil/src/**/*.vue ]] || \
   [[ "$FILE" == app.vigil/src/*.ts  ]] || [[ "$FILE" == app.vigil/src/**/*.ts  ]]; then
  cd app.vigil && npm run lint -- --fix "$FILE" 2>/dev/null
  exit 0
fi
