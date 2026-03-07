#!/usr/bin/env bash
set -euo pipefail

FIXTURES_ROOT="tests/fixtures"
TESTDATA_ROOT="tests/testdata"
SNAPSHOTS_ROOT="tests/snapshots"
PLUGIN="$(realpath "$(pwd)/bin/compiler.php")"

if [[ ! -x "$PLUGIN" ]]; then
  echo "Plugin not executable: $PLUGIN" >&2
  exit 1
fi

find "$TESTDATA_ROOT" -mindepth 2 -maxdepth 2 -type f -name '*.hex' | sort | while read -r fixture; do
  rel="${fixture#"$TESTDATA_ROOT"/}"
  fixture_dir="$FIXTURES_ROOT/$(dirname "$rel")"
  snapshot_dir="$SNAPSHOTS_ROOT/$(dirname "$rel")"

  mapfile -t all_protos < <(find "$fixture_dir" -type f -name '*.proto' | sort)
  proto_inputs=()

  for proto in "${all_protos[@]}"; do
    proto_inputs+=("${proto#"$fixture_dir"/}")
  done

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
    -I "$FIXTURES_ROOT" \
    -I "$fixture_dir" \
    "${proto_inputs[@]}"
done
