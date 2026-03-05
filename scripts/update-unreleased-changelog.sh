#!/usr/bin/env bash

set -euo pipefail

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

if [ ! -f CHANGELOG.md ]; then
  echo "CHANGELOG.md was not found at repository root."
  exit 1
fi

read_latest_changelog_version() {
  awk '
    /^## \[[0-9]+\.[0-9]+\.[0-9]+\] - / {
      version = $0
      sub(/^## \[/, "", version)
      sub(/\].*$/, "", version)
      print version
      exit
    }
  ' CHANGELOG.md
}

latest_version="$(read_latest_changelog_version)"
if [ -z "$latest_version" ]; then
  echo "Could not determine latest version from CHANGELOG.md."
  exit 1
fi

if ! git rev-parse -q --verify "refs/tags/$latest_version" >/dev/null 2>&1; then
  echo "Tag '$latest_version' referenced by CHANGELOG.md was not found."
  echo "Create the matching tag first, or update CHANGELOG.md to the correct latest version."
  exit 1
fi

body_file="$(mktemp)"
output_file="$(mktemp)"
trap 'rm -f "$body_file" "$output_file"' EXIT

commit_subjects=()
while IFS= read -r subject; do
  if [ -z "$subject" ]; then
    continue
  fi

  case "$subject" in
    "Initial plan"|Merge\ pull\ request*|Merge\ branch*|chore\(changelog\):\ update\ unreleased\ section)
      continue
      ;;
  esac

  commit_subjects+=("$subject")
done < <(git log --reverse --format='%s' "${latest_version}..HEAD")

if [ "${#commit_subjects[@]}" -gt 0 ]; then
  printf "### Changed\n\n" > "$body_file"
  for subject in "${commit_subjects[@]}"; do
    printf -- "- %s\n" "$subject" >> "$body_file"
  done
fi

has_body=0
if [ -s "$body_file" ]; then
  has_body=1
fi

awk -v body_path="$body_file" -v has_body="$has_body" '
  BEGIN {
    in_unreleased = 0
    inserted = 0
  }
  /^## \[Unreleased\]$/ {
    print
    print ""
    if (has_body == 1) {
      while ((getline body_line < body_path) > 0) {
        print body_line
      }
      close(body_path)
      print ""
    }
    inserted = 1
    in_unreleased = 1
    next
  }
  in_unreleased {
    if ($0 ~ /^## \[/) {
      in_unreleased = 0
      print $0
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
' CHANGELOG.md > "$output_file"

mv "$output_file" CHANGELOG.md
echo "Updated CHANGELOG.md Unreleased section from commits since tag '$latest_version'."
