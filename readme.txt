=== AI Provider for Ollama ===
Contributors: wordpressdotorg
Tags: ai, ollama, llm, artificial-intelligence, connector
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 0.1.1
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
* Automatic model discovery from local Ollama (`/v1/models`)

**Requirements:**

* PHP 7.4 or higher
* Ollama running locally or on a reachable server
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required

**Configuration:**

* `OLLAMA_BASE_URL` (optional, exact OpenAI-compatible base URL for your deployment; local default is `http://localhost:11434/v1`) - set via `define( 'OLLAMA_BASE_URL', '...' );` in `wp-config.php`
* `OLLAMA_API_KEY` (optional, for secured/proxied deployments) - if needed, set via `define( 'OLLAMA_API_KEY', '...' );`
* For Ollama Cloud API access, see https://docs.ollama.com/cloud#cloud-api-access and configure `OLLAMA_BASE_URL` to the exact Cloud OpenAI-compatible base URL plus `OLLAMA_API_KEY` as needed

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-ollama/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure Ollama is running and your desired models are installed
4. Optionally configure `OLLAMA_BASE_URL` to the exact OpenAI-compatible base URL for your deployment if Ollama is not running on the default local address

== Frequently Asked Questions ==

= Do I need an API key? =

Usually no. Local Ollama instances typically do not require authentication. Use `OLLAMA_API_KEY` only if your deployment is behind a proxy that requires a bearer token.

For Ollama Cloud API access details, see https://docs.ollama.com/cloud#cloud-api-access.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client plugin to be installed and activated. It provides the Ollama-specific provider implementation used by the PHP AI Client.

== Changelog ==

= 0.1.1 =

* Updated provider metadata so Ollama appears in WP AI Client credential flows that list cloud providers
* Replaced availability preflight with a no-network configuration check to avoid false not-configured responses
* Added lazy HTTP transporter initialization for safer registry startup timing

= 0.1.0 =

* Initial Ollama provider package based on the WordPress OpenAI provider structure
* Text generation support via Ollama OpenAI-compatible chat completions API
* Function calling support
* JSON output support
