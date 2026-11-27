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
* `[ethcalendar]` shortcode: a month calendar with Gregorian dates alongside, in English or Amharic with its own default language and numerals. Override per calendar with `[ethcalendar language="am" numerals="geez"]`.
* Feeds, sitemaps, schema.org data and `datetime` attributes stay Gregorian, so search engines and other tools keep reading your dates correctly.

== Installation ==

1. Install from Plugins → Add New, or upload the zip.
2. Activate.
3. Choose language, numerals and format under the Ethiopian Calendar menu.

== Frequently Asked Questions ==

= Will this change dates in my RSS feed or for Google? =

No. Only dates shown to people are converted. Machine-readable formats are left untouched.

= Can I use my own format? =

Yes. Pick "Custom" and use tokens: d j (day), m n (month number), F M (month name), Y y (year), l D (weekday), S (st/nd/rd/th) and E (era).

== Screenshots ==

1. The month calendar in English and Amharic with Ge'ez numerals.
2. Settings.

== Changelog ==

= 2.0.0 =
* Rebuilt for PHP 8.1+ and WordPress 6.4 to 7.1.
* Fixed: dates disappeared with block themes on WordPress 6.9 and later.
* Fixed: English month names were shifted by one month and Pagume caused an error.
* Fixed: English dates were labelled "A.D"; they now use "E.C.".
* Fixed: PHP warnings before settings were first saved.
* Fixed: machine-readable dates (datetime attributes, schema.org) were converted.
* New: Ge'ez numerals, a weekday format, custom formats, and switches for updated and comment dates.
* New: the calendar shortcode is rendered on the server, supports several calendars per page and Amharic, and shows Gregorian dates.
* Settings from 1.x are carried over automatically, including whether dates were converted and the calendar's language and numerals. The 1.1 shortcode attributes `lang` and `numeral` still work.
* Settings are removed when the plugin is deleted.

= 1.1.1 =
* Calendar language and numeral settings; option to turn date conversion on or off.

= 1.0.0 =
* First release.
