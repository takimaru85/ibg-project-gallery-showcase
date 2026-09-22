/**
 * WordPress Project Gallery - admin script.
 *
 * Handles the Project Gallery meta box (WP Media uploader + sortable
 * reordering) and initializes the accent color picker on the settings page.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		initGalleryField();
		initColorPicker();
	} );

	function initColorPicker() {
		var $field = $( '.wpg-color-picker' );

		if ( $field.length && $.fn.wpColorPicker ) {
			$field.wpColorPicker();
		}
	}

	function initGalleryField() {
		var $field = $( '#wpg-gallery-field' );

		if ( ! $field.length ) {
			return;
		}

		var $list       = $field.find( '#wpg-gallery-list' );
		var $hiddenInput = $field.find( '#wpg_gallery_images' );
		var $addButton  = $field.find( '#wpg-add-images' );
		var frame       = null;

		var i18n = ( typeof wpgAdmin !== 'undefined' && wpgAdmin.i18n ) ? wpgAdmin.i18n : {};

		function syncHiddenInput() {
			var ids = $list.find( '.wpg-gallery-item' ).map( function () {
				return $( this ).data( 'id' );
			} ).get();

			$hiddenInput.val( ids.join( ',' ) );
		}

		function addImages( attachments ) {
			$.each( attachments, function ( i, attachment ) {
				if ( $list.find( '.wpg-gallery-item[data-id="' + attachment.id + '"]' ).length ) {
					return; // Skip duplicates already in the gallery.
				}

				var thumbUrl = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				var $item = $(
					'<li class="wpg-gallery-item" data-id="' + attachment.id + '">' +
						'<img src="' + thumbUrl + '" alt="" />' +
						'<button type="button" class="wpg-remove-image" aria-label="' + ( i18n.remove || 'Remove image' ) + '">&times;</button>' +
					'</li>'
				);

				$list.append( $item );
			} );

			syncHiddenInput();
		}

		$addButton.on( 'click', function ( event ) {
			event.preventDefault();

			if ( ! window.wp || ! wp.media ) {
				return;
			}

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: ( i18n.modalTitle || 'Select Project Images' ),
				button: { text: ( i18n.modalButton || 'Add to gallery' ) },
				multiple: 'add',
				library: { type: 'image' },
			} );

			frame.on( 'select', function () {
				var selection = frame.state().get( 'selection' ).toJSON();
				addImages( selection );
			} );

			frame.open();
		} );

		$list.on( 'click', '.wpg-remove-image', function ( event ) {
			event.preventDefault();
			$( this ).closest( '.wpg-gallery-item' ).remove();
			syncHiddenInput();
		} );

		if ( $.fn.sortable ) {
			$list.sortable( {
				items: '.wpg-gallery-item',
				cursor: 'move',
				update: syncHiddenInput,
			} );
		}
	}
} )( jQuery );
