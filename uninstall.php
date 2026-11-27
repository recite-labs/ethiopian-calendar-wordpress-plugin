<?php
/**
 * Removes plugin data when the plugin is deleted from the Plugins screen.
 *
 * @package EthiopianCalendar
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ethiopian_calendar_settings' );
delete_option( 'ethiopian-calendar' ); // 1.x option, in case it was never migrated.
