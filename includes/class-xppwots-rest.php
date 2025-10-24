<?php

namespace XPPWOTS;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
  exit;
}

final class REST
{
  public static function init(): void
  {
    add_action('rest_api_init', [__CLASS__, 'routes']);
  }

  public static function can(): bool
  {
    return current_user_can('edit_posts');
  }

  public static function routes(): void
  {
    register_rest_route('xppwots/v1', '/action', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => function () {
        return new WP_REST_Response(['status' => 'success', 'data' => (object)[], 'error' => null], 200);
      },
    ]);

    register_rest_route('xppwots/v1', '/records', [
      'methods' => 'GET',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => function () {
        return new WP_REST_Response(xppwots_mock_records(), 200);
      },
    ]);

    register_rest_route('xppwots/v1', '/save', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => function (WP_REST_Request $req) {
        return new WP_REST_Response(['ok' => true, 'updated_at' => time()], 200);
      },
    ]);

    register_rest_route('xppwots/v1', '/delete', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => function (WP_REST_Request $req) {
        return new WP_REST_Response(['ok' => true], 200);
      },
    ]);

    register_rest_route('xppwots/v1', '/callback', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => function (WP_REST_Request $req) {
        return new WP_REST_Response(['ok' => true], 200);
      },
    ]);

    register_rest_route('xppwots/v1', '/import', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => [__CLASS__, 'import_tweets'],
    ]);

    register_rest_route('xppwots/v1', '/groups', [
      'methods' => 'GET',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => [__CLASS__, 'groups'],
    ]);

    register_rest_route('xppwots/v1', '/purge', [
      'methods' => 'POST',
      'permission_callback' => [__CLASS__, 'can'],
      'callback' => [__CLASS__, 'purge_handle'],
    ]);
  }

  private static function find_existing(string $handle, string $tweet_id)
  {
    if (!$handle || !$tweet_id) return 0;
    $q = get_posts([
      'post_type' => 'xppwots_tweet',
      'post_status' => 'any',
      'meta_query' => [
        'relation' => 'AND',
        ['key' => 'xppwots_tweet_id', 'value' => $tweet_id, 'compare' => '='],
        ['key' => 'xppwots_handle', 'value' => $handle, 'compare' => '='],
      ],
      'fields' => 'ids',
      'posts_per_page' => 1,
      'no_found_rows' => true,
      'suppress_filters' => true,
    ]);
    return $q ? (int)$q[0] : 0;
  }

  public static function import_tweets(WP_REST_Request $req)
  {
    $b = $req->get_json_params();
    $handle = isset($b['handle']) ? sanitize_text_field($b['handle']) : '';
    $profile = isset($b['profile']) && is_array($b['profile']) ? [
      'name' => sanitize_text_field($b['profile']['name'] ?? ''),
      'screen_name' => sanitize_text_field($b['profile']['screen_name'] ?? ''),
      'description' => sanitize_text_field($b['profile']['description'] ?? ''),
      'image_url' => esc_url_raw($b['profile']['image_url'] ?? ''),
    ] : null;

    if ($handle && $profile) {
      $all = get_option('xppwots_profiles', []);
      if (!is_array($all)) $all = [];
      $key = strtolower($handle);
      $all[$key] = $profile;
      update_option('xppwots_profiles', $all, false);
    }

    $items = is_array($b['tweets'] ?? null) ? $b['tweets'] : [];
    $created = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($items as $tw) {
      $tweet_id = isset($tw['tweet_id']) ? sanitize_text_field($tw['tweet_id']) : '';
      $url = isset($tw['url']) ? esc_url_raw($tw['url']) : '';
      $full_text = isset($tw['full_text']) ? wp_kses_post($tw['full_text']) : '';
      $created_at = isset($tw['created_at']) ? sanitize_text_field($tw['created_at']) : '';
      $likes = isset($tw['favorite_count']) ? (int)$tw['favorite_count'] : 0;
      $retweets = isset($tw['retweet_count']) ? (int)$tw['retweet_count'] : 0;
      $replies = isset($tw['reply_count']) ? (int)$tw['reply_count'] : 0;
      $views = isset($tw['views']) ? (int)$tw['views'] : 0;
      $bookmarks = isset($tw['bookmark_count']) ? (int)$tw['bookmark_count'] : 0;

      if (!$handle || !$tweet_id) {
        $skipped++;
        continue;
      }

      $existing = self::find_existing($handle, $tweet_id);

      if ($existing) {
        update_post_meta($existing, 'xppwots_handle', $handle);
        update_post_meta($existing, 'xppwots_tweet_id', $tweet_id);
        update_post_meta($existing, 'xppwots_url', $url);
        update_post_meta($existing, 'xppwots_full_text', $full_text);
        update_post_meta($existing, 'xppwots_created_at', $created_at);
        update_post_meta($existing, 'xppwots_fetched_at', date('c'));
        update_post_meta($existing, 'xppwots_likes', $likes);
        update_post_meta($existing, 'xppwots_retweets', $retweets);
        update_post_meta($existing, 'xppwots_replies', $replies);
        update_post_meta($existing, 'xppwots_views', $views);
        update_post_meta($existing, 'xppwots_bookmarks', $bookmarks);
        if (!get_the_title($existing)) {
          wp_update_post(['ID' => $existing, 'post_title' => $handle . ' — ' . $tweet_id]);
        }
        $updated++;
      } else {
        $post_id = wp_insert_post(['post_type' => 'xppwots_tweet', 'post_status' => 'publish', 'post_title' => $handle . ' — ' . $tweet_id], true);
        if (is_wp_error($post_id) || !$post_id) {
          $skipped++;
          continue;
        }
        update_post_meta($post_id, 'xppwots_handle', $handle);
        update_post_meta($post_id, 'xppwots_tweet_id', $tweet_id);
        update_post_meta($post_id, 'xppwots_url', $url);
        update_post_meta($post_id, 'xppwots_full_text', $full_text);
        update_post_meta($post_id, 'xppwots_created_at', $created_at);
        update_post_meta($post_id, 'xppwots_fetched_at', date('c'));
        update_post_meta($post_id, 'xppwots_likes', $likes);
        update_post_meta($post_id, 'xppwots_retweets', $retweets);
        update_post_meta($post_id, 'xppwots_replies', $replies);
        update_post_meta($post_id, 'xppwots_views', $views);
        update_post_meta($post_id, 'xppwots_bookmarks', $bookmarks);
        $created++;
      }
    }

    return new WP_REST_Response(['ok' => true, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped], 200);
  }

  public static function groups(WP_REST_Request $req)
  {
    $q = new \WP_Query([
      'post_type' => 'xppwots_tweet',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
      'no_found_rows' => true,
    ]);
    $profiles = get_option('xppwots_profiles', []);
    if (!is_array($profiles)) $profiles = [];
    $groups = [];
    foreach ($q->posts as $pid) {
      $h = get_post_meta($pid, 'xppwots_handle', true) ?: '';
      if (!$h) {
        continue;
      }
      if (!isset($groups[$h])) {
        $key = strtolower($h);
        $groups[$h] = [
          'handle' => $h,
          'profile' => $profiles[$key] ?? null,
          'count' => 0,
          'latest_created_at' => '',
          'latest_fetched_at' => '',
          'totals' => ['likes' => 0, 'retweets' => 0, 'replies' => 0, 'views' => 0, 'bookmarks' => 0],
          'tweets' => [],
        ];
      }
      $tw = [
        'tweet_id' => get_post_meta($pid, 'xppwots_tweet_id', true),
        'url' => get_post_meta($pid, 'xppwots_url', true),
        'full_text' => get_post_meta($pid, 'xppwots_full_text', true),
        'created_at' => get_post_meta($pid, 'xppwots_created_at', true),
        'fetched_at' => get_post_meta($pid, 'xppwots_fetched_at', true),
        'favorite_count' => (int)get_post_meta($pid, 'xppwots_likes', true),
        'retweet_count' => (int)get_post_meta($pid, 'xppwots_retweets', true),
        'reply_count' => (int)get_post_meta($pid, 'xppwots_replies', true),
        'views' => (int)get_post_meta($pid, 'xppwots_views', true),
        'bookmark_count' => (int)get_post_meta($pid, 'xppwots_bookmarks', true),
      ];
      $groups[$h]['tweets'][] = $tw;
      $groups[$h]['count']++;
      $groups[$h]['totals']['likes'] += $tw['favorite_count'];
      $groups[$h]['totals']['retweets'] += $tw['retweet_count'];
      $groups[$h]['totals']['replies'] += $tw['reply_count'];
      $groups[$h]['totals']['views'] += $tw['views'];
      $groups[$h]['totals']['bookmarks'] += $tw['bookmark_count'];
      $c = strtotime($tw['created_at'] ?: '1970-01-01');
      $f = strtotime($tw['fetched_at'] ?: '1970-01-01');
      if ($c && $c > strtotime($groups[$h]['latest_created_at'] ?: '1970-01-01')) $groups[$h]['latest_created_at'] = $tw['created_at'];
      if ($f && $f > strtotime($groups[$h]['latest_fetched_at'] ?: '1970-01-01')) $groups[$h]['latest_fetched_at'] = $tw['fetched_at'];
    }
    $out = array_values($groups);
    return new WP_REST_Response($out, 200);
  }

  public static function purge_handle(WP_REST_Request $req)
  {
    $handle = sanitize_text_field($req->get_param('handle'));
    if (!$handle) return new WP_REST_Response(['ok' => false], 400);
    $q = get_posts([
      'post_type' => 'xppwots_tweet',
      'post_status' => 'any',
      'numberposts' => -1,
      'meta_key' => 'xppwots_handle',
      'meta_value' => $handle,
      'fields' => 'ids',
    ]);
    $deleted = 0;
    foreach ($q as $pid) {
      if (wp_delete_post($pid, true)) $deleted++;
    }
    $all = get_option('xppwots_profiles', []);
    if (is_array($all) && isset($all[$handle])) {
      unset($all[$handle]);
      update_option('xppwots_profiles', $all, false);
    }
    return new WP_REST_Response(['ok' => true, 'deleted' => $deleted], 200);
  }
}
