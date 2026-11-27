<?php
/**
 * One place that turns a moment into the text readers see.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar;

use DateTimeImmutable;
use DateTimeInterface;
use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\Formatter;
use Exception;

/**
 * Formats dates with the site settings, optionally overridden per call.
 */
final class Dates {

	/**
	 * Format a date for display.
	 *
	 * @param DateTimeInterface    $time      Moment, in the timezone whose calendar day should be used.
	 * @param array<string, mixed> $overrides Optional: language, numerals, format, display.
	 */
	public static function format( DateTimeInterface $time, array $overrides = array() ): string {
		$settings = Settings::all();
		$language = in_array( $overrides['language'] ?? '', Formatter::LANGUAGES, true ) ? $overrides['language'] : $settings['language'];
		$numerals = in_array( $overrides['numerals'] ?? '', Formatter::NUMERALS, true ) ? $overrides['numerals'] : $settings['numerals'];
		$display  = in_array( $overrides['display'] ?? '', Settings::DISPLAY_MODES, true ) ? $overrides['display'] : $settings['display'];
		$format   = self::resolveFormat( (string) ( $overrides['format'] ?? '' ), $language, $settings );

		$text = ( new Formatter( $language, $numerals ) )->format( $format, EthiopianDate::fromDateTime( $time ), $time );
		if ( 'both' === $display ) {
			$text .= ' (' . self::gregorian( $time ) . ')';
		}
		return $text;
	}

	/**
	 * Gregorian date in the site's date format.
	 *
	 * @param DateTimeInterface $time Moment.
	 */
	public static function gregorian( DateTimeInterface $time ): string {
		return (string) wp_date( (string) get_option( 'date_format', 'F j, Y' ), $time->getTimestamp(), $time->getTimezone() );
	}

	/**
	 * Parse a date string ("2026-11-27", "now", "next friday") in the site timezone.
	 *
	 * @param string $value Date string.
	 */
	public static function parse( string $value ): ?DateTimeImmutable {
		$value = trim( $value );
		if ( '' === $value ) {
			return current_datetime();
		}
		try {
			return new DateTimeImmutable( $value, wp_timezone() );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * A format string from a preset key, a custom string, or the site setting.
	 *
	 * @param string               $format   Preset key ("1"-"5"), format string, or ''.
	 * @param string               $language Language.
	 * @param array<string, mixed> $settings Settings.
	 */
	private static function resolveFormat( string $format, string $language, array $settings ): string {
		if ( '' === $format ) {
			if ( 'custom' === $settings['preset'] && '' !== $settings['custom_format'] ) {
				return $settings['custom_format'];
			}
			$format = $settings['preset'];
		}
		if ( isset( Settings::PRESETS[ $format ] ) ) {
			return Settings::PRESETS[ $format ][ $language ];
		}
		return $format;
	}
}
