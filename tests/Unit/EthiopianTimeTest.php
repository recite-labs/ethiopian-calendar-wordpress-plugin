<?php
/**
 * Ethiopian clock tests.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Tests\Unit;

use EthiopianCalendar\Calendar\EthiopianTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EthiopianTimeTest extends TestCase {

	public static function times(): array {
		return array(
			'dawn'          => array( 6, 0, '12:00 in the morning', 'ጠዋት 12:00' ),
			'7 am is 1'     => array( 7, 0, '1:00 in the morning', 'ጠዋት 1:00' ),
			'9:20'          => array( 9, 20, '3:20 in the morning', 'ጠዋት 3:20' ),
			'noon is 6'     => array( 12, 0, '6:00 in the afternoon', 'ከሰዓት 6:00' ),
			'5:45 pm'       => array( 17, 45, '11:45 in the afternoon', 'ከሰዓት 11:45' ),
			'6 pm is 12'    => array( 18, 0, '12:00 in the evening', 'ምሽት 12:00' ),
			'11 pm'         => array( 23, 5, '5:05 in the evening', 'ምሽት 5:05' ),
			'midnight is 6' => array( 0, 0, '6:00 at night', 'ሌሊት 6:00' ),
			'3 am is 9'     => array( 3, 30, '9:30 at night', 'ሌሊት 9:30' ),
		);
	}

	#[DataProvider( 'times' )]
	public function testClock( int $hour, int $minute, string $english, string $amharic ): void {
		$time = new EthiopianTime( $hour, $minute );
		$this->assertSame( $english, $time->format( 'en' ) );
		$this->assertSame( $amharic, $time->format( 'am' ) );
	}

	public function testEveryHourMapsToOneToTwelve(): void {
		for ( $h = 0; $h < 24; $h++ ) {
			$hour = ( new EthiopianTime( $h, 0 ) )->hour();
			$this->assertGreaterThanOrEqual( 1, $hour );
			$this->assertLessThanOrEqual( 12, $hour );
		}
	}
}
