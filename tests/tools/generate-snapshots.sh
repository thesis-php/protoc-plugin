#!/usr/bin/env bash
set -euo pipefail

ROOT="tests/testdata"
SNAPSHOTS_ROOT="tests/snapshots"
PLUGIN="$(realpath "$(pwd)/bin/compiler.php")"

if [[ ! -x "$PLUGIN" ]]; then
  echo "Plugin not executable: $PLUGIN" >&2
  exit 1
fi

find "$ROOT" -mindepth 2 -maxdepth 2 -type f -name '*.txt' | sort | while read -r fixture; do
  rel="${fixture#"$ROOT"/}"
  case_dir="$(dirname "$fixture")"
  case_name="$(basename "$fixture" .txt)"
  snapshot_dir="$SNAPSHOTS_ROOT/$(dirname "$rel")"

  proto="$case_dir/$case_name.proto"
  proto_inputs=()

  if [[ -f "$proto" ]]; then
    proto_inputs+=("${proto#"$ROOT"/}")
  else
    while read -r entry; do
      proto_inputs+=("${entry#"$case_dir"/}")
    done < <(find "$case_dir" -type f -name '*.proto' | sort)
  fi

  if [[ ${#proto_inputs[@]} -eq 0 ]]; then
    echo "No .proto files found for fixture: $fixture" >&2
    exit 1
  fi

  rm -rf "$snapshot_dir"
  mkdir -p "$snapshot_dir"

  echo "generating $rel -> $snapshot_dir"

  protoc \
    --plugin=protoc-gen-custom-plugin="$PLUGIN" \
    --custom-plugin_out="$snapshot_dir" \
    -I "$ROOT" \
    -I "$case_dir" \
    "${proto_inputs[@]}"
done
