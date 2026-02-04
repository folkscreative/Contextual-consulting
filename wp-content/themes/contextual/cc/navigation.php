<?php
/**
 * Navigation Stuff
 */

// page numbers instead of older/newer posts for archive pages
// from https://www.wpbeginner.com/wp-themes/how-to-add-numeric-pagination-in-your-wordpress-theme/
// but then changed as we are no longer using wp_query!
function cc_navigation_posts($paged, $max, $post_type) {
    /** Stop execution if there's only 1 page */
    if( $max <= 1 )
        return;
  
    // $paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
    // $max   = intval( $wp_query->max_num_pages );

    $links = array();
  
    /** Add current page to the array */
    if ( $paged >= 1 )
        $links[] = $paged;
  
    /** Add the pages around the current page to the array */
    if ( $paged >= 3 ) {
        $links[] = $paged - 1;
        $links[] = $paged - 2;
    }
  
    if ( ( $paged + 2 ) <= $max ) {
        $links[] = $paged + 2;
        $links[] = $paged + 1;
    }
  
    $html = '<div class="cc-navigation"><ul>' . "\n";

    $base_archive_url = get_post_type_archive_link( $post_type );
  
    /** Previous Post Link */
    if($paged > 1){
        $url = add_query_arg('page', ($paged - 1), $base_archive_url );
        $label = __( '&laquo; Previous Page' );
        $html .= '<li><a href="' . esc_url( $url ) . '">' . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a></li>' . "\n";
    }

    /** Link to first page, plus ellipses if necessary */
    if ( ! in_array( 1, $links ) ) {
        $class = 1 == $paged ? ' class="active"' : '';
        $url = add_query_arg('page', 1, $base_archive_url );
        $html .= '<li' . $class . '><a href="' . esc_url( $url) . '">1</a></li>';
  
        if ( ! in_array( 2, $links ) )
            $html .= '<li>…</li>';
    }
  
    /** Link to current page, plus 2 pages in either direction if necessary */
    sort( $links );
    foreach ( (array) $links as $link ) {
        $class = $paged == $link ? ' class="active"' : '';
        $url = add_query_arg('page', $link, $base_archive_url );
        $html .= '<li' . $class . '><a href="' . esc_url( $url ) . '">' . $link . '</a></li>';
    }
  
    /** Link to last page, plus ellipses if necessary */
    if ( ! in_array( $max, $links ) ) {
        if ( ! in_array( $max - 1, $links ) )
            $html .= '<li>…</li>' . "\n";
  
        $class = $paged == $max ? ' class="active"' : '';
        $url = add_query_arg('page', $max, $base_archive_url );
        $html .= '<li' . $class . '><a href="' . esc_url( $url ) . '">' . $max . '</a></li>';
    }
  
    /** Next Post Link */
    if($paged < $max){
        $nextpage = (int) $paged + 1;
        $label = __( 'Next Page &raquo;' );
        $url = add_query_arg('page', ($paged + 1), $base_archive_url );
        $html .= '<li><a href="' . esc_url( $url ) . '">' . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a></li>' . "\n";
    }
  
    $html .= '</ul></div>' . "\n";
    return $html;
}

// navigation for the resource hub
// this needs to work similar to the above but needs to submit a form
// but, also the urls are a different format eg https://contextualconsulting.co.uk/resources/page/2
function cc_navigation_form( $curr_page, $max_pages, $post_type ){
    if( $max_pages <= 1 ) return '';

    if( (int)$curr_page < 1 ){
        $curr_page = 1;
    }

    $links = array();
    if ( $curr_page >= 1 ){
        $links[] = $curr_page;
    }
    if ( $curr_page >= 3 ) {
        $links[] = $curr_page - 1;
        $links[] = $curr_page - 2;
    }
    if ( ( $curr_page + 2 ) <= $max_pages ) {
        $links[] = $curr_page + 2;
        $links[] = $curr_page + 1;
    }

    $html = '<div class="cc-navigation"><ul>';

    $base_archive_url = get_post_type_archive_link( $post_type );

    // prev
    if($curr_page > 1){
        if( $curr_page == 2 ){
            $url = $base_archive_url;
        }else{
            $url = $base_archive_url.'/page/'.($curr_page - 1);
        }
        $label = __( '&laquo; Previous Page' );
        $html .= '<li><a href="javascript:void(0);" class="cc-nav-form-link" data-dest="'.esc_url( $url ).'">' . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a></li>';
    }

    // Link to first page, plus ellipses if necessary
    if ( ! in_array( 1, $links ) ) {
        $class = 1 == $curr_page ? ' class="active"' : '';
        $url = $base_archive_url;
        $html .= '<li' . $class . '><a href="javascript:void(0);" class="cc-nav-form-link" data-dest="' . esc_url( $url) . '">1</a></li>';
  
        if ( ! in_array( 2, $links ) )
            $html .= '<li>…</li>';
    }
  
    // Link to current page, plus 2 pages in either direction if necessary
    sort( $links );
    foreach ( (array) $links as $link ) {
        $class = $curr_page == $link ? ' class="active"' : '';
        if( $link == 1 ){
            $url = $base_archive_url;
        }else{
            $url = $base_archive_url.'/page/'.$link;
        }
        $html .= '<li' . $class . '><a href="javascript:void(0);" class="cc-nav-form-link" data-dest="' . esc_url( $url ) . '">' . $link . '</a></li>';
    }
  
    // Link to last page, plus ellipses if necessary
    if ( ! in_array( $max_pages, $links ) ) {
        if ( ! in_array( $max_pages - 1, $links ) )
            $html .= '<li>…</li>';
  
        $class = $curr_page == $max_pages ? ' class="active"' : '';
        $url = $base_archive_url.'/page/'.$max_pages;
        $html .= '<li' . $class . '><a href="javascript:void(0);" class="cc-nav-form-link" data-dest="' . esc_url( $url ) . '">' . $max_pages . '</a></li>';
    }
  
    // Next
    if($curr_page < $max_pages){
        $nextpage = (int) $curr_page + 1;
        $label = __( 'Next Page &raquo;' );
        $url = $base_archive_url.'/page/'.($curr_page + 1);
        $html .= '<li><a href="javascript:void(0);" class="cc-nav-form-link" data-dest="' . esc_url( $url ) . '">' . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a></li>';
    }
  
    $html .= '</ul></div>';
    return $html;
}

// similar again but for the training and topics search results page
// uses $_GET and get_permalink to assemble the URL
function cc_navigation_tsf( $curr_page, $max_pages, $params, $link_class ){
    if( $max_pages <= 1 ) return '';

    if( (int)$curr_page < 1 ){
        $curr_page = 1;
    }

    $links = array();
    if ( $curr_page >= 1 ){
        $links[] = $curr_page;
    }
    if ( $curr_page >= 3 ) {
        $links[] = $curr_page - 1;
        $links[] = $curr_page - 2;
    }
    if ( ( $curr_page + 2 ) <= $max_pages ) {
        $links[] = $curr_page + 2;
        $links[] = $curr_page + 1;
    }

    $html = '<div class="cc-navigation"><ul>';

    // prev
    if($curr_page > 1){
        $link_params = base64_encode( maybe_serialize( array_merge( $params, array( 'page' => $curr_page - 1 ) ) ) );
        $label = '&laquo; Previous Page';
        $html .= '<li><a href="javascript:void(0);" class="'.$link_class.'" data-params="'.$link_params.'">' . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a></li>';
    }

    // Link to first page, plus ellipses if necessary
    if ( ! in_array( 1, $links ) ) {
        $class = 1 == $curr_page ? ' class="active"' : '';
        $link_params = base64_encode( maybe_serialize( array_merge( $params, array( 'page' => 1 ) ) ) );
        $html .= '<li' . $class . '><a href="javascript:void(0);" class="'.$link_class.'" data-params="'.$link_params.'">1</a></li>';
        if ( ! in_array( 2, $links ) ){
            $html .= '<li>…</li>';
        }
    }
  
    // Link to current page, plus 2 pages in either direction if necessary
    sort( $links );
    foreach ( (array) $links as $link ) {
        $class = $curr_page == $link ? ' class="active"' : '';
        $link_params = base64_encode( maybe_serialize( array_merge( $params, array( 'page' => $link ) ) ) );
        $html .= '<li' . $class . '><a href="javascript:void(0);" class="'.$link_class.'" data-params="'.$link_params.'">' . $link . '</a></li>';
    }
  
    // Link to last page, plus ellipses if necessary
    if ( ! in_array( $max_pages, $links ) ) {
        if ( ! in_array( $max_pages - 1, $links ) ){
            $html .= '<li>…</li>';
        }
        $class = $curr_page == $max_pages ? ' class="active"' : '';
        $link_params = base64_encode( maybe_serialize( array_merge( $params, array( 'page' => $max_pages ) ) ) );
        $html .= '<li' . $class . '><a href="javascript:void(0);" class="'.$link_class.'" data-params="'.$link_params.'">' . $max_pages . '</a></li>';
    }
  
    // Next
    if($curr_page < $max_pages){
        $nextpage = (int) $curr_page + 1;
        $label = 'Next Page &raquo;';
        $link_params = base64_encode( maybe_serialize( array_merge( $params, array( 'page' => $curr_page + 1 ) ) ) );
        $html .= '<li><a href="javascript:void(0);" class="'.$link_class.'" data-params="'.$link_params.'">' . preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) . '</a></li>';
    }
  
    $html .= '</ul></div>';
    return $html;
}