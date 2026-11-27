<?php
/**
 * PHPUnit bootstrap: Composer autoload plus minimal stand-ins for the few
 * WordPress functions the unit-tested classes call.
 *
 * @package EthiopianCalendar
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';

$GLOBALS['ethcal_test_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return $GLOBALS['ethcal_test_options'][ $name ] ?? $default;
	}
	function update_option( $name, $value ) {
		$GLOBALS['ethcal_test_options'][ $name ] = $value;
		return true;
	}
	function delete_option( $name ) {
		unset( $GLOBALS['ethcal_test_options'][ $name ] );
		return true;
	}
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( (string) $text ) );
	}
	function apply_filters( $hook, $value ) {
		return $value;
	}
}
