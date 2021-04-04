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

	$text = empty( $atts['text'] ) ? 'Checkout' : sanitize_text_field( $atts['text'] );
	$class = empty( $atts['class'] ) ? '' : sanitize_text_field( $atts['class'] );
	$price_id = empty( $atts['price_id'] ) ? '' : sanitize_text_field( $atts['price_id'] );
	
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'rn_sps_stripe' );
	wp_enqueue_script( 'rn_sps_checkout' );
	
	
	if( !empty( $price_id ) ) {
		return "<button class='payment-suite-checkout $class' data-price_id='$price_id'>$text</button>";
	}
	return "<button class='payment-suite-checkout $class' >$text</button>";
	
}
add_shortcode( 'stripe_checkout', 'rn_sps_checkout_shortcode' );

function rn_sps_checkout(){
	$nonce = empty( $_POST['nonce'] ) ? '' : sanitize_text_field( $_POST['nonce'] );
	$price_id = empty( $_POST['price_id'] ) ? '' : sanitize_text_field( $_POST['price_id'] );
	
	if( !wp_verify_nonce(  $nonce, 'rn_sps_checkout_nonce' ) ) {
		wp_send_json_error( 'Invalid authentication. Please refresh the page and try again.' );
	}
	
	$session_id = rn_sps_stripe_create_session( $price_id );
		
	if( $session_id ) {
		wp_send_json_success( $session_id );
	}
	wp_send_json_error();
}
add_action( 'wp_ajax_nopriv_rn_sps_checkout', 'rn_sps_checkout' );
add_action( 'wp_ajax_rn_sps_checkout', 'rn_sps_checkout' );