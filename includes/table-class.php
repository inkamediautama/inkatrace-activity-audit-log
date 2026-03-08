<?php
defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WAAL_Table extends WP_List_Table {
    function display_tablenav($which) {
        if ($which === 'top') {
            return;
        }
        parent::display_tablenav($which);
    }

    function get_columns() {
        return ['no'=>waal_t('No'),'severity'=>waal_t('Severity'),'name'=>waal_t('Name'),'role'=>waal_t('Role'),'event'=>waal_t('Event'),'action'=>waal_t('Action'),'title'=>waal_t('Content'),'ip'=>waal_t('IP'),'ua'=>waal_t('Device'),'time'=>waal_t('Time'),'option'=>waal_t('Option')];
    }

    private function get_wp_user($user_id) {
        static $cache = [];
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return null;
        }
        if (!array_key_exists($user_id, $cache)) {
            $cache[$user_id] = get_userdata($user_id) ?: null;
        }
        return $cache[$user_id];
    }

    private function get_human_name($user_id, $user = null) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return '';
        }
        if (!$user || !($user instanceof WP_User)) {
            $user = $this->get_wp_user($user_id);
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

    private function get_severity_payload($item) {
        $action = sanitize_key((string) ($item->action ?? ''));
        $user_id = (int) ($item->user_id ?? 0);

        if ($action === 'bruteforce') {
            return ['label' => waal_t('Security Alert'), 'class' => 'danger'];
        }
        if ($action === 'login_failed') {
            return ['label' => waal_t('Warning Alert'), 'class' => 'warning'];
        }

        $threat_actions = ['password_reset_failed', 'permission_denied', 'blocked_request'];
        if (in_array($action, $threat_actions, true) || strpos($action, 'failed') !== false) {
            return ['label' => waal_t('Threat'), 'class' => 'warning'];
        }

        if ($user_id > 0 || $user_id === 0) {
            return ['label' => waal_t('Safe'), 'class' => 'safe'];
        }

        return ['label' => waal_t('Safe'), 'class' => 'safe'];
    }

    private function render_severity_badge($item) {
        $severity = $this->get_severity_payload($item);
        $class = 'waal-severity waal-severity--' . $severity['class'];
        return '<span class="' . esc_attr($class) . '"><span class="waal-severity-dot" aria-hidden="true"></span>' . esc_html($severity['label']) . '</span>';
    }

    private function render_time_cell($raw_datetime) {
        $raw_datetime = trim((string) $raw_datetime);
        if ($raw_datetime === '') {
            return '-';
        }

        $event_ts = strtotime($raw_datetime);
        if (!$event_ts) {
            return esc_html($raw_datetime);
        }

        $now_ts = current_time('timestamp');
        $past_diff = function_exists('waal_human_time_diff') ? waal_human_time_diff($event_ts, $now_ts) : human_time_diff($event_ts, $now_ts);
        $future_diff = function_exists('waal_human_time_diff') ? waal_human_time_diff($now_ts, $event_ts) : human_time_diff($now_ts, $event_ts);
        $relative = ($event_ts <= $now_ts)
            ? $past_diff . ' ' . waal_t('ago')
            : waal_t('in') . ' ' . $future_diff;
        $absolute = wp_date('Y-m-d H:i:s', $event_ts);

        return '<span class="waal-time-relative">' . esc_html($relative) . '</span><br><span class="waal-time-absolute">' . esc_html($absolute) . '</span>';
    }

    private function get_username($item, $user = null) {
        $user_id = (int) ($item->user_id ?? 0);
        $action = sanitize_key((string) ($item->action ?? ''));
        $object_title = (string) ($item->object_title ?? '');
        if (!$user || !($user instanceof WP_User)) {
            $user = $this->get_wp_user($user_id);
        }
        if ($user && !empty($user->user_login)) {
            return (string) $user->user_login;
        }
        if ($user_id <= 0 && $action === 'login_failed') {
            return $object_title !== '' ? $object_title : '-';
        }
        return '-';
    }

    private function get_name_initials($name) {
        $name = trim((string) $name);
        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/', $name);
        $parts = array_values(array_filter(array_map('trim', (array) $parts)));
        if (empty($parts)) {
            return strtoupper(substr($name, 0, 1));
        }

        $first = strtoupper(substr((string) $parts[0], 0, 1));
        if (count($parts) === 1) {
            return $first;
        }
        $last = strtoupper(substr((string) $parts[count($parts) - 1], 0, 1));
        return $first . $last;
    }

    private function render_name_avatar($user_id, $name, $user = null) {
        $user_id = (int) $user_id;
        $name = (string) $name;
        if (!$user || !($user instanceof WP_User)) {
            $user = $this->get_wp_user($user_id);
        }

        if ($user_id > 0 && $user) {
            return get_avatar($user_id, 24, '', $name, [
                'class' => 'waal-name-avatar-img',
                'force_display' => true,
            ]);
        }

        $initials = $this->get_name_initials($name);
        return '<span class="waal-name-avatar-fallback" aria-hidden="true">' . esc_html($initials) . '</span>';
    }

    function prepare_items() {
        $nonce = isset($_GET['waal_nonce']) ? sanitize_text_field(wp_unslash($_GET['waal_nonce'])) : '';
        if ($nonce !== '' && !wp_verify_nonce($nonce, 'waal_filter_logs')) {
            wp_die('Security token is invalid');
        }

        $allowed_per_page = [5, 10, 20, 50];
        $filters = function_exists('waal_get_request_filters')
            ? waal_get_request_filters()
            : ['per_page' => 5];
        $per = (int) ($filters['per_page'] ?? 5);
        if (!in_array($per, $allowed_per_page, true)) {
            $per = 5;
        }

        $paged_raw = (int) filter_input(INPUT_GET, 'paged', FILTER_VALIDATE_INT);
        $p = max(1, (int) $paged_raw);

        $total = function_exists('waal_count_filtered_logs')
            ? (int) waal_count_filtered_logs()
            : 0;
        $total_pages = max(1, (int) ceil($total / $per));
        if ($p > $total_pages) {
            $p = 1;
        }
        $_GET['paged'] = $p;
        $_REQUEST['paged'] = $p;
        $off = ($p - 1) * $per;

        $this->items = function_exists('waal_get_filtered_logs')
            ? waal_get_filtered_logs($per, $off)
            : [];
        $row_no = $off + 1;
        foreach ($this->items as $item) {
            $item->_row_no = $row_no;
            $row_no++;
        }

        $this->set_pagination_args(['total_items'=>$total,'per_page'=>$per]);
        $this->_column_headers=[$this->get_columns(),[],[]];
    }

    function no_items() {
        echo esc_html(waal_t('No matching log data found.'));
    }

    function column_default($i,$c){
        switch($c){
            case 'no':
                return esc_html((string) ((int) ($i->_row_no ?? 0)));
            case 'severity':
                return $this->render_severity_badge($i);
            case 'name':
                $user_id = (int) ($i->user_id ?? 0);
                $action = sanitize_key((string) ($i->action ?? ''));
                $object_title = (string) ($i->object_title ?? '');
                $user = $this->get_wp_user($user_id);
                $name = '';
                if ($user) {
                    $name = $this->get_human_name($user_id, $user);
                } elseif (function_exists('waal_get_actor_label')) {
                    $name = waal_get_actor_label($user_id, '', $action, $object_title);
                } elseif ($user_id <= 0 && $action === 'login_failed') {
                    $name = waal_t('Threat');
                } else {
                    $name = $user_id > 0 ? 'User #' . $user_id : 'System/Server';
                }

                $username = $this->get_username($i, $user);
                $name_html = '<span class="waal-name-main">' . esc_html($name) . '</span>';
                if ($username !== '' && $username !== '-' && strtolower($username) !== strtolower((string) $name)) {
                    $name_html .= '<span class="waal-name-sub">@' . esc_html($username) . '</span>';
                }
                $avatar_html = $this->render_name_avatar($user_id, $name, $user);
                return '<span class="waal-name-with-avatar">' . $avatar_html . '<span class="waal-name-stack">' . $name_html . '</span></span>';
            case 'role': return esc_html($i->user_role?:'-');
            case 'event':
                if (function_exists('waal_get_event_label')) {
                    return esc_html(waal_get_event_label($i->object_type ?? '', $i->action ?? ''));
                }
                return esc_html($i->object_type ?: '-');
            case 'action':
                if (function_exists('waal_get_action_verb') && function_exists('waal_get_action_verb_label')) {
                    return esc_html(waal_get_action_verb_label(waal_get_action_verb($i->action ?? '')));
                }
                return esc_html($i->action ?: '-');
            case 'title':
                if (function_exists('waal_get_content_label')) {
                    return esc_html(waal_get_content_label($i->action, $i->object_title ?? '', $i->object_type ?? ''));
                }
                return esc_html($i->object_title?:'-');
            case 'ip':
                $raw_ip = sanitize_text_field((string) ($i->ip_address ?? ''));
                if ($raw_ip === '') {
                    return '-';
                }
                $display_ip = function_exists('waal_mask_ip_address')
                    ? waal_mask_ip_address($raw_ip)
                    : $raw_ip;
                return '<span class="waal-ip-badge" data-ip="' . esc_attr($raw_ip) . '">' . esc_html($display_ip) . '</span>';
            case 'ua': return esc_html($i->user_agent?:'-');
            case 'time':
                return $this->render_time_cell($i->created_at ?? '');
            case 'option':
                $user_id = (int) ($i->user_id ?? 0);
                $action = sanitize_key((string) ($i->action ?? ''));
                $object_title = (string) ($i->object_title ?? '');
                $user = $this->get_wp_user($user_id);
                if ($user) {
                    $display_name = $this->get_human_name($user_id, $user);
                } elseif (function_exists('waal_get_actor_label')) {
                    $display_name = waal_get_actor_label($user_id, '', $action, $object_title);
                } elseif ($user_id <= 0 && $action === 'login_failed') {
                    $display_name = waal_t('Threat');
                } else {
                    $display_name = $user_id > 0 ? 'User #' . $user_id : 'System/Server';
                }
                $username = $this->get_username($i, $user);
                $name_for_detail = $display_name;
                if ($username !== '' && $username !== '-' && strtolower($username) !== strtolower((string) $display_name)) {
                    $name_for_detail .= ' (@' . $username . ')';
                }

                $raw_ip = sanitize_text_field((string) ($i->ip_address ?? ''));
                $display_ip = $raw_ip !== ''
                    ? (function_exists('waal_mask_ip_address') ? waal_mask_ip_address($raw_ip) : $raw_ip)
                    : '-';
                $payload = [
                    'id' => (int) ($i->id ?? 0),
                    'no' => (int) ($i->_row_no ?? 0),
                    'severity' => $this->get_severity_payload($i)['label'],
                    'name' => $name_for_detail,
                    'role' => (string) ($i->user_role ?? '-'),
                    'event' => function_exists('waal_get_event_label') ? (string) waal_get_event_label($i->object_type ?? '', $i->action ?? '') : (string) ($i->object_type ?? '-'),
                    'action' => function_exists('waal_get_action_verb') && function_exists('waal_get_action_verb_label')
                        ? (string) waal_get_action_verb_label(waal_get_action_verb($i->action ?? ''))
                        : (string) ($i->action ?? '-'),
                    'content' => function_exists('waal_get_content_label')
                        ? (string) waal_get_content_label($i->action, $i->object_title ?? '', $i->object_type ?? '')
                        : (string) ($i->object_title ?? '-'),
                    'ip' => $display_ip,
                    'raw_ip' => $raw_ip,
                    'ua' => (string) ($i->user_agent ?? '-'),
                    'time' => trim((string) ($i->created_at ?? '-')),
                    'incident_note' => (string) ($i->incident_note ?? ''),
                    'incident_status' => sanitize_key((string) ($i->incident_status ?? 'open')),
                    'incident_updated_at' => trim((string) ($i->incident_updated_at ?? '')),
                ];

                return '<button type="button" class="button button-small waal-open-log-detail" data-waal-detail="' . esc_attr(wp_json_encode($payload)) . '">' . esc_html(waal_t('Detail')) . '</button>';
        }
        return '';
    }
}
