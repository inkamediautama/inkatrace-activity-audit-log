<?php
defined('ABSPATH') || exit;

function waal_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'activity_logs';
}

function waal_wp_date($format, $timestamp = null) {
    $timestamp = $timestamp === null ? time() : (int) $timestamp;
    if (function_exists('wp_date')) {
        return wp_date((string) $format, $timestamp);
    }
    return date_i18n((string) $format, $timestamp);
}

function waal_create_table() {
    global $wpdb;
    $table = waal_table_name();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        user_role VARCHAR(50),
        action VARCHAR(50) NOT NULL,
        object_type VARCHAR(50),
        object_id BIGINT UNSIGNED,
        object_title TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        incident_note TEXT,
        incident_status VARCHAR(20) DEFAULT 'open',
        incident_updated_by BIGINT UNSIGNED NULL,
        incident_updated_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY idx_time (created_at),
        KEY idx_user (user_id),
        KEY idx_action (action),
        KEY idx_object_type (object_type),
        KEY idx_filter (created_at, user_id, action),
        KEY idx_dedupe (user_id, action, object_type, object_id, created_at)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function waal_table_exists() {
    global $wpdb;
    $table = waal_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $found = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    return $found === $table;
}
