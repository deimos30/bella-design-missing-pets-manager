=== Deimos Lost & Found Animals ===
Contributors: wko1
Tags: lost, found, animals, pets, shelter
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage lost and found animals with filtering and shortcode display. Works with any WordPress theme.

== Description ==

A WordPress plugin for kennels, shelters, and rescue organizations to manage and display lost and found animals.

Copyright (c) 2026 Wojtek Kobylecki. Licensed under GPLv2 or later.

= Features =

* Custom Post Type for Animals (Dog, Cat, Other)
* Classic Editor interface (easy to use)
* Featured Image as main photo
* Status badges (Found Today, Found, Available, Reunited, Not Available)
* Filter by status and gender
* Sort by date or name
* Responsive grid display (1-4 columns)
* Single animal page with full details
* Social sharing buttons
* Settings page with customizable options
* Filter bar width and alignment controls
* Color pickers for styling
* Configurable contact phone and email
* Works with ANY WordPress theme

= Shortcode =

Use `[deimlofo_animals]` to display animals on any page or post.

= Shortcode Parameters =

* `limit` - Number of animals (default: from Settings, -1 for all)
* `status` - Filter by status
* `columns` - Grid columns 1-4 (default: from Settings)
* `show_filters` - Show filter bar (default: from Settings)

= Examples =

`[deimlofo_animals limit="8" columns="4"]`
`[deimlofo_animals status="Found" show_filters="false"]`

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/deimos-lost-found-animals` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Lost & Found Animals' > 'Settings' to configure options
4. Use shortcode `[deimlofo_animals]` on any page
5. Go to Settings > Permalinks and click Save Changes

== Frequently Asked Questions ==

= How do I add a photo? =

Use the "Main Photo (Featured Image)" box in the right sidebar when editing an animal.

= How do I set contact information? =

Go to Lost & Found Animals > Settings > Contact Settings to set your phone number and email address.

= The filter bar doesn't display correctly? =

Go to Settings and adjust the Filter Bar Width. Use "Compact" or "Medium" for better display.

= I upgraded from 1.0.6 and the shortcode stopped working =

Version 1.1.0 renamed the shortcode from `[lost_found_animals]` to `[deimlofo_animals]`. The old
shortcode is no longer registered, so any page using it must be updated to the new name. Your
animals, photos and settings are migrated automatically; only the shortcode text needs changing.

== Screenshots ==

1. Animal grid display on frontend
2. Single animal page
3. Settings page with customization options
4. Admin list with status badges

== Changelog ==

= 1.1.1 =
* Fixed: Settings from 1.0.x could be lost when upgrading. Activating the plugin created default `deimlofo_settings` before the migration ran, so the migration skipped the legacy `lfa_settings` and then deleted them. Activation now migrates first and only writes defaults when no legacy settings are left.
* Fixed: Legacy settings are merged with the new defaults and validated with the same rules as the settings screen (columns 1-4, integer limit, allowlisted width/alignment, hex colours, sanitized phone and email).
* Fixed: The legacy settings are deleted only after the new settings, animals and animal details have been written and verified by reading them back from the database. The database version is recorded only after a successful migration.
* Fixed: A failed or interrupted migration keeps all original data, shows a notice to administrators and is retried automatically. The migration is protected by a lock and is safe to run repeatedly.
* Fixed: The migration now also runs on the front end, so animals stay visible after an automatic update even before an administrator visits the dashboard.
* Fixed: Menu items linking to animals or the animal archive are updated to the new post type during migration.
* Fixed: The single animal page no longer triggers "Theme without header.php/footer.php" deprecation notices or prints a second `<title>` on block themes; it now renders the theme's header and footer template parts.
* Changed: Variables in the single animal template are prefixed so the template defines no generic global variables.
* Tested with WordPress 7.1 (and the minimum supported 5.0) on PHP 7.4 and 8.5.

= 1.1.0 =
* Changed: All functions, classes, constants, options, post meta, nonces, asset handles, image sizes and CSS classes are now prefixed with `deimlofo` / `DEIMLOFO_` to meet WordPress.org uniqueness requirements.
* Changed: Custom post type renamed from `animal` to `deimlofo_animal`. The public URL slug stays `animal`, so existing links keep working.
* Changed: Shortcode renamed from `[lost_found_animals]` to `[deimlofo_animals]`. The old shortcode is no longer available.
* Added: Automatic one-time data migration from 1.0.x. Animals, statuses, contact data, custom fields and featured images are carried over, and the routine is safe to run more than once.
* Changed: Settings-dependent CSS now ships through `wp_add_inline_style()` instead of an echoed `<style>` block in `wp_head`.
* Changed: Colour picker initialisation moved from an inline `<script>` block into `assets/js/admin.js`, loaded only on the plugin settings page.
* Changed: Status badge colours moved from inline `style` attributes to `deimlofo-status--*` CSS classes.
* Removed: `onclick` attributes and the `javascript:` Back link. The Back link now has a real archive URL and is enhanced with an event listener.
* Fixed: Multiple shortcode instances on one page no longer collide; markup uses classes scoped per container instead of duplicate element IDs.
* Security: Enumerated fields (type, status, gender, microchip) are validated against allowlists, dates are checked with `checkdate()`, and colours are validated with `sanitize_hex_color()`.
* Security: Nonce values are unslashed and sanitized before verification, and all output is escaped at the point of output.

= 1.0.6 =
* Changed: Renamed plugin to "Deimos Lost & Found Animals" and updated slug/text-domain.
* Removed: Gallery support; plugin now uses a single Featured Image only.
* Added: Color setting for "View Details" button.
* Added: Contact settings for default phone and email used on the single animal page.

= 1.0.5 =
* Fixed: Filter bar now displays as single horizontal line (theme-independent)
* New: Filter Bar Width setting (Compact/Medium/Large/Full)
* New: Filter Bar Alignment setting (Left/Center/Right)
* Improved: CSS uses !important to override theme styles

= 1.0.4 =
* New: Settings page under Lost & Found Animals menu
* New: Configurable grid columns (1-4)
* New: Configurable animals limit
* New: Show/hide filters option
* New: Color picker for filter bar background
* New: Color picker for Reset button

= 1.0.3 =
* Security: Added direct file access protection

= 1.0.2 =
* Fixed: Removed deprecated load_plugin_textdomain()
* Updated: Tested up to WordPress 6.9

= 1.0.1 =
* Changed: Switched to Classic Editor for Animal post type

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.1 =
Fixes the 1.0.x upgrade so that your existing settings are always kept. Recommended for everyone upgrading from 1.0.x. Remember to use the [deimlofo_animals] shortcode.

= 1.1.0 =
Important: the shortcode is now [deimlofo_animals]. Update any page using the old [lost_found_animals] shortcode. Your animals, photos and settings migrate automatically.

= 1.0.6 =
Plugin renamed! Gallery removed - now uses single Featured Image. New contact settings for phone and email.
