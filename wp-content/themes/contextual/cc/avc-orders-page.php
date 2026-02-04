<?php
/**
 * ACT Value Cards Orders
 */

add_action('admin_menu', 'cc_avc_orders_admin_pages');
function cc_avc_orders_admin_pages(){
	// add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', string $icon_url = '', int|float $position = null ): string
	add_menu_page('ACT Values Cards Orders', 'AVC Orders', 'manage_options', 'avc_orders', 'cc_avc_orders_page', 'dashicons-format-gallery');
}

function cc_avc_orders_page(){
	$orders = cc_avc_complated_orders();
	?>
	<h1>ACT Values Cards Orders</h1>

	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th style="text-align:left;">ID</th>
					<th style="text-align:left;">Date</th>
					<th style="text-align:left;">Who</th>
					<th style="text-align:right;">Qty</th>
					<th style="text-align:right;">Price</th>
					<th style="text-align:right;">PnP</th>
					<th style="text-align:right;">VAT</th>
					<th style="text-align:right;">Total</th>
					<th style="text-align:left;">Payment</th>
					<th style="text-align:left;">Ship</th>
					<th style="text-align:left;">Bill</th>
					<th style="text-align:left;">Invoice</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($orders as $order) { ?>
					<tr>
						<td>
							<?php echo $order['id']; ?>
						</td>
						<td>
							<?php
							$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $order['last_update'] );
							echo $date->format( 'd/m/Y H:i' );
							?>
						</td>
						<td>
							<?php
							echo $order['firstname'].' '.$order['lastname'].'<br>'.$order['email'].' '.$order['phone'];
							if( $order['user_id'] > 0 ){ ?>
								<a href="/wp-admin/user-edit.php?user_id=<?php echo $order['user_id']; ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a>
							<?php } ?>
						</td>
						<td style="text-align:right;">
							<?php echo $order['packs']; ?>
						</td>
						<td style="text-align:right;">
							<?php echo $order['currency'].' '.cc_money_format($order['pack_price'], $order['currency']); ?>
						</td>
						<td style="text-align:right;">
							<?php echo cc_money_format($order['pnp'], $order['currency']); ?>
						</td>
						<td style="text-align:right;">
							<?php echo cc_money_format($order['vat'], $order['currency']); ?>
						</td>
						<td style="text-align:right;">
							<?php echo cc_money_format($order['total'], $order['currency']); ?>
						</td>
						<td>
							<?php echo $order['pay_method']; ?>
						</td>
						<td>
							<?php echo cc_avc_order_address( $order, 'ship' ); ?>
						</td>
						<td>
							<?php echo cc_avc_order_address( $order, 'bill' ); ?>
						</td>
						<td>
							<?php
							if( $order['inv_address'] <> '' || $order['inv_email'] <> '' || $order['inv_phone'] <> '' || $order['inv_ref'] <> '' ){
								echo 'Address: '.$order['inv_address'].'<br>'
									.'Email: '.$order['inv_email'].'<br>'
									.'Phone: '.$order['inv_phone'].'<br>'
									.'Ref: '.$order['inv_ref'];
							}
							?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<?php
}
