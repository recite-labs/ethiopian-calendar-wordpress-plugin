<?php
/**
 * The [ethcalendar] month view, rendered on the server.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Frontend;

use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\Formatter;
use EthiopianCalendar\Plugin;
use EthiopianCalendar\Settings;

/**
 * Renders a month grid using the same PHP engine as the date filters.
 *
 * Usage: [ethcalendar], [ethcalendar language="am" numerals="geez"], [ethcalendar month="2019-1"].
 * The 1.1.x attribute names lang="" and numeral="" still work.
 * Visitors move between months with a query argument, so any number of calendars
 * can share a page and no JavaScript is needed.
 */
final class CalendarShortcode {

	public const TAG        = 'ethcalendar';
	private const QUERY_ARG = 'ethcal_month';
	private const GREGORIAN = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );

	/**
	 * Register the shortcode and its stylesheet (enqueued only when used).
	 */
	public static function register(): void {
		add_shortcode( self::TAG, array( self::class, 'render' ) );
		add_action(
			'wp_enqueue_scripts',
			static function () {
				wp_register_style( 'ethiopian-calendar', Plugin::url( 'assets/css/calendar.css' ), array(), Plugin::VERSION );
			}
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function render( $atts ): string {
		$settings         = Settings::all();
		$atts             = shortcode_atts(
			array(
				'language' => '',
				'numerals' => '',
				'lang'     => '',
				'numeral'  => '',
				'month'    => '',
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);
		$atts['language'] = $atts['language'] ? $atts['language'] : ( $atts['lang'] ? $atts['lang'] : $settings['calendar_language'] );
		$atts['numerals'] = $atts['numerals'] ? $atts['numerals'] : ( $atts['numeral'] ? $atts['numeral'] : $settings['calendar_numerals'] );
		if ( ! in_array( $atts['language'], Formatter::LANGUAGES, true ) ) {
			$atts['language'] = 'en';
		}

		$formatter        = new Formatter( $atts['language'], $atts['numerals'] );
		$today            = EthiopianDate::fromDateTime( current_datetime() );
		[ $year, $month ] = self::requestedMonth( $atts['month'], $today );

		$first    = new EthiopianDate( $year, $month, 1 );
		$days     = EthiopianDate::daysInMonth( $year, $month );
		$last     = new EthiopianDate( $year, $month, $days );
		$previous = $first->addDays( -1 );
		$next     = $last->addDays( 1 );
		$offset   = $first->dayOfWeek();

		wp_enqueue_style( 'ethiopian-calendar' );

		ob_start();
		?>
		<div class="ethcal" lang="<?php echo esc_attr( $atts['language'] ); ?>">
			<div class="ethcal__header">
				<a class="ethcal__nav" href="<?php echo esc_url( self::monthUrl( $previous ) ); ?>" aria-label="<?php esc_attr_e( 'Previous month', 'ethiopian-calendar' ); ?>">&lsaquo;</a>
				<div class="ethcal__title">
					<span class="ethcal__month"><?php echo esc_html( $formatter->monthName( $month ) . ' ' . $formatter->number( $year ) ); ?></span>
					<span class="ethcal__gregorian"><?php echo esc_html( self::gregorianRange( $first, $last ) ); ?></span>
				</div>
				<a class="ethcal__nav" href="<?php echo esc_url( self::monthUrl( $next ) ); ?>" aria-label="<?php esc_attr_e( 'Next month', 'ethiopian-calendar' ); ?>">&rsaquo;</a>
			</div>
			<div class="ethcal__grid" role="grid">
				<?php for ( $w = 0; $w < 7; $w++ ) : ?>
					<div class="ethcal__weekday" role="columnheader"><?php echo esc_html( $formatter->weekdayName( $w, true ) ); ?></div>
				<?php endfor; ?>
				<?php for ( $blank = 0; $blank < $offset; $blank++ ) : ?>
					<div class="ethcal__day ethcal__day--empty"></div>
				<?php endfor; ?>
				<?php
				for ( $d = 1; $d <= $days; $d++ ) :
					$date          = new EthiopianDate( $year, $month, $d );
					$is_today      = $date->toJdn() === $today->toJdn();
					[ , $gm, $gd ] = $date->toGregorian();
					?>
					<div class="ethcal__day<?php echo $is_today ? ' ethcal__day--today' : ''; ?>" role="gridcell"<?php echo $is_today ? ' aria-current="date"' : ''; ?>>
						<span class="ethcal__num"><?php echo esc_html( $formatter->number( $d ) ); ?></span>
						<span class="ethcal__greg"><?php echo esc_html( 1 === $d || 1 === $gd ? self::GREGORIAN[ $gm - 1 ] . ' ' . $gd : (string) $gd ); ?></span>
					</div>
				<?php endfor; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Month to show: query argument, then shortcode attribute, then today.
	 *
	 * @param string        $attribute Shortcode month attribute, "YYYY-M".
	 * @param EthiopianDate $today     Today.
	 * @return int[] [year, month]
	 */
	private static function requestedMonth( string $attribute, EthiopianDate $today ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation.
		$raw = isset( $_GET[ self::QUERY_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_ARG ] ) ) : $attribute;
		if ( preg_match( '/^(\d{1,4})-(\d{1,2})$/', $raw, $m ) && EthiopianDate::isValid( (int) $m[1], (int) $m[2], 1 ) ) {
			return array( (int) $m[1], (int) $m[2] );
		}
		return array( $today->year(), $today->month() );
	}

	/**
	 * Link to the month containing a date.
	 *
	 * @param EthiopianDate $date Date.
	 */
	private static function monthUrl( EthiopianDate $date ): string {
		return add_query_arg( self::QUERY_ARG, $date->year() . '-' . $date->month() );
	}

	/**
	 * Gregorian span of a month, e.g. "Nov 10 – Dec 9, 2026".
	 *
	 * @param EthiopianDate $first First day.
	 * @param EthiopianDate $last  Last day.
	 */
	private static function gregorianRange( EthiopianDate $first, EthiopianDate $last ): string {
		[ $y1, $m1, $d1 ] = $first->toGregorian();
		[ $y2, $m2, $d2 ] = $last->toGregorian();
		$start            = self::GREGORIAN[ $m1 - 1 ] . ' ' . $d1 . ( $y1 !== $y2 ? ', ' . $y1 : '' );
		return $start . ' – ' . self::GREGORIAN[ $m2 - 1 ] . ' ' . $d2 . ', ' . $y2;
	}
}
