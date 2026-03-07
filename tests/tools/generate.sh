#!/usr/bin/env bash
set -euo pipefail

FIXTURES_ROOT="tests/fixtures"
TESTDATA_ROOT="tests/testdata"
PLUGIN="$(realpath $(pwd)/tests/tools/plugin.sh)"

if [[ ! -x "$PLUGIN" ]]; then
  echo "Plugin not executable: $PLUGIN" >&2
  exit 1
fi

find "$FIXTURES_ROOT" -mindepth 1 -maxdepth 1 -type d | sort | while read -r case_dir; do
  case_name="$(basename "$case_dir")"
  case_testdata_dir="$TESTDATA_ROOT/$case_name"

  mapfile -t top_level_protos < <(find "$case_dir" -maxdepth 1 -type f -name '*.proto' | sort)
  mapfile -t all_protos < <(find "$case_dir" -type f -name '*.proto' | sort)

  if [[ ${#all_protos[@]} -eq 0 ]]; then
    continue
  fi

  inputs=()
  output_name="request.hex"

  for proto in "${all_protos[@]}"; do
    inputs+=("${proto#"$case_dir"/}")
  done

  mkdir -p "$case_testdata_dir"
  out="$case_testdata_dir/$output_name"

  echo "dumping $case_name fixtures → $out"

  DUMP_OUT="$out" \
  protoc \
    --plugin=protoc-gen-dump="$PLUGIN" \
    --dump_out=. \
    --experimental_editions \
    -I "$FIXTURES_ROOT" \
    -I "$case_dir" \
    "${inputs[@]}" 2>&1 | grep -v "hasn't been updated to support optional fields" || true
done
