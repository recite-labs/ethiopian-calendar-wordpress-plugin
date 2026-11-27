<?php
/**
 * Classic widget: today's Ethiopian date.
 *
 * @package EthiopianCalendar
 */

namespace EthiopianCalendar\Frontend;

use WP_Widget;

/**
 * For classic themes with widget areas. Block themes use the Ethiopian Date Today block.
 */
final class TodayWidget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'ethiopian_calendar_today',
			__( 'Ethiopian Date Today', 'ethiopian-calendar' ),
			array(
				'description'                 => __( "Today's date in the Ethiopian calendar, with the Ethiopian clock if you like.", 'ethiopian-calendar' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Register the widget.
	 */
	public static function register(): void {
		add_action( 'widgets_init', static fn() => register_widget( self::class ) );
	}

	/**
	 * Front-end output.
	 *
	 * @param array<string, mixed> $args     Sidebar args.
	 * @param array<string, mixed> $instance Settings.
	 */
	public function widget( $args, $instance ): void {
		$title = apply_filters( 'widget_title', $instance['title'] ?? '', $instance, $this->id_base );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- sidebar markup comes from the theme; components escape their own output.
		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}
		echo '<p>' . Components::today( array( 'language' => $instance['language'] ?? '' ) ) . '</p>';
		if ( ! empty( $instance['clock'] ) ) {
			echo '<p>' . Components::clock( array( 'language' => $instance['language'] ?? '' ) ) . '</p>';
		}
		echo $args['after_widget'];
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admin form.
	 *
	 * @param array<string, mixed> $instance Settings.
	 */
	public function form( $instance ): string {
		$title    = $instance['title'] ?? '';
		$language = $instance['language'] ?? '';
		$clock    = ! empty( $instance['clock'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'ethiopian-calendar' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'language' ) ); ?>"><?php esc_html_e( 'Language:', 'ethiopian-calendar' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'language' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'language' ) ); ?>">
				<option value="" <?php selected( '', $language ); ?>><?php esc_html_e( 'Site setting', 'ethiopian-calendar' ); ?></option>
				<option value="en" <?php selected( 'en', $language ); ?>>English</option>
				<option value="am" <?php selected( 'am', $language ); ?>>አማርኛ</option>
			</select>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'clock' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'clock' ) ); ?>" value="1" <?php checked( $clock ); ?>>
			<label for="<?php echo esc_attr( $this->get_field_id( 'clock' ) ); ?>"><?php esc_html_e( 'Show the Ethiopian clock', 'ethiopian-calendar' ); ?></label>
		</p>
		<?php
		return '';
	}

	/**
	 * Save settings.
	 *
	 * @param array<string, mixed> $new_instance New values.
	 * @param array<string, mixed> $old_instance Old values.
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title'    => sanitize_text_field( $new_instance['title'] ?? '' ),
			'language' => in_array( $new_instance['language'] ?? '', array( '', 'en', 'am' ), true ) ? $new_instance['language'] : '',
			'clock'    => ! empty( $new_instance['clock'] ),
		);
	}
}
