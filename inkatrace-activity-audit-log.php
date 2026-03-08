<?php
/**
 * Plugin Name: InkaTrace for Activity & Audit Log
 * Plugin URI:  https://products.inkamedia.id/inkatrace-for-activity-audit-log
 * Description: Provides real-time visibility into WordPress activity by recording critical actions across users, content, and system changes — all in one centralized audit log.
 * Version: 1.2.4.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * Author: Inkamedia
 * Author URI: https://www.inkamedia.id/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: inkatrace-activity-audit-log
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

define('WAAL_PATH', plugin_dir_path(__FILE__));
define('WAAL_URL', plugin_dir_url(__FILE__));
define('WAAL_BASENAME', plugin_basename(__FILE__));
define('WAAL_VERSION', '1.2.4.4');
define('WAAL_EDITION', 'free');
define('WAAL_DB_SCHEMA_VERSION', '2026-03-07-1');
if (!defined('WAAL_UPGRADE_URL')) {
    define('WAAL_UPGRADE_URL', 'https://products.inkamedia.id/inkatrace-for-activity-audit-log');
}

require_once WAAL_PATH . 'includes/db.php';
require_once WAAL_PATH . 'includes/settings.php';
require_once WAAL_PATH . 'includes/hooks.php';
require_once WAAL_PATH . 'includes/admin-page.php';
require_once WAAL_PATH . 'includes/table-class.php';
require_once WAAL_PATH . 'includes/dashboard-widget.php';
require_once WAAL_PATH . 'includes/purge.php';

add_action('plugins_loaded', function () {
    $installed_schema = (string) get_option('waal_db_schema_version', '');
    if (!function_exists('waal_create_table')) {
        return;
    }
    if ($installed_schema !== WAAL_DB_SCHEMA_VERSION || (function_exists('waal_table_exists') && !waal_table_exists())) {
        waal_create_table();
        update_option('waal_db_schema_version', WAAL_DB_SCHEMA_VERSION, false);
    }
});

add_action('admin_init', function () {
    $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
    if (!$request_uri) {
        return;
    }

    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    $base = $path ? basename($path) : '';

    if ($base === 'wp-activity-log-settings') {
        wp_safe_redirect(admin_url('admin.php?page=wp-activity-log-settings'));
        exit;
    }

    if ($base === 'wp-activity-log') {
        wp_safe_redirect(admin_url('admin.php?page=wp-activity-log'));
        exit;
    }
});

register_activation_hook(__FILE__, function () {
    if (function_exists('waal_create_table')) {
        waal_create_table();
    }

    if (!wp_next_scheduled('waal_daily_purge_event')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'waal_daily_purge_event');
    }
});

register_deactivation_hook(__FILE__, function () {
    $timestamp = wp_next_scheduled('waal_daily_purge_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'waal_daily_purge_event');
    }

});
