<?php
/**
 * Template functions for themes.
 *
 * @package EthiopianCalendar
 */

use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Dates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ethiopian_calendar_format' ) ) {
	/**
	 * Format a date in the Ethiopian calendar with the site settings.
	 *
	 * Examples:
	 *
	 *     echo esc_html( ethiopian_calendar_format( get_the_date( 'c' ) ) );
	 *     echo esc_html( ethiopian_calendar_format( 'now', array( 'language' => 'am', 'format' => 'F j ቀን Y' ) ) );
	 *
	 * @param string|int|DateTimeInterface $date Date string, Unix timestamp or DateTime. Default now.
	 * @param array<string, string>        $args Optional: language (en|am), numerals (arabic|geez),
	 *                                           format (preset "1"-"5" or tokens), display (ethiopian|both).
	 * @return string Plain text; escape it for the context you print it in.
	 */
	function ethiopian_calendar_format( $date = 'now', array $args = array() ): string {
		if ( $date instanceof DateTimeInterface ) {
			$time = DateTimeImmutable::createFromInterface( $date )->setTimezone( wp_timezone() );
		} elseif ( is_int( $date ) ) {
			$time = ( new DateTimeImmutable( '@' . $date ) )->setTimezone( wp_timezone() );
		} else {
			$time = Dates::parse( (string) $date );
		}
		return $time ? Dates::format( $time, $args ) : '';
	}
}

if ( ! function_exists( 'ethiopian_calendar_today' ) ) {
	/**
	 * Today's date in the Ethiopian calendar.
	 *
	 * @param array<string, string> $args See ethiopian_calendar_format().
	 */
	function ethiopian_calendar_today( array $args = array() ): string {
		return ethiopian_calendar_format( 'now', $args );
	}
}

if ( ! function_exists( 'ethiopian_calendar_convert' ) ) {
	/**
	 * Convert a Gregorian date to Ethiopian.
	 *
	 * @param int $year  Gregorian year.
	 * @param int $month Gregorian month.
	 * @param int $day   Gregorian day.
	 * @return array{year: int, month: int, day: int}
	 */
	function ethiopian_calendar_convert( int $year, int $month, int $day ): array {
		$date = EthiopianDate::fromGregorian( $year, $month, $day );
		return array(
			'year'  => $date->year(),
			'month' => $date->month(),
			'day'   => $date->day(),
		);
	}
}
