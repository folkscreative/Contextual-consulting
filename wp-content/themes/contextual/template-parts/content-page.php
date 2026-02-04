<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Contextual
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php if(has_shortcode($post->post_content, 'section')){
		the_content();
	}else{
		echo do_shortcode('[section]'.apply_filters('the_content',get_the_content()).'[/section]');
	}
	echo wms_get_the_signature(); ?>
</article><!-- #post-<?php the_ID(); ?> -->
