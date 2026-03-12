<?php
/**
 * Plugin Name: Ollama Provider Test Endpoint
 * Description: REST API endpoint for CI integration testing of the Ollama provider.
 */

add_action('rest_api_init', function () {
    register_rest_route('ollama-test/v1', '/status', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => 'ollama_test_provider_status',
    ));
});

/**
 * Returns the integration status of the Ollama provider.
 *
 * @return array{ai_client_available: bool, provider_registered: bool, ollama_reachable: bool, model_count: int, error: string|null}
 */
function ollama_test_provider_status()
{
    $ai_client_class = 'WordPress\AiClient\AiClient';
    $provider_class  = 'Zaherg\OllamaAiProvider\Provider\OllamaProvider';

    $result = array(
        'ai_client_available' => class_exists($ai_client_class),
        'provider_registered' => false,
        'ollama_reachable'    => false,
        'model_count'         => 0,
        'error'               => null,
    );

    if (! $result['ai_client_available']) {
        $result['error'] = 'WordPress AI Client SDK is not available';
        return $result;
    }

    try {
        $registry = call_user_func(array($ai_client_class, 'defaultRegistry'));
        $result['provider_registered'] = $registry->hasProvider($provider_class);
    } catch (\Exception $e) {
        $result['error'] = $e->getMessage();
        return $result;
    }

    // Test direct connectivity to Ollama via wp_remote_get.
    $ollama_url = defined('OLLAMA_BASE_URL') ? OLLAMA_BASE_URL : 'http://localhost:11434/v1';
    $response   = wp_remote_get($ollama_url . '/models');

    if (is_wp_error($response)) {
        $result['error'] = $response->get_error_message();
        return $result;
    }

    $result['ollama_reachable'] = true;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['data']) && is_array($body['data'])) {
        $result['model_count'] = count($body['data']);
    }

    return $result;
}
