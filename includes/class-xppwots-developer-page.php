<?php

namespace XPPWOTS;

if (!defined('ABSPATH')) exit;

final class Developer_Page
{

  public static function init(): void {}

  public static function render(): void
  {
    if (! current_user_can('edit_posts')) {
      return;
    }

    $img1 = esc_url(XPPWOTS_URL . 'assets/developer/dev-1.jpg');
    $img2 = esc_url(XPPWOTS_URL . 'assets/developer/dev-2.jpg');
?>
    <div class="wrap xppwots-wrap xppwots-developer-page">
      <h1><?php echo esc_html(I18n::t('about_dev')); ?></h1>
      <p class="description"><?php echo esc_html(I18n::t('page_desc_developer')); ?></p>

      <div class="xppwots-dev-avatars">
        <img src="<?php echo $img1; ?>" alt="<?php echo esc_attr(I18n::t('dev_name')); ?>">
        <img src="<?php echo $img2; ?>" alt="<?php echo esc_attr(I18n::t('dev_name')); ?>">
      </div>

      <div class="xppwots-dev-bio">
        <h2><?php echo esc_html(I18n::t('dev_name')); ?></h2>
        <p><?php echo esc_html(I18n::t('dev_bio')); ?></p>
        <p><a href="https://wailsolaiman.online/about/" target="_blank" rel="noopener noreferrer"><?php echo esc_html(I18n::t('read_more')); ?></a></p>
      </div>

      <div class="xppwots-dev-portfolio">
        <h2><?php echo esc_html(I18n::t('portfolio_title')); ?></h2>
        <p><?php echo esc_html(I18n::t('portfolio_desc')); ?></p>
        <p><a href="https://wailsolaiman.online/portfolio/" target="_blank" rel="noopener noreferrer"><?php echo esc_html(I18n::t('portfolio_button')); ?></a></p>
      </div>

      <div class="xppwots-dev-links">
        <h2><?php echo esc_html(I18n::t('useful_links')); ?></h2>
        <ul>
          <li><b><?php echo esc_html(I18n::t('link_api_keys')); ?></b></li>
          <li><b><?php echo esc_html(I18n::t('link_hostinger_install')); ?></b></li>
          <li><b><?php echo esc_html(I18n::t('link_local_install')); ?></b></li>
          <li><b><?php echo esc_html(I18n::t('link_hostinger_update')); ?></b></li>
        </ul>
        <p><a href="https://wailsolaiman.online/automation/" target="_blank" rel="noopener noreferrer"><?php echo esc_html(I18n::t('open_guides')); ?></a></p>
      </div>
    </div>
<?php
  }
}
