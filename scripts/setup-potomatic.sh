#!/usr/bin/env bash
# Install the translation CLI dependencies separately from the plugin's npm project.
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
POTOMATIC_DIR="$PROJECT_DIR/vendor/publishpress/translations/potomatic"

if [[ ! -f "$POTOMATIC_DIR/package.json" ]]; then
    echo "Potomatic is missing. Run composer install first." >&2
    exit 1
fi

npm --prefix "$POTOMATIC_DIR" install --omit=dev --no-audit --no-fund
