<?php
/**
 * Converts WordPress display dates to the Ethiopian calendar.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Frontend;

use DateTimeImmutable;
use EthiopianCalendar\Dates;
use EthiopianCalendar\Settings;

/**
 * Hooks into post, modified and comment date filters.
 *
 * Only human-readable output is converted. Machine-readable formats such as
 * 'c' or 'U' (used for datetime attributes, feeds and schema.org) pass through.
 */
final class DateFilters {

	/**
	 * Formats that must stay Gregorian.
	 */
	private const MACHINE_FORMATS = array( 'c', 'r', 'U', 'G', 'u', 'v', DATE_ATOM, DATE_RFC2822, DATE_RFC3339, DATE_RSS, DATE_W3C, 'Y-m-d\TH:i:sP', 'Y-m-d H:i:s', 'Y-m-d' );

	/**
	 * Register filters according to the settings.
	 */
	public static function register(): void {
		$settings = Settings::all();
		if ( $settings['convert']['post_date'] ) {
			add_filter( 'get_the_date', array( self::class, 'filterPostDate' ), 10, 3 );
		}
		if ( $settings['convert']['modified_date'] ) {
			add_filter( 'get_the_modified_date', array( self::class, 'filterModifiedDate' ), 10, 3 );
		}
		if ( $settings['convert']['comment_date'] ) {
			add_filter( 'get_comment_date', array( self::class, 'filterCommentDate' ), 10, 3 );
			add_filter( 'render_block_core/comment-date', array( self::class, 'filterCommentDateBlock' ), 10, 2 );
		}
		if ( $settings['convert']['post_date'] || $settings['convert']['modified_date'] ) {
			add_filter( 'render_block_core/post-date', array( self::class, 'filterPostDateBlock' ), 10, 2 );
		}
	}

	/**
	 * Post Date block (block themes). Since WordPress 6.9 it formats an ISO datetime
	 * with wp_date() instead of calling get_the_date(), so its text is rewritten here.
	 *
	 * @param string               $content Rendered block.
	 * @param array<string, mixed> $block   Parsed block.
	 */
	public static function filterPostDateBlock( string $content, array $block ): string {
		$attrs    = $block['attrs'] ?? array();
		$modified = str_contains( $content, 'wp-block-post-date__modified-date' ) || 'modified' === ( $attrs['displayType'] ?? '' );
		$enabled  = Settings::all()['convert'][ $modified ? 'modified_date' : 'post_date' ];
		return $enabled ? self::rewriteTimeTag( $content, $attrs ) : $content;
	}

	/**
	 * Comment Date block (block themes).
	 *
	 * @param string               $content Rendered block.
	 * @param array<string, mixed> $block   Parsed block.
	 */
	public static function filterCommentDateBlock( string $content, array $block ): string {
		return self::rewriteTimeTag( $content, $block['attrs'] ?? array() );
	}

	/**
	 * Replace the text of a <time datetime="..."> element with the Ethiopian date.
	 * The datetime attribute itself is left as is.
	 *
	 * @param string               $content Block HTML.
	 * @param array<string, mixed> $attrs   Block attributes.
	 */
	public static function rewriteTimeTag( string $content, array $attrs ): string {
		if ( 'human-diff' === ( $attrs['format'] ?? '' ) ) {
			return $content;
		}
		return (string) preg_replace_callback(
			'#(<time\b[^>]*\bdatetime="([^"]+)"[^>]*>)(.*?)(</time>)#s',
			static function ( array $m ): string {
				$timestamp = strtotime( html_entity_decode( $m[2] ) );
				if ( false === $timestamp ) {
					return $m[0];
				}
				$time = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( wp_timezone() );
				return $m[1] . esc_html( self::formatForDisplay( $time ) ) . $m[4];
			},
			$content
		);
	}

	/**
	 * Ethiopian date in the configured format.
	 *
	 * @param DateTimeImmutable $time Time in the site timezone.
	 */
	public static function formatForDisplay( DateTimeImmutable $time ): string {
		return Dates::format( $time );
	}

	/**
	 * Whether a requested format should be left alone.
	 *
	 * @param string $format Format passed to the date function.
	 */
	public static function isMachineFormat( string $format ): bool {
		/**
		 * Filters the formats that are never converted.
		 *
		 * @param string[] $formats Formats.
		 */
		$formats = apply_filters( 'ethiopian_calendar_passthrough_formats', self::MACHINE_FORMATS );
		return in_array( $format, $formats, true );
	}

	/**
	 * Filter for get_the_date.
	 *
	 * @param string|int        $the_date Formatted date.
	 * @param string            $format   Requested format ('' = site default).
	 * @param \WP_Post|int|null $post    Post.
	 * @return string|int
	 */
	public static function filterPostDate( $the_date, $format, $post ) {
		$post = get_post( $post );
		return $post ? self::convert( $the_date, (string) $format, $post->post_date ) : $the_date;
	}

	/**
	 * Filter for get_the_modified_date.
	 *
	 * @param string|int        $the_date Formatted date.
	 * @param string            $format   Requested format.
	 * @param \WP_Post|int|null $post     Post.
	 * @return string|int
	 */
	public static function filterModifiedDate( $the_date, $format, $post ) {
		$post = get_post( $post );
		return $post ? self::convert( $the_date, (string) $format, $post->post_modified ) : $the_date;
	}

	/**
	 * Filter for get_comment_date.
	 *
	 * @param string|int       $date    Formatted date.
	 * @param string           $format  Requested format.
	 * @param \WP_Comment|null $comment Comment.
	 * @return string|int
	 */
	public static function filterCommentDate( $date, $format, $comment ) {
		return $comment instanceof \WP_Comment ? self::convert( $date, (string) $format, $comment->comment_date ) : $date;
	}

	/**
	 * Convert a local MySQL datetime for display.
	 *
	 * @param string|int $original Value WordPress produced.
	 * @param string     $format   Requested format.
	 * @param string     $local    Local datetime 'Y-m-d H:i:s'.
	 * @return string|int
	 */
	private static function convert( $original, string $format, string $local ) {
		if ( self::isMachineFormat( $format ) || '' === $local || str_starts_with( $local, '0000' ) ) {
			return $original;
		}
		$time = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $local, wp_timezone() );
		if ( ! $time ) {
			return $original;
		}
		return self::formatForDisplay( $time );
	}
}
