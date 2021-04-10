<?php
	
////////////////////////////
// CHECKOUT PAGE 
////////////////////////////

function rn_sps_register_scripts() {
	
	wp_register_script( 'rn_sps_stripe', "https://js.stripe.com/v3/", [], RN_SPS_PLUGIN_VER, true );
	wp_register_script( 'rn_sps_checkout', RN_SPS_PLUGINS_URL . '/includes/checkout/checkout.js', [ 'jquery', 'rn_sps_stripe' ], RN_SPS_PLUGIN_VER, true );
	wp_localize_script( 'rn_sps_checkout', 'rnSpsCheckoutData', [
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'public_key' => get_option( 'rn_sps_public_key' ),
		'nonce'=> wp_create_nonce( 'rn_sps_checkout_nonce' ),
	] );	
}
add_action( 'init', 'rn_sps_register_scripts' ); 

function rn_sps_checkout_shortcode( $atts ){
	if( !empty( $_POST['stripeToken'] ) ) {
		rn_sps_do_checkout();
	}
	$text = empty( $atts['text'] ) ? 'Checkout' : sanitize_text_field( $atts['text'] );
	
	$name = get_option( 'rn_sps_name' );	
	$price = get_option( 'rn_sps_price' );		
	$currency = get_option( 'rn_sps_currency' );
	$require_name = get_option( 'rn_sps_require_name' );
	$require_email = get_option( 'rn_sps_require_email' );
	$require_phone = get_option( 'rn_sps_require_phone' );	
	$require_address = get_option( 'rn_sps_require_address' );	
	$require_note = get_option( 'rn_sps_require_note' );
	
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rn_sps_stripe' );
	wp_enqueue_script( 'rn_sps_checkout' );
	ob_start();
	?>
	
	<form class='payment-suite-checkout' action="" method="post" >
		<h2><?php echo $name . ' - ' . $price . ' ' . $currency ?></h2>
		<?php if ( $require_name ) {
			echo "<input type='text' name='rn_sps_name' placeholder='Jane Doe' required>";
		} ?>
		<?php if ( $require_email ) {
			echo "<input type='email' name='rn_sps_email' placeholder='jane@doe.com' required>";
		} ?>
		<?php if ( $require_phone ) {
			echo "<input type='tel' name='rn_sps_phone' placeholder='1-555-867-5309' required>";
		} ?>	
		<?php if ( $require_address ) {
			echo "<input type='text' name='rn_sps_address' placeholder='Address' required>";
		} ?>	
		<?php if ( $require_note ) {
			echo "<textarea name='rn_sps_description' required></textarea>";
		} ?>
		<div id="card-element"></div>
		<div id="card-errors" role="alert"></div>
		<button type='submit' id='payment-suite-submit-button' style='display:none;'></button>
		<button type='button' id='payment-suite-checkout-button'><?php echo $price . ' ' . $currency ?></button>
		<?php wp_nonce_field( 'fca_ssp_checkout', 'fca_ssp_checkout_nonce' ) ?>
	</form>
	<?php
	return ob_get_clean();
	
}
add_shortcode( 'stripe_checkout', 'rn_sps_checkout_shortcode' );

function rn_sps_do_checkout() {
	$nonce = empty( $_POST['fca_ssp_checkout_nonce'] ) ? '' : sanitize_text_field( $_POST['fca_ssp_checkout_nonce'] );
	
	if( !wp_verify_nonce(  $nonce, 'fca_ssp_checkout' ) ) {
		wp_die( 'Invalid authentication. Please refresh the page and try again.' );
	}
	
	$customer = rn_sps_create_stripe_customer();
	$charge = rn_sps_create_stripe_charge( $customer ); 
}