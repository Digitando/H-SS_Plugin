=== HŠSS Rezultati ===
Contributors: digitando
Tags: sports, api, results, live, shortcode
Requires at least: 5.6
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates with the School Sports API to display sports data, results, and schedules on your WordPress site.

== Description ==

HŠSS Rezultati plugin provides seamless integration with the School Sports API service, allowing you to display sports results, schedules, and live updates on your WordPress website.

= Core Features =

* **Admin Settings Page** - Configure API credentials and refresh intervals
* **Shortcode Generator** - Visual interface for creating shortcodes
* **Editor Integration** - Shortcode button in the WordPress editor
* **Real-time Updates** - WebSocket support with AJAX fallback
* **Server-side Caching** - Reduces API load and improves performance

= Available Shortcodes =

* `[school_sports_api_results]` - Display sports results
* `[school_sports_api_live]` - Display live results

= Supported Sports =

* Odbojka (Volleyball)
* Futsal
* Košarka (Basketball)
* Košarka 3x3 (3x3 Basketball)
* Rukomet (Handball)
* Badminton
* Stolni tenis (Table Tennis)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/school-sports-api` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->HŠSS Rezultati screen to configure the plugin
4. Use the shortcodes in your posts or pages

== Frequently Asked Questions ==

= How do I display sports results? =

Use the `[school_sports_api_results]` shortcode with parameters:
* `sport` - Sport type (odbojka, futsal, kosarka, etc.)
* `school_type` - School type (ss for high school, os for elementary school)
* `school_year` - School year (2024 for 2023/2024, etc.)

Example: `[school_sports_api_results sport="odbojka" school_type="ss" school_year="2024"]`

= How do I display live results? =

Use the `[school_sports_api_live]` shortcode with parameters:
* `school_type` - School type (ss for high school, os for elementary school, empty for all)
* `refresh_interval` - Refresh interval in seconds (minimum 30)

Example: `[school_sports_api_live school_type="ss" refresh_interval="30"]`

== Screenshots ==

1. Admin settings page
2. Shortcode generator
3. Sports results display
4. Live results display

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release