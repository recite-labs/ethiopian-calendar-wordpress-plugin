<?php
/**
 * Dates helper and template function tests.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use EthiopianCalendar\Dates;
use EthiopianCalendar\Settings;
use PHPUnit\Framework\TestCase;

final class DatesTest extends TestCase {

	private DateTimeImmutable $meskel;

	protected function setUp(): void {
		$GLOBALS['ethcal_test_options'] = array( 'date_format' => 'F j, Y' );
		$this->meskel                   = new DateTimeImmutable( '2026-09-27 10:00', new DateTimeZone( 'Africa/Addis_Ababa' ) );
	}

	public function testUsesSiteSettings(): void {
		update_option( Settings::OPTION, Settings::sanitize( array( 'language' => 'am', 'preset' => '2' ) ) );
		$this->assertSame( 'መስከረም 17 ቀን 2019', Dates::format( $this->meskel ) );
	}

	public function testDisplayBothAppendsGregorian(): void {
		update_option( Settings::OPTION, Settings::sanitize( array( 'display' => 'both' ) ) );
		$this->assertSame( '17th Meskerem, 2019 E.C. (September 27, 2026)', Dates::format( $this->meskel ) );
	}

	public function testOverridesWinOverSettings(): void {
		update_option( Settings::OPTION, Settings::sanitize( array( 'language' => 'am' ) ) );
		$this->assertSame(
			'Sunday, Meskerem ፲፯, ፳፻፲፱',
			Dates::format( $this->meskel, array( 'language' => 'en', 'numerals' => 'geez', 'format' => '5' ) )
		);
		$this->assertSame( '2019', Dates::format( $this->meskel, array( 'format' => 'Y' ) ) );
	}

	public function testInvalidOverridesFallBack(): void {
		$this->assertSame( '17th Meskerem, 2019 E.C.', Dates::format( $this->meskel, array( 'language' => 'fr', 'display' => 'nope' ) ) );
	}

	public function testParse(): void {
		$this->assertSame( '2026-09-27', Dates::parse( '2026-09-27' )->format( 'Y-m-d' ) );
		$this->assertNull( Dates::parse( 'not a date at all' ) );
	}

	public function testTemplateFunctions(): void {
		$this->assertSame( '17th Meskerem, 2019 E.C.', ethiopian_calendar_format( '2026-09-27' ) );
		$this->assertSame( '17th Meskerem, 2019 E.C.', ethiopian_calendar_format( $this->meskel ) );
		$this->assertSame( '17th Meskerem, 2019 E.C.', ethiopian_calendar_format( $this->meskel->getTimestamp() ) );
		$this->assertSame( array( 'year' => 2019, 'month' => 13, 'day' => 6 ), ethiopian_calendar_convert( 2027, 9, 11 ) );
		$this->assertSame( '', ethiopian_calendar_format( 'garbage date' ) );
	}
}
