#!/bin/bash
# Blocks dangerous bash operations before they run.
# Exit code 2 = block the command and show message to Claude.

CMD=$(echo "$CLAUDE_TOOL_INPUT" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('command',''))" 2>/dev/null)

BLOCKED_PATTERNS=(
  "rm -rf /"
  "rm -rf ~"
  "drop table"
  "DROP TABLE"
  "git push --force"
  "git push -f"
  "> /dev/sda"
)

for pattern in "${BLOCKED_PATTERNS[@]}"; do
  if echo "$CMD" | grep -qF "$pattern"; then
    echo "BLOCKED: Dangerous command pattern detected: $pattern" >&2
    exit 2
  fi
done

exit 0
