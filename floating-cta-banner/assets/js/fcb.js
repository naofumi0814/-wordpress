/**
 * Floating CTA Banner – fcb.js
 *
 * Vanilla JavaScript (ES6+). No jQuery dependency.
 * Responsibilities:
 *  1. Apply position / width classes to the banner element.
 *  2. Show banner after a scroll threshold.
 *  3. Handle dismiss (close button) with localStorage persistence.
 *  4. Respect mobile breakpoint by switching position if needed.
 */

/* global fcbConfig */
( function () {
	'use strict';

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------
	const STORAGE_KEY  = 'fcb_dismissed';
	const BANNER_ID    = 'fcb-banner';

	// -----------------------------------------------------------------------
	// Merge runtime config (passed via wp_localize_script)
	// -----------------------------------------------------------------------
	const cfg = Object.assign(
		{
			scrollPx:     200,
			dismissDays:  7,
			breakpointPx: 768,
			posDesktop:   'bottom',
			posMobile:    'bottom',
			widthMode:    'full',
			widthFixedPx: 400,
			debug:        false, // PHP fcb_debug=1 のときtrue
		},
		typeof fcbConfig !== 'undefined' ? fcbConfig : {}
	);

	// -----------------------------------------------------------------------
	// Debug logger – console.log は fcb_debug=ON のときのみ
	// -----------------------------------------------------------------------
	function fcbLog( message, data ) {
		if ( ! cfg.debug ) {
			return;
		}
		if ( data !== undefined ) {
			// eslint-disable-next-line no-console
			console.log( '[FCB] ' + message, data );
		} else {
			// eslint-disable-next-line no-console
			console.log( '[FCB] ' + message );
		}
	}

	// ①設定値の読み取り確認
	fcbLog( 'fcbConfig 読み込み完了', {
		scrollPx:     cfg.scrollPx,
		dismissDays:  cfg.dismissDays,
		breakpointPx: cfg.breakpointPx,
		posDesktop:   cfg.posDesktop,
		posMobile:    cfg.posMobile,
		widthMode:    cfg.widthMode,
		widthFixedPx: cfg.widthFixedPx,
		fcbConfigRaw: typeof fcbConfig !== 'undefined' ? fcbConfig : '(undefined – wp_localize_script 未実行)',
	} );

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Get localStorage item, returning null on error (Safari private mode etc.)
	 * @param {string} key
	 * @returns {string|null}
	 */
	function lsGet( key ) {
		try {
			return localStorage.getItem( key );
		} catch {
			return null;
		}
	}

	/**
	 * Set localStorage item, silently failing on error.
	 * @param {string} key
	 * @param {string} value
	 */
	function lsSet( key, value ) {
		try {
			localStorage.setItem( key, value );
		} catch {
			// silently ignore (private browsing / storage full)
		}
	}

	/**
	 * Return true if the banner was previously dismissed and the period
	 * has NOT yet expired.
	 * @returns {boolean}
	 */
	function isDismissed() {
		const raw = lsGet( STORAGE_KEY );

		// ③dismiss判定ログ
		if ( ! raw ) {
			fcbLog( '③ dismiss判定: localStorage に fcb_dismissed なし → 非dismiss' );
			return false;
		}
		const ts = parseInt( raw, 10 );
		if ( isNaN( ts ) ) {
			fcbLog( '③ dismiss判定: localStorage 値が不正 → 非dismiss', { raw } );
			return false;
		}
		const dismissed = Date.now() < ts;
		fcbLog( '③ dismiss判定', {
			storedExpiry:    new Date( ts ).toLocaleString(),
			now:             new Date().toLocaleString(),
			remainingMs:     ts - Date.now(),
			isDismissed:     dismissed,
		} );
		return dismissed;
	}

	/**
	 * Save dismiss timestamp in localStorage.
	 * @param {number} days Number of days to persist the dismissed state.
	 */
	function saveDismiss( days ) {
		const expiry = Date.now() + days * 24 * 60 * 60 * 1000;
		lsSet( STORAGE_KEY, String( expiry ) );
	}

	/**
	 * Determine whether we are on a mobile viewport.
	 * @returns {boolean}
	 */
	function isMobile() {
		if ( cfg.breakpointPx <= 0 ) {
			return false;
		}
		return window.innerWidth < cfg.breakpointPx;
	}

	/**
	 * Return the appropriate position string for the current viewport.
	 * @returns {string}
	 */
	function currentPosition() {
		return isMobile() ? cfg.posMobile : cfg.posDesktop;
	}

	// -----------------------------------------------------------------------
	// Apply layout attributes / classes
	// -----------------------------------------------------------------------

	/**
	 * Set position-related data attribute and CSS classes on the banner.
	 * @param {HTMLElement} banner
	 */
	function applyPosition( banner ) {
		const pos = currentPosition();

		// data-position drives CSS rules
		banner.setAttribute( 'data-position', pos );

		// Remove previous width classes
		banner.classList.remove( 'fcb-width-auto', 'fcb-width-full', 'fcb-width-fixed' );

		// Corner positions force 'auto' width unless user set 'fixed'
		const isCorner = pos !== 'bottom' && pos !== 'top';

		if ( isCorner && cfg.widthMode !== 'fixed' ) {
			banner.classList.add( 'fcb-width-auto' );
			banner.style.width = '';
			// Re-set left/right for auto-width (CSS handles the rest via [data-position])
		} else if ( cfg.widthMode === 'fixed' ) {
			banner.classList.add( 'fcb-width-fixed' );
			banner.style.width = cfg.widthFixedPx + 'px';

			// Center fixed-width full-span positions
			if ( ! isCorner ) {
				banner.style.left  = '50%';
				banner.style.right = 'auto';
				banner.style.transform = 'translateX(-50%)';
				// Keep translate for hidden state integrated:
				// Override handled via inline style – hidden class adds translateY.
				// We combine: use a CSS var trick or just manage inline.
				banner.style.transform = banner.classList.contains( 'fcb-hidden' )
					? 'translateX(-50%) translateY(20px)'
					: 'translateX(-50%)';
			}
		} else {
			// full width
			banner.classList.add( 'fcb-width-full' );
			banner.style.width = '';
		}
	}

	/**
	 * Show the banner (remove hidden class).
	 * @param {HTMLElement} banner
	 */
	function showBanner( banner ) {
		banner.classList.remove( 'fcb-hidden' );

		// If fixed-width centered, adjust transform now that hidden is removed
		if ( cfg.widthMode === 'fixed' && ! isMobile() ) {
			const pos = currentPosition();
			if ( pos === 'bottom' || pos === 'top' ) {
				banner.style.transform = 'translateX(-50%)';
			}
		}
	}

	/**
	 * Hide the banner (add hidden class).
	 * @param {HTMLElement} banner
	 */
	function hideBanner( banner ) {
		banner.classList.add( 'fcb-hidden' );
	}

	// -----------------------------------------------------------------------
	// Scroll logic
	// -----------------------------------------------------------------------

	/**
	 * Return current page scroll offset in px.
	 * @returns {number}
	 */
	function scrollY() {
		return window.pageYOffset || document.documentElement.scrollTop || 0;
	}

	// -----------------------------------------------------------------------
	// Main init
	// -----------------------------------------------------------------------
	function init() {
		// ①要素取得確認
		const banner = document.getElementById( BANNER_ID );
		fcbLog( '①要素取得', {
			found:        !! banner,
			id:           BANNER_ID,
			classList:    banner ? Array.from( banner.classList ).join( ' ' ) : '(なし)',
			dataPosition: banner ? ( banner.dataset.position || '(未設定)' ) : '(なし)',
			computedDisplay: banner ? window.getComputedStyle( banner ).display : '(なし)',
		} );

		if ( ! banner ) {
			fcbLog( '①要素なし: #fcb-banner が DOM に存在しない。wp_footer が呼ばれているか、テーマがwp_footer()を呼んでいるか確認してください。' );
			return; // Banner not on this page
		}

		// Read dismiss days from data attribute (allows shortcode to override)
		const dismissDays = parseInt( banner.dataset.dismissDays || String( cfg.dismissDays ), 10 );

		// If previously dismissed, do not show
		if ( isDismissed() ) {
			// Keep element in DOM but never show (CSS already hides it)
			fcbLog( '③ dismiss有効期間内 → バナーを表示しない。リセットするには: localStorage.removeItem("fcb_dismissed")' );
			return;
		}

		// Apply position + width
		applyPosition( banner );
		fcbLog( 'applyPosition 完了', {
			dataPosition: banner.getAttribute( 'data-position' ),
			classList:    Array.from( banner.classList ).join( ' ' ),
			inlineWidth:  banner.style.width,
		} );

		// ---- Scroll-reveal logic ----
		const threshold = parseInt( String( cfg.scrollPx ), 10 );
		fcbLog( '④ スクロール閾値設定', {
			threshold,
			currentScrollY: scrollY(),
			willShowNow:    threshold <= 0 || scrollY() >= threshold,
		} );

		function checkScroll() {
			const sy = scrollY();
			const shouldShow = sy >= threshold;
			fcbLog( '④ scroll イベント', { scrollY: sy, threshold, shouldShow } );
			if ( shouldShow ) {
				showBanner( banner );
			} else {
				hideBanner( banner );
			}
		}

		if ( threshold <= 0 ) {
			// Show immediately
			fcbLog( '④ threshold=0 → 即時表示' );
			showBanner( banner );
		} else {
			// Check on load and listen for scroll
			checkScroll();
			window.addEventListener( 'scroll', checkScroll, { passive: true } );
		}

		// ②hidden解除確認 (showBanner 後にクラスを確認)
		setTimeout( function () {
			fcbLog( '② hidden解除後の状態確認', {
				hasFcbHidden:    banner.classList.contains( 'fcb-hidden' ),
				computedOpacity: window.getComputedStyle( banner ).opacity,
				computedVisibility: window.getComputedStyle( banner ).visibility,
				computedDisplay: window.getComputedStyle( banner ).display,
				computedZIndex:  window.getComputedStyle( banner ).zIndex,
				computedPosition: window.getComputedStyle( banner ).position,
				boundingRect:    JSON.stringify( banner.getBoundingClientRect() ),
			} );
		}, 500 );

		// ---- Close button ----
		const closeBtn = banner.querySelector( '.fcb-close' );
		fcbLog( 'close button 取得', { found: !! closeBtn } );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function () {
				fcbLog( '× ボタン クリック → dismiss保存', { dismissDays } );
				hideBanner( banner );
				saveDismiss( dismissDays );

				// Remove scroll listener to avoid un-hiding after dismiss
				window.removeEventListener( 'scroll', checkScroll );
			} );
		}

		// ---- Responsive: reapply position on resize ----
		let resizeTimer;
		window.addEventListener( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function () {
				applyPosition( banner );
				fcbLog( 'resize → applyPosition', {
					windowWidth:  window.innerWidth,
					isMobile:     isMobile(),
					dataPosition: banner.getAttribute( 'data-position' ),
				} );
			}, 100 );
		} );
	}

	// -----------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
