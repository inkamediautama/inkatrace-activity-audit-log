=== InkaTrace for Activity & Audit Log ===
Contributors: inkamedia
Tags: activity log, audit log, security, monitoring, admin tools
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.4.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit and monitor key WordPress activities across users, content, login activity, and system events in one admin log.

== Description ==

InkaTrace for Activity & Audit Log provides real-time visibility into WordPress activity by recording key actions across users, content, login activity, and system changes in one centralized audit log.

Key features in the free version:

* Activity log table with filters, search, and saved filter presets.
* Incident notes and basic incident status tracking inside log detail.
* Dashboard activity widget.
* Failed-login monitoring with warning and security alert severity levels.
* Optional auto-purge retention controls.
* Optional IP geolocation consent for admin IP detail lookups.
* Simple access control: administrators always have access, with an optional editor view toggle.
* Lightweight admin UI focused on daily audit workflow.

A separate commercial Pro plugin is available for extended compliance, retention, export, and investigation workflows.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install via WordPress Plugins screen.
2. Activate the plugin through the `Plugins` screen.
3. Open `Activity Log` from the WordPress admin menu.

== Frequently Asked Questions ==

= Is this plugin GPL compatible? =

Yes. This plugin is licensed under GPLv2 or later.

== Privacy ==

This plugin stores activity log records locally in your WordPress database.

For security auditing purposes, log entries can include:
* User account information related to the action.
* IP addresses.
* User agent / device strings.
* Log content describing the recorded action.

== External Services ==

This plugin can call external IP geolocation services when an administrator enables IP geolocation consent in Settings and then opens IP details in the Activity Log.

Services used:
* `https://ipapi.co/`
* `https://ipwho.is/`

Data sent:
* The visitor IP address stored in the activity log.

Purpose:
* To display geographic/network context for audit and security investigation.

These requests are optional, only triggered from the admin UI, and never run on front-end page loads.

== Changelog ==

= 1.2.4.4 =
* Added Incident Notes in log detail to support local audit investigation workflow.
* Added basic incident status tracking in Free log detail.
* Added Saved Search Presets for faster reuse of Activity Log filters.
* Improved repeated failed-login detection with lighter local counters, clearer warning escalation, and cleaner brute-force promotion.
* Refined admin workflow consistency across Activity Log, Compliance Reports, and settings screens.
* Added explicit opt-in consent for external IP geolocation lookups.
* Simplified Free upsell placement to dedicated upgrade entry points.

= 1.2.4.3 =
* Added brute-force pattern detection for repeated failed logins (default threshold: 5 attempts in 10 minutes, configurable via filters).
* Introduced dedicated `Warning Alert` severity for single failed login attempts.
* Reserved `Security Alert` (danger) severity for confirmed brute-force events.
* Upgraded email notification template styling and logo loading strategy for better Gmail rendering.
* Simplified free access control to a single optional editor view setting.
* Removed free permission matrix flow from settings to keep the free feature set focused.

= 1.2.4.1 =
* Removed chart/trend summary logic from Free (no server-side chart generator, no chart endpoint, no chart asset usage).
* Simplified Free dashboard and settings presentation to reduce unnecessary upgrade prompts.
* Improved bilingual consistency (English/Indonesian) across key admin screens.

= 1.2.4 =
* Added a dedicated premium features page for users who want to review extended capabilities.
* Simplified `Role Access` in Free settings for a clearer access model.
* Improved interface consistency across Activity Log screens.

= 1.2.3 =
* Improved Free admin guidance and settings descriptions for clearer navigation.
* Minor stability updates and UI consistency improvements.

= 1.2.2 =
* Refined Event/Action mapping and filter structure for clearer log exploration.
* Added safer IP masking format and export alignment for updated table columns.
* Added bilingual (EN/ID) admin text support with language switcher state preservation.

= 1.2.1 =
* Dashboard layout refresh: full-width log table/filter with cleaner spacing and clearer section grouping.
* Synced Free/Pro dashboard structure while preserving feature access differences.

= 1.2.0 =
* Version sync release with Pro 1.2.0.
* Maintenance update for release metadata compatibility.

= 1.1.9 =
* Release sync with Pro 1.1.9.
* Maintenance update for private update metadata and release detail formatting.

= 1.1.8 =
* Improved upgrade flow: Free edition now auto-deactivates when Pro is activated.
* Prevented dual-active Free/Pro conflicts by keeping only one edition active automatically.

= 1.1.7 =
* Version sync release to match InkaTrace for Activity & Audit Log Pro 1.1.7.

= 1.1.6 =
* Added InkaTrace for Activity & Audit Log branding logo in admin page headers.
* Updated admin menu icon to radar-style icon for clearer product identity.
* Version bump and packaging refresh.

= 1.1.5 =
* Maintenance release for settings and notification hardening.
* Internal compatibility updates for client/server release flow.

= 1.1.2 =
* Removed custom update integration from the free plugin to comply with WordPress.org policy.
* Minor compatibility and submission-readiness updates.

= 1.1.1 =
* Improved admin UI and filtering experience.
* Compatibility updates for modern WordPress and PHP environments.
