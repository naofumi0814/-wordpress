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
		},
		typeof fcbConfig !== 'undefined' ? fcbConfig : {}
	);

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
		if ( ! raw ) {
			return false;
		}
		const ts = parseInt( raw, 10 );
		if ( isNaN( ts ) ) {
			return false;
		}
		return Date.now() < ts;
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
		const banner = document.getElementById( BANNER_ID );
		if ( ! banner ) {
			return; // Banner not on this page
		}

		// Read dismiss days from data attribute (allows shortcode to override)
		const dismissDays = parseInt( banner.dataset.dismissDays || String( cfg.dismissDays ), 10 );

		// If previously dismissed, do not show
		if ( isDismissed() ) {
			// Keep element in DOM but never show (CSS already hides it)
			return;
		}

		// Apply position + width
		applyPosition( banner );

		// ---- Scroll-reveal logic ----
		const threshold = parseInt( String( cfg.scrollPx ), 10 );

		function checkScroll() {
			if ( scrollY() >= threshold ) {
				showBanner( banner );
			} else {
				hideBanner( banner );
			}
		}

		if ( threshold <= 0 ) {
			// Show immediately
			showBanner( banner );
		} else {
			// Check on load and listen for scroll
			checkScroll();
			window.addEventListener( 'scroll', checkScroll, { passive: true } );
		}

		// ---- Close button ----
		const closeBtn = banner.querySelector( '.fcb-close' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function () {
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
