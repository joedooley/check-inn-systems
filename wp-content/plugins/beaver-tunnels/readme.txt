=== Beaver Tunnels ===
Contributors: firetree, danielmilner
Tags: beaver builder
Requires at least: 4.3
Tested up to: 4.5.3
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://ww.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that allows Beaver Builder to travel outside of the content area and tunnel to other areas of your site.

== Description ==

A WordPress plugin that allows Beaver Builder to travel outside of the content area and tunnel to other areas of your site.

> Requires [Beaver Builder](http://beaverbuilder.com/) Standard, Pro, or Agency.

= Features: =
* Attach Beaver Builder Templates to action hooks in your themes and plugins.
* Conditionally display the templates on the desired pages.
* Visual Hook Guide to show you where each action hook is.

== Installation ==

1. Upload the beaver-tunnels folder to the /wp-content/plugins/ directory.
2. Activate the Beaver Tunnels plugin through the 'Plugins' menu in WordPress.

== Changelog ==

= 1.2.2 =
* Improved behavior when Beaver Builder is deactivated.
* Fixed incorrect viewport meta in the Template page template.
* Added support for Genesis stylesheets on the Template editor screen.

= 1.2.1 =
* Reverted back to the previous method of rendering Templates because of issues with certain modules.

= 1.2.0 =
* Added: Multisite support. Certain settings will move to the Network Admin when network activated.
* Added: White Label settings are inherited from Beaver Builder.
* Enhancement: Beaver Builder Template Admin is turned on when plugin is activated.
* Enhancement: Template assets are now enqueued with Beaver Builder and are no longer rendered inline with the tunneled Template.
* Fixed: The minimal Template override now has the correct meta tags to be responsive.
* Fixed: The `[beaver_tunnels]` shortcode now overrides the `$wp_query` object as well.

= 1.1.1 =
* Fix: Removed some debugging output when using taxonomy conditionals.

= 1.1.0 =
* Added: `[beaver_tunnels]` shortcode for use in tunneled templates.

= 1.0.3 =
* Fix: Styles are imported correctly when editing a Template and using the Beaver Builder Theme.

= 1.0.2 =
* Fix: Admin bar now only displays if user can edit page.
* Fix: More checks to make sure that the admin bar is visible when it should be.
* Enhancement: License Key field type is now set to "password".

= 1.0.1 =
* Fixed an issue with term display rules.
* Added more options for user status.

= 1.0.0 =
* First public release.
