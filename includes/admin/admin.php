<?php
	
////////////////////////////
// DASHBOARD PAGE 
////////////////////////////

function rn_sps_admin_enqueue() {

	wp_enqueue_style( 'rn_sps_admin_css', RN_SPS_PLUGINS_URL . '/includes/admin/admin.css', [], RN_SPS_PLUGIN_VER );
	
}
add_action( 'admin_enqueue_scripts', 'rn_sps_admin_enqueue' );

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
		'Payment Suite Dashboard',
		'Dashboard',
		'manage_options',
		'payment-suite-payments',
		'rn_sps_render_dashboard_page',
    );
	
	add_submenu_page(
		'payment-suite-payments',
		'Payment Suite Checkout',
		'Checkout',
		'manage_options',
		'payment-suite-checkout',
		'rn_sps_render_checkout_page',
    );
	
	add_submenu_page(
		'payment-suite-payments',
		'Payment Suite Settings',
		'Settings',
		'manage_options',
		'payment-suite-settings',
		'rn_sps_render_settings_page',
    );
	
	
}
add_action( 'admin_menu', 'rn_sps_settings_menu', 99 );

function rn_sps_render_dashboard_page() {

	if ( !empty( $_POST['rn_sps'] ) ) {
		rn_sps_save_dashboard_page( $_POST['rn_sps'] );
	}
	
	?>
	<div class='wrap' class='rn-sps-settings-page' >
		<h1>Payment Suite Dashboard</h1>
		<div id='rn-sps-payment-history' >
			<h2>Payment History</h2>
			
			<div class='rn-sps-payments-cards'>
				<div class='rn-sps-card-heading'>
					<span >Amount</span>
					<span >Status</span>
					<span class='doublewidth'>Customer</span>
					<span class='doublewidth'>Date</span>
					<span >Payment Method</span>
				</div>
				<?php
				forEach ( rn_sps_stripe_get_charges() as $charge ) {
						
					$customer_dashboard_link = rn_sps_stripe_dashboard_url( '/customers/' . $charge->customer );
					$customer_name = $charge->customer;
										
					if( !empty( $charge->metadata->email ) ) {
						$customer_name = $charge->metadata->email;
					}		
					if( !empty( $charge->metadata->name ) ) {
						$customer_name = $charge->metadata->name;
					}
					
					$customer_td = "<a href='$customer_dashboard_link' target='_blank'>$customer_name</a>";
					
				?>
					<a class='rn-sps-card' href='<?= $customer_dashboard_link ?>' target='_blank'>
					
						<span>$<?php echo number_format ( $charge->amount / 100, 2 ) ?></span>
						<span style='<?php echo $charge->status === 'succeeded' ? 'color:#54CDBD;' : '' ?>' ><?php echo ucfirst( $charge->status ) ?></span>
						<span class='doublewidth'><?php echo $customer_name ?></span>
						<span class='doublewidth'><?php echo date( 'D, F j g:i a', $charge->created ) ?></span>
						<span><?php echo ucfirst( $charge->payment_method_details->card->brand ) . ' ' . $charge->payment_method_details->card->last4 ?></span>
						<span class="dashicons dashicons-external"></span></a>
					</a>
				<?php } ?>		
			</div>
		</div>
	</div>
<?php 
}

function rn_sps_save_dashboard_page( $form ) {
	$nonce = sanitize_text_field( $_POST['rn_sps_save_nonce'] );
	if( !wp_verify_nonce( $nonce, 'rn_sps_save_dashboard_page' ) ) {
		wp_die( 'Invalid authentication.  Please refresh the page or try logging in again.' );
	}
	
}

////////////////////////////
// CHECKOUT PAGE 
////////////////////////////

function rn_sps_render_checkout_page() {

	if ( !empty( $_POST['rn_sps'] ) ) {
		rn_sps_save_checkout_page( $_POST['rn_sps'] );
	}
	
	
	$settings = [

		'name' => [
			'name' => 'Product Name',
			'placeholder' => 'T-Shirt',
			'value' => get_option( 'rn_sps_name' ),
		],
		'description' => [
			'name' => 'Product Description',
			'placeholder' => '',
			'value' => get_option( 'rn_sps_description' ),
		],	
		'price' => [
			'name' => 'Price',
			'placeholder' => '9.99',
			'value' => get_option( 'rn_sps_price' ),
		],			
		'currency' => [
			'name' => 'Currency',
			'placeholder' => 'USD',
			'value' => get_option( 'rn_sps_currency' ),
		],	
		'require_name' => [
			'name' => 'Require Customer Name',
			'checked' => get_option( 'rn_sps_require_name' ),
		],			
		'require_email' => [
			'name' => 'Require Customer Email',
			'checked' => get_option( 'rn_sps_require_email' ),
		],	
		'require_phone' => [
			'name' => 'Require Customer Phone',
			'checked' => get_option( 'rn_sps_require_phone' ),
		],			
		'require_address' => [
			'name' => 'Require Customer Address',
			'checked' => get_option( 'rn_sps_require_address' ),
		],		
		'require_note' => [
			'name' => 'Require Note',
			'checked' => get_option( 'rn_sps_require_note' ),
		],		
	];	
	
	?>
	<div class='wrap' class='rn-sps-settings-page' >
		<h2>Checkout Settings</h2>
		
		<form action="<?php echo add_query_arg([]) ?>" method='post' >
			<table class='form-table' >
				<tr>
					<th>Shortcode</th>
					<td><input onclick='this.select()' readonly value='[stripe_checkout]' ></td>
				</tr>
				<?php 
				forEach ( $settings as $key => $value ) { 
					$td_html = isSet( $value['checked'] ) ? rn_sps_checkbox( $key, $value['checked'] ) : rn_sps_input( $key, $value['placeholder'], $value['value'] );
					if( !empty( $value['help'] ) ) {
						$td_html .= '<p class="description">' . $value['help'] . '</p>';
					}
					?>
					<tr>
						<th><?php echo $value['name'] ?></th>
						<td><?php echo $td_html ?></td>
					</tr>
				<?php } ?>
			</table>
			<?php wp_nonce_field( 'rn_sps_save_checkout_page', 'rn_sps_save_nonce' ) ?>
			<button class='button button-primary' type='submit'>Save</button>
		</form>

	</div>
<?php }

function rn_sps_save_checkout_page( $form ) {
	$nonce = sanitize_text_field( $_POST['rn_sps_save_nonce'] );
	if( !wp_verify_nonce( $nonce, 'rn_sps_save_checkout_page' ) ) {
		wp_die( 'Invalid authentication.  Please refresh the page or try logging in again.' );
	}
		
	$name = empty( $form[ 'name' ] ) ? '' : sanitize_text_field( $form[ 'name' ] );
	update_option( 'rn_sps_name', $name );
			
	$description = empty( $form[ 'description' ] ) ? '' : sanitize_text_field( $form[ 'description' ] );
	update_option( 'rn_sps_description', $description );
	
	$price = empty( $form[ 'price' ] ) ? '' : floatVal( $form[ 'price' ] );
	update_option( 'rn_sps_price', $price );
		
	$currency = empty( $form[ 'currency' ] ) ? '' : sanitize_text_field( $form[ 'currency' ] );
	update_option( 'rn_sps_currency', $currency );

	$require_name = empty( $form[ 'require_name' ] ) ? false : true;
	update_option( 'rn_sps_require_name', $require_name );
	
	$require_email = empty( $form[ 'require_email' ] ) ? false : true;
	update_option( 'rn_sps_require_email', $require_email );

	$require_phone = empty( $form[ 'require_phone' ] ) ? false : true;
	update_option( 'rn_sps_require_phone', $require_phone );
	
	$require_address = empty( $form[ 'require_address' ] ) ? false : true;
	update_option( 'rn_sps_require_address', $require_address );	
	
	$require_note = empty( $form[ 'require_note' ] ) ? false : true;
	update_option( 'rn_sps_require_note', $require_note );
	
	
}

////////////////////////////
// SETTINGS PAGE 
////////////////////////////


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

	
}
