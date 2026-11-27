<?php
/**
 * Front-end pieces shared by blocks, shortcodes and the widget.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Frontend;

use EthiopianCalendar\Api\RestController;
use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\EthiopianTime;
use EthiopianCalendar\Calendar\Formatter;
use EthiopianCalendar\Dates;
use EthiopianCalendar\Plugin;
use EthiopianCalendar\Settings;

/**
 * Renders today's date, any date, the Ethiopian clock and the date converter.
 *
 * Everything is rendered in PHP. The clock script only advances the minutes;
 * the converter script calls the REST API, and without JavaScript the
 * converter submits as a normal form and PHP shows the result.
 */
final class Components {

	public const STYLE        = 'ethiopian-calendar-components';
	public const CLOCK_SCRIPT = 'ethiopian-calendar-clock';
	public const CONV_SCRIPT  = 'ethiopian-calendar-converter';

	/**
	 * Register front-end assets (enqueued only when something is rendered).
	 */
	public static function registerAssets(): void {
		wp_register_style( self::STYLE, Plugin::url( 'assets/css/components.css' ), array(), Plugin::VERSION );
		wp_register_script( self::CLOCK_SCRIPT, Plugin::url( 'assets/js/clock.js' ), array(), Plugin::VERSION, array( 'in_footer' => true ) );
		wp_register_script( self::CONV_SCRIPT, Plugin::url( 'assets/js/converter.js' ), array(), Plugin::VERSION, array( 'in_footer' => true ) );
		wp_localize_script( self::CONV_SCRIPT, 'ethiopianCalendarApi', array( 'root' => esc_url_raw( rest_url( RestController::NAMESPACE ) ) ) );
	}

	/**
	 * Today's date.
	 *
	 * @param array<string, mixed> $args language, numerals, format, display.
	 */
	public static function today( array $args = array() ): string {
		return self::date( array_merge( $args, array( 'date' => 'now' ) ) );
	}

	/**
	 * Any date, e.g. date="2026-09-27".
	 *
	 * @param array<string, mixed> $args date, language, numerals, format, display.
	 */
	public static function date( array $args = array() ): string {
		$time = Dates::parse( (string) ( $args['date'] ?? 'now' ) );
		if ( ! $time ) {
			return '';
		}
		wp_enqueue_style( self::STYLE );
		return sprintf(
			'<time class="ethcal-date" datetime="%s"%s>%s</time>',
			esc_attr( $time->format( 'Y-m-d' ) ),
			self::langAttribute( $args ),
			esc_html( Dates::format( $time, $args ) )
		);
	}

	/**
	 * The Ethiopian clock, optionally with today's date.
	 *
	 * @param array<string, mixed> $args language, show_date (bool), numerals, format.
	 */
	public static function clock( array $args = array() ): string {
		$language = self::language( $args );
		$now      = current_datetime();
		wp_enqueue_style( self::STYLE );
		wp_enqueue_script( self::CLOCK_SCRIPT );

		$date = '';
		if ( ! empty( $args['show_date'] ) ) {
			$date = '<span class="ethcal-clock__date">' . esc_html( Dates::format( $now, array_merge( $args, array( 'language' => $language ) ) ) ) . '</span>';
		}
		return sprintf(
			'<span class="ethcal-clock" lang="%1$s" data-ethcal-clock data-offset="%2$d" data-language="%1$s" data-labels="%3$s"><span class="ethcal-clock__time" data-ethcal-clock-time>%4$s</span>%5$s</span>',
			esc_attr( $language ),
			(int) $now->getOffset(),
			esc_attr( (string) wp_json_encode( EthiopianTime::periodLabels( $language ) ) ),
			esc_html( EthiopianTime::fromDateTime( $now )->format( $language ) ),
			$date // Escaped above.
		);
	}

	/**
	 * Two-way date converter.
	 *
	 * @param array<string, mixed> $args language, numerals.
	 */
	public static function converter( array $args = array() ): string {
		$language  = self::language( $args );
		$numerals  = in_array( $args['numerals'] ?? '', Formatter::NUMERALS, true ) ? $args['numerals'] : Settings::all()['numerals'];
		$formatter = new Formatter( $language, $numerals );
		$today     = EthiopianDate::fromDateTime( current_datetime() );
		wp_enqueue_style( self::STYLE );
		wp_enqueue_script( self::CONV_SCRIPT );

		// Without JavaScript the form submits here and the result is rendered below.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET conversion.
		$g_value = isset( $_GET['ethcal_g'] ) ? sanitize_text_field( wp_unslash( $_GET['ethcal_g'] ) ) : current_datetime()->format( 'Y-m-d' );
		$e_year  = isset( $_GET['ethcal_ey'] ) ? absint( $_GET['ethcal_ey'] ) : $today->year();
		$e_month = isset( $_GET['ethcal_em'] ) ? absint( $_GET['ethcal_em'] ) : $today->month();
		$e_day   = isset( $_GET['ethcal_ed'] ) ? absint( $_GET['ethcal_ed'] ) : $today->day();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$to_ethiopian = '';
		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $g_value, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			$to_ethiopian = $formatter->format( 'am' === $language ? 'l፣ F j ቀን Y E' : 'l, F j, Y E', EthiopianDate::fromGregorian( (int) $m[1], (int) $m[2], (int) $m[3] ) );
		}
		$to_gregorian = '';
		if ( EthiopianDate::isValid( $e_year, $e_month, $e_day ) ) {
			[ $gy, $gm, $gd ] = ( new EthiopianDate( $e_year, $e_month, $e_day ) )->toGregorian();
			$to_gregorian     = wp_date( 'l, F j, Y', mktime( 12, 0, 0, $gm, $gd, $gy ) );
		} else {
			$to_gregorian = __( 'That date does not exist.', 'ethiopian-calendar' );
		}

		$id = wp_unique_id( 'ethcal-conv-' );
		ob_start();
		?>
		<div class="ethcal-converter" lang="<?php echo esc_attr( $language ); ?>" data-ethcal-converter data-language="<?php echo esc_attr( $language ); ?>" data-numerals="<?php echo esc_attr( $numerals ); ?>">
			<form class="ethcal-converter__half" method="get" data-direction="to-ethiopian">
				<label for="<?php echo esc_attr( $id ); ?>-g" class="ethcal-converter__label"><?php esc_html_e( 'Gregorian date', 'ethiopian-calendar' ); ?></label>
				<div class="ethcal-converter__inputs">
					<input type="date" id="<?php echo esc_attr( $id ); ?>-g" name="ethcal_g" value="<?php echo esc_attr( $g_value ); ?>" min="1900-01-01" max="2100-12-31" required>
					<noscript><button type="submit"><?php esc_html_e( 'Convert', 'ethiopian-calendar' ); ?></button></noscript>
				</div>
				<output class="ethcal-converter__result" data-ethcal-result aria-live="polite"><?php echo esc_html( $to_ethiopian ); ?></output>
			</form>
			<form class="ethcal-converter__half" method="get" data-direction="to-gregorian">
				<span class="ethcal-converter__label" id="<?php echo esc_attr( $id ); ?>-e"><?php esc_html_e( 'Ethiopian date', 'ethiopian-calendar' ); ?></span>
				<div class="ethcal-converter__inputs" role="group" aria-labelledby="<?php echo esc_attr( $id ); ?>-e">
					<select name="ethcal_em" aria-label="<?php esc_attr_e( 'Month', 'ethiopian-calendar' ); ?>">
						<?php for ( $month = 1; $month <= 13; $month++ ) : ?>
							<option value="<?php echo esc_attr( (string) $month ); ?>" <?php selected( $month, $e_month ); ?>><?php echo esc_html( $formatter->monthName( $month ) ); ?></option>
						<?php endfor; ?>
					</select>
					<input type="number" name="ethcal_ed" value="<?php echo esc_attr( (string) $e_day ); ?>" min="1" max="30" aria-label="<?php esc_attr_e( 'Day', 'ethiopian-calendar' ); ?>" class="ethcal-converter__day">
					<input type="number" name="ethcal_ey" value="<?php echo esc_attr( (string) $e_year ); ?>" min="1800" max="2200" aria-label="<?php esc_attr_e( 'Year', 'ethiopian-calendar' ); ?>" class="ethcal-converter__year">
					<noscript><button type="submit"><?php esc_html_e( 'Convert', 'ethiopian-calendar' ); ?></button></noscript>
				</div>
				<output class="ethcal-converter__result" data-ethcal-result aria-live="polite"><?php echo esc_html( $to_gregorian ); ?></output>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Language from args, falling back to the site setting.
	 *
	 * @param array<string, mixed> $args Args.
	 */
	private static function language( array $args ): string {
		return in_array( $args['language'] ?? '', Formatter::LANGUAGES, true ) ? $args['language'] : Settings::all()['language'];
	}

	/**
	 * The lang="" attribute for the output language.
	 *
	 * @param array<string, mixed> $args Args.
	 */
	private static function langAttribute( array $args ): string {
		return ' lang="' . esc_attr( self::language( $args ) ) . '"';
	}
}
