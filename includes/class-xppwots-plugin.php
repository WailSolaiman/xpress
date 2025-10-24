<?php

namespace XPPWOTS;

if (!defined('ABSPATH')) {
  exit;
}

final class Plugin
{
  public static function init(): void
  {
    I18n::init();
    Admin_Page::init();
    CPT::init();
    Settings::init();
    Records_Page::init();
    Developer_Page::init();
    Help_Page::init();
    REST::init();
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    add_filter('admin_body_class', [__CLASS__, 'admin_body_class']);
  }

  public static function is_our_screen(string $hook): bool
  {
    if ($hook === 'toplevel_page_xppwots-dashboard') {
      return true;
    }
    if (str_starts_with($hook, 'xppwots-dashboard_page_xppwots-')) {
      return true;
    }
    if (isset($_GET['page']) && is_string($_GET['page']) && str_starts_with($_GET['page'], 'xppwots-')) {
      return true;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && is_string($screen->base)) {
      if ($screen->base === 'toplevel_page_xppwots-dashboard' || str_starts_with($screen->base, 'xppwots-dashboard_page_xppwots-')) {
        return true;
      }
    }
    return false;
  }

  public static function enqueue_admin_assets(string $hook): void
  {
    if (! self::is_our_screen($hook)) return;

    $css_ver = @filemtime(XPPWOTS_PATH . 'assets/admin.css') ?: XPPWOTS_VERSION;
    $js_ver  = @filemtime(XPPWOTS_PATH . 'assets/admin.js')  ?: XPPWOTS_VERSION;

    wp_register_style('xppwots-admin', XPPWOTS_URL . 'assets/admin.css', [], $css_ver);
    wp_enqueue_style('xppwots-admin');

    wp_register_script('xppwots-admin', XPPWOTS_URL . 'assets/admin.js', ['jquery'], $js_ver, true);

    $locale = I18n::lang(); // strictly 'ar' or 'en'
    $localized = [
      'nonce'   => wp_create_nonce('wp_rest'),
      'restUrl' => esc_url_raw(rest_url('xppwots/v1')),
      'i18n'    => I18n::js_map(),
      'ui'      => [
        'rtl'    => ($locale === 'ar'),
        'locale' => $locale,
      ],
      'endpoints' => [
        'profile' => esc_url_raw(xppwots_get_option('profile_url', '')),
        'tweets'  => esc_url_raw(xppwots_get_option('tweets_url', '')),
      ],
    ];

    wp_localize_script('xppwots-admin', 'XPPWOTS_DATA', $localized);
    wp_enqueue_script('xppwots-admin');
  }

  public static function admin_body_class(string $classes): string
  {
    $screen = get_current_screen();
    if ($screen && self::is_our_screen($screen->id)) {
      $classes .= ' xppwots-ui';
    }
    return $classes;
  }
}
