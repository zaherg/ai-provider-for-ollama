# AI Provider for Ollama

An AI Provider for Ollama for the [PHP AI Client](https://github.com/WordPress/php-ai-client) SDK. Works as both a Composer package and a WordPress plugin.

This package is based on the WordPress package [`WordPress/ai-provider-for-openai`](https://github.com/WordPress/ai-provider-for-openai) and adapts that provider implementation for Ollama's OpenAI-compatible `/v1` API (for example `/v1/models` and `/v1/chat/completions`).

## Requirements

- PHP 7.4 or higher
- A reachable OpenAI-compatible `/v1` server endpoint for your deployment
- Exact server URL/path (default: `http://localhost:11434/v1`)
- When using with WordPress, WordPress 7.0 or higher
  - If using an older WordPress release, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed

## Installation

### As a Composer Package

Add the GitHub repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/zaherg/ai-provider-for-ollama"
        }
    ],
    "require": {
        "zaherg/ai-provider-for-ollama": "^0.1.3"
    }
}
```

Then run:
```bash
composer update
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
// Required: set the exact OpenAI-compatible /v1 base URL for your server.
define('OLLAMA_BASE_URL', 'http://localhost:11434/v1');
// Optional: if your Ollama server requires bearer auth.
define('OLLAMA_API_KEY', 'your-api-key');

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
// Optional: if your Ollama server requires bearer auth.
define('OLLAMA_API_KEY', 'your-api-key');

$result = AiClient::prompt('Explain quantum computing')
    ->usingProvider('ollama')
    ->generateTextResult();

echo $result->toText();
```

## Configuration

- `OLLAMA_BASE_URL` (required): Exact server URL/path using the OpenAI-compatible `/v1` base URL (default: `http://localhost:11434/v1`). Set via a constant in `wp-config.php` (`define('OLLAMA_BASE_URL', '...');`).
- `OLLAMA_API_KEY` (optional): Bearer token for secured/proxied Ollama servers. Set via `define('OLLAMA_API_KEY', '...');` if your server requires auth.

## Supported Features (Current)

- Text generation / chat completion
- Function calling (tool declarations)
- JSON output (`response_format` / schema)
- Model discovery via `/v1/models`

Image generation and other non-text capabilities are not implemented in this package yet.

## License

GPL-2.0-or-later
