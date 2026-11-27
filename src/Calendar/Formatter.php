<?php
/**
 * Formats Ethiopian dates with PHP-date-style tokens.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Calendar;

use DateTimeInterface;

/**
 * Token-based formatter for Ethiopian dates in English and Amharic.
 *
 * Date tokens are Ethiopian; time tokens (H, i, g, a ...) are taken from the
 * original DateTime unchanged. Supported date tokens:
 *
 *   d  day, two digits        j  day
 *   m  month, two digits      n  month number
 *   F  month name             M  short month name
 *   Y  year                   y  two-digit year
 *   l  weekday name           D  short weekday name
 *   S  English ordinal suffix (st, nd, rd, th; empty in Amharic)
 *   E  era (E.C. / ዓ.ም.)
 *
 * A backslash escapes the next character, as in PHP's date().
 */
final class Formatter {

	public const LANGUAGES = array( 'en', 'am' );
	public const NUMERALS  = array( 'arabic', 'geez' );

	private const MONTHS = array(
		'en' => array( 'Meskerem', 'Tikimt', 'Hidar', 'Tahsas', 'Tir', 'Yekatit', 'Megabit', 'Miyazia', 'Ginbot', 'Sene', 'Hamle', 'Nehase', 'Pagume' ),
		'am' => array( 'መስከረም', 'ጥቅምት', 'ኅዳር', 'ታኅሣሥ', 'ጥር', 'የካቲት', 'መጋቢት', 'ሚያዝያ', 'ግንቦት', 'ሰኔ', 'ሐምሌ', 'ነሐሴ', 'ጳጉሜ' ),
	);

	private const MONTHS_SHORT = array(
		'en' => array( 'Mes', 'Tik', 'Hid', 'Tah', 'Tir', 'Yek', 'Meg', 'Miy', 'Gin', 'Sen', 'Ham', 'Neh', 'Pag' ),
		'am' => array( 'መስከ', 'ጥቅም', 'ኅዳር', 'ታኅሣ', 'ጥር', 'የካቲ', 'መጋቢ', 'ሚያዝ', 'ግንቦ', 'ሰኔ', 'ሐምሌ', 'ነሐሴ', 'ጳጉሜ' ),
	);

	private const WEEKDAYS = array(
		'en' => array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
		'am' => array( 'እሑድ', 'ሰኞ', 'ማክሰኞ', 'ረቡዕ', 'ሐሙስ', 'ዓርብ', 'ቅዳሜ' ),
	);

	private const WEEKDAYS_SHORT = array(
		'en' => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
		'am' => array( 'እሑድ', 'ሰኞ', 'ማክሰ', 'ረቡዕ', 'ሐሙስ', 'ዓርብ', 'ቅዳሜ' ),
	);

	private const ERA = array(
		'en' => 'E.C.',
		'am' => 'ዓ.ም.',
	);

	/**
	 * Output language.
	 *
	 * @var string
	 */
	private string $language;

	/**
	 * Numeral system for numbers in the output.
	 *
	 * @var string
	 */
	private string $numerals;

	/**
	 * Constructor.
	 *
	 * @param string $language 'en' or 'am'.
	 * @param string $numerals 'arabic' or 'geez'.
	 */
	public function __construct( string $language = 'en', string $numerals = 'arabic' ) {
		$this->language = in_array( $language, self::LANGUAGES, true ) ? $language : 'en';
		$this->numerals = in_array( $numerals, self::NUMERALS, true ) ? $numerals : 'arabic';
	}

	/**
	 * Month name.
	 *
	 * @param int  $month Month 1-13.
	 * @param bool $short Short form.
	 */
	public function monthName( int $month, bool $short = false ): string {
		$names = $short ? self::MONTHS_SHORT : self::MONTHS;
		return $names[ $this->language ][ $month - 1 ];
	}

	/**
	 * Weekday name.
	 *
	 * @param int  $weekday 0 (Sunday) to 6.
	 * @param bool $short   Short form.
	 */
	public function weekdayName( int $weekday, bool $short = false ): string {
		$names = $short ? self::WEEKDAYS_SHORT : self::WEEKDAYS;
		return $names[ $this->language ][ $weekday ];
	}

	/**
	 * Number in the configured numeral system.
	 *
	 * @param int $number Number.
	 */
	public function number( int $number ): string {
		return 'geez' === $this->numerals ? GeezNumeral::from( $number ) : (string) $number;
	}

	/**
	 * Format an Ethiopian date.
	 *
	 * @param string                 $format Format string.
	 * @param EthiopianDate          $date   Date.
	 * @param DateTimeInterface|null $time   Source DateTime for time tokens; omitted time tokens print as-is.
	 */
	public function format( string $format, EthiopianDate $date, ?DateTimeInterface $time = null ): string {
		$out    = '';
		$chars  = preg_split( '//u', $format, -1, PREG_SPLIT_NO_EMPTY );
		$length = count( $chars );
		$geez   = 'geez' === $this->numerals;

		for ( $i = 0; $i < $length; $i++ ) {
			$c = $chars[ $i ];
			switch ( $c ) {
				case '\\':
					++$i;
					$out .= $chars[ $i ] ?? '';
					break;
				case 'd':
					$out .= $geez ? $this->number( $date->day() ) : sprintf( '%02d', $date->day() );
					break;
				case 'j':
					$out .= $this->number( $date->day() );
					break;
				case 'm':
					$out .= $geez ? $this->number( $date->month() ) : sprintf( '%02d', $date->month() );
					break;
				case 'n':
					$out .= $this->number( $date->month() );
					break;
				case 'F':
					$out .= $this->monthName( $date->month() );
					break;
				case 'M':
					$out .= $this->monthName( $date->month(), true );
					break;
				case 'Y':
					$out .= $this->number( $date->year() );
					break;
				case 'y':
					$out .= $geez ? $this->number( $date->year() % 100 ) : sprintf( '%02d', $date->year() % 100 );
					break;
				case 'l':
					$out .= $this->weekdayName( $date->dayOfWeek() );
					break;
				case 'D':
					$out .= $this->weekdayName( $date->dayOfWeek(), true );
					break;
				case 'S':
					$out .= 'en' === $this->language ? self::ordinalSuffix( $date->day() ) : '';
					break;
				case 'E':
					$out .= self::ERA[ $this->language ];
					break;
				case 'a':
				case 'A':
				case 'g':
				case 'G':
				case 'h':
				case 'H':
				case 'i':
				case 's':
				case 'T':
				case 'e':
				case 'P':
				case 'O':
					$out .= $time ? $time->format( $c ) : $c;
					break;
				default:
					$out .= $c;
			}
		}

		return $out;
	}

	/**
	 * English ordinal suffix for a day number.
	 *
	 * @param int $n Number.
	 */
	public static function ordinalSuffix( int $n ): string {
		if ( $n % 100 >= 11 && $n % 100 <= 13 ) {
			return 'th';
		}
		return array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' )[ $n % 10 ];
	}
}
