<?php

function rn_sps_subscriptions_menu() {

	add_submenu_page(
		'payment-suite-payments',
		'Payment Suite Subscriptions',
		'Subscriptions',
		'manage_options',
		'payment-suite-subscriptions',
		'rn_sps_render_subscriptions_page',
    );
}
add_action( 'admin_menu', 'rn_sps_subscriptions_menu', 101 );

function rn_sps_render_subscriptions_page() {

	if ( !empty( $_POST['rn_sps'] ) ) {
		rn_sps_save_subscriptions_page( $_POST['rn_sps'] );
	}
		
	$settings = [

		'webhook_secret' => [
			'name' => 'Webhook Secret',
			'placeholder' => 'whsec_...',
			'value' => get_option( 'rn_sps_webhook_secret' ),
		],	

	];	
	?>
	<div class='wrap' id='rn-sps-subcriptions-page' >
		<h2>Subscription Settings</h2>
		<form action="<?= add_query_arg([]) ?>" method='post' >
			<table class='form-table' >			
				<?php forEach ( $settings as $key => $value ) { ?>
					<tr>
						<th><?= $value['name'] ?></th>
						<td><?= rn_sps_input( $key, $value['placeholder'], $value['value'] ) ?></td>
					</tr>				
				<?php } ?>	
			</table>
			<?php wp_nonce_field( 'rn_sps_save_subscriptions_page', 'rn_sps_save_nonce' ) ?>
			<button class='button button-primary' type='submit'>Save</button>
		</form>
		<div>
		<h2>Deploy</h2>
		<table class='form-table' >
			<tr>
				<th>Shortcode</th>
				<td><input readonly value='[stripe_confirm]'></td>
			</tr>				
			
		</table>
		</div>
	</div>
<?php }

function rn_sps_save_subscriptions_page( $form ) {
	$nonce = sanitize_text_field( $_POST['rn_sps_save_nonce'] );
	if( !wp_verify_nonce( $nonce, 'rn_sps_save_subscriptions_page' ) ) {
		wp_die( 'Invalid authentication.  Please refresh the page or try logging in again.' );
	}
	
	$create_user = empty( $form[ 'create_user' ] ) ? false : true;
	update_option( 'rn_sps_create_user', $create_user );
	
}
/*
function rn_sps_subscription_checkout( $session ) {
	
	$price_id = empty( $_REQUEST['price_id'] ) ? '' : sanitize_text_field( $_REQUEST['price_id'] );
	
	if( $price_id ) {
		$session['mode'] = 'subscription';
		$session['line_items'] = [
			[
				'price' => $price_id,
				'quantity' => 1,
			]
		];
	}
	return $session;
}
add_filter( 'rn_sps_session', 'rn_sps_subscription_checkout' );
*/
function rn_sps_confirm_shortcode(){
	
	$session_id = empty( $_GET['session_id'] ) ? '' : sanitize_text_field( $_GET['session_id'] );
	$session = rn_sps_stripe_get_session( $session_id );
	
	if( $session ) {
		echo json_encode( $session, JSON_PRETTY_PRINT );
		$customer_email = empty( $session->customer_details->email ) ? '' : $session->customer_details->email;
		$payment_status = empty( $session->payment_status ) ? '' : $session->payment_status;
		$amount_total = empty( $session->amount_total ) ? '' : $session->amount_total;
		
		echo "$customer_email $payment_status $amount_total";
		if ( get_option( 'rn_sps_create_user' ) ) {
			$password = wp_generate_password();
			$user_id = wp_create_user( $customer_email, $password, $customer_email );
	
			if ( is_wp_error( $user_id ) ) {
				rn_sps_log( $user_id->get_error_message(), 'wp error' );
				return $user_id->get_error_message();
			}
			
			if ( !empty( $session->subscription ) ) {
				update_user_meta( $user_id, 'subscription_id', $session->subscription );				
				update_user_meta( $user_id, 'subscription', rn_sps_stripe_get_subscription( $session->subscription ) );
			}
			
			if ( !empty( $session->customer ) ) {
				update_user_meta( $user_id, 'customer', $session->customer );		
			}
			
			return $user_id;
		}
	}
}
add_shortcode( 'stripe_confirm', 'rn_sps_confirm_shortcode' );

//STRIPE WEBHOOK
function rn_sps_ajax_stripe_webhook() {
	
	$timestamp_tolerence = 5 * MINUTE_IN_SECONDS;
	$input = @file_get_contents( 'php://input' );
	$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
	
	//CLOSE HTTP REQUEST
	session_write_close();
	fastcgi_finish_request();

	$header_parts = explode( ',', $sig_header );
	$timestamp = !empty( $header_parts[0] ) ? str_replace('t=', '', $header_parts[0] ) : false;
	$signature = !empty( $header_parts[1] ) ? str_replace('v1=', '', $header_parts[1] ) : false;
	$signed_payload = "$timestamp.$input";
	
	$hash = hash_hmac( 'sha256', $signed_payload, get_option( 'rn_sps_webhook_secret' ) );
	
	$is_valid = hash_equals( $hash, $signature );
	$timestamp_ok = current_time('timestamp') - $timestamp < $timestamp_tolerence;
	
	if ( !$timestamp_ok ) {
		rn_sps_log( $timestamp, 'rn_sps_ajax_stripe_webhook timestamp exceeded' );
	}
	
	if( $is_valid && $timestamp_ok ) {
		rn_sps_process_stripe_webhook_input( $input );
	}
	
}
add_action( 'wp_ajax_nopriv_stripe_webhook', 'rn_sps_ajax_stripe_webhook', 1 );
add_action( 'wp_ajax_stripe_webhook', 'rn_sps_ajax_stripe_webhook', 1 );

function rn_sps_process_stripe_webhook_input( $input ) {
	
	$event = json_decode( $input );
	$type = empty( $event->type ) ? false : $event->type;
	switch( $type ) {	
		case 'customer.subscription.updated' :
			rn_sps_stripe_customer_subscription_updated( $event );
			break;	
					
		case 'customer.subscription.deleted' :
			rn_sps_stripe_customer_subscription_deleted( $event );
			break;
			
		default:
			// do nothing;
	}
	
}

