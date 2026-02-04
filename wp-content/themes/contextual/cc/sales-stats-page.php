<?php
/**
 * Sales Stats Search/display page
 */

add_action('admin_menu', 'cc_sales_stats_admin_pages');
function cc_sales_stats_admin_pages(){
	// add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', string $icon_url = '', int|float $position = null ): string
	add_menu_page('Sales Stats', 'Sales Stats', 'manage_options', 'cc_sales_stats', 'cc_sales_stats_weekly', 'dashicons-performance');
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int|float $position = null ): string|false	
	add_submenu_page( 'cc_sales_stats', 'Sales Stats Weekly', 'Weekly', 'manage_options', 'cc_sales_stats', 'cc_sales_stats_weekly');
	add_submenu_page( 'cc_sales_stats', 'Sales Stats Daily', 'Daily', 'manage_options', 'cc_sales_stats_daily', 'cc_sales_stats_daily');
	add_submenu_page( 'cc_sales_stats', 'Sales Stats Categories', 'Categories', 'manage_options', 'cc_sales_stats_cats', 'cc_sales_stats_cats');
}

function cc_sales_stats_weekly(){
	// ccpa_write_log('function cc_sales_stats_weekly');
	add_thickbox();
	?>
	<h1>Sales Stats (Weekly)</h1>

	<?php
	$start_ddmmyyyy = date('d/m/Y', strtotime('-3 months'));
	$start_date = date('Y-m-d', strtotime('-3 months'));
	$end_ddmmyyyy = date('d/m/Y');
	$end_date = date('Y-m-d');

	if(isset($_GET['sd']) || isset($_GET['ed'])){
		if(isset($_GET['sd']) && $_GET['sd'] <> ''){
			$datetime = DateTime::createFromFormat("d/m/Y", $_GET['sd']);
			if($datetime){
				$start_ddmmyyyy = $datetime->format('d/m/Y');
				$start_date = $datetime->format('Y-m-d');
			}
		}
		if(isset($_GET['ed']) && $_GET['ed'] <> ''){
			$datetime = DateTime::createFromFormat("d/m/Y", $_GET['ed']);
			if($datetime){
				$end_ddmmyyyy = $datetime->format('d/m/Y');
				$end_date = $datetime->format('Y-m-d');
			}
		}
	}
	?>

	<div class="cc-sales-stats-search-wrap">
		<form action="<?php echo admin_url('admin.php'); ?>" method="get">
			<input type="hidden" name="page" value="cc_sales_stats">
			<table>
				<tr>
					<td><strong><label for="start-date">Start date (dd/mm/yyyy) inclusive:</label></strong></td>
					<td width="250px"><input type="text" id="start-date" name="sd" class="widefat" value="<?php echo $start_ddmmyyyy; ?>"></td>
					<td><strong><label for="end-date">End date (dd/mm/yyyy) inclusive:</label></strong></td>
					<td width="250px"><input type="text" id="end-date" name="ed" class="widefat" value="<?php echo $end_ddmmyyyy; ?>"></td>
					<td><input type="submit" id="cc-sales-stats-search-submit" name="" class="button button-primary" value="Go"></td>
				</tr>
			</table>
		</form>
	</div>

	<?php
	// ccpa_write_log('about to get workshop results');
	$data = cc_stats_get_results($start_date, $end_date);
	// ccpa_write_log('got workshop results');
	// var_dump($stats);
	$sales_stats_cats = get_option('sales_stats_cats', array());
	?>

	<h2>Categories</h2>
	<table class="table-condensed cc-stats-cats-key-table">
		<tr>
			<?php 
			foreach ($sales_stats_cats as $key => $cat) {
				?>
				<td style="background:<?php echo $cat['colour']; ?>;"><?php echo $cat['cat_name']; ?></td>
				<?php
			} ?>
		</tr>
	</table>

	<p>&nbsp;</p>

	<?php // ccpa_write_log('assembling workshop table'); ?>

	<h2>Live training</h2>
	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th>Week Comm.</th>
					<?php foreach ($data['workshops'] as $training_id) { ?>
						<th valign="top"><?php
							$title = get_the_title($training_id);
							echo '<span title="'.$title.'">'.$training_id.':<br>'.substr($title, 0, 25).'</span>';
						?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($data['workshops'] as $training_id) { ?>
						<th><?php
							$totals = cc_stats_cumulative($training_id);
							// echo (int) get_post_meta($training_id, 'cumulative_registrations', true);
							// echo $totals['registrations'];
							echo "<span title='Attendees: ".$totals['attendees']."<br>Registrations: ".$totals['registrations']."<br>Upsells: ".$totals['upsells']."'>".$totals['attendees']."</span>";
							echo '<br>&pound;';
							// echo number_format( (float) get_post_meta($training_id, 'cumulative_reg_amount', true), 2);
							echo number_format( $totals['amount'], 2 );
						?></th>
					<?php } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// set up the first (last) Monday to report from
				$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $end_date.' 00:00:00', new DateTimeZone('UTC'));
				if($datetime->format('D') <> 'Mon'){
					$datetime->modify('last monday');
				}

				$row_date = $datetime->format('Y-m-d');
				while ($row_date >= $start_date) {
					?>
					<tr>
						<td valign="top"><?php echo $datetime->format('d/m/Y'); ?></td>
						<?php
						$total_regs = $total_upsells = $total_attendees = $total_amount = 0;
						foreach ($data['workshops'] as $training_id) {

							if( isset( $data['stats'][$row_date][$training_id]['promoted'] ) && $data['stats'][$row_date][$training_id]['promoted'] > 0 ){
								echo '<td class="promoted text-center" valign="top">';
							}elseif( isset( $data['stats'][$row_date][$training_id]['category'] ) && $data['stats'][$row_date][$training_id]['category'] <> '' ){
								$cat_key = (int) $data['stats'][$row_date][$training_id]['category'];
								echo '<td class="text-center" valign="top" style="background:'.$sales_stats_cats[$cat_key]['colour'].';">';
							}else{
								echo '<td class="text-center" valign="top">';
							}

							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&wk='.$row_date.'&t='.$training_id.'" target="_blank"'
									." title='Attendees: ".$data['stats'][$row_date][$training_id]['attendees']
									."<br>Registrations: ".$data['stats'][$row_date][$training_id]['registrations']
									."<br>Upsells: ".$data['stats'][$row_date][$training_id]['upsells']."'>"
									.$data['stats'][$row_date][$training_id]['attendees'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
								$total_upsells = $total_upsells + $data['stats'][$row_date][$training_id]['upsells'];
								$total_attendees = $total_attendees + $data['stats'][$row_date][$training_id]['attendees'];
							}

							if( isset( $data['stats'][$row_date][$training_id]['amount'] ) ){
								echo '<br>&pound;'.$data['stats'][$row_date][$training_id]['amount'];
								$total_amount = $total_amount + $data['stats'][$row_date][$training_id]['amount'];
							}

							if( isset( $data['stats'][$row_date][$training_id]['promoted'] ) && $data['stats'][$row_date][$training_id]['promoted'] > 0 ){
								echo '<br><a href="/wp-admin/post.php?post='.$data['stats'][$row_date][$training_id]['promoted'].'&action=edit" target="_blank">email</a>';
							}

							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<br><a href="javascript:void(0);" class="ss-cat" data-wk="'.$row_date.'" data-t="'.$training_id.'"><span class="dashicons dashicons-tag"></span></a>';
								echo ' <a href="javascript:void(0);" class="ss-notes" data-wk="'.$row_date.'" data-t="'.$training_id.'"><span class="dashicons dashicons-admin-comments"></span>';
								if( isset( $data['stats'][$row_date][$training_id]['notes'] ) && $data['stats'][$row_date][$training_id]['notes'] <> '' ){
									echo ' <span title="'.$data['stats'][$row_date][$training_id]['notes'].'">'.substr($data['stats'][$row_date][$training_id]['notes'], 0, 10).'</span>';
								}
								echo '</a>';
							}

							echo '</td>';

						} ?>
						<td valign="top" class="text-center">
							<?php echo "<span title='Attendees: ".$total_attendees."<br>Registrations: ".$total_regs."<br>Upsells: ".$total_upsells."'>".$total_attendees.'</span><br>&pound;'.$total_amount; ?>
						</td>
					</tr>
					<?php
					$datetime->modify('-7 days');
					$row_date = $datetime->format('Y-m-d');
				} ?>
			</tbody>
		</table>
	</div>

	<?php // ccpa_write_log('assembling recordings table'); ?>

	<h2>On-demand</h2>
	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th>Week Comm.</th>
					<?php foreach ($data['recordings'] as $training_id) { ?>
						<th valign="top"><?php
							if( get_post_type($training_id) == 'course' && get_post_meta( $training_id, '_course_type', true ) == 'on-demand' ){
								$title = get_the_title($training_id);
							}else{
								$title = 'Unknown';
							}
							echo '<span title="'.$title.'">'.$training_id.':<br>'.substr($title, 0, 25).'</span>';
						?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($data['recordings'] as $training_id) { ?>
						<th><?php
							$totals = cc_stats_cumulative($training_id);
							// echo (int) get_post_meta($training_id, 'cumulative_registrations', true);
							// echo $totals['registrations'];
							echo '<span title="Attendees: '.$totals['attendees'].'<br>Registrations: '.$totals['registrations'].'<br>Upsells: '.$totals['upsells'].'">'.$totals['attendees'].'</span>';
							echo '<br>&pound;';
							// echo number_format( (float) get_post_meta($training_id, 'cumulative_reg_amount', true), 2);
							echo number_format( $totals['amount'], 2 );
						?></th>
					<?php } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// set up the first Monday to report from
				$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $end_date.' 00:00:00', new DateTimeZone('UTC'));
				if($datetime->format('D') <> 'Mon'){
					$datetime->modify('last monday');
				}

				$row_date = $datetime->format('Y-m-d');
				while ($row_date >= $start_date) {
					?>
					<tr>
						<td valign="top"><?php echo $datetime->format('d/m/Y'); ?></td>
						<?php
						$total_regs = $total_upsells = $total_attendees = $total_amount = 0;
						foreach ($data['recordings'] as $training_id) {
							if( isset( $data['stats'][$row_date][$training_id]['promoted'] ) && $data['stats'][$row_date][$training_id]['promoted'] > 0 ){
								echo '<td class="promoted text-center" valign="top">';
							}elseif( isset( $data['stats'][$row_date][$training_id]['category'] ) && $data['stats'][$row_date][$training_id]['category'] <> '' ){
								$cat_key = (int) $data['stats'][$row_date][$training_id]['category'];
								echo '<td class="text-center" valign="top" style="background:'.$sales_stats_cats[$cat_key]['colour'].';">';
							}else{
								echo '<td class="text-center" valign="top">';
							}
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&wk='.$row_date.'&t='.$training_id.'" target="_blank"'
									." title='Attendees: ".$data['stats'][$row_date][$training_id]['attendees']
									."<br>Registrations: ".$data['stats'][$row_date][$training_id]['registrations']
									."<br>Upsells: ".$data['stats'][$row_date][$training_id]['upsells']."'>"
									.$data['stats'][$row_date][$training_id]['attendees'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
								$total_upsells = $total_upsells + $data['stats'][$row_date][$training_id]['upsells'];
								$total_attendees = $total_attendees + $data['stats'][$row_date][$training_id]['attendees'];
							}
							/*
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&wk='.$row_date.'&t='.$training_id.'" target="_blank">'.$data['stats'][$row_date][$training_id]['registrations'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
							}
							*/
							if( isset( $data['stats'][$row_date][$training_id]['amount'] ) ){
								echo '<br>&pound;'.$data['stats'][$row_date][$training_id]['amount'];
								$total_amount = $total_amount + $data['stats'][$row_date][$training_id]['amount'];
							}
							if( isset( $data['stats'][$row_date][$training_id]['promoted'] ) && $data['stats'][$row_date][$training_id]['promoted'] > 0 ){
								echo '<br><a href="/wp-admin/post.php?post='.$data['stats'][$row_date][$training_id]['promoted'].'&action=edit" target="_blank">email</a>';
							}
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<br><a href="javascript:void(0);" class="ss-cat" data-wk="'.$row_date.'" data-t="'.$training_id.'"><span class="dashicons dashicons-tag"></span></a>';
								echo ' <a href="javascript:void(0)" class="ss-notes" data-wk="'.$row_date.'" data-t="'.$training_id.'"><span class="dashicons dashicons-admin-comments"></span>';
								if( isset( $data['stats'][$row_date][$training_id]['notes'] ) && $data['stats'][$row_date][$training_id]['notes'] <> '' ){
									echo ' <span title="'.$data['stats'][$row_date][$training_id]['notes'].'">'.substr($data['stats'][$row_date][$training_id]['notes'], 0, 10).'</span>';
								}
								echo '</a>';
							}
							echo '</td>';
						}
						?>
						<td valign="top" class="text-center">
							<?php echo "<span title='Attendees: ".$total_attendees."<br>Registrations: ".$total_regs."<br>Upsells: ".$total_upsells."'>".$total_attendees.'</span><br>&pound;'.$total_amount; ?>
							<?php // echo $total_regs.'<br>&pound;'.$total_amount; ?>
						</td>
					</tr>
					<?php
					$datetime->modify('-7 days');
					$row_date = $datetime->format('Y-m-d');
				} ?>
			</tbody>
		</table>
	</div>

	<?php // ccpa_write_log('assembling series table'); ?>

	<h2>Training series</h2>
	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th>Week Comm.</th>
					<?php foreach ($data['series'] as $training_id) { ?>
						<th valign="top"><?php
							$title = get_the_title($training_id);
							echo '<span title="'.$title.'">'.$training_id.':<br>'.substr($title, 0, 25).'</span>';
						?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($data['series'] as $training_id) { ?>
						<th><?php
							$totals = cc_stats_cumulative($training_id);
							// echo (int) get_post_meta($training_id, 'cumulative_registrations', true);
							// echo $totals['registrations'];
							echo "<span title='Attendees: ".$totals['attendees']."<br>Registrations: ".$totals['registrations']."<br>Upsells: ".$totals['upsells']."'>".$totals['attendees']."</span>";
							echo '<br>&pound;';
							// echo number_format( (float) get_post_meta($training_id, 'cumulative_reg_amount', true), 2);
							echo number_format( $totals['amount'], 2 );
						?></th>
					<?php } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// set up the first (last) Monday to report from
				$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $end_date.' 00:00:00', new DateTimeZone('UTC'));
				if($datetime->format('D') <> 'Mon'){
					$datetime->modify('last monday');
				}

				$row_date = $datetime->format('Y-m-d');
				while ($row_date >= $start_date) {
					?>
					<tr>
						<td valign="top"><?php echo $datetime->format('d/m/Y'); ?></td>
						<?php
						$total_regs = $total_upsells = $total_attendees = $total_amount = 0;
						foreach ($data['series'] as $training_id) {

							if( isset( $data['stats'][$row_date][$training_id]['promoted'] ) && $data['stats'][$row_date][$training_id]['promoted'] > 0 ){
								echo '<td class="promoted text-center" valign="top">';
							}elseif( isset( $data['stats'][$row_date][$training_id]['category'] ) && $data['stats'][$row_date][$training_id]['category'] <> '' ){
								$cat_key = (int) $data['stats'][$row_date][$training_id]['category'];
								echo '<td class="text-center" valign="top" style="background:'.$sales_stats_cats[$cat_key]['colour'].';">';
							}else{
								echo '<td class="text-center" valign="top">';
							}

							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&wk='.$row_date.'&t='.$training_id.'" target="_blank"'
									." title='Attendees: ".$data['stats'][$row_date][$training_id]['attendees']
									."<br>Registrations: ".$data['stats'][$row_date][$training_id]['registrations']
									."<br>Upsells: ".$data['stats'][$row_date][$training_id]['upsells']."'>"
									.$data['stats'][$row_date][$training_id]['attendees'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
								$total_upsells = $total_upsells + $data['stats'][$row_date][$training_id]['upsells'];
								$total_attendees = $total_attendees + $data['stats'][$row_date][$training_id]['attendees'];
							}

							if( isset( $data['stats'][$row_date][$training_id]['amount'] ) ){
								echo '<br>&pound;'.$data['stats'][$row_date][$training_id]['amount'];
								$total_amount = $total_amount + $data['stats'][$row_date][$training_id]['amount'];
							}

							if( isset( $data['stats'][$row_date][$training_id]['promoted'] ) && $data['stats'][$row_date][$training_id]['promoted'] > 0 ){
								echo '<br><a href="/wp-admin/post.php?post='.$data['stats'][$row_date][$training_id]['promoted'].'&action=edit" target="_blank">email</a>';
							}

							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<br><a href="javascript:void(0);" class="ss-cat" data-wk="'.$row_date.'" data-t="'.$training_id.'"><span class="dashicons dashicons-tag"></span></a>';
								echo ' <a href="javascript:void(0);" class="ss-notes" data-wk="'.$row_date.'" data-t="'.$training_id.'"><span class="dashicons dashicons-admin-comments"></span>';
								if( isset( $data['stats'][$row_date][$training_id]['notes'] ) && $data['stats'][$row_date][$training_id]['notes'] <> '' ){
									echo ' <span title="'.$data['stats'][$row_date][$training_id]['notes'].'">'.substr($data['stats'][$row_date][$training_id]['notes'], 0, 10).'</span>';
								}
								echo '</a>';
							}

							echo '</td>';

						} ?>
						<td valign="top" class="text-center">
							<?php echo "<span title='Attendees: ".$total_attendees."<br>Registrations: ".$total_regs."<br>Upsells: ".$total_upsells."'>".$total_attendees.'</span><br>&pound;'.$total_amount; ?>
						</td>
					</tr>
					<?php
					$datetime->modify('-7 days');
					$row_date = $datetime->format('Y-m-d');
				} ?>
			</tbody>
		</table>
	</div>

	<?php
	// ccpa_write_log('cc_sales_stats_weekly done');
}


function cc_sales_stats_daily(){
	?>
	<h1>Sales Stats (Daily)</h1>

	<?php
	$start_ddmmyyyy = date('d/m/Y', strtotime('-7 days'));
	$start_date = date('Y-m-d', strtotime('-7 days'));
	$end_ddmmyyyy = date('d/m/Y');
	$end_date = date('Y-m-d');

	if(isset($_GET['sd']) || isset($_GET['ed'])){
		if(isset($_GET['sd']) && $_GET['sd'] <> ''){
			$datetime = DateTime::createFromFormat("d/m/Y", $_GET['sd']);
			if($datetime){
				$start_ddmmyyyy = $datetime->format('d/m/Y');
				$start_date = $datetime->format('Y-m-d');
			}
		}
		if(isset($_GET['ed']) && $_GET['ed'] <> ''){
			$datetime = DateTime::createFromFormat("d/m/Y", $_GET['ed']);
			if($datetime){
				$end_ddmmyyyy = $datetime->format('d/m/Y');
				$end_date = $datetime->format('Y-m-d');
			}
		}
	}
	?>

	<div class="cc-sales-stats-search-wrap">
		<form action="<?php echo admin_url('admin.php'); ?>" method="get">
			<input type="hidden" name="page" value="cc_sales_stats_daily">
			<table>
				<tr>
					<td><strong><label for="start-date">Start date (dd/mm/yyyy) inclusive:</label></strong></td>
					<td width="250px"><input type="text" id="start-date" name="sd" class="widefat" value="<?php echo $start_ddmmyyyy; ?>"></td>
					<td><strong><label for="end-date">End date (dd/mm/yyyy) inclusive:</label></strong></td>
					<td width="250px"><input type="text" id="end-date" name="ed" class="widefat" value="<?php echo $end_ddmmyyyy; ?>"></td>
					<td><input type="submit" id="cc-sales-stats-search-submit" name="" class="button button-primary" value="Go"></td>
				</tr>
			</table>
		</form>
	</div>

	<?php $data = cc_stats_get_daily_results($start_date, $end_date); ?>

	<h2>Live training</h2>
	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th>Date</th>
					<?php foreach ($data['workshops'] as $training_id) { ?>
						<th valign="top"><?php
							$title = get_the_title($training_id);
							echo '<span title="'.$title.'">'.$training_id.':<br>'.substr($title, 0, 25).'</span>';
						?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($data['workshops'] as $training_id) { ?>
						<th><?php
							$totals = cc_stats_cumulative($training_id);
							// echo $totals['registrations'];
							echo '<span title="Attendees: '.$totals['attendees'].'<br>Registrations: '.$totals['registrations'].'<br>Upsells: '.$totals['upsells'].'">'.$totals['attendees'].'</span>';
							echo '<br>&pound;';
							echo number_format( $totals['amount'], 2 );
						?></th>
					<?php } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// set up the first day to report from
				$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $end_date.' 00:00:00', new DateTimeZone('UTC'));

				$row_date = $datetime->format('Y-m-d');
				while ($row_date >= $start_date) {
					?>
					<tr>
						<td valign="top"><?php echo $datetime->format('D d/m/Y'); ?></td>
						<?php
						$total_regs = $total_upsells = $total_attendees = $total_amount = 0;
						foreach ($data['workshops'] as $training_id) {
							echo '<td class="text-center" valign="top">';
							/*
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&day='.$row_date.'&t='.$training_id.'" target="_blank">'.$data['stats'][$row_date][$training_id]['registrations'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
							}
							*/
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&day='.$row_date.'&t='.$training_id.'" target="_blank"'
									." title='Attendees: ".$data['stats'][$row_date][$training_id]['attendees']
									."<br>Registrations: ".$data['stats'][$row_date][$training_id]['registrations']
									."<br>Upsells: ".$data['stats'][$row_date][$training_id]['upsells']."'>"
									.$data['stats'][$row_date][$training_id]['attendees'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
								$total_upsells = $total_upsells + $data['stats'][$row_date][$training_id]['upsells'];
								$total_attendees = $total_attendees + $data['stats'][$row_date][$training_id]['attendees'];
							}
							if( isset( $data['stats'][$row_date][$training_id]['amount'] ) ){
								echo '<br>&pound;'.$data['stats'][$row_date][$training_id]['amount'];
								$total_amount = $total_amount + $data['stats'][$row_date][$training_id]['amount'];
							}
							echo '</td>';
						}
						?>
						<td valign="top" class="text-center">
							<?php // echo $total_regs.'<br>&pound;'.$total_amount; ?>
							<?php echo "<span title='Attendees: ".$total_attendees."<br>Registrations: ".$total_regs."<br>Upsells: ".$total_upsells."'>".$total_attendees.'</span><br>&pound;'.$total_amount; ?>
						</td>
					</tr>
					<?php
					$datetime->modify('-1 day');
					$row_date = $datetime->format('Y-m-d');
				} ?>
			</tbody>
		</table>
	</div>

	<h2>On-demand</h2>
	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th>Date</th>
					<?php foreach ($data['recordings'] as $training_id) { ?>
						<th valign="top"><?php
							if( get_post_type( $training_id ) == 'course' && get_post_meta( $training_id, '_course_type', true ) == 'on-demand' ){
								$title = get_the_title($training_id);
							}else{
								$title = 'Unknown';
							}
							echo '<span title="'.$title.'">'.$training_id.':<br>'.substr($title, 0, 25).'</span>';
						?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($data['recordings'] as $training_id) { ?>
						<th><?php
							$totals = cc_stats_cumulative($training_id);
							// echo $totals['registrations'];
							echo '<span title="Attendees: '.$totals['attendees'].'<br>Registrations: '.$totals['registrations'].'<br>Upsells: '.$totals['upsells'].'">'.$totals['attendees'].'</span>';
							echo '<br>&pound;';
							echo number_format( $totals['amount'], 2 );
						?></th>
					<?php } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// set up the first day to report from
				$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $end_date.' 00:00:00', new DateTimeZone('UTC'));

				$row_date = $datetime->format('Y-m-d');
				while ($row_date >= $start_date) {
					?>
					<tr>
						<td valign="top"><?php echo $datetime->format('D d/m/Y'); ?></td>
						<?php
						$total_regs = $total_upsells = $total_attendees = $total_amount = 0;
						foreach ($data['recordings'] as $training_id) {
							echo '<td class="text-center" valign="top">';
							/*
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&day='.$row_date.'&t='.$training_id.'" target="_blank">'.$data['stats'][$row_date][$training_id]['registrations'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
							}
							*/
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&day='.$row_date.'&t='.$training_id.'" target="_blank"'
									." title='Attendees: ".$data['stats'][$row_date][$training_id]['attendees']
									."<br>Registrations: ".$data['stats'][$row_date][$training_id]['registrations']
									."<br>Upsells: ".$data['stats'][$row_date][$training_id]['upsells']."'>"
									.$data['stats'][$row_date][$training_id]['attendees'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
								$total_upsells = $total_upsells + $data['stats'][$row_date][$training_id]['upsells'];
								$total_attendees = $total_attendees + $data['stats'][$row_date][$training_id]['attendees'];
							}
							if( isset( $data['stats'][$row_date][$training_id]['amount'] ) ){
								echo '<br>&pound;'.$data['stats'][$row_date][$training_id]['amount'];
								$total_amount = $total_amount + $data['stats'][$row_date][$training_id]['amount'];
							}
							echo '</td>';
						}
						?>
						<td valign="top" class="text-center">
							<?php // echo $total_regs.'<br>&pound;'.$total_amount; ?>
							<?php echo "<span title='Attendees: ".$total_attendees."<br>Registrations: ".$total_regs."<br>Upsells: ".$total_upsells."'>".$total_attendees.'</span><br>&pound;'.$total_amount; ?>
						</td>
					</tr>
					<?php
					$datetime->modify('-1 day');
					$row_date = $datetime->format('Y-m-d');
				} ?>
			</tbody>
		</table>
	</div>

	<h2>Series</h2>
	<div class="table-responsive">
		<table class="table table-condensed cc-stats-table striped">
			<thead>
				<tr>
					<th>Date</th>
					<?php foreach ($data['series'] as $training_id) { ?>
						<th valign="top"><?php
							$title = get_the_title($training_id);
							echo '<span title="'.$title.'">'.$training_id.':<br>'.substr($title, 0, 25).'</span>';
						?></th>
					<?php } ?>
					<th>&nbsp;</th>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<?php foreach ($data['series'] as $training_id) { ?>
						<th><?php
							$totals = cc_stats_cumulative($training_id);
							// echo $totals['registrations'];
							echo '<span title="Attendees: '.$totals['attendees'].'<br>Registrations: '.$totals['registrations'].'<br>Upsells: '.$totals['upsells'].'">'.$totals['attendees'].'</span>';
							echo '<br>&pound;';
							echo number_format( $totals['amount'], 2 );
						?></th>
					<?php } ?>
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// set up the first day to report from
				$datetime = DateTime::createFromFormat("Y-m-d H:i:s", $end_date.' 00:00:00', new DateTimeZone('UTC'));

				$row_date = $datetime->format('Y-m-d');
				while ($row_date >= $start_date) {
					?>
					<tr>
						<td valign="top"><?php echo $datetime->format('D d/m/Y'); ?></td>
						<?php
						$total_regs = $total_upsells = $total_attendees = $total_amount = 0;
						foreach ($data['series'] as $training_id) {
							echo '<td class="text-center" valign="top">';
							/*
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&day='.$row_date.'&t='.$training_id.'" target="_blank">'.$data['stats'][$row_date][$training_id]['registrations'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
							}
							*/
							if( isset( $data['stats'][$row_date][$training_id]['registrations'] ) ){
								echo '<a href="/wp-admin/admin.php?page=ccpa_payments&day='.$row_date.'&t='.$training_id.'" target="_blank"'
									." title='Attendees: ".$data['stats'][$row_date][$training_id]['attendees']
									."<br>Registrations: ".$data['stats'][$row_date][$training_id]['registrations']
									."<br>Upsells: ".$data['stats'][$row_date][$training_id]['upsells']."'>"
									.$data['stats'][$row_date][$training_id]['attendees'].'</a>';
								$total_regs = $total_regs + $data['stats'][$row_date][$training_id]['registrations'];
								$total_upsells = $total_upsells + $data['stats'][$row_date][$training_id]['upsells'];
								$total_attendees = $total_attendees + $data['stats'][$row_date][$training_id]['attendees'];
							}
							if( isset( $data['stats'][$row_date][$training_id]['amount'] ) ){
								echo '<br>&pound;'.$data['stats'][$row_date][$training_id]['amount'];
								$total_amount = $total_amount + $data['stats'][$row_date][$training_id]['amount'];
							}
							echo '</td>';
						}
						?>
						<td valign="top" class="text-center">
							<?php // echo $total_regs.'<br>&pound;'.$total_amount; ?>
							<?php echo "<span title='Attendees: ".$total_attendees."<br>Registrations: ".$total_regs."<br>Upsells: ".$total_upsells."'>".$total_attendees.'</span><br>&pound;'.$total_amount; ?>
						</td>
					</tr>
					<?php
					$datetime->modify('-1 day');
					$row_date = $datetime->format('Y-m-d');
				} ?>
			</tbody>
		</table>
	</div>


	<?php
}

// sales stats categories
// used to add a background colour to the weekly sales stats
function cc_sales_stats_cats(){
	?>
	<h1>Sales Stats Categories</h1>
	<?php

	// var_dump($_REQUEST);

	if(isset($_POST['cc-sales-stats-cats-submit'])){
		$sales_stats_cats = array();
		$high_key = 0;
		foreach ( $_POST['cat'] as $key => $value ) {
			if( isset($_POST['delete'][$key]) && $_POST['delete'][$key] == 'yes'){
				continue;
			}
			if( $value == '' && ( !isset( $_POST['colour'][$key] ) || $_POST['colour'][$key] == '' ) ) {
				continue;
			}
			$cat_name = trim( sanitize_text_field( $value ) );
			$colour = trim( sanitize_text_field( $_POST['colour'][$key] ) );
			// echo $key.'|'.$cat_name.'|'.$colour;
			if($key == 9999){
				$cat_key = $high_key + 1;
			}else{
				$cat_key = $key;
				if($key > $high_key){
					$high_key = $key;
				}
			}
			// echo $key.'|'.$cat_key.'|'.$high_key.'<br>';
			$sales_stats_cats[$cat_key] = array(
				'cat_name' => $cat_name,
				'colour' => $colour,
			);
		}

		// var_dump($sales_stats_cats);
		update_option('sales_stats_cats', $sales_stats_cats);
	}else{
		$sales_stats_cats = get_option('sales_stats_cats', array());
	}
	
	?>
	<form method="post">
		<table>
			<tr>
				<?php
				$cats_count = 0;
				foreach ($sales_stats_cats as $key => $cat) {
					?>
					<td id="cat-wrap-<?php echo $key; ?>" class="cat-wrap">
						<label>Category:</label><br>
						<input type="text" class="widefat" name="cat[<?php echo $key; ?>]" value="<?php echo $cat['cat_name']; ?>">
						<label>Colour:</label><br>
						<input type="text" class="colour-field" name="colour[<?php echo $key; ?>]" value="<?php echo $cat['colour']; ?>">
						<a href="javascript:void(0);" class="sales-cat-del" data-catid="<?php echo $key; ?>"><span class="dashicons dashicons-trash"></span></a>
						<input type="hidden" id="cat-del-<?php echo $key; ?>" name="delete[<?php echo $key; ?>]" value="no">
						<div class="cat-sample" style="background:<?php echo $cat['colour']; ?>;">Example text</div>
					</td>
					<?php
					if($cats_count > 3){
						?>
						</tr><tr>
						<?php
					}
					$cats_count ++;
				}
				?>
			</tr>
			<tr>
				<td>
					<h5>Add new Category</h5>
					<label>New category:</label><br>
					<input type="text" class="widefat" name="cat[9999]" value="">
					<label>Colour:</label><br>
					<input type="text" class="colour-field" name="colour[9999]" value="">
				</td>
			</tr>
			<tr>
				<td><input type="submit" id="cc-sales-stats-cats-submit" name="cc-sales-stats-cats-submit" class="button button-primary" value="Update"></td>
			</tr>
		</table>
	</form>
	<?php
}
