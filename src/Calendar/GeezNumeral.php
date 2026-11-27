<?php
/**
 * Ge'ez (Ethiopic) numerals.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Calendar;

/**
 * Converts positive integers to Ethiopic numerals (፩, ፲, ፻, ፼ ...).
 */
final class GeezNumeral {

	private const ONES = array( '', '፩', '፪', '፫', '፬', '፭', '፮', '፯', '፰', '፱' );
	private const TENS = array( '', '፲', '፳', '፴', '፵', '፶', '፷', '፸', '፹', '፺' );

	/**
	 * Convert a number. Zero and negatives have no Ge'ez form and are returned as digits.
	 *
	 * @param int $number Number to convert.
	 */
	public static function from( int $number ): string {
		if ( $number <= 0 ) {
			return (string) $number;
		}

		$digits = (string) $number;
		if ( 1 === strlen( $digits ) % 2 ) {
			$digits = '0' . $digits;
		}
		$groups = str_split( $digits, 2 );
		$count  = count( $groups );
		$out    = '';

		foreach ( $groups as $i => $group ) {
			$pos   = $count - 1 - $i; // 0 = rightmost pair.
			$value = (int) $group;
			$text  = self::TENS[ (int) $group[0] ] . self::ONES[ (int) $group[1] ];

			if ( 0 === $pos ) {
				$out .= $text;
				continue;
			}

			$separator = ( 1 === $pos % 2 ) ? '፻' : '፼';

			if ( 0 === $value ) {
				// An empty pair only needs a ፼ to close a non-empty higher part.
				if ( '፼' === $separator && '' !== $out && ! str_ends_with( $out, '፼' ) ) {
					$out .= '፼';
				}
				continue;
			}

			// A bare 1 is not written before ፻ or a leading ፼ (100 = ፻, 10000 = ፼).
			if ( 1 === $value && ( '' === $out || '፻' === $separator ) ) {
				$text = '';
			}
			$out .= $text . $separator;
		}

		return $out;
	}
}
