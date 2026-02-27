=== AI Provider for Ollama ===
Contributors: wordpressdotorg
Tags: ai, ollama, llm, artificial-intelligence, connector
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 0.1.3
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Provider for Ollama for the PHP AI Client SDK.

== Description ==

This plugin provides Ollama integration for the PHP AI Client SDK using Ollama's OpenAI-compatible `/v1` API.

**Features:**

* Text generation / chat completion
* Function calling support
* JSON output support
* Automatic provider registration
* Automatic model discovery from the configured server (`/v1/models`)

**Requirements:**

* PHP 7.4 or higher
* A reachable OpenAI-compatible `/v1` server endpoint for your deployment
* Exact server URL/path (default: `http://localhost:11434/v1`)
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required

**Configuration:**

* `OLLAMA_BASE_URL` (required, exact server URL/path using the OpenAI-compatible `/v1` base URL, default `http://localhost:11434/v1`) - set via `define( 'OLLAMA_BASE_URL', '...' );` in `wp-config.php`
* `OLLAMA_API_KEY` (optional, bearer token for secured/proxied Ollama servers) - set via `define( 'OLLAMA_API_KEY', '...' );` if your server requires auth

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-ollama/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure your server is reachable and your desired models are available
4. Configure `OLLAMA_BASE_URL` to the exact OpenAI-compatible `/v1` base URL for your server; set `OLLAMA_API_KEY` only if your server requires auth

== Frequently Asked Questions ==

= Do I need an API key? =

Not always. Local Ollama usually does not require authentication. Configure `OLLAMA_API_KEY` only if your server requires a bearer token.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client plugin to be installed and activated. It provides the Ollama-specific provider implementation used by the PHP AI Client.

== Changelog ==

= 0.1.1 =

* Updated provider metadata for WP AI Client credential flows
* Replaced availability preflight with a no-network configuration check to avoid false not-configured responses
* Added lazy HTTP transporter initialization for safer registry startup timing

= 0.1.0 =

* Initial Ollama provider package based on the WordPress OpenAI provider structure
* Text generation support via Ollama OpenAI-compatible chat completions API
* Function calling support
* JSON output support
