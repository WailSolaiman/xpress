<?php

namespace XPPWOTS;

if (!defined('ABSPATH')) {
  exit;
}

final class Records_Page
{
  public static function init(): void {}

  public static function render(): void
  {
?>
    <div class="wrap xppwots-wrap">
      <h1>X-Press — <?php echo esc_html(I18n::t('records')); ?></h1>
      <p class="description"><?php echo esc_html(I18n::t('page_desc_records')); ?></p>
      <div id="xppwots-notices" aria-live="polite"></div>

      <div class="xppwots-toolbar" style="margin:12px 0; display:flex; gap:8px;">
        <button id="xppwots-groups-refresh" class="button"><?php echo esc_html(I18n::t('search')); ?></button>
      </div>

      <div class="xppwots-state xppwots-loading" id="xppwots-rec-loading" hidden>
        <span class="xppwots-spinner" aria-hidden="true"></span>
        <span><?php echo esc_html(I18n::t('processing')); ?></span>
      </div>

      <div class="xppwots-state xppwots-empty" id="xppwots-rec-empty" hidden>
        <span><?php echo esc_html(I18n::t('cpt_not_found')); ?></span>
      </div>

      <div class="xppwots-state xppwots-error" id="xppwots-rec-error" hidden>
        <span><?php echo esc_html(I18n::t('msg_generic_error')); ?></span>
        <button class="button" id="xppwots-groups-retry"><?php echo esc_html(I18n::t('tw_retry')); ?></button>
      </div>

      <div id="xppwots-groups-wrap" hidden>
        <table class="widefat fixed striped xppwots-table">
          <thead>
            <tr>
              <th><?php echo esc_html(I18n::t('col_name')); ?></th>
              <th style="width:120px"><?php echo esc_html(I18n::t('col_tweets')); ?></th>
              <th style="width:200px"><?php echo esc_html(I18n::t('col_latest')); ?></th>
              <th style="width:220px"><?php echo esc_html(I18n::t('col_totals')); ?></th>
              <th style="width:160px"><?php echo esc_html(I18n::t('actions')); ?></th>
            </tr>
          </thead>
          <tbody id="xppwots-groups-body"></tbody>
        </table>
        <tbody id="xppwots-groups-body"></tbody>
        </table>
      </div>
    </div>
<?php
  }
}
