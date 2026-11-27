<?php
/**
 * Admin settings screen.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Admin;

use DateTimeImmutable;
use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\Formatter;
use EthiopianCalendar\Dates;
use EthiopianCalendar\Plugin;
use EthiopianCalendar\Settings;

/**
 * The Ethiopian Calendar settings screen, built on the Settings API.
 *
 * Every date on the page is formatted in PHP. Preset previews are rendered for
 * each language and numeral combination up front; custom formats are previewed
 * through a small AJAX call. The form is plain radios and checkboxes, so it
 * works without JavaScript.
 */
final class SettingsPage {

	public const SLUG         = 'ethiopian-calendar';
	private const AJAX_ACTION = 'ethiopian_calendar_preview';

	/**
	 * Admin menu icon: a calendar page with a "today" dot, drawn for WordPress to recolour.
	 */
	private const MENU_ICON = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" fill-rule="evenodd" d="M5 2a1 1 0 0 1 2 0v1h6V2a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h1V2Zm-1 5v9h12V7H4Zm8.5 5.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z"/></svg>';

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'addMenu' ) );
		add_action( 'admin_init', array( self::class, 'registerSetting' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( self::class, 'ajaxPreview' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ETHIOPIAN_CALENDAR_FILE ), array( self::class, 'actionLinks' ) );
	}

	/**
	 * Add the top-level menu (kept from 1.x so users find it where it was).
	 */
	public static function addMenu(): void {
		add_menu_page(
			__( 'Ethiopian Calendar', 'ethiopian-calendar' ),
			__( 'Ethiopian Calendar', 'ethiopian-calendar' ),
			'manage_options',
			self::SLUG,
			array( self::class, 'render' ),
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- data URI for the menu icon.
			'data:image/svg+xml;base64,' . base64_encode( self::MENU_ICON )
		);
	}

	/**
	 * Register the option with its sanitizer.
	 */
	public static function registerSetting(): void {
		register_setting(
			self::SLUG,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => static fn( $input ) => Settings::sanitize( is_array( $input ) ? $input : array() ),
			)
		);
	}

	/**
	 * Load the screen's CSS and JS on this screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueueAssets( string $hook ): void {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'ethiopian-calendar-admin', Plugin::url( 'assets/css/admin.css' ), array(), Plugin::VERSION );
		wp_enqueue_script( 'ethiopian-calendar-admin', Plugin::url( 'assets/js/admin.js' ), array(), Plugin::VERSION, array( 'in_footer' => true ) );
		wp_localize_script(
			'ethiopian-calendar-admin',
			'ethiopianCalendarAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => wp_create_nonce( self::AJAX_ACTION ),
				'copied'  => __( 'Copied', 'ethiopian-calendar' ),
				'copy'    => __( 'Copy', 'ethiopian-calendar' ),
				'dirty'   => __( 'You have unsaved changes', 'ethiopian-calendar' ),
				'clean'   => __( 'All changes saved', 'ethiopian-calendar' ),
			)
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public static function actionLinks( array $links ): array {
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ), esc_html__( 'Settings', 'ethiopian-calendar' ) ) );
		return $links;
	}

	/**
	 * AJAX: preview a custom format for today and the sample post.
	 */
	public static function ajaxPreview(): void {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$format    = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : '';
		$language  = isset( $_POST['language'] ) ? sanitize_key( wp_unslash( $_POST['language'] ) ) : 'en';
		$numerals  = isset( $_POST['numerals'] ) ? sanitize_key( wp_unslash( $_POST['numerals'] ) ) : 'arabic';
		$formatter = new Formatter( $language, $numerals );
		$sample    = self::samplePost();
		wp_send_json_success(
			array(
				'today'  => self::formatTime( $formatter, $format, current_datetime() ),
				'sample' => self::formatTime( $formatter, $format, $sample['time'] ),
			)
		);
	}

	/**
	 * Render the screen.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = Settings::all();
		$name     = Settings::OPTION;
		$now      = current_datetime();
		$sample   = self::samplePost();
		$am       = new Formatter( 'am' );
		$en       = new Formatter( 'en' );

		// Every preset for every language × numerals, for today and for the sample post.
		$previews = array();
		foreach ( Formatter::LANGUAGES as $language ) {
			foreach ( Formatter::NUMERALS as $numerals ) {
				$formatter = new Formatter( $language, $numerals );
				foreach ( Settings::PRESETS as $key => $preset ) {
					$previews[ $language ][ $numerals ][ $key ] = array(
						'today'  => self::formatTime( $formatter, $preset[ $language ], $now ),
						'sample' => self::formatTime( $formatter, $preset[ $language ], $sample['time'] ),
						'format' => $preset[ $language ],
					);
				}
			}
		}
		$current        = $previews[ $settings['language'] ][ $settings['numerals'] ];
		$sample_preview = Dates::format( $sample['time'] );
		$gregorian      = array(
			'today'  => Dates::gregorian( $now ),
			'sample' => Dates::gregorian( $sample['time'] ),
		);
		$surfaces       = array(
			'post_date'     => array( __( 'Post published dates', 'ethiopian-calendar' ), __( 'Blog lists, single posts and archive pages', 'ethiopian-calendar' ) ),
			'modified_date' => array( __( 'Post updated dates', 'ethiopian-calendar' ), __( '"Last updated" lines, where your theme shows them', 'ethiopian-calendar' ) ),
			'comment_date'  => array( __( 'Comment dates', 'ethiopian-calendar' ), __( 'Under each comment', 'ethiopian-calendar' ) ),
		);
		?>
		<div class="wrap ethcal-admin">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Ethiopian Calendar', 'ethiopian-calendar' ); ?></h1>
			<?php settings_errors(); ?>

			<header class="ethcal-hero">
				<div class="ethcal-hero__today">
					<p class="ethcal-eyebrow"><?php esc_html_e( 'Ethiopian Calendar · Today', 'ethiopian-calendar' ); ?></p>
					<p class="ethcal-hero__date" lang="am"><?php echo esc_html( $am->format( 'l፣ F j ቀን Y', EthiopianDate::fromDateTime( $now ) ) ); ?></p>
					<p class="ethcal-hero__sub">
						<?php echo esc_html( $en->format( 'l, jS F Y E', EthiopianDate::fromDateTime( $now ) ) ); ?> —
						<strong><?php echo esc_html( wp_date( 'F j, Y' ) ); ?></strong>
					</p>
				</div>
				<aside class="ethcal-preview" aria-live="polite">
					<p class="ethcal-preview__label"><?php esc_html_e( 'How readers see a post', 'ethiopian-calendar' ); ?></p>
					<p class="ethcal-preview__title"><?php echo esc_html( $sample['title'] ); ?></p>
					<p class="ethcal-preview__date" data-ethcal-sample><?php echo esc_html( $sample_preview ); ?></p>
					<p class="ethcal-preview__note">
						<?php
						/* translators: %s: the sample post's date in the Gregorian calendar. */
						printf( esc_html__( 'Feeds and Google still see %s', 'ethiopian-calendar' ), esc_html( wp_date( 'M j, Y', $sample['time']->getTimestamp() ) ) );
						?>
					</p>
				</aside>
			</header>

			<form method="post" action="options.php" class="ethcal-form" data-ethcal-form>
				<?php settings_fields( self::SLUG ); ?>
				<script type="application/json" data-ethcal-previews><?php echo wp_json_encode( $previews ); ?></script>
				<script type="application/json" data-ethcal-gregorian><?php echo wp_json_encode( $gregorian ); ?></script>

				<section class="ethcal-section">
					<div class="ethcal-section__label">
						<h2><?php esc_html_e( 'Language', 'ethiopian-calendar' ); ?></h2>
						<p><?php esc_html_e( "Month and weekday names, and the numbers they're written with.", 'ethiopian-calendar' ); ?></p>
					</div>
					<div class="ethcal-section__controls ethcal-row">
						<?php
						self::segmented(
							__( 'Names', 'ethiopian-calendar' ),
							"{$name}[language]",
							$settings['language'],
							array(
								'en' => 'English',
								'am' => 'አማርኛ',
							)
						);
						self::segmented(
							__( 'Numbers', 'ethiopian-calendar' ),
							"{$name}[numerals]",
							$settings['numerals'],
							array(
								'arabic' => '1 2 3',
								'geez'   => "፩ ፪ ፫  Ge'ez",
							)
						);
						?>
					</div>
				</section>

				<section class="ethcal-section">
					<div class="ethcal-section__label">
						<h2><?php esc_html_e( 'Date format', 'ethiopian-calendar' ); ?></h2>
						<p><?php esc_html_e( "Shown with today's date. Pick one, or write your own with tokens.", 'ethiopian-calendar' ); ?></p>
					</div>
					<div class="ethcal-section__controls">
						<fieldset class="ethcal-formats">
							<legend class="screen-reader-text"><?php esc_html_e( 'Date format', 'ethiopian-calendar' ); ?></legend>
							<?php foreach ( $current as $key => $preview ) : ?>
								<label class="ethcal-format">
									<input type="radio" name="<?php echo esc_attr( $name ); ?>[preset]" value="<?php echo esc_attr( $key ); ?>" <?php checked( (string) $key, $settings['preset'] ); ?>>
									<span class="ethcal-format__preview" data-ethcal-preset="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preview['today'] ); ?></span>
									<code class="ethcal-format__code" data-ethcal-code="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preview['format'] ); ?></code>
								</label>
							<?php endforeach; ?>
							<div class="ethcal-format ethcal-format--custom">
								<input type="radio" id="ethcal-custom" name="<?php echo esc_attr( $name ); ?>[preset]" value="custom" <?php checked( 'custom', $settings['preset'] ); ?>>
								<label for="ethcal-custom" class="ethcal-format__name"><?php esc_html_e( 'Custom', 'ethiopian-calendar' ); ?></label>
								<span class="ethcal-format__field">
									<input type="text" class="code" name="<?php echo esc_attr( $name ); ?>[custom_format]" value="<?php echo esc_attr( $settings['custom_format'] ); ?>" placeholder="l, F j, Y E" aria-label="<?php esc_attr_e( 'Custom format', 'ethiopian-calendar' ); ?>" data-ethcal-custom>
									<span class="ethcal-format__custom-preview" data-ethcal-custom-preview></span>
								</span>
							</div>
						</fieldset>
						<details class="ethcal-tokens">
							<summary><?php esc_html_e( 'Token reference', 'ethiopian-calendar' ); ?></summary>
							<dl>
								<dt><code>d</code> <code>j</code></dt><dd><?php esc_html_e( 'Day, with or without a leading zero', 'ethiopian-calendar' ); ?></dd>
								<dt><code>m</code> <code>n</code></dt><dd><?php esc_html_e( 'Month number, with or without a leading zero', 'ethiopian-calendar' ); ?></dd>
								<dt><code>F</code> <code>M</code></dt><dd><?php esc_html_e( 'Month name, full or short', 'ethiopian-calendar' ); ?></dd>
								<dt><code>Y</code> <code>y</code></dt><dd><?php esc_html_e( 'Year, four or two digits', 'ethiopian-calendar' ); ?></dd>
								<dt><code>l</code> <code>D</code></dt><dd><?php esc_html_e( 'Weekday, full or short', 'ethiopian-calendar' ); ?></dd>
								<dt><code>S</code></dt><dd><?php esc_html_e( 'English ordinal suffix: st, nd, rd, th', 'ethiopian-calendar' ); ?></dd>
								<dt><code>E</code></dt><dd><?php esc_html_e( 'Era: E.C. or ዓ.ም.', 'ethiopian-calendar' ); ?></dd>
								<dt><code>g:i a</code></dt><dd><?php esc_html_e( 'Time tokens work as in WordPress. Use \ to print a letter as is.', 'ethiopian-calendar' ); ?></dd>
							</dl>
						</details>
					</div>
				</section>

				<section class="ethcal-section">
					<div class="ethcal-section__label">
						<h2><?php esc_html_e( 'Where to show Ethiopian dates', 'ethiopian-calendar' ); ?></h2>
						<p><?php esc_html_e( 'Feeds, sitemaps and search engines always get the Gregorian date.', 'ethiopian-calendar' ); ?></p>
					</div>
					<div class="ethcal-section__controls ethcal-switches">
						<div class="ethcal-row ethcal-switches__mode">
							<?php
							self::segmented(
								__( 'Show', 'ethiopian-calendar' ),
								"{$name}[display]",
								$settings['display'],
								array(
									'ethiopian' => __( 'Ethiopian only', 'ethiopian-calendar' ),
									'both'      => __( 'Ethiopian + Gregorian', 'ethiopian-calendar' ),
								)
							);
							?>
						</div>
						<?php foreach ( $surfaces as $key => [ $label, $help ] ) : ?>
							<label class="ethcal-switch">
								<input type="hidden" name="<?php echo esc_attr( $name ); ?>[convert][<?php echo esc_attr( $key ); ?>]" value="0">
								<input type="checkbox" role="switch" name="<?php echo esc_attr( $name ); ?>[convert][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings['convert'][ $key ] ); ?>>
								<span class="ethcal-switch__track" aria-hidden="true"></span>
								<span class="ethcal-switch__text">
									<span class="ethcal-switch__label"><?php echo esc_html( $label ); ?></span>
									<span class="ethcal-switch__help"><?php echo esc_html( $help ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="ethcal-section ethcal-section--last">
					<div class="ethcal-section__label">
						<h2><?php esc_html_e( 'Month calendar', 'ethiopian-calendar' ); ?></h2>
						<p><?php esc_html_e( 'Paste the shortcode into any page or post. Gregorian dates appear under each day.', 'ethiopian-calendar' ); ?></p>
					</div>
					<div class="ethcal-section__controls">
						<div class="ethcal-row">
							<?php
							self::segmented(
								__( 'Names', 'ethiopian-calendar' ),
								"{$name}[calendar_language]",
								$settings['calendar_language'],
								array(
									'en' => 'English',
									'am' => 'አማርኛ',
								)
							);
							self::segmented(
								__( 'Numbers', 'ethiopian-calendar' ),
								"{$name}[calendar_numerals]",
								$settings['calendar_numerals'],
								array(
									'arabic' => '1 2 3',
									'geez'   => "፩ ፪ ፫  Ge'ez",
								)
							);
							?>
						</div>
						<div class="ethcal-shortcode">
							<code data-ethcal-shortcode>[ethcalendar]</code>
							<button type="button" class="ethcal-shortcode__copy" data-ethcal-copy><?php esc_html_e( 'Copy', 'ethiopian-calendar' ); ?></button>
						</div>
					</div>
				</section>

				<div class="ethcal-savebar" data-ethcal-savebar>
					<span class="ethcal-savebar__dot" aria-hidden="true"></span>
					<span class="ethcal-savebar__status" data-ethcal-status aria-live="polite"><?php esc_html_e( 'All changes saved', 'ethiopian-calendar' ); ?></span>
					<button type="reset" class="ethcal-button ethcal-button--quiet" data-ethcal-discard><?php esc_html_e( 'Discard', 'ethiopian-calendar' ); ?></button>
					<button type="submit" class="ethcal-button ethcal-button--primary"><?php esc_html_e( 'Save changes', 'ethiopian-calendar' ); ?></button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * A segmented control: radio inputs styled as buttons.
	 *
	 * @param string                $legend  Visible label.
	 * @param string                $name    Input name.
	 * @param string                $current Selected value.
	 * @param array<string, string> $options Value => label.
	 */
	private static function segmented( string $legend, string $name, string $current, array $options ): void {
		?>
		<fieldset class="ethcal-segmented">
			<legend><?php echo esc_html( $legend ); ?></legend>
			<span class="ethcal-segmented__track">
				<?php foreach ( $options as $value => $label ) : ?>
					<label>
						<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $current ); ?>>
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</span>
		</fieldset>
		<?php
	}

	/**
	 * The most recent published post, or a stand-in, for the preview card.
	 *
	 * @return array{title: string, time: DateTimeImmutable}
	 */
	private static function samplePost(): array {
		$posts = get_posts(
			array(
				'numberposts' => 1,
				'post_status' => 'publish',
			)
		);
		if ( $posts ) {
			$time = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $posts[0]->post_date, wp_timezone() );
			if ( $time ) {
				return array(
					'title' => get_the_title( $posts[0] ),
					'time'  => $time,
				);
			}
		}
		return array(
			'title' => __( 'Your latest post', 'ethiopian-calendar' ),
			'time'  => current_datetime(),
		);
	}

	/**
	 * Format a time with a formatter.
	 *
	 * @param Formatter         $formatter Formatter.
	 * @param string            $format    Format string.
	 * @param DateTimeImmutable $time      Time.
	 */
	private static function formatTime( Formatter $formatter, string $format, DateTimeImmutable $time ): string {
		return $formatter->format( $format, EthiopianDate::fromDateTime( $time ), $time );
	}
}
