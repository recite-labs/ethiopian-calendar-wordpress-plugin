<?php
/**
 * Plugin bootstrap.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar;

use EthiopianCalendar\Admin\SettingsPage;
use EthiopianCalendar\Api\RestController;
use EthiopianCalendar\Blocks\Blocks;
use EthiopianCalendar\Frontend\CalendarShortcode;
use EthiopianCalendar\Frontend\DateFilters;
use EthiopianCalendar\Frontend\Shortcodes;
use EthiopianCalendar\Frontend\TodayWidget;

/**
 * Wires the plugin into WordPress.
 */
final class Plugin {

	public const VERSION = '2.0.0';

	/**
	 * Start the plugin. Called on plugins_loaded.
	 */
	public static function boot(): void {
		Settings::maybeMigrate();
		DateFilters::register();
		CalendarShortcode::register();
		Shortcodes::register();
		Blocks::register();
		RestController::register();
		TodayWidget::register();
		if ( is_admin() ) {
			SettingsPage::register();
		}
	}

	/**
	 * Activation hook.
	 */
	public static function activate(): void {
		Settings::maybeMigrate( true );
	}

	/**
	 * URL of a file inside the plugin.
	 *
	 * @param string $path Relative path.
	 */
	public static function url( string $path ): string {
		return plugins_url( $path, ETHIOPIAN_CALENDAR_FILE );
	}
}
