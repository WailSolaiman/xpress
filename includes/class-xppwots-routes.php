<?php

namespace XPPWOTS;

if (! defined('ABSPATH')) exit;

class Routes
{

  // Slugs
  public const MENU_TOP     = 'xppwots-dashboard';
  public const PAGE_DASH    = 'xppwots-dashboard';
  public const PAGE_SETTINGS = 'xppwots-settings';
  public const PAGE_RECORDS = 'xppwots-records';
  public const PAGE_HELP    = 'xppwots-help';

  // Build admin.php?page= URLs
  public static function admin_url(string $page, array $args = []): string
  {
    return add_query_arg(array_merge(['page' => $page], $args), admin_url('admin.php'));
  }
  public static function dashboard_url(array $args = []): string
  {
    return self::admin_url(self::PAGE_DASH, $args);
  }
  public static function settings_url(array $args = []): string
  {
    return self::admin_url(self::PAGE_SETTINGS, $args);
  }
  public static function records_url(array $args = []): string
  {
    return self::admin_url(self::PAGE_RECORDS, $args);
  }
  public static function help_url(array $args = []): string
  {
    return self::admin_url(self::PAGE_HELP, $args);
  }

  // Screen IDs
  public static function screen_id_top(): string
  {
    return 'toplevel_page_' . self::PAGE_DASH;
  }
  public static function screen_id_settings(): string
  {
    return self::MENU_TOP . '_page_' . self::PAGE_SETTINGS;
  }
  public static function screen_id_records(): string
  {
    return self::MENU_TOP . '_page_' . self::PAGE_RECORDS;
  }
  public static function screen_id_help(): string
  {
    return self::MENU_TOP . '_page_' . self::PAGE_HELP;
  }

  public static function screen_ids(): array
  {
    return [
      self::screen_id_top(),
      self::screen_id_settings(),
      self::screen_id_records(),
      self::screen_id_help(),
    ];
  }
  public static function is_our_screen_id(string $id): bool
  {
    return in_array($id, self::screen_ids(), true);
  }

  // Central menu registration
  public static function register_menus(): void
  {
    add_menu_page(
      'XPress',
      'XPress',
      'manage_options',
      self::MENU_TOP,
      [Admin_Page::class, 'render'],
      'dashicons-admin-generic',
      56
    );

    add_submenu_page(
      self::MENU_TOP,
      I18n::t('dashboard'),
      I18n::t('dashboard'),
      'manage_options',
      self::PAGE_DASH,
      [Admin_Page::class, 'render']
    );

    add_submenu_page(
      self::MENU_TOP,
      I18n::t('settings'),
      I18n::t('settings'),
      'manage_options',
      self::PAGE_SETTINGS,
      [Settings::class, 'render']
    );

    add_submenu_page(
      self::MENU_TOP,
      I18n::t('records'),
      I18n::t('records'),
      'edit_posts',
      self::PAGE_RECORDS,
      [Records_Page::class, 'render']
    );

    add_submenu_page(
      self::MENU_TOP,
      I18n::t('help'),
      I18n::t('help'),
      'read',
      self::PAGE_HELP,
      [Help_Page::class, 'render']
    );
  }
}
