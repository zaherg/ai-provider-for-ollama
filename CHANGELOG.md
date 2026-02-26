# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
- AI assistance disclaimer file (`DISCLAMER.md`).

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
