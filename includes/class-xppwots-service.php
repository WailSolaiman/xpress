<?php

namespace XPPWOTS;

use WP_Error;

if (! defined('ABSPATH')) {
  exit;
}

final class Service
{

  /**
   * POST JSON to configured endpoint.
   */
  public static function post(string $path, array $payload)
  {
    $cfg = get_option('xppwots_settings', []);
    $base = $cfg['endpoint_url'] ?? '';
    if (empty($base)) {
      return new WP_Error('no_endpoint', 'Endpoint not configured.');
    }

    $url = rtrim($base, '/') . '/' . ltrim($path, '/');
    $token = $cfg['token'] ?? '';
    $timeout = max(5, (int)($cfg['timeout'] ?? 20));

    $args = [
      'headers' => array_filter([
        'Content-Type' => 'application/json; charset=utf-8',
        'Authorization' => $token ? 'Bearer ' . $token : null,
      ]),
      'body'    => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      'timeout' => $timeout,
    ];

    $res = wp_remote_post($url, $args);

    if (is_wp_error($res)) {
      return $res;
    }
    $code = (int) wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);

    if ($code < 200 || $code >= 300) {
      return new WP_Error('bad_status', 'HTTP ' . $code, ['body' => $body]);
    }

    $data = json_decode($body, true);
    return $data ?? [];
  }
}
