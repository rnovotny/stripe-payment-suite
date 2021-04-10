<?php
	
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
