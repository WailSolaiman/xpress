<?php

/**
 * Plugin Name: X-Press
 * Description: Simple admin UI with dashboard showing the most engaged tweets for X accounts with help of an n8n workflow.
 * Version: 1.0.0
 * Author: Wail Solaiman
 * Author URI: https://example.com/
 * Text Domain: xppwots
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) exit;

/** =======================
 *  0) Core plugin constants
 * ======================= */
define('XPPWOTS_PATH', plugin_dir_path(__FILE__));
define('XPPWOTS_URL',  plugin_dir_url(__FILE__));
define('XPPWOTS_VERSION', '1.0.0');

add_filter('gettext', 'xppwots_translate_plugin_description', 10, 3);
function xppwots_translate_plugin_description($translated, $text, $domain)
{
  if ($domain === 'xppwots' && $text === 'Simple admin UI with dashboard showing the most engaged tweets for X accounts with help of an n8n workflow.') {
    if (get_locale() === 'ar' || (function_exists('get_user_locale') && get_user_locale() === 'ar')) {
      return 'واجهة مستخدم إدارية بسيطة مع لوحة معلومات تعرض التغريدات الأكثر تفاعلاً لحسابات X بمساعدة سير عمل n8n.';
    }
  }
  return $translated;
}

/** ============================================================
 *  1) Licensing CONFIG — tweak here only if you reuse this file
 * ============================================================ */
$PLG = [
  'NAME'        => 'X-Press',
  'PRODUCT'     => 'xpress',                           // must match your license server product
  'VERSION'     => XPPWOTS_VERSION,                    // keep in sync with header Version
  'API_BASE'    => 'https://lic.wailsolaiman.online/index.php',  // your license/update server (index.php)
  'AUTHOR'      => 'Wail Solaiman',
  'AUTHOR_URI'  => 'https://example.com/',
  'PLUGIN_URI'  => 'https://example.com/',
  'CSS_PREFIX'  => 'xpress-lic',
];

/* Auto-derived keys (no need to change) */
$PLG['SLUG']        = plugin_basename(__FILE__);              // x-press/x-press.php
$PLG['BASENAME']    = dirname($PLG['SLUG']);                  // x-press
$PLG['TEXTDOMAIN']  = 'xppwots';
$PLG['MENU_SLUG']   = $PLG['PRODUCT'] . '-license';             // xpress-license
$PLG['OPT_LICENSE'] = $PLG['PRODUCT'] . '_license_key';         // xpress_license_key
$PLG['OPT_BOUND']   = $PLG['PRODUCT'] . '_bound_site';          // xpress_bound_site
$PLG['TRANSIENT']   = $PLG['PRODUCT'] . '_license_check_time';  // ping scheduler
/* صلاحية التحقق القصير (لإبطال شبه فوري) */
if (!defined('XPRESS_LICENSE_TTL')) {
  define('XPRESS_LICENSE_TTL', 30 * MINUTE_IN_SECONDS); // 30 دقيقة
}
$PLG['TRANSIENT_VALID'] = $PLG['PRODUCT'] . '_license_valid_ok'; // xpress_license_valid_ok

/* ============================================================
 *  1.1) Friendly HTTP error extractor (JSON/HTML → message)
 * ============================================================ */
function xpress_friendly_http_error($resp, $fallback)
{
  if (is_wp_error($resp)) return 'تعذّر الاتصال بالخادم.';
  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);
  $d = json_decode($body, true);
  if (is_array($d) && !empty($d['message'])) return $d['message'];
  return $fallback . ' (HTTP ' . $code . ')';
}

/** ============================================================
 *  2) Conflict-proof closures (helpers live inside $PLG only)
 * ============================================================ */
$PLG['is_licensed'] = static function (array $PLG): bool {
  $has_key = (bool) get_option($PLG['OPT_LICENSE'], '');
  if (!$has_key) return false;
  // يجب أن يكون آخر تحقق ناجح خلال TTL
  return (bool) get_transient($PLG['TRANSIENT_VALID']);
};

$PLG['provision'] = static function (array $PLG) {
  $resp = wp_remote_post($PLG['API_BASE'] . '?r=provision', [
    'timeout' => 12,
    'body' => [
      'product' => $PLG['PRODUCT'],
      'site'    => untrailingslashit(home_url()),
      // 'email' => get_option('admin_email')
    ],
  ]);
  if (is_wp_error($resp)) return new WP_Error('net', 'تعذّر الاتصال بخادم الترخيص.');

  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);
  if ($code !== 200) {
    return new WP_Error('http', xpress_friendly_http_error($resp, 'فشل طلب التفعيل'));
  }

  $d = json_decode($body, true);
  if (empty($d['license'])) return new WP_Error('bad', 'رد غير متوقع من الخادم.');

  update_option($PLG['OPT_LICENSE'], $d['license']);
  update_option($PLG['OPT_BOUND'],   $d['site'] ?? untrailingslashit(home_url()));
  set_transient($PLG['TRANSIENT_VALID'], 1, XPRESS_LICENSE_TTL); // اعتبره صالحًا الآن
  set_transient($PLG['TRANSIENT'], time(), 6 * HOUR_IN_SECONDS); // جدولة تحقق دوري
  return true;
};

$PLG['verify'] = static function (array $PLG, string $key) {
  if (empty($key)) return new WP_Error('empty', 'مفتاح الترخيص غير موجود أو غير مفعّل.');
  $resp = wp_remote_post($PLG['API_BASE'] . '?r=verify', [
    'timeout' => 12,
    'body' => [
      'license' => $key,
      'product' => $PLG['PRODUCT'],
      'site'    => untrailingslashit(home_url()),
    ],
  ]);
  if (is_wp_error($resp)) return new WP_Error('net', 'تعذّر الاتصال بالخادم.');

  $code = wp_remote_retrieve_response_code($resp);
  $body = wp_remote_retrieve_body($resp);
  if ($code !== 200) {
    return new WP_Error('http', xpress_friendly_http_error($resp, 'فشل التحقق من الترخيص'));
  }

  $data = json_decode($body, true);
  if (! is_array($data)) return new WP_Error('bad', 'رد غير متوقع من الخادم.');

  if (! empty($data['valid'])) {
    update_option($PLG['OPT_LICENSE'], $key);
    update_option($PLG['OPT_BOUND'],   $data['bound_site'] ?? untrailingslashit(home_url()));
    set_transient($PLG['TRANSIENT_VALID'], 1, XPRESS_LICENSE_TTL);
    set_transient($PLG['TRANSIENT'], time(), 6 * HOUR_IN_SECONDS);
    return true;
  }
  return new WP_Error('invalid', $data['message'] ?? 'مفتاح الترخيص غير صالح.');
};

/** ============================================================
 *  2.1) Early verify on load (fast revocation)
 *  - إن كان هناك مفتاح محلي ولم يكن لدينا تأكيد حديث، نتحقق الآن.
 *  - عند الفشل: نحذف المفتاح فورًا ونعرض إشعارًا للمشرف.
 * ============================================================ */
add_action('plugins_loaded', function () use ($PLG) {
  $key = get_option($PLG['OPT_LICENSE'], '');
  if (!$key) return;
  if (get_transient($PLG['TRANSIENT_VALID'])) return; // لدينا تحقق حديث

  $resp = wp_remote_post($PLG['API_BASE'] . '?r=verify', [
    'timeout' => 10,
    'body' => [
      'license' => $key,
      'product' => $PLG['PRODUCT'],
      'site'    => untrailingslashit(home_url()),
    ],
  ]);

  if (is_wp_error($resp)) {
    // لا نُسقط الترخيص بسبب خطأ شبكة مؤقت، سنحاول لاحقًا
    return;
  }

  $code = wp_remote_retrieve_response_code($resp);
  $data = json_decode(wp_remote_retrieve_body($resp), true);

  if ($code === 200 && is_array($data) && !empty($data['valid'])) {
    set_transient($PLG['TRANSIENT_VALID'], 1, XPRESS_LICENSE_TTL);
  } else {
    delete_option($PLG['OPT_LICENSE']);
    delete_option($PLG['OPT_BOUND']);
    delete_transient($PLG['TRANSIENT_VALID']);
    add_action('admin_notices', function () use ($PLG, $data, $code) {
      $msg = $data['message'] ?? 'تم إبطال الترخيص أو انتهاؤه. يرجى إعادة التفعيل.';
      echo '<div class="notice notice-error"><p>' . esc_html($msg) . ' (HTTP ' . intval($code) . ')</p></div>';
    });
  }
}, 1);

/** ============================================================
 *  3) License settings page (RTL) — auto & manual activation
 * ============================================================ */
add_action('admin_menu', function () use ($PLG) {
  add_options_page(
    'ترخيص ' . $PLG['NAME'],
    $PLG['NAME'] . ' License',
    'manage_options',
    $PLG['MENU_SLUG'],
    function () use ($PLG) {
      if (! current_user_can('manage_options')) return;

      $msg = '';
      if (isset($_POST['auto_activate']) && check_admin_referer('xpress_lic_nonce')) {
        $res = ($PLG['provision'])($PLG);
        $msg = is_wp_error($res)
          ? '<div class="notice notice-error"><p>' . esc_html($res->get_error_message()) . '</p></div>'
          : '<div class="notice notice-success"><p>تم إنشاء مفتاح الترخيص, اضغط على تفعيل.</p></div>';
      }
      if (isset($_POST['manual_activate']) && check_admin_referer('xpress_lic_nonce')) {
        $key = sanitize_text_field($_POST['license_key'] ?? '');
        $res = ($PLG['verify'])($PLG, $key);
        $msg = is_wp_error($res)
          ? '<div class="notice notice-error"><p>' . esc_html($res->get_error_message()) . '</p></div>'
          : '<div class="notice notice-success"><p>تم تفعيل الترخيص بنجاح.</p></div>';
      }

      $saved = get_option($PLG['OPT_LICENSE'], '');
      $bound = get_option($PLG['OPT_BOUND'], '');

      echo '<div class="wrap ' . $PLG['CSS_PREFIX'] . '-rtl" dir="rtl" style="direction:rtl;text-align:right">';
      echo '<h1>ترخيص ' . $PLG['NAME'] . '</h1>';
      echo $msg;

      if (empty($saved)) {
        echo '<form method="post" style="margin:10px 0">';
        wp_nonce_field('xpress_lic_nonce');
        echo '<button class="button button-primary" name="auto_activate" value="1">تفعيل تلقائي الآن</button>';
        echo '</form>';
        echo '<p style="color:#666">سيتم إنشاء مفتاح مرخّص وربطه بهذا الموقع تلقائيًا.</p>';
      }

      echo '<form method="post" style="margin:20px 0">';
      wp_nonce_field('xpress_lic_nonce');
      echo '<p><label>مفتاح الترخيص:</label></p>';
      echo '<input type="text" class="regular-text" name="license_key" value="' . esc_attr($saved) . '" ' . ($saved ? 'readonly' : '') . ' />';
      echo '<p><button class="button" name="manual_activate" value="1">تفعيل</button></p>';
      echo '</form>';

      echo '<hr><p><strong>الموقع المرتبط:</strong> ' . esc_html($bound ?: 'غير محدّد') . '</p>';
      echo '</div>';
    }
  );
});

add_action('admin_enqueue_scripts', function ($hook) use ($PLG) {
  if ($hook !== 'settings_page_' . $PLG['MENU_SLUG']) return;
  wp_add_inline_style('wp-admin', '.' . $PLG['CSS_PREFIX'] . '-rtl *{direction:rtl;text-align:right}');
});

/** ============================================================
 *  4) Admin notice + quick link when not licensed
 * ============================================================ */
add_action('admin_notices', function () use ($PLG) {
  if (($PLG['is_licensed'])($PLG)) return;
  if (! current_user_can('manage_options')) return;
  $url = admin_url('options-general.php?page=' . $PLG['MENU_SLUG']);
  echo '<div class="notice notice-error"><p>إضافة <strong>' . esc_html($PLG['NAME']) . '</strong> غير مُفعَّلة. '
    . 'يرجى <a href="' . esc_url($url) . '">تفعيل الترخيص</a> لاستخدامها.</p></div>';
});

add_filter('plugin_action_links_' . $PLG['SLUG'], function ($links) use ($PLG) {
  $url = admin_url('options-general.php?page=' . $PLG['MENU_SLUG']);
  array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Activate License', 'xppwots') . '</a>');
  return $links;
});

/** ============================================================
 *  5) Periodic verify (every 6h) — refresh short-valid flag
 * ============================================================ */
add_action('admin_init', function () use ($PLG) {
  $key = get_option($PLG['OPT_LICENSE'], '');
  if (!$key) return;

  // استخدم transient للجدولة كي لا نطلب كل تحميل
  if (get_transient($PLG['TRANSIENT'])) return;

  $resp = wp_remote_post($PLG['API_BASE'] . '?r=verify', [
    'timeout' => 12,
    'body' => [
      'license' => $key,
      'product' => $PLG['PRODUCT'],
      'site'    => untrailingslashit(home_url()),
    ],
  ]);

  if (!is_wp_error($resp)) {
    $code = wp_remote_retrieve_response_code($resp);
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if ($code === 200 && is_array($data) && !empty($data['valid'])) {
      set_transient($PLG['TRANSIENT_VALID'], 1, XPRESS_LICENSE_TTL);
    } else {
      // فشل التحقق الدوري: نُسقط الترخيص للأمان
      delete_option($PLG['OPT_LICENSE']);
      delete_option($PLG['OPT_BOUND']);
      delete_transient($PLG['TRANSIENT_VALID']);
    }
  }

  set_transient($PLG['TRANSIENT'], time(), 6 * HOUR_IN_SECONDS);
});

/** ============================================================
 *  6) Load features ONLY when licensed; otherwise stay dormant
 * ============================================================ */
if (($PLG['is_licensed'])($PLG)) {
  // Require all includes (your original files)
  require_once XPPWOTS_PATH . 'includes/class-xppwots-i18n.php';
  require_once XPPWOTS_PATH . 'includes/helpers.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-settings.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-admin-page.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-records-page.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-cpt.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-help-page.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-developer-page.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-rest.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-service.php';
  require_once XPPWOTS_PATH . 'includes/class-xppwots-plugin.php';

  // Boot only when licensed
  \XPPWOTS\Plugin::init();

  // Private update checks (optional: only when licensed)
  add_filter('pre_set_site_transient_update_plugins', function ($transient) use ($PLG) {
    if (empty($transient->checked)) return $transient;
    $key = get_option($PLG['OPT_LICENSE'], '');
    if (!$key) return $transient;
    if (!get_transient($PLG['TRANSIENT_VALID'])) return $transient; // لا تحديثات بدون تحقق حديث

    $resp = wp_remote_post($PLG['API_BASE'] . '?r=check_update', [
      'timeout' => 12,
      'body' => [
        'product'         => $PLG['PRODUCT'],
        'current_version' => $PLG['VERSION'],
        'license'         => $key,
        'site'            => untrailingslashit(home_url()),
      ],
    ]);
    if (is_wp_error($resp)) return $transient;

    $d = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($d['update_available'])) return $transient;

    $obj = (object)[
      'slug'        => $PLG['BASENAME'],
      'plugin'      => $PLG['SLUG'],
      'new_version' => $d['new_version'],
      'requires'    => $d['requires'] ?? '5.8',
      'tested'      => $d['tested']   ?? '6.6',
      'package'     => $d['package'],
      'url'         => $d['details_url'] ?? '',
    ];
    $transient->response[$PLG['SLUG']] = $obj;
    return $transient;
  });

  /** ==========================================================
   *  6.1) Plugin info modal (optional) — uses ?r=plugin_info
   * ========================================================== */
  add_filter('plugins_api', function ($result, $action, $args) use ($PLG) {
    if ($action !== 'plugin_information') return $result;
    if (empty($args->slug) || $args->slug !== $PLG['BASENAME']) return $result;

    $key  = get_option($PLG['OPT_LICENSE'], '');
    $resp = wp_remote_post($PLG['API_BASE'] . '?r=plugin_info&product=' . $PLG['PRODUCT'], [
      'timeout' => 12,
      'body'    => ['license' => $key],
    ]);
    if (is_wp_error($resp)) return $result;

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (!is_array($data)) return $result;

    return (object)[
      'name'          => $data['name']    ?? $PLG['NAME'],
      'slug'          => $PLG['BASENAME'],
      'version'       => $data['version'] ?? $PLG['VERSION'],
      'author'        => '<a href="' . esc_url($PLG['AUTHOR_URI']) . '">' . esc_html($PLG['AUTHOR']) . '</a>',
      'homepage'      => $data['homepage'] ?? $PLG['PLUGIN_URI'],
      'requires'      => $data['requires'] ?? '5.8',
      'tested'        => $data['tested']   ?? '6.6',
      'download_link' => '',
      'sections'      => [
        'description' => $data['description'] ?? '',
        'changelog'   => $data['changelog']   ?? '',
      ],
      'banners'       => [],
      'external'      => true,
    ];
  }, 10, 3);
} else {
  // Not licensed: stay dormant (features not loaded)
}

/** ============================================================
 *  7) Activation/Deactivation hooks (kept as no-op)
 * ============================================================ */
register_activation_hook(__FILE__, function () { /* no-op */
});
register_deactivation_hook(__FILE__, function () { /* no-op */
});
