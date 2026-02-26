<?php

/**
 * Plugin Name: AI Provider for Ollama
 * Plugin URI: https://github.com/WordPress/ai-provider-for-ollama
 * Description: AI Provider for Ollama for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 0.1.1
 * Author: WordPress AI Team
 * Author URI: https://make.wordpress.org/ai/
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-ollama
 *
 * @package Zaherg\OllamaAiProvider
 */

declare(strict_types=1);

namespace Zaherg\OllamaAiProvider;

use WordPress\AiClient\AiClient;
use Zaherg\OllamaAiProvider\Provider\OllamaProvider;

if (!defined('ABSPATH')) {
    return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Registers the AI Provider for Ollama with the AI Client.
 *
 * @return void
 */
function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();

    if ($registry->hasProvider(OllamaProvider::class)) {
        return;
    }

    $registry->registerProvider(OllamaProvider::class);
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);
