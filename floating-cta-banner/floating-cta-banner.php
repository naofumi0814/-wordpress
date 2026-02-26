<?php
/**
 * Plugin Name:       Floating CTA Banner
 * Plugin URI:        https://example.com/floating-cta-banner
 * Description:       スマホ/PCで画面に追尾するCTAバナーを表示します。管理画面から表示位置・サイズ・文言・リンク先・色・表示条件などを設定できます。
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       floating-cta-banner
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FCB_VERSION',     '1.0.0' );
define( 'FCB_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'FCB_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'FCB_OPTION_KEY',  'fcb_settings' );

/* -----------------------------------------------------------------------
 * Load sub-files
 * --------------------------------------------------------------------- */
require_once FCB_PLUGIN_DIR . 'includes/sanitizers.php';
require_once FCB_PLUGIN_DIR . 'includes/admin.php';

/* -----------------------------------------------------------------------
 * Helper: get settings with defaults
 * --------------------------------------------------------------------- */
function fcb_get_settings(): array {
	$defaults = [
		'enable'                => 0,
		'position_desktop'      => 'bottom',
		'position_mobile'       => 'bottom',
		'width_mode'            => 'full',
		'width_fixed_px'        => 400,
		'breakpoint_px'         => 768,
		'main_text'             => '今すぐお問い合わせ',
		'sub_text'              => '',
		'button_text'           => 'お問い合わせ',
		'link_url'              => '',
		'link_target'           => '_self',
		'bg_color'              => '#1a73e8',
		'text_color'            => '#ffffff',
		'button_color'          => '#ff5722',
		'show_on'               => 'all',
		'include_page_ids'      => '',
		'include_category_ids'  => '',
		'hide_for_logged_in'    => 0,
		'show_after_scroll_px'  => 200,
		'show_close_button'     => 1,
		'dismiss_days'          => 7,
		'custom_css'            => '',
	];

	$saved = get_option( FCB_OPTION_KEY, [] );

	return wp_parse_args( $saved, $defaults );
}

/* -----------------------------------------------------------------------
 * Decide whether to output banner on current page
 * Returns 'plugin'|'shortcode'|false
 * --------------------------------------------------------------------- */
function fcb_should_display( array $opts ): string|false {

	// Always render if shortcode is in use (shortcode priority wins)
	if ( did_action( 'fcb_shortcode_rendered' ) ) {
		return 'shortcode';
	}

	if ( empty( $opts['enable'] ) ) {
		return false;
	}

	// Hide for logged-in users
	if ( ! empty( $opts['hide_for_logged_in'] ) && is_user_logged_in() ) {
		return false;
	}

	// Show-on conditions (only on front-end singular/archive checks)
	if ( ! is_admin() ) {
		$show_on = $opts['show_on'];

		if ( 'posts' === $show_on && ! is_singular( 'post' ) ) {
			// Also allow if we're checking category later
			$cats = fcb_parse_id_list( $opts['include_category_ids'] );
			if ( empty( $cats ) ) {
				return false;
			}
			if ( ! is_singular( 'post' ) ) {
				return false;
			}
		}

		if ( 'pages' === $show_on && ! is_singular( 'page' ) ) {
			return false;
		}

		if ( 'specific_pages' === $show_on ) {
			$ids = fcb_parse_id_list( $opts['include_page_ids'] );
			if ( empty( $ids ) || ! is_singular() || ! in_array( get_the_ID(), $ids, true ) ) {
				return false;
			}
		}

		// Category filter (only on posts)
		if ( ! empty( $opts['include_category_ids'] ) && is_singular( 'post' ) ) {
			$cat_ids   = fcb_parse_id_list( $opts['include_category_ids'] );
			$post_cats = wp_get_post_categories( get_the_ID() );
			if ( ! array_intersect( $cat_ids, $post_cats ) ) {
				return false;
			}
		}
	}

	return 'plugin';
}

/* -----------------------------------------------------------------------
 * Parse comma-separated ID string → array of ints
 * --------------------------------------------------------------------- */
function fcb_parse_id_list( string $str ): array {
	if ( '' === trim( $str ) ) {
		return [];
	}
	return array_filter(
		array_map( 'intval', explode( ',', $str ) ),
		fn( $v ) => $v > 0
	);
}

/* -----------------------------------------------------------------------
 * Enqueue assets (only when banner should be displayed)
 * --------------------------------------------------------------------- */
add_action( 'wp_enqueue_scripts', 'fcb_enqueue_assets' );
function fcb_enqueue_assets(): void {
	$opts = fcb_get_settings();

	// Check fast path: if plugin disabled AND no shortcode on this page,
	// skip. Shortcode usage is detected at render time so we enqueue
	// conservatively when shortcode is registered.
	if ( empty( $opts['enable'] ) ) {
		// Still register so shortcode can enqueue manually via wp_enqueue.
		// Assets are only truly needed when shortcode triggers output.
		return;
	}

	fcb_do_enqueue( $opts );
}

function fcb_do_enqueue( array $opts ): void {
	wp_enqueue_style(
		'fcb-style',
		FCB_PLUGIN_URL . 'assets/css/fcb.css',
		[],
		FCB_VERSION
	);

	wp_enqueue_script(
		'fcb-script',
		FCB_PLUGIN_URL . 'assets/js/fcb.js',
		[],
		FCB_VERSION,
		[ 'strategy' => 'defer', 'in_footer' => true ]
	);

	// Pass PHP settings to JS
	wp_localize_script(
		'fcb-script',
		'fcbConfig',
		[
			'scrollPx'      => (int) $opts['show_after_scroll_px'],
			'dismissDays'   => (int) $opts['dismiss_days'],
			'breakpointPx'  => (int) $opts['breakpoint_px'],
			'posDesktop'    => esc_js( $opts['position_desktop'] ),
			'posMobile'     => esc_js( $opts['position_mobile'] ),
			'widthMode'     => esc_js( $opts['width_mode'] ),
			'widthFixedPx'  => (int) $opts['width_fixed_px'],
		]
	);
}

/* -----------------------------------------------------------------------
 * Output banner HTML in wp_footer
 * --------------------------------------------------------------------- */
add_action( 'wp_footer', 'fcb_render_banner', 20 );
function fcb_render_banner(): void {
	$opts   = fcb_get_settings();
	$source = fcb_should_display( $opts );

	if ( false === $source ) {
		return;
	}

	// Ensure assets are loaded even if enqueue was skipped (shortcode path)
	if ( ! wp_style_is( 'fcb-style', 'enqueued' ) ) {
		fcb_do_enqueue( $opts );
	}

	fcb_output_banner_html( $opts );

	// Custom CSS
	if ( ! empty( $opts['custom_css'] ) ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<style id="fcb-custom-css">' . wp_strip_all_tags( $opts['custom_css'] ) . '</style>' . "\n";
	}
}

/* -----------------------------------------------------------------------
 * Build and echo the banner HTML
 * --------------------------------------------------------------------- */
function fcb_output_banner_html( array $opts, array $override = [] ): void {
	$o = wp_parse_args( $override, $opts );

	$main_text    = $o['main_text'];
	$sub_text     = $o['sub_text'];
	$button_text  = $o['button_text'];
	$link_url     = $o['link_url'];
	$link_target  = '_blank' === $o['link_target'] ? '_blank' : '_self';
	$rel          = '_blank' === $link_target ? ' rel="noopener noreferrer"' : '';
	$show_close   = ! empty( $o['show_close_button'] );
	$has_link     = '' !== trim( $link_url );

	$bg_color     = $o['bg_color'];
	$text_color   = $o['text_color'];
	$button_color = $o['button_color'];

	// Inline style for the wrapper
	$inline_style = sprintf(
		'--fcb-bg:%s;--fcb-color:%s;--fcb-btn-bg:%s;',
		esc_attr( $bg_color ),
		esc_attr( $text_color ),
		esc_attr( $button_color )
	);

	?>
	<div id="fcb-banner"
		 class="fcb-banner fcb-hidden"
		 role="region"
		 aria-label="<?php esc_attr_e( 'CTA Banner', 'floating-cta-banner' ); ?>"
		 style="<?php echo esc_attr( $inline_style ); ?>"
		 data-dismiss-days="<?php echo esc_attr( (string) (int) $o['dismiss_days'] ); ?>"
	>
		<?php if ( $show_close ) : ?>
		<button class="fcb-close"
				type="button"
				aria-label="<?php esc_attr_e( '閉じる', 'floating-cta-banner' ); ?>"
		>&times;</button>
		<?php endif; ?>

		<div class="fcb-inner">
			<?php if ( $has_link && '' === trim( $button_text ) ) : ?>
				<?php /* Full-banner link when no button text */ ?>
				<a class="fcb-link-wrap"
				   href="<?php echo esc_url( $link_url ); ?>"
				   target="<?php echo esc_attr( $link_target ); ?>"
				   <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<?php fcb_banner_content( $main_text, $sub_text ); ?>
				</a>
			<?php else : ?>
				<div class="fcb-content-wrap">
					<?php fcb_banner_content( $main_text, $sub_text ); ?>
				</div>
				<?php if ( $has_link && '' !== trim( $button_text ) ) : ?>
				<a class="fcb-btn"
				   href="<?php echo esc_url( $link_url ); ?>"
				   target="<?php echo esc_attr( $link_target ); ?>"
				   <?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				><?php echo esc_html( $button_text ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/* -----------------------------------------------------------------------
 * Helper: banner text content
 * --------------------------------------------------------------------- */
function fcb_banner_content( string $main_text, string $sub_text ): void {
	?>
	<div class="fcb-texts">
		<?php if ( '' !== $main_text ) : ?>
		<span class="fcb-main-text"><?php echo esc_html( $main_text ); ?></span>
		<?php endif; ?>
		<?php if ( '' !== $sub_text ) : ?>
		<span class="fcb-sub-text"><?php echo esc_html( $sub_text ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

/* -----------------------------------------------------------------------
 * Shortcode: [floating_cta_banner]
 * --------------------------------------------------------------------- */
add_shortcode( 'floating_cta_banner', 'fcb_shortcode_handler' );
function fcb_shortcode_handler( $atts ): string {
	$opts = fcb_get_settings();

	// Allowed overrides via shortcode attributes
	$allowed = [
		'main_text', 'sub_text', 'button_text', 'link_url',
		'link_target', 'bg_color', 'text_color', 'button_color',
		'show_close_button', 'dismiss_days',
	];

	$defaults = [];
	foreach ( $allowed as $key ) {
		$defaults[ $key ] = $opts[ $key ] ?? '';
	}

	$atts = shortcode_atts( $defaults, $atts, 'floating_cta_banner' );

	// Enqueue assets if not done yet
	if ( ! wp_style_is( 'fcb-style', 'enqueued' ) ) {
		fcb_do_enqueue( $opts );
	}

	// Signal that shortcode rendered (used by fcb_should_display)
	do_action( 'fcb_shortcode_rendered' );

	// Capture output
	ob_start();
	fcb_output_banner_html( $opts, $atts );
	return (string) ob_get_clean();
}

/* -----------------------------------------------------------------------
 * Activation: set default options
 * --------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'fcb_activate' );
function fcb_activate(): void {
	if ( false === get_option( FCB_OPTION_KEY ) ) {
		$defaults = fcb_get_settings();
		add_option( FCB_OPTION_KEY, $defaults );
	}
}

/* -----------------------------------------------------------------------
 * Deactivation: (nothing destructive – keep settings)
 * --------------------------------------------------------------------- */
register_deactivation_hook( __FILE__, 'fcb_deactivate' );
function fcb_deactivate(): void {
	// Intentionally empty – preserve settings on deactivation.
}
