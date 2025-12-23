=== Template Porter for Elementor ===
Contributors: mrmoazr
Tags: elementor, templates, export, import, images
Requires at least: 5.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export and import Elementor templates WITH images bundled. No more broken image links!

== Description ==

**Template Porter for Elementor** is a powerful tool that solves the common problem of broken images when exporting and importing Elementor templates. Unlike the default Elementor export/import feature, this plugin automatically bundles all images with your template.

= Key Features =

* **Complete Template Export** - Exports Elementor templates with ALL images bundled into a single ZIP file
* **Automatic Image Import** - Imports images directly into your Media Library
* **Smart ID Mapping** - Automatically updates template JSON with new attachment IDs
* **Zero Manual Work** - No need to manually reselect images in the Elementor editor
* **Preserve Everything** - Maintains all template settings, page settings, and metadata
* **User-Friendly Interface** - Simple admin interface for easy export and import

= How It Works =

**Export:**
1. Select an Elementor template from the dropdown
2. Click "Export Template"
3. Download the generated ZIP file containing the template JSON and all images

**Import:**
1. Upload the ZIP file exported by this plugin
2. Click "Import Template"
3. All images are uploaded to your Media Library
4. Template JSON is automatically updated with new image IDs
5. Your template is ready to use immediately in Elementor!

= Perfect For =

* Moving templates between development and production sites
* Sharing templates with clients or team members
* Creating template backups with all assets included
* Building a template library for multiple projects

= Technical Details =

This plugin extracts all image attachment IDs from your Elementor template data, bundles the actual image files with the template JSON, and on import, intelligently maps old image IDs to new ones. This ensures that all images work immediately in the Elementor editor without any manual intervention.

= Requirements =

* WordPress 5.2 or higher
* PHP 7.4 or higher
* Elementor page builder plugin (free or pro)
* PHP ZipArchive extension enabled

== Installation ==

= Automatic Installation =

1. Log in to your WordPress dashboard
2. Navigate to Plugins > Add New
3. Search for "Elementor Template Porter"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Activate the plugin through the 'Plugins' menu in WordPress

= After Activation =

1. You'll see a new "Template Porter" menu item in your WordPress admin sidebar
2. Navigate to Template Porter to start exporting or importing templates

== Frequently Asked Questions ==

= Does this work with Elementor Free? =

Yes! This plugin works with both Elementor Free and Elementor Pro.

= What types of templates can I export? =

You can export any Elementor template type: pages, sections, headers, footers, popups, and any custom template type from your Elementor Library.

= Will this export templates from Theme Builder? =

The plugin exports templates from the Elementor Library (elementor_library post type). If your Theme Builder templates are saved there, they can be exported.

= Do I need to manually reselect images after import? =

No! That's the whole point of this plugin. All images are automatically imported and linked correctly in your template.

= What happens if an image fails to import? =

The plugin logs all import activities. If an image fails to import, it will be noted in the import log, but the template will still be created with the images that were successfully imported.

= Can I use this to migrate templates between multisite installations? =

Yes! This plugin works perfectly for moving templates between any WordPress installations, including multisite networks.

= Is there a file size limit? =

The file size limit depends on your server's PHP upload_max_filesize and post_max_size settings. Large templates with many high-resolution images may require increasing these limits.

= Does this plugin collect any data? =

No. This plugin does not collect, store, or transmit any user data. All export and import operations happen entirely on your server.

= Is this plugin compatible with other page builders? =

No, this plugin is specifically designed for Elementor templates only.

== Screenshots ==

1. Admin interface showing export and import sections
2. Template selection dropdown for export
3. Successful export with download link
4. Import interface with file upload
5. Import success message with edit link

== Changelog ==

= 1.0.0 =
* Initial release
* Export Elementor templates with bundled images
* Import templates with automatic image ID mapping
* Simple admin interface
* Full security implementation with file validation
* Elementor dependency check
* Comprehensive error handling and logging

== Upgrade Notice ==

= 1.0.0 =
Initial release of Elementor Template Porter.

== Credits ==

This plugin was developed to solve a real-world problem faced by Elementor users worldwide. Special thanks to the WordPress and Elementor communities.

== Privacy Policy ==

Elementor Template Porter does not collect, store, or transmit any personal data. All operations are performed locally on your WordPress installation.
