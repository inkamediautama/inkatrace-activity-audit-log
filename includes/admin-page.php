<?php
defined('ABSPATH') || exit;

function waal_menu_icon_data_uri() {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">'
        . '<circle cx="10" cy="10" r="7" stroke="#fff" stroke-width="1.7"/>'
        . '<circle cx="10" cy="10" r="4.4" stroke="#fff" stroke-width="1.7"/>'
        . '<circle cx="10" cy="10" r="2" stroke="#fff" stroke-width="1.7"/>'
        . '<line x1="10" y1="2" x2="10" y2="18" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>'
        . '<line x1="2" y1="10" x2="18" y2="10" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>'
        . '<path d="M10 10L6.2 13.8" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>'
        . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

add_action('admin_menu', function(){
    if (!waal_user_can_view_logs()) {
        return;
    }

    add_menu_page(
        'Activity Log',
        waal_t('Activity Log'),
        'read',
        'wp-activity-log',
        'waal_render_admin_page',
        waal_menu_icon_data_uri(),
        30
    );

    add_submenu_page(
        'wp-activity-log',
        'Activity Log',
        waal_t('Activity Log'),
        'read',
        'wp-activity-log',
        'waal_render_admin_page'
    );

    if (function_exists('waal_user_can_manage_logs') && waal_user_can_manage_logs()) {
        add_submenu_page(
            'wp-activity-log',
            'Settings Activity Log',
            waal_t('Settings'),
            'read',
            'wp-activity-log-settings',
            'waal_render_settings_page'
        );

        add_submenu_page(
            'wp-activity-log',
            'Compliance Reports',
            'Compliance Reports',
            'read',
            'wp-activity-log-insights',
            'waal_render_insights_page'
        );

        add_submenu_page(
            'wp-activity-log',
            'Documentation',
            waal_t('Documentation'),
            'read',
            'wp-activity-log-docs',
            'waal_render_documentation_page'
        );
    }

    add_submenu_page(
        'wp-activity-log',
        'Upgrade to Pro',
        waal_t("What's in Pro?"),
        'read',
        'wp-activity-log-upgrade',
        'waal_render_upgrade_page'
    );

    add_submenu_page(
        'wp-activity-log',
        'Upgrade to Premium',
        waal_t('Upgrade to Premium'),
        'read',
        'wp-activity-log-upgrade-direct',
        'waal_handle_upgrade_redirect'
    );
});

add_action('admin_post_waal_manual_purge_logs', 'waal_handle_manual_purge_logs');
add_action('admin_post_waal_save_filter_preset', 'waal_handle_save_filter_preset');
add_action('admin_post_waal_delete_filter_preset', 'waal_handle_delete_filter_preset');
add_action('wp_ajax_waal_ip_info', 'waal_ajax_ip_info');
add_action('wp_ajax_waal_save_incident_note', 'waal_ajax_save_incident_note');
add_action('admin_enqueue_scripts', 'waal_enqueue_admin_styles');

function waal_handle_upgrade_redirect() {
    $upgrade_url = function_exists('waal_get_upgrade_url') ? waal_get_upgrade_url() : '';
    if ($upgrade_url !== '') {
        wp_safe_redirect($upgrade_url);
        exit;
    }

    wp_die(esc_html(waal_t('Upgrade link is not available.')));
}

add_action('admin_print_footer_scripts', function () {
    $page_raw = (string) filter_input(INPUT_GET, 'page', FILTER_UNSAFE_RAW);
    $page = sanitize_key($page_raw);
    $is_plugin_page = in_array($page, ['wp-activity-log', 'wp-activity-log-settings', 'wp-activity-log-insights', 'wp-activity-log-docs', 'wp-activity-log-upgrade'], true);
    if (!$is_plugin_page || !function_exists('waal_get_upgrade_url')) {
        return;
    }

    $upgrade_url = esc_url_raw(waal_get_upgrade_url());
    if ($upgrade_url === '') {
        return;
    }
    ?>
    <script>
    (function () {
        var link = document.querySelector('#toplevel_page_wp-activity-log .wp-submenu a[href*="page=wp-activity-log-upgrade-direct"]');
        if (!link) return;
        link.href = <?php echo wp_json_encode($upgrade_url); ?>;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
    })();
    </script>
    <?php
});

function waal_adjust_hex_color($hex, $percent = 0) {
    $hex = ltrim((string) $hex, '#');
    if (!preg_match('/^[A-Fa-f0-9]{6}$/', $hex)) {
        $hex = '2C6652';
    }
    $percent = max(-100, min(100, (float) $percent));
    $rgb = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    foreach ($rgb as $index => $value) {
        $rgb[$index] = $percent >= 0
            ? (int) round($value + ((255 - $value) * ($percent / 100)))
            : (int) round($value * (1 + ($percent / 100)));
        $rgb[$index] = max(0, min(255, $rgb[$index]));
    }
    return sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]);
}

function waal_hex_to_rgb_string($hex) {
    $hex = ltrim((string) $hex, '#');
    if (!preg_match('/^[A-Fa-f0-9]{6}$/', $hex)) {
        $hex = '2C6652';
    }
    return implode(',', [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))]);
}

function waal_get_theme_css() {
    $accent_2 = function_exists('waal_get_theme_color') ? waal_get_theme_color() : '#2C6652';
    $accent_1 = waal_adjust_hex_color($accent_2, -18);
    $accent_3 = waal_adjust_hex_color($accent_2, 12);
    $accent_rgb = waal_hex_to_rgb_string($accent_2);
    return '.waal-admin-wrap{--waal-accent-1:' . $accent_1 . ';--waal-accent-2:' . $accent_2 . ';--waal-accent-3:' . $accent_3 . ';}'
        . '.waal-admin-wrap input:focus,.waal-admin-wrap select:focus,.waal-admin-wrap textarea:focus{border-color:' . $accent_2 . ';box-shadow:0 0 0 1px ' . $accent_2 . ';}'
        . '.waal-admin-wrap .nav-tab-active{border-color:' . $accent_2 . ';background:linear-gradient(135deg,' . $accent_1 . ',' . $accent_2 . ');}'
        . '.waal-admin-wrap .button-primary{border-color:' . $accent_2 . ';box-shadow:0 7px 18px rgba(' . $accent_rgb . ',.24);}'
        . '.waal-premium-cta{border-color:' . $accent_2 . ';background:linear-gradient(135deg,' . $accent_1 . ' 0%,' . $accent_2 . ' 55%,' . $accent_3 . ' 100%);box-shadow:0 10px 24px rgba(' . $accent_rgb . ',.28);}'
        . '.waal-upgrade-premium-btn.button{border-color:' . $accent_2 . ';background:linear-gradient(135deg,' . $accent_1 . ' 0%,' . $accent_2 . ' 55%,' . $accent_3 . ' 100%);box-shadow:0 8px 20px rgba(' . $accent_rgb . ',.24);}';
}

function waal_enqueue_admin_styles($hook_suffix = '') {
    $page_raw = (string) filter_input(INPUT_GET, 'page', FILTER_UNSAFE_RAW);
    $page = sanitize_key($page_raw);
    $is_plugin_page = in_array($page, ['wp-activity-log', 'wp-activity-log-settings', 'wp-activity-log-insights', 'wp-activity-log-docs', 'wp-activity-log-upgrade'], true);
    $is_dashboard = ($hook_suffix === 'index.php') && function_exists('waal_user_can_view_logs') && waal_user_can_view_logs();

    if (!$is_plugin_page && !$is_dashboard) {
        return;
    }

    $style_file = WAAL_PATH . 'includes/style.css';
    $version = file_exists($style_file) ? (string) filemtime($style_file) : '1.0.0';
    wp_enqueue_style('waal-admin-style', WAAL_URL . 'includes/style.css', [], $version);
    wp_add_inline_style('waal-admin-style', waal_get_theme_css());
    wp_enqueue_script('waal-admin-script', WAAL_URL . 'includes/admin.js', [], $version, true);
    wp_localize_script('waal-admin-script', 'waalAdminI18n', [
        'ipInfoTitle' => waal_t('IP Information'),
        'ipInfoLoading' => waal_t('Loading IP info...'),
        'ipInfoError' => waal_t('IP info is not available.'),
        'ipInfoType' => waal_t('Type'),
        'ipInfoScope' => waal_t('Scope'),
        'ipInfoCountry' => waal_t('Country'),
        'ipInfoRegion' => waal_t('Region'),
        'ipInfoCity' => waal_t('City'),
        'ipInfoOrg' => waal_t('Organization'),
        'ipInfoTimezone' => waal_t('Timezone'),
        'ipInfoExternalDisabled' => waal_t('External IP geolocation is disabled until you enable it in Settings.'),
        'saving' => waal_t('Saving...'),
        'saved' => waal_t('Saved'),
        'saveFailed' => waal_t('Failed to save incident note.'),
        'lastUpdated' => waal_t('Last updated'),
        'notesStored' => waal_t('Notes are stored locally for audit investigation.'),
    ]);
    wp_localize_script('waal-admin-script', 'waalAdminAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('waal_ip_info'),
        'incidentNonce' => wp_create_nonce('waal_save_incident_note'),
        'geoLookupEnabled' => waal_ip_geo_lookup_enabled() ? 1 : 0,
    ]);
}

function waal_ajax_save_incident_note() {
    if (!function_exists('waal_user_can_manage_audit_integrity') || !waal_user_can_manage_audit_integrity()) {
        wp_send_json_error(['message' => waal_t('Access denied.')], 403);
    }

    check_ajax_referer('waal_save_incident_note', 'nonce');

    $log_id = (int) filter_input(INPUT_POST, 'log_id', FILTER_VALIDATE_INT);
    if ($log_id <= 0) {
        wp_send_json_error(['message' => waal_t('Invalid log entry.')], 400);
    }

    $allowed_statuses = [
        'open' => waal_t('Open'),
        'investigating' => waal_t('Investigating'),
        'resolved' => waal_t('Resolved'),
        'false_positive' => waal_t('False Positive'),
    ];

    $status = sanitize_key((string) wp_unslash($_POST['incident_status'] ?? 'open'));
    if (!isset($allowed_statuses[$status])) {
        $status = 'open';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'activity_logs';
    $note = sanitize_textarea_field((string) wp_unslash($_POST['incident_note'] ?? ''));
    $updated_at = current_time('mysql');
    $updated_by = get_current_user_id();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $updated = $wpdb->update(
        $table,
        [
            'incident_note' => $note,
            'incident_status' => $status,
            'incident_updated_by' => $updated_by > 0 ? $updated_by : null,
            'incident_updated_at' => $updated_at,
        ],
        ['id' => $log_id],
        ['%s', '%s', '%d', '%s'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error(['message' => waal_t('Failed to save incident note.')], 500);
    }

    wp_send_json_success([
        'note' => $note,
        'status' => $status,
        'status_label' => $allowed_statuses[$status],
        'updated_at' => $updated_at,
        'updated_by' => $updated_by > 0 ? waal_get_actor_label($updated_by, '', '', '') : '-',
    ]);
}

function waal_get_ip_scope($ip) {
    $ip = trim((string) $ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return 'invalid';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && strpos($ip, '127.') === 0) {
            return 'loopback';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && ($ip === '::1' || stripos($ip, 'fe80:') === 0)) {
            return 'loopback';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false) {
            return 'private';
        }
        return 'reserved';
    }
    return 'public';
}

function waal_ip_geo_lookup_enabled() {
    return (int) get_option('waal_ip_geo_lookup_enabled', 0) === 1;
}

function waal_fetch_ip_geo_info($ip) {
    $ip = trim((string) $ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return [];
    }
    if (!waal_ip_geo_lookup_enabled()) {
        return [];
    }
    if (waal_get_ip_scope($ip) !== 'public') {
        return [];
    }

    $cache_key = 'waal_ip_info_' . md5($ip);
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $mapped = [];
    $providers = [
        [
            'url' => 'https://ipapi.co/' . rawurlencode($ip) . '/json/',
            'map' => static function (array $json) {
                return [
                    'country' => sanitize_text_field((string) ($json['country_name'] ?? '')),
                    'region' => sanitize_text_field((string) ($json['region'] ?? '')),
                    'city' => sanitize_text_field((string) ($json['city'] ?? '')),
                    'org' => sanitize_text_field((string) ($json['org'] ?? ($json['asn'] ?? ''))),
                    'timezone' => sanitize_text_field((string) ($json['timezone'] ?? '')),
                ];
            },
        ],
        [
            'url' => 'https://ipwho.is/' . rawurlencode($ip),
            'map' => static function (array $json) {
                return [
                    'country' => sanitize_text_field((string) ($json['country'] ?? '')),
                    'region' => sanitize_text_field((string) ($json['region'] ?? '')),
                    'city' => sanitize_text_field((string) ($json['city'] ?? '')),
                    'org' => sanitize_text_field((string) ($json['connection']['org'] ?? '')),
                    'timezone' => sanitize_text_field((string) ($json['timezone']['id'] ?? ($json['timezone'] ?? ''))),
                ];
            },
        ],
    ];

    foreach ($providers as $provider) {
        $response = wp_remote_get($provider['url'], [
            'timeout' => 4,
            'redirection' => 2,
            'user-agent' => 'InkaTrace/' . (defined('WAAL_VERSION') ? WAAL_VERSION : '1.0.0'),
        ]);
        if (is_wp_error($response)) {
            continue;
        }
        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            continue;
        }
        $body = wp_remote_retrieve_body($response);
        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            continue;
        }

        $mapped = call_user_func($provider['map'], $json);
        if (!empty(array_filter($mapped))) {
            break;
        }
    }

    if (empty($mapped)) {
        return [];
    }

    set_transient($cache_key, $mapped, 6 * HOUR_IN_SECONDS);
    return $mapped;
}

function waal_ajax_ip_info() {
    if (!is_admin() || !is_user_logged_in() || !waal_user_can_view_logs()) {
        wp_send_json_error(['message' => 'Access denied'], 403);
    }
    check_ajax_referer('waal_ip_info', 'nonce');

    $ip = sanitize_text_field((string) wp_unslash($_POST['ip'] ?? ''));
    $scope = waal_get_ip_scope($ip);
    $type = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' : 'IPv4';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        wp_send_json_error(['message' => 'Invalid IP'], 400);
    }

    $geo = waal_fetch_ip_geo_info($ip);
    wp_send_json_success([
        'ip' => $ip,
        'type' => $type,
        'scope' => $scope,
        'geo' => $geo,
    ]);
}

function waal_render_language_switcher($page_slug = 'wp-activity-log') {
    if (!function_exists('waal_get_supported_languages') || !function_exists('waal_get_ui_language')) {
        return;
    }

    $supported = waal_get_supported_languages();
    if (empty($supported) || !is_array($supported)) {
        return;
    }

    $current = waal_get_ui_language();
    $nonce = wp_create_nonce('waal_switch_lang');
    $preserve_keys = [
        'from',
        'to',
        'user',
        'log_event',
        'log_action',
        'q',
        'per_page',
        'paged',
        'tab',
    ];
    ?>
    <form method="get" class="waal-lang-switcher">
        <input type="hidden" name="page" value="<?php echo esc_attr($page_slug); ?>">
        <input type="hidden" name="waal_lang_nonce" value="<?php echo esc_attr($nonce); ?>">
        <?php foreach ($preserve_keys as $key): ?>
            <?php
            $preserved_value = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW);
            if ($preserved_value === null) {
                continue;
            }
            ?>
            <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(sanitize_text_field((string) $preserved_value)); ?>">
        <?php endforeach; ?>
        <label for="waal-lang"><?php echo esc_html(function_exists('waal_t') ? waal_t('Language') : 'Language'); ?></label>
        <select id="waal-lang" name="waal_lang" onchange="this.form.submit()">
            <?php foreach ($supported as $lang_code => $lang_label): ?>
                <option value="<?php echo esc_attr((string) $lang_code); ?>" <?php selected($current, (string) $lang_code); ?>>
                    <?php echo esc_html((string) $lang_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php
}

function waal_cache_key($prefix, $payload = []) {
    $payload['cache_token'] = waal_get_cache_token();
    return 'waal_' . $prefix . '_' . md5(wp_json_encode($payload));
}

function waal_get_cache_token() {
    return (int) get_option('waal_cache_token', 1);
}

function waal_bump_cache_token() {
    update_option('waal_cache_token', waal_get_cache_token() + 1, false);
}

function waal_get_filter_preset_storage_key() {
    return 'waal_filter_presets';
}

function waal_sanitize_filter_preset_label($label) {
    $label = trim(sanitize_text_field((string) $label));
    if ($label === '') {
        return '';
    }
    return function_exists('mb_substr') ? mb_substr($label, 0, 60) : substr($label, 0, 60);
}

function waal_get_filter_presets() {
    if (!is_user_logged_in()) {
        return [];
    }
    $presets = get_user_meta(get_current_user_id(), waal_get_filter_preset_storage_key(), true);
    return is_array($presets) ? $presets : [];
}

function waal_save_filter_presets(array $presets) {
    if (!is_user_logged_in()) {
        return false;
    }
    update_user_meta(get_current_user_id(), waal_get_filter_preset_storage_key(), $presets);
    return true;
}

function waal_get_current_filter_preset_key() {
    return sanitize_key((string) filter_input(INPUT_GET, 'waal_preset', FILTER_UNSAFE_RAW));
}

function waal_build_filter_preset_payload_from_request() {
    $user_raw = (int) filter_input(INPUT_POST, 'user', FILTER_VALIDATE_INT);
    return [
        'from' => sanitize_text_field((string) wp_unslash($_POST['from'] ?? '')),
        'to' => sanitize_text_field((string) wp_unslash($_POST['to'] ?? '')),
        'user' => max(0, $user_raw),
        'event' => sanitize_key((string) wp_unslash($_POST['log_event'] ?? '')),
        'action' => sanitize_key((string) wp_unslash($_POST['log_action'] ?? '')),
        'q' => sanitize_text_field((string) wp_unslash($_POST['q'] ?? '')),
    ];
}

function waal_apply_filter_preset(array $filters) {
    if (function_exists('waal_filter_presets_enabled') && !waal_filter_presets_enabled()) {
        return $filters;
    }
    $preset_key = waal_get_current_filter_preset_key();
    if ($preset_key === '') {
        return $filters;
    }
    $presets = waal_get_filter_presets();
    if (empty($presets[$preset_key]['filters']) || !is_array($presets[$preset_key]['filters'])) {
        return $filters;
    }
    $preset_filters = $presets[$preset_key]['filters'];
    $filters['from'] = sanitize_text_field((string) ($preset_filters['from'] ?? ''));
    $filters['to'] = sanitize_text_field((string) ($preset_filters['to'] ?? ''));
    $filters['user'] = max(0, (int) ($preset_filters['user'] ?? 0));
    $filters['event'] = sanitize_key((string) ($preset_filters['event'] ?? ''));
    $filters['action'] = sanitize_key((string) ($preset_filters['action'] ?? ''));
    $filters['q'] = sanitize_text_field((string) ($preset_filters['q'] ?? ''));
    return $filters;
}

function waal_handle_save_filter_preset() {
    if (!waal_user_can_view_logs()) {
        wp_die('Access denied');
    }
    if (function_exists('waal_filter_presets_enabled') && !waal_filter_presets_enabled()) {
        wp_safe_redirect(admin_url('admin.php?page=wp-activity-log'));
        exit;
    }
    check_admin_referer('waal_save_filter_preset', 'waal_preset_nonce');

    $label = waal_sanitize_filter_preset_label((string) wp_unslash($_POST['waal_preset_name'] ?? ''));
    $redirect_args = ['page' => 'wp-activity-log'];
    if ($label === '') {
        $redirect_args['waal_preset_status'] = 'empty';
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    $presets = waal_get_filter_presets();
    $key = sanitize_key(sanitize_title($label));
    if ($key === '') {
        $key = 'preset_' . strtolower(wp_generate_password(6, false, false));
    }
    if (!isset($presets[$key]) && count($presets) >= 8) {
        $presets = array_slice($presets, -7, null, true);
    }
    $presets[$key] = [
        'label' => $label,
        'filters' => waal_build_filter_preset_payload_from_request(),
        'updated_at' => current_time('mysql'),
    ];
    waal_save_filter_presets($presets);

    $redirect_args['waal_preset_status'] = 'saved';
    $redirect_args['waal_preset'] = $key;
    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

function waal_handle_delete_filter_preset() {
    if (!waal_user_can_view_logs()) {
        wp_die('Access denied');
    }
    if (function_exists('waal_filter_presets_enabled') && !waal_filter_presets_enabled()) {
        wp_safe_redirect(admin_url('admin.php?page=wp-activity-log'));
        exit;
    }
    check_admin_referer('waal_delete_filter_preset', 'waal_preset_delete_nonce');

    $preset_key = sanitize_key((string) wp_unslash($_POST['waal_preset_key'] ?? ''));
    $presets = waal_get_filter_presets();
    if ($preset_key !== '' && isset($presets[$preset_key])) {
        unset($presets[$preset_key]);
        waal_save_filter_presets($presets);
    }

    wp_safe_redirect(add_query_arg([
        'page' => 'wp-activity-log',
        'waal_preset_status' => 'deleted',
    ], admin_url('admin.php')));
    exit;
}

function waal_get_request_filters() {
    $from_raw = (string) filter_input(INPUT_GET, 'from', FILTER_UNSAFE_RAW);
    $to_raw = (string) filter_input(INPUT_GET, 'to', FILTER_UNSAFE_RAW);
    $user_raw = (int) filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
    $event_raw = (string) filter_input(INPUT_GET, 'log_event', FILTER_UNSAFE_RAW);
    $action_raw = (string) filter_input(INPUT_GET, 'log_action', FILTER_UNSAFE_RAW);
    $search_raw = (string) filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW);
    $per_page_raw = (int) filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT);

    $filters = [
        'from' => sanitize_text_field($from_raw),
        'to' => sanitize_text_field($to_raw),
        'user' => (int) $user_raw,
        'event' => sanitize_key($event_raw),
        'action' => sanitize_key($action_raw),
        'q' => sanitize_text_field($search_raw),
        'per_page' => max(1, (int) $per_page_raw),
    ];

    return waal_apply_filter_preset($filters);
}

function waal_has_active_filters(array $filters) {
    return !empty($filters['from']) || !empty($filters['to']) || !empty($filters['user']) || !empty($filters['event']) || !empty($filters['action']) || !empty($filters['q']);
}

function waal_get_purge_query_context(array $filters) {
    global $wpdb;

    $t = $wpdb->prefix . 'activity_logs';
    $join = '';
    $where = "WHERE 1=1 AND l.object_type NOT IN ('revision', 'customize_changeset')";
    $args = [];

    if (!empty($filters['from'])) {
        $where .= ' AND l.created_at >= %s';
        $args[] = sanitize_text_field((string) $filters['from']) . ' 00:00:00';
    }
    if (!empty($filters['to'])) {
        $where .= ' AND l.created_at <= %s';
        $args[] = sanitize_text_field((string) $filters['to']) . ' 23:59:59';
    }
    if (!empty($filters['user'])) {
        $where .= ' AND l.user_id = %d';
        $args[] = (int) $filters['user'];
    }
    if (!empty($filters['event'])) {
        $where .= ' AND l.object_type = %s';
        $args[] = sanitize_key((string) $filters['event']);
    }
    if (!empty($filters['action'])) {
        $verb = sanitize_key((string) $filters['action']);
        $raw_actions = function_exists('waal_get_raw_actions_for_verb') ? waal_get_raw_actions_for_verb($verb) : [$verb];
        $raw_actions = array_values(array_unique(array_filter(array_map('sanitize_key', (array) $raw_actions))));
        if (!empty($raw_actions)) {
            $where .= ' AND l.action IN (' . implode(',', array_fill(0, count($raw_actions), '%s')) . ')';
            $args = array_merge($args, $raw_actions);
        }
    }
    if (!empty($filters['q'])) {
        $join = 'LEFT JOIN ' . $wpdb->users . ' u ON u.ID = l.user_id';
        $like = '%' . $wpdb->esc_like(sanitize_text_field((string) $filters['q'])) . '%';
        $where .= ' AND (u.display_name LIKE %s OR u.user_login LIKE %s OR l.user_role LIKE %s OR l.action LIKE %s OR l.object_type LIKE %s OR l.object_title LIKE %s OR l.ip_address LIKE %s OR l.user_agent LIKE %s)';
        $args = array_merge($args, [$like, $like, $like, $like, $like, $like, $like, $like]);
    }

    return ['table' => $t, 'join' => $join, 'where' => $where, 'args' => $args];
}

function waal_count_logs_for_purge(array $filters) {
    global $wpdb;
    $ctx = waal_get_purge_query_context($filters);
    $sql = 'SELECT COUNT(*) FROM ' . $ctx['table'] . ' l ' . $ctx['join'] . ' ' . $ctx['where'];
    if (!empty($ctx['args'])) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = $wpdb->prepare($sql, $ctx['args']);
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    return (int) $wpdb->get_var($sql);
}

function waal_delete_logs_for_purge(array $filters) {
    global $wpdb;
    $ctx = waal_get_purge_query_context($filters);
    $sql = 'DELETE l FROM ' . $ctx['table'] . ' l ' . $ctx['join'] . ' ' . $ctx['where'];
    if (!empty($ctx['args'])) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = $wpdb->prepare($sql, $ctx['args']);
    }
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    return (int) $wpdb->query($sql);
}

function waal_handle_manual_purge_logs() {
    if (!function_exists('waal_user_can_manage_audit_integrity') || !waal_user_can_manage_audit_integrity()) {
        wp_die('Access denied');
    }

    check_admin_referer('waal_manual_purge_logs', 'waal_purge_nonce');

    $post_user = (int) filter_input(INPUT_POST, 'user', FILTER_VALIDATE_INT);
    $filters = [
        'from' => sanitize_text_field((string) (wp_unslash($_POST['from'] ?? ''))),
        'to' => sanitize_text_field((string) (wp_unslash($_POST['to'] ?? ''))),
        'user' => max(0, $post_user),
        'event' => sanitize_key((string) (wp_unslash($_POST['log_event'] ?? ''))),
        'action' => sanitize_key((string) (wp_unslash($_POST['log_action'] ?? ''))),
        'q' => sanitize_text_field((string) (wp_unslash($_POST['q'] ?? ''))),
    ];

    $deleted = waal_delete_logs_for_purge($filters);
    waal_bump_cache_token();
    $scope = waal_has_active_filters($filters) ? 'filtered' : 'all';

    $redirect_args = [
        'page' => 'wp-activity-log',
        'waal_purge_status' => 'success',
        'waal_purge_deleted' => max(0, (int) $deleted),
        'waal_purge_scope' => $scope,
        'from' => $filters['from'],
        'to' => $filters['to'],
        'user' => $filters['user'],
        'log_event' => $filters['event'],
        'log_action' => $filters['action'],
        'q' => $filters['q'],
    ];

    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

function waal_get_filtered_logs($limit = null, $offset = 0) {
    global $wpdb;
    $t = $wpdb->prefix . 'activity_logs';

    $ctx = waal_get_log_query_context();
    $effective_limit = $limit === null ? max(1, waal_count_filtered_logs()) : max(1, (int) $limit);
    $sql = "SELECT l.* FROM $t l {$ctx['join']} {$ctx['where']} ORDER BY l.created_at DESC LIMIT %d OFFSET %d";
    $ctx['args'][] = $effective_limit;
    $ctx['args'][] = max(0, (int) $offset);
    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    return $wpdb->get_results($wpdb->prepare($sql, $ctx['args']));
}

function waal_count_filtered_logs() {
    global $wpdb;
    $t = $wpdb->prefix . 'activity_logs';
    $ctx = waal_get_log_query_context();
    $sql = "SELECT COUNT(*) FROM $t l {$ctx['join']} {$ctx['where']}";

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    return (int) $wpdb->get_var($wpdb->prepare($sql, $ctx['args']));
}

function waal_get_log_query_context() {
    global $wpdb;
    $filters = waal_get_request_filters();
    $from = $filters['from'];
    $to = $filters['to'];
    $user = $filters['user'];
    $event = $filters['event'];
    $act = $filters['action'];
    $search = $filters['q'];

    $dedupe_seconds = 90;
    $where = "WHERE 1=1
              AND l.object_type NOT IN ('revision', 'customize_changeset')
              AND NOT EXISTS (
                    SELECT 1
                    FROM {$wpdb->prefix}activity_logs d
                    WHERE d.id > l.id
                      AND d.user_id = l.user_id
                      AND d.action = l.action
                      AND d.object_type <=> l.object_type
                      AND d.object_id <=> l.object_id
                      AND d.created_at >= l.created_at
                      AND d.created_at <= DATE_ADD(l.created_at, INTERVAL {$dedupe_seconds} SECOND)
              )";
    $args = [];

    if ($from) {
        $where .= " AND l.created_at >= %s";
        $args[] = $from . ' 00:00:00';
    }
    if ($to) {
        $where .= " AND l.created_at <= %s";
        $args[] = $to . ' 23:59:59';
    }
    if ($user) {
        $where .= " AND l.user_id = %d";
        $args[] = $user;
    }
    if ($event) {
        $where .= " AND l.object_type = %s";
        $args[] = $event;
    }
    if ($act) {
        $raw_actions = function_exists('waal_get_raw_actions_for_verb') ? waal_get_raw_actions_for_verb($act) : [$act];
        $raw_actions = array_values(array_unique(array_filter(array_map('sanitize_key', (array) $raw_actions))));
        if (!empty($raw_actions)) {
            $where .= " AND l.action IN (" . implode(',', array_fill(0, count($raw_actions), '%s')) . ")";
            $args = array_merge($args, $raw_actions);
        }
    }
    if ($search) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= " AND (u.display_name LIKE %s OR u.user_login LIKE %s OR l.user_role LIKE %s OR l.action LIKE %s OR l.object_type LIKE %s OR l.object_title LIKE %s OR l.ip_address LIKE %s OR l.user_agent LIKE %s)";
        $args = array_merge($args, [$like, $like, $like, $like, $like, $like, $like, $like]);
    }

    $join = '';
    if ($search !== '') {
        $join = 'LEFT JOIN ' . $wpdb->users . ' u ON u.ID=l.user_id';
    }

    $where .= ' AND l.id >= %d';
    $args[] = 0;
    return ['where' => $where, 'args' => $args, 'join' => $join];
}

function waal_get_available_actions() {
    global $wpdb;
    $cache_key = waal_cache_key('actions', ['v' => 1]);
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $t = $wpdb->prefix . 'activity_logs';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $sql = "SELECT DISTINCT action FROM {$t} WHERE object_type NOT IN ('revision', 'customize_changeset') ORDER BY action ASC";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $actions = $wpdb->get_col($sql);
    if (!$actions) {
        return [];
    }
    $result = [];
    foreach ((array) $actions as $raw_action) {
        $verb = function_exists('waal_get_action_verb')
            ? waal_get_action_verb((string) $raw_action)
            : sanitize_key((string) $raw_action);
        if ($verb !== '' && !in_array($verb, $result, true)) {
            $result[] = $verb;
        }
    }
    sort($result);
    set_transient($cache_key, $result, 120);
    return $result;
}

function waal_get_available_events() {
    global $wpdb;
    $cache_key = waal_cache_key('events', ['v' => 1]);
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $t = $wpdb->prefix . 'activity_logs';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $sql = "SELECT DISTINCT object_type FROM {$t} WHERE object_type NOT IN ('revision', 'customize_changeset') ORDER BY object_type ASC";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $events = $wpdb->get_col($sql);
    $result = array_values(array_filter(array_map('sanitize_key', (array) $events)));
    set_transient($cache_key, $result, 120);
    return $result;
}

function waal_get_human_name_for_user($user_id, $user = null) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return '';
    }

    if (!$user || !($user instanceof WP_User)) {
        $user = get_userdata($user_id);
    }
    if (!$user) {
        return 'User #' . $user_id;
    }

    $first_name = trim((string) get_user_meta($user_id, 'first_name', true));
    $last_name = trim((string) get_user_meta($user_id, 'last_name', true));
    $full_name = trim($first_name . ' ' . $last_name);
    if ($full_name !== '') {
        return $full_name;
    }

    $display_name = trim((string) ($user->display_name ?? ''));
    if ($display_name !== '') {
        return $display_name;
    }

    $user_login = trim((string) ($user->user_login ?? ''));
    if ($user_login !== '') {
        return $user_login;
    }

    return 'User #' . $user_id;
}

function waal_get_action_filter_label($action) {
    $verb = sanitize_key((string) $action);
    if ($verb === '') {
        return '';
    }
    if (function_exists('waal_get_action_verb_label')) {
        return (string) waal_get_action_verb_label($verb);
    }
    return ucwords(str_replace('_', ' ', $verb));
}

function waal_get_log_user_options() {
    global $wpdb;

    $options = [];
    $collect_user = static function ($user) use (&$options) {
        $user_id = (int) ($user->ID ?? 0);
        if ($user_id <= 0) {
            return;
        }
        $label = waal_get_human_name_for_user($user_id, $user);
        $options[$user_id] = $label;
    };

    // Primary source: users registered on current site/blog.
    $users = get_users([
        'fields' => ['ID', 'display_name', 'user_login'],
        'orderby' => 'display_name',
        'order' => 'ASC',
        'blog_id' => get_current_blog_id(),
    ]);

    foreach ((array) $users as $user) {
        $collect_user($user);
    }

    // Fallback for environments where blog membership query returns too few users.
    if (count($options) <= 1) {
        $all_users = get_users([
            'fields' => ['ID', 'display_name', 'user_login'],
            'orderby' => 'display_name',
            'order' => 'ASC',
        ]);
        foreach ((array) $all_users as $user) {
            $collect_user($user);
        }
    }

    // Fallback: include user IDs found in logs (useful for imported/migrated logs).
    $log_table = $wpdb->prefix . 'activity_logs';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $sql = "SELECT DISTINCT user_id FROM {$log_table} WHERE user_id > 0 ORDER BY user_id ASC";
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $log_user_ids = $wpdb->get_col($sql);
    foreach ((array) $log_user_ids as $raw_user_id) {
        $user_id = (int) $raw_user_id;
        if ($user_id <= 0 || isset($options[$user_id])) {
            continue;
        }
        $user = get_userdata($user_id);
        $options[$user_id] = waal_get_human_name_for_user($user_id, $user);
    }

    if (!empty($options)) {
        natcasesort($options);
    }

    return $options;
}

function waal_get_logs_db_size_label() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'activity_logs';
    $size_bytes = 0;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $size_row = $wpdb->get_row(
        $wpdb->prepare(
            'SELECT (DATA_LENGTH + INDEX_LENGTH) AS total_size
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
            DB_NAME,
            $table_name
        )
    );

    if ($size_row && isset($size_row->total_size)) {
        $size_bytes = (int) $size_row->total_size;
    }

    return $size_bytes > 0 ? size_format($size_bytes, 2) : '0 B';
}

function waal_get_compliance_filters() {
    $range_raw = (string) filter_input(INPUT_GET, 'range', FILTER_UNSAFE_RAW);
    $from_raw = (string) filter_input(INPUT_GET, 'from', FILTER_UNSAFE_RAW);
    $to_raw = (string) filter_input(INPUT_GET, 'to', FILTER_UNSAFE_RAW);
    $range = sanitize_key($range_raw);
    if (!in_array($range, ['last7', 'last30', 'last90', 'custom'], true)) {
        $range = 'last30';
    }

    $default_to = current_time('Y-m-d');
    $default_from = gmdate('Y-m-d', strtotime('-29 days', strtotime($default_to)));

    if ($range === 'custom') {
        $from = sanitize_text_field($from_raw !== '' ? $from_raw : $default_from);
        $to = sanitize_text_field($to_raw !== '' ? $to_raw : $default_to);
    } else {
        $days_map = [
            'last7' => 6,
            'last30' => 29,
            'last90' => 89,
        ];
        $days_back = isset($days_map[$range]) ? $days_map[$range] : 29;
        $to = $default_to;
        $from = gmdate('Y-m-d', strtotime('-' . $days_back . ' days', strtotime($to)));
    }

    if ($from !== '' && $to !== '' && strtotime($from) > strtotime($to)) {
        $swap = $from;
        $from = $to;
        $to = $swap;
    }

    return [
        'range' => $range,
        'from' => $from,
        'to' => $to,
    ];
}

function waal_get_compliance_report_data(array $filters) {
    global $wpdb;

    $table = function_exists('waal_table_name') ? waal_table_name() : ($wpdb->prefix . 'activity_logs');
    $where = "WHERE object_type NOT IN ('revision', 'customize_changeset')";
    $args = [];

    if (!empty($filters['from'])) {
        $where .= ' AND created_at >= %s';
        $args[] = $filters['from'] . ' 00:00:00';
    }
    if (!empty($filters['to'])) {
        $where .= ' AND created_at <= %s';
        $args[] = $filters['to'] . ' 23:59:59';
    }

    $threat_actions = ['login_failed', 'bruteforce', 'password_reset_failed', 'permission_denied', 'blocked_request'];
    $warning_actions = ['login_failed', 'password_reset_failed'];
    $security_alert_actions = ['bruteforce'];

    $summary_sql = "
        SELECT
            COUNT(*) AS total_logs,
            SUM(CASE WHEN action IN ('" . implode("','", array_map('esc_sql', $threat_actions)) . "') THEN 1 ELSE 0 END) AS threat_logs,
            SUM(CASE WHEN action IN ('" . implode("','", array_map('esc_sql', $warning_actions)) . "') THEN 1 ELSE 0 END) AS warning_logs,
            SUM(CASE WHEN action IN ('" . implode("','", array_map('esc_sql', $security_alert_actions)) . "') THEN 1 ELSE 0 END) AS security_alert_logs,
            COUNT(DISTINCT CASE WHEN user_id > 0 THEN user_id END) AS active_users
        FROM {$table}
        {$where}
    ";
    if (!empty($args)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $summary_sql = $wpdb->prepare($summary_sql, $args);
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $summary = $wpdb->get_row($summary_sql);

    $top_sql = "
        SELECT action, COUNT(*) AS total
        FROM {$table}
        {$where}
        GROUP BY action
        ORDER BY total DESC, action ASC
        LIMIT 5
    ";
    if (!empty($args)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $top_sql = $wpdb->prepare($top_sql, $args);
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $top_actions = $wpdb->get_results($top_sql);

    $top_ip_sql = "
        SELECT ip_address, COUNT(*) AS total
        FROM {$table}
        {$where}
            AND action IN ('" . implode("','", array_map('esc_sql', $threat_actions)) . "')
            AND ip_address <> ''
        GROUP BY ip_address
        ORDER BY total DESC, ip_address ASC
        LIMIT 1
    ";
    if (!empty($args)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
        $top_ip_sql = $wpdb->prepare($top_ip_sql, $args);
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $top_ip = $wpdb->get_row($top_ip_sql);

    $total_logs = isset($summary->total_logs) ? (int) $summary->total_logs : 0;
    $threat_logs = isset($summary->threat_logs) ? (int) $summary->threat_logs : 0;
    $warning_logs = isset($summary->warning_logs) ? (int) $summary->warning_logs : 0;
    $security_alert_logs = isset($summary->security_alert_logs) ? (int) $summary->security_alert_logs : 0;
    $active_users = isset($summary->active_users) ? (int) $summary->active_users : 0;
    $safe_logs = max(0, $total_logs - $threat_logs);
    $threat_ratio = $total_logs > 0 ? round(($threat_logs / $total_logs) * 100, 2) : 0;

    return [
        'total_logs' => $total_logs,
        'threat_logs' => $threat_logs,
        'warning_logs' => $warning_logs,
        'security_alert_logs' => $security_alert_logs,
        'active_users' => $active_users,
        'safe_logs' => $safe_logs,
        'threat_ratio' => $threat_ratio,
        'top_risky_ip' => isset($top_ip->ip_address) ? (string) $top_ip->ip_address : '',
        'top_risky_ip_count' => isset($top_ip->total) ? (int) $top_ip->total : 0,
        'top_actions' => is_array($top_actions) ? $top_actions : [],
    ];
}

function waal_render_insights_page() {
    if (!function_exists('waal_user_can_manage_logs') || !waal_user_can_manage_logs()) {
        wp_die('Access denied');
    }
    $filters = waal_get_compliance_filters();
    $report = waal_get_compliance_report_data($filters);
    $upgrade_url = function_exists('waal_get_upgrade_url') ? waal_get_upgrade_url() : '#';
    ?>
    <div class="wrap waal-admin-wrap">
        <div class="waal-page-header">
            <div class="waal-page-header-row">
                <h1><?php echo esc_html(waal_t('Compliance Reports')); ?></h1>
                <?php waal_render_language_switcher('wp-activity-log-insights'); ?>
            </div>
            <p><?php echo esc_html(waal_t('Review period-based audit metrics from your activity log in the free edition.')); ?></p>
        </div>

        <div class="waal-card">
            <h3><?php echo esc_html(waal_t('Compliance Reports')); ?></h3>
            <p class="waal-section-desc"><?php echo esc_html(waal_t('Generate a compliance-friendly summary with event totals, threat counts, and top activities.')); ?></p>
            <form method="get" class="waal-insights-form">
                <input type="hidden" name="page" value="wp-activity-log-insights">
                <input type="hidden" name="waal_lang_nonce" value="<?php echo esc_attr(wp_create_nonce('waal_switch_lang')); ?>">
                <?php $current_lang = function_exists('waal_get_ui_language') ? waal_get_ui_language() : ''; ?>
                <?php if ($current_lang !== ''): ?>
                    <input type="hidden" name="waal_lang" value="<?php echo esc_attr($current_lang); ?>">
                <?php endif; ?>
                <div class="waal-insights-toolbar">
                    <div class="waal-field">
                        <label for="waal-compliance-range"><?php echo esc_html(waal_t('Range')); ?></label>
                        <select id="waal-compliance-range" name="range">
                            <option value="last7" <?php selected($filters['range'], 'last7'); ?>><?php echo esc_html(waal_t('Last 7 Days')); ?></option>
                            <option value="last30" <?php selected($filters['range'], 'last30'); ?>><?php echo esc_html(waal_t('Last 30 Days')); ?></option>
                            <option value="last90" <?php selected($filters['range'], 'last90'); ?>><?php echo esc_html(waal_t('Last 90 Days')); ?></option>
                            <option value="custom" <?php selected($filters['range'], 'custom'); ?>><?php echo esc_html(waal_t('Custom Range')); ?></option>
                        </select>
                    </div>
                    <div class="waal-field">
                        <label for="waal-compliance-from"><?php echo esc_html(waal_t('From')); ?></label>
                        <input id="waal-compliance-from" type="date" name="from" value="<?php echo esc_attr($filters['from']); ?>" <?php disabled($filters['range'] !== 'custom'); ?>>
                    </div>
                    <div class="waal-field">
                        <label for="waal-compliance-to"><?php echo esc_html(waal_t('To')); ?></label>
                        <input id="waal-compliance-to" type="date" name="to" value="<?php echo esc_attr($filters['to']); ?>" <?php disabled($filters['range'] !== 'custom'); ?>>
                    </div>
                    <div class="waal-field waal-field--action">
                        <label>&nbsp;</label>
                        <button class="button button-primary waal-field-submit" type="submit"><?php echo esc_html(waal_t('Refresh Report')); ?></button>
                    </div>
                </div>
            </form>

            <div class="waal-compliance-metrics">
                <div class="waal-compliance-metric">
                    <strong><?php echo esc_html(waal_t('Window:')); ?></strong>
                    <span><?php echo esc_html($filters['from'] . ' to ' . $filters['to']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--safe">
                    <strong><?php echo esc_html(waal_t('Total logs:')); ?></strong>
                    <span><?php echo esc_html((string) $report['total_logs']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--warning">
                    <strong><?php echo esc_html(waal_t('Threat logs:')); ?></strong>
                    <span><?php echo esc_html((string) $report['threat_logs']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--safe">
                    <strong><?php echo esc_html(waal_t('Active users:')); ?></strong>
                    <span><?php echo esc_html((string) $report['active_users']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--safe">
                    <strong><?php echo esc_html(waal_t('Safe logs:')); ?></strong>
                    <span><?php echo esc_html((string) $report['safe_logs']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--warning">
                    <strong><?php echo esc_html(waal_t('Warning logs:')); ?></strong>
                    <span><?php echo esc_html((string) $report['warning_logs']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--danger">
                    <strong><?php echo esc_html(waal_t('Security alert logs:')); ?></strong>
                    <span><?php echo esc_html((string) $report['security_alert_logs']); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--warning">
                    <strong><?php echo esc_html(waal_t('Threat ratio:')); ?></strong>
                    <span><?php echo esc_html(number_format_i18n($report['threat_ratio'], 2) . '%'); ?></span>
                </div>
                <div class="waal-compliance-metric waal-compliance-metric--warning">
                    <strong><?php echo esc_html(waal_t('Top risky IP:')); ?></strong>
                    <span>
                        <?php if ($report['top_risky_ip'] !== ''): ?>
                            <?php echo esc_html($report['top_risky_ip'] . ' (' . $report['top_risky_ip_count'] . ')'); ?>
                        <?php else: ?>
                            <?php echo esc_html(waal_t('No public threat IP detected.')); ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="waal-compliance-panel">
                <h4><?php echo esc_html(waal_t('Top Activities')); ?></h4>
                <?php if (empty($report['top_actions'])): ?>
                    <p class="description" style="margin-bottom:0;"><?php echo esc_html(waal_t('No log activity found in the selected period.')); ?></p>
                <?php else: ?>
                    <ul class="waal-compliance-top-list">
                        <?php foreach ($report['top_actions'] as $item): ?>
                            <li>
                                <?php
                                $action = isset($item->action) ? (string) $item->action : '';
                                $label = function_exists('waal_get_action_label') ? waal_get_action_label($action) : $action;
                                $count = isset($item->total) ? (int) $item->total : 0;
                                ?>
                                <span><?php echo esc_html($label); ?></span>
                                <strong><?php echo esc_html((string) $count); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <?php
}

function waal_render_upgrade_page() {
    if (!waal_user_can_view_logs()) {
        wp_die('Access denied');
    }
    $upgrade_url = function_exists('waal_get_upgrade_url') ? waal_get_upgrade_url() : '#';
    $feature_cards = [
        [
            'icon' => '🔒',
            'title' => waal_t('Permissions Matrix & Audit Guardrails'),
            'desc' => waal_t('Control access per module while keeping integrity-critical actions administrator-only.'),
            'bullets' => [
                waal_t('Per-module permission matrix'),
                waal_t('Admin-only purge/archive/restore'),
                waal_t('Safer audit evidence handling'),
            ],
        ],
        [
            'icon' => '📊',
            'title' => waal_t('Compliance Reports'),
            'desc' => waal_t('Generate compliance-friendly summaries and export CSV for audits or client handover.'),
            'bullets' => [
                waal_t('Window, totals, threats, active users'),
                waal_t('CSV compliance export'),
                waal_t('Clear summary layout'),
            ],
        ],
        [
            'icon' => '🕵️',
            'title' => waal_t('Forensic Timeline'),
            'desc' => waal_t('Investigate chronology quickly with focused filters and recent security-relevant actions.'),
            'bullets' => [
                waal_t('Filter by date, user, IP, limit'),
                waal_t('Incident-friendly timeline format'),
                waal_t('Faster investigation workflow'),
            ],
        ],
        [
            'icon' => '🗃️',
            'title' => waal_t('Long-term Retention & Restore'),
            'desc' => waal_t('Archive logs before purge and restore archived entries safely when required.'),
            'bullets' => [
                waal_t('Archive metadata snapshots'),
                waal_t('Restore with duplicate-skip protection'),
                waal_t('Status tracking for archive records'),
                waal_t('Import downloaded JSON archives'),
            ],
        ],
        [
            'icon' => '🖨️',
            'title' => waal_t('Branded PDF Exports'),
            'desc' => waal_t('Add company identity to PDF exports for cleaner handover to clients, auditors, and internal stakeholders.'),
            'bullets' => [
                waal_t('Company logo in PDF header'),
                waal_t('Company name and address'),
                waal_t('Branding managed from Style settings'),
            ],
        ],
        [
            'icon' => '🔔',
            'title' => waal_t('Advanced Notifications'),
            'desc' => waal_t('Get notified on suspicious logins, daily summaries, and critical site changes.'),
            'bullets' => [
                waal_t('Threat alert email'),
                waal_t('Daily activity report'),
                waal_t('Critical plugin/theme/core alerts'),
            ],
        ],
        [
            'icon' => '🧩',
            'title' => waal_t('Detail JSON View'),
            'desc' => waal_t('Open structured log detail and copy JSON for deeper technical analysis.'),
            'bullets' => [
                waal_t('Normalized JSON payload'),
                waal_t('Copy-ready output'),
                waal_t('IP context in detail modal'),
            ],
        ],
    ];
    ?>
    <div class="wrap waal-admin-wrap">
        <div class="waal-page-header">
            <div class="waal-page-header-row">
                <h1><?php echo esc_html(waal_t('Upgrade to Pro')); ?></h1>
                <?php waal_render_language_switcher('wp-activity-log-upgrade'); ?>
            </div>
            <p><?php echo esc_html(waal_t('Unlock advanced audit workflow built for compliance, investigation, and long-term retention.')); ?></p>
        </div>

        <section class="waal-card waal-upgrade-hero">
            <h2><?php echo esc_html(waal_t('Unlock the Full Power of InkaTrace Pro')); ?></h2>
            <p><?php echo esc_html(waal_t('Take your WordPress activity security and monitoring to the next level with advanced compliance, investigation, and retention workflows.')); ?></p>
        </section>

        <h2 id="waal-upgrade-features" class="waal-upgrade-section-title"><?php echo esc_html(waal_t('Premium Features')); ?></h2>

        <div class="waal-upgrade-grid">
            <?php foreach ($feature_cards as $card): ?>
                <section class="waal-card waal-upgrade-feature-card">
                    <div class="waal-upgrade-feature-head">
                        <span class="waal-upgrade-feature-icon" aria-hidden="true"><?php echo esc_html($card['icon']); ?></span>
                        <h3><?php echo esc_html($card['title']); ?></h3>
                        <span class="waal-upgrade-badge">PRO</span>
                    </div>
                    <p class="waal-upgrade-feature-desc"><?php echo esc_html($card['desc']); ?></p>
                    <ul class="waal-upgrade-list">
                        <?php foreach ($card['bullets'] as $item): ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>

        <div class="waal-card waal-upgrade-bottom-cta">
            <div class="waal-premium-cta">
                <div class="waal-premium-cta__meta">
                    <span class="waal-premium-cta__icon" aria-hidden="true">★</span>
                    <div>
                        <strong><?php echo esc_html(waal_t('Ready to Upgrade?')); ?></strong>
                        <p><?php echo esc_html(waal_t('Upgrade once to unlock advanced compliance, forensic timeline, and archive restore workflows.')); ?></p>
                    </div>
                </div>
                <a class="button waal-premium-cta__button" href="<?php echo esc_url($upgrade_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(waal_t('Upgrade to Pro')); ?></a>
            </div>
        </div>
    </div>
    <?php
}

function waal_render_documentation_page() {
    if (!function_exists('waal_user_can_manage_logs') || !waal_user_can_manage_logs()) {
        wp_die('Access denied');
    }

    $is_id = function_exists('waal_get_ui_language') && waal_get_ui_language() === 'id_ID';
    $page_desc = $is_id
        ? 'Panduan penggunaan lengkap untuk semua fitur Activity Log.'
        : 'Complete usage guide for all Activity Log features.';

    $sections = [
        [
            'icon' => 'dashicons-analytics',
            'title' => $is_id ? '1. Dashboard Log Aktivitas' : '1. Activity Log Dashboard',
            'items' => $is_id ? [
                'Tujuan: memonitor aktivitas user dan perubahan sistem dari satu layar utama tanpa berpindah menu.',
                'Langkah: isi filter Tanggal, User, Event, dan Aksi sesuai kebutuhan audit, lalu klik Terapkan Filter.',
                'Langkah lanjutan: klik Reset jika ingin menghapus semua filter dan kembali ke tampilan default.',
                'Jika preset filter diaktifkan pada tab Style, Anda dapat menyimpan kombinasi filter yang sering dipakai agar audit berulang lebih cepat.',
                'Validasi cepat: arahkan kursor ke kolom IP untuk melihat konteks lokasi dasar saat data IP publik tersedia.',
                'Investigasi detail: klik tombol Detail pada baris log untuk membuka informasi lengkap per kejadian.',
                'Gunakan Incident Notes di modal Detail untuk menyimpan catatan audit internal pada event tertentu.',
                'Catatan integritas: Purge Manual hanya tersedia untuk administrator agar bukti audit tidak mudah dihapus.',
            ] : [
                'Goal: monitor user activity and system changes from one central dashboard.',
                'Steps: set Date, User, Event, and Action filters, then click Apply Filter.',
                'Reset flow: click Reset anytime to clear all filters and return to the default view.',
                'If filter presets are enabled from Style, you can save frequently used filter combinations for faster repeat audits.',
                'Quick validation: hover the IP column to view basic geo context when public IP data is available.',
                'Deep investigation: click the Detail button on a row to open complete event context.',
                'Use Incident Notes in the Detail modal to keep internal audit comments attached to specific events.',
                'Integrity note: Manual Purge is administrator-only to protect audit evidence.',
            ],
        ],
        [
            'icon' => 'dashicons-download',
            'title' => $is_id ? '2. Ekspor Log' : '2. Export Logs',
            'items' => $is_id ? [
                'Di edisi Free, halaman dashboard menampilkan CTA ekspor agar tim Anda memahami bahwa ekspor terfilter tersedia di Pro.',
                'Di versi Pro, hasil ekspor CSV/Excel/PDF selalu mengikuti filter aktif sehingga data yang dibagikan tetap relevan.',
                'Best practice: atur filter terlebih dulu, cek hasil tabel, baru lakukan ekspor untuk menghindari data berlebih.',
                'Kontrol akses: hak ekspor di Pro mengikuti Permission Matrix agar hanya role tertentu yang bisa mengunduh data.',
            ] : [
                'In Free edition, the dashboard shows an export CTA so your team knows filtered export is available in Pro.',
                'In Pro, CSV/Excel/PDF exports always follow current filters so shared reports stay relevant.',
                'Best practice: apply filters first, verify table results, then export to avoid unnecessary data volume.',
                'Access control: Pro export rights follow the Permissions Matrix so downloads stay role-restricted.',
            ],
        ],
        [
            'icon' => 'dashicons-admin-generic',
            'title' => $is_id ? '3. Pengaturan > Umum' : '3. Settings > General',
            'items' => $is_id ? [
                'Role Access di halaman ini menentukan apakah Editor boleh melihat Activity Log, sementara Administrator selalu memiliki akses penuh.',
                'Auto-Purge dan durasi retensi hanya bisa diubah administrator untuk menjaga kebijakan audit tetap konsisten.',
                'Exclude Events dipakai untuk mengurangi noise log; kategori yang dicentang tidak dicatat untuk aktivitas baru.',
                'Dampak perubahan: Exclude Events tidak menghapus data historis yang sudah ada di database.',
                'Saran operasional: aktifkan Exclude Events secara bertahap dan evaluasi 1-2 hari sebelum menambah kategori baru.',
            ] : [
                'Role Access on this page controls whether Editors may view the Activity Log, while Administrators always keep full access.',
                'Auto-Purge and retention period are administrator-only to keep audit policy consistent.',
                'Exclude Events reduces log noise by skipping selected categories for future entries.',
                'Change impact: Exclude Events does not remove previously stored historical data.',
                'Operational tip: enable Exclude Events gradually and evaluate for 1-2 days before adding more categories.',
            ],
        ],
        [
            'icon' => 'dashicons-admin-customizer',
            'title' => $is_id ? '4. Pengaturan > Gaya' : '4. Settings > Style',
            'items' => $is_id ? [
                'Tab Style menggabungkan pengaturan visual dan opsi antarmuka agar halaman operasional tetap ringkas.',
                'Gunakan Theme Color untuk mengganti warna utama plugin; gradient tombol dan CTA akan menyesuaikan otomatis.',
                'Kolom HEX dapat diisi dengan copy-paste kode warna agar penyesuaian brand lebih presisi.',
                'Interface Options dipakai untuk menampilkan atau menyembunyikan Saved Filter Presets di dashboard sesuai kebutuhan tim.',
                'Export Branding tetap khusus Pro dan ditampilkan di Free sebagai CTA agar batas fitur tetap jelas.',
            ] : [
                'The Style tab groups visual controls and interface options so operational settings stay focused.',
                'Use Theme Color to change the plugin accent; button and CTA gradients adapt automatically.',
                'The HEX field supports copy-paste color codes for more precise brand matching.',
                'Interface Options lets you show or hide Saved Filter Presets in the dashboard depending on team preference.',
                'Export Branding remains Pro-only and is shown in Free as a clear upgrade CTA.',
            ],
        ],
        [
            'icon' => 'dashicons-email-alt',
            'title' => $is_id ? '5. Pengaturan > Notifikasi' : '5. Settings > Notifications',
            'items' => $is_id ? [
                'Di Free, administrator dapat mengaktifkan notifikasi email untuk ancaman login dan perubahan kritis situs.',
                'Di Pro, workflow notifikasi diperluas dengan kemampuan lanjutan seperti laporan harian dan kontrol audit yang lebih detail.',
                'Gunakan email tujuan yang aktif dipantau oleh tim operasional, bukan email pasif atau jarang dibuka.',
                'Saran implementasi: mulai dari alert ancaman terlebih dulu, lalu tambah laporan harian setelah alur stabil.',
            ] : [
                'In Free, administrators can enable email notifications for login threats and critical site changes.',
                'In Pro, the notification workflow expands with advanced capabilities such as daily reports and more detailed audit controls.',
                'Use a mailbox actively monitored by operations, not an infrequently checked address.',
                'Rollout tip: start with threat alerts first, then enable daily summaries once workflow is stable.',
            ],
        ],
        [
            'icon' => 'dashicons-shield',
            'title' => $is_id ? '6. Kontrol Akses Pro' : '6. Pro Access Controls',
            'items' => $is_id ? [
                'Di Free, kontrol akses dibatasi sederhana: Administrator selalu bisa membuka log, dan Editor dapat diizinkan melalui satu opsi.',
                'Di Pro, Anda dapat mengatur permission lanjutan untuk akses log, ekspor, insight, dan detail investigasi.',
                'Aksi sensitif tetap admin-only: purge, archive, restore, dan perubahan lisensi tetap dibatasi administrator.',
                'Pemisahan ini menjaga edisi gratis tetap sederhana sambil memberi kontrol akses yang lebih rinci di Pro.',
            ] : [
                'In Free, access control stays intentionally simple: Administrators always keep access, and Editors may be allowed through one setting.',
                'In Pro, you can configure advanced permissions for log access, exports, insights, and investigation details.',
                'Sensitive actions remain admin-only: purge, archive, restore, and license changes stay restricted.',
                'This keeps the free edition simple while leaving granular access control to Pro.',
            ],
        ],
        [
            'icon' => 'dashicons-media-document',
            'title' => $is_id ? '7. Laporan Kepatuhan' : '7. Compliance Reports',
            'items' => $is_id ? [
                'Di Free, halaman ini menampilkan ringkasan kepatuhan berbasis periode dari data log yang sudah tercatat.',
                'Metrik utama yang tersedia meliputi total log, log ancaman, pengguna aktif, dan aktivitas teratas.',
                'Gunakan laporan ini saat audit berkala, pemantauan internal, atau saat menyiapkan ringkasan untuk klien.',
                'Forensic Timeline tetap tersedia di Pro untuk investigasi kronologis yang lebih rinci.',
            ] : [
                'In Free, this page provides period-based compliance summaries from recorded activity log data.',
                'Available metrics include total logs, threat logs, active users, and top activities.',
                'Use this report for periodic audits, internal monitoring, or preparing summaries for clients.',
                'Forensic Timeline remains available in Pro for more detailed chronological investigation.',
            ],
        ],
        [
            'icon' => 'dashicons-backup',
            'title' => $is_id ? '8. Long-term Retention & Restore' : '8. Long-term Retention & Restore',
            'items' => $is_id ? [
                'Di Free, modul ini dijelaskan secara ringkas agar pengguna memahami bahwa retensi jangka panjang tersedia di Pro.',
                'Di Pro, sistem membuat snapshot archive sebelum purge agar data lama tetap bisa dipulihkan.',
                'Restore dilengkapi proteksi duplicate-skip untuk mencegah data ganda saat pengembalian log.',
                'Impor arsip JSON di Pro memungkinkan file hasil unduhan dimasukkan kembali sebelum proses restore.',
                'Status archive (Ready, Restored, Partial) memudahkan pelacakan hasil pemulihan oleh tim admin.',
            ] : [
                'In Free, this module is described briefly so users understand that long-term retention is available in Pro.',
                'In Pro, the system creates archive snapshots before purge so historical records can be restored.',
                'Restore includes duplicate-skip protection to prevent duplicated rows during recovery.',
                'JSON archive import in Pro lets you bring downloaded archive files back before restore.',
                'Archive statuses (Ready, Restored, Partial) make recovery tracking clear for administrators.',
            ],
        ],
        [
            'icon' => 'dashicons-translation',
            'title' => $is_id ? '9. Bahasa dan UI Bilingual' : '9. Language and Bilingual UI',
            'items' => $is_id ? [
                'Gunakan switch bahasa di header untuk beralih cepat antara Bahasa Indonesia dan English.',
                'Pilihan bahasa disimpan per akun user, jadi setiap admin bisa memakai bahasa yang berbeda.',
                'Jika ada istilah yang belum konsisten, laporkan melalui Need Help agar terjemahan dapat disempurnakan.',
            ] : [
                'Use the header language switcher to move between Bahasa Indonesia and English.',
                'Language preference is stored per user account, so each admin can keep a different UI language.',
                'If you find wording inconsistencies, report them via Need Help so translations can be refined.',
            ],
        ],
        [
            'icon' => 'dashicons-sos',
            'title' => $is_id ? '10. Butuh Bantuan' : '10. Need Help',
            'items' => $is_id ? [
                'Gunakan tombol Buka Form Laporan di halaman Settings untuk bug report, feature request, atau feedback.',
                'Isi Subject dengan ringkas dan spesifik, misalnya: "Timeline filter tidak menampilkan data user tertentu".',
                'Di Message, sertakan waktu kejadian, langkah reproduksi, hasil aktual, dan hasil yang diharapkan.',
                'Laporan yang lengkap mempercepat analisis tim support dan mengurangi bolak-balik klarifikasi.',
            ] : [
                'Use Open Report Form from Settings for bug reports, feature requests, or product feedback.',
                'Keep Subject short and specific, for example: "Timeline filter does not show a specific user".',
                'In Message, include timestamp, reproduction steps, actual result, and expected result.',
                'Complete reports speed up support analysis and reduce back-and-forth clarification.',
            ],
        ],
    ];
    ?>
    <div class="wrap waal-admin-wrap">
        <div class="waal-page-header">
            <div class="waal-page-header-row">
                <h1><?php echo esc_html(waal_t('Documentation')); ?></h1>
                <?php waal_render_language_switcher('wp-activity-log-docs'); ?>
            </div>
            <p><?php echo esc_html($page_desc); ?></p>
        </div>

        <div class="waal-doc-grid">
            <?php foreach ($sections as $section): ?>
                <div class="waal-card waal-doc-card">
                    <div class="waal-doc-card-head">
                        <h2 class="waal-doc-title">
                            <span class="dashicons <?php echo esc_attr($section['icon']); ?>" aria-hidden="true"></span>
                            <?php echo esc_html($section['title']); ?>
                        </h2>
                    </div>
                    <div class="waal-doc-card-body">
                        <ul>
                            <?php foreach ($section['items'] as $item): ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function waal_render_admin_page() {
    if (!waal_user_can_view_logs()) wp_die('Access denied');

    $filters = waal_get_request_filters();
    $filter_presets_enabled = function_exists('waal_filter_presets_enabled') ? waal_filter_presets_enabled() : true;
    $filter_presets = $filter_presets_enabled ? waal_get_filter_presets() : [];
    $current_preset_key = $filter_presets_enabled ? waal_get_current_filter_preset_key() : '';
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

    $table = new WAAL_Table();
    $table->prepare_items();
    $purge_visible_count = waal_count_filtered_logs();
    $purge_scope_is_filtered = waal_has_active_filters($filters);
    ?>
    <div class="wrap waal-admin-wrap">
        <div class="waal-page-header">
            <div class="waal-page-header-row">
                <h1><?php echo esc_html(waal_t('Activity Log')); ?></h1>
                <?php waal_render_language_switcher('wp-activity-log'); ?>
            </div>
        </div>
        <div class="waal-page-header-notices">
            <?php if (function_exists('waal_user_can_manage_logs') && waal_user_can_manage_logs()): ?>
                <?php $docs_url = admin_url('admin.php?page=wp-activity-log-docs'); ?>
                <div class="notice notice-info is-dismissible">
                    <p>
                        <?php echo esc_html(waal_t('Need setup guidance? Open Documentation for a complete feature guide.')); ?>
                        <a class="button button-secondary" style="margin-left:8px;" href="<?php echo esc_url($docs_url); ?>"><?php echo esc_html(waal_t('Open Documentation')); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            <?php
            $purge_status = sanitize_key((string) filter_input(INPUT_GET, 'waal_purge_status', FILTER_UNSAFE_RAW));
            if ($purge_status === 'success') {
                $purged_total = (int) filter_input(INPUT_GET, 'waal_purge_deleted', FILTER_VALIDATE_INT);
                $purged_scope = sanitize_key((string) filter_input(INPUT_GET, 'waal_purge_scope', FILTER_UNSAFE_RAW));
                $scope_label = ($purged_scope === 'filtered') ? 'filtered logs' : 'all logs';
                ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(sprintf('Purge completed: %d %s deleted.', max(0, $purged_total), $scope_label)); ?></p></div>
                <?php
            }
            if ($filter_presets_enabled) {
                $preset_status = sanitize_key((string) filter_input(INPUT_GET, 'waal_preset_status', FILTER_UNSAFE_RAW));
                if ($preset_status === 'saved') {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(waal_t('Filter preset saved.')) . '</p></div>';
                } elseif ($preset_status === 'deleted') {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(waal_t('Filter preset deleted.')) . '</p></div>';
                } elseif ($preset_status === 'empty') {
                    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html(waal_t('Enter a preset name before saving.')) . '</p></div>';
                }
            }
            ?>
        </div>
        <div class="waal-page-header">
            <p><?php echo esc_html(waal_t('Monitor user activity and system changes in one view.')); ?></p>
        </div>

        <div class="waal-card">
            <form method="get" id="waal-log-filter-form">
                <input type="hidden" name="page" value="wp-activity-log" />
                <?php wp_nonce_field('waal_filter_logs', 'waal_nonce'); ?>

                <div class="waal-filter-grid">
                    <div class="waal-field">
                        <label for="waal-from"><?php echo esc_html(waal_t('From')); ?></label>
                        <input id="waal-from" type="date" name="from" value="<?php echo esc_attr($filters['from']); ?>">
                    </div>
                    <div class="waal-field">
                        <label for="waal-to"><?php echo esc_html(waal_t('To')); ?></label>
                        <input id="waal-to" type="date" name="to" value="<?php echo esc_attr($filters['to']); ?>">
                    </div>
                    <div class="waal-field">
                        <label for="waal-user"><?php echo esc_html(waal_t('User')); ?></label>
                        <select name="user" id="waal-user">
                            <option value="0"><?php echo esc_html(waal_t('All Users')); ?></option>
                            <?php foreach (waal_get_log_user_options() as $uid => $label): ?>
                                <option value="<?php echo esc_attr((string) $uid); ?>" <?php selected($filters['user'], (int) $uid); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="waal-field">
                        <label for="waal-event"><?php echo esc_html(waal_t('Event')); ?></label>
                        <select id="waal-event" name="log_event">
                            <option value=""><?php echo esc_html(waal_t('All')); ?></option>
                            <?php foreach (waal_get_available_events() as $event): ?>
                                <option value="<?php echo esc_attr($event); ?>" <?php selected($filters['event'], $event); ?>><?php echo esc_html(function_exists('waal_get_event_label') ? waal_get_event_label($event, '') : ucfirst($event)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="waal-field">
                        <label for="waal-action"><?php echo esc_html(waal_t('Action')); ?></label>
                        <select id="waal-action" name="log_action">
                            <option value=""><?php echo esc_html(waal_t('All')); ?></option>
                            <?php foreach (waal_get_available_actions() as $a): ?>
                                <option value="<?php echo esc_attr($a); ?>" <?php selected($filters['action'], $a); ?>><?php echo esc_html(waal_get_action_filter_label($a)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($filter_presets_enabled): ?>
                        <div class="waal-field">
                            <label for="waal-preset-trigger"><?php echo esc_html(waal_t('Saved Presets')); ?></label>
                            <div class="waal-preset-picker" data-waal-preset-picker>
                                <input type="hidden" name="waal_preset" value="<?php echo esc_attr($current_preset_key); ?>" data-waal-preset-input>
                                <button type="button" id="waal-preset-trigger" class="waal-preset-trigger" data-waal-preset-trigger aria-haspopup="true" aria-expanded="false">
                                    <span data-waal-preset-label>
                                        <?php
                                        echo esc_html(
                                            ($current_preset_key !== '' && isset($filter_presets[$current_preset_key]))
                                                ? (string) ($filter_presets[$current_preset_key]['label'] ?? $current_preset_key)
                                                : waal_t('Select preset')
                                        );
                                        ?>
                                    </span>
                                    <span class="waal-preset-caret" aria-hidden="true">▾</span>
                                </button>
                                <div class="waal-preset-menu" data-waal-preset-menu hidden>
                                    <button type="button" class="waal-preset-option<?php echo $current_preset_key === '' ? ' is-active' : ''; ?>" data-waal-preset-option="" data-waal-preset-label-text="<?php echo esc_attr(waal_t('Select preset')); ?>">
                                        <span><?php echo esc_html(waal_t('Select preset')); ?></span>
                                    </button>
                                    <?php foreach ($filter_presets as $preset_key => $preset): ?>
                                        <?php $preset_label = (string) ($preset['label'] ?? $preset_key); ?>
                                        <div class="waal-preset-item">
                                            <button type="button" class="waal-preset-option<?php echo $current_preset_key === (string) $preset_key ? ' is-active' : ''; ?>" data-waal-preset-option="<?php echo esc_attr((string) $preset_key); ?>" data-waal-preset-label-text="<?php echo esc_attr($preset_label); ?>">
                                                <span><?php echo esc_html($preset_label); ?></span>
                                            </button>
                                            <button type="button" class="waal-preset-item-delete" data-waal-preset-delete="<?php echo esc_attr((string) $preset_key); ?>" title="<?php echo esc_attr(waal_t('Delete Preset')); ?>" aria-label="<?php echo esc_attr(waal_t('Delete Preset')); ?>">×</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="waal-actions">
                    <button class="button button-primary"><?php echo esc_html(waal_t('Apply Filter')); ?></button>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wp-activity-log')); ?>"><?php echo esc_html(waal_t('Reset')); ?></a>
                    <?php if ($filter_presets_enabled): ?>
                        <button type="button" class="button button-secondary" id="waal-open-preset-modal"><?php echo esc_html(waal_t('Add Filter Preset')); ?></button>
                    <?php endif; ?>
                    <?php if (function_exists('waal_user_can_manage_audit_integrity') && waal_user_can_manage_audit_integrity()): ?>
                        <button type="button" class="button button-secondary button-link-delete" id="waal-open-purge-modal"><?php echo esc_html(waal_t('Purge Manual')); ?></button>
                    <?php endif; ?>
                </div>
            </form>
            <?php if ($filter_presets_enabled && $current_preset_key !== '' && isset($filter_presets[$current_preset_key])): ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="waal-delete-preset-form" hidden>
                    <input type="hidden" name="action" value="waal_delete_filter_preset">
                    <input type="hidden" name="waal_preset_key" value="<?php echo esc_attr($current_preset_key); ?>" data-waal-preset-delete-key>
                    <?php wp_nonce_field('waal_delete_filter_preset', 'waal_preset_delete_nonce'); ?>
                </form>
            <?php endif; ?>
            <?php if (function_exists('waal_user_can_manage_audit_integrity') && waal_user_can_manage_audit_integrity()): ?>
                <div id="waal-purge-modal" class="waal-modal" aria-hidden="true">
                    <div class="waal-modal-backdrop" data-waal-close-modal></div>
                    <div class="waal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="waal-purge-title">
                        <h3 id="waal-purge-title"><?php echo esc_html(waal_t('Confirm Manual Purge')); ?></h3>
                        <p>
                            <?php echo esc_html(sprintf(
                                'This will delete %d %s.',
                                max(0, (int) $purge_visible_count),
                                $purge_scope_is_filtered ? 'visible log entries matching the current filter' : 'visible log entries (all data)'
                            )); ?>
                        </p>
                        <p class="description"><?php echo esc_html(waal_t('This action cannot be undone.')); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="waal_manual_purge_logs">
                            <input type="hidden" name="from" value="<?php echo esc_attr($filters['from']); ?>">
                            <input type="hidden" name="to" value="<?php echo esc_attr($filters['to']); ?>">
                            <input type="hidden" name="user" value="<?php echo esc_attr((string) $filters['user']); ?>">
                            <input type="hidden" name="log_event" value="<?php echo esc_attr($filters['event']); ?>">
                            <input type="hidden" name="log_action" value="<?php echo esc_attr($filters['action']); ?>">
                            <input type="hidden" name="q" value="<?php echo esc_attr($filters['q']); ?>">
                            <?php wp_nonce_field('waal_manual_purge_logs', 'waal_purge_nonce'); ?>
                            <div class="waal-modal-actions">
                                <button type="button" class="button" data-waal-close-modal><?php echo esc_html(waal_t('Cancel')); ?></button>
                                <button type="submit" class="button button-primary button-link-delete"><?php echo esc_html(waal_t('Yes, Purge Now')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($filter_presets_enabled): ?>
                <div id="waal-save-preset-modal" class="waal-modal waal-modal--center" aria-hidden="true">
                    <div class="waal-modal-backdrop" data-waal-close-preset-modal></div>
                    <div class="waal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="waal-save-preset-title">
                        <h3 id="waal-save-preset-title"><?php echo esc_html(waal_t('Add Filter Preset')); ?></h3>
                        <p><?php echo esc_html(waal_t('Save the current filter as a reusable preset.')); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="waal-save-preset-form">
                            <input type="hidden" name="action" value="waal_save_filter_preset">
                            <input type="hidden" name="from" value="">
                            <input type="hidden" name="to" value="">
                            <input type="hidden" name="user" value="">
                            <input type="hidden" name="log_event" value="">
                            <input type="hidden" name="log_action" value="">
                            <input type="hidden" name="q" value="">
                            <?php wp_nonce_field('waal_save_filter_preset', 'waal_preset_nonce'); ?>
                            <div class="waal-field">
                                <label for="waal-preset-name-modal"><?php echo esc_html(waal_t('Preset name')); ?></label>
                                <input id="waal-preset-name-modal" type="text" name="waal_preset_name" value="" placeholder="<?php echo esc_attr(waal_t('Preset name')); ?>">
                            </div>
                            <div class="waal-modal-actions">
                                <button type="button" class="button" data-waal-close-preset-modal><?php echo esc_html(waal_t('Cancel')); ?></button>
                                <button type="submit" class="button button-primary"><?php echo esc_html(waal_t('Save Filter Preset')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="waal-main-grid">
            <div class="waal-card waal-table-card">
                <?php
                $per_page = $filters['per_page'];
                $per_page_options = [5, 10, 20, 50];
                if (!in_array($per_page, $per_page_options, true)) {
                    $per_page = 5;
                }
                ?>
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="waal-table-toolbar" id="waal-table-toolbar">
                    <input type="hidden" name="page" value="wp-activity-log" />
                    <input type="hidden" name="from" value="<?php echo esc_attr($filters['from']); ?>" />
                    <input type="hidden" name="to" value="<?php echo esc_attr($filters['to']); ?>" />
                    <input type="hidden" name="user" value="<?php echo esc_attr($filters['user']); ?>" />
                    <input type="hidden" name="log_event" value="<?php echo esc_attr($filters['event']); ?>" />
                    <input type="hidden" name="log_action" value="<?php echo esc_attr($filters['action']); ?>" />
                    <div class="waal-toolbar-left">
                        <label class="waal-toolbar-label" for="waal-per-page"><?php echo esc_html(waal_t('Show')); ?></label>
                        <select id="waal-per-page" name="per_page">
                            <option value="5" <?php selected($per_page, 5); ?>>5</option>
                            <option value="10" <?php selected($per_page, 10); ?>>10</option>
                            <option value="20" <?php selected($per_page, 20); ?>>20</option>
                            <option value="50" <?php selected($per_page, 50); ?>>50</option>
                        </select>
                        <span class="waal-toolbar-label"><?php echo esc_html(waal_t('data')); ?></span>
                    </div>
                    <div class="waal-toolbar-right">
                        <label class="waal-toolbar-label" for="waal-search"><?php echo esc_html(waal_t('Search:')); ?></label>
                        <input type="search" id="waal-search" name="q" value="<?php echo esc_attr($filters['q']); ?>" />
                    </div>
                </form>

                <?php $table->display(); ?>

                <div id="waal-log-detail-modal" class="waal-modal waal-modal--center" aria-hidden="true">
                    <div class="waal-modal-backdrop" data-waal-close-log-detail></div>
                    <div class="waal-modal-dialog waal-log-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="waal-log-detail-title">
                        <h3 id="waal-log-detail-title"><?php echo esc_html(waal_t('Log Detail')); ?></h3>
                        <dl class="waal-log-detail-grid">
                            <dt><?php echo esc_html(waal_t('No')); ?></dt><dd data-waal-detail-no>-</dd>
                            <dt><?php echo esc_html(waal_t('Severity')); ?></dt><dd data-waal-detail-severity>-</dd>
                            <dt><?php echo esc_html(waal_t('Name')); ?></dt><dd data-waal-detail-name>-</dd>
                            <dt><?php echo esc_html(waal_t('Role')); ?></dt><dd data-waal-detail-role>-</dd>
                            <dt><?php echo esc_html(waal_t('Event')); ?></dt><dd data-waal-detail-event>-</dd>
                            <dt><?php echo esc_html(waal_t('Action')); ?></dt><dd data-waal-detail-action>-</dd>
                            <dt><?php echo esc_html(waal_t('Content')); ?></dt><dd data-waal-detail-content>-</dd>
                            <dt><?php echo esc_html(waal_t('Device')); ?></dt><dd data-waal-detail-ua>-</dd>
                            <dt><?php echo esc_html(waal_t('Time')); ?></dt><dd data-waal-detail-time>-</dd>
                        </dl>
                        <div class="waal-log-detail-ip-block">
                            <p class="waal-log-detail-ip-title"><?php echo esc_html(waal_t('IP Information')); ?></p>
                            <p class="waal-log-detail-ip-value" data-waal-detail-ip>-</p>
                            <div class="waal-log-detail-ip-meta" data-waal-detail-ip-meta><?php echo esc_html(waal_t('Loading IP info...')); ?></div>
                        </div>
                        <?php if (function_exists('waal_user_can_manage_audit_integrity') && waal_user_can_manage_audit_integrity()): ?>
                            <div class="waal-log-detail-notes">
                                <p class="waal-log-detail-notes-title"><?php echo esc_html(waal_t('Incident Notes')); ?></p>
                                <div class="waal-log-detail-note-controls">
                                    <select data-waal-incident-status>
                                        <option value="open"><?php echo esc_html(waal_t('Open')); ?></option>
                                        <option value="investigating"><?php echo esc_html(waal_t('Investigating')); ?></option>
                                        <option value="resolved"><?php echo esc_html(waal_t('Resolved')); ?></option>
                                        <option value="false_positive"><?php echo esc_html(waal_t('False Positive')); ?></option>
                                    </select>
                                </div>
                                <textarea class="large-text" rows="4" data-waal-incident-note placeholder="<?php echo esc_attr(waal_t('Add internal note for this event...')); ?>"></textarea>
                                <p class="description" data-waal-incident-note-meta><?php echo esc_html(waal_t('Notes are stored locally for audit investigation.')); ?></p>
                                <div class="waal-log-detail-note-actions">
                                    <button type="button" class="button button-primary" data-waal-save-incident-note data-label-default="<?php echo esc_attr(waal_t('Save Note')); ?>"><?php echo esc_html(waal_t('Save Note')); ?></button>
                                    <button type="button" class="button" data-waal-close-log-detail><?php echo esc_html(waal_t('Close')); ?></button>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!(function_exists('waal_user_can_manage_audit_integrity') && waal_user_can_manage_audit_integrity())): ?>
                            <div class="waal-modal-actions">
                                <button type="button" class="button" data-waal-close-log-detail><?php echo esc_html(waal_t('Close')); ?></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php
}
