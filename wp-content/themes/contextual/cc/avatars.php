<?php
/**
 * Avatars
 * based on initials
 * see https://tqdev.com/2022-generate-avatars-initials-php
 */

// get the initials
function cc_avatar( $size = '', $user_id = null ){
    $user_info = $user_id ? new WP_User( $user_id ) : wp_get_current_user();

    // let's see if there's a gravatar for them first
    $args = array(
        'default' => '404'
    );
    $gravatar = get_avatar_url( $user_info->user_email, $args );
    // ccpa_write_log('function cc_avatar');
    // ccpa_write_log($gravatar);
    if( $gravatar ){
        // we have a url but it might not be an image ... we have to test it out
        $headers = @get_headers( $gravatar );
        if( preg_match( "|200|", $headers[0] ) ){
            // yup, it's actually an image
            if( $size == '' ){
                $class = 'site-gravatar';
            }else{
                $class = $size.'-gravatar';
            }
            return '<img src="'.$gravatar.'" alt="user gravatar" class="'.$class.'">';
        }
    }

    // ok, construct something instead
	$first_name = $last_name = '';

    if ( $user_info->first_name ) {
        $first_name = $user_info->first_name;
        if ( $user_info->last_name ) {
            $last_name = $user_info->last_name;
        }
    }else{
    	$display_name = $user_info->display_name;
    	$words = preg_split('/[\s-]+/', $display_name);
    	$first_name = array_shift($words);
    	$last_name = array_pop($words);
    }

	$capitals = '';

	if ( ctype_digit( $first_name ) && strlen( $first_name ) == 1 ){
		// first_name is one numeric character
        $capitals .= $first_name;
	}else{
		// let's get the first grapheme (letter) ... not really sure why we are using a grapheme rather than a latter :-(
        $first = grapheme_substr( $first_name, 0, 1 );
        if( ! ctype_digit( $first ) ){
        	// it's not completely numeric
        	$capitals .= $first;
        }
	}

	if ( ctype_digit( $last_name ) && strlen( $last_name ) == 1 ){
        $capitals .= $last_name;
	}else{
        $first = grapheme_substr( $last_name, 0, 1 );
        if( ! ctype_digit( $first ) ){
        	$capitals .= $first;
        }
	}

    $capitals = strtoupper( $capitals );

	// choose a a random color that is always the same for the same name
	// level 600, see: materialuicolors.co
	/*
    $colors = [
        '#e53935', // red
        '#d81b60', // pink
        '#8e24aa', // purple
        '#5e35b1', // deep-purple
        '#3949ab', // indigo
        '#1e88e5', // blue
        '#039be5', // light-blue
        '#00acc1', // cyan
        '#00897b', // teal
        '#43a047', // green
        '#7cb342', // light-green
        '#c0ca33', // lime
        '#fdd835', // yellow
        '#ffb300', // amber
        '#fb8c00', // orange
        '#f4511e', // deep-orange
        '#6d4c41', // brown
        '#757575', // grey
        '#546e7a', // blue-grey
    ];
    */
    $colors = [
        'red',
        'pink',
        'purple',
        'deep-purple',
        'indigo',
        'blue',
        'light-blue',
        'cyan',
        'teal',
        'green',
        'light-green',
        'lime',
        'yellow',
        'amber',
        'orange',
        'deep-orange',
        'brown',
        'grey',
        'blue-grey',
    ];
    $unique = hexdec( substr( md5( $first_name.' '.$last_name ), -8 ) );
    $chosen_colour = $colors[$unique % count( $colors )];

    // and pull this all together into an avatar
    if( $size == '' ) $size = 'large';
    return '<div class="initials-avatar '.$size.' '.$chosen_colour.'">'.$capitals.'</div>';
}

