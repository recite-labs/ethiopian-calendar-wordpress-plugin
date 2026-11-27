<?php
/**
 * Block registration.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Blocks;

use EthiopianCalendar\Frontend\CalendarShortcode;
use EthiopianCalendar\Frontend\Components;
use EthiopianCalendar\Plugin;

/**
 * Dynamic blocks: metadata in blocks/<name>/block.json, rendering in PHP,
 * one plain-JavaScript editor script (no build step).
 */
final class Blocks {

	public const EDITOR_SCRIPT = 'ethiopian-calendar-blocks';

	/**
	 * Hook registration.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'registerBlocks' ) );
	}

	/**
	 * Register the editor script, front-end assets and the blocks.
	 */
	public static function registerBlocks(): void {
		wp_register_script(
			self::EDITOR_SCRIPT,
			Plugin::url( 'blocks/editor.js' ),
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			Plugin::VERSION,
			true
		);
		wp_set_script_translations( self::EDITOR_SCRIPT, 'ethiopian-calendar', dirname( ETHIOPIAN_CALENDAR_FILE ) . '/languages' );
		Components::registerAssets();

		$dir = dirname( ETHIOPIAN_CALENDAR_FILE ) . '/blocks/';
		register_block_type( $dir . 'today', array( 'render_callback' => array( self::class, 'renderToday' ) ) );
		register_block_type( $dir . 'clock', array( 'render_callback' => array( self::class, 'renderClock' ) ) );
		register_block_type( $dir . 'converter', array( 'render_callback' => array( self::class, 'renderConverter' ) ) );
		register_block_type( $dir . 'calendar', array( 'render_callback' => array( self::class, 'renderCalendar' ) ) );
	}

	/**
	 * Today's date block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 */
	public static function renderToday( array $attributes ): string {
		return self::wrap( Components::today( $attributes ), $attributes );
	}

	/**
	 * Ethiopian clock block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 */
	public static function renderClock( array $attributes ): string {
		return self::wrap(
			Components::clock(
				array(
					'language'  => $attributes['language'] ?? '',
					'show_date' => ! empty( $attributes['showDate'] ),
				)
			),
			$attributes
		);
	}

	/**
	 * Date converter block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 */
	public static function renderConverter( array $attributes ): string {
		return self::wrap( Components::converter( $attributes ), $attributes );
	}

	/**
	 * Month calendar block (the [ethcalendar] view).
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 */
	public static function renderCalendar( array $attributes ): string {
		return self::wrap(
			CalendarShortcode::render(
				array(
					'language' => $attributes['language'] ?? '',
					'numerals' => $attributes['numerals'] ?? '',
				)
			),
			$attributes
		);
	}

	/**
	 * Wrap output with the block's wrapper attributes (colour, typography, spacing, alignment).
	 *
	 * @param string               $html       Inner HTML.
	 * @param array<string, mixed> $attributes Attributes.
	 */
	private static function wrap( string $html, array $attributes ): string {
		$extra = array();
		if ( ! empty( $attributes['textAlign'] ) ) {
			$extra['class'] = 'has-text-align-' . sanitize_html_class( $attributes['textAlign'] );
		}
		return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes( $extra ), $html );
	}
}
