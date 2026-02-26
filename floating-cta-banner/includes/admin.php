<?php
/**
 * Admin settings page for Floating CTA Banner.
 *
 * Registers:
 *  - Settings menu under "Settings > Floating CTA Banner"
 *  - All settings fields via WordPress Settings API
 *  - Sanitize callback delegated to fcb_sanitize_settings()
 *
 * @package FloatingCTABanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -----------------------------------------------------------------------
 * Register settings with the Settings API
 * --------------------------------------------------------------------- */
add_action( 'admin_init', 'fcb_register_settings' );
function fcb_register_settings(): void {
	register_setting(
		'fcb_settings_group',
		FCB_OPTION_KEY,
		[
			'sanitize_callback' => 'fcb_sanitize_settings',
			'default'           => [],
		]
	);

	/* ---- Section: General ---- */
	add_settings_section(
		'fcb_section_general',
		__( '基本設定', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'enable',             __( 'バナーを有効化',         'floating-cta-banner' ), 'fcb_field_enable',             'fcb_section_general' );
	fcb_add_field( 'fcb_debug',          __( 'デバッグモード',         'floating-cta-banner' ), 'fcb_field_fcb_debug',          'fcb_section_general' );

	/* ---- Section: Layout ---- */
	add_settings_section(
		'fcb_section_layout',
		__( 'レイアウト設定', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'position_desktop',   __( '表示位置（PC）',         'floating-cta-banner' ), 'fcb_field_position_desktop',   'fcb_section_layout' );
	fcb_add_field( 'position_mobile',    __( '表示位置（モバイル）',   'floating-cta-banner' ), 'fcb_field_position_mobile',    'fcb_section_layout' );
	fcb_add_field( 'width_mode',         __( '横幅モード',             'floating-cta-banner' ), 'fcb_field_width_mode',         'fcb_section_layout' );
	fcb_add_field( 'width_fixed_px',     __( '横幅（px）',             'floating-cta-banner' ), 'fcb_field_width_fixed_px',     'fcb_section_layout' );
	fcb_add_field( 'breakpoint_px',      __( 'モバイル切替 px',        'floating-cta-banner' ), 'fcb_field_breakpoint_px',      'fcb_section_layout' );

	/* ---- Section: Content ---- */
	add_settings_section(
		'fcb_section_content',
		__( 'コンテンツ設定', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'main_text',          __( 'メインテキスト',         'floating-cta-banner' ), 'fcb_field_main_text',          'fcb_section_content' );
	fcb_add_field( 'sub_text',           __( 'サブテキスト',           'floating-cta-banner' ), 'fcb_field_sub_text',           'fcb_section_content' );
	fcb_add_field( 'button_text',        __( 'ボタン文言',             'floating-cta-banner' ), 'fcb_field_button_text',        'fcb_section_content' );
	fcb_add_field( 'link_url',           __( 'リンクURL',              'floating-cta-banner' ), 'fcb_field_link_url',           'fcb_section_content' );
	fcb_add_field( 'link_target',        __( 'リンクターゲット',       'floating-cta-banner' ), 'fcb_field_link_target',        'fcb_section_content' );

	/* ---- Section: Colors ---- */
	add_settings_section(
		'fcb_section_colors',
		__( 'カラー設定', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'bg_color',           __( '背景色',                 'floating-cta-banner' ), 'fcb_field_bg_color',           'fcb_section_colors' );
	fcb_add_field( 'text_color',         __( '文字色',                 'floating-cta-banner' ), 'fcb_field_text_color',         'fcb_section_colors' );
	fcb_add_field( 'button_color',       __( 'ボタン色',               'floating-cta-banner' ), 'fcb_field_button_color',       'fcb_section_colors' );

	/* ---- Section: Display Conditions ---- */
	add_settings_section(
		'fcb_section_conditions',
		__( '表示条件', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'show_on',            __( '表示対象',               'floating-cta-banner' ), 'fcb_field_show_on',            'fcb_section_conditions' );
	fcb_add_field( 'include_page_ids',   __( 'ページID（カンマ区切り）','floating-cta-banner' ), 'fcb_field_include_page_ids',   'fcb_section_conditions' );
	fcb_add_field( 'include_category_ids',__( 'カテゴリID（カンマ区切り）','floating-cta-banner' ),'fcb_field_include_category_ids','fcb_section_conditions' );
	fcb_add_field( 'hide_for_logged_in', __( 'ログインユーザー非表示', 'floating-cta-banner' ), 'fcb_field_hide_for_logged_in', 'fcb_section_conditions' );
	fcb_add_field( 'show_after_scroll_px',__( 'スクロール後に表示 (px)','floating-cta-banner' ),'fcb_field_show_after_scroll_px','fcb_section_conditions' );

	/* ---- Section: Dismiss ---- */
	add_settings_section(
		'fcb_section_dismiss',
		__( '閉じるボタン設定', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'show_close_button',  __( '閉じるボタンを表示',    'floating-cta-banner' ), 'fcb_field_show_close_button',  'fcb_section_dismiss' );
	fcb_add_field( 'dismiss_days',       __( '再表示しない期間',       'floating-cta-banner' ), 'fcb_field_dismiss_days',       'fcb_section_dismiss' );

	/* ---- Section: Custom CSS ---- */
	add_settings_section(
		'fcb_section_custom',
		__( 'カスタムCSS', 'floating-cta-banner' ),
		'__return_null',
		'fcb_settings_page'
	);

	fcb_add_field( 'custom_css',         __( 'カスタムCSS',            'floating-cta-banner' ), 'fcb_field_custom_css',         'fcb_section_custom' );
}

/**
 * Helper to add a settings field with shared option-key prefix.
 */
function fcb_add_field( string $id, string $label, callable|string $callback, string $section ): void {
	add_settings_field(
		'fcb_' . $id,
		$label,
		$callback,
		'fcb_settings_page',
		$section,
		[ 'label_for' => 'fcb_' . $id ]
	);
}

/* -----------------------------------------------------------------------
 * Add menu page
 * --------------------------------------------------------------------- */
add_action( 'admin_menu', 'fcb_add_menu' );
function fcb_add_menu(): void {
	add_options_page(
		__( 'Floating CTA Banner 設定', 'floating-cta-banner' ),
		__( 'Floating CTA Banner', 'floating-cta-banner' ),
		'manage_options',
		'fcb-settings',
		'fcb_render_settings_page'
	);
}

/* -----------------------------------------------------------------------
 * Settings page HTML
 * --------------------------------------------------------------------- */
function fcb_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( '権限がありません。', 'floating-cta-banner' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Floating CTA Banner 設定', 'floating-cta-banner' ); ?></h1>

		<?php settings_errors( 'fcb_settings' ); ?>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'fcb_settings_group' );
			do_settings_sections( 'fcb_settings_page' );
			submit_button( __( '設定を保存', 'floating-cta-banner' ) );
			?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'ショートコードの使い方', 'floating-cta-banner' ); ?></h2>
		<p><?php esc_html_e( '以下のショートコードをページや投稿に埋め込むことができます。', 'floating-cta-banner' ); ?></p>
		<code>[floating_cta_banner]</code>
		<p><?php esc_html_e( '属性で一部の設定を上書きできます：', 'floating-cta-banner' ); ?></p>
		<code>[floating_cta_banner main_text="今すぐ予約" link_url="https://example.com/contact" button_text="予約する"]</code>
		<p><?php esc_html_e( '利用可能な属性: main_text, sub_text, button_text, link_url, link_target, bg_color, text_color, button_color', 'floating-cta-banner' ); ?></p>
	</div>
	<?php
}

/* =======================================================================
 * Individual field callbacks
 * ===================================================================== */

function fcb_get_opt( string $key ): mixed {
	$opts = fcb_get_settings();
	return $opts[ $key ] ?? '';
}

/* ---- General ---- */

function fcb_field_enable(): void {
	$val = fcb_get_opt( 'enable' );
	?>
	<input type="checkbox"
		   id="fcb_enable"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[enable]"
		   value="1"
		   <?php checked( 1, $val ); ?>
	>
	<label for="fcb_enable"><?php esc_html_e( 'バナーを有効にする', 'floating-cta-banner' ); ?></label>
	<?php
}

/**
 * デバッグモード ON/OFF フィールド。
 * ONにすると PHP側は error_log() で、JS側は console.log() でデバッグ情報を出力します。
 * 本番環境では必ずOFFにしてください。
 */
function fcb_field_fcb_debug(): void {
	$val = fcb_get_opt( 'fcb_debug' );
	?>
	<input type="checkbox"
		   id="fcb_fcb_debug"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[fcb_debug]"
		   value="1"
		   <?php checked( 1, $val ); ?>
	>
	<label for="fcb_fcb_debug">
		<?php esc_html_e( 'デバッグログを有効にする', 'floating-cta-banner' ); ?>
	</label>
	<p class="description">
		<?php
		esc_html_e(
			'PHP: wp-content/debug.log に出力（WP_DEBUG_LOG=true 必須）。 JS: ブラウザコンソールに出力。本番では必ずOFFにしてください。',
			'floating-cta-banner'
		);
		?>
	</p>
	<?php
}

/* ---- Layout ---- */

function fcb_field_position_desktop(): void {
	fcb_render_position_select( 'position_desktop', fcb_get_opt( 'position_desktop' ) );
}

function fcb_field_position_mobile(): void {
	fcb_render_position_select( 'position_mobile', fcb_get_opt( 'position_mobile' ) );
}

function fcb_render_position_select( string $field, string $current ): void {
	$options = [
		'bottom'       => __( '下固定（bottom）', 'floating-cta-banner' ),
		'top'          => __( '上固定（top）', 'floating-cta-banner' ),
		'bottom-left'  => __( '左下（bottom-left）', 'floating-cta-banner' ),
		'bottom-right' => __( '右下（bottom-right）', 'floating-cta-banner' ),
		'top-left'     => __( '左上（top-left）', 'floating-cta-banner' ),
		'top-right'    => __( '右上（top-right）', 'floating-cta-banner' ),
	];
	?>
	<select id="fcb_<?php echo esc_attr( $field ); ?>"
			name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[<?php echo esc_attr( $field ); ?>]">
		<?php foreach ( $options as $val => $label ) : ?>
		<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
		<?php endforeach; ?>
	</select>
	<?php
}

function fcb_field_width_mode(): void {
	$current = fcb_get_opt( 'width_mode' );
	$options = [
		'auto'  => __( 'auto（内容に合わせる）', 'floating-cta-banner' ),
		'full'  => __( 'full（画面幅いっぱい）', 'floating-cta-banner' ),
		'fixed' => __( 'fixed（px指定）', 'floating-cta-banner' ),
	];
	?>
	<select id="fcb_width_mode" name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[width_mode]">
		<?php foreach ( $options as $val => $label ) : ?>
		<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
		<?php endforeach; ?>
	</select>
	<?php
}

function fcb_field_width_fixed_px(): void {
	$val = (int) fcb_get_opt( 'width_fixed_px' );
	?>
	<input type="number"
		   id="fcb_width_fixed_px"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[width_fixed_px]"
		   value="<?php echo esc_attr( (string) $val ); ?>"
		   min="100" max="2000" step="1"
		   class="small-text"
	> px
	<p class="description"><?php esc_html_e( '横幅モードが "fixed" のときに使用されます。', 'floating-cta-banner' ); ?></p>
	<?php
}

function fcb_field_breakpoint_px(): void {
	$val = (int) fcb_get_opt( 'breakpoint_px' );
	?>
	<input type="number"
		   id="fcb_breakpoint_px"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[breakpoint_px]"
		   value="<?php echo esc_attr( (string) $val ); ?>"
		   min="0" max="2560" step="1"
		   class="small-text"
	> px
	<p class="description"><?php esc_html_e( 'この幅未満をモバイルとして扱います。0 にすると常にPCレイアウトを使用。', 'floating-cta-banner' ); ?></p>
	<?php
}

/* ---- Content ---- */

function fcb_field_main_text(): void {
	$val = fcb_get_opt( 'main_text' );
	?>
	<input type="text"
		   id="fcb_main_text"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[main_text]"
		   value="<?php echo esc_attr( $val ); ?>"
		   class="regular-text"
	>
	<?php
}

function fcb_field_sub_text(): void {
	$val = fcb_get_opt( 'sub_text' );
	?>
	<input type="text"
		   id="fcb_sub_text"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[sub_text]"
		   value="<?php echo esc_attr( $val ); ?>"
		   class="regular-text"
	>
	<p class="description"><?php esc_html_e( '任意。空欄の場合は表示されません。', 'floating-cta-banner' ); ?></p>
	<?php
}

function fcb_field_button_text(): void {
	$val = fcb_get_opt( 'button_text' );
	?>
	<input type="text"
		   id="fcb_button_text"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[button_text]"
		   value="<?php echo esc_attr( $val ); ?>"
		   class="regular-text"
	>
	<p class="description"><?php esc_html_e( '任意。空欄の場合はテキスト全体がリンクになります。', 'floating-cta-banner' ); ?></p>
	<?php
}

function fcb_field_link_url(): void {
	$val = fcb_get_opt( 'link_url' );
	?>
	<input type="url"
		   id="fcb_link_url"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[link_url]"
		   value="<?php echo esc_attr( $val ); ?>"
		   class="regular-text"
		   placeholder="https://example.com"
	>
	<?php
}

function fcb_field_link_target(): void {
	$current = fcb_get_opt( 'link_target' );
	?>
	<select id="fcb_link_target"
			name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[link_target]">
		<option value="_self"  <?php selected( $current, '_self' );  ?>><?php esc_html_e( '同じタブで開く（_self）',  'floating-cta-banner' ); ?></option>
		<option value="_blank" <?php selected( $current, '_blank' ); ?>><?php esc_html_e( '新しいタブで開く（_blank）', 'floating-cta-banner' ); ?></option>
	</select>
	<p class="description"><?php esc_html_e( '_blank 時は rel="noopener noreferrer" が自動付与されます。', 'floating-cta-banner' ); ?></p>
	<?php
}

/* ---- Colors ---- */

function fcb_field_bg_color(): void {
	fcb_render_color_input( 'bg_color', '#1a73e8' );
}

function fcb_field_text_color(): void {
	fcb_render_color_input( 'text_color', '#ffffff' );
}

function fcb_field_button_color(): void {
	fcb_render_color_input( 'button_color', '#ff5722' );
}

function fcb_render_color_input( string $field, string $default ): void {
	$val = fcb_get_opt( $field );
	if ( '' === $val ) {
		$val = $default;
	}
	?>
	<input type="color"
		   id="fcb_<?php echo esc_attr( $field ); ?>"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[<?php echo esc_attr( $field ); ?>]"
		   value="<?php echo esc_attr( $val ); ?>"
	>
	<?php
}

/* ---- Display Conditions ---- */

function fcb_field_show_on(): void {
	$current = fcb_get_opt( 'show_on' );
	$options = [
		'all'            => __( 'すべてのページ', 'floating-cta-banner' ),
		'posts'          => __( '投稿のみ', 'floating-cta-banner' ),
		'pages'          => __( '固定ページのみ', 'floating-cta-banner' ),
		'specific_pages' => __( '特定のページID', 'floating-cta-banner' ),
	];
	?>
	<select id="fcb_show_on" name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[show_on]">
		<?php foreach ( $options as $val => $label ) : ?>
		<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
		<?php endforeach; ?>
	</select>
	<?php
}

function fcb_field_include_page_ids(): void {
	$val = fcb_get_opt( 'include_page_ids' );
	?>
	<input type="text"
		   id="fcb_include_page_ids"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[include_page_ids]"
		   value="<?php echo esc_attr( $val ); ?>"
		   class="regular-text"
		   placeholder="1,2,3"
	>
	<p class="description"><?php esc_html_e( '表示対象が「特定のページID」の場合に使用します。', 'floating-cta-banner' ); ?></p>
	<?php
}

function fcb_field_include_category_ids(): void {
	$val = fcb_get_opt( 'include_category_ids' );
	?>
	<input type="text"
		   id="fcb_include_category_ids"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[include_category_ids]"
		   value="<?php echo esc_attr( $val ); ?>"
		   class="regular-text"
		   placeholder="1,2,3"
	>
	<p class="description"><?php esc_html_e( '投稿の場合のみ有効。指定カテゴリの投稿にのみ表示されます。', 'floating-cta-banner' ); ?></p>
	<?php
}

function fcb_field_hide_for_logged_in(): void {
	$val = fcb_get_opt( 'hide_for_logged_in' );
	?>
	<input type="checkbox"
		   id="fcb_hide_for_logged_in"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[hide_for_logged_in]"
		   value="1"
		   <?php checked( 1, $val ); ?>
	>
	<label for="fcb_hide_for_logged_in"><?php esc_html_e( 'ログインユーザーにはバナーを表示しない', 'floating-cta-banner' ); ?></label>
	<?php
}

function fcb_field_show_after_scroll_px(): void {
	$val = (int) fcb_get_opt( 'show_after_scroll_px' );
	?>
	<input type="number"
		   id="fcb_show_after_scroll_px"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[show_after_scroll_px]"
		   value="<?php echo esc_attr( (string) $val ); ?>"
		   min="0" max="9999" step="1"
		   class="small-text"
	> px
	<p class="description"><?php esc_html_e( '0 にすると最初から表示されます。', 'floating-cta-banner' ); ?></p>
	<?php
}

/* ---- Dismiss ---- */

function fcb_field_show_close_button(): void {
	$val = fcb_get_opt( 'show_close_button' );
	?>
	<input type="checkbox"
		   id="fcb_show_close_button"
		   name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[show_close_button]"
		   value="1"
		   <?php checked( 1, $val ); ?>
	>
	<label for="fcb_show_close_button"><?php esc_html_e( '×ボタンでバナーを閉じられるようにする', 'floating-cta-banner' ); ?></label>
	<?php
}

function fcb_field_dismiss_days(): void {
	$current = (string) fcb_get_opt( 'dismiss_days' );
	$options = [
		'0'  => __( 'リロードで再表示（記憶しない）', 'floating-cta-banner' ),
		'1'  => __( '1日', 'floating-cta-banner' ),
		'7'  => __( '7日', 'floating-cta-banner' ),
		'30' => __( '30日', 'floating-cta-banner' ),
	];
	?>
	<select id="fcb_dismiss_days" name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[dismiss_days]">
		<?php foreach ( $options as $val => $label ) : ?>
		<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>>
			<?php echo esc_html( $label ); ?>
		</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( '「リロードで再表示」を選ぶと×ボタンで閉じても再読み込み後にバナーが戻ります。日数を選ぶとその期間は再表示されません。', 'floating-cta-banner' ); ?>
	</p>
	<?php
}

/* ---- Custom CSS ---- */

function fcb_field_custom_css(): void {
	$val = fcb_get_opt( 'custom_css' );
	?>
	<textarea id="fcb_custom_css"
			  name="<?php echo esc_attr( FCB_OPTION_KEY ); ?>[custom_css]"
			  rows="8"
			  class="large-text code"
			  placeholder="/* #fcb-banner { ... } */"
	><?php echo esc_textarea( $val ); ?></textarea>
	<p class="description"><?php esc_html_e( 'バナーのデザインを上書きしたい場合に入力してください。スクリプトタグは使用できません。', 'floating-cta-banner' ); ?></p>
	<?php
}
