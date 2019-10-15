/**
 * @toggle functionality for the upload module node in modules installed page
 *
 */

jQuery( document ).ready( function( $ ) {
	$('#osdxp-module-upload-button').click(function(){
		$('#osdxp-module-upload-field').toggle();
	});
});