<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Contextual
 */

global $rpm_theme_options;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="post-summary row">
		<div class="post-image col-12 col-sm-4 col-md-3">
			<?php
			if(isset($rpm_theme_options['blog-fallback-img']['url']) && $rpm_theme_options['blog-fallback-img']['url'] <> ''){
				$default_image = $rpm_theme_options['blog-fallback-img']['url'];
			}else{
				$default_image = false;
			}
			$args = array(
				'scan' => true,
				'size' => 'post-thumb',
				'image_class' => 'img-responsive',
				'default' => $default_image,
			);
			if ( function_exists( 'get_the_image' ) ) { ?>
				<div class="entry-image">
					<?php get_the_image($args); ?>
				</div>
			<?php } ?>
		</div>
		<div class="post-text col-12 col-sm-8 col-md-9">
			<header class="entry-header">
				<?php the_title( '<h3 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3>' ); ?>
			</header><!-- .entry-header -->
			<div class="entry-meta">
				<?php contextual_posted_on(); ?>
			</div><!-- .entry-meta -->
			<div class="entry-excerpt">
				<?php the_excerpt(); ?>
			</div>
			<footer class="entry-footer">
				<?php contextual_entry_footer(); ?>
			</footer>
		</div>
	</div>
</article><!-- #post-<?php the_ID(); ?> -->
