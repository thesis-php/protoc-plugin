#!/usr/bin/env bash
# Fails if tests/snapshots differ from HEAD after they have been regenerated
# (`make generate-snapshots`), i.e. the committed snapshots are stale.
set -euo pipefail

SNAPSHOTS_ROOT="tests/snapshots"
MAX_DIFF_LINES=500

# The plugin version in the header of every snapshot comes from Composer and
# depends on how the repository was checked out (branch name, detached merge
# ref in CI), so a change of that line alone is not a mismatch. A hunk that
# changes anything else is still reported in full.
VERSION_LINE='^ \* +thesis/protoc-plugin — v[^[:space:]]+$'

# Command substitutions (not process substitutions) so that a failing git
# command aborts the script instead of silently producing an empty list.
changed="$(git diff --name-status --no-renames HEAD -- "$SNAPSHOTS_ROOT")"
untracked="$(git ls-files --others --exclude-standard -- "$SNAPSHOTS_ROOT")"

mismatched=()

while IFS=$'\t' read -r status path; do
  [[ -n "$path" ]] || continue

  if [[ "$status" == "M" ]]; then
    rc=0
    git diff --quiet -I"$VERSION_LINE" HEAD -- "$path" || rc=$?
    case "$rc" in
      0) continue ;;
      1) ;;
      *) exit "$rc" ;;
    esac
  fi

  mismatched+=("$status	$path")
done <<< "$changed"

while IFS= read -r path; do
  [[ -n "$path" ]] || continue
  mismatched+=("??	$path")
done <<< "$untracked"

if [[ ${#mismatched[@]} -eq 0 ]]; then
  echo "Snapshots in $SNAPSHOTS_ROOT match the generator output."
  exit 0
fi

{
  echo "Snapshots in $SNAPSHOTS_ROOT do not match the generator output."
  echo "(M = modified, D = deleted, A = added, ?? = new untracked file; the plugin version line is ignored)"
  echo
  printf '  %s\n' "${mismatched[@]}"
  echo

  diff_output="$(git diff --no-color -I"$VERSION_LINE" HEAD -- "$SNAPSHOTS_ROOT")"
  if [[ -n "$diff_output" ]]; then
    diff_lines="$(wc -l <<< "$diff_output")"
    head -n "$MAX_DIFF_LINES" <<< "$diff_output"
    if (( diff_lines > MAX_DIFF_LINES )); then
      echo "... diff truncated ($diff_lines lines in total)"
    fi
    echo
  fi

  echo "If the change in the generated code is intended, regenerate the snapshots"
  echo "(\`make test\` or \`make generate-snapshots\`), review \`git status $SNAPSHOTS_ROOT\`"
  echo "and \`git diff $SNAPSHOTS_ROOT\`, and commit them together with the code change."
  echo "Otherwise the change in the generator output is a regression."
} >&2

exit 1
