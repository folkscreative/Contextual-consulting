<?php
/**
 * My Account
 * - Main menu
 */

function cc_myacct_menu($my_acct_section){
	$my_acct_sections = cc_myacct_pages();
	$this_page = get_permalink();
	$html = '<div id="my-acct-menu" class="my-acct-menu-wrap mb-5">';
	foreach ($my_acct_sections as $url => $page_info) {
		if($my_acct_section == $url){
			$item_class = 'selected';
		}else{
			$item_class = '';
		}
		$html .= '<div class="my-acct-menu-item '.$item_class.'">';
		if($url == 'supervision'){
			$html .= '<a href="/supervision" title="'.$page_info['title'].'" class="my-acct-menu-link">';
		}elseif( strpos( $url, '_dash') == 4 ){
			$html .= '<a href="';
			$html .= add_query_arg( array(
			    'my' => 'dashboard',
			    'org' => $page_info['org'],
			), $this_page );
			$html .= '" title="'.$page_info['title'].'" class="my-acct-menu-link">';
		}else{
			$html .= '<a href="'.add_query_arg('my', $url, $this_page).'" title="'.$page_info['title'].'" class="my-acct-menu-link">';
		}			
		// $html .= '<span class="my-acct-menu-icon">'.$page_info['icon'].'</span>';
		$html .= '<span class="my-acct-menu-text">'.$page_info['title'].'</span>';
		$html .= '</a></div>';
	}
	$html .= '</div>';
	return $html;
}

// valid pages
// Sep 2025 - added membership
// Jan 2025 - icon and show no longer used
function cc_myacct_pages(){
	$myacct_pages = array(
		'workshops' => array(
			'title' => 'Live training',
			'icon' => '<i class="fa-solid fa-tv fa-fw"></i>',
			'show' => 'yes',
		),
		'recordings' => array(
			'title' => 'On-demand',
			'icon' => '<i class="fa-solid fa-circle-play fa-fw"></i>',
			'show' => 'yes',
		),
		/*
		'membership' => array(
			'title' => 'Membership',
			'icon' => '<i class="fa-solid fa-crown fa-fw"></i>',
			'show' => 'yes',
		),
		*/
		'orders' => array(
			'title' => 'Order history',
			'icon' => '<i class="fa-solid fa-receipt fa-fw"></i>',
			'show' => 'yes',
		),
	);
	
	// Only show refer a friend if applicable
	if( cc_friend_show_my_acct() ){
		$myacct_pages['friend'] = array(
			'title' => 'Refer a friend',
			'icon' => '<i class="fa-solid fa-user-group"></i>',
			'show' => 'yes',
		);
	}
	
	// Profile section (show=no means it's in menu but not prominently displayed)
	$myacct_pages['details'] = array(
		'title' => 'My profile',
		'icon' => '<i class="fa-solid fa-user fa-fw"></i>',
		'show' => 'no',
	);
	
	$user_id = get_current_user_id();
	$portal_user = get_user_meta( $user_id, 'portal_user', true );
	
	// Merge accounts option for non-portal users
	if( $portal_user == '' ){
		$myacct_pages['merge'] = array(
			'title' => 'Merge accounts',
			'icon' => '<i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i>',
			'show' => 'no',
		);
	}
	
	// Portal admin dashboards
	if( $portal_user == 'nlft' || $portal_user == 'cnwl' ){
		$portal_admin = get_user_meta( $user_id, 'portal_admin', true);
		if( $portal_admin == 'yes' ){
			$myacct_pages['dashboard'] = array(
				'title' => 'Dashboard',
				'icon' => '<i class="fa-solid fa-gauge fa-fw"></i>',
				'show' => 'no',
			);
		}
	}
	
	// Admin-only portal dashboards
	if( current_user_can( 'manage_options' ) ){
		$myacct_pages['nlft_dash'] = array(
			'title' => 'NLFT dashboard',
			'icon' => '<i class="fa-solid fa-gauge fa-fw"></i>',
			'show' => 'no',
			'org' => 'nlft',
		);
		$myacct_pages['cnwl_dash'] = array(
			'title' => 'CNWL dashboard',
			'icon' => '<i class="fa-solid fa-gauge fa-fw"></i>',
			'show' => 'no',
			'org' => 'cnwl',
		);
	}
	
	return $myacct_pages;
}


/* was ...
function cc_myacct_pages(){
	$myacct_pages = array(
		'workshops' => array(
			'title' => 'Live training',
			'icon' => '<i class="fa-solid fa-tv fa-fw"></i>',
			'show' => 'yes',
		),
		'recordings' => array(
			'title' => 'On-demand',
			'icon' => '<i class="fa-solid fa-circle-play fa-fw"></i>',
			'show' => 'yes',
		),
		'orders' => array(
			'title' => 'Order history',
			'icon' => '<i class="fa-solid fa-circle-play fa-fw"></i>',
			'show' => 'yes',
		),
	);
	if( cc_friend_show_my_acct() ){
		$myacct_pages['friend'] = array(
			'title' => 'Refer a friend',
			'icon' => '<i class="fa-solid fa-user-group"></i>',
			'show' => 'yes',
		);
	}
	$myacct_pages['details'] = array(
		'title' => 'My profile',
		'icon' => '<i class="fa-solid fa-info fa-fw"></i>',
		'show' => 'no',
	);
	$user_id = get_current_user_id();
	$portal_user = get_user_meta( $user_id, 'portal_user', true );
	if( $portal_user == '' ){
		$myacct_pages['merge'] = array(
			'title' => 'Merge accounts',
			'icon' => '<i class="fa-solid fa-arrow-right-arrow-left fa-fw"></i>',
			'show' => 'no',
		);
	}
	if( $portal_user == 'nlft' || $portal_user == 'cnwl' ){
		$portal_admin = get_user_meta( $user_id, 'portal_admin', true);
		if( $portal_admin == 'yes' ){
			$myacct_pages['dashboard'] = array(
				'title' => 'Org. dashboard',
				'icon' => '<i class="fa-solid fa-gauge fa-fw"></i>',
				'show' => 'no',
			);
		}
	}
		/*
		'past-workshops' => array(
			'title' => 'Past training',
			'icon' => '<i class="fa-solid fa-video fa-fw"></i>',
			'show' => 'yes',
		),
		'supervision' => array(
			'title' => 'Supervision',
			'icon' => '<i class="fa-solid fa-handshake-simple"></i>',
			'show' => 'yes',
		),
		'offers' => array(
			'title' => 'Offers',
			'icon' => '<i class="fa-solid fa-percent fa-fw"></i>',
			'show' => 'yes',
		),
		'resources' => array(
			'title' => 'Resources',
			'icon' => '<i class="fa-regular fa-folder-open fa-fw"></i>',
			'show' => 'yes',
		),
		*//*
	if( current_user_can( 'manage_options' ) ){
		$myacct_pages['nlft_dash'] = array(
			'title' => 'NLFT dashboard',
			'icon' => '<i class="fa-solid fa-gauge fa-fw"></i>',
			'show' => 'no',
			'org' => 'nlft',
		);
		$myacct_pages['cnwl_dash'] = array(
			'title' => 'CNWL dashboard',
			'icon' => '<i class="fa-solid fa-gauge fa-fw"></i>',
			'show' => 'no',
			'org' => 'cnwl',
		);
	}
	return $myacct_pages;
}
*/

