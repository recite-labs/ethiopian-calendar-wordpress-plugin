<?php
/**
 * Plugin settings: schema, defaults, sanitizing and migration from 1.x.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar;

use EthiopianCalendar\Calendar\Formatter;

/**
 * Reads and writes the single settings option.
 */
final class Settings {

	public const OPTION        = 'ethiopian_calendar_settings';
	public const LEGACY_OPTION = 'ethiopian-calendar';
	public const SCHEMA        = 2;

	/**
	 * Format presets. Keys 1-4 match the 1.x settings so old choices carry over.
	 */
	public const PRESETS = array(
		'1' => array(
			'en' => 'jS F, Y E',
			'am' => 'F j ቀን Y E',
		),
		'2' => array(
			'en' => 'jS F, Y',
			'am' => 'F j ቀን Y',
		),
		'3' => array(
			'en' => 'd/m/Y E',
			'am' => 'd/m/Y E',
		),
		'4' => array(
			'en' => 'd/m/Y',
			'am' => 'd/m/Y',
		),
		'5' => array(
			'en' => 'l, F j, Y',
			'am' => 'l፣ F j ቀን Y',
		),
	);

	/**
	 * Places where WordPress dates can be converted.
	 */
	public const SURFACES = array( 'post_date', 'modified_date', 'comment_date' );

	/**
	 * Ethiopian date alone, or followed by the Gregorian date in brackets.
	 */
	public const DISPLAY_MODES = array( 'ethiopian', 'both' );

	/**
	 * Default settings for a fresh install.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'schema'            => self::SCHEMA,
			'language'          => 'en',
			'numerals'          => 'arabic',
			'preset'            => '1',
			'custom_format'     => '',
			'convert'           => array_fill_keys( self::SURFACES, true ),
			'calendar_language' => 'en',
			'calendar_numerals' => 'arabic',
			'display'           => 'ethiopian',
		);
	}

	/**
	 * Current settings merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		return self::sanitize( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Format string for the current settings.
	 *
	 * @param array<string, mixed>|null $settings Settings, or null for the saved ones.
	 */
	public static function formatString( ?array $settings = null ): string {
		$settings = $settings ?? self::all();
		if ( 'custom' === $settings['preset'] && '' !== $settings['custom_format'] ) {
			return $settings['custom_format'];
		}
		$preset = self::PRESETS[ $settings['preset'] ] ?? self::PRESETS['1'];
		return $preset[ $settings['language'] ];
	}

	/**
	 * Formatter for the current settings.
	 *
	 * @param array<string, mixed>|null $settings Settings, or null for the saved ones.
	 */
	public static function formatter( ?array $settings = null ): Formatter {
		$settings = $settings ?? self::all();
		return new Formatter( $settings['language'], $settings['numerals'] );
	}

	/**
	 * Validate input into a complete settings array.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$out = self::defaults();

		if ( isset( $input['language'] ) && in_array( $input['language'], Formatter::LANGUAGES, true ) ) {
			$out['language'] = $input['language'];
		}
		if ( isset( $input['numerals'] ) && in_array( $input['numerals'], Formatter::NUMERALS, true ) ) {
			$out['numerals'] = $input['numerals'];
		}
		if ( isset( $input['preset'] ) && ( 'custom' === $input['preset'] || isset( self::PRESETS[ (string) $input['preset'] ] ) ) ) {
			$out['preset'] = (string) $input['preset'];
		}
		if ( isset( $input['custom_format'] ) && is_string( $input['custom_format'] ) ) {
			$out['custom_format'] = trim( wp_strip_all_tags( $input['custom_format'] ) );
		}
		if ( isset( $input['calendar_language'] ) && in_array( $input['calendar_language'], Formatter::LANGUAGES, true ) ) {
			$out['calendar_language'] = $input['calendar_language'];
		}
		if ( isset( $input['calendar_numerals'] ) && in_array( $input['calendar_numerals'], Formatter::NUMERALS, true ) ) {
			$out['calendar_numerals'] = $input['calendar_numerals'];
		}
		if ( isset( $input['display'] ) && in_array( $input['display'], self::DISPLAY_MODES, true ) ) {
			$out['display'] = $input['display'];
		}
		if ( isset( $input['convert'] ) && is_array( $input['convert'] ) ) {
			foreach ( self::SURFACES as $surface ) {
				$out['convert'][ $surface ] = ! empty( $input['convert'][ $surface ] );
			}
		}

		return $out;
	}

	/**
	 * Create or upgrade the settings. Safe to call on every load.
	 *
	 * Upgrades from 1.x keep their behaviour: in 1.1.x, converting post dates was
	 * a setting ("cts") that was off unless turned on, and the defaults were
	 * Amharic, format 2, and an Amharic calendar with Ge'ez numerals.
	 *
	 * @param bool $fresh_install True when called from activation, where a site with
	 *                            no settings at all is a new install rather than a
	 *                            1.1.x site that never saved its settings.
	 */
	public static function maybeMigrate( bool $fresh_install = false ): void {
		$current = get_option( self::OPTION );
		if ( is_array( $current ) && ( $current['schema'] ?? 0 ) >= self::SCHEMA ) {
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION );
		if ( is_array( $legacy ) ) {
			// 1.0.x had no "cts" switch and always converted post dates.
			$post_date = isset( $legacy['cts'] ) ? 'y' === $legacy['cts'] : ! isset( $legacy['cl'] );
			$settings  = self::legacySettings( $legacy, $post_date );
		} elseif ( $fresh_install ) {
			$settings = self::defaults();
		} else {
			// Updated from 1.1.x without ever saving settings: nothing was converted.
			$settings = self::legacySettings( array(), false );
		}

		update_option( self::OPTION, $settings );
		delete_option( self::LEGACY_OPTION );
		delete_option( 'wpp_dismissed' ); // Leftover from the 1.x activation notice.
	}

	/**
	 * Map 1.x settings onto the current schema, using 1.1.x defaults for missing keys.
	 *
	 * @param array<string, mixed> $legacy    1.x option value.
	 * @param bool                 $post_date Whether post dates were converted.
	 * @return array<string, mixed>
	 */
	private static function legacySettings( array $legacy, bool $post_date ): array {
		return self::sanitize(
			array(
				'language'          => $legacy['language'] ?? 'am',
				'preset'            => (string) ( $legacy['format'] ?? '2' ),
				'calendar_language' => $legacy['cl'] ?? 'am',
				'calendar_numerals' => $legacy['cn'] ?? 'geez',
				// 1.x never converted updated or comment dates.
				'convert'           => array( 'post_date' => $post_date ),
			)
		);
	}
}
