jQuery( document ).ready( function ( e ) {

	if( jQuery( '.hit-count-reset' ).length ) {

		jQuery( '.hit-count-reset' ).click( function() {

			var post_id = jQuery( '#post_ID' ).val();

			jQuery.post(
				ajaxurl,
				{
					action: 'reset_hit_count',
					post_id: post_id
				},
				function( response ) {
					if( response ) {
						jQuery( '#post-views strong' ).html( response );
						jQuery( '#post-views strong' ).fadeOut( 100 ).fadeIn( 100 ).fadeOut( 100 ).fadeIn( 100 );
					}
				}
			);

		});

	}
});