<?php
defined('ABSPATH') || exit;

function waal_get_supported_languages() {
    return [
        'en_US' => 'English',
        'id_ID' => 'Bahasa Indonesia',
    ];
}

function waal_get_ui_language() {
    $saved = get_user_meta(get_current_user_id(), 'waal_ui_language', true);
    $saved = sanitize_text_field((string) $saved);
    $supported = waal_get_supported_languages();
    if (isset($supported[$saved])) {
        return $saved;
    }

    $locale = function_exists('get_user_locale') ? (string) get_user_locale() : (string) get_locale();
    return strpos($locale, 'id_') === 0 ? 'id_ID' : 'en_US';
}

function waal_localize_human_time_fragment($diff) {
    $diff = trim((string) $diff);
    if ($diff === '' || waal_get_ui_language() !== 'id_ID') {
        return $diff;
    }

    $unit_map = [
        'second' => 'detik',
        'seconds' => 'detik',
        'sec' => 'detik',
        'secs' => 'detik',
        'minute' => 'menit',
        'minutes' => 'menit',
        'min' => 'menit',
        'mins' => 'menit',
        'hour' => 'jam',
        'hours' => 'jam',
        'day' => 'hari',
        'days' => 'hari',
        'week' => 'minggu',
        'weeks' => 'minggu',
        'month' => 'bulan',
        'months' => 'bulan',
        'year' => 'tahun',
        'years' => 'tahun',
    ];

    return (string) preg_replace_callback('/\b[a-zA-Z]+\b/', static function ($matches) use ($unit_map) {
        $word = strtolower((string) ($matches[0] ?? ''));
        return $unit_map[$word] ?? $matches[0];
    }, $diff);
}

function waal_human_time_diff($from, $to = null) {
    $from = (int) $from;
    $to = $to === null ? current_time('timestamp') : (int) $to;
    return waal_localize_human_time_fragment(human_time_diff($from, $to));
}

function waal_t($text) {
    $text = (string) $text;
    if ($text === '') {
        return '';
    }

    $lang = waal_get_ui_language();
    if ($lang !== 'id_ID') {
        return $text;
    }

    static $id_map = [
        'Activity Log' => 'Log Aktivitas',
        'Monitor user activity and system changes in one view.' => 'Pantau aktivitas pengguna dan perubahan sistem dalam satu tampilan.',
        'From' => 'Dari',
        'To' => 'Sampai',
        'User' => 'Pengguna',
        'All Users' => 'Semua Pengguna',
        'All Events' => 'Semua Event',
        'Event' => 'Event',
        'Action' => 'Aksi',
        'Settings' => 'Pengaturan',
        'All Activities' => 'Semua Aktivitas',
        'All' => 'Semua',
        'Apply Filter' => 'Terapkan Filter',
        'Reset' => 'Reset',
        'Purge' => 'Bersihkan',
        'Purge Manual' => 'Bersihkan Manual',
        'Confirm Manual Purge' => 'Konfirmasi Pembersihan Manual',
        'This action cannot be undone.' => 'Aksi ini tidak dapat dibatalkan.',
        'Cancel' => 'Batal',
        'Yes, Purge Now' => 'Ya, Bersihkan Sekarang',
        'Export CSV' => 'Ekspor CSV',
        'Export Excel' => 'Ekspor Excel',
        'Export PDF' => 'Ekspor PDF',
        'Search:' => 'Cari:',
        'Show' => 'Tampilkan',
        'data' => 'data',
        'No data yet.' => 'Belum ada data.',
        'No matching log data found.' => 'Tidak ada data log yang cocok.',
        'Severity' => 'Severity',
        'Name' => 'Nama',
        'Username' => 'Username',
        'Role' => 'Role',
        'Content' => 'Konten',
        'IP' => 'IP',
        'Device' => 'Perangkat',
        'Time' => 'Waktu',
        'Safe' => 'Aman',
        'Security Alert' => 'Peringatan Keamanan',
        'Warning Alert' => 'Peringatan',
        'Threat' => 'Ancaman',
        'System/Server' => 'Sistem/Server',
        'Log Database Size' => 'Ukuran Database Log',
        'Created at' => 'Dibuat pada',
        'Publish' => 'Publikasikan',
        'Edit' => 'Ubah',
        'Delete' => 'Hapus',
        'Activate' => 'Aktifkan',
        'Deactivate' => 'Nonaktifkan',
        'Update' => 'Perbarui',
        'Install' => 'Pasang',
        'Switch' => 'Ganti',
        'Login' => 'Login',
        'Logout' => 'Logout',
        'Language' => 'Bahasa',
        "What's in Pro?" => 'Apa di Pro?',
        'Upgrade to Pro' => 'Upgrade ke Pro',
        'Premium Feature' => 'Fitur Premium',
        'Security & Audit Integrity' => 'Keamanan & Integritas Audit',
        'Compliance & Investigation' => 'Kepatuhan & Investigasi',
        'Retention & Recovery' => 'Retensi & Pemulihan',
        'Unlock advanced audit workflow built for compliance, investigation, and long-term retention.' => 'Buka alur audit lanjutan untuk kepatuhan, investigasi, dan retensi jangka panjang.',
        'Role-based permission matrix per module (administrator guardrails for integrity actions).' => 'Matriks permission berbasis per modul (dengan guardrails administrator untuk aksi integritas).',
        'Detail JSON view with copy action for advanced incident handling.' => 'Tampilan JSON detail dengan aksi salin untuk penanganan insiden lanjutan.',
        'Critical change alerts for plugin/theme/core updates.' => 'Alert perubahan kritis untuk update plugin/tema/core.',
        'Compliance Reports with summary metrics and CSV export.' => 'Laporan Kepatuhan dengan metrik ringkasan dan ekspor CSV.',
        'Forensic Timeline with filters by date, user, IP, and result limit.' => 'Timeline Forensik dengan filter tanggal, pengguna, IP, dan batas hasil.',
        'Enhanced event context for faster audit and client reporting.' => 'Konteks event lebih kaya untuk audit dan pelaporan klien yang lebih cepat.',
        'Long-term local archive before purge with metadata snapshots.' => 'Arsip lokal jangka panjang sebelum purge dengan snapshot metadata.',
        'Archive restore flow with status tracking and duplicate-skip safety.' => 'Alur restore arsip dengan pelacakan status dan proteksi skip duplikat.',
        'Administrator-only purge/archive/restore controls to protect audit evidence.' => 'Kontrol purge/arsip/restore khusus administrator untuk melindungi bukti audit.',
        'Upgrade once to unlock advanced compliance, forensic timeline, and archive restore workflows.' => 'Upgrade sekali untuk membuka alur kepatuhan, timeline forensik, dan restore arsip lanjutan.',
        'Unlock the Full Power of InkaTrace Pro' => 'Buka Kekuatan Penuh InkaTrace Pro',
        'Take your WordPress activity security and monitoring to the next level with advanced compliance, investigation, and retention workflows.' => 'Bawa keamanan dan monitoring aktivitas WordPress ke level berikutnya dengan alur kepatuhan, investigasi, dan retensi lanjutan.',
        'See Features' => 'Lihat Fitur',
        'Premium Features' => 'Fitur Premium',
        'Permissions Matrix & Audit Guardrails' => 'Matriks Permission & Guardrails Audit',
        'Control access per module while keeping integrity-critical actions administrator-only.' => 'Atur akses per modul sambil menjaga aksi kritis integritas tetap khusus administrator.',
        'Per-module permission matrix' => 'Matriks permission per modul',
        'Admin-only purge/archive/restore' => 'Purge/arsip/restore khusus admin',
        'Safer audit evidence handling' => 'Penanganan bukti audit lebih aman',
        'Generate compliance-friendly summaries and export CSV for audits or client handover.' => 'Buat ringkasan ramah kepatuhan dan ekspor CSV untuk audit atau handover klien.',
        'Window, totals, threats, active users' => 'Periode, total, ancaman, pengguna aktif',
        'CSV compliance export' => 'Ekspor CSV kepatuhan',
        'Clear summary layout' => 'Layout ringkasan yang jelas',
        'Investigate chronology quickly with focused filters and recent security-relevant actions.' => 'Investigasi kronologi lebih cepat dengan filter terfokus dan aksi keamanan terbaru.',
        'Filter by date, user, IP, limit' => 'Filter tanggal, pengguna, IP, batas',
        'Incident-friendly timeline format' => 'Format timeline ramah insiden',
        'Faster investigation workflow' => 'Alur investigasi lebih cepat',
        'Archive logs before purge and restore archived entries safely when required.' => 'Arsipkan log sebelum purge dan pulihkan entri arsip dengan aman saat dibutuhkan.',
        'Archive metadata snapshots' => 'Snapshot metadata arsip',
        'Restore with duplicate-skip protection' => 'Restore dengan proteksi skip duplikat',
        'Status tracking for archive records' => 'Pelacakan status untuk arsip',
        'Import downloaded JSON archives' => 'Impor arsip JSON hasil unduhan',
        'Branded PDF Exports' => 'Ekspor PDF Bermerek',
        'Add company identity to PDF exports for cleaner handover to clients, auditors, and internal stakeholders.' => 'Tambahkan identitas perusahaan ke ekspor PDF agar handover ke klien, auditor, dan pihak internal lebih rapi.',
        'Company logo in PDF header' => 'Logo perusahaan di header PDF',
        'Company name and address' => 'Nama dan alamat perusahaan',
        'Branding managed from Style settings' => 'Branding dikelola dari pengaturan Gaya',
        'Advanced Notifications' => 'Notifikasi Lanjutan',
        'Get notified on suspicious logins, daily summaries, and critical site changes.' => 'Dapatkan notifikasi login mencurigakan, ringkasan harian, dan perubahan situs kritis.',
        'Threat alert email' => 'Email alert ancaman',
        'Daily activity report' => 'Laporan aktivitas harian',
        'Critical plugin/theme/core alerts' => 'Alert kritis plugin/tema/core',
        'Detail JSON View' => 'Tampilan JSON Detail',
        'Open structured log detail and copy JSON for deeper technical analysis.' => 'Buka detail log terstruktur dan salin JSON untuk analisis teknis mendalam.',
        'Normalized JSON payload' => 'Payload JSON ternormalisasi',
        'Copy-ready output' => 'Output siap salin',
        'IP context in detail modal' => 'Konteks IP di modal detail',
        'Incident Notes' => 'Catatan Insiden',
        'Add internal note for this event...' => 'Tambahkan catatan internal untuk event ini...',
        'Notes are stored locally for audit investigation.' => 'Catatan disimpan secara lokal untuk investigasi audit.',
        'Save Note' => 'Simpan Catatan',
        'Saving...' => 'Menyimpan...',
        'Saved' => 'Tersimpan',
        'Failed to save incident note.' => 'Gagal menyimpan catatan insiden.',
        'Last updated' => 'Terakhir diperbarui',
        'Open' => 'Terbuka',
        'Saved Presets' => 'Preset Tersimpan',
        'Select preset' => 'Pilih preset',
        'Add Filter Preset' => 'Tambah Preset Filter',
        'Load Preset' => 'Muat Preset',
        'Preset name' => 'Nama preset',
        'Save Current Filter' => 'Simpan Filter Saat Ini',
        'Save Filter Preset' => 'Simpan Preset Filter',
        'Save the current filter as a reusable preset.' => 'Simpan filter saat ini sebagai preset yang dapat dipakai kembali.',
        'Delete Preset' => 'Hapus Preset',
        'Interface Options' => 'Opsi Antarmuka',
        'Style' => 'Gaya',
        'Theme Color' => 'Warna Tema',
        'Choose one primary color and the plugin will generate matching button and CTA gradients automatically.' => 'Pilih satu warna utama dan plugin akan membuat gradient tombol dan CTA yang serasi secara otomatis.',
        'Primary Theme Color' => 'Warna Tema Utama',
        'Buttons, active tabs, CTA accents, and focus highlights will adapt automatically from this color.' => 'Tombol, tab aktif, aksen CTA, dan highlight fokus akan menyesuaikan otomatis dari warna ini.',
        'Export Branding' => 'Branding Ekspor',
        'Add company logo, company name, and address headers to export files with Pro.' => 'Tambahkan logo perusahaan, nama perusahaan, dan header alamat ke file ekspor dengan Pro.',
        'Pro can add branded company headers to CSV, Excel, and PDF exports automatically.' => 'Pro dapat menambahkan header perusahaan bermerek ke ekspor CSV, Excel, dan PDF secara otomatis.',
        'Style settings can only be changed by an administrator.' => 'Pengaturan gaya hanya dapat diubah oleh administrator.',
        'Enable or simplify optional Activity Log tools based on your workflow.' => 'Aktifkan atau sederhanakan alat Activity Log opsional sesuai alur kerja Anda.',
        'Show Filter Presets in Activity Log' => 'Tampilkan Preset Filter di Activity Log',
        'Turn this off to keep the Activity Log filter area simpler for users who do not need saved presets.' => 'Matikan ini agar area filter Activity Log tetap lebih sederhana bagi pengguna yang tidak membutuhkan preset tersimpan.',
        'Filter preset saved.' => 'Preset filter berhasil disimpan.',
        'Filter preset deleted.' => 'Preset filter berhasil dihapus.',
        'Enter a preset name before saving.' => 'Masukkan nama preset sebelum menyimpan.',
        'Export Logs' => 'Ekspor Log',
        'Export filtered activity log data in CSV, Excel, or PDF with Pro.' => 'Ekspor data activity log yang sedang difilter ke CSV, Excel, atau PDF dengan Pro.',
        'Ready to Upgrade?' => 'Siap Upgrade?',
        'ago' => 'lalu',
        'in' => 'dalam',
        'Failed' => 'Gagal',
        'Blocked' => 'Diblokir',
        'Auth' => 'Otentikasi',
        'System' => 'Sistem',
        'Publish Page' => 'Publikasikan Halaman',
        'Publish Post' => 'Publikasikan Post',
        'Edit Page' => 'Ubah Halaman',
        'Edit Post' => 'Ubah Post',
        'Delete Page' => 'Hapus Halaman',
        'Delete Post' => 'Hapus Post',
        'User Logged In' => 'Pengguna Login',
        'Login Failed' => 'Login Gagal',
        'User Logged Out' => 'Pengguna Logout',
        'Brute Force Detected' => 'Brute Force Terdeteksi',
        'Customizer Changes Published' => 'Perubahan Kustomisasi Dipublikasikan',
        'Edit Content' => 'Ubah Konten',
        'Delete Content' => 'Hapus Konten',
        'Plugin Activated' => 'Plugin Diaktifkan',
        'Plugin Deactivated' => 'Plugin Dinonaktifkan',
        'Plugin Deleted' => 'Plugin Dihapus',
        'Plugin Updated' => 'Plugin Diperbarui',
        'Plugin Installed' => 'Plugin Dipasang',
        'Theme Switched' => 'Tema Diganti',
        'Theme Updated' => 'Tema Diperbarui',
        'Theme Installed' => 'Tema Dipasang',
        'Update WordPress Core' => 'Perbarui WordPress Core',
        'Install WordPress Core' => 'Pasang WordPress Core',
        'Activate plugin: %s' => 'Aktifkan plugin: %s',
        'Deactivate plugin: %s' => 'Nonaktifkan plugin: %s',
        'Delete plugin: %s' => 'Hapus plugin: %s',
        'Update plugin: %s' => 'Perbarui plugin: %s',
        'Install plugin: %s' => 'Pasang plugin: %s',
        'Publish customizer changes: %s' => 'Publikasikan perubahan kustomisasi: %s',
        'Publish %s: %s' => 'Publikasikan %s: %s',
        'Edit %s: %s' => 'Ubah %s: %s',
        'Delete %s: %s' => 'Hapus %s: %s',
        'Switch theme: %s' => 'Ganti tema: %s',
        'Update theme: %s' => 'Perbarui tema: %s',
        'Install theme: %s' => 'Pasang tema: %s',
        'Update WordPress core' => 'Perbarui WordPress core',
        'Login successful' => 'Login berhasil',
        'Login failed: %s' => 'Login gagal: %s',
        'Site design changes' => 'Perubahan desain situs',
        '%d setting(s)' => '%d pengaturan',
        'post' => 'post',
        'page' => 'halaman',
        'plugin' => 'plugin',
        'theme' => 'tema',
        'customizer changes' => 'perubahan kustomisasi',
        'content' => 'konten',
        'General' => 'Umum',
        'Notifications' => 'Notifikasi',
        'Permissions' => 'Permission',
        'Documentation' => 'Dokumentasi',
        'Open Documentation' => 'Buka Dokumentasi',
        'Complete usage guide for Activity Log features.' => 'Panduan penggunaan lengkap untuk fitur Activity Log.',
        'Need setup guidance? Open Documentation for a complete feature guide.' => 'Perlu panduan setup? Buka Dokumentasi untuk panduan fitur lengkap.',
        'Settings Activity Log' => 'Pengaturan Log Aktivitas',
        'Configure access, notifications, and log retention for the free edition.' => 'Atur akses, notifikasi, dan retensi log untuk edisi gratis.',
        'Role Access' => 'Akses Peran',
        'Administrators always have full access. Optionally allow Editors to view the Activity Log in free edition.' => 'Administrator selalu memiliki akses penuh. Anda dapat mengizinkan Editor untuk melihat Activity Log di edisi gratis.',
        'Allow Editors to view Activity Log' => 'Izinkan Editor melihat Activity Log',
        'Advanced role and module permissions are available in Pro.' => 'Permission peran dan modul lanjutan tersedia di Pro.',
        'Log Retention (Optional)' => 'Retensi Log (Opsional)',
        'When enabled, old logs are automatically deleted based on retention period.' => 'Saat diaktifkan, log lama akan dihapus otomatis berdasarkan periode retensi.',
        'Auto-purge settings are administrator-only for audit integrity.' => 'Pengaturan auto-purge hanya untuk administrator demi integritas audit.',
        'Enable Auto-Purge' => 'Aktifkan Auto-Purge',
        'Keep for' => 'Simpan selama',
        'days' => 'hari',
        'Exclude Events' => 'Kecualikan Event',
        'Choose event categories to exclude from future activity logs.' => 'Pilih kategori event yang dikecualikan dari log aktivitas berikutnya.',
        'Excluded events are ignored for new logs only. Existing logs are not removed.' => 'Event yang dikecualikan hanya diabaikan untuk log baru. Log lama tidak dihapus.',
        'Event exclusion is administrator-only for audit integrity.' => 'Pengecualian event khusus administrator demi integritas audit.',
        'Administrator-only setting for audit integrity.' => 'Pengaturan ini khusus administrator demi integritas audit.',
        'Auto-purge and event exclusion can only be changed by an administrator.' => 'Auto-purge dan pengecualian event hanya bisa diubah oleh administrator.',
        'Media' => 'Media',
        'Core' => 'Core',
        'Customizer' => 'Customizer',
        'Long-term Retention & Restore' => 'Retensi Jangka Panjang & Restore',
        'Archive logs locally before purge and restore archived data when needed.' => 'Arsipkan log secara lokal sebelum purge dan pulihkan data arsip saat dibutuhkan.',
        'Enable local archive before purge' => 'Aktifkan arsip lokal sebelum purge',
        'Available in Pro with archive metadata history and one-click restore.' => 'Tersedia di Pro dengan riwayat metadata arsip dan restore sekali klik.',
        'Email Notifications' => 'Notifikasi Email',
        'Need Help?' => 'Butuh Bantuan?',
        'Send a bug report, feature request, or feedback directly from this page.' => 'Kirim laporan bug, permintaan fitur, atau masukan langsung dari halaman ini.',
        'Open Report Form' => 'Buka Form Laporan',
        'Option' => 'Opsi',
        'Detail' => 'Detail',
        'Log Detail' => 'Detail Log',
        'Related Resource' => 'Resource Terkait',
        'No related resource available for this log.' => 'Tidak ada resource terkait yang tersedia untuk log ini.',
        'Edit Resource' => 'Edit Resource',
        'View Resource' => 'Lihat Resource',
        'Open File' => 'Buka File',
        'Edit User' => 'Edit Pengguna',
        'Edit Comment' => 'Edit Komentar',
        'Open Plugins' => 'Buka Plugins',
        'Open Themes' => 'Buka Themes',
        'IP Display & Exposure' => 'Tampilan & Eksposur IP',
        'IP Display Mode' => 'Mode Tampilan IP',
        'Full' => 'Penuh',
        'Masked' => 'Disamarkan',
        'Role-based' => 'Berdasarkan Role',
        'Choose how IP addresses appear in the admin UI. Exports and audit JSON remain full for investigation needs.' => 'Pilih bagaimana alamat IP ditampilkan di UI admin. Ekspor dan JSON audit tetap penuh untuk kebutuhan investigasi.',
        'Choose how IP addresses appear in the admin UI. Exports, webhooks, and audit JSON remain full for investigation needs.' => 'Pilih bagaimana alamat IP ditampilkan di UI admin. Ekspor, webhook, dan JSON audit tetap penuh untuk kebutuhan investigasi.',
        'IP display mode can only be changed by an administrator.' => 'Mode tampilan IP hanya bisa diubah oleh administrator.',
        'Full shows the exact IP. Masked shows partial IP only. Role-based shows full IP for administrators and masked IP for other roles.' => 'Penuh menampilkan IP lengkap. Disamarkan hanya menampilkan sebagian IP. Berdasarkan role menampilkan IP penuh untuk administrator dan IP tersamar untuk role lain.',
        'Close' => 'Tutup',
        'Save Settings' => 'Simpan Pengaturan',
        'Compliance Reports' => 'Laporan Kepatuhan',
        'Review period-based audit metrics from your activity log in the free edition.' => 'Tinjau metrik audit berbasis periode dari activity log Anda di edisi gratis.',
        'Generate a compliance-friendly summary with event totals, threat counts, and top activities.' => 'Buat ringkasan ramah kepatuhan dengan total event, jumlah ancaman, dan aktivitas teratas.',
        'Range' => 'Rentang',
        'Last 7 Days' => '7 Hari Terakhir',
        'Last 30 Days' => '30 Hari Terakhir',
        'Last 90 Days' => '90 Hari Terakhir',
        'Custom Range' => 'Rentang Kustom',
        'Refresh Report' => 'Segarkan Laporan',
        'Window:' => 'Periode:',
        'Total logs:' => 'Total log:',
        'Threat logs:' => 'Log ancaman:',
        'Active users:' => 'Pengguna aktif:',
        'Safe logs:' => 'Log aman:',
        'Warning logs:' => 'Log peringatan:',
        'Security alert logs:' => 'Log alert keamanan:',
        'Threat ratio:' => 'Rasio ancaman:',
        'Top risky IP:' => 'IP paling berisiko:',
        'No public threat IP detected.' => 'Belum ada IP ancaman publik terdeteksi.',
        'Top Activities' => 'Aktivitas Teratas',
        'No log activity found in the selected period.' => 'Tidak ada aktivitas log pada periode yang dipilih.',
        'Forensic Timeline' => 'Timeline Forensik',
        'Forensic Timeline is available in Pro for chronological investigation by user, IP, and recent security actions.' => 'Forensic Timeline tersedia di Pro untuk investigasi kronologis berdasarkan pengguna, IP, dan aksi keamanan terbaru.',
    ];

    return isset($id_map[$text]) ? $id_map[$text] : $text;
}

add_action('admin_init', function () {
    if (!is_admin()) {
        return;
    }
    $page = sanitize_key((string) filter_input(INPUT_GET, 'page', FILTER_UNSAFE_RAW));
    if (!in_array($page, ['wp-activity-log', 'wp-activity-log-settings', 'wp-activity-log-insights', 'wp-activity-log-docs', 'wp-activity-log-upgrade'], true)) {
        return;
    }

    $lang = sanitize_text_field((string) filter_input(INPUT_GET, 'waal_lang', FILTER_UNSAFE_RAW));
    if ($lang === '') {
        return;
    }

    $nonce = sanitize_text_field((string) filter_input(INPUT_GET, 'waal_lang_nonce', FILTER_UNSAFE_RAW));
    if (!wp_verify_nonce($nonce, 'waal_switch_lang')) {
        return;
    }

    $supported = waal_get_supported_languages();
    if (!isset($supported[$lang])) {
        return;
    }

    update_user_meta(get_current_user_id(), 'waal_ui_language', $lang);
});

function waal_get_plugin_name($plugin_file) {
    $plugin_file = (string) $plugin_file;
    if ($plugin_file === '') {
        return '-';
    }

    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();
    if (isset($plugins[$plugin_file]['Name']) && $plugins[$plugin_file]['Name'] !== '') {
        return $plugins[$plugin_file]['Name'];
    }

    $basename = basename($plugin_file, '.php');
    $basename = str_replace(['-', '_'], ' ', $basename);
    return ucwords($basename);
}

function waal_get_actor_label($user_id, $display_name = '', $action = '', $object_title = '') {
    $user_id = (int) $user_id;
    $display_name = trim((string) $display_name);
    $action = sanitize_key((string) $action);
    $object_title = trim((string) $object_title);

    if ($display_name !== '') {
        return $display_name;
    }

    if ($user_id > 0) {
        $user = get_userdata($user_id);
        if ($user && !empty($user->display_name)) {
            return (string) $user->display_name;
        }
        return 'User #' . $user_id;
    }

    if ($action === 'login_failed') {
        $matched_user = null;
        if ($object_title !== '') {
            $matched_user = get_user_by('login', $object_title);
            if (!$matched_user && is_email($object_title)) {
                $matched_user = get_user_by('email', $object_title);
            }
        }
        if ($matched_user && $matched_user instanceof WP_User) {
            return $matched_user->display_name ?: $matched_user->user_login;
        }
        return waal_t('Threat');
    }

    return waal_t('System/Server');
}

function waal_get_action_label($action, $object_type = '') {
    $action = sanitize_key($action);
    $object_type = sanitize_key($object_type);

    if ($action === 'publish' && $object_type === 'page') return waal_t('Publish Page');
    if ($action === 'publish' && $object_type === 'post') return waal_t('Publish Post');
    if ($action === 'update' && $object_type === 'page') return waal_t('Edit Page');
    if ($action === 'update' && $object_type === 'post') return waal_t('Edit Post');
    if ($action === 'delete' && $object_type === 'page') return waal_t('Delete Page');
    if ($action === 'delete' && $object_type === 'post') return waal_t('Delete Post');

    $map = [
        'login' => 'User Logged In',
        'login_failed' => 'Login Failed',
        'bruteforce' => 'Brute Force Detected',
        'logout' => 'User Logged Out',
        'customizer_publish' => 'Customizer Changes Published',
        'publish' => 'publish',
        'update' => 'Edit Content',
        'delete' => 'Delete Content',
        'plugin_activate' => 'Plugin Activated',
        'plugin_deactivate' => 'Plugin Deactivated',
        'plugin_delete' => 'Plugin Deleted',
        'plugin_update' => 'Plugin Updated',
        'plugin_install' => 'Plugin Installed',
        'theme_switch' => 'Theme Switched',
        'theme_update' => 'Theme Updated',
        'theme_install' => 'Theme Installed',
        'core_update' => 'Update WordPress Core',
        'core_install' => 'Install WordPress Core',
    ];

    if (isset($map[$action])) {
        return waal_t($map[$action]);
    }

    if (in_array($action, ['publish', 'update', 'delete'], true) && $object_type !== '') {
        $label = waal_get_object_type_label($object_type);
        if ($action === 'publish') return waal_t('Publish') . ' ' . ucfirst($label);
        if ($action === 'update') return waal_t('Edit') . ' ' . ucfirst($label);
        if ($action === 'delete') return waal_t('Delete') . ' ' . ucfirst($label);
    }

    return ucwords(str_replace('_', ' ', $action));
}

function waal_get_action_verb($action) {
    $action = sanitize_key((string) $action);
    if ($action === '') {
        return '';
    }

    $map = [
        'publish' => 'publish',
        'update' => 'edit',
        'delete' => 'delete',
        'plugin_activate' => 'activate',
        'plugin_deactivate' => 'deactivate',
        'plugin_delete' => 'delete',
        'plugin_update' => 'update',
        'plugin_install' => 'install',
        'theme_switch' => 'switch',
        'theme_update' => 'update',
        'theme_install' => 'install',
        'core_update' => 'update',
        'core_install' => 'install',
        'customizer_publish' => 'publish',
        'login' => 'login',
        'logout' => 'logout',
        'login_failed' => 'failed',
        'bruteforce' => 'failed',
        'password_reset_failed' => 'failed',
        'permission_denied' => 'blocked',
        'blocked_request' => 'blocked',
    ];

    if (isset($map[$action])) {
        return $map[$action];
    }
    if (substr($action, -7) === '_failed') {
        return 'failed';
    }

    return str_replace('_', ' ', $action);
}

function waal_get_action_verb_label($verb) {
    $verb = sanitize_key((string) $verb);
    if ($verb === '') {
        return '';
    }

    $map = [
        'publish' => 'Publish',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'update' => 'Update',
        'install' => 'Install',
        'switch' => 'Switch',
        'login' => 'Login',
        'logout' => 'Logout',
        'failed' => 'Failed',
        'blocked' => 'Blocked',
    ];

    if (isset($map[$verb])) {
        return waal_t($map[$verb]);
    }
    return waal_t(ucwords(str_replace('_', ' ', $verb)));
}

function waal_get_raw_actions_for_verb($verb) {
    $verb = sanitize_key((string) $verb);
    if ($verb === '') {
        return [];
    }

    $map = [
        'publish' => ['publish', 'customizer_publish'],
        'edit' => ['update'],
        'delete' => ['delete', 'plugin_delete'],
        'activate' => ['plugin_activate'],
        'deactivate' => ['plugin_deactivate'],
        'update' => ['plugin_update', 'theme_update', 'core_update'],
        'install' => ['plugin_install', 'theme_install', 'core_install'],
        'switch' => ['theme_switch'],
        'login' => ['login'],
        'logout' => ['logout'],
        'failed' => ['login_failed', 'bruteforce', 'password_reset_failed'],
        'blocked' => ['permission_denied', 'blocked_request'],
    ];

    if (isset($map[$verb])) {
        return $map[$verb];
    }

    return [$verb];
}

function waal_get_event_label($object_type, $action = '') {
    $object_type = sanitize_key((string) $object_type);
    $action = sanitize_key((string) $action);

    if (in_array($action, ['login', 'logout', 'login_failed', 'bruteforce', 'password_reset_failed', 'permission_denied', 'blocked_request'], true)) {
        return waal_t('Auth');
    }
    if ($object_type !== '') {
        return waal_t(ucfirst((string) waal_get_object_type_label($object_type)));
    }
    return waal_t('System');
}

function waal_ip_display_mode() {
    $mode = sanitize_key((string) get_option('waal_ip_display_mode', 'full'));
    return in_array($mode, ['full', 'masked', 'role_based'], true) ? $mode : 'full';
}

function waal_user_can_view_full_ip() {
    $mode = waal_ip_display_mode();
    if ($mode === 'full') {
        return true;
    }
    if ($mode === 'masked') {
        return false;
    }

    if (function_exists('waal_user_can_manage_audit_integrity')) {
        return waal_user_can_manage_audit_integrity();
    }

    return current_user_can('manage_options');
}

function waal_apply_ip_mask($ip_address) {
    $ip_address = trim((string) $ip_address);
    if ($ip_address === '') {
        return '-';
    }

    $normalized = preg_replace('/[^0-9a-fA-F:\.]/', '', $ip_address);
    if (!is_string($normalized) || $normalized === '') {
        return '-';
    }

    if (filter_var($normalized, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $normalized);
        if (count($parts) === 4) {
            return $parts[0] . '.xxx.xxx.xxx';
        }
        return $normalized;
    }

    if (filter_var($normalized, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $normalized);
        if (count($parts) >= 2) {
            $visible = array_slice($parts, 0, 2);
            return implode(':', $visible) . ':xxxx:xxxx:xxxx:xxxx';
        }
        return $normalized;
    }

    return $normalized;
}

function waal_mask_ip_address($ip_address) {
    $ip_address = trim((string) $ip_address);
    if ($ip_address === '') {
        return '-';
    }
    if (waal_user_can_view_full_ip()) {
        $ip_address = preg_replace('/[^0-9a-fA-F:\.]/', '', $ip_address);
        return $ip_address !== '' ? $ip_address : '-';
    }
    return waal_apply_ip_mask($ip_address);
}

function waal_ip_detail_value($ip_address) {
    $ip_address = trim((string) $ip_address);
    if ($ip_address === '') {
        return '';
    }

    return waal_user_can_view_full_ip() ? $ip_address : waal_apply_ip_mask($ip_address);
}

function waal_normalize_ip_candidate($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (strpos($value, ',') !== false) {
        $parts = array_map('trim', explode(',', $value));
        $value = (string) ($parts[0] ?? '');
    }

    if ($value === '') {
        return '';
    }

    if (strpos($value, ':') !== false && strpos($value, '.') !== false) {
        if (preg_match('/^\[?([0-9a-fA-F:\.]+)\]?:(\d+)$/', $value, $matches)) {
            $value = (string) ($matches[1] ?? $value);
        }
    }

    $value = trim($value, "[] \t\n\r\0\x0B");
    return filter_var($value, FILTER_VALIDATE_IP) ? $value : '';
}

function waal_get_request_ip_address() {
    $server = isset($_SERVER) && is_array($_SERVER) ? $_SERVER : [];
    $header_priority = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_TRUE_CLIENT_IP',
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_CLIENT_IP',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($header_priority as $server_key) {
        if (!isset($server[$server_key])) {
            continue;
        }

        $raw_value = wp_unslash($server[$server_key]);
        if ($server_key === 'HTTP_FORWARDED') {
            if (preg_match('/for=(?:"?\[?)([0-9a-fA-F:\.]+)(?:\]?")?/i', (string) $raw_value, $matches)) {
                $candidate = waal_normalize_ip_candidate((string) ($matches[1] ?? ''));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
            continue;
        }

        $candidate = waal_normalize_ip_candidate((string) $raw_value);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function waal_get_object_type_label($object_type) {
    $object_type = sanitize_key($object_type);
    if ($object_type === 'post') return waal_t('post');
    if ($object_type === 'page') return waal_t('page');
    if ($object_type === 'plugin') return waal_t('plugin');
    if ($object_type === 'theme') return waal_t('theme');
    if ($object_type === 'customizer') return waal_t('customizer changes');

    $obj = get_post_type_object($object_type);
    if ($obj && !empty($obj->labels->singular_name)) {
        return strtolower((string) $obj->labels->singular_name);
    }

    return waal_t(str_replace('_', ' ', $object_type ?: 'content'));
}

function waal_get_content_label($action, $title = '', $object_type = '') {
    $action = sanitize_key($action);
    $object_type = sanitize_key($object_type);
    $title = trim((string) $title);
    $title = $title !== '' ? $title : '-';

    if ($action === 'plugin_activate') return sprintf(waal_t('Activate plugin: %s'), $title);
    if ($action === 'plugin_deactivate') return sprintf(waal_t('Deactivate plugin: %s'), $title);
    if ($action === 'plugin_delete') return sprintf(waal_t('Delete plugin: %s'), $title);
    if ($action === 'plugin_update') return sprintf(waal_t('Update plugin: %s'), $title);
    if ($action === 'plugin_install') return sprintf(waal_t('Install plugin: %s'), $title);
    if ($action === 'customizer_publish') return sprintf(waal_t('Publish customizer changes: %s'), $title);

    if (in_array($action, ['publish', 'update', 'delete'], true)) {
        $label = waal_get_object_type_label($object_type);
        if ($action === 'publish') return sprintf(waal_t('Publish %s: %s'), $label, $title);
        if ($action === 'update') return sprintf(waal_t('Edit %s: %s'), $label, $title);
        if ($action === 'delete') return sprintf(waal_t('Delete %s: %s'), $label, $title);
    }

    if ($action === 'theme_switch') return sprintf(waal_t('Switch theme: %s'), $title);
    if ($action === 'theme_update') return sprintf(waal_t('Update theme: %s'), $title);
    if ($action === 'theme_install') return sprintf(waal_t('Install theme: %s'), $title);
    if ($action === 'core_update') return waal_t('Update WordPress core');
    if ($action === 'login') return waal_t('Login successful');
    if ($action === 'logout') return waal_t('User Logged Out');
    if ($action === 'login_failed') return sprintf(waal_t('Login failed: %s'), $title);
    if ($action === 'bruteforce') return sprintf(waal_t('Brute force suspected: %s'), $title);

    return $title;
}

function waal_get_changeset_summary($post) {
    if (!$post) {
        return waal_t('Site design changes');
    }

    $title = trim((string) ($post->post_title ?? ''));
    if ($title !== '') {
        return $title;
    }

    $content = (string) ($post->post_content ?? '');
    if ($content !== '') {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return sprintf(waal_t('%d setting(s)'), count($decoded));
        }
    }

    return waal_t('Site design changes');
}

function waal_get_post_log_title($post) {
    if (!$post || !is_object($post)) {
        return '-';
    }

    $title = trim((string) ($post->post_title ?? ''));
    if ($title !== '') {
        return $title;
    }

    $status = (string) ($post->post_status ?? '');
    $post_id = (int) ($post->ID ?? 0);
    if (in_array($status, ['draft', 'auto-draft', 'pending'], true)) {
        return '';
    }

    $type_label = waal_get_object_type_label((string) ($post->post_type ?? 'content'));
    if ($post_id > 0) {
        return ucfirst($type_label) . ' #' . $post_id;
    }

    return ucfirst($type_label);
}

function waal_get_excludable_events() {
    return [
        'auth' => waal_t('Auth'),
        'post' => ucfirst((string) waal_get_object_type_label('post')),
        'page' => ucfirst((string) waal_get_object_type_label('page')),
        'media' => waal_t('Media'),
        'plugin' => ucfirst((string) waal_get_object_type_label('plugin')),
        'theme' => ucfirst((string) waal_get_object_type_label('theme')),
        'customizer' => waal_t('Customizer'),
        'core' => waal_t('Core'),
        'system' => waal_t('System'),
    ];
}

function waal_get_excluded_events() {
    $saved = get_option('waal_excluded_events', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    $allowed = array_keys(waal_get_excludable_events());
    return array_values(array_intersect(array_map('sanitize_key', $saved), $allowed));
}

function waal_get_event_bucket($action, $object_type) {
    $action = sanitize_key((string) $action);
    $object_type = sanitize_key((string) $object_type);

    if (in_array($action, ['login', 'logout', 'login_failed', 'bruteforce', 'password_reset_failed', 'permission_denied', 'blocked_request'], true)) {
        return 'auth';
    }
    if ($object_type !== '') {
        return $object_type;
    }
    return 'system';
}

function waal_should_exclude_log_event($action, $object_type) {
    $excluded = waal_get_excluded_events();
    if (empty($excluded)) {
        return false;
    }
    return in_array(waal_get_event_bucket($action, $object_type), $excluded, true);
}

function waal_touch_cache_token() {
    static $bumped_in_request = false;
    if ($bumped_in_request) {
        return;
    }
    $current = (int) get_option('waal_cache_token', 1);
    update_option('waal_cache_token', $current + 1, false);
    $bumped_in_request = true;
}

function waal_get_bruteforce_threshold() {
    $threshold = (int) apply_filters('waal_bruteforce_threshold', 5);
    return max(2, $threshold);
}

function waal_get_bruteforce_window_minutes() {
    $minutes = (int) apply_filters('waal_bruteforce_window_minutes', 10);
    return max(1, $minutes);
}

function waal_get_failed_login_warning_threshold() {
    $threshold = (int) apply_filters('waal_failed_login_warning_threshold', 3);
    $threshold = min($threshold, waal_get_bruteforce_threshold());
    return max(2, $threshold);
}

function waal_state_option_name($key) {
    return 'waal_state_' . md5((string) $key);
}

function waal_state_get($key) {
    $key = (string) $key;
    $transient_value = get_transient($key);
    if ($transient_value !== false) {
        return (int) $transient_value;
    }

    $stored = get_option(waal_state_option_name($key), null);
    if (!is_array($stored)) {
        return 0;
    }

    $expires_at = isset($stored['expires_at']) ? (int) $stored['expires_at'] : 0;
    if ($expires_at > 0 && current_time('timestamp') >= $expires_at) {
        delete_option(waal_state_option_name($key));
        return 0;
    }

    return isset($stored['value']) ? (int) $stored['value'] : 0;
}

function waal_state_set($key, $value, $ttl_seconds) {
    $key = (string) $key;
    $value = (int) $value;
    $ttl_seconds = max(1, (int) $ttl_seconds);
    set_transient($key, $value, $ttl_seconds);
    update_option(waal_state_option_name($key), [
        'value' => $value,
        'expires_at' => current_time('timestamp') + $ttl_seconds,
    ], false);
}

function waal_state_delete($key) {
    $key = (string) $key;
    delete_transient($key);
    delete_option(waal_state_option_name($key));
}

function waal_get_failed_login_state_keys($username, $ip_address) {
    $username = strtolower(trim(sanitize_text_field((string) $username)));
    $ip_address = trim(sanitize_text_field((string) $ip_address));

    $keys = [];
    if ($username !== '') {
        $keys[] = 'waal_failed_login_user_' . md5($username);
    }
    if ($ip_address !== '') {
        $keys[] = 'waal_failed_login_ip_' . md5($ip_address);
    }
    return $keys;
}

function waal_get_failed_login_attempt_count($username, $ip_address) {
    $max_count = 0;
    foreach (waal_get_failed_login_state_keys($username, $ip_address) as $key) {
        $current = waal_state_get($key);
        if ($current > $max_count) {
            $max_count = $current;
        }
    }
    return $max_count;
}

function waal_increment_failed_login_attempts($username, $ip_address) {
    $window_seconds = waal_get_bruteforce_window_minutes() * MINUTE_IN_SECONDS;
    $max_count = 0;

    foreach (waal_get_failed_login_state_keys($username, $ip_address) as $key) {
        $current = waal_state_get($key);
        $current++;
        waal_state_set($key, $current, $window_seconds);
        if ($current > $max_count) {
            $max_count = $current;
        }
    }

    return $max_count;
}

function waal_reset_failed_login_attempts($username, $ip_address, $reset_ip_state = false) {
    $username = strtolower(trim(sanitize_text_field((string) $username)));
    $ip_address = trim(sanitize_text_field((string) $ip_address));

    if ($username !== '') {
        waal_state_delete('waal_failed_login_user_' . md5($username));
    }
    if ($reset_ip_state && $ip_address !== '') {
        waal_state_delete('waal_failed_login_ip_' . md5($ip_address));
    }

    waal_state_delete('waal_failed_login_logged_' . md5($username . '|' . $ip_address));
}

function waal_should_log_failed_login($username, $ip_address) {
    $window_seconds = waal_get_bruteforce_window_minutes() * MINUTE_IN_SECONDS;
    $username = strtolower(trim(sanitize_text_field((string) $username)));
    $ip_address = trim(sanitize_text_field((string) $ip_address));
    $key = 'waal_failed_login_logged_' . md5($username . '|' . $ip_address);
    if (waal_state_get($key) > 0) {
        return false;
    }
    waal_state_set($key, 1, $window_seconds);
    return true;
}

function waal_should_send_failed_login_warning($username, $ip_address, $attempt_count = 0) {
    $attempt_count = max((int) $attempt_count, waal_get_failed_login_attempt_count($username, $ip_address));
    if ($attempt_count < waal_get_failed_login_warning_threshold()) {
        return false;
    }

    $username = strtolower(trim(sanitize_text_field((string) $username)));
    $ip_address = trim(sanitize_text_field((string) $ip_address));
    $window_seconds = waal_get_bruteforce_window_minutes() * MINUTE_IN_SECONDS;
    $bucket = (int) floor(current_time('timestamp') / $window_seconds);
    $key = 'waal_failed_login_warning_alert_' . md5($username . '|' . $ip_address . '|' . (string) $bucket);
    if (waal_state_get($key) > 0) {
        return false;
    }

    waal_state_set($key, 1, $window_seconds);
    return true;
}

function waal_promote_recent_failed_login_to_bruteforce($username, $ip_address) {
    if (function_exists('waal_table_exists') && !waal_table_exists()) {
        return false;
    }

    global $wpdb;
    $table = function_exists('waal_table_name') ? waal_table_name() : ($wpdb->prefix . 'activity_logs');
    $username = strtolower(trim(sanitize_text_field((string) $username)));
    $ip_address = trim(sanitize_text_field((string) $ip_address));
    $since = waal_wp_date('Y-m-d H:i:s', current_time('timestamp') - (waal_get_bruteforce_window_minutes() * MINUTE_IN_SECONDS));

    $log_id = 0;
    if ($username !== '') {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $log_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE action = %s AND LOWER(object_title) = %s AND created_at >= %s
             ORDER BY id DESC LIMIT 1",
            'login_failed',
            $username,
            $since
        ));
    }

    if ($log_id === 0 && $ip_address !== '') {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $log_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE action = %s AND ip_address = %s AND created_at >= %s
             ORDER BY id DESC LIMIT 1",
            'login_failed',
            $ip_address,
            $since
        ));
    }

    if ($log_id === 0) {
        return false;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    $updated = $wpdb->update(
        $table,
        ['action' => 'bruteforce', 'object_type' => 'auth'],
        ['id' => $log_id],
        ['%s', '%s'],
        ['%d']
    );

    if ($updated === false) {
        return false;
    }

    waal_touch_cache_token();
    return true;
}

function waal_is_bruteforce_detected($username, $ip_address, $attempt_count = 0) {
    if (function_exists('waal_table_exists') && !waal_table_exists()) {
        return (int) $attempt_count >= waal_get_bruteforce_threshold();
    }

    global $wpdb;
    $table = function_exists('waal_table_name') ? waal_table_name() : ($wpdb->prefix . 'activity_logs');

    $username = trim(sanitize_text_field((string) $username));
    $ip_address = trim(sanitize_text_field((string) $ip_address));
    $threshold = waal_get_bruteforce_threshold();
    $since = waal_wp_date('Y-m-d H:i:s', current_time('timestamp') - (waal_get_bruteforce_window_minutes() * MINUTE_IN_SECONDS));

    $ip_count = 0;
    if ($ip_address !== '') {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $ip_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE action = %s AND ip_address = %s AND created_at >= %s",
            'login_failed',
            $ip_address,
            $since
        ));
    }

    $user_count = 0;
    if ($username !== '') {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $user_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE action = %s AND LOWER(object_title) = %s AND created_at >= %s",
            'login_failed',
            strtolower($username),
            $since
        ));
    }

    return max((int) $attempt_count, $ip_count, $user_count) >= $threshold;
}

function waal_maybe_log_bruteforce($username, $ip_address, $attempt_count = 0) {
    if (!waal_is_bruteforce_detected($username, $ip_address, $attempt_count)) {
        return;
    }

    $username = trim(sanitize_text_field((string) $username));
    $ip_address = trim(sanitize_text_field((string) $ip_address));
    $window_minutes = waal_get_bruteforce_window_minutes();
    $bucket = (int) floor(current_time('timestamp') / ($window_minutes * MINUTE_IN_SECONDS));
    $cache_key = 'waal_bruteforce_logged_' . md5(strtolower($username) . '|' . $ip_address . '|' . (string) $bucket);
    if (waal_state_get($cache_key) > 0) {
        return;
    }
    waal_state_set($cache_key, 1, $window_minutes * MINUTE_IN_SECONDS);

    if (waal_promote_recent_failed_login_to_bruteforce($username, $ip_address)) {
        return;
    }

    $title = $username !== '' ? $username : ($ip_address !== '' ? $ip_address : 'unknown');
    waal_log(0, 'bruteforce', 'auth', 0, $title);
}

function waal_free_notifications_enabled() {
    return (int) get_option('waal_notify_enabled', 0) === 1;
}

function waal_free_notification_email() {
    $email = sanitize_email((string) get_option('waal_notify_email', ''));
    if ($email === '' || !is_email($email)) {
        $email = sanitize_email((string) get_option('admin_email', ''));
    }
    return $email;
}

function waal_free_notify_threat_enabled() {
    return (int) get_option('waal_notify_threat', 1) === 1;
}

function waal_free_notify_critical_enabled() {
    return (int) get_option('waal_notify_critical_changes', 1) === 1;
}

function waal_free_get_email_logo_url() {
    if (defined('WAAL_URL') && defined('WAAL_PATH')) {
        $relative = 'includes/logo-inkatrace-activity-audit-log-email.png';
        $absolute = WAAL_PATH . $relative;
        $version = file_exists($absolute) ? (string) filemtime($absolute) : (string) WAAL_VERSION;
        return esc_url_raw(WAAL_URL . $relative . '?v=' . rawurlencode($version));
    }
    return '';
}

function waal_free_render_email_html(array $payload) {
    $brand = 'InkaTrace for Activity & Audit Log';
    $logo = waal_free_get_email_logo_url();
    $headline = sanitize_text_field((string) ($payload['headline'] ?? 'Activity Notification'));
    $intro = sanitize_text_field((string) ($payload['intro'] ?? ''));
    $preheader = sanitize_text_field((string) ($payload['preheader'] ?? $headline));
    $severity_label = sanitize_text_field((string) ($payload['severity_label'] ?? 'Info'));
    $severity_color = sanitize_hex_color((string) ($payload['severity_color'] ?? '#2563eb'));
    if ($severity_color === null || $severity_color === '') {
        $severity_color = '#2563eb';
    }

    $summary_rows = '';
    foreach ((array) ($payload['summary_rows'] ?? []) as $row) {
        $label = sanitize_text_field((string) ($row['label'] ?? ''));
        $value = sanitize_text_field((string) ($row['value'] ?? ''));
        if ($label === '') {
            continue;
        }
        $summary_rows .= '<tr>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;color:#4b5563;">' . esc_html($label) . '</td>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;color:#111827;font-weight:600;text-align:right;">' . esc_html($value !== '' ? $value : '-') . '</td>'
            . '</tr>';
    }

    $highlight_html = '';
    $highlight = (array) ($payload['highlight'] ?? []);
    $highlight_title = sanitize_text_field((string) ($highlight['title'] ?? ''));
    $highlight_text = sanitize_text_field((string) ($highlight['text'] ?? ''));
    if ($highlight_title !== '' || $highlight_text !== '') {
        $highlight_html = '<div style="margin:16px 0 0;padding:12px 14px;background:#fff7ed;border-left:4px solid #f59e0b;border-radius:8px;">'
            . ($highlight_title !== '' ? '<div style="font-size:13px;font-weight:700;color:#9a3412;margin-bottom:4px;">' . esc_html($highlight_title) . '</div>' : '')
            . ($highlight_text !== '' ? '<div style="font-size:13px;color:#7c2d12;">' . esc_html($highlight_text) . '</div>' : '')
            . '</div>';
    }

    $cta_url = esc_url((string) ($payload['cta_url'] ?? ''));
    $cta_label = sanitize_text_field((string) ($payload['cta_label'] ?? ''));
    $cta_html = '';
    if ($cta_url !== '' && $cta_label !== '') {
        $cta_html = '<div style="margin:18px 0 0;">'
            . '<a href="' . $cta_url . '" style="display:inline-block;background:#047857;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:600;">'
            . esc_html($cta_label)
            . '</a></div>';
    }

    $footer_note = sanitize_text_field((string) ($payload['footer_note'] ?? 'This is an automated notification from InkaTrace.'));

    return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#eef3f2;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . esc_html($preheader) . '</div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3f2;padding:24px 0;"><tr><td align="center">'
        . '<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #dbe4e3;border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:linear-gradient(90deg,#0b7aa3 0%,#159b6c 100%);padding:14px 18px;color:#ffffff;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>'
        . '<td style="vertical-align:middle;">'
        . ($logo !== '' ? '<img src="' . esc_url($logo) . '" alt="' . esc_attr($brand) . '" style="height:auto;max-width:220px;width:auto;display:block;">' : '')
        . '</td>'
        . '<td style="text-align:right;vertical-align:middle;">'
        . '<span style="display:inline-block;background:' . esc_attr($severity_color) . ';color:#fff;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:700;line-height:1.2;">' . esc_html($severity_label) . '</span>'
        . '</td>'
        . '</tr></table>'
        . '</td></tr>'
        . '<tr><td style="padding:24px;">'
        . '<h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;color:#0f172a;">' . esc_html($headline) . '</h1>'
        . ($intro !== '' ? '<p style="margin:0;color:#334155;font-size:14px;line-height:1.6;">' . esc_html($intro) . '</p>' : '')
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:16px 0 0;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">'
        . $summary_rows
        . '</table>'
        . $highlight_html
        . $cta_html
        . '<p style="margin:20px 0 0;font-size:12px;color:#64748b;">' . esc_html($footer_note) . '</p>'
        . '<p style="margin:10px 0 0;font-size:13px;color:#0f172a;font-weight:600;">InkaTrace for Activity &amp; Audit Log</p>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function waal_free_send_notification_email($subject, array $payload) {
    if (!waal_free_notifications_enabled()) {
        return false;
    }

    $to = waal_free_notification_email();
    if ($to === '' || !is_email($to)) {
        return false;
    }

    $safe_subject = sanitize_text_field((string) $subject);
    if ($safe_subject === '') {
        $safe_subject = 'InkaTrace Activity Notification';
    }

    $body = waal_free_render_email_html($payload);

    return (bool) wp_mail($to, $safe_subject, $body, ['Content-Type: text/html; charset=UTF-8']);
}

function waal_free_is_critical_site_change_action($action) {
    $action = sanitize_key((string) $action);
    return in_array($action, [
        'plugin_activate',
        'plugin_deactivate',
        'plugin_delete',
        'theme_switch',
        'core_update',
        'core_install',
    ], true);
}

function waal_free_maybe_send_threat_alert($user_id, $action, $title, $log_id = 0) {
    if (!waal_free_notify_threat_enabled()) {
        return;
    }
    $action = sanitize_key((string) $action);
    if ($action !== 'bruteforce') {
        return;
    }

    $fingerprint = md5((string) $user_id . '|' . $action . '|' . (string) $title);
    $cache_key = 'waal_threat_alert_' . $fingerprint;
    if (get_transient($cache_key)) {
        return;
    }
    set_transient($cache_key, 1, 10 * MINUTE_IN_SECONDS);

    $ip = function_exists('waal_get_request_ip_address') ? waal_get_request_ip_address() : sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $site = home_url('/');
    $event_id = max(0, (int) $log_id);
    $detail_url = admin_url('admin.php?page=wp-activity-log');
    if ($event_id > 0) {
        $detail_url = add_query_arg(['s' => $event_id], $detail_url);
    }

    waal_free_send_notification_email(
        'Security Alert: Brute Force Suspected',
        [
        'preheader' => 'Danger alert detected from repeated failed login activity.',
        'severity_label' => 'Danger',
        'severity_color' => '#dc2626',
        'headline' => 'Brute Force Alert Email',
        'intro' => 'Repeated failed logins indicate a potential brute force attempt.',
        'summary_rows' => [
            ['label' => 'Severity', 'value' => 'Danger'],
            ['label' => 'Event ID', 'value' => $event_id > 0 ? (string) $event_id : '-'],
            ['label' => 'Event', 'value' => 'Brute force suspected'],
            ['label' => 'Username/Email Input', 'value' => trim((string) $title) !== '' ? trim((string) $title) : '-'],
            ['label' => 'IP Address', 'value' => $ip !== '' ? $ip : '-'],
            ['label' => 'Detected At', 'value' => current_time('mysql')],
            ['label' => 'Site', 'value' => $site],
        ],
        'highlight' => [
            'title' => 'Threat Highlight',
            'text' => 'High-risk pattern detected. Immediate action is recommended.',
        ],
        'cta_label' => 'View Log Detail',
        'cta_url' => $detail_url,
        'footer_note' => 'Automated authentication alert generated by InkaTrace monitoring.',
    ]);
}

function waal_free_maybe_send_failed_login_warning($username, $ip_address, $attempt_count = 0) {
    if (!waal_free_notify_threat_enabled()) {
        return;
    }
    if (!waal_should_send_failed_login_warning($username, $ip_address, $attempt_count)) {
        return;
    }

    $username = trim(sanitize_text_field((string) $username));
    $ip_address = trim(sanitize_text_field((string) $ip_address));
    $site = home_url('/');
    $detail_url = admin_url('admin.php?page=wp-activity-log');
    $attempt_total = max((int) $attempt_count, waal_get_failed_login_attempt_count($username, $ip_address));

    waal_free_send_notification_email(
        'Warning Alert: Repeated Failed Login Attempts',
        [
        'preheader' => 'Warning alert detected from repeated failed login activity.',
        'severity_label' => 'Warning',
        'severity_color' => '#b45309',
        'headline' => 'Warning Alert Email',
        'intro' => 'Repeated failed login attempts were detected and recorded by InkaTrace.',
        'summary_rows' => [
            ['label' => 'Severity', 'value' => 'Warning'],
            ['label' => 'Event', 'value' => 'Repeated failed login attempts'],
            ['label' => 'Attempts in current window', 'value' => (string) $attempt_total],
            ['label' => 'Username/Email Input', 'value' => $username !== '' ? $username : '-'],
            ['label' => 'IP Address', 'value' => $ip_address !== '' ? $ip_address : '-'],
            ['label' => 'Detected At', 'value' => current_time('mysql')],
            ['label' => 'Site', 'value' => $site],
        ],
        'highlight' => [
            'title' => 'Threat Highlight',
            'text' => 'This pattern has reached the warning threshold but has not yet been promoted to brute force.',
        ],
        'cta_label' => 'View Activity Log',
        'cta_url' => $detail_url,
        'footer_note' => 'Automated repeated-login warning generated by InkaTrace monitoring.',
    ]);
}

function waal_free_maybe_send_critical_change_alert($user_id, $action, $title, $log_id = 0) {
    if (!waal_free_notify_critical_enabled()) {
        return;
    }
    if (!waal_free_is_critical_site_change_action($action)) {
        return;
    }

    $action = sanitize_key((string) $action);
    $fingerprint = md5((string) $user_id . '|' . $action . '|' . (string) $title);
    $cache_key = 'waal_critical_alert_' . $fingerprint;
    if (get_transient($cache_key)) {
        return;
    }
    set_transient($cache_key, 1, 10 * MINUTE_IN_SECONDS);

    $site = home_url('/');
    $actor = function_exists('waal_get_actor_label')
        ? waal_get_actor_label((int) $user_id, '', $action, (string) $title)
        : ('User #' . (int) $user_id);
    $event_label = function_exists('waal_get_action_label')
        ? waal_get_action_label($action)
        : ucwords(str_replace('_', ' ', $action));
    $ip = function_exists('waal_get_request_ip_address') ? waal_get_request_ip_address() : sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $event_id = max(0, (int) $log_id);
    $detail_url = admin_url('admin.php?page=wp-activity-log');
    if ($event_id > 0) {
        $detail_url = add_query_arg(['s' => $event_id], $detail_url);
    }

    waal_free_send_notification_email('Critical Change Alert: ' . $event_label, [
        'preheader' => 'Critical site change detected by activity monitor.',
        'severity_label' => 'Critical',
        'severity_color' => '#b45309',
        'headline' => 'Critical Alert Email',
        'intro' => 'A high-impact configuration or lifecycle change has been logged.',
        'summary_rows' => [
            ['label' => 'Severity', 'value' => 'Critical'],
            ['label' => 'Event ID', 'value' => $event_id > 0 ? (string) $event_id : '-'],
            ['label' => 'Action', 'value' => $event_label],
            ['label' => 'Actor', 'value' => $actor !== '' ? $actor : '-'],
            ['label' => 'Target', 'value' => trim((string) $title) !== '' ? trim((string) $title) : '-'],
            ['label' => 'IP Address', 'value' => $ip !== '' ? $ip : '-'],
            ['label' => 'Detected At', 'value' => current_time('mysql')],
            ['label' => 'Site', 'value' => $site],
        ],
        'highlight' => [
            'title' => 'Threat Highlight',
            'text' => 'Changes on plugin/theme/core should be reviewed quickly to avoid service disruption.',
        ],
        'cta_label' => 'View Log Detail',
        'cta_url' => $detail_url,
        'footer_note' => 'Automated critical-change notification generated by InkaTrace monitoring.',
    ]);
}

function waal_log($user_id, $action, $object_type = '', $object_id = 0, $title = '') {
    global $wpdb;
    static $table_checked = false;
    if (!$table_checked) {
        if (function_exists('waal_table_exists') && !waal_table_exists() && function_exists('waal_create_table')) {
            waal_create_table();
        }
        $table_checked = true;
    }

    $table = function_exists('waal_table_name') ? waal_table_name() : ($wpdb->prefix . 'activity_logs');
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        $user_id = (int) get_current_user_id();
    }

    $user  = get_userdata($user_id);
    $role  = $user ? implode(',', (array) $user->roles) : '';
    $action = sanitize_key($action);
    $object_type = sanitize_key($object_type);
    $object_id = (int) $object_id;
    $title = wp_strip_all_tags($title);
    $ip_address = function_exists('waal_get_request_ip_address') ? waal_get_request_ip_address() : sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $user_agent = sanitize_textarea_field(wp_unslash($_SERVER['HTTP_USER_AGENT'] ?? ''));


    // Never store technical rows in activity log.
    if (in_array($object_type, ['revision', 'customize_changeset'], true)) {
        return;
    }
    if (waal_should_exclude_log_event($action, $object_type)) {
        return;
    }

    static $in_request_fingerprints = [];
    $fp = md5(implode('|', [$user_id, $action, $object_type, $object_id, $title, $ip_address, $user_agent]));
    if (isset($in_request_fingerprints[$fp])) {
        return;
    }
    $in_request_fingerprints[$fp] = true;

    // Prevent rapid duplicate writes across overlapping hooks/requests in a short window.
    $recent_fp_key = 'waal_recent_log_' . $fp;
    if (get_transient($recent_fp_key)) {
        return;
    }
    set_transient($recent_fp_key, 1, 3);

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    $ok = $wpdb->insert($table, [
        'user_id'      => (int) $user_id,
        'user_role'    => sanitize_text_field($role),
        'action'       => $action,
        'object_type'  => $object_type,
        'object_id'    => $object_id,
        'object_title' => $title,
        'ip_address'   => $ip_address,
        'user_agent'   => $user_agent,
        'created_at'   => current_time('mysql'),
    ]);
    if (!$ok) {
        delete_transient($recent_fp_key);
    }
    if ($ok) {
        waal_touch_cache_token();
        $log_id = isset($wpdb->insert_id) ? (int) $wpdb->insert_id : 0;
        waal_free_maybe_send_threat_alert($user_id, $action, $title, $log_id);
        waal_free_maybe_send_critical_change_alert($user_id, $action, $title, $log_id);
    }
}

add_action('wp_login', function($login, $user){
    $username = '';
    if ($user instanceof WP_User && !empty($user->user_login)) {
        $username = (string) $user->user_login;
    } else {
        $username = sanitize_text_field((string) $login);
    }
    $ip_address = function_exists('waal_get_request_ip_address') ? waal_get_request_ip_address() : sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    waal_reset_failed_login_attempts($username, $ip_address);
    waal_log($user->ID, 'login', 'auth');
}, 10, 2);
add_action('wp_login_failed', function($username){
    $username = sanitize_text_field((string) $username);
    $ip_address = function_exists('waal_get_request_ip_address') ? waal_get_request_ip_address() : sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $attempt_count = waal_increment_failed_login_attempts($username, $ip_address);
    if (waal_should_log_failed_login($username, $ip_address)) {
        waal_log(0, 'login_failed', 'auth', 0, $username);
    }
    waal_free_maybe_send_failed_login_warning($username, $ip_address, $attempt_count);
    waal_maybe_log_bruteforce($username, $ip_address, $attempt_count);
});

add_action('clear_auth_cookie', function(){
    $user_id = get_current_user_id();
    if ($user_id) {
        waal_log($user_id, 'logout', 'auth');
    }
});

add_action('transition_post_status', function($new, $old, $post){
    if (!$post || empty($post->ID)) return;
    if ((string) $post->post_type === 'customize_changeset') {
        if ($new !== $old && $new === 'publish') {
            waal_log(get_current_user_id(), 'customizer_publish', 'customizer', $post->ID, waal_get_changeset_summary($post));
        }
        return;
    }
    if ((string) $post->post_type === 'revision') return;
    if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID)) return;
    if (in_array($new, ['auto-draft', 'inherit'], true)) return;
    if ((string) $old === 'auto-draft') return;

    $log_title = waal_get_post_log_title($post);
    if ($log_title === '') return;

    if ($new !== $old && $new === 'publish') waal_log(get_current_user_id(), 'publish', $post->post_type, $post->ID, $log_title);
    elseif ($new !== $old) waal_log(get_current_user_id(), 'update', $post->post_type, $post->ID, $log_title);
}, 10, 3);

add_action('post_updated', function($post_id, $post_after, $post_before){
    if (wp_is_post_autosave($post_id)) return;
    if (wp_is_post_revision($post_id)) return;
    if (!$post_after || in_array($post_after->post_type, ['revision', 'customize_changeset'], true)) return;
    if (in_array($post_after->post_status, ['auto-draft', 'inherit'], true)) return;
    if (!$post_after || $post_after->post_status !== $post_before->post_status) return;
    $log_title = waal_get_post_log_title($post_after);
    if ($log_title === '') return;

    waal_log(get_current_user_id(), 'update', $post_after->post_type, $post_id, $log_title);
}, 10, 3);

add_action('delete_post', function($post_id){
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if ($post = get_post($post_id)) {
        if (in_array((string) $post->post_type, ['revision', 'customize_changeset'], true)) return;
        $log_title = waal_get_post_log_title($post);
        if ($log_title === '') {
            $log_title = 'Post #' . (int) $post_id;
        }
        waal_log(get_current_user_id(), 'delete', $post->post_type, $post_id, $log_title);
    }
});

add_action('activated_plugin', function($plugin, $network_wide){
    if (defined('WAAL_BASENAME') && $plugin === WAAL_BASENAME) return;
    $title = waal_get_plugin_name($plugin);
    if ($network_wide) $title .= ' (Jaringan)';
    waal_log(get_current_user_id(), 'plugin_activate', 'plugin', 0, $title);
}, 10, 2);

add_action('deactivated_plugin', function($plugin, $network_deactivating){
    if (defined('WAAL_BASENAME') && $plugin === WAAL_BASENAME) return;
    $title = waal_get_plugin_name($plugin);
    if ($network_deactivating) $title .= ' (Jaringan)';
    waal_log(get_current_user_id(), 'plugin_deactivate', 'plugin', 0, $title);
}, 10, 2);

add_action('deleted_plugin', function($plugin, $deleted){
    if (defined('WAAL_BASENAME') && $plugin === WAAL_BASENAME) return;
    $title = waal_get_plugin_name($plugin) . ($deleted ? ' (Deleted)' : ' (Failed)');
    waal_log(get_current_user_id(), 'plugin_delete', 'plugin', 0, $title);
}, 10, 2);

add_action('switch_theme', function($new_name, $new_theme){
    waal_log(get_current_user_id(), 'theme_switch', 'theme', 0, $new_name . ' (' . $new_theme->get_stylesheet() . ')');
}, 10, 2);

add_action('upgrader_process_complete', function($upgrader, $hook_extra){
    $type = sanitize_key($hook_extra['type'] ?? '');
    $action = sanitize_key($hook_extra['action'] ?? '');
    if (!$type || !$action) {
        return;
    }

    if ($type === 'plugin') {
        $plugins = (array) ($hook_extra['plugins'] ?? []);
        foreach ($plugins as $plugin) {
            if (defined('WAAL_BASENAME') && $plugin === WAAL_BASENAME) {
                continue;
            }
            waal_log(get_current_user_id(), 'plugin_' . $action, 'plugin', 0, waal_get_plugin_name((string) $plugin));
        }
    } elseif ($type === 'theme') {
        $themes = (array) ($hook_extra['themes'] ?? []);
        foreach ($themes as $theme) {
            waal_log(get_current_user_id(), 'theme_' . $action, 'theme', 0, (string) $theme);
        }
    } elseif ($type === 'core') {
        waal_log(get_current_user_id(), 'core_' . $action, 'core', 0, 'WordPress core');
    }
}, 10, 2);
