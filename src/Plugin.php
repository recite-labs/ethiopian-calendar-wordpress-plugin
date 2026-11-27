<?php
/**
 * Plugin bootstrap.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar;

use EthiopianCalendar\Admin\SettingsPage;
use EthiopianCalendar\Frontend\CalendarShortcode;
use EthiopianCalendar\Frontend\DateFilters;

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
		if ( is_admin() ) {
			SettingsPage::register();
		}
	}

	/**
	 * Activation hook.
	 */
	public static function activate(): void {
		Settings::maybeMigrate();
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
