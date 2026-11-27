<?php
/**
 * Settings, migration and passthrough tests.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Tests\Unit;

use EthiopianCalendar\Frontend\DateFilters;
use EthiopianCalendar\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['ethcal_test_options'] = array();
	}

	public function testFreshInstallGetsDefaults(): void {
		Settings::maybeMigrate( true );
		$this->assertSame( Settings::defaults(), get_option( Settings::OPTION ) );
		$this->assertSame( 'jS F, Y E', Settings::formatString() );
	}

	public function testUpgradeFrom110WithSavedSettings(): void {
		// Option as saved by the 1.1.1 settings screen.
		update_option( Settings::LEGACY_OPTION, array( 'cts' => 'y', 'format' => '1', 'language' => 'en', 'cn' => 'arabic', 'cl' => 'en' ) );
		update_option( 'wpp_dismissed', 0 );
		Settings::maybeMigrate();

		$settings = get_option( Settings::OPTION );
		$this->assertSame( 'en', $settings['language'] );
		$this->assertSame( '1', $settings['preset'] );
		$this->assertSame( 'en', $settings['calendar_language'] );
		$this->assertSame( 'arabic', $settings['calendar_numerals'] );
		$this->assertSame( array( 'post_date' => true, 'modified_date' => false, 'comment_date' => false ), $settings['convert'] );
		$this->assertFalse( get_option( Settings::LEGACY_OPTION ) );
		$this->assertFalse( get_option( 'wpp_dismissed' ) );
	}

	public function testUpgradeFrom110WithConversionOffKeepsItOff(): void {
		update_option( Settings::LEGACY_OPTION, array( 'cts' => 'n', 'format' => '3', 'language' => 'am' ) );
		Settings::maybeMigrate();

		$settings = get_option( Settings::OPTION );
		$this->assertFalse( $settings['convert']['post_date'] );
		$this->assertSame( 'am', $settings['language'] );
		$this->assertSame( '3', $settings['preset'] );
		$this->assertSame( 'am', $settings['calendar_language'] );
		$this->assertSame( 'geez', $settings['calendar_numerals'] );
	}

	public function testUpgradeFrom110NeverSavedConvertsNothing(): void {
		Settings::maybeMigrate();

		$settings = get_option( Settings::OPTION );
		$this->assertSame( array( 'post_date' => false, 'modified_date' => false, 'comment_date' => false ), $settings['convert'] );
		$this->assertSame( 'am', $settings['language'] );
		$this->assertSame( '2', $settings['preset'] );
	}

	public function testUpgradeFrom100AlwaysConvertedPostDates(): void {
		update_option( Settings::LEGACY_OPTION, array( 'language' => 'en', 'format' => '4' ) );
		Settings::maybeMigrate();

		$settings = get_option( Settings::OPTION );
		$this->assertTrue( $settings['convert']['post_date'] );
		$this->assertSame( '4', $settings['preset'] );
	}

	public function testMigrationRunsOnce(): void {
		update_option( Settings::OPTION, array_merge( Settings::defaults(), array( 'language' => 'am' ) ) );
		update_option( Settings::LEGACY_OPTION, array( 'language' => 'en', 'format' => '4' ) );
		Settings::maybeMigrate();
		$this->assertSame( 'am', get_option( Settings::OPTION )['language'] );
	}

	public function testSanitizeRejectsBadValues(): void {
		$clean = Settings::sanitize(
			array(
				'language'      => 'fr',
				'numerals'      => 'roman',
				'preset'        => '99',
				'custom_format' => '<script>x</script>F j',
				'convert'       => array( 'post_date' => '0', 'comment_date' => '1', 'evil' => '1' ),
			)
		);
		$this->assertSame( 'en', $clean['language'] );
		$this->assertSame( 'arabic', $clean['numerals'] );
		$this->assertSame( '1', $clean['preset'] );
		$this->assertSame( 'xF j', $clean['custom_format'] );
		$this->assertSame( array( 'post_date' => false, 'modified_date' => false, 'comment_date' => true ), $clean['convert'] );
	}

	public function testCustomFormatIsUsedOnlyWhenSet(): void {
		$settings = Settings::sanitize( array( 'preset' => 'custom', 'custom_format' => 'Y' ) );
		$this->assertSame( 'Y', Settings::formatString( $settings ) );
		$settings = Settings::sanitize( array( 'preset' => 'custom', 'custom_format' => '' ) );
		$this->assertSame( 'jS F, Y E', Settings::formatString( $settings ) );
	}

	public function testMachineFormatsPassThrough(): void {
		foreach ( array( 'c', 'U', 'r', DATE_W3C, 'Y-m-d' ) as $format ) {
			$this->assertTrue( DateFilters::isMachineFormat( $format ), $format );
		}
		foreach ( array( '', 'F j, Y', 'd/m/Y' ) as $format ) {
			$this->assertFalse( DateFilters::isMachineFormat( $format ), $format );
		}
	}
}
