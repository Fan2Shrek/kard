#!/usr/bin/env sh

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
"$SCRIPT_DIR/console" d:m:m -n

exec "$@"
