#!/usr/bin/env bash

set -euo pipefail

if [[ -z "${DUMP_OUT:-}" ]]; then
  echo "DUMP_OUT env is not set" >&2
  exit 1
fi

od -An -tx1 -v | tr -d ' \n' > "$DUMP_OUT"
