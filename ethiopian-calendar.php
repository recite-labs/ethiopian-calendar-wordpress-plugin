<?php
/**
 * Plugin Name:       Ethiopian Calendar
 * Plugin URI:        https://github.com/recite-labs/ethiopian-calendar-wordpress-plugin
 * Description:       Show post and comment dates in the Ethiopian calendar, in English or Amharic, and add a month calendar with the [ethcalendar] shortcode.
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Kibru Demeke
 * Author URI:        https://github.com/dkibru
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ethiopian-calendar
 * Domain Path:       /languages
 *
 * @package EthiopianCalendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ETHIOPIAN_CALENDAR_FILE', __FILE__ );

// PSR-4 autoloader for EthiopianCalendar\ => src/. The plugin has no runtime Composer dependencies.
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'EthiopianCalendar\\';
		if ( 0 !== strncmp( $class_name, $prefix, strlen( $prefix ) ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( \EthiopianCalendar\Plugin::class, 'activate' ) );
add_action( 'plugins_loaded', array( \EthiopianCalendar\Plugin::class, 'boot' ) );
