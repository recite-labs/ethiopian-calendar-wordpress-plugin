/**
 * Ethiopian clock: advances the server-rendered time once a minute.
 *
 * Uses the site's UTC offset from the markup, so visitors in other timezones
 * see the site's local Ethiopian time. The day starts at 6:00: 7:00 is 1.
 */
( function () {
	'use strict';

	const clocks = document.querySelectorAll( '[data-ethcal-clock]' );
	if ( ! clocks.length ) {
		return;
	}

	function period( hour ) {
		if ( hour >= 6 && hour < 12 ) {
			return 'morning';
		}
		if ( hour >= 12 && hour < 18 ) {
			return 'afternoon';
		}
		return hour >= 18 ? 'evening' : 'night';
	}

	function tick() {
		clocks.forEach( ( clock ) => {
			const local = new Date( Date.now() + Number( clock.dataset.offset ) * 1000 );
			const hours = local.getUTCHours();
			const minutes = String( local.getUTCMinutes() ).padStart( 2, '0' );
			const shifted = ( ( hours - 6 ) % 12 + 12 ) % 12 || 12;
			const label = JSON.parse( clock.dataset.labels )[ period( hours ) ];
			const time = `${ shifted }:${ minutes }`;
			clock.querySelector( '[data-ethcal-clock-time]' ).textContent =
				'am' === clock.dataset.language ? `${ label } ${ time }` : `${ time } ${ label }`;
		} );
	}

	tick();
	window.setTimeout( () => {
		tick();
		window.setInterval( tick, 60000 );
	}, ( 60 - new Date().getSeconds() ) * 1000 );
} )();
