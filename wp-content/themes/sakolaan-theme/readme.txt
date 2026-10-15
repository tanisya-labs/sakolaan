=== Sakolaan Theme ===

Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Version: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Copyright 2026 Tanisya

Sakolaan Theme is distributed under the terms of the GNU General Public License v2 or later.

== Description ==

Sakolaan Theme is a full-site editing theme for school websites. It provides the presentation layer for the companion Sakolaan plugin while keeping content and settings in WordPress.

== Installation ==

1. Install and activate the Sakolaan plugin with the same version as this theme (currently 0.0.1).
2. Upload the Sakolaan Theme folder to /wp-content/themes/.
3. Activate Sakolaan Theme from Appearance > Themes.
4. Configure the school website from the Sakolaan administration screens.

== Companion Plugin ==

The Sakolaan plugin is required for the theme's custom blocks, content types, school settings, and dynamic data. Custom block names retain the sakolaan-theme namespace for compatibility with existing templates and saved content.

== External Service ==

When an administrator configures a Google Maps URL, the map block embeds a page from Google Maps. A visitor's browser connects to Google and may send technical information such as the visitor's IP address and browser details. No map is loaded when a map URL has not been configured.

Google Privacy Policy: https://policies.google.com/privacy
Google Terms of Service: https://policies.google.com/terms

== Font Credits ==

Public Sans, Copyright 2015 The Public Sans Project Authors, licensed under the SIL Open Font License 1.1.
Source: https://github.com/uswds/public-sans

Source Serif 4, Copyright 2014-2021 Adobe, licensed under the SIL Open Font License 1.1.
Source: https://github.com/adobe-fonts/source-serif

== Changelog ==

= 0.0.1 =
* Initial release with safe mode and matching plugin-theme version validation.
