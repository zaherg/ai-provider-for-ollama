#!/usr/bin/env bash

set -euo pipefail

if [ "${1:-}" = "" ]; then
  echo "Usage: scripts/create-release.sh <version> [branch]"
  echo "Example: scripts/create-release.sh 0.1.2 main"
  exit 1
fi

VERSION="$1"
TARGET_BRANCH="${2:-main}"
RELEASE_DATE="$(date +%F)"

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

if ! command -v gh >/dev/null 2>&1; then
  echo "gh is required but not installed."
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "gh is not authenticated. Run: gh auth login"
  exit 1
fi

current_branch="$(git branch --show-current)"
if [ "$current_branch" != "$TARGET_BRANCH" ]; then
  echo "Current branch is '$current_branch'. Switch to '$TARGET_BRANCH' before releasing."
  exit 1
fi

if gh release view "$VERSION" >/dev/null 2>&1; then
  echo "Release '$VERSION' already exists."
  exit 1
fi

git fetch origin "$TARGET_BRANCH" --tags >/dev/null 2>&1

local_head="$(git rev-parse HEAD)"
remote_head="$(git rev-parse "origin/$TARGET_BRANCH")"
if [ "$local_head" != "$remote_head" ]; then
  echo "Local HEAD ($local_head) is not up to date with origin/$TARGET_BRANCH ($remote_head)."
  echo "Pull/rebase first, then run the release script again."
  exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "Working tree is dirty. Commit or stash changes before releasing."
  exit 1
fi

extract_changelog_section() {
  local target_version="$1"
  awk -v version="$target_version" '
    $0 ~ "^## \\[" version "\\]" { in_section=1; next }
    /^## \\[/ { if (in_section) exit }
    in_section { print }
  ' CHANGELOG.md
}

trim_blank_lines() {
  local input_file="$1"
  local output_file
  output_file="$(mktemp)"
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

      for (index = start; index <= end; index++) {
        print lines[index]
      }
    }
  ' "$input_file" > "$output_file"
  mv "$output_file" "$input_file"
}

extract_unreleased_section() {
  awk '
    /^## \[Unreleased\]/ { in_section=1; next }
    /^## \[/ { if (in_section) exit }
    in_section { print }
  ' CHANGELOG.md
}

promote_unreleased_section() {
  local target_version="$1"
  local release_date="$2"
  local body_file="$3"
  local output_file

  output_file="$(mktemp)"

  awk -v version="$target_version" -v date="$release_date" -v body_path="$body_file" '
    BEGIN {
      inserted = 0
      skipping_unreleased_body = 0
    }
    /^## \[Unreleased\]$/ {
      print
      print ""
      print "## [" version "] - " date
      print ""
      while ((getline body_line < body_path) > 0) {
        print body_line
      }
      close(body_path)
      print ""
      inserted = 1
      skipping_unreleased_body = 1
      next
    }
    skipping_unreleased_body {
      if ($0 ~ /^## \[/) {
        skipping_unreleased_body = 0
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
  ' CHANGELOG.md > "$output_file"

  mv "$output_file" CHANGELOG.md
}

set_plugin_and_readme_versions() {
  local target_version="$1"

  perl -pi -e "s/^ \\* Version: .*/ * Version: ${target_version}/" plugin.php
  perl -pi -e "s/^Stable tag: .*/Stable tag: ${target_version}/" readme.txt
}

validate_version_metadata() {
  local target_version="$1"
  local plugin_version
  local stable_tag

  plugin_version="$(sed -n 's/^ \* Version: //p' plugin.php | head -n 1 | tr -d '[:space:]')"
  stable_tag="$(sed -n 's/^Stable tag: //p' readme.txt | head -n 1 | tr -d '[:space:]')"

  if [ "$plugin_version" != "$target_version" ]; then
    echo "plugin.php version ($plugin_version) does not match expected version ($target_version)."
    exit 1
  fi

  if [ "$stable_tag" != "$target_version" ]; then
    echo "readme.txt Stable tag ($stable_tag) does not match expected version ($target_version)."
    exit 1
  fi
}

notes_file="$(mktemp)"
unreleased_file="$(mktemp)"
trap 'rm -f "$notes_file" "$unreleased_file"' EXIT

extract_unreleased_section > "$unreleased_file"
trim_blank_lines "$unreleased_file"

if grep -q "^## \[$VERSION\]" CHANGELOG.md; then
  echo "Found an existing CHANGELOG.md section for '$VERSION'. Using it as the release source of truth."
  validate_version_metadata "$VERSION"
else
  if [ ! -s "$unreleased_file" ]; then
    echo "CHANGELOG.md Unreleased section is empty."
    echo "Add changes under '## [Unreleased]' before releasing."
    exit 1
  fi

  promote_unreleased_section "$VERSION" "$RELEASE_DATE" "$unreleased_file"
  set_plugin_and_readme_versions "$VERSION"
  validate_version_metadata "$VERSION"

  git add CHANGELOG.md plugin.php readme.txt
  git commit -m "chore: prepare ${VERSION} release"
  git push origin "$TARGET_BRANCH"
fi

extract_changelog_section "$VERSION" > "$notes_file"
trim_blank_lines "$notes_file"

if [ ! -s "$notes_file" ]; then
  echo "CHANGELOG.md section for '$VERSION' is empty."
  exit 1
fi

echo "Creating GitHub release '$VERSION' from committed CHANGELOG.md notes on '$TARGET_BRANCH'..."
gh release create "$VERSION" \
  --title "$VERSION" \
  --target "$TARGET_BRANCH" \
  --notes-file "$notes_file"

echo "Release '$VERSION' created successfully."
