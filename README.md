# AI Provider for Ollama

An AI Provider for Ollama for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

This package is based on the WordPress package [`WordPress/ai-provider-for-openai`](https://github.com/WordPress/ai-provider-for-openai) and adapts that provider implementation for Ollama's OpenAI-compatible `/v1` API (for example `/v1/models` and `/v1/chat/completions`).

## Requirements

- PHP 7.4 or higher
- Ollama running locally or remotely (default base URL: `http://localhost:11434/v1`)
- When using with WordPress, WordPress 7.0 or higher
  - If using an older WordPress release, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed

## Installation

### As a Composer Package

```bash
composer require wordpress/ai-provider-for-ollama
```

### As a WordPress Plugin

1. Upload the plugin files
2. Upload to `/wp-content/plugins/ai-provider-for-ollama/`
3. Ensure the PHP AI Client plugin is installed and activated
4. Activate the plugin through the WordPress admin

## Usage

### With WordPress

The provider automatically registers itself with the PHP AI Client on the `init` hook.

```php
// Optional: in wp-config.php, set the exact OpenAI-compatible base URL for your deployment.
define('OLLAMA_BASE_URL', 'http://localhost:11434/v1');

$result = AiClient::prompt('Hello, world!')
    ->usingProvider('ollama')
    ->generateTextResult();
```

### As a Standalone Package

```php
use WordPress\AiClient\AiClient;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

$registry = AiClient::defaultRegistry();
$registry->registerProvider(OllamaProvider::class);

define('OLLAMA_BASE_URL', 'http://localhost:11434/v1');

$result = AiClient::prompt('Explain quantum computing')
    ->usingProvider('ollama')
    ->generateTextResult();

echo $result->toText();
```

## Configuration

- `OLLAMA_BASE_URL` (optional): Exact Ollama OpenAI-compatible base URL for your deployment (local default: `http://localhost:11434/v1`). Set via a constant in `wp-config.php` (`define('OLLAMA_BASE_URL', '...');`).
- `OLLAMA_API_KEY` (optional): Bearer token for proxied/secured Ollama deployments. Local Ollama usually needs no auth. If needed, set via `define('OLLAMA_API_KEY', '...');`.
- For Ollama Cloud API access, see the official docs: [Ollama Cloud API Access](https://docs.ollama.com/cloud#cloud-api-access). Configure `OLLAMA_BASE_URL` to the exact Cloud OpenAI-compatible base URL and set `OLLAMA_API_KEY` as required.

## Supported Features (Current)

- Text generation / chat completion
- Function calling (tool declarations)
- JSON output (`response_format` / schema)
- Model discovery via `/v1/models`

Image generation and other non-text capabilities are not implemented in this package yet.

## License

GPL-2.0-or-later
