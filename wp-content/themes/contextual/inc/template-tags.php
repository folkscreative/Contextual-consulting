<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package Contextual
 */

if ( ! function_exists( 'contextual_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time.
	 */
	function contextual_posted_on( $post_id=0, $return=false ) {
		if( $post_id == 0 ){
			$post_id = get_the_id();
		}
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U', $post_id ) !== get_the_modified_time( 'U', $post_id ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf( $time_string,
			esc_attr( get_the_date( DATE_W3C, $post_id ) ),
			esc_html( get_the_date( '', $post_id ) ),
			esc_attr( get_the_modified_date( DATE_W3C, $post_id ) ),
			esc_html( get_the_modified_date( '', $post_id ) )
		);

		/*
		$posted_on = sprintf(
			/* translators: %s: post date. *//*
			esc_html_x( 'Posted on %s', 'post date', 'contextual' ),
			'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
		);
		*/
		$posted_on = '<i class="fa-solid fa-calendar-days"></i> '.$time_string;

		if( $return ){
			return '<p class="posted-on mb-1">' . $posted_on . '</p>';
		}

		echo '<p class="posted-on mb-1">' . $posted_on . '</p>'; // WPCS: XSS OK.

	}
endif;

if ( ! function_exists( 'contextual_posted_by' ) ) :
	/**
	 * Prints HTML with meta information for the current author.
	 */
	function contextual_posted_by() {
		$byline = sprintf(
			/* translators: %s: post author. */
			esc_html_x( 'by %s', 'post author', 'contextual' ),
			'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
		);

		echo '<span class="byline"> ' . $byline . '</span>'; // WPCS: XSS OK.

	}
endif;

if ( ! function_exists( 'contextual_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function contextual_entry_footer() {
		// Hide category and tag text for pages.
		if ( 'post' === get_post_type() ) {
			/* translators: used between list items, there is a space after the comma */
			$categories_list = get_the_category_list( esc_html__( ', ', 'contextual' ) );
			if ( $categories_list ) {
				/* translators: 1: list of categories. */
				printf( '<span class="cat-links">' . esc_html__( 'Posted in %1$s', 'contextual' ) . '</span>', $categories_list ); // WPCS: XSS OK.
			}

			/* translators: used between list items, there is a space after the comma */
			$tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'contextual' ) );
			if ( $tags_list ) {
				/* translators: 1: list of tags. */
				printf( '<span class="tags-links">' . esc_html__( 'Tagged %1$s', 'contextual' ) . '</span>', $tags_list ); // WPCS: XSS OK.
			}
		}

		if ( ! is_single() && ! post_password_required() && ( comments_open() || get_comments_number() ) ) {
			echo '<span class="comments-link">';
			comments_popup_link(
				sprintf(
					wp_kses(
						/* translators: %s: post title */
						__( 'Leave a Comment<span class="sr-only"> on %s</span>', 'contextual' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				)
			);
			echo '</span>';
		}

		edit_post_link(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Edit <span class="sr-only">%s</span>', 'contextual' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				get_the_title()
			),
			'<span class="edit-link">',
			'</span>'
		);
	}
endif;

// the categories and tags
function contextual_post_cats($show='both', $echo=true, $post_id=false){
	$html = '';
	if($show == 'both' || $show = 'cats'){
		$categories_list = get_the_category_list( esc_html__( ', ', 'contextual' ), '', $post_id );
		if ( $categories_list ) {
			/* translators: 1: list of categories. */
			$html .= sprintf( '<p class="cat-links mb-0"><i class="fa-regular fa-circle-right"></i>' . esc_html__( ' %1$s', 'contextual' ) . '</p>', $categories_list ); // WPCS: XSS OK.
		}
	}

	if($show == 'both' || $show == 'tags'){
		/* translators: used between list items, there is a space after the comma */
		$tags_list = get_the_tag_list( '', esc_html_x( ', ', 'list item separator', 'contextual' ), '', $post_id ); // should post_id default to 0???
		if ( $tags_list ) {
			/* translators: 1: list of tags. */
			$html .= sprintf( '<p class="tags-links small mb-0"><i class="fa-solid fa-tags"></i>' . esc_html__( ' %1$s', 'contextual' ) . '</p>', $tags_list ); // WPCS: XSS OK.
		}
	}
	if($echo){
		echo $html;
	}else{
		return $html;
	}
}


if ( ! function_exists( 'contextual_post_thumbnail' ) ) :
	/**
	 * Displays an optional post thumbnail.
	 *
	 * Wraps the post thumbnail in an anchor element on index views, or a div
	 * element when on single views.
	 */
	function contextual_post_thumbnail() {
		if ( post_password_required() || is_attachment() || ! has_post_thumbnail() ) {
			return;
		}

		if ( is_singular() ) :
			?>

			<div class="post-thumbnail">
				<?php the_post_thumbnail(); ?>
			</div><!-- .post-thumbnail -->

		<?php else : ?>

		<a class="post-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php
			the_post_thumbnail( 'post-thumbnail', array(
				'alt' => the_title_attribute( array(
					'echo' => false,
				) ),
			) );
			?>
		</a>

		<?php
		endif; // End is_singular().
	}
endif;
