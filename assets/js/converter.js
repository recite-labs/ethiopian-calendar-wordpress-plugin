/**
 * Date converter: asks the plugin's REST API as the inputs change.
 * Without this script, each half submits as a normal form and PHP answers.
 */
( function () {
	'use strict';

	const api = ( window.ethiopianCalendarApi || {} ).root;
	if ( ! api ) {
		return;
	}

	document.querySelectorAll( '[data-ethcal-converter]' ).forEach( ( converter ) => {
		const params = { language: converter.dataset.language, numerals: converter.dataset.numerals };

		converter.querySelectorAll( 'form' ).forEach( ( form ) => {
			const output = form.querySelector( '[data-ethcal-result]' );
			const toEthiopian = 'to-ethiopian' === form.dataset.direction;
			let request = 0;

			function convert() {
				const data = new FormData( form );
				const query = new URLSearchParams( params );
				if ( toEthiopian ) {
					if ( ! data.get( 'ethcal_g' ) ) {
						return;
					}
					query.set( 'date', data.get( 'ethcal_g' ) );
				} else {
					query.set( 'ethiopian', [ data.get( 'ethcal_ey' ), data.get( 'ethcal_em' ), data.get( 'ethcal_ed' ) ].join( '-' ) );
				}
				const id = ++request;
				fetch( `${ api }/convert?${ query }` )
					.then( ( response ) => response.json().then( ( body ) => ( { ok: response.ok, body } ) ) )
					.then( ( { ok, body } ) => {
						if ( id !== request ) {
							return;
						}
						output.textContent = ok
							? ( toEthiopian ? body.ethiopian.text : body.gregorian.text )
							: body.message;
						output.classList.toggle( 'is-error', ! ok );
					} )
					.catch( () => {} );
			}

			form.addEventListener( 'input', convert );
			form.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				convert();
			} );
		} );
	} );
} )();
