<?php
/**
 * Formatter and Ge'ez numeral tests.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Tests\Unit;

use DateTimeImmutable;
use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\Formatter;
use EthiopianCalendar\Calendar\GeezNumeral;
use Geezify\Geezify;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase {

	public static function formats(): array {
		// Meskel: Sunday, Meskerem 17, 2019 (2026-09-27 10:05).
		return array(
			array( 'en', 'arabic', 'jS F, Y E', '17th Meskerem, 2019 E.C.' ),
			array( 'en', 'arabic', 'd/m/Y', '17/01/2019' ),
			array( 'en', 'arabic', 'l, F j, Y', 'Sunday, Meskerem 17, 2019' ),
			array( 'en', 'arabic', 'D M j y', 'Sun Mes 17 19' ),
			array( 'en', 'arabic', 'F j, Y g:i a', 'Meskerem 17, 2019 10:05 am' ),
			array( 'en', 'arabic', '\Y\e\a\r: Y', 'Year: 2019' ),
			array( 'am', 'arabic', 'F j ቀን Y E', 'መስከረም 17 ቀን 2019 ዓ.ም.' ),
			array( 'am', 'arabic', 'l፣ F j ቀን Y', 'እሑድ፣ መስከረም 17 ቀን 2019' ),
			array( 'am', 'geez', 'F j ቀን Y E', 'መስከረም ፲፯ ቀን ፳፻፲፱ ዓ.ም.' ),
			array( 'am', 'arabic', 'jS F', '17 መስከረም' ),
		);
	}

	#[DataProvider( 'formats' )]
	public function testFormats( string $language, string $numerals, string $format, string $expected ): void {
		$time = new DateTimeImmutable( '2026-09-27 10:05:00' );
		$this->assertSame( $expected, ( new Formatter( $language, $numerals ) )->format( $format, EthiopianDate::fromDateTime( $time ), $time ) );
	}

	public function testAllMonthNamesAreDistinctAndPagumeIsMonth13(): void {
		$formatter = new Formatter( 'en' );
		$names     = array_map( array( $formatter, 'monthName' ), range( 1, 13 ) );
		$this->assertCount( 13, array_unique( $names ) );
		$this->assertSame( 'Meskerem', $names[0] );
		$this->assertSame( 'Pagume', $names[12] );
		$this->assertSame( 'ጳጉሜ', ( new Formatter( 'am' ) )->monthName( 13 ) );
	}

	public function testOrdinalSuffix(): void {
		$this->assertSame(
			array( 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'st', 'nd', 'rd' ),
			array_map( array( Formatter::class, 'ordinalSuffix' ), array( 1, 2, 3, 4, 11, 12, 13, 21, 22, 23 ) )
		);
	}

	public function testUnknownLanguageFallsBackToEnglish(): void {
		$this->assertSame( 'Hidar', ( new Formatter( 'xx', 'nope' ) )->monthName( 3 ) );
	}

	public static function geezNumbers(): array {
		return array(
			array( 1, '፩' ),
			array( 10, '፲' ),
			array( 17, '፲፯' ),
			array( 30, '፴' ),
			array( 100, '፻' ),
			array( 101, '፻፩' ),
			array( 1999, '፲፱፻፺፱' ),
			array( 2019, '፳፻፲፱' ),
			array( 10000, '፼' ),
		);
	}

	#[DataProvider( 'geezNumbers' )]
	public function testGeezNumerals( int $number, string $expected ): void {
		$this->assertSame( $expected, GeezNumeral::from( $number ) );
	}

	/**
	 * Every number from 1 to 99,999 must match the geezify library used by plugin 1.x.
	 */
	public function testGeezMatchesGeezify(): void {
		$geezify = Geezify::create();
		for ( $n = 1; $n < 100000; $n++ ) {
			$this->assertSame( $geezify->toGeez( $n ), GeezNumeral::from( $n ), (string) $n );
		}
	}

	public function testZeroHasNoGeezForm(): void {
		$this->assertSame( '0', GeezNumeral::from( 0 ) );
	}
}
