<?php
/**
 * My Account Refer a Friend panel
 */

function cc_myacct_friend(){
	$user_info = wp_get_current_user();
	$html = '<h3 class="d-md-none">Refer a friend</h3><div class="myacct-panel myacct-friend-panel dark-bg">';

	// hand out the code?
	$refer_friend_active = get_option('refer_friend_active', '');
	$raf_flag = get_user_meta( $user_info->ID, 'refer_a_friend', true );
	if( $refer_friend_active == 'active' && $raf_flag == 'yes' ){

		$refer_friend_msg = get_option('refer_friend_msg', '');
		if($refer_friend_msg <> ''){
			$html .= '<div class="refer-friend-msg">';
			$html .= wpautop( do_shortcode( $refer_friend_msg ) );
			$html .= '</div>';
		}

		$refer_friend_code = cc_friend_user_code( $user_info->ID );
		$html .= '<form><div class="row"><div class="col-md-6 offset-md-3 text-center">';
		$html .= '<input type="text" class="form-control form-control-lg text-center" value="'.$refer_friend_code.'">';
		$html .= '</div></div></form>';

	}

	// if the code has been used, show their balance
	if( cc_friend_been_used( $refer_friend_code ) ){

		if($refer_friend_active == 'active'){
			$html .= '<hr>';
		}

		$usage = cc_friend_get_usage( 'raf_code', $refer_friend_code );
		$html .= '<h6>Usage:</h6>';
		$html .= '<div class="row"><div class="col-8">Your credits:</div><div class="col-4 text-end">'.cc_money_format($usage['credited'], $usage['currency']).'</div></div>';
		$html .= '<div class="row"><div class="col-8">You\'ve used:</div><div class="col-4 text-end">'.cc_money_format($usage['redeemed'], $usage['currency']).'</div></div>';
		if($usage['expired'] > 0){
			$html .= '<div class="row"><div class="col-8">Expired:</div><div class="col-4 text-end">'.cc_money_format($usage['expired'], $usage['currency']).'</div></div>';
		}
		$html .= '<div class="row"><div class="col-8">Your balance:</div><div class="col-4 text-end">'.cc_money_format($usage['balance'], $usage['currency']).'</div></div>';

		if( $usage['balance'] > 0 ){
			$html .= '<p>You can use your balance towards your training by entering the code <strong>'.$refer_friend_code.'</strong> as a gift voucher code.</p>';
			$next_expiry = cc_friend_next_expiry( $user_info->ID );
			if( $next_expiry['amount'] > 0 ){
				$html .= '<p>Note that '.cc_money_format($next_expiry['amount'], $next_expiry['currency']).' of your credits will expire if not used by '.$next_expiry['expiry_date'].'.</p>';
			}
		}
	}

	$html .= '</div>';
	return $html;
}