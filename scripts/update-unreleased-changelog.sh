#!/usr/bin/env bash

set -euo pipefail

CHANGELOG_FILE="${1:-CHANGELOG.md}"

if [ ! -f "$CHANGELOG_FILE" ]; then
  echo "Missing changelog file: $CHANGELOG_FILE"
  exit 1
fi

latest_version="$(awk '
  /^## \[[0-9]+\.[0-9]+\.[0-9]+\]/ {
    line = $0
    sub(/^## \[/, "", line)
    sub(/\].*$/, "", line)
    print line
    exit
  }
' "$CHANGELOG_FILE")"

if [ -z "$latest_version" ]; then
  echo "Could not determine the latest version from $CHANGELOG_FILE"
  exit 1
fi

base_ref=""
if git rev-parse -q --verify "refs/tags/$latest_version" >/dev/null 2>&1; then
  base_ref="$latest_version"
elif git rev-parse -q --verify "refs/tags/v$latest_version" >/dev/null 2>&1; then
  base_ref="v$latest_version"
fi

if [ -z "$base_ref" ]; then
  echo "No git tag found for latest changelog version: $latest_version"
  echo "Expected tag '$latest_version' or 'v$latest_version'."
  exit 1
fi

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

added_file="$tmp_dir/added.txt"
changed_file="$tmp_dir/changed.txt"
fixed_file="$tmp_dir/fixed.txt"
generated_body_file="$tmp_dir/generated-body.txt"
rewritten_file="$tmp_dir/changelog.rewritten.md"
final_file="$tmp_dir/changelog.final.md"

while IFS= read -r subject; do
  if [ -z "$subject" ]; then
    continue
  fi

  case "$subject" in
    "Initial plan"|\
    "Update CHANGELOG.md")
      continue
      ;;
    "docs: auto-update changelog"*|"chore: auto-update changelog"*|"chore(changelog): update unreleased section"*)
      continue
      ;;
  esac

  section="changed"
  description="$subject"

  if printf '%s\n' "$subject" | grep -Eq '^[A-Za-z]+(\([^)]+\))?(!)?: .+$'; then
    commit_type="$(printf '%s\n' "$subject" | sed -E 's/^([A-Za-z]+)(\([^)]+\))?(!)?: .*/\1/' | tr '[:upper:]' '[:lower:]')"
    description="$(printf '%s\n' "$subject" | sed -E 's/^[A-Za-z]+(\([^)]+\))?(!)?: (.+)$/\3/')"

    case "$commit_type" in
      feat)
        section="added"
        ;;
      fix)
        section="fixed"
        ;;
      *)
        section="changed"
        ;;
    esac
  fi

  case "$section" in
    added)
      printf -- '- %s\n' "$description" >> "$added_file"
      ;;
    fixed)
      printf -- '- %s\n' "$description" >> "$fixed_file"
      ;;
    changed)
      printf -- '- %s\n' "$description" >> "$changed_file"
      ;;
  esac
done < <(git log --no-merges --pretty=format:'%s' "${base_ref}..HEAD")

for section_file in "$added_file" "$changed_file" "$fixed_file"; do
  if [ -f "$section_file" ]; then
    awk '!seen[$0]++' "$section_file" > "$section_file.dedup"
    mv "$section_file.dedup" "$section_file"
  fi
done

section_written=0

if [ -s "$added_file" ]; then
  section_written=1
  printf '### Added\n\n' >> "$generated_body_file"
  cat "$added_file" >> "$generated_body_file"
  printf '\n' >> "$generated_body_file"
fi

if [ -s "$changed_file" ]; then
  section_written=1
  printf '### Changed\n\n' >> "$generated_body_file"
  cat "$changed_file" >> "$generated_body_file"
  printf '\n' >> "$generated_body_file"
fi

if [ -s "$fixed_file" ]; then
  section_written=1
  printf '### Fixed\n\n' >> "$generated_body_file"
  cat "$fixed_file" >> "$generated_body_file"
  printf '\n' >> "$generated_body_file"
fi

if [ "$section_written" -eq 0 ]; then
  printf '%s\n' '- No unreleased changes.' > "$generated_body_file"
else
  awk '
    { sub(/\r$/, ""); lines[++count] = $0 }
    END {
      start = 1
      while (start <= count && lines[start] ~ /^[[:space:]]*$/) {
        start++
      }

      end = count
      while (end >= start && lines[end] ~ /^[[:space:]]*$/) {
        end--
      }

      for (line_index = start; line_index <= end; line_index++) {
        print lines[line_index]
      }
    }
  ' "$generated_body_file" > "$generated_body_file.trimmed"
  mv "$generated_body_file.trimmed" "$generated_body_file"
fi

awk -v body_file="$generated_body_file" '
  BEGIN {
    inserted = 0
    in_unreleased = 0
  }
  /^## \[Unreleased\]$/ {
    print
    print ""
    while ((getline body_line < body_file) > 0) {
      print body_line
    }
    close(body_file)
    print ""
    inserted = 1
    in_unreleased = 1
    next
  }
  in_unreleased {
    if ($0 ~ /^## \[/) {
      in_unreleased = 0
      print
    }
    next
  }
  {
    print
  }
  END {
    if (!inserted) {
      exit 2
    }
  }
' "$CHANGELOG_FILE" > "$rewritten_file"

origin_url="$(git config --get remote.origin.url || true)"
repo_path=""

case "$origin_url" in
  git@github.com:*)
    repo_path="${origin_url#git@github.com:}"
    repo_path="${repo_path%.git}"
    ;;
  https://github.com/*)
    repo_path="${origin_url#https://github.com/}"
    repo_path="${repo_path%.git}"
    ;;
esac

if [ -n "$repo_path" ]; then
  compare_url="https://github.com/${repo_path}/compare/${base_ref}...HEAD"
  awk -v unreleased_line="[Unreleased]: ${compare_url}" '
    BEGIN {
      replaced = 0
    }
    /^\[Unreleased\]: / {
      print unreleased_line
      replaced = 1
      next
    }
    {
      print
    }
    END {
      if (!replaced) {
        print unreleased_line
      }
    }
  ' "$rewritten_file" > "$final_file"
else
  cp "$rewritten_file" "$final_file"
fi

mv "$final_file" "$CHANGELOG_FILE"

echo "Updated $CHANGELOG_FILE from commits in range ${base_ref}..HEAD"
