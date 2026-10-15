=== Sakolaan ===
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage school identity, content, admissions, SEO, and theme settings from one focused WordPress dashboard.

== Description ==

Sakolaan provides the content and configuration layer for Sakolaan Theme. Its dashboard organizes school identity, official information, contact details, social profiles, statistics, content, admissions, SEO, header, footer, and homepage settings.

Content visibility, ordering, and item limits are managed from a unified control. The plugin also provides settings and school-data backups, import previews, health checks, permalink tools, and guarded migration and recovery workflows for older Sakolaan installations.

Demo media is distributed as a separate, checksummed package. Official GitHub Release packages contain the matching HTTPS URL and SHA-256 checksum automatically. Administrators may override both values together in wp-config.php. Plugin-managed content is retained when the plugin is deactivated. Uninstall removes settings only when an administrator has enabled that option.

Official website: https://sakolaan.com/

Developed by Tanisya: https://tanisya.com/

== External Service ==

When an administrator explicitly installs demo data, Sakolaan downloads the configured media package from SAKOLAAN_DEMO_MEDIA_URL. Official packages use the matching asset on GitHub Releases at github.com. The request is only made during demo installation when no matching validated cache exists. Administrators can override the URL and checksum together to use another HTTPS host. GitHub services are subject to the GitHub Privacy Statement (https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement) and Terms of Service (https://docs.github.com/en/site-policy/github-terms/github-terms-of-service).

== Installation ==

1. Upload the `sakolaan` folder to `/wp-content/plugins/`, or install the plugin package through the WordPress Plugins screen.
2. Activate Sakolaan from the Plugins screen.
3. Upload and activate Sakolaan Theme with the same version as the plugin (currently 0.0.1).
4. Open the Sakolaan menu to configure school identity, content, SEO, and appearance.
5. Open Data Demo. Official release packages are ready to download their matching media automatically. For a private mirror, define both SAKOLAAN_DEMO_MEDIA_URL and SAKOLAAN_DEMO_MEDIA_SHA256 in wp-config.php.

== Frequently Asked Questions ==

= Does deactivating the plugin delete school content? =

No. Deactivation does not delete plugin-managed content.

= Can I preview an import before applying it? =

Yes. Settings and school-data imports are validated and previewed before they can be applied.

= Can I host the demo media package myself? =

Yes. Define both SAKOLAAN_DEMO_MEDIA_URL and SAKOLAAN_DEMO_MEDIA_SHA256 in wp-config.php. A partial override is rejected so a manual URL can never be paired silently with the release checksum.

= How is legacy data migration protected? =

The first migration creates a checksummed JSON snapshot. Migration stops if the snapshot cannot be created or conflicting destination data is detected. Status, retry, download, and recovery actions are available under Sakolaan > More > System > Maintenance.

== Changelog ==

= 0.0.1 =

* Initial release with matching plugin-theme version validation and dependency notices.
