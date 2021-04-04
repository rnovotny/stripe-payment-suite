<?php
	
////////////////////////////
// SETTINGS PAGE 
////////////////////////////

function rn_sps_settings_menu() {

	add_menu_page(
		'Payment Suite',
		'Payment Suite',
		'manage_options',
		'payment-suite-payments',
		null,
		'dashicons-cart',
		76
	);
	add_submenu_page(
		'payment-suite-payments',
		'Payment Suite Settings',
		'Settings',
		'manage_options',
		'payment-suite-payments',
		'rn_sps_render_settings_page',
    );
	
	add_submenu_page(
		'payment-suite-payments',
		'Payment Suite Prices',
		'Prices',
		'manage_options',
		'payment-suite-prices',
		'rn_sps_render_prices_page',
    );
	
	add_submenu_page(
		'payment-suite-payments',
		'Payment Suite Sessions',
		'Sessions',
		'manage_options',
		'payment-suite-sessions',
		'rn_sps_render_sessions_page',
    );
}
add_action( 'admin_menu', 'rn_sps_settings_menu', 99 );

function rn_sps_render_settings_page() {

	if ( !empty( $_POST['rn_sps'] ) ) {
		rn_sps_save_settings_page( $_POST['rn_sps'] );
	}
	
	$settings = [

		'public_key' => [
			'name' => 'Publishable Key',
			'placeholder' => 'pk_...',
			'value' => get_option( 'rn_sps_public_key' ),
		],		
		'secret_key' => [
			'name' => 'Secret Key',
			'placeholder' => 'sk_...',
			'value' => get_option( 'rn_sps_secret_key' ),
			'help' => "Go to <a href='https://dashboard.stripe.com/apikeys' target='_blank'>https://dashboard.stripe.com/apikeys</a> to find your API keys.",
		],	
		'success_url' => [
			'name' => 'Success URL',
			'placeholder' => 'https://mysite.com/success',
			'value' => get_option( 'rn_sps_success_url' ),
		],
		'cancel_url' => [
			'name' => 'Cancel URL',
			'placeholder' => 'https://mysite.com/cancel',
			'value' => get_option( 'rn_sps_cancel_url' ),
		],	


	];	
	?>
	<div class='wrap' class='rn-sps-settings-page' >
		<h2>Stripe Settings</h2>
		<form action="<?php echo add_query_arg([]) ?>" method='post' >
			<table class='form-table' >
				<?php 
				forEach ( $settings as $key => $value ) { ?>
					<tr>
						<th><?php echo $value['name'] ?></th>
						<td><?php echo rn_sps_input( $key, $value['placeholder'], $value['value'] ) ?>
						<?php echo empty( $value['help'] ) ? '' : '<p class="description">' . $value['help'] . '</p>' ?></td>
					</tr>				
				<?php } ?>
			</table>
			<?php wp_nonce_field( 'rn_sps_save_settings_page', 'rn_sps_save_nonce' ) ?>
			<button class='button button-primary' type='submit'>Save</button>
		</form>

	</div>
<?php }

function rn_sps_save_settings_page( $form ) {
	$nonce = sanitize_text_field( $_POST['rn_sps_save_nonce'] );
	if( !wp_verify_nonce( $nonce, 'rn_sps_save_settings_page' ) ) {
		wp_die( 'Invalid authentication.  Please refresh the page or try logging in again.' );
	}
		
	$public_key = empty( $form[ 'public_key' ] ) ? '' : sanitize_text_field( $form[ 'public_key' ] );
	update_option( 'rn_sps_public_key', $public_key );
	
	$secret_key = empty( $form[ 'secret_key' ] ) ? '' : sanitize_text_field( $form[ 'secret_key' ] );
	update_option( 'rn_sps_secret_key', $secret_key );
	
	//PER STRIPE DOCS IT SHOULD ALLOW "{" AND "}" CHARACTERS - CANT USE URL SANITIZATION https://stripe.com/docs/payments/checkout/custom-success-page
	$success_url = empty( $form[ 'success_url' ] ) ? '' : sanitize_text_field( $form[ 'success_url' ] );
	update_option( 'rn_sps_success_url', $success_url );	
	
	$cancel_url = empty( $form[ 'cancel_url' ] ) ? '' : sanitize_text_field( $form[ 'cancel_url' ] );
	update_option( 'rn_sps_cancel_url', $cancel_url );
	
}

function rn_sps_render_prices_page() {

	if ( !empty( $_POST['rn_sps'] ) ) {
		rn_sps_save_prices_page( $_POST['rn_sps'] );
	}
	
	$settings = [

		'currency' => [
			'name' => 'Currency',
			'placeholder' => 'USD',
			'value' => get_option( 'rn_sps_currency' ),
		],	
		'name' => [
			'name' => 'Product Name',
			'placeholder' => 'T-Shirt',
			'value' => get_option( 'rn_sps_name' ),
		],	
		'price' => [
			'name' => 'Price',
			'placeholder' => '9.99',
			'value' => get_option( 'rn_sps_price' ),
		],		



	];	
	
	?>
	<div class='wrap' class='rn-sps-settings-page' >

		<h2>Prices</h2>
		<table class='wp-list-table widefat fixed striped table-view-list' style='max-width:952px;' >
			<tr><th>Name</th><th>Currency</th><th>Price</th><th style='width:360px;'>Shortcode</th><th width='100px;'></th></tr>
			<?php 
			forEach ( rn_sps_stripe_get_prices() as $price ) { ?>
				<tr>
					<td><?php echo $price->nickname ?></td>
					<td><?php echo $price->currency ?></td>
					<td><?php echo ( $price->unit_amount ) / 100 ?></td>
					<td><input type='text' style='width:100%;' id='<?php echo 'rn-sps-price-' . $price->id ?>' onclick='this.select();document.execCommand("copy")' readonly value='<?php echo '[stripe_checkout price_id="' . $price->id . '"]' ?> '></td>
					<td><button type='button' class='button' onclick='document.getElementById("rn-sps-price-<?php echo $price->id ?>").select();document.execCommand("copy")' >Copy</button>
					<a href='<?php echo rn_sps_stripe_dashboard_url( '/prices/' . $price->id ) ?>' class='button' style='margin-left: 1px' target='_blank' >Edit</a></td>
				</tr>			
			<?php } ?>
		</table>
		<a style='margin:20px 0 0 2px;' href='<?php echo rn_sps_stripe_dashboard_url( '/products/create' ) ?>' class='button button-primary' target='_blank' >Add</a>
		
				
		<h2>Custom Price</h2>
		<form action="<?php echo add_query_arg([]) ?>" method='post' >
			<table class='form-table' >
				<?php 
				forEach ( $settings as $key => $value ) { ?>
					<tr>
						<th><?php echo $value['name'] ?></th>
						<td><?php echo rn_sps_input( $key, $value['placeholder'], $value['value'] ) ?></td>
					</tr>				
				<?php } ?>
				<tr>
					<th>Shortcode</th>
					<td><input onclick='this.select()' readonly value='[stripe_checkout]' ></td>
				</tr>
			</table>
			<?php wp_nonce_field( 'rn_sps_save_prices_page', 'rn_sps_save_nonce' ) ?>
			<button class='button button-primary' type='submit'>Save</button>
		</form>
	</div>
<?php }
function rn_sps_save_prices_page( $form ) {
	$nonce = sanitize_text_field( $_POST['rn_sps_save_nonce'] );
	if( !wp_verify_nonce( $nonce, 'rn_sps_save_prices_page' ) ) {
		wp_die( 'Invalid authentication.  Please refresh the page or try logging in again.' );
	}
		
	$name = empty( $form[ 'name' ] ) ? '' : sanitize_text_field( $form[ 'name' ] );
	update_option( 'rn_sps_name', $name );
	
	$price = empty( $form[ 'price' ] ) ? '' : floatVal( $form[ 'price' ] );
	update_option( 'rn_sps_price', $price );
		
	$currency = empty( $form[ 'currency' ] ) ? '' : sanitize_text_field( $form[ 'currency' ] );
	update_option( 'rn_sps_currency', $currency );
	
}

function rn_sps_render_sessions_page() {
	
	?>
	<div class='wrap' class='rn-sps-settings-page' >
		<h2>Sessions</h2>
		<table class='wp-list-table widefat fixed striped table-view-list' style='max-width:952px;' >
			<tr><th>Amount</th><th>Status</th><th>Customer</th></tr>
			<?php 
			forEach ( rn_sps_stripe_get_sessions() as $session ) {
				$customer_td = '';
				if ( $session->customer ) {
					$customer_dashboard_link = rn_sps_stripe_dashboard_url( '/customers/' . $session->customer );
					$customer_td = "<a href='$customer_dashboard_link' target='_blank'>" . $session->customer_details->email ?? $session->customer . '</a>';
				}
			?>
				<tr>
					<td><?php echo $session->amount_total / 100 ?></td>
					<td><?php echo $session->payment_status ?></td>
					<td><?php echo $customer_td ?></td>
					
				</tr>			
			<?php } ?>
		</table>
	</div>
<?php }