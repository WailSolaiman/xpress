<?php

namespace XPPWOTS;

if (!defined('ABSPATH')) {
  exit;
}

final class Settings
{
  public static function init(): void
  {
    add_action('admin_init', [__CLASS__, 'register_settings']);
  }

  public static function register_settings(): void
  {
    register_setting('xppwots_settings_group', 'xppwots_settings', [
      'sanitize_callback' => [__CLASS__, 'sanitize'],
      'default' => [
        'profile_url' => '',
        'tweets_url' => '',
        'token' => '',
        'receive_mode' => 'callback',
        'poll_interval' => 15,
        'timeout' => 20,
      ],
    ]);

    add_settings_section('xppwots_s1', '', '__return_false', 'xppwots_settings');

    add_settings_field('profile_url', \XPPWOTS\I18n::t('field_profile_url'), [__CLASS__, 'field_profile_url'], 'xppwots_settings', 'xppwots_s1');
    add_settings_field('tweets_url',  \XPPWOTS\I18n::t('field_tweets_url'),  [__CLASS__, 'field_tweets_url'],  'xppwots_settings', 'xppwots_s1');
    add_settings_field('token', I18n::t('field_token'), [__CLASS__, 'field_token'], 'xppwots_settings', 'xppwots_s1');
    add_settings_field('receive_mode', I18n::t('receive_mode'), [__CLASS__, 'field_receive_mode'], 'xppwots_settings', 'xppwots_s1');
    add_settings_field('poll_interval', I18n::t('poll_interval'), [__CLASS__, 'field_poll'], 'xppwots_settings', 'xppwots_s1');
    add_settings_field('timeout', I18n::t('timeout_sec'), [__CLASS__, 'field_timeout'], 'xppwots_settings', 'xppwots_s1');
  }

  public static function sanitize(array $in): array
  {
    return [
      'profile_url' => isset($in['profile_url']) ? esc_url_raw($in['profile_url']) : '',
      'tweets_url' => isset($in['tweets_url']) ? esc_url_raw($in['tweets_url']) : '',
      'token' => isset($in['token']) ? sanitize_text_field($in['token']) : '',
      'receive_mode' => in_array($in['receive_mode'] ?? 'callback', ['callback', 'polling'], true) ? $in['receive_mode'] : 'callback',
      'poll_interval' => max(5, (int)($in['poll_interval'] ?? 15)),
      'timeout' => max(5, (int)($in['timeout'] ?? 20)),
    ];
  }

  public static function field_profile_url(): void
  {
    $o = get_option('xppwots_settings', []);
?>
    <input type="url" class="regular-text" name="xppwots_settings[profile_url]" value="<?php echo esc_attr($o['profile_url'] ?? ''); ?>" placeholder="https://api.example.com/api/profile">
  <?php
  }

  public static function field_tweets_url(): void
  {
    $o = get_option('xppwots_settings', []);
  ?>
    <input type="url" class="regular-text" name="xppwots_settings[tweets_url]" value="<?php echo esc_attr($o['tweets_url'] ?? ''); ?>" placeholder="https://api.example.com/api/tweets">
  <?php
  }

  public static function field_token(): void
  {
    $o = get_option('xppwots_settings', []);
  ?>
    <input type="text" class="regular-text" name="xppwots_settings[token]" value="<?php echo esc_attr($o['token'] ?? ''); ?>" placeholder="Optional">
  <?php
  }

  public static function field_receive_mode(): void
  {
    $o = get_option('xppwots_settings', []);
    $m = $o['receive_mode'] ?? 'callback';
  ?>
    <label><input type="radio" name="xppwots_settings[receive_mode]" value="callback" <?php checked($m, 'callback'); ?>> <?php echo esc_html(I18n::t('callback')); ?></label>
    <label style="margin-inline-start:10px;"><input type="radio" name="xppwots_settings[receive_mode]" value="polling" <?php checked($m, 'polling'); ?>> <?php echo esc_html(I18n::t('polling')); ?></label>
    <p class="description"><?php echo esc_html(I18n::t('callback_ep')); ?>: <?php echo esc_html(rest_url('xppwots/v1/callback')); ?></p>
  <?php
  }

  public static function field_poll(): void
  {
    $o = get_option('xppwots_settings', []);
  ?>
    <input type="number" class="small-text" min="5" name="xppwots_settings[poll_interval]" value="<?php echo esc_attr((int)($o['poll_interval'] ?? 15)); ?>">
  <?php
  }

  public static function field_timeout(): void
  {
    $o = get_option('xppwots_settings', []);
  ?>
    <input type="number" class="small-text" min="5" name="xppwots_settings[timeout]" value="<?php echo esc_attr((int)($o['timeout'] ?? 20)); ?>">
  <?php
  }

  public static function render(): void
  {
  ?>
    <div class="wrap xppwots-wrap">
      <h1>X-Press — <?php echo esc_html(I18n::t('settings')); ?></h1>
      <p class="description"><?php echo esc_html(I18n::t('page_desc_settings')); ?></p>
      <form action="options.php" method="post">
        <?php
        settings_fields('xppwots_settings_group');
        do_settings_sections('xppwots_settings');
        submit_button(I18n::t('save'));
        ?>
      </form>
    </div>
<?php
  }
}
