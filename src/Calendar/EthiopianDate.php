<?php
/**
 * Ethiopian calendar date value object and conversion.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Calendar;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * An immutable date in the Ethiopian calendar (Amete Mihret era).
 *
 * Conversion goes through the Julian Day Number (JDN), so it is exact for any
 * date and has no dependency on PHP's calendar extension.
 */
final class EthiopianDate {

	/**
	 * JDN of Meskerem 1, year 1 (Amete Mihret).
	 */
	public const EPOCH = 1724221;

	/**
	 * Ethiopian year.
	 *
	 * @var int
	 */
	private int $year;

	/**
	 * Month, 1 (Meskerem) to 13 (Pagume).
	 *
	 * @var int
	 */
	private int $month;

	/**
	 * Day of month, 1 to 30 (1 to 5 or 6 in Pagume).
	 *
	 * @var int
	 */
	private int $day;

	/**
	 * Create a date; throws when the date does not exist.
	 *
	 * @param int $year  Ethiopian year.
	 * @param int $month Month 1-13.
	 * @param int $day   Day of month.
	 * @throws InvalidArgumentException When the date is invalid.
	 */
	public function __construct( int $year, int $month, int $day ) {
		if ( ! self::isValid( $year, $month, $day ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- integers, not output.
			throw new InvalidArgumentException( sprintf( 'Invalid Ethiopian date %d-%d-%d.', $year, $month, $day ) );
		}
		$this->year  = $year;
		$this->month = $month;
		$this->day   = $day;
	}

	/**
	 * Whether the given Ethiopian date exists.
	 *
	 * @param int $year  Ethiopian year.
	 * @param int $month Month.
	 * @param int $day   Day.
	 */
	public static function isValid( int $year, int $month, int $day ): bool {
		return $month >= 1 && $month <= 13 && $day >= 1 && $day <= self::daysInMonth( $year, $month );
	}

	/**
	 * Ethiopian leap years have a 6-day Pagume; they fall the year before a Julian leap year.
	 *
	 * @param int $year Ethiopian year.
	 */
	public static function isLeapYear( int $year ): bool {
		return 3 === self::mod( $year, 4 );
	}

	/**
	 * Number of days in a month.
	 *
	 * @param int $year  Ethiopian year.
	 * @param int $month Month 1-13.
	 */
	public static function daysInMonth( int $year, int $month ): int {
		if ( 13 === $month ) {
			return self::isLeapYear( $year ) ? 6 : 5;
		}
		return 30;
	}

	/**
	 * Convert from a Julian Day Number.
	 *
	 * @param int $jdn Julian Day Number.
	 */
	public static function fromJdn( int $jdn ): self {
		// Count from Meskerem 1 of year 0 so each 4-year cycle ends with its leap year (years 0-3, leap = 3).
		$offset = $jdn - ( self::EPOCH - 365 );
		$r      = self::mod( $offset, 1461 );
		$n      = ( $r % 365 ) + 365 * intdiv( $r, 1460 );
		$year   = 4 * intdiv( $offset - $r, 1461 ) + intdiv( $r, 365 ) - intdiv( $r, 1460 );
		return new self( $year, intdiv( $n, 30 ) + 1, ( $n % 30 ) + 1 );
	}

	/**
	 * Convert from a Gregorian date.
	 *
	 * @param int $year  Gregorian year.
	 * @param int $month Gregorian month.
	 * @param int $day   Gregorian day.
	 */
	public static function fromGregorian( int $year, int $month, int $day ): self {
		return self::fromJdn( self::gregorianToJdn( $year, $month, $day ) );
	}

	/**
	 * Convert from a DateTime, using the calendar date in its own timezone.
	 *
	 * @param DateTimeInterface $date Date.
	 */
	public static function fromDateTime( DateTimeInterface $date ): self {
		return self::fromGregorian( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 'j' ) );
	}

	/**
	 * Julian Day Number of this date.
	 */
	public function toJdn(): int {
		return self::EPOCH - 1 + 365 * ( $this->year - 1 ) + intdiv( $this->year, 4 ) + 30 * ( $this->month - 1 ) + $this->day;
	}

	/**
	 * Gregorian date as [year, month, day].
	 *
	 * @return int[]
	 */
	public function toGregorian(): array {
		return self::jdnToGregorian( $this->toJdn() );
	}

	/**
	 * Year.
	 */
	public function year(): int {
		return $this->year;
	}

	/**
	 * Month 1-13.
	 */
	public function month(): int {
		return $this->month;
	}

	/**
	 * Day of month.
	 */
	public function day(): int {
		return $this->day;
	}

	/**
	 * Day of week, 0 (Sunday) to 6 (Saturday).
	 */
	public function dayOfWeek(): int {
		return self::mod( $this->toJdn() + 1, 7 );
	}

	/**
	 * Date shifted by a number of days.
	 *
	 * @param int $days Days to add (negative to subtract).
	 */
	public function addDays( int $days ): self {
		return self::fromJdn( $this->toJdn() + $days );
	}

	/**
	 * Gregorian (proleptic) date to JDN.
	 *
	 * @param int $y Year.
	 * @param int $m Month.
	 * @param int $d Day.
	 */
	public static function gregorianToJdn( int $y, int $m, int $d ): int {
		$a = intdiv( 14 - $m, 12 );
		$y = $y + 4800 - $a;
		$m = $m + 12 * $a - 3;
		return $d + intdiv( 153 * $m + 2, 5 ) + 365 * $y + intdiv( $y, 4 ) - intdiv( $y, 100 ) + intdiv( $y, 400 ) - 32045;
	}

	/**
	 * JDN to Gregorian (proleptic) date.
	 *
	 * @param int $jdn Julian Day Number.
	 * @return int[] [year, month, day]
	 */
	public static function jdnToGregorian( int $jdn ): array {
		$a = $jdn + 32044;
		$b = intdiv( 4 * $a + 3, 146097 );
		$c = $a - intdiv( 146097 * $b, 4 );
		$d = intdiv( 4 * $c + 3, 1461 );
		$e = $c - intdiv( 1461 * $d, 4 );
		$m = intdiv( 5 * $e + 2, 153 );
		return array(
			100 * $b + $d - 4800 + intdiv( $m, 10 ),
			$m + 3 - 12 * intdiv( $m, 10 ),
			$e - intdiv( 153 * $m + 2, 5 ) + 1,
		);
	}

	/**
	 * Floor modulo (always non-negative for positive divisors).
	 *
	 * @param int $a Dividend.
	 * @param int $b Divisor.
	 */
	private static function mod( int $a, int $b ): int {
		return ( ( $a % $b ) + $b ) % $b;
	}
}
