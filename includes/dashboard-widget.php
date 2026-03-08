<?php
defined('ABSPATH') || exit;

add_action('wp_dashboard_setup', function(){
    if (waal_user_can_view_logs()) {
        wp_add_dashboard_widget('waal_widget','Latest Website Activity','waal_widget_render');
    }
});

function waal_widget_severity_payload($action) {
    $action = sanitize_key((string) $action);
    if ($action === 'bruteforce') {
        return ['label' => 'Security Alert', 'class' => 'danger'];
    }
    if ($action === 'login_failed') {
        return ['label' => 'Warning Alert', 'class' => 'warning'];
    }
    if (in_array($action, ['password_reset_failed', 'permission_denied', 'blocked_request'], true) || strpos($action, 'failed') !== false) {
        return ['label' => 'Threat', 'class' => 'warning'];
    }
    return ['label' => 'Safe', 'class' => 'safe'];
}

function waal_widget_render(){
    global $wpdb;

    $range_raw = (string) filter_input(INPUT_GET, 'waal_widget_range', FILTER_UNSAFE_RAW);
    $range = sanitize_key($range_raw);
    if (!in_array($range, ['all', 'today', '7days'], true)) {
        $range = 'all';
    }

    if ($range === 'today') {
        $start = current_time('Y-m-d') . ' 00:00:00';
        $end = current_time('Y-m-d') . ' 23:59:59';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, action, object_type, object_id, object_title, created_at
             FROM {$wpdb->prefix}activity_logs
             WHERE object_type NOT IN ('revision', 'customize_changeset')
               AND created_at BETWEEN %s AND %s
             ORDER BY created_at DESC
             LIMIT %d",
            $start,
            $end,
            50
        ));
    } elseif ($range === '7days') {
        $start = waal_wp_date('Y-m-d H:i:s', current_time('timestamp') - (7 * DAY_IN_SECONDS));
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, action, object_type, object_id, object_title, created_at
             FROM {$wpdb->prefix}activity_logs
             WHERE object_type NOT IN ('revision', 'customize_changeset')
               AND created_at >= %s
             ORDER BY created_at DESC
             LIMIT %d",
            $start,
            50
        ));
    } else {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, action, object_type, object_id, object_title, created_at
             FROM {$wpdb->prefix}activity_logs
             WHERE object_type NOT IN ('revision', 'customize_changeset')
             ORDER BY created_at DESC
             LIMIT %d",
            50
        ));
    }

    $logs = [];
    $seen = [];
    foreach ((array) $rows as $row) {
        $fingerprint = md5(implode('|', [
            (int) $row->user_id,
            (string) $row->action,
            (string) $row->object_type,
            (int) $row->object_id,
            (string) $row->object_title,
        ]));
        $current_ts = strtotime((string) $row->created_at);

        // Only collapse near-identical events within short interval.
        // Same action at clearly different times must still be visible.
        if (isset($seen[$fingerprint])) {
            $last_ts = (int) $seen[$fingerprint];
            if ($current_ts && $last_ts && abs($last_ts - $current_ts) <= 90) {
                continue;
            }
        }

        $seen[$fingerprint] = $current_ts ?: time();
        $logs[] = $row;
        if (count($logs) >= 5) {
            break;
        }
    }

    $today_url = add_query_arg('waal_widget_range', 'today', admin_url('index.php'));
    $week_url = add_query_arg('waal_widget_range', '7days', admin_url('index.php'));

    $security_from = waal_wp_date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $security_row = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) AS failed_total, COUNT(DISTINCT ip_address) AS unique_ip
         FROM {$wpdb->prefix}activity_logs
         WHERE action = %s
           AND created_at >= %s",
        'login_failed',
        $security_from
    ));
    $failed_total = (int) ($security_row->failed_total ?? 0);
    $unique_ip = (int) ($security_row->unique_ip ?? 0);

    if ($failed_total > 0) {
        echo '<p class="waal-widget-alert">';
        echo esc_html("Warning: {$failed_total} failed login attempts from {$unique_ip} IP (last 24 hours).");
        echo '</p>';
    }

    echo '<p class="waal-widget-filter">';
    echo '<a class="button ' . ($range === 'today' ? 'button-primary' : '') . '" href="' . esc_url($today_url) . '">Today</a>';
    echo '<a class="button ' . ($range === '7days' ? 'button-primary' : '') . '" href="' . esc_url($week_url) . '">Last 7 days</a>';
    echo '</p>';

    if (empty($logs)) {
        echo '<p>No activity recorded yet.</p>';
        return;
    }

    echo '<ul class="waal-widget-list">';
    foreach($logs as $l){
        $action = sanitize_key((string) ($l->action ?? ''));
        $content_label = function_exists('waal_get_content_label') ? waal_get_content_label($l->action, $l->object_title ?? '', $l->object_type ?? '') : ($l->object_title ?: '-');
        $when = strtotime((string) $l->created_at);
        $when_text = $when ? sprintf('%s ago', human_time_diff($when, current_time('timestamp'))) : (string) $l->created_at;
        $severity = waal_widget_severity_payload($action);

        echo '<li class="waal-widget-item">';
        echo '<div class="waal-widget-row">';
        echo '<span class="waal-widget-main">';
        echo '<span class="waal-widget-severity waal-widget-severity--' . esc_attr($severity['class']) . '"><span class="waal-widget-severity-dot" aria-hidden="true"></span>' . esc_html($severity['label']) . '</span>';
        echo '<span class="waal-widget-title">' . esc_html($content_label) . '</span>';
        echo '</span>';
        echo '<span class="waal-widget-time">' . esc_html($when_text) . '</span>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
    echo '<p class="waal-widget-footer"><a href="'.esc_url(admin_url('admin.php?page=wp-activity-log')).'">View All Logs</a></p>';
}
