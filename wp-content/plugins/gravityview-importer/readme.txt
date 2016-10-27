=== GravityView Importer ===
Tags: gravity forms, gravityview
Requires at least: 3.8
Tested up to: 4.6
Stable tag: trunk
Contributors: katzwebservices
License: GPL 3 or higher

Import Gravity Forms entries

== Description ==

Easily import Gravity Forms entries from a CSV file. Learn more on [gravityview.co](https://gravityview.co/extensions/gravity-forms-entry-importer/).

== Installation ==

1. Upload plugin files to your plugins folder, or install using WordPress' built-in Add New Plugin installer
2. Activate the plugin
3. Follow the instructions

== Changelog ==

= 1.3.1 & 1.3.2 on September 2, 2016 =
* Fixed: Support for non-Latin file names
* Fixed: Empty error box on the Map Fields screen
* Fixed: PHP warning

= 1.3 on March 25, 2016 =
* Added: Support for processing Add-on feeds, like User Registration

= 1.2 on March 18, 2016 =
* Added: Support for Product Option fields and Credit Card fields
* Added: Support for Quiz Addon and Poll Addon fields
* Added: Support for importing "Is Read" and "Is Starred" values (use `1` (yes) or `0` (no))
* Fixed: Conflict with Members plugin
    * Allow users with `gravityforms_import_entries` capabilities to import entries. Call the Nobel Prize Committee!
* Fixed: List field importing with fields that have multiple columns. There were duplicate "List" fields in the mapping drop-down, and if those options were selected, list fields would not import properly.
* Fixed: Import multiple checkboxes formatted as CSV values
    * Tweak: Allow for whitespace after each comma
* Fixed: When mapping an uploaded file, the first column name would sometimes be wrapped in quotes
* Fixed: Double quotes (`"`) in header would be shown as two double quotes (`""`)
* Fixed: Translation files not being generated.
* Fixed: When Gravity Forms "No-Conflict Mode" enabled, license activation didn't work
* Tweak: Grouped related fields together in drop-down, now under groups like "Payment Details" and "Entry Notes"
* Tweak: Use blog encoding as default, since that's how Gravity Forms works (previously, UTF-8 was always default)
* Tweak: Added "Gravity Forms Entries" link in WordPress Tools > Import page

= 1.1.3 on February 4, 2016 =
* Fixed: Import and Settings screens potentially not showing
* Fixed: The Changelog and automatic updates were showing for GravityView, not the Entry Importer plugin
* Fixed: Gravity Forms Importer requires Gravity Forms 1.9.5, not Gravity Forms 1.9

= 1.1.2 on August 7 =
* Fixed: Files not displaying as uploaded for Windows servers
* Fixed: PHP warning for undefined variable $action (thanks, Robert!)
* Fixed: Abstract function declaration style (thanks, Robert!)

= 1.1.1 on July 17 =
* Fixed: Javascript conflict with other Gravity Forms Addon feed configuration screens

= 1.1 on June 27 =
* Added: Update existing entry details by specifying an Entry ID field. [Read more](http://docs.gravityview.co/article/257-formatting-guide-csv-import#field-pre-defined-text#field-entry-id)
* Fixed: Issue imported fields with `0` or `0.00` values
* Fixed: Issue where imports fail because of the "mapped fields were empty" error
* Fixed: Date Field formats respect field "Date Format" settings
* Fixed: Issue where multiple imported columns had the same title
* Fixed: Importing Checkbox fields
* Fixed: Date Fields now respect the date formats set in the Gravity Forms field settings
* Added: Developer actions `gravityview-importer/add-entry/added` and `gravityview-importer/add-entry/error` that are triggered after each entry is imported
* Fixed: Duplicate "Use Default Values" configuration option
* Improved file format handling to use the blog encoding as the "To" format
* Updated the [formatting guide for Multi Select fields](http://docs.gravityview.co/article/257-formatting-guide-csv-import#field-pre-defined-text)
* Tweak: Only show Admin settings when Update Entry & Update Post fields are mapped
* Tweak: Fixed incorrect existing entry count
* Fixed: If not using PHP 5.3 or higher, show a notice
* Added: The `gravityview-importer/strict-mode/fill-checkbox-choices` filter

= 1.0.7 on May 12 =
* Fixed: Prevent "Your emails do not match" error when Email field has "Enable Email Confirmation" enabled
* Fixed: Mapping "Created By" was not properly assigning imported entries to the defined user
* Fixed: JSON-formatted Post Image field imports
* Fixed: JSON-formatted Post Tags displaying as JSON in the Gravity Forms Entry
* Fixed: For web hosts without the `mb_convert_encoding()` function, add an alternative
* Fixed: PHP notice related to compatibility with Gravity Forms `get_field_map_choices()` method
* Fixed: Make sure that `__DIR__` is defined on the server

= 1.0.6 on May 4 =
* Fixed: Fatal error during import if a name could not be parsed

= 1.0.5 on April 30 =
* Fixed: "There was a problem while inserting the field values" error on some server configurations
* Updated: Hungarian translation (thanks Robert Tokar!)
* Added: Additional information when displaying an error returned by Gravity Forms
* Fixed: PHP warning caused by CSV parsing library

= 1.0.4 on April 29 =
* Fixed: PHP version 5.3 compatibility

= 1.0.3 on April 29  =
* Added: Support for field Default Values
* Fixed: Name and Address field validation issues
* Fixed: Set width for Field Mapping dropdowns to prevent overflow
* Fixed: Updating Post Data
* Fixed: Show all import-blocking errors for each row in the report, not just one per row
* Fixed: Show better phone formatting error
* Updated translations:
    - Bengali (thanks [@tareqhi](https://www.transifex.com/accounts/profile/tareqhi/)!)
    - Hungarian (thanks [@Darqebus](https://www.transifex.com/accounts/profile/Darqebus/)!)

= 1.0.2 =
* Fixed: Fatal error when handling import in some installations
* Fixed: Set max width for drop-downs in Conditional Logic section
* Updated: Translations

= 1.0.1 Beta =
* Allow for changing character set of imported file ([read how](http://docs.gravityview.co/article/258-exporting-a-csv-from-excel#charset))
* Fixed PHP notices and a fatal error
* Don't show "Download File with Errors" button when there are no added entries
* Fix support for TSV files, allow Text files

= 1.0 Beta =

* First preview release

== Upgrade Notice ==