<?php
/**
 * Newsletter stuff
 * added for contextual consulting
 */

add_shortcode('newsletter_form', 'wms_newsletter_form');


// adds a newsletter form onto the page
// [newsletter_form xclass=""]
function wms_newsletter_form($atts){
	$a = shortcode_atts( array(
	    'xclass' => '',
        'btn_class' => 'white',
        'location' => '',
    ), $atts );
    $xclass = "{$a['xclass']}";
    $btn_class = 'btn-'."{$a['btn_class']}";
    if( $a['location'] == 'popup' ){
        $desktop_breakpoint = 'xxl';
    }else{
        $desktop_breakpoint = 'lg';
    }
    $html = '
    <form class="newsletter-form text-start mb-5 needs-validation '.$xclass.'" novalidate>
        <div class="row">
            <div class="col-12 col-md-6 col-'.$desktop_breakpoint.'-3">
                <label for="firstname" class="form-label">First name</label>
                <input type="text" name="firstname" class="form-control form-control-lg" required>
                <div class="invalid-feedback">Please enter your first name</div>
            </div><!-- .col-12 col-md-6 col-'.$desktop_breakpoint.'-3 -->
            <div class="col-12 col-md-6 col-'.$desktop_breakpoint.'-3">
                <label for="lastname" class="form-label">Last name</label>
                <input type="text" name="lastname" class="form-control form-control-lg" required>
                <div class="invalid-feedback">Please enter your last name</div>
            </div><!-- .col-12 col-md-6 col-'.$desktop_breakpoint.'-3 -->
            <div class="col-12 col-md-6 col-'.$desktop_breakpoint.'-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control form-control-lg" required>
                <div class="invalid-feedback">Please enter a valid email</div>
            </div><!-- .col-12 col-md-6 col-'.$desktop_breakpoint.'-4 -->
            <div class="col-12 col-md-6 col-'.$desktop_breakpoint.'-2">
                <label class="form-label w-100">&nbsp;</label>
                <button type="submit" class="btn '.$btn_class.' newsletter-form-submit">Submit</button>
            </div><!-- .col-12 col-md-6 col-'.$desktop_breakpoint.'-2 -->
        </div><!-- .row -->
        <p id="newsletter-form-msg" class="newsletter-form-msg mt-3 text-center"></p>
    </form><!-- .newsletter-form -->';
    return $html;
}

// process a submitted form
add_action('wp_ajax_wms_newsletter_submit', 'wms_newsletter_submit');
add_action('wp_ajax_nopriv_wms_newsletter_submit', 'wms_newsletter_submit');
function wms_newsletter_submit(){
    $response = array(
        'status' => 'error',
        'msg' => 'error',
        'msg_class' => 'error',
    );

    $userdata = array(
        'email' => '',
        'firstname' => '',
        'lastname' => '',
    );

    if( isset( $_POST['email'] ) && $_POST['email'] <> '' ){
        $userdata['email'] = sanitize_email( $_POST['email'] );
    }
    if( isset( $_POST['firstname'] ) && $_POST['firstname'] <> '' ){
        $userdata['firstname'] = sanitize_text_field( $_POST['firstname'] );
    }
    if( isset( $_POST['lastname'] ) && $_POST['lastname'] <> '' ){
        $userdata['lastname'] = sanitize_text_field( $_POST['lastname'] );
    }

    if( $userdata['email'] == '' || $userdata['firstname'] == '' || $userdata['lastname'] == '' ){
        $response['msg'] = '<i class="fa-solid fa-xmark"></i> Please complete your firstname, lastname and email.';
    }else{
        // is the person already a subscriber?
        $subscriber = mailster('subscribers')->get_by_mail($userdata['email']);
        if($subscriber){
            $subscriber_id = $subscriber->ID;
        }else{
            // add to subscribers
            $subscriber_id = mailster('subscribers')->add($userdata, true);
        }
        // add to the newsletter list
        mailster('subscribers')->assign_lists($subscriber_id, ccmac_news_list_id());
        $response['status'] = 'ok';
        $response['msg'] = '<i class="fa-solid fa-check"></i> You have been successfully subscribed to the newsletter.';
        $response['msg_class'] = 'success';
    }
    echo json_encode($response);
    die();
}
