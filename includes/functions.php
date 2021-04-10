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

function rn_sps_checkbox( $name, $checked = '', $value = 1, $atts = '' ) {

	$checked = empty( $checked ) ? '' : "checked='checked'";			
	$html = "<div class='rn-sps-field rn-sps-field-checkbox'>";
	$html .= "<input $atts type='checkbox' class='rn-sps-input-checkbox rn-sps-$name' name='rn_sps[$name]' value='$value' $checked>";
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

function rn_sps_stripe_get_charges() {
	try {

		$payments = [];
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );
		$response = \Stripe\Charge::all();
		
		$data = empty( $response->data ) ? false : $response->data;
		
		return $data;
	
	} catch ( Exception $e ) {
		rn_sps_log( $e->getMessage(), 'stripe exception' );
		return [];
	}
}


function rn_sps_create_stripe_customer() {
	try {
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );	
		$stripe_token = empty( $_POST['stripeToken'] ) ? false : sanitize_text_field( $_POST['stripeToken'] );
		
		if( $stripe_token ) {
			$customer = [
				'source' => $stripe_token
			];
			
			if( !empty( $_POST['rn_sps_email'] ) ) {
				$customer['email'] = sanitize_text_field( $_POST['rn_sps_email'] );
			}
			
			if( !empty( $_POST['rn_sps_name'] ) ) {
				$customer['name'] = sanitize_text_field( $_POST['rn_sps_name'] );
			}
			
			if( !empty( $_POST['rn_sps_phone'] ) ) {
				$customer['phone'] = sanitize_text_field( $_POST['rn_sps_phone'] );
			}
			
			if( !empty( $_POST['rn_sps_address'] ) ) {
				$address = sanitize_text_field( $_POST['rn_sps_address'] );
				$customer['address']['line1'] = $address;
			}	
			if( !empty( $_POST['rn_sps_description'] ) ) {
				$customer['description'] = sanitize_text_field( $_POST['rn_sps_description'] );
				
			}
			
			return \Stripe\Customer::create( $customer );
		}
		return false;
	} catch (Exception $e) {
		rn_sps_log( $e->getMessage(), 'stripe error' );
		$msg = $e->getMessage() ?? '';
		$msg .=  '<br>Contact support@landingcube.com for assistance or try again.';
		return new WP_Error( 'create_change_failed', $msg );
	}
}

function rn_sps_create_stripe_charge( $customer ) {
	try {
		$charge = [
			'amount' => intVal( 100 * get_option( 'rn_sps_price' ) ), //RN NOTE: TO DO: HANDLE NON-100x CURRENCIES,
			'currency' => get_option( 'rn_sps_currency' ),
			'description' => get_option( 'rn_sps_name' ),
			'customer' => $customer->id,
		];
		if( !empty( $customer->email ) ) {
			$charge['metadata']['email'] = $customer->email;
		}
		if( !empty( $customer->name ) ) {
			$charge['metadata']['name'] = $customer->name;
		}		
		if( !empty( $customer->phone ) ) {
			$charge['metadata']['phone'] = $customer->phone;
		}		
		if( !empty( $customer->address->line1 ) ) {
			$charge['metadata']['address'] = $customer->address->line1;
		}
		if( !empty( $customer->description ) ) {
			$charge['metadata']['note'] =  $customer->description;
		}
		
		\Stripe\Stripe::setApiKey( get_option( 'rn_sps_secret_key' ) );	
		return \Stripe\Charge::create( $charge );
		
	} catch (Exception $e) {
		rn_sps_log( $e->getMessage(), 'stripe error' );
		$msg = $e->getMessage() ?? '';
		$msg .=  '<br>Contact support@landingcube.com for assistance or try again.';
		return new WP_Error( 'create_change_failed', $msg );
	}
}


