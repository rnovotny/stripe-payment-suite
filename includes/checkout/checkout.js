(function(){
	var $ = jQuery
	var $checkoutButtons = $( 'button.payment-suite-checkout' )
	if ( $checkoutButtons.length && typeof( Stripe ) !== 'undefined' && typeof( rnSpsCheckoutData ) !== 'undefined' && rnSpsCheckoutData.public_key ) {
		var stripe = Stripe( rnSpsCheckoutData.public_key )
		$checkoutButtons.click( function(){
			$.ajax({
				url: rnSpsCheckoutData.ajaxurl,
				type: 'POST',
				data: {
					"action": "rn_sps_checkout",
					"nonce": rnSpsCheckoutData.nonce,
					"price_id": $(this).data('price_id')
				}
			}).done( function( response ) {
				if( response && response.success ) {
					return stripe.redirectToCheckout( { sessionId: response.data } )				
				} else if( response.data ) {
					alert( response.data )
				} else {
					alert('an error occured :(')
				}
			})
		})
		
	}
})()