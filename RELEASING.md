# Releasing

This repository uses a changelog-first release flow. `CHANGELOG.md` is finalized before the tag and GitHub release are created.

## Release Steps

1. Add changes under `## [Unreleased]` in `CHANGELOG.md` as work is merged.
2. Run the release script from a clean local checkout on `main`:

```bash
scripts/create-release.sh 0.1.2
```

The script performs the release in this order:

1. Validates the requested version (`X.Y.Z`) and repo state (`gh` auth, clean tree, branch, remote sync).
2. Updates `plugin.php` `Version`.
3. Updates `readme.txt` `Stable tag`.
4. Moves the `CHANGELOG.md` `Unreleased` entries into `## [X.Y.Z] - YYYY-MM-DD`.
5. Commits and pushes the release-prep changes.
6. Creates and pushes the Git tag (`X.Y.Z`).
7. Creates the GitHub release using the committed `CHANGELOG.md` version section as release notes.

## Notes

- The script supports a safe resume path if the release-prep commit already exists but the GitHub release creation failed.
- The GitHub release body must match the committed `CHANGELOG.md` section for the same version.

## CI Guards

- `.github/workflows/release-metadata.yml`
  - On `pull_request`/branch `push`: verifies `plugin.php` version matches `readme.txt` `Stable tag`.
  - On tag `push`: also verifies the tag matches the latest versioned `CHANGELOG.md` section.
- `.github/workflows/changelog.yml`
  - On GitHub `release` publish: verifies the release body matches the committed `CHANGELOG.md` section for that tag.
