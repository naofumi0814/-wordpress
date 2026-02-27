<?php
/**
 * Sanitization helpers for Floating CTA Banner plugin.
 *
 * All functions follow WordPress sanitize_* conventions:
 * - Accept raw input.
 * - Return sanitized value, never throw.
 *
 * @package FloatingCTABanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a hex colour string.
 * Returns the default value if the input is not a valid 3- or 6-digit hex colour.
 *
 * @param  string $color   Raw input.
 * @param  string $default Fallback hex colour (with #).
 * @return string          Sanitized hex colour.
 */
function fcb_sanitize_hex_color( string $color, string $default = '#000000' ): string {
	$color = trim( $color );
	if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color ) ) {
		return $color;
	}
	return $default;
}

/**
 * Sanitize a positive integer. Returns $default if the result is <= 0.
 *
 * @param  mixed $value   Raw input.
 * @param  int   $default Fallback value (must be >= 0).
 * @return int
 */
function fcb_sanitize_positive_int( mixed $value, int $default = 0 ): int {
	$int = (int) $value;
	return $int >= 0 ? $int : $default;
}

/**
 * Sanitize a comma-separated list of positive integers.
 * Invalid entries (non-numeric, <= 0) are stripped.
 *
 * @param  mixed $value Raw input.
 * @return string       Cleaned comma-separated string, or ''.
 */
function fcb_sanitize_id_list( mixed $value ): string {
	if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
		return '';
	}
	$parts = explode( ',', (string) $value );
	$clean = [];
	foreach ( $parts as $part ) {
		$id = (int) trim( $part );
		if ( $id > 0 ) {
			$clean[] = $id;
		}
	}
	return implode( ',', $clean );
}

/**
 * Sanitize a select/radio value against an allowed list.
 *
 * @param  mixed    $value   Raw input.
 * @param  string[] $allowed Array of allowed string values.
 * @param  string   $default Default value (must be in $allowed).
 * @return string
 */
function fcb_sanitize_select( mixed $value, array $allowed, string $default ): string {
	$value = (string) $value;
	return in_array( $value, $allowed, true ) ? $value : $default;
}

/**
 * Sanitize the full FCB settings array.
 * Called by the Settings API sanitize callback.
 *
 * @param  mixed $raw Potentially untrusted input from $_POST.
 * @return array      Fully sanitized settings array.
 */
function fcb_sanitize_settings( mixed $raw ): array {
	if ( ! is_array( $raw ) ) {
		$raw = [];
	}

	return [
		// --- Toggles ---
		'enable'               => ! empty( $raw['enable'] ) ? 1 : 0,
		'hide_for_logged_in'   => ! empty( $raw['hide_for_logged_in'] ) ? 1 : 0,
		'show_close_button'    => ! empty( $raw['show_close_button'] ) ? 1 : 0,

		// --- Position ---
		'position_desktop'     => fcb_sanitize_select(
			$raw['position_desktop'] ?? '',
			[ 'bottom', 'top', 'bottom-left', 'bottom-right', 'top-left', 'top-right' ],
			'bottom'
		),
		'position_mobile'      => fcb_sanitize_select(
			$raw['position_mobile'] ?? '',
			[ 'bottom', 'top', 'bottom-left', 'bottom-right', 'top-left', 'top-right' ],
			'bottom'
		),

		// --- Width ---
		'width_mode'           => fcb_sanitize_select(
			$raw['width_mode'] ?? '',
			[ 'auto', 'full', 'fixed' ],
			'full'
		),
		'width_fixed_px'       => fcb_sanitize_positive_int( $raw['width_fixed_px'] ?? 400, 400 ),

		// --- Breakpoint ---
		'breakpoint_px'        => fcb_sanitize_positive_int( $raw['breakpoint_px'] ?? 768, 768 ),

		// --- Content ---
		'main_text'            => sanitize_text_field( $raw['main_text'] ?? '' ),
		'sub_text'             => sanitize_text_field( $raw['sub_text'] ?? '' ),
		'link_url'             => esc_url_raw( $raw['link_url'] ?? '' ),
		'link_target'          => fcb_sanitize_select(
			$raw['link_target'] ?? '',
			[ '_self', '_blank' ],
			'_self'
		),

		// --- Colors (グラデーション2色 + 文字色) ---
		'bg_color'             => fcb_sanitize_hex_color( $raw['bg_color'] ?? '#0f172a', '#0f172a' ),
		'bg_color_2'           => fcb_sanitize_hex_color( $raw['bg_color_2'] ?? '#312e81', '#312e81' ),
		'text_color'           => fcb_sanitize_hex_color( $raw['text_color'] ?? '#f1f5f9', '#f1f5f9' ),

		// --- Font sizes (px) ---
		'main_text_size'       => fcb_sanitize_positive_int( $raw['main_text_size'] ?? 15, 15 ),
		'sub_text_size'        => fcb_sanitize_positive_int( $raw['sub_text_size'] ?? 13, 13 ),

		// --- Display conditions ---
		'show_on'              => fcb_sanitize_select(
			$raw['show_on'] ?? '',
			[ 'all', 'posts', 'pages', 'specific_pages' ],
			'all'
		),
		'include_page_ids'     => fcb_sanitize_id_list( $raw['include_page_ids'] ?? '' ),
		'include_category_ids' => fcb_sanitize_id_list( $raw['include_category_ids'] ?? '' ),

		// --- Scroll trigger ---
		'show_after_scroll_px' => fcb_sanitize_positive_int( $raw['show_after_scroll_px'] ?? 200, 0 ),

		// --- Dismiss ---
		// '0' = 閉じてもリロードで再表示（localStorage未保存）
		'dismiss_days'         => fcb_sanitize_select(
			$raw['dismiss_days'] ?? '',
			[ '0', '1', '7', '30' ],
			'7'
		),

		// --- Custom CSS ---
		'custom_css'           => wp_strip_all_tags( $raw['custom_css'] ?? '' ),

		// --- Debug mode (管理画面でONにしたときのみerror_log/console.logを出力) ---
		'fcb_debug'            => ! empty( $raw['fcb_debug'] ) ? 1 : 0,
	];
}
