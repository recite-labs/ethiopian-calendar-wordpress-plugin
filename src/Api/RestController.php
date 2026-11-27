<?php
/**
 * Read-only REST API.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Api;

use DateTimeImmutable;
use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\EthiopianTime;
use EthiopianCalendar\Calendar\Formatter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Routes under /wp-json/ethiopian-calendar/v1:
 *
 *   GET /convert?date=2026-11-27           Gregorian → Ethiopian
 *   GET /convert?ethiopian=2019-3-18       Ethiopian → Gregorian
 *   GET /today
 *
 * All take optional language (en|am) and numerals (arabic|geez). Public and
 * read-only: the data is the same for every visitor.
 */
final class RestController {

	public const NAMESPACE = 'ethiopian-calendar/v1';

	/**
	 * Hook route registration.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function routes(): void {
		$common = array(
			'language' => array(
				'type'    => 'string',
				'enum'    => Formatter::LANGUAGES,
				'default' => 'en',
			),
			'numerals' => array(
				'type'    => 'string',
				'enum'    => Formatter::NUMERALS,
				'default' => 'arabic',
			),
		);

		register_rest_route(
			self::NAMESPACE,
			'/convert',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'convert' ),
				'permission_callback' => '__return_true',
				'args'                => $common + array(
					'date'      => array(
						'type'    => 'string',
						'pattern' => '^\d{4}-\d{2}-\d{2}$',
					),
					'ethiopian' => array(
						'type'    => 'string',
						'pattern' => '^\d{1,4}-\d{1,2}-\d{1,2}$',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/today',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'today' ),
				'permission_callback' => '__return_true',
				'args'                => $common,
			)
		);
	}

	/**
	 * GET /convert.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function convert( WP_REST_Request $request ) {
		$gregorian = $request->get_param( 'date' );
		$ethiopian = $request->get_param( 'ethiopian' );

		if ( $gregorian ) {
			[ $y, $m, $d ] = array_map( 'intval', explode( '-', $gregorian ) );
			if ( ! checkdate( $m, $d, $y ) ) {
				return new WP_Error( 'ethiopian_calendar_invalid_date', __( 'That Gregorian date does not exist.', 'ethiopian-calendar' ), array( 'status' => 400 ) );
			}
			$date = EthiopianDate::fromGregorian( $y, $m, $d );
		} elseif ( $ethiopian ) {
			[ $y, $m, $d ] = array_map( 'intval', explode( '-', $ethiopian ) );
			if ( ! EthiopianDate::isValid( $y, $m, $d ) ) {
				return new WP_Error( 'ethiopian_calendar_invalid_date', __( 'That Ethiopian date does not exist.', 'ethiopian-calendar' ), array( 'status' => 400 ) );
			}
			$date = new EthiopianDate( $y, $m, $d );
		} else {
			return new WP_Error( 'ethiopian_calendar_missing_date', __( 'Pass date (Gregorian) or ethiopian.', 'ethiopian-calendar' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( self::payload( $date, $request ) );
	}

	/**
	 * GET /today.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function today( WP_REST_Request $request ): WP_REST_Response {
		$now     = current_datetime();
		$time    = EthiopianTime::fromDateTime( $now );
		$payload = self::payload( EthiopianDate::fromDateTime( $now ), $request );

		$payload['time'] = array(
			'hour'   => $time->hour(),
			'minute' => $time->minute(),
			'period' => $time->period(),
			'text'   => $time->format( (string) $request->get_param( 'language' ) ),
		);
		return new WP_REST_Response( $payload );
	}

	/**
	 * Response body for a date.
	 *
	 * @param EthiopianDate   $date    Date.
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>
	 */
	private static function payload( EthiopianDate $date, WP_REST_Request $request ): array {
		$language         = (string) $request->get_param( 'language' );
		$numerals         = (string) $request->get_param( 'numerals' );
		$formatter        = new Formatter( $language, $numerals );
		[ $gy, $gm, $gd ] = $date->toGregorian();
		$noon             = new DateTimeImmutable( sprintf( '%04d-%02d-%02d 12:00:00', $gy, $gm, $gd ), wp_timezone() );

		return array(
			'ethiopian' => array(
				'year'       => $date->year(),
				'month'      => $date->month(),
				'day'        => $date->day(),
				'weekday'    => $date->dayOfWeek(),
				'month_name' => $formatter->monthName( $date->month() ),
				'leap_year'  => EthiopianDate::isLeapYear( $date->year() ),
				'text'       => $formatter->format( 'am' === $language ? 'l፣ F j ቀን Y E' : 'l, F j, Y E', $date ),
			),
			'gregorian' => array(
				'year'  => $gy,
				'month' => $gm,
				'day'   => $gd,
				'iso'   => $noon->format( 'Y-m-d' ),
				'text'  => wp_date( 'l, F j, Y', $noon->getTimestamp() ),
			),
		);
	}
}
