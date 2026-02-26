#!/usr/bin/env bash

set -euo pipefail

usage() {
  echo "Usage: scripts/validate-release-metadata.sh [--tag-version <version>] [--release-body-file <path>]"
  echo "Examples:"
  echo "  scripts/validate-release-metadata.sh"
  echo "  scripts/validate-release-metadata.sh --tag-version 0.1.1"
  echo "  scripts/validate-release-metadata.sh --tag-version 0.1.1 --release-body-file /tmp/body.txt"
}

TAG_VERSION=""
RELEASE_BODY_FILE=""

while [ "$#" -gt 0 ]; do
  case "$1" in
    --tag-version)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --tag-version"
        usage
        exit 1
      fi
      TAG_VERSION="${2#v}"
      shift 2
      ;;
    --release-body-file)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --release-body-file"
        usage
        exit 1
      fi
      RELEASE_BODY_FILE="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1"
      usage
      exit 1
      ;;
  esac
done

if [ -n "$RELEASE_BODY_FILE" ] && [ -z "$TAG_VERSION" ]; then
  echo "--release-body-file requires --tag-version"
  exit 1
fi

for required_file in CHANGELOG.md plugin.php readme.txt; do
  if [ ! -f "$required_file" ]; then
    echo "Missing required file: $required_file"
    exit 1
  fi
done

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

      for (line_index = start; line_index <= end; line_index++) {
        print lines[line_index]
      }
    }
  ' "$input_file" > "$output_file"

  mv "$output_file" "$input_file"
}

extract_changelog_section() {
  local target_version="$1"
  awk -v version="$target_version" '
    $0 ~ "^## \\[" version "\\]" { in_section=1; next }
    /^## \[/ { if (in_section) exit }
    in_section { print }
  ' CHANGELOG.md
}

read_plugin_version() {
  sed -n 's/^ \* Version: //p' plugin.php | head -n 1 | tr -d '[:space:]'
}

read_stable_tag() {
  sed -n 's/^Stable tag: //p' readme.txt | head -n 1 | tr -d '[:space:]'
}

read_latest_changelog_version() {
  awk '
    /^## \[[0-9]+\.[0-9]+\.[0-9]+\]/ {
      line = $0
      sub(/^## \[/, "", line)
      sub(/\].*$/, "", line)
      print line
      exit
    }
  ' CHANGELOG.md
}

plugin_version="$(read_plugin_version)"
stable_tag="$(read_stable_tag)"

if [ -z "$plugin_version" ]; then
  echo "plugin.php is missing a Version header."
  exit 1
fi

if [ -z "$stable_tag" ]; then
  echo "readme.txt is missing a Stable tag value."
  exit 1
fi

if [ "$plugin_version" != "$stable_tag" ]; then
  echo "Version mismatch: plugin.php=$plugin_version, readme.txt Stable tag=$stable_tag"
  exit 1
fi

echo "Version headers match: $plugin_version"

if [ -z "$TAG_VERSION" ]; then
  exit 0
fi

if ! printf '%s' "$TAG_VERSION" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$'; then
  echo "Tag version '$TAG_VERSION' is not a SemVer tag in X.Y.Z format."
  exit 1
fi

latest_changelog_version="$(read_latest_changelog_version)"
if [ -z "$latest_changelog_version" ]; then
  echo "CHANGELOG.md is missing a versioned section."
  exit 1
fi

if [ "$latest_changelog_version" != "$TAG_VERSION" ]; then
  echo "Latest CHANGELOG.md version ($latest_changelog_version) does not match tag/release version ($TAG_VERSION)"
  exit 1
fi

section_file="$(mktemp)"
release_file="$(mktemp)"
trap 'rm -f "$section_file" "$release_file"' EXIT

extract_changelog_section "$TAG_VERSION" > "$section_file"
trim_blank_lines "$section_file"

if [ ! -s "$section_file" ]; then
  echo "CHANGELOG.md section for $TAG_VERSION is missing or empty."
  exit 1
fi

echo "CHANGELOG.md latest section matches tag/release version: $TAG_VERSION"

if [ -z "$RELEASE_BODY_FILE" ]; then
  exit 0
fi

if [ ! -f "$RELEASE_BODY_FILE" ]; then
  echo "Release body file does not exist: $RELEASE_BODY_FILE"
  exit 1
fi

cp "$RELEASE_BODY_FILE" "$release_file"
trim_blank_lines "$release_file"

if [ ! -s "$release_file" ]; then
  echo "Release body is empty."
  exit 1
fi

if ! diff -u "$section_file" "$release_file"; then
  echo "Release body does not match CHANGELOG.md section for $TAG_VERSION"
  exit 1
fi

echo "Release body matches CHANGELOG.md section for $TAG_VERSION"
