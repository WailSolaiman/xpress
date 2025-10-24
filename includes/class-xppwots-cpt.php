<?php

namespace XPPWOTS;

if (!defined('ABSPATH')) {
  exit;
}

final class CPT
{
  public static function init(): void
  {
    add_action('init', [__CLASS__, 'register_type']);
    add_action('init', [__CLASS__, 'register_meta']);
    add_action('add_meta_boxes', [__CLASS__, 'add_boxes']);
    add_action('save_post_xppwots_tweet', [__CLASS__, 'save_meta'], 10, 2);
    add_filter('manage_xppwots_tweet_posts_columns', [__CLASS__, 'cols']);
    add_action('manage_xppwots_tweet_posts_custom_column', [__CLASS__, 'col_data'], 10, 2);
    add_filter('manage_edit-xppwots_tweet_sortable_columns', [__CLASS__, 'sortable']);
  }

  public static function register_type(): void
  {
    register_post_type('xppwots_tweet', [
      'labels' => [
        'name' => I18n::t('cpt_tweets'),
        'singular_name' => I18n::t('cpt_tweet'),
        'menu_name' => I18n::t('cpt_menu_name'),
      ],
      'public' => false,
      'publicly_queryable' => false,
      'show_ui' => false,
      'show_in_menu' => false,
      'show_in_nav_menus' => false,
      'show_in_admin_bar' => false,
      'exclude_from_search' => true,
      'capability_type' => 'post',
      'map_meta_cap' => true,
      'supports' => ['title'],
      'has_archive' => false,
      'show_in_rest' => false,
    ]);
  }

  public static function register_meta(): void
  {
    $s = ['show_in_rest' => true, 'single' => true, 'type' => 'string', 'auth_callback' => function () {
      return current_user_can('edit_posts');
    }];
    $i = ['show_in_rest' => true, 'single' => true, 'type' => 'integer', 'auth_callback' => function () {
      return current_user_can('edit_posts');
    }];
    register_post_meta('xppwots_tweet', 'xppwots_handle', $s);
    register_post_meta('xppwots_tweet', 'xppwots_tweet_id', $s);
    register_post_meta('xppwots_tweet', 'xppwots_url', $s);
    register_post_meta('xppwots_tweet', 'xppwots_full_text', $s);
    register_post_meta('xppwots_tweet', 'xppwots_created_at', $s);
    register_post_meta('xppwots_tweet', 'xppwots_fetched_at', $s);
    register_post_meta('xppwots_tweet', 'xppwots_likes', $i);
    register_post_meta('xppwots_tweet', 'xppwots_retweets', $i);
    register_post_meta('xppwots_tweet', 'xppwots_replies', $i);
    register_post_meta('xppwots_tweet', 'xppwots_views', $i);
    register_post_meta('xppwots_tweet', 'xppwots_bookmarks', $i);
  }

  public static function add_boxes(): void
  {
    add_meta_box('xppwots_tweet_meta', I18n::t('cpt_box_title'), [__CLASS__, 'box_render'], 'xppwots_tweet', 'normal', 'high');
  }

  private static function field($key, $label, $type = 'text', $value = ''): void
  {
    $name = "xppwots_meta[$key]";
    if ($type === 'textarea') {
      printf('<p><label><strong>%s</strong></label><br><textarea name="%s" rows="5" style="width:100%%">%s</textarea></p>', esc_html($label), esc_attr($name), esc_textarea((string)$value));
    } else {
      printf('<p><label><strong>%s</strong></label><br><input type="%s" name="%s" value="%s" class="regular-text" style="width:100%%"></p>', esc_html($label), esc_attr($type), esc_attr($name), esc_attr((string)$value));
    }
  }

  public static function box_render(\WP_Post $post): void
  {
    wp_nonce_field('xppwots_tweet_meta', 'xppwots_tweet_meta_nonce');
    $g = fn($k, $d = '') => get_post_meta($post->ID, $k, true) ?: $d;
    echo '<div class="xppwots-wrap">';
    self::field('handle', I18n::t('cpt_field_handle'), 'text', $g('xppwots_handle'));
    self::field('tweet_id', I18n::t('cpt_field_tweet_id'), 'text', $g('xppwots_tweet_id'));
    self::field('url', I18n::t('cpt_field_url'), 'url', $g('xppwots_url'));
    self::field('full_text', I18n::t('cpt_field_full_text'), 'textarea', $g('xppwots_full_text'));
    self::field('created_at', I18n::t('cpt_field_created_at'), 'text', $g('xppwots_created_at'));
    self::field('likes', I18n::t('cpt_field_likes'), 'number', $g('xppwots_likes', 0));
    self::field('retweets', I18n::t('cpt_field_retweets'), 'number', $g('xppwots_retweets', 0));
    self::field('replies', I18n::t('cpt_field_replies'), 'number', $g('xppwots_replies', 0));
    self::field('views', I18n::t('cpt_field_views'), 'number', $g('xppwots_views', 0));
    self::field('bookmarks', I18n::t('cpt_field_bookmarks'), 'number', $g('xppwots_bookmarks', 0));
    self::field('fetched_at', I18n::t('cpt_field_fetched_at'), 'text', $g('xppwots_fetched_at'));
    echo '</div>';
  }

  public static function save_meta(int $post_id, \WP_Post $post): void
  {
    if (!isset($_POST['xppwots_tweet_meta_nonce']) || !wp_verify_nonce($_POST['xppwots_tweet_meta_nonce'], 'xppwots_tweet_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $in = $_POST['xppwots_meta'] ?? [];
    $s = fn($k) => isset($in[$k]) ? sanitize_text_field($in[$k]) : '';
    $i = fn($k) => isset($in[$k]) ? (int)$in[$k] : 0;
    update_post_meta($post_id, 'xppwots_handle', $s('handle'));
    update_post_meta($post_id, 'xppwots_tweet_id', $s('tweet_id'));
    update_post_meta($post_id, 'xppwots_url', esc_url_raw($s('url')));
    update_post_meta($post_id, 'xppwots_full_text', $s('full_text'));
    update_post_meta($post_id, 'xppwots_created_at', $s('created_at'));
    update_post_meta($post_id, 'xppwots_fetched_at', $s('fetched_at'));
    update_post_meta($post_id, 'xppwots_likes', $i('likes'));
    update_post_meta($post_id, 'xppwots_retweets', $i('retweets'));
    update_post_meta($post_id, 'xppwots_replies', $i('replies'));
    update_post_meta($post_id, 'xppwots_views', $i('views'));
    update_post_meta($post_id, 'xppwots_bookmarks', $i('bookmarks'));
    if (empty(get_the_title($post_id)) && $s('tweet_id')) {
      wp_update_post(['ID' => $post_id, 'post_title' => $s('handle') . ' — ' . $s('tweet_id')]);
    }
  }

  public static function cols($cols): array
  {
    $o = [];
    $o['cb'] = $cols['cb'] ?? '';
    $o['title'] = I18n::t('cpt_col_title');
    $o['xppwots_handle'] = I18n::t('cpt_col_handle');
    $o['xppwots_metrics'] = I18n::t('cpt_col_metrics');
    $o['xppwots_dates'] = I18n::t('cpt_col_dates');
    return $o;
  }

  public static function col_data(string $col, int $post_id): void
  {
    if ($col === 'xppwots_handle') {
      $h = get_post_meta($post_id, 'xppwots_handle', true);
      $u = get_post_meta($post_id, 'xppwots_url', true);
      echo esc_html($h ?: '—');
      if ($u) echo '<br><a href="' . esc_url($u) . '" target="_blank" rel="noopener">' . esc_html(I18n::t('cpt_open')) . '</a>';
      return;
    }
    if ($col === 'xppwots_metrics') {
      $m = [
        '❤' => (int)get_post_meta($post_id, 'xppwots_likes', true),
        '🔁' => (int)get_post_meta($post_id, 'xppwots_retweets', true),
        '💬' => (int)get_post_meta($post_id, 'xppwots_replies', true),
        '👁️' => (int)get_post_meta($post_id, 'xppwots_views', true),
        '🔖' => (int)get_post_meta($post_id, 'xppwots_bookmarks', true),
      ];
      $out = [];
      foreach ($m as $k => $v) {
        if ($v) {
          $out[] = $k . ' ' . $v;
        }
      }
      echo esc_html($out ? implode(' • ', $out) : '—');
      return;
    }
    if ($col === 'xppwots_dates') {
      $c = get_post_meta($post_id, 'xppwots_created_at', true);
      $f = get_post_meta($post_id, 'xppwots_fetched_at', true);
      echo esc_html(($c ?: '—') . ' | ' . ($f ?: '—'));
      return;
    }
  }

  public static function sortable($cols): array
  {
    return $cols;
  }
}
