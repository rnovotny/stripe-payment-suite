<?php

////////////////////////////
// FUNCTIONS
////////////////////////////
if( !class_exists('Stripe\Stripe') ) {
	include_once( RN_SPS_PLUGIN_DIR . '/includes/stripe/init.php' );	
}
use Stripe\Stripe;

function rn_sps_log( $message = '', $context = 'stripe plugin error' ) {
	if ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_LOG' ) ) {
		if( WP_DEBUG && WP_DEBUG_LOG ) {
			error_log( $context . ': ' . $message );			
		}
	}
}

function rn_sps_input( $name, $placeholder = '', $value = '', $type = 'text', $atts = '' ) {

	$value = esc_attr( $value );
	$html = "<div class='rn-sps-field rn-sps-field-$type'>";
	$html .= "<input $atts type='$type' placeholder='$placeholder' class='regular-text rn-sps-input-$type rn-sps-$name' name='rn_sps[$name]' value='$value'>";
	$html .= '</div>';
	
	return $html;	
}

function rn_sps_is_test_mode() {	
	return stripos( get_option( 'rn_sps_public_key' ), '_test_' ) OR stripos( get_option( 'rn_sps_secret_key' ), '_test_' );	
}

function rn_sps_stripe_dashboard_url( $path = '/' ) {	
	$url = "https://dashboard.stripe.com";
	if( rn_sps_is_test_mode() ){
		$url = "https://dashboard.stripe.com/test";
	}
	
	return $url . $path;
}

function rn_sps_stripe_create_session( $price_id = '' ) {
	try {
		
		$session = [
			'mode' => 'payment',
			'payment_method_types' => [ 'card' ],
			'line_items' => [ 
				[
					'quantity' => 1,
					'price_data' => [ 
						'currency' => get_option( 'rn_sps_currency' ),
						'unit_amount' => intVal( 100 * get_option( 'rn_sps_price' ) ), //RN NOTE: TO DO: HANDLE NON-100x CURRENCIES
						'product_data' => [
							'name' => get_option( 'rn_sps_name' ),
						],
					],
				]
			],
			'success_url' => get_option( 'rn_sps_success_url' ),
			'cancel_url' => get_option( 'rn_sps_cancel_url' ),
		];
		
		if( $price_id ) {
			$session['line_items'] = [
				[
					'price' => $price_id,
					'quantity' => 1,
				]
			];
		}
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$session = \Stripe\Checkout\Session::create( apply_filters( 'rn_sps_session', $session ) );	
		
		return empty( $session->id ) ? false : $session->id;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return false;
	}
}

function rn_sps_stripe_get_sessions() {
	try {

		$data = [];
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$response = \Stripe\Checkout\Session::all();
		
		$data = empty( $response->data ) ? false : $response->data;
		
		return $data;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return [];
	}
}

function rn_sps_stripe_get_prices() {
	try {

		$prices = [];
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$response = \Stripe\Price::all( [ 'limit' => 100 ] );
		
		$data = empty( $response->data ) ? false : $response->data;
		
		return $data;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return [];
	}
}

function rn_sps_stripe_get_payments() {
	try {

		$payments = [];
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$response = \Stripe\Charge::all( [ 'limit' => 10 ] );
		
		$data = empty( $response->data ) ? false : $response->data;
		
		return $data;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return [];
	}
}

function rn_sps_stripe_get_session( $session_id ) {
	try {
		
		$session_id = sanitize_text_field( $session_id );
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$session = \Stripe\Checkout\Session::retrieve( $session_id );	
		
		return empty( $session->id ) ? false : $session;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return false;
	}
}

function rn_sps_stripe_get_subscription( $subscription_id ) {
	try {
		
		$session_id = sanitize_text_field( $session_id );
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$session = \Stripe\Checkout\Session::retrieve( $session_id );	
		
		return empty( $session->id ) ? false : $session;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return false;
	}
}

function rn_sps_get_user_from_subscription_id( $subscription_id ) {
	
	if ( empty ( $subscription_id ) ) {
		return false;
	}
	
	$user = get_users( [
		'meta_key' => 'subscription_id',
		'meta_value' => $subscription_id,
		'number' => 1,
	] );
	
	if ( isSet( $user[0] ) ) {
		return $user[0];
	}
	return false;
}

function rn_sps_stripe_customer_subscription_updated( $event ) {
	
	$subscription = empty( $event->data->object ) ? false : $event->data->object;
	$subscription_id = empty( $subscription->id  ) ? false : $subscription->id; 
	
	$user = rn_sps_get_user_from_subscription_id( $subscription_id );
	
	if ( !empty( $user->ID ) ) {
		update_user_meta( $user->ID, 'subscription', $subscription );
	}
}

function rn_sps_stripe_customer_subscription_deleted( $event ) {
	
	$subscription = empty( $event->data->object ) ? false : $event->data->object;
	$subscription_id = empty( $subscription->id  ) ? false : $subscription->id; 
	
	$user = rn_sps_get_user_from_subscription_id( $subscription_id );
	
	if ( !empty( $user->ID ) ) {
		update_user_meta( $user->ID, 'subscription', $subscription );

	}
}