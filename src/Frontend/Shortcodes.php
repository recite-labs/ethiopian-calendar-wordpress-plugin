<?php
/**
 * Shortcodes for classic themes and page builders.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Frontend;

/**
 * [ethcal_today], [ethcal_date date="2026-09-27"], [ethcal_clock], [ethcal_converter].
 *
 * Common attributes: language="en|am", numerals="arabic|geez", format="1-5 or tokens",
 * display="ethiopian|both". [ethcal_clock show_date="yes"] adds today's date.
 */
final class Shortcodes {

	private const DEFAULTS = array(
		'language' => '',
		'numerals' => '',
		'format'   => '',
		'display'  => '',
	);

	/**
	 * Register the shortcodes.
	 */
	public static function register(): void {
		add_shortcode( 'ethcal_today', array( self::class, 'today' ) );
		add_shortcode( 'ethcal_date', array( self::class, 'date' ) );
		add_shortcode( 'ethcal_clock', array( self::class, 'clock' ) );
		add_shortcode( 'ethcal_converter', array( self::class, 'converter' ) );
	}

	/**
	 * [ethcal_today].
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function today( $atts ): string {
		return Components::today( shortcode_atts( self::DEFAULTS, (array) $atts, 'ethcal_today' ) );
	}

	/**
	 * [ethcal_date date="..."].
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function date( $atts ): string {
		return Components::date( shortcode_atts( self::DEFAULTS + array( 'date' => 'now' ), (array) $atts, 'ethcal_date' ) );
	}

	/**
	 * [ethcal_clock].
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function clock( $atts ): string {
		$atts              = shortcode_atts( self::DEFAULTS + array( 'show_date' => 'no' ), (array) $atts, 'ethcal_clock' );
		$atts['show_date'] = in_array( strtolower( (string) $atts['show_date'] ), array( 'yes', 'true', '1' ), true );
		return Components::clock( $atts );
	}

	/**
	 * [ethcal_converter].
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function converter( $atts ): string {
		return Components::converter( shortcode_atts( self::DEFAULTS, (array) $atts, 'ethcal_converter' ) );
	}
}
