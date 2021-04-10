<?php
	
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