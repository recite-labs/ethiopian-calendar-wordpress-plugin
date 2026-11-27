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
	 * Default settings for a fresh install.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'schema'        => self::SCHEMA,
			'language'      => 'en',
			'numerals'      => 'arabic',
			'preset'        => '1',
			'custom_format' => '',
			'convert'       => array_fill_keys( self::SURFACES, true ),
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
		if ( isset( $input['convert'] ) && is_array( $input['convert'] ) ) {
			foreach ( self::SURFACES as $surface ) {
				$out['convert'][ $surface ] = ! empty( $input['convert'][ $surface ] );
			}
		}

		return $out;
	}

	/**
	 * Create defaults on first run, or carry over 1.x settings. Safe to call on every load.
	 */
	public static function maybeMigrate(): void {
		$current = get_option( self::OPTION );
		if ( is_array( $current ) && ( $current['schema'] ?? 0 ) >= self::SCHEMA ) {
			return;
		}

		$settings = self::defaults();
		$legacy   = get_option( self::LEGACY_OPTION );
		if ( is_array( $legacy ) ) {
			$settings = self::sanitize(
				array(
					'language' => $legacy['language'] ?? 'en',
					'preset'   => (string) ( $legacy['format'] ?? '1' ),
					// 1.x only converted post dates; keep upgrades from changing other dates silently.
					'convert'  => array( 'post_date' => true ),
				)
			);
			delete_option( self::LEGACY_OPTION );
		}

		update_option( self::OPTION, $settings );
	}
}
