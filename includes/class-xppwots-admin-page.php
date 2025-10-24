<?php

namespace XPPWOTS;

if (! defined('ABSPATH')) {
  exit;
}

final class Admin_Page
{

  public static function init(): void
  {
    add_action('admin_menu', [__CLASS__, 'register_menu']);
  }

  public static function register_menu(): void
  {
    add_menu_page(
      'XPress — ' . I18n::t('dashboard'),
      'XPress',
      'edit_posts',
      'xppwots-dashboard',
      [__CLASS__, 'render'],
      'dashicons-admin-generic',
      56
    );

    add_submenu_page(
      'xppwots-dashboard',
      'XPress — ' . I18n::t('dashboard'),
      I18n::t('dashboard'),
      'edit_posts',
      'xppwots-dashboard',
      [__CLASS__, 'render']
    );

    add_submenu_page(
      'xppwots-dashboard',
      'XPress — ' . I18n::t('records'),
      I18n::t('records'),
      'edit_posts',
      'xppwots-records',
      ['\\XPPWOTS\\Records_Page', 'render']
    );

    add_submenu_page(
      'xppwots-dashboard',
      'XPress — ' . I18n::t('settings'),
      I18n::t('settings'),
      'edit_posts',
      'xppwots-settings',
      ['\\XPPWOTS\\Settings', 'render']
    );

    add_submenu_page(
      'xppwots-dashboard',
      'XPress — ' . I18n::t('help'),
      I18n::t('help'),
      'edit_posts',
      'xppwots-help',
      ['\\XPPWOTS\\Help_Page', 'render']
    );

    add_submenu_page(
      'xppwots-dashboard',
      'XPress — ' . I18n::t('developer'),
      I18n::t('developer'),
      'edit_posts',
      'xppwots-developer',
      ['\\XPPWOTS\\Developer_Page', 'render'],
      99
    );
  }

  public static function render(): void
  {
?>
    <div class="wrap xppwots-wrap">
      <h1><?php echo esc_html(I18n::t('dashboard')); ?> X-Press</h1>
      <p class="description"><?php echo esc_html(I18n::t('page_desc_dashboard')); ?></p>
      <div id="xppwots-notices" aria-live="polite"></div>

      <section class="xppwots-card xppwots-tweets">
        <header class="xppwots-card__header">
          <h2><?php echo esc_html(I18n::t('tw_heading')); ?></h2>
        </header>

        <div class="xppwots-tw-form">
          <label for="xppwots-handle" class="screen-reader-text"><?php echo esc_html(I18n::t('tw_username')); ?></label>
          <input id="xppwots-handle" class="xppwots-input" type="text" inputmode="latin" placeholder="<?php echo esc_attr(I18n::t('tw_username')); ?>">
          <button id="xppwots-fetch" class="button button-primary"><?php echo esc_html(I18n::t('tw_fetch')); ?></button>
          <button id="xppwots-save-results" class="button button-success" disabled><?php echo esc_html(I18n::t('tw_save')); ?></button>
        </div>

        <div id="xppwots-profile" class="xppwots-profile" hidden>
          <img id="xppwots-avatar" class="xppwots-avatar" src="" alt="">
          <div class="xppwots-profile-meta">
            <div class="xppwots-profile-name" id="xppwots-name"></div>
            <div class="xppwots-profile-handle" id="xppwots-screen"></div>
            <p class="xppwots-profile-bio" id="xppwots-bio"></p>
          </div>
        </div>

        <div id="xppwots-list" class="xppwots-grid" data-state="idle" aria-live="polite"></div>

        <div class="xppwots-state xppwots-loading" id="xppwots-loading" hidden>
          <span class="xppwots-spinner" aria-hidden="true"></span>
          <span><?php echo esc_html(I18n::t('tw_loading')); ?></span>
        </div>

        <div class="xppwots-state xppwots-empty" id="xppwots-empty" hidden>
          <span><?php echo esc_html(I18n::t('tw_empty')); ?></span>
        </div>

        <div class="xppwots-state xppwots-error" id="xppwots-error" hidden>
          <span><?php echo esc_html(I18n::t('tw_error')); ?></span>
          <button class="button" id="xppwots-retry"><?php echo esc_html(I18n::t('tw_retry')); ?></button>
        </div>
      </section>
    </div>
<?php
  }
}
