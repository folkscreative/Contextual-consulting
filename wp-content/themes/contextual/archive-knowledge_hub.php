<?php
/**
 * The knowledge hub archive page
 */

get_header();

$category_id = 0;
if( isset( $_GET['cat'] ) ){
	$cat_slug = stripslashes( sanitize_text_field( $_GET['cat'] ) );
	$category = get_category_by_slug( $cat_slug );
	if( $category ){
		$category_id = $category->term_id;
	}
}

?>

<div class="wms-sect-page-head">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<header class="entry-header">
					<h1 class="entry-title">
						<?php
						if( $category_id == 0 ){
							echo 'Knowledge hub';
						}else{
							echo '<a href="/knowledge">Knowledge hub</a>: '.$category->name;
						}
						?>
					</h1>
				</header>
			</div>
		</div>

		<?php
		if( $category_id == 0 ){
			if($rpm_theme_options['knowledge_hub-intro'] <> ''){ ?>
				<div class="row mt-3">
					<div class="col-12 mb-5">
						<?php echo do_shortcode( wpautop( $rpm_theme_options['knowledge_hub-intro'] ) ); ?>
					</div>
				</div>
			<?php }
		}else{
			if( $category->description <> '' ){ ?>
				<div class="row mt-3">
					<div class="col-12">
						<p class="mb-5">
							<?php echo $category->description; ?>
						</p>
					</div>
				</div>
			<?php }
		}


		/*
		// search and filter
		?>
		<div id="blog-sf-trigger-row" class="row mt-5">
			<div class="col text-center">
				<a href="javascript:void(0);" id="blog-sf-trigger" class="blog-sf-trigger closed btn btn-xl btn-primary">
					<i class="fa-solid fa-filter"></i>
					<i class="fa-solid fa-magnifying-glass"></i>
					Search &amp; Filter
				</a>
			</div>
		</div>

		<?php
		// has anything been selected for the loop?
		$tax_alls = array(
			'iss' => '',
			'app' => '',
			'rtp' => '',
			'oth' => '',
		);
		$wanted_isss = array();
		$wanted_apps = array();
		$wanted_rtps = array();
		$wanted_oths = array();
		$panel_class = 'closed';
		$pn_form_fields = '';

		if( isset( $_POST['tax-iss'] ) ){
			foreach ($_POST['tax-iss'] as $key => $value) {
				$term = (int) $value;
				if( $term > 0){
					$wanted_isss[] = $term;
					$panel_class = 'open';
					$pn_form_fields .= '<input type="hidden" name="tax-iss[]" value="'.$term.'">';
				}
			}
		}
		if( isset( $_POST['tax-app'] ) ){
			foreach ($_POST['tax-app'] as $key => $value) {
				$term = (int) $value;
				if( $term > 0){
					$wanted_apps[] = $term;
					$panel_class = 'open';
					$pn_form_fields .= '<input type="hidden" name="tax-app[]" value="'.$term.'">';
				}
			}
		}
		if( isset( $_POST['tax-rtp'] ) ){
			foreach ($_POST['tax-rtp'] as $key => $value) {
				$term = (int) $value;
				if( $term > 0){
					$wanted_rtps[] = $term;
					$panel_class = 'open';
					$pn_form_fields .= '<input type="hidden" name="tax-rtp[]" value="'.$term.'">';
				}
			}
		}
		if( isset( $_POST['tax-oth'] ) ){
			foreach ($_POST['tax-oth'] as $key => $value) {
				$term = (int) $value;
				if( $term > 0){
					$wanted_oths[] = $term;
					$panel_class = 'open';
					$pn_form_fields .= '<input type="hidden" name="tax-oth[]" value="'.$term.'">';
				}
			}
		}

		$search = '';
		if( isset( $_POST['bsf-search'] ) && $_POST['bsf-search'] <> '' ){
			$search = stripslashes( sanitize_text_field( $_POST['bsf-search'] ) );
			$panel_class = 'open';
			$pn_form_fields .= '<input type="hidden" name="bsf-search" value="'.$search.'">';
		}

		if( isset( $_POST['tax-iss-all'] ) && $_POST['tax-iss-all'] == 'all' ){
			$tax_alls['iss'] = 'checked';
		}
		if( isset( $_POST['tax-app-all'] ) && $_POST['tax-app-all'] == 'all' ){
			$tax_alls['app'] = 'checked';
		}
		if( isset( $_POST['tax-rtp-all'] ) && $_POST['tax-rtp-all'] == 'all' ){
			$tax_alls['rtp'] = 'checked';
		}
		if( isset( $_POST['tax-oth-all'] ) && $_POST['tax-oth-all'] == 'all' ){
			$tax_alls['oth'] = 'checked';
		}
		?>

		<div class="row <?php echo $panel_class; ?>" id="blog-sf-panel-row">
			<div id="blog-sf-panel" class="col blog-sf-panel mt-3">
				<form action="<?php echo get_post_type_archive_link( 'knowledge_hub' ); ?>" method="post">
					<div class="row">

						<?php $args = array(
							'taxonomy' => 'tax_issues',
						);
						$terms = get_terms( $args );
						if( ! empty( $terms ) ){ ?>
							<div class="col">
								<p class="text-center">
									<input type="checkbox" class="btn-check" id="tax-iss-all" name="tax-iss-all" value="all" <?php echo $tax_alls['iss']; ?> autocomplete="off">
									<label for="tax-iss-all" id="tax-iss-all-label" class="btn-tax btn-tax btn-iss btn-tax-lg">Issues:</label>
								</p>
								<p class="btns-iss">
									<?php foreach ($terms as $term) {
										if( in_array( $term->term_id, $wanted_isss ) ){
											$tax_cat_checked = 'checked';
										}else{
											$tax_cat_checked = '';
										} ?>
										<input type="checkbox" class="btn-check" id="tax-<?php echo $term->term_id; ?>" name="tax-iss[]" value="<?php echo $term->term_id; ?>" <?php echo $tax_cat_checked; ?> autocomplete="off">
										<label class="btn-tax btn-tax btn-iss m-1" for="tax-<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
									<?php } ?>
								</p>
							</div>
						<?php }

						$args = array(
							'taxonomy' => 'tax_approaches',
						);
						$terms = get_terms( $args );
						if( ! empty( $terms ) ){ ?>
							<div class="col">
								<p class="text-center">
									<input type="checkbox" class="btn-check" id="tax-app-all" name="tax-app-all" value="all" <?php echo $tax_alls['app']; ?> autocomplete="off">
									<label for="tax-app-all" class="btn-tax btn-tax btn-app btn-tax-lg">Approaches:</label>
								</p>
								<p class="btns-app">
									<?php foreach ($terms as $term) {
										if( in_array( $term->term_id, $wanted_apps ) ){
											$tax_cat_checked = 'checked';
										}else{
											$tax_cat_checked = '';
										} ?>
										<input type="checkbox" class="btn-check" id="tax-<?php echo $term->term_id; ?>" name="tax-app[]" value="<?php echo $term->term_id; ?>" <?php echo $tax_cat_checked; ?> autocomplete="off">
										<label class="btn-tax btn-tax btn-app m-1" for="tax-<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
									<?php } ?>
								</p>
							</div>
						<?php }

						$args = array(
							'taxonomy' => 'tax_rtypes',
						);
						$terms = get_terms( $args );
						if( ! empty( $terms ) ){ ?>
							<div class="col">
								<p class="text-center">
									<input type="checkbox" class="btn-check" id="tax-rtp-all" name="tax-rtp-all" value="all" <?php echo $tax_alls['rtp']; ?> autocomplete="off">
									<label for="tax-rtp-all" class="btn-tax btn-tax btn-rtp btn-tax-lg">Resources:</label>
								</p>
								<p class="btns-rtp">
									<?php foreach ($terms as $term) {
										if( in_array( $term->term_id, $wanted_rtps ) ){
											$tax_cat_checked = 'checked';
										}else{
											$tax_cat_checked = '';
										} ?>
										<input type="checkbox" class="btn-check" id="tax-<?php echo $term->term_id; ?>" name="tax-rtp[]" value="<?php echo $term->term_id; ?>" <?php echo $tax_cat_checked; ?> autocomplete="off">
										<label class="btn-tax btn-tax btn-rtp m-1" for="tax-<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
									<?php } ?>
								</p>
							</div>
						<?php }

						$args = array(
							'taxonomy' => 'tax_others',
						);
						$terms = get_terms( $args );
						if( ! empty( $terms ) ){ ?>
							<div class="col">
								<p class="text-center">
									<input type="checkbox" class="btn-check" id="tax-oth-all" name="tax-oth-all" value="all" <?php echo $tax_alls['oth']; ?> autocomplete="off">
									<label for="tax-oth-all" class="btn-tax btn-tax btn-oth btn-tax-lg">Other:</label>
								</p>
								<p class="btns-oth">
									<?php foreach ($terms as $term) {
										if( in_array( $term->term_id, $wanted_oths ) ){
											$tax_cat_checked = 'checked';
										}else{
											$tax_cat_checked = '';
										} ?>
										<input type="checkbox" class="btn-check" id="tax-<?php echo $term->term_id; ?>" name="tax-oth[]" value="<?php echo $term->term_id; ?>" <?php echo $tax_cat_checked; ?> autocomplete="off">
										<label class="btn-tax btn-tax btn-oth m-1" for="tax-<?php echo $term->term_id; ?>"><?php echo $term->name; ?></label>
									<?php } ?>
								</p>
							</div>
						<?php } ?>

					</div>

					<div class="row">
						<div class="search-wrap col-12 col-md-9 col-lg-10">
							<label for="bsf-search" class="form-label">Search:</label>
							<input type="text" class="form-control" name="bsf-search" id="bsf-search" value="<?php echo $search; ?>">
						</div>
						<div class="btn-wrap col-12 col-md-3 col-lg-2 text-end">
							<label class="form-label">&nbsp;</label><br>
							<button type="submit" class="btn btn-primary btn-sm">Apply</button>
						</div>
					</div>

				</form>
			</div>
		</div>
		<div class="mb-5"></div>
		*/ ?>

	</div>
</div>

<?php
/*
// we've already done this in the loop but we need it again now ...
$taxonomies = cc_topics_sanitize_taxonomies();
$workshop_ids = workshops_next_in_taxonomies( $taxonomies );
if( count( $workshop_ids ) > 0 ){
	$show_workshops = true;
}else{
	$show_workshops = false;
}
*/


if( $category_id > 0 ){

	$args = array(
		'post_type' => 'knowledge_hub',
		'numberposts' => -1,
		'category' => $category_id,
		'orderby' => 'title',
		'order' => 'ASC',
	);
	$kh_posts = get_posts($args);
	?>

	<div class="wms-section-std sml-padd-top">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="row">

					<?php if( empty( $kh_posts )){ ?>

						<p>Nothing found for <?php echo $category->name; ?></p>

					<?php }else{

						foreach ($kh_posts as $kh_post) {
							echo cc_topics_cpt_card_col( $kh_post->ID, true );
						}

					} ?>

				</div>
			</div>
		</div>
	</div>

<?php }else{

	// what categories are wanted?
	$categories = get_categories();
	$wanted_cats = array();
	foreach ($categories as $category) {
		$args = array(
			'numberposts' => 1, // we just want to know whether there is at least one
			'cat' => $category->term_id,
			'post_type' => 'knowledge_hub',
		);
		$kh_posts = get_posts($args);
		if( count( $kh_posts ) > 0 ){
			$wanted_cats[] = $category->term_id;
		}
	}
	// $cats_still_to_show = count( $wanted_cats );
	$cat_count = 0;
	?>

	<div class="wms-section-std sml-padd-top">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="row align-items-stretch">
					
					<?php foreach ($wanted_cats as $category_id) { ?>

						<div class="col-12 col-md-6 col-lg-4">
							<?php
							echo cc_knowledge_hub_card( $category_id );
							$cat_count ++;
							?>
						</div>

					<?php } ?>

				</div>
			</div>
		</div>
	</div>

<?php }

get_footer();
