/**
 * Ethiopian Calendar settings screen.
 *
 * Progressive enhancement only: the form saves without this file. All date
 * text comes from PHP (pre-rendered previews, or the AJAX preview endpoint),
 * so there is no second date engine in JavaScript.
 */
( function () {
	'use strict';

	const form = document.querySelector( '[data-ethcal-form]' );
	if ( ! form ) {
		return;
	}

	const i18n = window.ethiopianCalendarAdmin || {};
	const previews = JSON.parse( form.querySelector( '[data-ethcal-previews]' ).textContent );
	const gregorian = JSON.parse( form.querySelector( '[data-ethcal-gregorian]' ).textContent );
	const withGregorian = ( text, which ) =>
		'both' === field( 'display' ).value ? `${ text } (${ gregorian[ which ] })` : text;
	const option = 'ethiopian_calendar_settings';
	const field = ( key ) => form.querySelector( `[name="${ option }[${ key }]"]:checked` );
	const customInput = form.querySelector( '[data-ethcal-custom]' );
	const customPreview = form.querySelector( '[data-ethcal-custom-preview]' );
	const sample = document.querySelector( '[data-ethcal-sample]' );
	const savebar = form.querySelector( '[data-ethcal-savebar]' );
	const status = form.querySelector( '[data-ethcal-status]' );

	let customTimer = 0;
	let customRequest = 0;

	/** Fetch a custom-format preview from PHP. */
	function previewCustom( language, numerals ) {
		const format = customInput.value.trim();
		if ( ! format ) {
			customPreview.textContent = '';
			return Promise.resolve( null );
		}
		const body = new URLSearchParams( {
			action: i18n.action,
			nonce: i18n.nonce,
			format,
			language,
			numerals,
		} );
		const id = ++customRequest;
		return fetch( i18n.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
			.then( ( response ) => response.json() )
			.then( ( result ) => {
				if ( id !== customRequest || ! result.success ) {
					return null;
				}
				customPreview.textContent = result.data.today;
				return result.data;
			} )
			.catch( () => null );
	}

	/** Refresh every preview from the current form state. */
	function refresh() {
		const language = field( 'language' ).value;
		const numerals = field( 'numerals' ).value;
		const preset = field( 'preset' ).value;
		const set = previews[ language ][ numerals ];

		Object.keys( set ).forEach( ( key ) => {
			form.querySelector( `[data-ethcal-preset="${ key }"]` ).textContent = set[ key ].today;
			form.querySelector( `[data-ethcal-code="${ key }"]` ).textContent = set[ key ].format;
		} );

		if ( 'custom' === preset && customInput.value.trim() ) {
			previewCustom( language, numerals ).then( ( data ) => {
				if ( data ) {
					sample.textContent = withGregorian( data.sample, 'sample' );
				}
			} );
		} else {
			sample.textContent = withGregorian( ( set[ preset ] || set[ '1' ] ).sample, 'sample' );
			previewCustom( language, numerals );
		}
	}

	/** Show whether the form differs from what was loaded. */
	const initial = new URLSearchParams( new FormData( form ) ).toString();
	function updateDirty() {
		const dirty = new URLSearchParams( new FormData( form ) ).toString() !== initial;
		savebar.classList.toggle( 'is-dirty', dirty );
		status.textContent = dirty ? i18n.dirty : i18n.clean;
	}

	form.addEventListener( 'change', () => {
		refresh();
		updateDirty();
	} );

	customInput.addEventListener( 'input', () => {
		const custom = form.querySelector( `[name="${ option }[preset]"][value="custom"]` );
		if ( customInput.value.trim() && ! custom.checked ) {
			custom.checked = true;
		}
		updateDirty();
		window.clearTimeout( customTimer );
		customTimer = window.setTimeout( refresh, 250 );
	} );

	form.addEventListener( 'reset', () => {
		// Let the browser restore the inputs first.
		window.setTimeout( () => {
			refresh();
			updateDirty();
		} );
	} );

	const copy = form.querySelector( '[data-ethcal-copy]' );
	copy.addEventListener( 'click', () => {
		const text = form.querySelector( '[data-ethcal-shortcode]' ).textContent;
		const done = () => {
			copy.textContent = i18n.copied;
			window.setTimeout( () => ( copy.textContent = i18n.copy ), 1500 );
		};
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( text ).then( done, () => {} );
		}
	} );

	refresh();
} )();
