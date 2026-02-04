<?php
/**
 * The Contextual Header
 * Individual functions called from the site's header.php
 */

// the main function that pulls in everything and returns the header code
function contextual_header(){
	global $rpm_theme_options;
	$html = '<header id="masthead" class="site-header-wrap">';
	$html .= '<div class="site-header container-fluid gx-xl-4">';
	$html .= contextual_header_menubar();
	$html .= '</div><!-- .container-fluid -->';
	$html .= '</header><!-- #masthead -->';
	return $html;
}

// the menubar
// this can include primary contact details, logo and menu
// this remains on screen when scrolled but is then realigned/resized etc
function contextual_header_menubar(){
	global $rpm_theme_options;
	$html = '<div class="menubar">';
	if( cc_users_is_valid_user_logged_in() ){
		$xclass = 'logged-in';
	}else{
		$xclass = 'logged-out';
	}
	$html .= '<div class="row align-items-center '.$xclass.'">';
	$html .= contextual_header_logo();
	// $html .= contextual_header_menubar_contacts();
	$html .= contextual_header_menu_desktop();
	$html .= contextual_header_search_col();
	$html .= contextual_header_action_btn();
	$html .= contextual_header_menu();
	$html .= '</div><!-- .row -->';
	$html .= '</div><!-- .menubar -->';
	return $html;
}

// the site logo
function contextual_header_logo(){
	global $rpm_theme_options;
	$html = '<div class="site-logo-col">';
	$html .= '<a class="navbar-brand" href="'.esc_url( home_url( '/' ) ).'">';
	if(isset($rpm_theme_options['logo-img']['url']) && $rpm_theme_options['logo-img']['url'] <> ''){
		if(isset($rpm_theme_options['logo-img-alt']['url']) && $rpm_theme_options['logo-img-alt']['url'] <> ''){
			$html .= '<img class="logo-light" src="'.$rpm_theme_options['logo-img']['url'].'" alt="'.get_bloginfo( 'name' ).'">';
			$html .= '<img class="logo-dark" src="'.$rpm_theme_options['logo-img-alt']['url'].'" alt="'.get_bloginfo( 'name' ).'">';
		}else{
			$html .= '<img class="logo" src="'.$rpm_theme_options['logo-img']['url'].'" alt="'.get_bloginfo( 'name' ).'">';
		}
	}else{
		$html .= get_bloginfo( 'name' );
	}
	$html .= '</a>';
	$html .= '</div><!-- .site-logo-col -->';
	return $html;
}

// contacts shown on the scrolled md menubar
function contextual_header_menubar_contacts(){
	global $rpm_theme_options;
	$html = '<div class="menubar-contacts-col d-none d-lg-block col-lg-4 col-xl-6 text-center">';
	// $html .= wms_social_icons();
	$html .= '</div><!-- .menubar-contacts-col -->';
	return $html;
}

// the action button
function contextual_header_action_btn(){
	global $rpm_theme_options;
	$html = '<div id="menubar-action-col" class="menubar-action-col text-center">';
	if(cc_users_is_valid_user_logged_in()){
		$html .= contextual_header_action_btn_logged_in();
	}else{
		$html .= contextual_header_action_btn_logged_out();
	}
	$html .= '</div><!-- .menubar-action-col -->';
	return $html;
}

// the my acct btn for a logged in user
function contextual_header_action_btn_logged_in(){
	$current_user = wp_get_current_user();
	$firstname = $current_user->user_firstname;
	if($firstname == ''){
		$firstname = 'My account';
	}
	$html = '<div class="header-action-wrap d-md-none">';
	$html .= '<div class="dropdown">';
	$html .= '<a href="/my-account" title="My account" class="account" data-bs-toggle="dropdown">'.cc_avatar().'</a>';
	$html .= '<ul class="dropdown-menu">';
	$html .= '<li><a class="dropdown-item" href="/my-account">My account</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/my-account?my=details">View my profile</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/my-account?my=friend">Refer a Friend</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/frequently-asked-questions">FAQs</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/my-account?my=merge">Merge accounts</a></li>';
	$html .= '<li><a class="dropdown-item" href="'.wp_logout_url( home_url() ).'">Logout</a></li>';
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';
	$html .= '<div class="header-action-wrap header-action-wrap-btn d-none d-md-block">';
	$html .= '<div class="dropdown">';
	$html .= '<a href="/my-account" title="My account" class="account" data-bs-toggle="dropdown">'.cc_avatar().'</a>';
	$html .= '<ul class="dropdown-menu mt-3">';
	$html .= '<li><a class="dropdown-item" href="/my-account">My account</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/my-account?my=details">View my profile</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/my-account?my=friend">Refer a Friend</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/frequently-asked-questions">FAQs</a></li>';
	// $html .= '<li><a class="dropdown-item" href="/my-account?my=merge">Merge accounts</a></li>';
	$html .= '<li><a class="dropdown-item" href="'.wp_logout_url( home_url() ).'">Logout</a></li>';
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';
	return $html;
}

// the logged out version of the action button
function contextual_header_action_btn_logged_out(){
	$html = '<div class="header-action-wrap d-md-none">';
	$html .= '<a href="/member-login" title="Login" class="login-btn">Sign in</a>';
	$html .= '</div>';
	$html .= '<div class="header-action-wrap header-action-wrap-btn d-none d-md-block">';
	$html .= '<a href="/member-login" title="Login" class="login-btn">Sign in</a>';
	$html .= '</div>';
	return $html;
}

// the site menu as shown on larger screens
function contextual_header_menu_desktop(){
	global $rpm_theme_options;
	$html = '<div class="site-menu-col-large navbar-expand-xl">';
	$html .= '<div class="site-menu navbar navbar-expand-xl">';
	// $html .= '<div class="site-menu-closer-wrap">';
	// $html .= '<a id="site-menu-closer" class="navbar-closer" href="javascript:void(0);" title="Close menu"><i class="fa-solid fa-xmark"></i></a>';
	// $html .= '</div><!-- .site-menu-closer-wrap -->';
	$html .= wp_nav_menu(
		array(
	        'menu'              => 'primary',
	        'theme_location'    => 'primary',
	        'depth'             => 2,
	        'container'         => 'nav',
	        'container_class'   => 'collapse navbar-collapse',
	        'container_id'      => 'wms-primary-navbar-desktop',
	        'menu_class'        => 'navbar-nav justify-content-center',
	        'fallback_cb'       => 'wp_bootstrap_navwalker::fallback',
	        'walker'            => new wp_bootstrap_navwalker(),
	        'echo'				=> false,
	    )
    );
	$html .= '</div><!-- .site-menu -->';
	$html .= '</div><!-- .site-menu-col-large -->';
	// $html .= '<div class="site-trigger-col col-2 col-lg-1 text-end">';
	// $html .= '<a href="javascript:void(0);" id="site-menu-trigger" class="site-menu-trigger" Title="Open menu">';
	// $html .= '<i class="fa-solid fa-bars"></i>';
	// $html .= '</a>';
	// $html .= '</div><!-- .site-trigger-col -->';
	return $html;
}

// the site menu
// includes the burger as well as the menu itself
function contextual_header_menu(){
	global $rpm_theme_options;
	$html = '<div class="site-menu-col d-xl-none">';
	$html .= '<div class="site-menu">';
	$html .= '<div class="site-menu-closer-wrap">';
	$html .= '<a id="site-menu-closer" class="navbar-closer" href="javascript:void(0);" title="Close menu"><i class="fa-solid fa-xmark"></i></a>';
	$html .= '</div><!-- .site-menu-closer-wrap -->';
	$html .= wp_nav_menu(
		array(
	        'menu'              => 'primary',
	        'theme_location'    => 'primary',
	        'depth'             => 2,
	        'container'         => 'nav',
	        'container_class'   => 'navbar',
	        'container_id'      => 'wms-primary-navbar',
	        'menu_class'        => 'navbar-nav',
	        'fallback_cb'       => 'wp_bootstrap_navwalker::fallback',
	        'walker'            => new wp_bootstrap_navwalker(),
	        'echo'				=> false,
	    )
    );
	$html .= '</div><!-- .site-menu -->';
	$html .= '</div><!-- .site-menu-col -->';
	$html .= '<div class="site-trigger-col text-end">';
	$html .= '<a href="javascript:void(0);" id="site-menu-trigger" class="site-menu-trigger" Title="Open menu">';
	$html .= '<i class="fa-solid fa-bars"></i>';
	$html .= '</a>';
	$html .= '</div><!-- .site-trigger-col -->';
	return $html;
}

// the search icon column
function contextual_header_search_col(){
	return '<div class="site-search-col"><a href="#" id="site-search-icon" class="site-search-icon" data-bs-toggle="modal" data-bs-target="#sf-modal"><i class="fa-solid fa-magnifying-glass"></i></a></div>';
}



/**
* Add stuff to the end of a specific menu that uses the wp_nav_menu() function
*/
add_filter('wp_nav_menu_items', 'contextual_append_to_menu', 10, 2);
function contextual_append_to_menu($items, $args){
	global $rpm_theme_options;
    if( $args->theme_location == 'primary' && $args->container_id == 'wms-primary-navbar' ){
	    // if($rpm_theme_options['menu-social'] == '1'){
	    	$items .= '<li class="extra-menu-item social-wrap">';
	    	$items .= wms_social_icons();
	    	$items .= '</li>';
	    // }
	    /*
	    if($rpm_theme_options['menu-phone'] == '1'){
	    	$items .= '<li class="extra-menu-item phone-wrap">';
			$items .= '<span class="phone-icon">';
			$items .= '<a href="'.contextual_header_phone_link().'">';
			$items .= '<i class="fas fa-fw fa-phone"></i></a></span><!-- .phone-icon -->';
			$items .= '<span class="phone-number">';
			$items .= '<a href="'.contextual_header_phone_link().'"><strong>';
			$items .= $rpm_theme_options['phone-number'].'</strong></a></span><!-- .phone-number -->';
	    	$items .= '</li>';
	    }
	    */
    }
    return $items;
}

