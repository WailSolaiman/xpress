<?php

namespace XPPWOTS;

if (!defined('ABSPATH')) {
  exit;
}

final class Help_Page
{
  public static function init(): void {}

  public static function render(): void
  {
    $cb = rest_url('xppwots/v1/callback');
?>
    <div class="wrap xppwots-wrap">
      <h1>X-Press — <?php echo esc_html(I18n::t('help')); ?></h1>
      <p class="description"><?php echo esc_html(I18n::t('help_intro')); ?></p>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_overview')); ?></h2>
        <p><?php echo esc_html(I18n::t('help_overview')); ?></p>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_requirements')); ?></h2>
        <ol>
          <li><?php echo esc_html(I18n::t('help_req_1')); ?></li>
          <li><?php echo esc_html(I18n::t('help_req_2')); ?></li>
          <li><?php echo esc_html(I18n::t('help_req_3')); ?></li>
        </ol>
        <h3><?php echo esc_html(I18n::t('help_rsp_profile')); ?></h3>
        <pre><code>[
  {
    "name": "Name",
    "screen_name": "handle",
    "description": "Bio",
    "image_url": "https://..."
  }
]</code></pre>
        <h3><?php echo esc_html(I18n::t('help_rsp_posts')); ?></h3>
        <pre><code>[
  {
    "tweets": [
      {
        "full_text": "...",
        "url": "https://x.com/.../status/123",
        "views": "205",
        "quote_count": 0,
        "reply_count": 0,
        "retweet_count": 2,
        "favorite_count": 1,
        "created_at": "Mon Aug 25 11:53:58 +0000 2025"
      }
    ]
  }
]</code></pre>
        <p><?php echo esc_html(I18n::t('help_rsp_note')); ?></p>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_quickstart')); ?></h2>
        <ol>
          <li><?php echo esc_html(I18n::t('help_qs_settings')); ?>
            <ul>
              <li><?php echo esc_html(I18n::t('help_qs_profile')); ?></li>
              <li><?php echo esc_html(I18n::t('help_qs_posts')); ?></li>
              <li><?php echo esc_html(I18n::t('help_qs_token')); ?></li>
              <li><?php echo esc_html(I18n::t('help_qs_timeout')); ?></li>
            </ul>
            <strong><?php echo esc_html(I18n::t('help_qs_save')); ?></strong>
          </li>
          <li><?php echo esc_html(I18n::t('help_qs_dashboard')); ?>
            <ul>
              <li><?php echo esc_html(I18n::t('help_qs_enter')); ?></li>
              <li><?php echo esc_html(I18n::t('help_qs_fetch')); ?></li>
              <li><?php echo esc_html(I18n::t('help_qs_states')); ?>
                <ul>
                  <li><?php echo esc_html(I18n::t('help_qs_loading')); ?></li>
                  <li><?php echo esc_html(I18n::t('help_qs_empty')); ?></li>
                  <li><?php echo esc_html(I18n::t('help_qs_error')); ?></li>
                </ul>
              </li>
              <li><?php echo esc_html(I18n::t('help_qs_store')); ?></li>
            </ul>
          </li>
        </ol>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_records')); ?></h2>
        <ul>
          <li><?php echo esc_html(I18n::t('help_records_1')); ?></li>
          <li><?php echo esc_html(I18n::t('help_records_2')); ?></li>
          <li><?php echo esc_html(I18n::t('help_records_3')); ?></li>
          <li><?php echo esc_html(I18n::t('help_records_4')); ?></li>
        </ul>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_settings_deep')); ?></h2>
        <ul>
          <li><?php echo esc_html(I18n::t('help_set_profile')); ?></li>
          <li><?php echo esc_html(I18n::t('help_set_posts')); ?></li>
          <li><?php echo esc_html(I18n::t('help_set_token')); ?></li>
          <li><?php echo esc_html(I18n::t('help_set_timeout')); ?></li>
          <li><?php echo esc_html(I18n::t('help_set_max')); ?></li>
        </ul>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_tips')); ?></h2>
        <ul>
          <li><?php echo esc_html(I18n::t('help_tip_empty')); ?></li>
          <li><?php echo esc_html(I18n::t('help_tip_error')); ?></li>
          <li><?php echo esc_html(I18n::t('help_tip_cors')); ?></li>
          <li><?php echo esc_html(I18n::t('help_tip_time')); ?></li>
        </ul>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_callback')); ?></h2>
        <p><code><?php echo esc_html($cb); ?></code></p>
      </div>

      <div class="xppwots-card">
        <h2><?php echo esc_html(I18n::t('help_title_privacy')); ?></h2>
        <p><?php echo esc_html(I18n::t('help_privacy')); ?></p>
      </div>
    </div>
<?php
  }
}
