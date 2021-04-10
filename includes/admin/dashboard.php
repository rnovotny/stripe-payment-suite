<?php
	
////////////////////////////
// DASHBOARD PAGE 
////////////////////////////

function rn_sps_settings_menu() {

	add_menu_page(
		'Payment Suite',
		'Payment Suite',
		'manage_options',
		'payment-suite-payments',
		'rn_sps_render_dashboard_page',
		'dashicons-cart',
		76
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
		<h2>Payments Dashboard</h2>
		<table class='wp-list-table widefat fixed striped table-view-list' style='max-width:1024px;' >
			<thead>
			<tr><th>Date</th><th>Customer</th><th>Amount</th><th>Card</th><th>Status</th><th style='width:50px'></th></tr>
			</thead>
			<tbody>
			<?php 
			forEach ( rn_sps_stripe_get_charges() as $charge ) {
				
					
					$customer_dashboard_link = rn_sps_stripe_dashboard_url( '/customers/' . $charge->customer );
					$customer_name = $charge->customer;
					
					
					if( !empty( $charge->metadata->name ) OR !empty( $charge->metadata->email ) OR !empty( $charge->metadata->phone ) ){
											
						if( !empty( $charge->metadata->name ) ) {
							$customer_name = ' ' . $charge->metadata->name;
						}
						if( !empty( $charge->metadata->email ) ) {
							$customer_name .= ' ' . $charge->metadata->email;
						}		
						if( !empty( $charge->metadata->phone ) ) {
							$customer_name .= ' (+' . $charge->metadata->phone . ')';
						}				
					}
					
					$customer_td = "<a href='$customer_dashboard_link' target='_blank'>$customer_name</a>";
					
				
			?>
				<tr>
					<td><?php echo date( 'D, F j g:i a', $charge->created ) ?></td>
					<td><?php echo $customer_td ?></td>
					<td><?php echo $charge->amount / 100 ?></td>
					<td><?php echo $charge->payment_method_details->card->brand . ' ' . $charge->payment_method_details->card->last4 ?></td>
					<td><?php echo $charge->status ?></td>
					<td><a href='<?php echo rn_sps_stripe_dashboard_url( '/payments/' . $charge->id ) ?>' class='button' style='margin-left: 1px' target='_blank' >View</a></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
<?php 
}

function rn_sps_save_dashboard_page( $form ) {
	$nonce = sanitize_text_field( $_POST['rn_sps_save_nonce'] );
	if( !wp_verify_nonce( $nonce, 'rn_sps_save_dashboard_page' ) ) {
		wp_die( 'Invalid authentication.  Please refresh the page or try logging in again.' );
	}
	
}
