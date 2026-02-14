#!/usr/bin/env bash
set -euo pipefail

ROOT="tests/testdata"
PLUGIN="$(realpath $(pwd)/tests/tools/plugin.sh)"

if [[ ! -x "$PLUGIN" ]]; then
  echo "Plugin not executable: $PLUGIN" >&2
  exit 1
fi

find "$ROOT" -type f -name '*.proto' -not -path "*/grpc/*" | while read -r proto; do
  dir="$(dirname "$proto")"
  base="$(basename "$proto" .proto)"
  out="$dir/$base.txt"

  echo "dumping $proto → $out"

  DUMP_OUT="$out" \
  protoc \
    --plugin=protoc-gen-dump="$PLUGIN" \
    --dump_out=. \
    -I "$ROOT" \
    "$proto" 2>&1 | grep -v "hasn't been updated to support optional fields" || true
done
