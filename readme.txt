=== Registrations for WooCommerce ===
Contributors: hastedesign, allysonsouza, anyssa
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=allyson_as%40hotmail%2ecom&lc=US&item_name=WooCommerce%20Registrations%20by%20Haste&currency_code=BRL&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: woocommerce, registrations, attendees, subscriptions, tickets, events
Requires at least: 3.0.1
Tested up to: 4.9.6
Stable tag: 2.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sell registrations for courses, lectures, events or any product based on dates.

== Description ==

= v2.0.4 Release Note =
Fixed out of stock issue caused by incompatibility with WooCommerce 3.4

= Getting Started =
Contribute with this project in **GitHub**: [https://github.com/HasteDesign/Registrations-for-WooCommerce](https://github.com/HasteDesign/Registrations-for-WooCommerce)

Registrations for WooCommerce allows the creation of the **registration** product type. Registration products can have dates as variations.

There are 3 types of dates:

1. Single day
2. Multiple days
3. Date range

For each date variation you can set the price, schedule, and places available (stock). That makes Registrations for WooCommerce great for stores selling courses, classes or any kind of scheduled services.

**Please notice that WooCommerce must be installed and active.**

== Installation ==

= How to create registration products =

1. Install and activate the Registrations for WooCommerce plugin;
2. Create a new product.
3. In the product edition screen, select `Registration` from the **Product Data** dropdown.
4. Go to the Dates section, choose the date type and add as many dates you want. Make sure to save your dates.
5. Go to the Variation section and create variations for all the dates you created earlier.
6. Setup each date variation. Mark as *virtual*, set the price, stock (available places), start and end time. If your date is a range date, you will need to choose the week days too.
7. Save your variations and publish your product. You will see the available dates as a select field at the product page.

For more information about Variable Products see: [WooThemes Variable Product](https://docs.woothemes.com/document/variable-product/)

== Screenshots ==

1. Product Type - Registration
2. Single Day
3. Multiple Days
4. Range Date
5. Variation based on date
6. Front-end date select - theme: Storefront
7. Additional checkout fields - theme: Storefront

== Changelog ==

= 2.0.4 - 2018-05-31
- Changed registration add to cart template according to WooCommerce 3.4
- Removed data store unused classes
- Fix WC_Product_Registration properties default values from array() to null, to be compatible with WooCoommerce 3.4
- Fix stock_status metabox visibility when editing registration product, preventing field to be visible on manage stock uncheck

= 2.0.3 - 2018-02-17
- Fix registrations reports: fixed undefined index in class-wc-report-list-registration.php

= 2.0.2 - 2017-10-30
- Fix registrations reports (contributor: @brettmhoffman)

= 2.0.1 - 2017-06-12 =
- Fixed bad ! empty statement that causes problems prior to PHP 5.5

= 2.0.0 - 2017-06-12 =
- WooCommerce 3.0 compatibility

= 1.0.6 - 2017-01-25 =
- Fixed checkout that won't validate participant fields
- Fixed dates attribute name translation trough filtering

= 1.0.5 - 2016-01-04 =
- Fixed url in WooCommece install notifications
- Fixed text-domians for some strings ('Clear Selection', 'Participant %u')

= 1.0.4 - 2016-11-11 =
- Fixed Text Domain in plugin header (removed hyphen)
- Fixed call to undefined callback registration_variation_option_name_additional_information in WC_Registrations_Admin
- Fixed verification if participant POST field is defined

= 1.0.3 - 2016-10-14 =
- Fixed .pot file, adding correct path and gettext config
- Fixed .po from pt_BR translation changed language from "en_US" to "pt_BR"
- Add gettext function to tweet text
- Fixed instalation tweet url, pointing to wordprees.org plugin page
- Added support of jQuery UI Datepicker for browsers that don't support default HTML5 input date

= 1.0.2 - 2016-06-10 =
* Changed text-domain to match plugin slug (registrations-for-woocommerce)

= 1.0.1 - 2016-06-10 =
* Filtering dates display on additional information tab
* Fixed variations reload on modify dates
* Fixed translation files
