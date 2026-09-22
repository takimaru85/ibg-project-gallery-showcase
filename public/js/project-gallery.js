/**
 * WordPress Project Gallery - public script.
 *
 * Progressive enhancement only: the shortcode already renders working
 * results and a working ?wpg_category= filter link without this file.
 */
( function () {
	'use strict';

	var i18n = ( typeof wpgGallery !== 'undefined' && wpgGallery.i18n ) ? wpgGallery.i18n : {};

	var lightboxEl    = null;
	var lightboxImgEl = null;
	var lightboxImages = [];
	var lightboxIndex  = 0;
	var lastFocusedEl  = null;

	document.addEventListener( 'DOMContentLoaded', function () {
		initFilters();
		initSingleGallery();
	} );

	/* ---------------- Category filter (AJAX enhancement) ---------------- */

	function initFilters() {
		var wrappers = document.querySelectorAll( '.wpg-gallery-wrapper' );

		wrappers.forEach( function ( wrapper ) {
			wrapper.classList.add( 'wpg-js-ready' );

			wrapper.addEventListener( 'click', function ( event ) {
				var link = event.target.closest( '.wpg-filter-link' );

				if ( link && wrapper.contains( link ) ) {
					event.preventDefault();
					loadProjects( wrapper, link.getAttribute( 'data-category' ) || '' );
					return;
				}

				var loadMoreBtn = event.target.closest( '.wpg-load-more-btn' );

				if ( loadMoreBtn && wrapper.contains( loadMoreBtn ) ) {
					event.preventDefault();
					var category = wrapper.getAttribute( 'data-category' ) || '';
					var show = loadMoreBtn.getAttribute( 'data-show' ) || '';
					loadProjects( wrapper, category, show );
				}
			} );

			var form = wrapper.querySelector( '.wpg-filter-form' );

			if ( form ) {
				var select = form.querySelector( '.wpg-filter-select' );

				form.addEventListener( 'submit', function ( event ) {
					event.preventDefault();
					loadProjects( wrapper, select ? select.value : '' );
				} );

				if ( select ) {
					select.addEventListener( 'change', function () {
						loadProjects( wrapper, select.value );
					} );
				}
			}
		} );
	}

	function loadProjects( wrapper, category, show ) {
		if ( typeof wpgGallery === 'undefined' || ! wpgGallery.ajaxUrl ) {
			return;
		}

		var results = wrapper.querySelector( '.wpg-gallery-results' );

		if ( ! results ) {
			return;
		}

		results.classList.add( 'is-loading' );

		var formData = new FormData();
		formData.append( 'action', 'wpg_filter_projects' );
		formData.append( 'nonce', wrapper.getAttribute( 'data-nonce' ) || '' );
		formData.append( 'category', category );
		formData.append( 'columns', wrapper.getAttribute( 'data-columns' ) || '3' );
		formData.append( 'limit', wrapper.getAttribute( 'data-limit' ) || '-1' );
		formData.append( 'featured', wrapper.getAttribute( 'data-featured' ) || '' );
		formData.append( 'orderby', wrapper.getAttribute( 'data-orderby' ) || 'date' );
		formData.append( 'order', wrapper.getAttribute( 'data-order' ) || 'DESC' );
		formData.append( 'layout', wrapper.getAttribute( 'data-layout' ) || 'grid' );
		formData.append( 'per_page', wrapper.getAttribute( 'data-per-page' ) || '0' );
		formData.append( 'show', show || '' );

		fetch( wpgGallery.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				results.classList.remove( 'is-loading' );

				if ( json && json.success && json.data && typeof json.data.html === 'string' ) {
					results.innerHTML = json.data.html;
					wrapper.setAttribute( 'data-category', category );
					updateActiveFilter( wrapper, category );
					updateUrl( category );
				} else {
					showError( results );
				}
			} )
			.catch( function () {
				results.classList.remove( 'is-loading' );
				showError( results );
			} );
	}

	function updateActiveFilter( wrapper, category ) {
		var links = wrapper.querySelectorAll( '.wpg-filter-link' );

		links.forEach( function ( link ) {
			var isActive = ( link.getAttribute( 'data-category' ) || '' ) === category;
			link.classList.toggle( 'is-active', isActive );

			if ( isActive ) {
				link.setAttribute( 'aria-current', 'true' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );

		var select = wrapper.querySelector( '.wpg-filter-select' );

		if ( select && select.value !== category ) {
			select.value = category;
		}
	}

	function updateUrl( category ) {
		if ( ! window.history || ! window.history.pushState ) {
			return;
		}

		try {
			var url = new URL( window.location.href );

			if ( category ) {
				url.searchParams.set( 'wpg_category', category );
			} else {
				url.searchParams.delete( 'wpg_category' );
			}

			window.history.pushState( {}, '', url );
		} catch ( e ) {
			// Ignore URL API failures (e.g. very old browsers).
		}
	}

	function showError( results ) {
		var message = i18n.error || 'Unable to load projects. Please try again.';
		results.innerHTML = '<p class="wpg-no-projects">' + escapeHtml( message ) + '</p>';
	}

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	/* ---------------- Single project gallery + lightbox ---------------- */

	function initSingleGallery() {
		var galleries = document.querySelectorAll( '.wpg-single-gallery' );

		galleries.forEach( function ( gallery ) {
			if ( gallery.dataset.wpgInit ) {
				return;
			}
			gallery.dataset.wpgInit = '1';

			var mainImg  = gallery.querySelector( '#wpg-main-image' );
			var thumbs   = Array.prototype.slice.call( gallery.querySelectorAll( '.wpg-thumb' ) );
			var prevBtn  = gallery.querySelector( '.wpg-gallery-prev' );
			var nextBtn  = gallery.querySelector( '.wpg-gallery-next' );
			var trigger  = gallery.querySelector( '.wpg-gallery-main-trigger' );
			var urlSpans = gallery.querySelectorAll( '#wpg-gallery-full-urls span' );
			var urls     = Array.prototype.map.call( urlSpans, function ( span ) {
				return span.getAttribute( 'data-full' );
			} );

			var currentIndex = 0;

			function setActive( index ) {
				if ( ! urls.length || ! mainImg ) {
					return;
				}

				currentIndex = ( index + urls.length ) % urls.length;

				mainImg.classList.add( 'is-fading' );

				window.setTimeout( function () {
					mainImg.src = urls[ currentIndex ];

					var onLoad = function () {
						mainImg.classList.remove( 'is-fading' );
						mainImg.removeEventListener( 'load', onLoad );
					};

					if ( mainImg.complete ) {
						onLoad();
					} else {
						mainImg.addEventListener( 'load', onLoad );
					}
				}, 150 );

				thumbs.forEach( function ( thumb, i ) {
					var active = i === currentIndex;
					thumb.classList.toggle( 'is-active', active );

					if ( active ) {
						thumb.setAttribute( 'aria-current', 'true' );
					} else {
						thumb.removeAttribute( 'aria-current' );
					}
				} );
			}

			thumbs.forEach( function ( thumb, i ) {
				thumb.addEventListener( 'click', function () {
					setActive( i );
				} );
			} );

			if ( prevBtn ) {
				prevBtn.addEventListener( 'click', function () {
					setActive( currentIndex - 1 );
				} );
			}

			if ( nextBtn ) {
				nextBtn.addEventListener( 'click', function () {
					setActive( currentIndex + 1 );
				} );
			}

			if ( trigger && gallery.getAttribute( 'data-lightbox' ) === '1' ) {
				trigger.addEventListener( 'click', function () {
					var images = urls.length ? urls : ( mainImg ? [ mainImg.src ] : [] );
					if ( images.length ) {
						openLightbox( images, currentIndex );
					}
				} );
			}
		} );
	}

	/* ---------------- Lightbox ---------------- */

	function buildLightbox() {
		if ( lightboxEl ) {
			return;
		}

		lightboxEl = document.createElement( 'div' );
		lightboxEl.className = 'wpg-lightbox';
		lightboxEl.setAttribute( 'role', 'dialog' );
		lightboxEl.setAttribute( 'aria-modal', 'true' );
		lightboxEl.setAttribute( 'aria-label', 'Project image viewer' );
		lightboxEl.hidden = true;

		var prevLabel  = escapeHtml( i18n.previous || 'Previous image' );
		var nextLabel  = escapeHtml( i18n.next || 'Next image' );
		var closeLabel = escapeHtml( i18n.close || 'Close' );

		var chevronLeft  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
		var chevronRight = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" /></svg>';
		var closeIcon    = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" /></svg>';

		lightboxEl.innerHTML =
			'<button type="button" class="wpg-lightbox-nav wpg-lightbox-prev" aria-label="' + prevLabel + '">' + chevronLeft + '</button>' +
			'<figure class="wpg-lightbox-figure"><img alt="" /></figure>' +
			'<button type="button" class="wpg-lightbox-nav wpg-lightbox-next" aria-label="' + nextLabel + '">' + chevronRight + '</button>' +
			'<button type="button" class="wpg-lightbox-close" aria-label="' + closeLabel + '">' + closeIcon + '</button>';

		document.body.appendChild( lightboxEl );
		lightboxImgEl = lightboxEl.querySelector( 'img' );

		lightboxEl.querySelector( '.wpg-lightbox-close' ).addEventListener( 'click', closeLightbox );
		lightboxEl.querySelector( '.wpg-lightbox-prev' ).addEventListener( 'click', function () {
			showLightboxImage( lightboxIndex - 1 );
		} );
		lightboxEl.querySelector( '.wpg-lightbox-next' ).addEventListener( 'click', function () {
			showLightboxImage( lightboxIndex + 1 );
		} );

		lightboxEl.addEventListener( 'click', function ( event ) {
			if ( event.target === lightboxEl ) {
				closeLightbox();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( ! lightboxEl || lightboxEl.hidden ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				closeLightbox();
			} else if ( 'ArrowLeft' === event.key ) {
				showLightboxImage( lightboxIndex - 1 );
			} else if ( 'ArrowRight' === event.key ) {
				showLightboxImage( lightboxIndex + 1 );
			} else if ( 'Tab' === event.key ) {
				trapFocus( event );
			}
		} );

		var touchStartX = null;

		lightboxEl.addEventListener( 'touchstart', function ( event ) {
			touchStartX = event.changedTouches[ 0 ].clientX;
		}, { passive: true } );

		lightboxEl.addEventListener( 'touchend', function ( event ) {
			if ( null === touchStartX ) {
				return;
			}

			var diff = event.changedTouches[ 0 ].clientX - touchStartX;

			if ( Math.abs( diff ) > 40 ) {
				if ( diff > 0 ) {
					showLightboxImage( lightboxIndex - 1 );
				} else {
					showLightboxImage( lightboxIndex + 1 );
				}
			}

			touchStartX = null;
		}, { passive: true } );
	}

	function trapFocus( event ) {
		var focusable = lightboxEl.querySelectorAll( 'button' );

		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last  = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function openLightbox( images, startIndex ) {
		buildLightbox();

		lightboxImages = images;
		lastFocusedEl  = document.activeElement;

		showLightboxImage( startIndex );
		toggleNavButtons();

		lightboxEl.hidden = false;
		document.body.style.overflow = 'hidden';

		var closeBtn = lightboxEl.querySelector( '.wpg-lightbox-close' );
		if ( closeBtn ) {
			closeBtn.focus();
		}
	}

	function toggleNavButtons() {
		var multi = lightboxImages.length > 1;
		var prev  = lightboxEl.querySelector( '.wpg-lightbox-prev' );
		var next  = lightboxEl.querySelector( '.wpg-lightbox-next' );

		if ( prev ) {
			prev.style.display = multi ? '' : 'none';
		}
		if ( next ) {
			next.style.display = multi ? '' : 'none';
		}
	}

	function showLightboxImage( index ) {
		if ( ! lightboxImages.length ) {
			return;
		}

		lightboxIndex = ( index + lightboxImages.length ) % lightboxImages.length;
		lightboxImgEl.src = lightboxImages[ lightboxIndex ];
	}

	function closeLightbox() {
		if ( ! lightboxEl ) {
			return;
		}

		lightboxEl.hidden = true;
		document.body.style.overflow = '';

		if ( lastFocusedEl && typeof lastFocusedEl.focus === 'function' ) {
			lastFocusedEl.focus();
		}
	}
} )();
