# Data export phpspreadsheet

This module extends Views Data Export module to allow XLSX files export.
Clone from data export phpexcel to use a library that is
- maintained
- compatible with PHP 7.2.
- option select color for each column

Support format
- Open Document Format/OASIS (.ods)
- Excel 2007 and above (.xlsx)
- Excel 97 and above (.xls)
- SpreadsheetML (.xml) (XML is ready support by Views data export
but not for Excel)

You can insert custom text for header

New feature for field image it will add image directly (just 1 image)

Support reader for service
- Microsoft Symbolic Link (.sylk)
- CSV (ready support by Views data export)
- Gnumeric (can export but i can't test yet)

For a full description of the module, visit the
[project page](https://www.drupal.org/project/views_data_export_phpspreadsheet).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/views_data_export_phpspreadsheet).


## Table of contents

- Installation
- Configuration
- Maintainers

## Installation

You must install with composer, it will add library phpspreadsheet to vendor

`composer require drupal/views_data_export_phpspreadsheet`

For further information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module has no modifiable settings. There is no configuration.


## Maintainers

- NGUYEN Bao - [lazzyvn](https://www.drupal.org/u/lazzyvn)
