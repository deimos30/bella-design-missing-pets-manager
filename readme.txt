=== Deimos Lost & Found Animals ===
Contributors: deimos30
Donate link: https://github.com/deimos30
Tags: lost, found, animals, pets, shelter
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.6x
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage lost and found animals with filtering and shortcode display. Works with any WordPress theme.

== Description ==

A WordPress plugin for kennels, shelters, and rescue organizations to manage and display lost and found animals.

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
* **Settings page with customizable options**
* **Filter bar width and alignment controls**
* **Color pickers for styling**
* **Configurable contact phone and email**
* Works with ANY WordPress theme

= Shortcode =

Use `[lost_found_animals]` to display animals on any page or post.

= Shortcode Parameters =

* `limit` - Number of animals (default: from Settings, -1 for all)
* `status` - Filter by status
* `columns` - Grid columns 1-4 (default: from Settings)
* `show_filters` - Show filter bar (default: from Settings)

= Examples =

`[lost_found_animals limit="8" columns="4"]`
`[lost_found_animals status="Found" show_filters="false"]`

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/deimos-lost-found-animals` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to 'Lost & Found Animals' > 'Settings' to configure options
4. Use shortcode `[lost_found_animals]` on any page
5. Go to Settings > Permalinks and click Save Changes

== Frequently Asked Questions ==

= How do I add a photo? =

Use the "Main Photo (Featured Image)" box in the right sidebar when editing an animal.

= How do I set contact information? =

Go to Lost & Found Animals > Settings > Contact Settings to set your phone number and email address.

= The filter bar doesn't display correctly? =

Go to Settings and adjust the Filter Bar Width. Use "Compact" or "Medium" for better display.

== Screenshots ==

1. Animal grid display on frontend
2. Single animal page
3. Settings page with customization options
4. Admin list with status badges

== Changelog ==

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

= 1.0.6 =
Plugin renamed! Gallery removed - now uses single Featured Image. New contact settings for phone and email.
