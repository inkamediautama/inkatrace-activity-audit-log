<?php
defined('ABSPATH') || exit;

function waal_get_upgrade_url() {
    $default = defined('WAAL_UPGRADE_URL') ? WAAL_UPGRADE_URL : 'https://inkamedia.id/inkatrace/';
    return apply_filters('waal_upgrade_url', $default);
}

function waal_get_identity_email() {
    $default = 'pt.inkamediautama@gmail.com';
    $email = apply_filters('waal_identity_email', $default);
    $email = sanitize_email((string) $email);
    return is_email($email) ? $email : $default;
}

function waal_user_can_manage_logs() {
    if (!is_user_logged_in()) {
        return false;
    }
    if (current_user_can('manage_options')) {
        return true;
    }

    $user = wp_get_current_user();
    return in_array('administrator', (array) $user->roles, true);
}

function waal_user_can_manage_audit_integrity() {
    if (!is_user_logged_in()) {
        return false;
    }
    if (current_user_can('manage_options')) {
        return true;
    }

    $user = wp_get_current_user();
    return in_array('administrator', (array) $user->roles, true);
}

function waal_user_can_view_logs() {
    if (!is_user_logged_in()) {
        return false;
    }
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;
    if (waal_user_can_manage_logs()) {
        return true;
    }

    $allow_editor_view = (int) get_option('waal_allow_editor_view', 1);
    if ($allow_editor_view && in_array('editor', $user_roles, true)) {
        return true;
    }

    return false;
}

function waal_filter_presets_enabled() {
    return (int) get_option('waal_filter_presets_enabled', 1) === 1;
}

function waal_sanitize_theme_color($color) {
    $color = trim((string) $color);
    if (!preg_match('/^#(?:[A-Fa-f0-9]{6})$/', $color)) {
        return '#2C6652';
    }
    return strtoupper($color);
}

function waal_get_theme_color() {
    return waal_sanitize_theme_color((string) get_option('waal_theme_color', '#2C6652'));
}

add_action('admin_post_waal_send_client_report', 'waal_handle_client_report');

function waal_handle_client_report() {
    if (!waal_user_can_manage_logs()) {
        wp_die('Access denied');
    }

    check_admin_referer('waal_send_client_report', 'waal_report_nonce');

    $subject = sanitize_text_field((string) (wp_unslash($_POST['waal_report_subject'] ?? '')));
    $message = sanitize_textarea_field((string) (wp_unslash($_POST['waal_report_message'] ?? '')));
    $sender_email = sanitize_email((string) (wp_unslash($_POST['waal_report_email'] ?? '')));
    $active_tab = sanitize_key((string) (wp_unslash($_POST['waal_active_tab'] ?? 'general')));
    if (!in_array($active_tab, ['general', 'style', 'notifications'], true)) {
        $active_tab = 'general';
    }

    $redirect_args = [
        'page' => 'wp-activity-log-settings',
        'tab' => $active_tab,
        'waal_report_status' => 'error',
    ];

    if ($subject === '' || $message === '') {
        $redirect_args['waal_report_reason'] = 'incomplete';
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    $to = waal_get_identity_email();
    $site_url = home_url('/');
    $plugin_version = defined('WAAL_VERSION') ? WAAL_VERSION : '-';
    $mail_subject = '[InkaTrace Free] ' . $subject;
    $lines = [
        'Site: ' . $site_url,
        'Plugin Version: ' . $plugin_version,
        'Reporter Email: ' . ($sender_email !== '' ? $sender_email : '-'),
        '',
        'Message:',
        $message,
    ];
    $mail_body = implode("\n", $lines);

    $headers = [];
    if ($sender_email !== '' && is_email($sender_email)) {
        $headers[] = 'Reply-To: ' . $sender_email;
    }

    $sent = wp_mail($to, $mail_subject, $mail_body, $headers);
    if ($sent) {
        $redirect_args['waal_report_status'] = 'sent';
    } else {
        $redirect_args['waal_report_reason'] = 'send';
    }

    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}

function waal_render_settings_page() {
    if (!waal_user_can_manage_logs()) {
        wp_die('Access denied');
    }

    $notice = '';
    $active_tab = sanitize_key((string) filter_input(INPUT_GET, 'tab', FILTER_UNSAFE_RAW));
    if (!in_array($active_tab, ['general', 'style', 'notifications'], true)) {
        $active_tab = 'general';
    }

    $report_status = sanitize_key((string) filter_input(INPUT_GET, 'waal_report_status', FILTER_UNSAFE_RAW));
    $report_reason = sanitize_key((string) filter_input(INPUT_GET, 'waal_report_reason', FILTER_UNSAFE_RAW));
    $help_open = in_array($report_status, ['sent', 'error'], true);
    if ($report_status === 'sent') {
        $notice .= '<div class="updated notice"><p>Thank you. Your report has been sent.</p></div>';
    } elseif ($report_status === 'error') {
        $reason_text = 'Failed to send report. Please try again.';
        if ($report_reason === 'incomplete') {
            $reason_text = 'Please fill in Subject and Message before submitting.';
        }
        $notice .= '<div class="notice notice-error"><p>' . esc_html($reason_text) . '</p></div>';
    }

    $request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : 'GET';
    if ($request_method === 'POST' && $active_tab === 'general') {
        check_admin_referer('waal_save_settings', 'waal_nonce');

        if (waal_user_can_manage_audit_integrity()) {
            $allow_editor_view = filter_input(INPUT_POST, 'waal_allow_editor_view', FILTER_UNSAFE_RAW);
            $purge_enabled = filter_input(INPUT_POST, 'waal_purge_enabled', FILTER_UNSAFE_RAW);
            $retention_post = filter_input(INPUT_POST, 'waal_retention_days', FILTER_UNSAFE_RAW);
            $ip_geo_lookup_enabled = filter_input(INPUT_POST, 'waal_ip_geo_lookup_enabled', FILTER_UNSAFE_RAW);
            $ip_display_mode_post = filter_input(INPUT_POST, 'waal_ip_display_mode', FILTER_UNSAFE_RAW);
            $excluded_events_post = filter_input(INPUT_POST, 'waal_excluded_events', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            update_option('waal_allow_editor_view', $allow_editor_view !== null ? 1 : 0);
            update_option('waal_purge_enabled', $purge_enabled !== null ? 1 : 0);
            update_option('waal_ip_geo_lookup_enabled', $ip_geo_lookup_enabled !== null ? 1 : 0);
            update_option('waal_ip_display_mode', in_array(sanitize_key((string) $ip_display_mode_post), ['full', 'masked', 'role_based'], true) ? sanitize_key((string) $ip_display_mode_post) : 'full');

            $retention_raw = $retention_post !== null ? $retention_post : 90;
            update_option('waal_retention_days', max(7, (int) $retention_raw));
            $allowed_events = array_keys(function_exists('waal_get_excludable_events') ? waal_get_excludable_events() : []);
            $excluded_events = is_array($excluded_events_post) ? array_map('sanitize_key', $excluded_events_post) : [];
            $excluded_events = array_values(array_intersect($excluded_events, $allowed_events));
            update_option('waal_excluded_events', $excluded_events);

            $notice = '<div class="updated notice"><p>' . esc_html(waal_t('Save Settings')) . '.</p></div>';
        } else {
            $notice = '<div class="notice notice-warning"><p>' . esc_html(waal_t('Auto-purge and event exclusion can only be changed by an administrator.')) . '</p></div>';
        }
    } elseif ($request_method === 'POST' && $active_tab === 'style') {
        check_admin_referer('waal_save_settings', 'waal_nonce');
        if (!waal_user_can_manage_audit_integrity()) {
            $notice = '<div class="notice notice-warning"><p>' . esc_html(waal_t('Style settings can only be changed by an administrator.')) . '</p></div>';
        } else {
            $filter_presets_enabled = filter_input(INPUT_POST, 'waal_filter_presets_enabled', FILTER_UNSAFE_RAW);
            $theme_color_post = filter_input(INPUT_POST, 'waal_theme_color', FILTER_UNSAFE_RAW);
            update_option('waal_filter_presets_enabled', $filter_presets_enabled !== null ? 1 : 0);
            update_option('waal_theme_color', waal_sanitize_theme_color((string) $theme_color_post));
            $notice = '<div class="updated notice"><p>' . esc_html(waal_t('Save Settings')) . '.</p></div>';
        }
    } elseif ($request_method === 'POST' && $active_tab === 'notifications') {
        check_admin_referer('waal_save_settings', 'waal_nonce');
        if (!waal_user_can_manage_audit_integrity()) {
            $notice = '<div class="notice notice-warning"><p>' . esc_html(waal_t('Notifications settings can only be changed by an administrator.')) . '</p></div>';
        } else {
            $notify_enabled = filter_input(INPUT_POST, 'waal_notify_enabled', FILTER_UNSAFE_RAW);
            $notify_threat = filter_input(INPUT_POST, 'waal_notify_threat', FILTER_UNSAFE_RAW);
            $notify_critical = filter_input(INPUT_POST, 'waal_notify_critical_changes', FILTER_UNSAFE_RAW);
            $notify_email = sanitize_email((string) filter_input(INPUT_POST, 'waal_notify_email', FILTER_UNSAFE_RAW));
            if ($notify_email === '' || !is_email($notify_email)) {
                $notify_email = sanitize_email((string) get_option('admin_email', ''));
            }

            update_option('waal_notify_enabled', $notify_enabled !== null ? 1 : 0);
            update_option('waal_notify_threat', $notify_threat !== null ? 1 : 0);
            update_option('waal_notify_critical_changes', $notify_critical !== null ? 1 : 0);
            update_option('waal_notify_email', $notify_email);
            $notice = '<div class="updated notice"><p>' . esc_html(waal_t('Save Settings')) . '.</p></div>';
        }
    }

    $purge_enabled = (int) get_option('waal_purge_enabled', 0);
    $retention_days = (int) get_option('waal_retention_days', 90);
    $excluded_events = function_exists('waal_get_excluded_events') ? waal_get_excluded_events() : [];
    $excludable_events = function_exists('waal_get_excludable_events') ? waal_get_excludable_events() : [];
    $notify_enabled = (int) get_option('waal_notify_enabled', 0);
    $notify_threat = (int) get_option('waal_notify_threat', 1);
    $notify_critical = (int) get_option('waal_notify_critical_changes', 1);
    $notify_email = sanitize_email((string) get_option('waal_notify_email', (string) get_option('admin_email', 'admin@example.com')));
    $allow_editor_view = (int) get_option('waal_allow_editor_view', 1);
    $ip_geo_lookup_enabled = (int) get_option('waal_ip_geo_lookup_enabled', 0);
    $ip_display_mode = sanitize_key((string) get_option('waal_ip_display_mode', 'full'));
    $filter_presets_enabled = (int) get_option('waal_filter_presets_enabled', 1);
    $theme_color = waal_get_theme_color();
    $tabs = [
        'general' => waal_t('General'),
        'style' => waal_t('Style'),
        'notifications' => waal_t('Notifications'),
    ];
    ?>
    <div class="wrap waal-admin-wrap">
        <?php if (function_exists('waal_render_admin_header')) waal_render_admin_header('wp-activity-log-settings', waal_t('Settings Activity Log'), waal_t('Configure access, notifications, and log retention for the free edition.'), 'wp-activity-log-settings'); ?>
        <?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <div class="waal-settings-tabs">
            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab_label): ?>
                    <?php
                    $tab_url = add_query_arg([
                        'page' => 'wp-activity-log-settings',
                        'tab' => $tab_key,
                    ], admin_url('admin.php'));
                    ?>
                    <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </h2>
        </div>

        <div class="waal-settings-layout">
            <div class="waal-settings-main">
                <form method="post">
                    <?php wp_nonce_field('waal_save_settings', 'waal_nonce'); ?>

                    <?php if ($active_tab === 'general'): ?>
                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('Role Access')); ?></h2>
                            <p class="waal-section-desc"><?php echo esc_html(waal_t('Administrators always have full access. Optionally allow Editors to view the Activity Log in free edition.')); ?></p>
                            <label>
                                <input type="checkbox" name="waal_allow_editor_view" <?php checked($allow_editor_view, 1); ?> <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                <?php echo esc_html(waal_t('Allow Editors to view Activity Log')); ?>
                            </label>
                        </div>

                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('Log Retention (Optional)')); ?></h2>
                            <?php if (!waal_user_can_manage_audit_integrity()): ?>
                                <p class="waal-section-desc"><?php echo esc_html(waal_t('Auto-purge settings are administrator-only for audit integrity.')); ?></p>
                                <label class="waal-retention-toggle">
                                    <input type="checkbox" <?php checked($purge_enabled, 1); ?> disabled>
                                    <?php echo esc_html(waal_t('Enable Auto-Purge')); ?>
                                </label>
                                <p class="waal-retention-days">
                                    <?php echo esc_html(waal_t('Keep for')); ?>
                                    <input type="number" min="7" value="<?php echo esc_attr((string) $retention_days); ?>" style="width:90px;" disabled>
                                    <?php echo esc_html(waal_t('days')); ?>
                                </p>
                            <?php else: ?>
                                <p class="waal-section-desc"><?php echo esc_html(waal_t('When enabled, old logs are automatically deleted based on retention period.')); ?></p>
                                <label class="waal-retention-toggle">
                                    <input type="checkbox" name="waal_purge_enabled" <?php checked($purge_enabled, 1); ?>>
                                    <?php echo esc_html(waal_t('Enable Auto-Purge')); ?>
                                </label>
                                <p class="waal-retention-days">
                                    <?php echo esc_html(waal_t('Keep for')); ?>
                                    <input type="number" name="waal_retention_days" min="7" value="<?php echo esc_attr((string) $retention_days); ?>" style="width:90px;">
                                    <?php echo esc_html(waal_t('days')); ?>
                                </p>
                                <p class="description waal-setting-note">
                                    <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                    <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Administrator-only setting for audit integrity.')); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="waal-card">
                            <h3><?php echo esc_html(waal_t('Exclude Events')); ?></h3>
                            <p class="description waal-helper-copy waal-helper-copy--flush"><?php echo esc_html(waal_t('Choose event categories to exclude from future activity logs.')); ?></p>
                            <?php if (!waal_user_can_manage_audit_integrity()): ?>
                                <p class="description waal-setting-note">
                                    <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                    <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Event exclusion is administrator-only for audit integrity.')); ?></span>
                                </p>
                                <div class="waal-role-list">
                                    <?php foreach ($excludable_events as $event_key => $event_label): ?>
                                        <label>
                                            <input type="checkbox" disabled <?php checked(in_array((string) $event_key, $excluded_events, true)); ?>>
                                            <?php echo esc_html((string) $event_label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="waal-role-list">
                                    <?php foreach ($excludable_events as $event_key => $event_label): ?>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="waal_excluded_events[]"
                                                value="<?php echo esc_attr((string) $event_key); ?>"
                                                <?php checked(in_array((string) $event_key, $excluded_events, true)); ?>
                                            >
                                            <?php echo esc_html((string) $event_label); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description waal-setting-note">
                                    <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                    <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Administrator-only setting for audit integrity.')); ?></span>
                                </p>
                            <?php endif; ?>
                            <p class="description waal-setting-note">
                                <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Excluded events are ignored for new logs only. Existing logs are not removed.')); ?></span>
                            </p>
                        </div>

                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('IP Geolocation Consent')); ?></h2>
                            <?php if (!waal_user_can_manage_audit_integrity()): ?>
                                <p class="waal-section-desc"><?php echo esc_html(waal_t('External IP geolocation consent is administrator-only because it may send public IP addresses to third-party providers.')); ?></p>
                                <label class="waal-retention-toggle">
                                    <input type="checkbox" <?php checked($ip_geo_lookup_enabled, 1); ?> disabled>
                                    <?php echo esc_html(waal_t('Allow external IP geolocation lookup')); ?>
                                </label>
                            <?php else: ?>
                                <p class="waal-section-desc"><?php echo esc_html(waal_t('When enabled, public IP addresses can be sent to external geolocation providers to enrich IP details in the admin UI.')); ?></p>
                                <label class="waal-retention-toggle">
                                    <input type="checkbox" name="waal_ip_geo_lookup_enabled" <?php checked($ip_geo_lookup_enabled, 1); ?>>
                                    <?php echo esc_html(waal_t('Allow external IP geolocation lookup')); ?>
                                </label>
                            <?php endif; ?>
                            <p class="description waal-setting-note">
                                <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Disabled by default. When turned off, IP details stay local and no public IP is sent to geolocation providers.')); ?></span>
                            </p>
                        </div>

                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('IP Display & Exposure')); ?></h2>
                            <p class="waal-section-desc"><?php echo esc_html(waal_t('Choose how IP addresses appear in the admin UI. Exports and audit JSON remain full for investigation needs.')); ?></p>
                            <?php if (!waal_user_can_manage_audit_integrity()): ?>
                                <p class="description waal-setting-note">
                                    <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                    <span class="waal-setting-note-text"><?php echo esc_html(waal_t('IP display mode can only be changed by an administrator.')); ?></span>
                                </p>
                            <?php endif; ?>
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row"><label for="waal-ip-display-mode"><?php echo esc_html(waal_t('IP Display Mode')); ?></label></th>
                                    <td>
                                        <select id="waal-ip-display-mode" name="waal_ip_display_mode" <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                            <option value="full" <?php selected($ip_display_mode, 'full'); ?>><?php echo esc_html(waal_t('Full')); ?></option>
                                            <option value="masked" <?php selected($ip_display_mode, 'masked'); ?>><?php echo esc_html(waal_t('Masked')); ?></option>
                                            <option value="role_based" <?php selected($ip_display_mode, 'role_based'); ?>><?php echo esc_html(waal_t('Role-based')); ?></option>
                                        </select>
                                        <p class="description waal-helper-copy"><?php echo esc_html(waal_t('Full shows the exact IP. Masked shows partial IP only. Role-based shows full IP for administrators and masked IP for other roles.')); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                    <?php elseif ($active_tab === 'style'): ?>
                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('Interface Options')); ?></h2>
                            <p class="waal-section-desc"><?php echo esc_html(waal_t('Enable or simplify optional Activity Log tools based on your workflow.')); ?></p>
                            <label>
                                <input type="checkbox" name="waal_filter_presets_enabled" <?php checked($filter_presets_enabled, 1); ?> <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                <?php echo esc_html(waal_t('Show Filter Presets in Activity Log')); ?>
                            </label>
                            <p class="description waal-setting-note">
                                <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Turn this off to keep the Activity Log filter area simpler for users who do not need saved presets.')); ?></span>
                            </p>
                        </div>

                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('Theme Color')); ?></h2>
                            <p class="waal-section-desc"><?php echo esc_html(waal_t('Choose one primary color and the plugin will generate matching button and CTA gradients automatically.')); ?></p>
                            <div class="waal-help-field">
                                <label for="waal-theme-color"><?php echo esc_html(waal_t('Primary Theme Color')); ?></label>
                                <div class="waal-theme-color-row">
                                    <input type="color" id="waal-theme-color" value="<?php echo esc_attr($theme_color); ?>" <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                    <input type="text" id="waal-theme-color-hex" name="waal_theme_color" value="<?php echo esc_attr($theme_color); ?>" maxlength="7" spellcheck="false" placeholder="#2C6652" <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                </div>
                            </div>
                            <p class="description waal-setting-note">
                                <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Buttons, active tabs, CTA accents, and focus highlights will adapt automatically from this color.')); ?></span>
                            </p>
                        </div>

                    <?php elseif ($active_tab === 'notifications'): ?>
                        <div class="waal-card">
                            <h2><?php echo esc_html(waal_t('Email Notifications')); ?></h2>
                            <p class="waal-section-desc"><?php echo esc_html(waal_t('Configure alert emails for important security and system events.')); ?></p>
                            <?php if (!waal_user_can_manage_audit_integrity()): ?>
                                <p class="description waal-setting-note">
                                    <span class="waal-setting-note-badge"><?php echo esc_html(waal_t('Note')); ?></span>
                                    <span class="waal-setting-note-text"><?php echo esc_html(waal_t('Notifications settings can only be changed by an administrator.')); ?></span>
                                </p>
                            <?php endif; ?>
                            <table class="form-table" role="presentation">
                                <tr>
                                    <th scope="row">Enable Notifications</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="waal_notify_enabled" <?php checked($notify_enabled, 1); ?> <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                            Enable email notifications
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="waal-notify-email">Notification Email</label></th>
                                    <td>
                                        <input type="email" name="waal_notify_email" id="waal-notify-email" class="regular-text" value="<?php echo esc_attr($notify_email); ?>" <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                        <p class="description waal-helper-copy"><?php echo esc_html(waal_t('Threat and critical change alerts are sent to this address.')); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Notify on Threat</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="waal_notify_threat" <?php checked($notify_threat, 1); ?> <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                            Send immediate alert for suspicious login activity
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Critical Site Changes</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="waal_notify_critical_changes" <?php checked($notify_critical, 1); ?> <?php disabled(!waal_user_can_manage_audit_integrity()); ?>>
                                            Send immediate alert for plugin/theme/core critical changes
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if ($active_tab === 'general' || $active_tab === 'style' || $active_tab === 'notifications'): ?>
                        <p><button class="button button-primary"><?php echo esc_html(waal_t('Save Settings')); ?></button></p>
                    <?php endif; ?>
                </form>
            </div>

            <aside class="waal-settings-side">
                <div class="waal-card waal-help-card">
                    <h2><?php echo esc_html(waal_t('Need Help?')); ?></h2>
                    <p class="waal-section-desc"><?php echo esc_html(waal_t('Send a bug report, feature request, or feedback directly from this page.')); ?></p>
                    <details class="waal-help-details" <?php echo $help_open ? 'open' : ''; ?>>
                        <summary class="button button-secondary"><?php echo esc_html(waal_t('Open Report Form')); ?></summary>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="waal-help-form">
                            <input type="hidden" name="action" value="waal_send_client_report">
                            <input type="hidden" name="waal_active_tab" value="<?php echo esc_attr($active_tab); ?>">
                            <?php wp_nonce_field('waal_send_client_report', 'waal_report_nonce'); ?>

                            <p class="waal-help-field">
                                <label for="waal-report-subject">Subject</label>
                                <input type="text" id="waal-report-subject" name="waal_report_subject" required>
                            </p>
                            <p class="waal-help-field">
                                <label for="waal-report-message">Message</label>
                                <textarea id="waal-report-message" name="waal_report_message" rows="5" required></textarea>
                            </p>
                            <p class="waal-help-field">
                                <label for="waal-report-email">Email (optional)</label>
                                <input type="email" id="waal-report-email" name="waal_report_email" placeholder="">
                            </p>
                            <p class="waal-help-actions"><button type="submit" class="button button-primary">Send Report</button></p>
                        </form>
                    </details>
                </div>
            </aside>
        </div>
    </div>
    <?php
}
