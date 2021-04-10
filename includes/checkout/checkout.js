(function(){
	var $ = jQuery
	
	var stripe = Stripe( rnSpsCheckoutData.public_key )
	var style = {
	  base: {
		// Add your base input styles here. For example:
		"fontSize": "18px",
		"color": "#32325d",
		"lineHeight": "1.33"
	  }
	}
	var elements = stripe.elements()
	var cardElement = elements.create( 'card', { "style": style } )
	var cardErrorEl = document.getElementById( 'card-errors' )
	cardElement.mount('#card-element')
	cardElement.addEventListener('change', function( event ) {
		if ( event.error ) {
			cardErrorEl.textContent = event.error.message
		} else {
			cardErrorEl.textContent = ''
		}
	})
		
	$('#payment-suite-checkout-button').click(function(e) {
		
		var $thisForm = $(this).closest('form')
		var $button = $(this)
		var buttonHtml = $button.html()
		$button.prop( 'disabled', 'disabled' ).html('Submitting...')
		
		stripe.createToken( cardElement ).then( function( result ) {
			$button.prop( 'disabled', false ).html( buttonHtml )
			
			if ( result.error ) {
				cardErrorEl.textContent = result.error.message
				return false
			}
			
			// Send the token to your server.
			var hiddenInput = document.createElement('input')
			hiddenInput.setAttribute( 'type', 'hidden' )
			hiddenInput.setAttribute( 'name', 'stripeToken' )
			hiddenInput.setAttribute( 'id', 'stripeToken' )
			hiddenInput.setAttribute( 'value', result.token.id )
			$thisForm.append( hiddenInput )
			$thisForm.find('#payment-suite-submit-button').click()
		})	
	})	
	
})()