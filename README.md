# Ethiopian Calendar for WordPress

Show post and comment dates in the Ethiopian calendar, in English or Amharic, and add a month calendar with `[ethcalendar]`.
Published on wordpress.org as [ethiopian-calendar](https://wordpress.org/plugins/ethiopian-calendar/). User-facing docs are in [readme.txt](readme.txt).

## Development

Requires PHP 8.1+ and Composer.

```bash
composer install
composer test        # PHPUnit: conversion checked against andegna/calender for every day 1900-2100
composer lint        # WordPress coding standards + PHP compatibility
bin/build.sh         # dist/ethiopian-calendar.zip for release
```

### Layout

| Path | What it does |
| --- | --- |
| `ethiopian-calendar.php` | Plugin header, autoloader, boot |
| `src/Calendar/EthiopianDate.php` | Gregorian ↔ Ethiopian conversion via Julian Day Number |
| `src/Calendar/Formatter.php` | Token formatting in English/Amharic, Arabic/Ge'ez numerals |
| `src/Calendar/GeezNumeral.php` | Ge'ez numerals |
| `src/Settings.php` | Option schema, defaults, sanitizing, 1.x migration |
| `src/Frontend/DateFilters.php` | Converts post, modified and comment dates (classic filters and blocks) |
| `src/Frontend/CalendarShortcode.php` | `[ethcalendar]` month view |
| `src/Admin/SettingsPage.php` | Settings screen (previews rendered in PHP; `assets/js/admin.js` only swaps them in) |
| `assets/css/admin.css`, `assets/js/admin.js` | Settings screen styles and progressive enhancement |
| `.wordpress-org/` | Directory icon, banner and screenshots (deployed to SVN `/assets`, not shipped in the zip) |

Design source: the Paper file "Ethiopian Calendar — WordPress plugin" (settings page, icon, banner).

The plugin has no runtime dependencies; `vendor/` is for development only and is not shipped.

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).
