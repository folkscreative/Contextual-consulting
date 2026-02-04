<?php
/**
 * The blog posts "archive" page
 * 
 * See also cc/loop.php for how selected posts are selected
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Contextual
 */

global $paged, $wp_query;

get_header();
$page_slider_html = wms_page_slider();
echo $page_slider_html;
$postid = get_option('page_for_posts');
$page_title_locn = get_post_meta( $postid, '_page_title_locn', true);
if($page_slider_html == '' || $page_title_locn == ''){
	if($page_slider_html == ''){
		$xclass = 'no-hero';
	}else{
		$xclass = '';
	} ?>
	<div class="wms-sect-page-head <?php echo $xclass; ?>">
		<div class="container">
			<div class="row">
				<div class="col-12 text-center">
					<header class="entry-header">
						<h1 class="entry-title mb-5"><?php echo get_the_title($postid); ?></h1>
					</header>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
<div class="wms-section-std sml-padd-top">
	<div class="container">
		<?php
		// show any content that is on the posts page ... but only on the first page
		if( ! is_paged() ){
			$content = get_the_content( null, false, $postid );
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
			echo $content;
		}

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
		$wanted_cats = array();
		$pn_form_fields = '';
		if( isset( $_POST['bsf-cats'] ) ){
			foreach ($_POST['bsf-cats'] as $key => $value) {
				$cat = (int) $value;
				if( $cat > 0){
					$wanted_cats[] = $cat;
					$panel_class = 'open';
					$pn_form_fields .= '<input type="hidden" name="bsf-cats[]" value="'.$cat.'">';
				}
			}
		}
		$search = '';
		if( isset( $_POST['bsf-search'] ) && $_POST['bsf-search'] <> '' ){
			$search = stripslashes( sanitize_text_field( $_POST['bsf-search'] ) );
			$pn_form_fields .= '<input type="hidden" name="bsf-search" value="'.$search.'">';
		}

		if( empty( $wanted_cats ) && $search == '' ){
			$panel_class = 'closed';
		}else{
			$panel_class = 'open';
		}
		if( empty( $wanted_cats ) ){
			$bsf_all_checked = 'checked';
		}else{
			$bsf_all_checked = '';
		}
		?>
		<div class="row <?php echo $panel_class; ?>" id="blog-sf-panel-row">
			<div id="blog-sf-panel" class="col col-md-8 offset-md-2 blog-sf-panel mt-3">
				<form action="<?php echo get_permalink( $postid ); ?>" method="post">
					<label class="form-label">Categories:</label>
					<div class="cat-wrap">
						<input type="checkbox" class="btn-check" id="bsf-all" name="bsf-all" value="all" <?php echo $bsf_all_checked; ?> autocomplete="off">
						<label class="btn-blog-sf" for="bsf-all">All</label>
						<?php
						$categories = get_categories();
						foreach ($categories as $category){
							if( in_array( $category->term_id, $wanted_cats ) ){
								$bsf_cat_checked = 'checked';
							}else{
								$bsf_cat_checked = '';
							}
							?>
							<input type="checkbox" class="btn-check btn-check-cat" id="bsf-<?php echo $category->term_id; ?>" name="bsf-cats[]" value="<?php echo $category->term_id; ?>" <?php echo $bsf_cat_checked; ?> autocomplete="off">
							<label class="btn-blog-sf" for="bsf-<?php echo $category->term_id; ?>"><?php echo $category->name; ?></label>
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

		<?php
		// the loop is modified by cc_loop_pre_get_posts

		if ( have_posts() ) :
			$post_count = 0;
			echo '<div class="row">';
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class('col-md-6 col-lg-4 mb-5'); ?>>
					<div class="card h-100">

						<?php
						// image
						if(isset($rpm_theme_options['blog-fallback-img']['url']) && $rpm_theme_options['blog-fallback-img']['url'] <> ''){
							$default_image = $rpm_theme_options['blog-fallback-img']['url'];
						}else{
							$default_image = false;
						}
						$args = array(
							'scan' => true,
							'size' => 'post-thumb',
							'image_class' => 'card-img-top',
							'default' => $default_image,
							'format' => 'array',
							'echo' => false,
						);
						echo '<a href="'.get_permalink().'" class="image-zoom"><div class="latest-post-img wms-bg-img"';
						if ( function_exists( 'get_the_image' ) ) {
							$image_arr = get_the_image($args);
							if(isset($image_arr['src']) && $image_arr['src'] <> '') {
								echo ' style="background-image:url('.$image_arr['src'].');"';
							}
						}
						echo '></div></a>';
						?>

						<div class="card-body d-flex flex-column">
							<?php the_title( '<h5 class="card-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h5>' ); ?>
							<div class="row card-cite mb-2">
								<div class="col-5 col-md-12 col-xxl-5">
									<?php contextual_posted_on(); ?>
								</div>
								<div class="col-7 col-md-12 col-xxl-7 text-end text-md-start text-xxl-end">
									<?php contextual_post_cats('cats'); ?>
								</div>
							</div>
							<?php the_excerpt(); ?>
							<p class="card-btn-wrap text-right mt-auto">
								<a href="<?php the_permalink(); ?>" class="btn btn-primary">Read more</a>
							</p>
						</div>

					</div>
				</article><!-- #post-<?php the_ID(); ?> -->

				<?php
				$post_count ++;

			endwhile;
			echo '</div>';

			// navigation
			if($pn_form_fields == ''){
				echo contextual_the_posts_navigation();
			}else{
				?>
				<nav class="navigation posts-navigation clearfix mb-5" aria-label="Posts">
					<h2 class="screen-reader-text">Posts navigation</h2>
					<div class="nav-links">
						<?php
						$max_page = $wp_query->max_num_pages;
						if ( ! $paged ) {
							$paged = 1;
						}
						$next_page = (int) $paged + 1;
						if ( $next_page <= $max_page ) { ?>
							<div class="nav-previous">
								<form action="<?php next_posts( $max_page ); ?>" method="post">
									<?php echo $pn_form_fields; ?>
									<button type="submit" class="btn btn-primary">Older posts</button>
								</form>
							</div>
						<?php }
						if( is_paged() ){ ?>
							<div class="nav-next">
								<form action="<?php previous_posts(); ?>" method="post">
									<?php echo $pn_form_fields; ?>
									<button type="submit" class="btn btn-primary">Newer posts</button>
								</form>
							</div>
						<?php } ?>
					</div>
				</nav>
				<?php
			}

		else :
			?>
			<div class="text-center mb-5">
				<h3>Nothing Found</h3>
				<p>Sorry, we can't find what you're looking for.</p>
			</div>
			<div class="mb-5">&nbsp;</div>
			<?php
		endif; ?>
	</div>
</div>
<?php get_footer();
