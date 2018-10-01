# Change Log
All the project notable changes will be documented here.

## [Unreleased]
### Added
### Changed
- Improved registrations-for-woocommerce.php inline documentation
### Deprecated
### Removed
### Fixed
### Security

## (2.0.4) - 2018-05-31
### Changed
- Changed registration add to cart template according to WooCommerce 3.4
### Deprecated
### Removed
- Removed data store unused classes
### Fixed
- Fix WC_Product_Registration properties default values from array() to null, to be compatible with WooCoommerce 3.4
- Fix stock_status metabox visibility when editing registration product, preventing field to be visible on manage stock uncheck

## (2.0.3) - 2018-02-17
### Fixed
- Fix registrations reports: fixed undefined index in class-wc-report-list-registration.php

## (2.0.2) - 2017-10-30
### Fixed
- Fix registrations reports (contributor: @brettmhoffman)

## (2.0.1) - 2017-06-12
### Fixed
- Fixed bad ! empty statement that causes problems prior to PHP 5.5

## (2.0.0) - 2017-06-12
### Added
- WooCommerce 3.0 compatibility
- Using varibale product Data_Store
- Fixed lot's of hooks to prettify date variation displays
- Fixed Ajax attributes saving
- Fixed Reports (major issues about new WC3.0 CRUD)

## (1.0.7) - 2017-03-19
### Added
- Prevent registrations from past dates (New option in Inventory tab for Registration product type)
- Add multiple hooks to make easily to add custom participant fields
### Changed
- Changed how participant order meta is stored (serialized data instead of CSV now)
### Fixed
- Prettify dates on order details
- Hidden date attributes in WooCommerce attributes default panel

## (1.0.6) - 2017-01-25
### Added
- Fixed checkout that won't validate participant fields
- Fixed dates attribute name translation trough filtering

## (1.0.5) - 2016-01-04
### Added
- Fixed url in WooCommerce install notifications
- Fixed text-domians for some strings ('Clear Selection', 'Participant %u')

## (1.0.4) - 2016-11-11
### Added
- Fixed Text Domain in plugin header (removed hyphen)
- Fixed call to undefined callback registration_variation_option_name_additional_information in WC_Registrations_Admin
- Fixed verification if participant POST field is defined

## (1.0.3) - 2016-10-14
### Added
- Fixed .pot file, adding correct path and gettext config
- Fixed .po from pt_BR translation changed language from "en_US" to "pt_BR"
- Add gettext function to tweet text
- Fixed instalation tweet url, pointing to wordprees.org plugin page
- Added support of jQuery UI Datepicker for browsers that don't support default HTML5 input date

## (1.0.2) - 2016-06-10
### Added
- Changed text-domain to match plugin slug (registrations-for-woocommerce)

## (1.0.1) - 2016-06-10
### Added
- Filtering dates display on additional information tab
- Fixed variations reload on modify dates
- Fixed translation files

## (1.0.0)
### Added
- Initial Release

[2.0.4]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v2.0.4
[2.0.3]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v2.0.3
[2.0.2]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v2.0.2
[2.0.1]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v2.0.1
[2.0.0]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v2.0.0
[1.0.7]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.7
[1.0.6]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.6
[1.0.5]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.5
[1.0.4]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.4
[1.0.3]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.3
[1.0.2]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.2
[1.0.1]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.1
[1.0.0]: https://github.com/HasteDesign/Registrations-for-WooCommerce/releases/tag/v1.0.0
