# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Add GitHub-based Composer installation documentation to README
- Update Composer installation to use GitHub with specific version
- Remove direct installation option, keep only VCS repository method
- Change composer install to composer update for new package
- Simplify installation: add repository config then use composer require
- Update wordpress/php-ai-client from ^0.4 to ^1.0
- Add Claude Code GitHub Actions workflow
- Add automated release workflow
- Disable Claude Code cloud workflow
- remove claude workflow
- ci: add bump-type dropdown with optional version override to release workflow
- test: add PHPUnit test suite and CI workflow
- update gitignore
- Remove plugin.php export-ignore from .gitattributes
- Add AI disclaimer caution alert to README.md
- feat(ci): automate unreleased changelog updates

## [0.1.3] - 2026-02-27

### Changed
- Added a shared Ollama HTTP initialization trait to centralize transporter setup across provider and model classes.

### Documentation
- Fixed README package naming to match `composer.json`.
- Corrected the AI assistance disclaimer filename from `DISCLAMER.md` to `DISCLAIMER.md`.

## [0.1.2] - 2026-02-26

### Changed
- Kept Ollama provider metadata classified as a cloud provider while removing the cloud docs URL from provider metadata.
- Exposed API key authentication metadata in provider metadata while keeping `OLLAMA_API_KEY` optional for Ollama deployments.

### Documentation
- Clarified server endpoint configuration wording and examples to use the default `http://localhost:11434/v1`.
- Updated README and WordPress `readme.txt` guidance to explain that `OLLAMA_API_KEY` is optional and only needed when the server requires bearer authentication.

## [0.1.1] - 2026-02-26

### Changed
- Updated provider metadata classification for WP AI Client credential UI flows.

### Fixed
- Replaced Ollama's availability preflight with a no-network configuration check to avoid false `ai_provider_not_configured` responses when local/no-auth Ollama is available.
- Added lazy HTTP transporter initialization in the Ollama model metadata directory and text generation model to handle registry initialization before WordPress AI Client HTTP discovery is ready.

## [0.1.0] - 2026-02-26

### Added
- Initial Ollama provider package based on `WordPress/ai-provider-for-openai`, adapted for Ollama's OpenAI-compatible API.
- Ollama text generation model support using OpenAI-compatible chat completions.
- Ollama model metadata directory support for `/models` discovery and text/chat model capability mapping.
- Optional no-auth request authentication fallback for local Ollama deployments.
- Optional Ollama API key support (`OLLAMA_API_KEY`) for secured/proxied Ollama deployments.
- `wp-config.php` constant support for `OLLAMA_BASE_URL`.
- AI assistance disclaimer file (`DISCLAIMER.md`).

### Changed
- Renamed package/plugin namespace prefix from `WordPress` to `Zaherg`.
- Updated base URL handling to use the exact configured OpenAI-compatible Ollama base URL (no forced `/v1` suffix normalization).
- Default Ollama base URL remains `http://localhost:11434/v1` when `OLLAMA_BASE_URL` is not configured.
- Exposed an optional Ollama API key authentication method in provider metadata for WP AI Client credentials integration.

### Removed
- Removed `.github/` and `.wordpress-org/` directories from this fork.

### Documentation
- Added upstream attribution noting this package is based on `WordPress/ai-provider-for-openai`.
- Clarified base URL configuration to use `wp-config.php` constants (not `putenv()` examples).
- Added Ollama base URL guidance for exact server URL/path configuration.
