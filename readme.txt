=== Ethiopian Calendar ===
Contributors: deqiq
Tags: ethiopian calendar, ethiopia, amharic, calendar, date
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show post and comment dates in the Ethiopian calendar, in English or Amharic, and add a month calendar to any page.

== Description ==

Ethiopian Calendar converts the dates your site shows into the Ethiopian calendar.

* Post published dates, post updated dates and comment dates, each switchable.
* Works with block themes (Post Date and Comment Date blocks) and classic themes.
* English or Amharic month and weekday names, with Arabic or Ge'ez numerals.
* Five ready-made formats or your own, e.g. "17th Meskerem, 2019 E.C." or "እሑድ፣ መስከረም ፲፯ ቀን ፳፻፲፱".
* Show the Ethiopian date alone, or followed by the Gregorian date: "ኅዳር 18 ቀን 2019 (November 27, 2026)".
* Feeds, sitemaps, schema.org data and `datetime` attributes stay Gregorian, so search engines and other tools keep reading your dates correctly.

**Blocks**

* Ethiopian Date Today
* Ethiopian Clock: the day starts at dawn, so 7:00 is 1 o'clock in the morning ("ጠዋት 1:00").
* Ethiopian Date Converter: visitors convert between Gregorian and Ethiopian dates. Works without JavaScript too.
* Ethiopian Month Calendar, with Gregorian dates under each day.

**Shortcodes** for classic themes and page builders

* `[ethcal_today]` and `[ethcal_date date="2026-09-27"]`
* `[ethcal_clock show_date="yes"]`
* `[ethcal_converter]`
* `[ethcalendar]` month calendar

All take `language="en|am"` and `numerals="arabic|geez"`; date shortcodes also take `format` (1-5 or tokens) and `display="ethiopian|both"`.

**For developers**

* Template functions: `ethiopian_calendar_format( $date, $args )`, `ethiopian_calendar_today()`, `ethiopian_calendar_convert( $y, $m, $d )`.
* REST API: `/wp-json/ethiopian-calendar/v1/convert?date=2026-09-27`, `?ethiopian=2019-1-17`, and `/today`.
* A classic "Ethiopian Date Today" widget.

== Installation ==

1. Install from Plugins → Add New, or upload the zip.
2. Activate.
3. Choose language, numerals and format under the Ethiopian Calendar menu.

== Frequently Asked Questions ==

= Will this change dates in my RSS feed or for Google? =

No. Only dates shown to people are converted. Machine-readable formats are left untouched.

= How does the Ethiopian clock work? =

The Ethiopian day starts at dawn. Hours are counted from 6:00, so 7:00 is 1 o'clock in the morning, noon is 6, and 18:00 is 12 in the evening. The clock uses your site's timezone.

= Can I use my own format? =

Yes. Pick "Custom" and use tokens: d j (day), m n (month number), F M (month name), Y y (year), l D (weekday), S (st/nd/rd/th) and E (era).

== Screenshots ==

1. The month calendar in English and Amharic with Ge'ez numerals.
2. Settings, with a live preview of how dates will look.

== Changelog ==

= 2.0.0 =
* Rebuilt for PHP 8.1+ and WordPress 6.4 to 7.1.
* Fixed: dates disappeared with block themes on WordPress 6.9 and later.
* Fixed: English month names were shifted by one month and Pagume caused an error.
* Fixed: English dates were labelled "A.D"; they now use "E.C.".
* Fixed: PHP warnings before settings were first saved.
* Fixed: machine-readable dates (datetime attributes, schema.org) were converted.
* New: blocks for today's date, the Ethiopian clock, a date converter and the month calendar.
* New: `[ethcal_today]`, `[ethcal_date]`, `[ethcal_clock]` and `[ethcal_converter]` shortcodes, template functions, a REST API and a classic widget.
* New: show the Gregorian date next to the Ethiopian date.
* New: redesigned settings screen with live previews.
* New: Ge'ez numerals, a weekday format, custom formats, and switches for updated and comment dates.
* New: the calendar shortcode is rendered on the server, supports several calendars per page and Amharic, and shows Gregorian dates.
* Settings from 1.x are carried over automatically, including whether dates were converted and the calendar's language and numerals. The 1.1 shortcode attributes `lang` and `numeral` still work.
* Settings are removed when the plugin is deleted.

= 1.1.1 =
* Calendar language and numeral settings; option to turn date conversion on or off.

= 1.0.0 =
* First release.
