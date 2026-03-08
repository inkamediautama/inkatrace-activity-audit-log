<?php
defined('ABSPATH') || exit;

add_action('waal_daily_purge_event', function(){
    if (!get_option('waal_purge_enabled')) return;
    $days=(int)get_option('waal_retention_days',0); if($days<=0) return;

    global $wpdb; $t = function_exists('waal_table_name') ? waal_table_name() : ($wpdb->prefix . 'activity_logs');
    $cut=waal_wp_date('Y-m-d H:i:s', current_time('timestamp')-($days*DAY_IN_SECONDS));
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $deleted = (int) $wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE created_at < %s", $cut));
    if ($deleted > 0 && function_exists('waal_bump_cache_token')) {
        waal_bump_cache_token();
    }
});
