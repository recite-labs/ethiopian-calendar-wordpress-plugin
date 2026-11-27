<?php
/**
 * Ethiopian time of day.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Calendar;

use DateTimeInterface;

/**
 * Ethiopian clock: the day starts at dawn, so hours are counted from 6:00.
 *
 * 7:00 is 1 o'clock in the morning period, noon is 6, 18:00 is 12 in the
 * evening, midnight is 6 at night.
 */
final class EthiopianTime {

	private const PERIODS = array(
		'en' => array(
			'morning'   => 'in the morning',
			'afternoon' => 'in the afternoon',
			'evening'   => 'in the evening',
			'night'     => 'at night',
		),
		'am' => array(
			'morning'   => 'ጠዋት',
			'afternoon' => 'ከሰዓት',
			'evening'   => 'ምሽት',
			'night'     => 'ሌሊት',
		),
	);

	/**
	 * Hour on the Ethiopian clock, 1-12.
	 *
	 * @var int
	 */
	private int $hour;

	/**
	 * Minute.
	 *
	 * @var int
	 */
	private int $minute;

	/**
	 * Period key: morning, afternoon, evening or night.
	 *
	 * @var string
	 */
	private string $period;

	/**
	 * From a standard 24-hour time.
	 *
	 * @param int $hour   Hour 0-23.
	 * @param int $minute Minute 0-59.
	 */
	public function __construct( int $hour, int $minute ) {
		$shifted      = ( ( $hour - 6 ) % 12 + 12 ) % 12;
		$this->hour   = 0 === $shifted ? 12 : $shifted;
		$this->minute = $minute;
		$this->period = self::periodFor( $hour );
	}

	/**
	 * From a DateTime, in its own timezone.
	 *
	 * @param DateTimeInterface $time Time.
	 */
	public static function fromDateTime( DateTimeInterface $time ): self {
		return new self( (int) $time->format( 'G' ), (int) $time->format( 'i' ) );
	}

	/**
	 * Period of the day for a standard hour.
	 *
	 * @param int $hour Hour 0-23.
	 */
	public static function periodFor( int $hour ): string {
		if ( $hour >= 6 && $hour < 12 ) {
			return 'morning';
		}
		if ( $hour >= 12 && $hour < 18 ) {
			return 'afternoon';
		}
		if ( $hour >= 18 ) {
			return 'evening';
		}
		return 'night';
	}

	/**
	 * Period labels for a language (also used by the live clock script).
	 *
	 * @param string $language 'en' or 'am'.
	 * @return array<string, string>
	 */
	public static function periodLabels( string $language ): array {
		return self::PERIODS[ $language ] ?? self::PERIODS['en'];
	}

	/**
	 * Hour 1-12.
	 */
	public function hour(): int {
		return $this->hour;
	}

	/**
	 * Minute.
	 */
	public function minute(): int {
		return $this->minute;
	}

	/**
	 * Period key.
	 */
	public function period(): string {
		return $this->period;
	}

	/**
	 * Display text, e.g. "3:20 in the morning" or "ጠዋት 3:20".
	 *
	 * @param string $language 'en' or 'am'.
	 */
	public function format( string $language = 'en' ): string {
		$clock = sprintf( '%d:%02d', $this->hour, $this->minute );
		$label = self::periodLabels( $language )[ $this->period ];
		return 'am' === $language ? $label . ' ' . $clock : $clock . ' ' . $label;
	}
}
