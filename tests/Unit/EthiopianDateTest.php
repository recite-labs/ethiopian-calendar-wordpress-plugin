<?php
/**
 * Conversion tests.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Tests\Unit;

use Andegna\DateTimeFactory;
use DateTimeImmutable;
use DateTimeZone;
use EthiopianCalendar\Calendar\EthiopianDate;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EthiopianDateTest extends TestCase {

	/**
	 * Known dates: [gregorian Y-m-d, ethiopian year, month, day, weekday].
	 */
	public static function knownDates(): array {
		return array(
			'Meskel 2019'                 => array( '2026-09-27', 2019, 1, 17, 0 ),
			'Pagume 3, 2018'              => array( '2026-09-08', 2018, 13, 3, 2 ),
			'Genna 2018'                  => array( '2026-01-07', 2018, 4, 29, 3 ),
			'Enkutatash 2019'             => array( '2026-09-11', 2019, 1, 1, 5 ),
			'Pagume 6 in leap year 2019'  => array( '2027-09-11', 2019, 13, 6, 6 ),
			'Enkutatash 2020 after leap'  => array( '2027-09-12', 2020, 1, 1, 0 ),
			'Pagume 6 in leap year 2015'  => array( '2023-09-11', 2015, 13, 6, 1 ),
			'Enkutatash 2016'             => array( '2023-09-12', 2016, 1, 1, 2 ),
			'Adwa 2018'                   => array( '2026-03-02', 2018, 6, 23, 1 ),
			'Timkat 2018'                 => array( '2026-01-19', 2018, 5, 11, 1 ),
			'Gregorian 2000-02-29'        => array( '2000-02-29', 1992, 6, 21, 2 ),
			'Gregorian 1900-03-01 (no leap)' => array( '1900-03-01', 1892, 6, 22, 4 ),
		);
	}

	#[DataProvider( 'knownDates' )]
	public function testKnownDates( string $gregorian, int $year, int $month, int $day, int $weekday ): void {
		[ $gy, $gm, $gd ] = array_map( 'intval', explode( '-', $gregorian ) );
		$date             = EthiopianDate::fromGregorian( $gy, $gm, $gd );

		$this->assertSame( array( $year, $month, $day ), array( $date->year(), $date->month(), $date->day() ) );
		$this->assertSame( $weekday, $date->dayOfWeek() );
		$this->assertSame( array( $gy, $gm, $gd ), ( new EthiopianDate( $year, $month, $day ) )->toGregorian() );
	}

	/**
	 * Every day from 1900 to 2100 must match the andegna/calender library
	 * (used by plugin 1.x) and round-trip back to the same Gregorian date.
	 */
	public function testMatchesAndegnaEveryDay1900To2100(): void {
		$utc  = new DateTimeZone( 'UTC' );
		$day  = new DateTimeImmutable( '1900-01-01', $utc );
		$end  = new DateTimeImmutable( '2100-12-31', $utc );
		$days = 0;

		while ( $day <= $end ) {
			$ours   = EthiopianDate::fromDateTime( $day );
			$theirs = DateTimeFactory::fromDateTime( new \DateTime( $day->format( 'Y-m-d' ), $utc ) );
			$label  = $day->format( 'Y-m-d' );

			$this->assertSame(
				array( $theirs->getYear(), $theirs->getMonth(), $theirs->getDay() ),
				array( $ours->year(), $ours->month(), $ours->day() ),
				$label
			);
			$this->assertSame( $label, vsprintf( '%04d-%02d-%02d', $ours->toGregorian() ), "round trip $label" );

			$day = $day->modify( '+1 day' );
			++$days;
		}

		$this->assertSame( 73414, $days );
	}

	public function testLeapYearsAndMonthLengths(): void {
		$this->assertTrue( EthiopianDate::isLeapYear( 2015 ) );
		$this->assertTrue( EthiopianDate::isLeapYear( 2019 ) );
		$this->assertFalse( EthiopianDate::isLeapYear( 2018 ) );
		$this->assertSame( 30, EthiopianDate::daysInMonth( 2018, 1 ) );
		$this->assertSame( 5, EthiopianDate::daysInMonth( 2018, 13 ) );
		$this->assertSame( 6, EthiopianDate::daysInMonth( 2019, 13 ) );
	}

	public function testRejectsInvalidDates(): void {
		$this->assertFalse( EthiopianDate::isValid( 2018, 13, 6 ) );
		$this->assertFalse( EthiopianDate::isValid( 2018, 14, 1 ) );
		$this->assertFalse( EthiopianDate::isValid( 2018, 1, 31 ) );
		$this->expectException( InvalidArgumentException::class );
		new EthiopianDate( 2018, 13, 6 );
	}

	public function testAddDaysCrossesYearBoundary(): void {
		$last = new EthiopianDate( 2018, 13, 5 );
		$next = $last->addDays( 1 );
		$this->assertSame( array( 2019, 1, 1 ), array( $next->year(), $next->month(), $next->day() ) );
		$back = $next->addDays( -1 );
		$this->assertSame( array( 2018, 13, 5 ), array( $back->year(), $back->month(), $back->day() ) );
	}
}
