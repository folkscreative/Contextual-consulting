<?php
/**
 * The template for the search results
 */

$search_term = '';
if( isset( $_GET['s'] ) && $_GET['s'] <> '' ){
	$search_term = stripslashes( sanitize_text_field( $_GET['s'] ) );
	cc_search_term_save( $search_term );
}

get_header();
while ( have_posts() ) : 
	the_post(); ?>
	<div class="wms-sect-page-head no-hero search-results">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<header class="entry-header">
						<h1 class="text-center">Search results</h1>
						<p class="text-center">You searched for:</p>
						<input type="hidden" id="search_term" value="<?php echo $search_term; ?>">
						<div class="row">
							<div class="col-md-8 offset-md-2">
								<form id="site-search-form" role="search" method="get" class="search-form row g-3" action="<?php echo home_url(); ?>">
									<div class="input-group mb-3">
										<label for="search-field" class="visually-hidden">Search</label>
										<input type="search" id="site-search-input" class="search-field form-control form-control-lg" placeholder="What are you looking for?" value="<?php echo $search_term; ?>" name="s" autocomplete="off">
										<button type="submit" class="btn btn-secondary search-submit"><i class="fa-solid fa-magnifying-glass"></i></button>
									</div>
								</form>
								<div id="site-search-suggestions" class="search-suggestions"></div> <!-- Suggestions will be displayed here -->
							</div>
						</div>
					</header>
				</div>
			</div>
		</div>
	</div>

	<article <?php post_class(); ?>>

		<div id="cc-sr-ult-wrap">
			<div class="sr-placeholder text-center m-5">
				<i class="fa-solid fa-spinner fa-spin-pulse"></i> Loading, please wait ...
			</div>
		</div>

		<div id="cc-sr-odt-wrap"></div>

		<div id="cc-sr-khub-wrap"></div>

		<div id="cc-sr-rhub-wrap"></div>

		<div id="cc-sr-blog-wrap"></div>

		<div id="cc-sr-trn-wrap"></div>

		<div id="cc-sr-pag-wrap"></div>

	</article><!-- #post-<?php the_ID(); ?> -->
<?php endwhile; // End of the loop.
get_footer();
