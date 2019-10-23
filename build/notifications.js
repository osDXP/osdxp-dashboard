/* global osdxpNotificationsSettings, jQuery */
(function( $ ) {
	'use strict';

	const closet = $( osdxpNotificationsSettings.selectorDrawer );
	const osdxpNotice = $( osdxpNotificationsSettings.selectorNotice );
	const inlineClass = 'osdxp-notice-inline';
	const notices = closet.find( 'div.error,div.notice,div.updated' );

	function reveal() {
		notices.filter( `.${inlineClass}` ).removeClass( `inline ${inlineClass}` );
		closet.insertAfter( osdxpNotice ).show();
		osdxpNotice.remove();
	}

	function getClass() {
		if ( closet.find( 'div.error,div.notice-error' ).length ) {
			return 'notice-error';
		}

		if ( closet.find( 'div.notice-warning' ).length ) {
			return 'notice-warning';
		}

		return 'notice-info';
	}

	if ( notices.length < osdxpNotificationsSettings.threshold ) {
		reveal();

		return;
	}

	notices.not( '.inline' ).addClass( `inline ${inlineClass}` );

	osdxpNotice.addClass( getClass() ).removeClass( 'hide-if-js' );

	osdxpNotice.find( 'button' ).click( reveal );
})( jQuery );
