<?php
/**
 * Loop
 * Modifies the loop for the blog page and archive pages to display only selected posts/CPTs
 */

add_action('pre_get_posts', 'cc_loop_pre_get_posts');
function cc_loop_pre_get_posts($query){
	if( !is_admin() && $query->is_main_query() ){
		if( $query->is_home() ){
			// blog posts
			if( isset( $_POST['bsf-cats'] ) ){
				$wanted_cats = array();
				foreach ($_POST['bsf-cats'] as $key => $value) {
					$cat = (int) $value;
					if( $cat > 0){
						$wanted_cats[] = $cat;
					}
				}
				if( ! empty( $wanted_cats ) ){
					$query->set('category__in', $wanted_cats);
				}
			}
			if( isset( $_POST['bsf-search'] ) && $_POST['bsf-search'] <> '' ){
				$search = stripslashes( sanitize_text_field( $_POST['bsf-search'] ) );
				if($search <> ''){
					$query->set('s', $search);
				}
			}
    // }elseif( is_post_type_archive( 'resource_hub' ) || is_post_type_archive( 'knowledge_hub' ) ){
		}elseif( is_post_type_archive( 'resource_hub' ) ){
			// the Resource Hub archive page
      // 9 posts per page ... not whatever the blogs posts per page is set to
      $query->set('posts_per_page', 9);
			$tax_query = cc_topics_tax_query( cc_topics_sanitize_taxonomies(), 'OR' );
			if( $tax_query <> '' ){
				$query->set( 'tax_query', $tax_query );
			}
			if( isset( $_POST['bsf-search'] ) && $_POST['bsf-search'] <> '' ){
				$search = stripslashes( sanitize_text_field( $_POST['bsf-search'] ) );
				if($search <> ''){
					$query->set('s', $search);
				}
			}
		}elseif( $query->is_tax( array( 'tax_issues', 'tax_approaches', 'tax_rtypes', 'tax_others', 'tax_trainlevels' ) ) ){
      // blog post archive page for one of our topic taxonomies
      // we only want to show blog posts
      $query->set( 'post_type', 'post' );
    }
	}
}


/**
 * Typical query .........
 * 
object(WP_Query)#1589 (49) {
  ["query"]=>  array(1) {
    ["post_type"]=>    string(12) "resource_hub"
  }
  ["query_vars"]=>  array(52) {
    ["post_type"]=>    string(12) "resource_hub"
    ["error"]=>    string(0) ""
    ["m"]=>    string(0) ""
    ["p"]=>    int(0)
    ["post_parent"]=>    string(0) ""
    ["subpost"]=>    string(0) ""
    ["subpost_id"]=>    string(0) ""
    ["attachment"]=>    string(0) ""
    ["attachment_id"]=>    int(0)
    ["name"]=>    string(0) ""
    ["pagename"]=>    string(0) ""
    ["page_id"]=>    int(0)
    ["second"]=>    string(0) ""
    ["minute"]=>    string(0) ""
    ["hour"]=>    string(0) ""
    ["day"]=>    int(0)
    ["monthnum"]=>    int(0)
    ["year"]=>    int(0)
    ["w"]=>    int(0)
    ["category_name"]=>    string(0) ""
    ["tag"]=>    string(0) ""
    ["cat"]=>    string(0) ""
    ["tag_id"]=>    string(0) ""
    ["author"]=>    string(0) ""
    ["author_name"]=>    string(0) ""
    ["feed"]=>    string(0) ""
    ["tb"]=>    string(0) ""
    ["paged"]=>    int(0)
    ["meta_key"]=>    string(0) ""
    ["meta_value"]=>    string(0) ""
    ["preview"]=>    string(0) ""
    ["s"]=>    string(0) ""
    ["sentence"]=>    string(0) ""
    ["title"]=>    string(0) ""
    ["fields"]=>    string(0) ""
    ["menu_order"]=>    string(0) ""
    ["embed"]=>    string(0) ""
    ["category__in"]=>    array(0) {
    }
    ["category__not_in"]=>    array(0) {
    }
    ["category__and"]=>    array(0) {
    }
    ["post__in"]=>    array(0) {
    }
    ["post__not_in"]=>    array(0) {
    }
    ["post_name__in"]=>    array(0) {
    }
    ["tag__in"]=>    array(0) {
    }
    ["tag__not_in"]=>    array(0) {
    }
    ["tag__and"]=>    array(0) {
    }
    ["tag_slug__in"]=>    array(0) {
    }
    ["tag_slug__and"]=>    array(0) {
    }
    ["post_parent__in"]=>    array(0) {
    }
    ["post_parent__not_in"]=>    array(0) {
    }
    ["author__in"]=>    array(0) {
    }
    ["author__not_in"]=>    array(0) {
    }
  }
  ["tax_query"]=>  object(WP_Tax_Query)#6826 (6) {
    ["queries"]=>    array(0) {
    }
    ["relation"]=>    string(3) "AND"
    ["table_aliases":protected]=>    array(0) {
    }
    ["queried_terms"]=>    array(0) {
    }
    ["primary_table"]=>    NULL
    ["primary_id_column"]=>    NULL
  }
  ["meta_query"]=>  bool(false)
  ["date_query"]=>  bool(false)
  ["post_count"]=>  int(0)
  ["current_post"]=>  int(-1)
  ["in_the_loop"]=>  bool(false)
  ["comment_count"]=>  int(0)
  ["current_comment"]=>  int(-1)
  ["found_posts"]=>  int(0)
  ["max_num_pages"]=>  int(0)
  ["max_num_comment_pages"]=>  int(0)
  ["is_single"]=>  bool(false)
  ["is_preview"]=>  bool(false)
  ["is_page"]=>  bool(false)
  ["is_archive"]=>  bool(true)
  ["is_date"]=>  bool(false)
  ["is_year"]=>  bool(false)
  ["is_month"]=>  bool(false)
  ["is_day"]=>  bool(false)
  ["is_time"]=>  bool(false)
  ["is_author"]=>  bool(false)
  ["is_category"]=>  bool(false)
  ["is_tag"]=>  bool(false)
  ["is_tax"]=>  bool(false)
  ["is_search"]=>  bool(false)
  ["is_feed"]=>  bool(false)
  ["is_comment_feed"]=>  bool(false)
  ["is_trackback"]=>  bool(false)
  ["is_home"]=>  bool(false)
  ["is_privacy_policy"]=>  bool(false)
  ["is_404"]=>  bool(false)
  ["is_embed"]=>  bool(false)
  ["is_paged"]=>  bool(false)
  ["is_admin"]=>  bool(false)
  ["is_attachment"]=>  bool(false)
  ["is_singular"]=>  bool(false)
  ["is_robots"]=>  bool(false)
  ["is_favicon"]=>  bool(false)
  ["is_posts_page"]=>  bool(false)
  ["is_post_type_archive"]=>  bool(true)
  ["query_vars_hash":"WP_Query":private]=>  string(32) "d4be676b5a7f45d5323c797fd455dfdc"
  ["query_vars_changed":"WP_Query":private]=>  bool(false)
  ["thumbnails_cached"]=>  bool(false)
  ["allow_query_attachment_by_filename":protected]=>  bool(false)
  ["stopwords":"WP_Query":private]=>  NULL
  ["compat_fields":"WP_Query":private]=>  array(2) {
    [0]=>    string(15) "query_vars_hash"
    [1]=>    string(18) "query_vars_changed"
  }
  ["compat_methods":"WP_Query":private]=>  array(2) {
    [0]=>    string(16) "init_query_flags"
    [1]=>    string(15) "parse_tax_query"
  }
}
*/