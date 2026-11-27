<?php
/**
 * Admin settings screen.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Admin;

use EthiopianCalendar\Calendar\EthiopianDate;
use EthiopianCalendar\Calendar\Formatter;
use EthiopianCalendar\Settings;

/**
 * Settings → Ethiopian Calendar, built on the Settings API.
 */
final class SettingsPage {

	public const SLUG = 'ethiopian-calendar';

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'addMenu' ) );
		add_action( 'admin_init', array( self::class, 'registerSetting' ) );
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
			'dashicons-calendar-alt'
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
	 * Render the screen.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings  = Settings::all();
		$now       = current_datetime();
		$today     = EthiopianDate::fromDateTime( $now );
		$formatter = Settings::formatter( $settings );
		$name      = Settings::OPTION;
		$surfaces  = array(
			'post_date'     => __( 'Post published dates', 'ethiopian-calendar' ),
			'modified_date' => __( 'Post updated dates', 'ethiopian-calendar' ),
			'comment_date'  => __( 'Comment dates', 'ethiopian-calendar' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ethiopian Calendar', 'ethiopian-calendar' ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: %s: today's date in the Ethiopian calendar. */
					esc_html__( 'Today: %s', 'ethiopian-calendar' ),
					'<strong>' . esc_html( $formatter->format( Settings::formatString( $settings ), $today, $now ) ) . '</strong>'
				);
				?>
			</p>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( self::SLUG ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Language', 'ethiopian-calendar' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[language]" value="en" <?php checked( 'en', $settings['language'] ); ?>> English</label><br>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[language]" value="am" <?php checked( 'am', $settings['language'] ); ?>> አማርኛ (Amharic)</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Numerals', 'ethiopian-calendar' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[numerals]" value="arabic" <?php checked( 'arabic', $settings['numerals'] ); ?>> 1 2 3 (<?php esc_html_e( 'Arabic', 'ethiopian-calendar' ); ?>)</label><br>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[numerals]" value="geez" <?php checked( 'geez', $settings['numerals'] ); ?>> ፩ ፪ ፫ (<?php esc_html_e( "Ge'ez", 'ethiopian-calendar' ); ?>)</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Date format', 'ethiopian-calendar' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( Settings::PRESETS as $key => $preset ) : ?>
									<label>
										<input type="radio" name="<?php echo esc_attr( $name ); ?>[preset]" value="<?php echo esc_attr( $key ); ?>" <?php checked( (string) $key, $settings['preset'] ); ?>>
										<span style="display:inline-block;min-width:16em"><?php echo esc_html( $formatter->format( $preset[ $settings['language'] ], $today, $now ) ); ?></span>
										<code><?php echo esc_html( $preset[ $settings['language'] ] ); ?></code>
									</label><br>
								<?php endforeach; ?>
								<label>
									<input type="radio" name="<?php echo esc_attr( $name ); ?>[preset]" value="custom" <?php checked( 'custom', $settings['preset'] ); ?>>
									<?php esc_html_e( 'Custom:', 'ethiopian-calendar' ); ?>
									<input type="text" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[custom_format]" value="<?php echo esc_attr( $settings['custom_format'] ); ?>" placeholder="l, F j, Y E">
								</label>
								<?php if ( 'custom' === $settings['preset'] && '' !== $settings['custom_format'] ) : ?>
									<p><?php esc_html_e( 'Preview:', 'ethiopian-calendar' ); ?> <strong><?php echo esc_html( $formatter->format( $settings['custom_format'], $today, $now ) ); ?></strong></p>
								<?php endif; ?>
								<p class="description">
									<?php esc_html_e( 'Tokens: d j (day), m n (month number), F M (month name), Y y (year), l D (weekday), S (st/nd/rd/th), E (era: E.C. or ዓ.ም.). Time tokens such as g:i a work as in WordPress. Use \ to print a token letter literally.', 'ethiopian-calendar' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show Ethiopian dates for', 'ethiopian-calendar' ); ?></th>
						<td>
							<fieldset>
								<?php foreach ( $surfaces as $key => $label ) : ?>
									<input type="hidden" name="<?php echo esc_attr( $name ); ?>[convert][<?php echo esc_attr( $key ); ?>]" value="0">
									<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[convert][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $settings['convert'][ $key ] ); ?>> <?php echo esc_html( $label ); ?></label><br>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'Machine-readable dates (feeds, sitemaps, schema.org, datetime attributes) always stay Gregorian.', 'ethiopian-calendar' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Calendar shortcode', 'ethiopian-calendar' ); ?></th>
						<td>
							<code>[ethcalendar]</code> &nbsp; <code>[ethcalendar language="am" numerals="geez"]</code>
							<p class="description"><?php esc_html_e( 'Shows a month calendar with Gregorian dates alongside.', 'ethiopian-calendar' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
