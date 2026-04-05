<?php
/*
Plugin Name: Kleinanzeigen
Description: Affiliate-System-Plugin fuer das PSOURCE-Kleinanzeigen-Plugin
Author URI: https://n3rds.work/docs/classifieds-handbuch/
Depends: ps-kleinanzeigen/loader.php
Class: Classifieds_Core
*/

define( 'AFF_KLEINANZEIGEN_ADDON', 1 );

$cf_plugin_active = (
	affiliate_is_plugin_active( 'ps-kleinanzeigen/loader.php' ) ||
	affiliate_is_plugin_active_for_network( 'ps-kleinanzeigen/loader.php' ) ||
	class_exists( 'Classifieds_Core' )
);

if ( $cf_plugin_active ) {
	add_action( 'classifieds_affiliate_credit_purchase', 'cf_affiliate_credit_purchase', 10, 4 );
	add_action( 'classifieds_affiliate_one_time_purchase', 'cf_affiliate_one_time_purchase', 10, 4 );
	add_action( 'classifieds_affiliate_settings', 'cf_affiliate_settings' );
}

function cf_affiliate_credit_purchase( $affiliate_settings, $user_id, $order_id, $credit_packages ) {
	global $blog_id, $site_id;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) || empty( $order_id ) || empty( $credit_packages ) || ! is_array( $credit_packages ) ) {
		return;
	}

	if ( function_exists( 'get_user_meta' ) ) {
		$aff = get_user_meta( $user_id, 'affiliate_referred_by', true );
		$credit_paid = get_user_meta( $user_id, 'cf_affiliate_credit_paid', true );
	} else {
		$aff = get_usermeta( $user_id, 'affiliate_referred_by' );
		$credit_paid = get_usermeta( $user_id, 'cf_affiliate_credit_paid' );
	}

	if ( empty( $aff ) ) {
		return;
	}

	$pay_future = ! empty( $affiliate_settings['cf_credit_pay_future'] );
	if ( ! $pay_future && 'yes' === $credit_paid ) {
		return;
	}

	$commissions = isset( $affiliate_settings['cf_credit_commissions'] ) && is_array( $affiliate_settings['cf_credit_commissions'] ) ? $affiliate_settings['cf_credit_commissions'] : array();
	$total_amount = 0;
	$package_notes = array();
	$paid_packages = array();

	foreach ( $credit_packages as $credit_package ) {
		$product_id = isset( $credit_package['product_id'] ) ? absint( $credit_package['product_id'] ) : 0;
		$quantity = isset( $credit_package['quantity'] ) ? absint( $credit_package['quantity'] ) : 1;
		$label = isset( $credit_package['label'] ) ? sanitize_text_field( $credit_package['label'] ) : '';

		if ( $product_id <= 0 || $quantity <= 0 || empty( $commissions[ $product_id ] ) ) {
			continue;
		}

		$commission = cf_affiliate_calculate_commission(
			$commissions[ $product_id ],
			isset( $credit_package['price'] ) ? $credit_package['price'] : 0,
			$quantity
		);
		if ( $commission <= 0 ) {
			continue;
		}

		$total_amount += $commission;
		$package_notes[] = $label ? $label . ' x' . $quantity : sprintf( __( 'Paket #%d x%d', 'affiliate' ), $product_id, $quantity );
		$paid_packages[] = array(
			'product_id' => $product_id,
			'label'      => $label,
			'quantity'   => $quantity,
			'commission' => number_format( $commission, 2, '.', '' ),
			'rule'       => $commissions[ $product_id ],
		);
	}

	if ( $total_amount <= 0 ) {
		return;
	}

	$meta = array(
		'affiliate_settings' => $affiliate_settings,
		'user_id'            => $user_id,
		'order_id'           => $order_id,
		'packages'           => $paid_packages,
		'blog_id'            => $blog_id,
		'site_id'            => $site_id,
		'current_user_id'    => get_current_user_id(),
		'LOCAL_URL'          => ( is_ssl() ? 'https://' : 'http://' ) . esc_attr( $_SERVER['HTTP_HOST'] ) . esc_attr( $_SERVER['REQUEST_URI'] ),
		'IP'                 => ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? esc_attr( $_SERVER['HTTP_X_FORWARD_FOR'] ) : esc_attr( $_SERVER['REMOTE_ADDR'] ),
	);

	$note = __( 'Kleinanzeigen Credit-Paket', 'affiliate' );
	if ( ! empty( $package_notes ) ) {
		$note .= ': ' . implode( ', ', $package_notes );
	}

	do_action( 'affiliate_purchase', $aff, number_format( $total_amount, 2, '.', '' ), 'paid:classifieds-credit', $order_id, $note, $meta );

	if ( ! $pay_future ) {
		if ( function_exists( 'update_user_meta' ) ) {
			update_user_meta( $user_id, 'cf_affiliate_credit_paid', 'yes' );
		} else {
			update_usermeta( $user_id, 'cf_affiliate_credit_paid', 'yes' );
		}
	}
}

function cf_affiliate_one_time_purchase( $affiliate_settings, $user_id, $order_id, $one_time_purchase ) {
	global $blog_id, $site_id;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) || empty( $order_id ) || empty( $one_time_purchase ) || ! is_array( $one_time_purchase ) ) {
		return;
	}

	if ( function_exists( 'get_user_meta' ) ) {
		$aff = get_user_meta( $user_id, 'affiliate_referred_by', true );
	} else {
		$aff = get_usermeta( $user_id, 'affiliate_referred_by' );
	}

	if ( empty( $aff ) ) {
		return;
	}

	$rule = isset( $affiliate_settings['cf_one_time_commission'] ) ? $affiliate_settings['cf_one_time_commission'] : array();
	$quantity = isset( $one_time_purchase['quantity'] ) ? absint( $one_time_purchase['quantity'] ) : 1;
	$price = isset( $one_time_purchase['price'] ) ? $one_time_purchase['price'] : 0;
	$amount = cf_affiliate_calculate_commission( $rule, $price, $quantity );
	if ( $amount <= 0 ) {
		return;
	}

	$meta = array(
		'affiliate_settings' => $affiliate_settings,
		'user_id'            => $user_id,
		'order_id'           => $order_id,
		'purchase'           => $one_time_purchase,
		'blog_id'            => $blog_id,
		'site_id'            => $site_id,
		'current_user_id'    => get_current_user_id(),
		'LOCAL_URL'          => ( is_ssl() ? 'https://' : 'http://' ) . esc_attr( $_SERVER['HTTP_HOST'] ) . esc_attr( $_SERVER['REQUEST_URI'] ),
		'IP'                 => ( isset( $_SERVER['HTTP_X_FORWARD_FOR'] ) ) ? esc_attr( $_SERVER['HTTP_X_FORWARD_FOR'] ) : esc_attr( $_SERVER['REMOTE_ADDR'] ),
	);

	$label = empty( $one_time_purchase['label'] ) ? __( 'Einmalzahlung', 'affiliate' ) : $one_time_purchase['label'];
	$note = sprintf( __( 'Kleinanzeigen Einmalzahlung: %s', 'affiliate' ), $label );

	do_action( 'affiliate_purchase', $aff, number_format( $amount, 2, '.', '' ), 'paid:classifieds-one-time', $order_id, $note, $meta );
}

function cf_affiliate_calculate_commission( $rule, $base_price, $quantity ) {
	if ( empty( $rule ) || ! is_array( $rule ) ) {
		return 0;
	}

	$mode = ( isset( $rule['mode'] ) && 'percent' === $rule['mode'] ) ? 'percent' : 'fixed';
	$value = isset( $rule['value'] ) ? (float) $rule['value'] : 0;
	$quantity = max( 1, absint( $quantity ) );
	$base_price = (float) str_replace( ',', '.', (string) $base_price );

	if ( $value <= 0 ) {
		return 0;
	}

	if ( 'percent' === $mode ) {
		return round( ( $base_price * $quantity ) * ( $value / 100 ), 2 );
	}

	return round( $value * $quantity, 2 );
}

function cf_affiliate_settings( $affiliate_settings ) {
	$one_time = isset( $affiliate_settings['one_time'] ) && is_array( $affiliate_settings['one_time'] ) ? $affiliate_settings['one_time'] : array();
	$credit_packages = isset( $affiliate_settings['credit_packages'] ) && is_array( $affiliate_settings['credit_packages'] ) ? $affiliate_settings['credit_packages'] : array();
	$costs = isset( $affiliate_settings['cost'] ) && is_array( $affiliate_settings['cost'] ) ? $affiliate_settings['cost'] : array();
	$commissions = isset( $costs['cf_credit_commissions'] ) && is_array( $costs['cf_credit_commissions'] ) ? $costs['cf_credit_commissions'] : array();
	$one_time_rule = isset( $costs['cf_one_time_commission'] ) && is_array( $costs['cf_one_time_commission'] ) ? $costs['cf_one_time_commission'] : array();
	$pay_future = ! empty( $costs['cf_credit_pay_future'] );
	?>
	<form method="post" class="affiliate_settings" id="affiliate_settings">
		<table class="table">
			<?php if ( ! empty( $one_time['enabled'] ) ) : ?>
			<tr>
				<td>
					<label><strong><?php echo esc_html( $one_time['label'] ); ?></strong></label>
					<br />
					<span class="description"><?php echo esc_html( sprintf( __( 'Einmalzahlung fuer %s', 'affiliate' ), $one_time['price'] ) ); ?></span>
					<br /><br />
					<select name="cf_one_time_commission_mode">
						<option value="fixed" <?php selected( empty( $one_time_rule['mode'] ) ? 'fixed' : $one_time_rule['mode'], 'fixed' ); ?>><?php _e( 'Festbetrag', 'affiliate' ); ?></option>
						<option value="percent" <?php selected( empty( $one_time_rule['mode'] ) ? 'fixed' : $one_time_rule['mode'], 'percent' ); ?>><?php _e( 'Prozent', 'affiliate' ); ?></option>
					</select>
					<input type="text" name="cf_one_time_commission_value" value="<?php echo esc_attr( isset( $one_time_rule['value'] ) ? $one_time_rule['value'] : '' ); ?>" class="small-text" placeholder="5.00" />
					<span class="cf-affiliate-unit description"><?php echo ( isset( $one_time_rule['mode'] ) && 'percent' === $one_time_rule['mode'] ) ? '%' : 'EUR'; ?></span>
					<span class="description"><?php _e( 'Provision fuer die Einmalzahlung.', 'affiliate' ); ?></span>
				</td>
			</tr>
			<tr>
				<td><br /></td>
			</tr>
			<?php endif; ?>
			<tr>
				<td>
					<label>
						<input type="checkbox" name="cf_credit_pay_future" value="1" <?php checked( $pay_future ); ?> />
						<?php _e( 'Affiliate auch an zukuenftigen Credit-Paket-Kaeufen beteiligen', 'affiliate' ); ?>
					</label>
					<br />
					<span class="description"><?php _e( 'Wenn deaktiviert, wird nur der erste erfolgreiche Kauf eines Kleinanzeigen-Credit-Pakets provisioniert.', 'affiliate' ); ?></span>
					<br />
					<span class="description"><?php _e( 'Bei Prozent wird der Wert auf den Paket- oder Einmalzahlungs-Preis angewendet. Bei Festbetrag gilt der Betrag pro gekauftem Paket bzw. pro Einmalzahlung.', 'affiliate' ); ?></span>
				</td>
			</tr>
			<tr>
				<td><br /></td>
			</tr>
			<?php if ( empty( $credit_packages ) ) : ?>
				<tr>
					<td>
						<span class="description"><?php _e( 'Noch keine Credit-Pakete vorhanden. Lege zuerst in Kleinanzeigen > Zahlungen deine Pakete an.', 'affiliate' ); ?></span>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $credit_packages as $credit_package ) : ?>
					<?php
					$product_id = isset( $credit_package['product_id'] ) ? absint( $credit_package['product_id'] ) : 0;
					if ( $product_id <= 0 ) {
						continue;
					}
					$label = isset( $credit_package['label'] ) ? $credit_package['label'] : sprintf( __( 'Paket #%d', 'affiliate' ), $product_id );
					$credits = isset( $credit_package['credits'] ) ? absint( $credit_package['credits'] ) : 0;
					$price = isset( $credit_package['price'] ) ? $credit_package['price'] : '0.00';
					$current_rule = isset( $commissions[ $product_id ] ) && is_array( $commissions[ $product_id ] ) ? $commissions[ $product_id ] : array();
					?>
					<tr>
						<td>
							<label for="cf_credit_commissions_<?php echo esc_attr( $product_id ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
							<br />
							<span class="description"><?php echo esc_html( sprintf( __( '%d Credits fuer %s', 'affiliate' ), $credits, $price ) ); ?></span>
							<br /><br />
							<select name="cf_credit_commission_mode[<?php echo esc_attr( $product_id ); ?>]">
								<option value="fixed" <?php selected( empty( $current_rule['mode'] ) ? 'fixed' : $current_rule['mode'], 'fixed' ); ?>><?php _e( 'Festbetrag', 'affiliate' ); ?></option>
								<option value="percent" <?php selected( empty( $current_rule['mode'] ) ? 'fixed' : $current_rule['mode'], 'percent' ); ?>><?php _e( 'Prozent', 'affiliate' ); ?></option>
							</select>
							<input type="text" id="cf_credit_commissions_<?php echo esc_attr( $product_id ); ?>" name="cf_credit_commission_value[<?php echo esc_attr( $product_id ); ?>]" value="<?php echo esc_attr( isset( $current_rule['value'] ) ? $current_rule['value'] : '' ); ?>" class="small-text" placeholder="5.00" />
							<span class="cf-affiliate-unit description"><?php echo ( isset( $current_rule['mode'] ) && 'percent' === $current_rule['mode'] ) ? '%' : 'EUR'; ?></span>
							<span class="description"><?php _e( 'Provision fuer dieses Credit-Paket.', 'affiliate' ); ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>
		<script type="text/javascript">
			(function($){
				function updateAffiliateUnit($select) {
					var unit = $select.val() === 'percent' ? '%' : 'EUR';
					$select.nextAll('.cf-affiliate-unit').first().text(unit);
				}

				$(function(){
					$('#affiliate_settings').on('change', 'select[name="cf_one_time_commission_mode"], select[name^="cf_credit_commission_mode["]', function(){
						updateAffiliateUnit($(this));
					});

					$('#affiliate_settings select[name="cf_one_time_commission_mode"], #affiliate_settings select[name^="cf_credit_commission_mode["]').each(function(){
						updateAffiliateUnit($(this));
					});
				});
			})(jQuery);
		</script>
		<p class="submit">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="hidden" name="key" value="affiliate_settings" />
			<input type="submit" class="button-primary" name="save" value="Änderungen speichern">
		</p>
	</form>
	<?php
}

?>