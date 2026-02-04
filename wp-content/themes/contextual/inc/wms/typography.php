<?php
/**
 * WMS
 * Typography
 *
 * Includes:
 * - 
 */

/**
 * v5.0 5/10/18
 * - v5 rebuild
 */

if(!defined('ABSPATH')) exit;

add_shortcode('lead', 'wms_typo_lead');
add_shortcode('jumbo', 'wms_typo_jumbo');
add_shortcode('blockquote', 'wms_typo_blockquote');
add_shortcode('hr', 'wms_typo_hr');
add_shortcode('line', 'wms_typo_hr');
add_shortcode('small', 'wms_typo_small');
add_shortcode('blank', 'wms_blank');
add_shortcode('email', 'wms_email');
add_shortcode('anchor', 'wms_anchor');
// added for CC ....
add_shortcode('subhead', 'wms_subhead');
add_shortcode('show', 'wms_show');


// [lead]
function wms_typo_lead($atts, $content=NULL){
    $atts = shortcode_atts( array(
            "xclass" => false,
            "data"   => false
    ), $atts );
    $class  = 'lead';
    $class .= ( $atts['xclass'] )   ? ' ' . $atts['xclass'] : '';
    $data_props = wms_parse_data_attributes( $atts['data'] );
    return sprintf(
        // '<span class="%s"%s>%s</span>',
        '<p class="%s"%s>%s</p>',
        esc_attr( trim($class) ),
        ( $data_props ) ? ' ' . $data_props : '',
        do_shortcode( $content )
    );
}

// even buigger text than lead
function wms_typo_jumbo($atts, $content){
    return '<span class="jumbo">'.do_shortcode($content).'</span>';
}

// [blockquote footer="Fred Smith" xclass=""]...[/blockquote]
// xclass useful for text-end, text-center ...
function wms_typo_blockquote($atts, $content){
    $a = shortcode_atts( array(
        'footer' => '',
        'xclass' => '',
    ), $atts );
    $footer = "{$a['footer']}";
    $xclass = "{$a['xclass']}";
    $html = '<blockquote class="blockquote '.$xclass.'">';
    $html .= '<div class="mb-0">'.do_shortcode($content).'</div>';
    if($footer <> ''){
    	$html .= '<footer class="blockquote-footer">'.$footer.'</footer>';
    }
    $html .= '</blockquote>';
    return $html;
}

// shortcode for a horozontal rule
function wms_typo_hr($atts){
    $a = shortcode_atts( array(
        'xclass' => ''
    ), $atts );
    $xclass = "{$a['xclass']}";
    if($xclass <> ''){
        return '<hr class="'.$xclass.'">';
    }
    return '<hr>';
}

// small and/or muted text
// [small muted="yes"]...[/small]
function wms_typo_small($atts, $content){
    $a = shortcode_atts( array(
        'xclass' => '',
        'muted' => '',
    ), $atts );
    $xclass = "{$a['xclass']}";
    $muted = "{$a['muted']}";
    $classes = $xclass;
    if($muted == 'yes'){
        $classes .= ' text-muted';
    }
    $html = '<small';
    if($classes <> ''){
        $html .= ' class="'.$classes.'"';
    }
    $html .= '>';
    $html .= do_shortcode($content);
    $html .= '</small>';
    return $html;
}

// adds nothing ....
// usage [blank size="10px" xclass=""]
// size is optional and can be a number (gives number of blank <p>s) or number of pixels (gives a fixed pixel space)
function wms_blank($atts, $content){
    $a = shortcode_atts( array(
        'size' => '',
        'xclass' => ''
    ), $atts );
    $size = "{$a['size']}";
    $xclass = "{$a['xclass']}";
    $HTML = '';
    if($size == ''){
        $HTML .= '<p class="'.$xclass.'">&nbsp;</p>';
    }else{
        if(substr($size, -2) == 'px'){
            $HTML .= '<div class="'.$xclass.'" style="display:block;height:'.$size.';"></div>';
        }else{
            if(is_numeric($size)){
                $HTML .= '<div class="'.$xclass.'">';
                for ($i=0; $i < $size; $i++) { 
                    $HTML .= '<p>&nbsp;</p>';
                }
                $HTML .= '</div>';
            }
        }
    }
    return $HTML;
}

// returns the theme email address or a different one .... as an obfuscated link
// [email address="joe@blow.co"]
// address is optional. if omitted, it will use the theme options business email address
function wms_email($atts){
    $a = shortcode_atts( array(
        'address' => ''
    ), $atts );
    $address = "{$a['address']}";
    global $rpm_theme_options;
    if($address == ''){
        $address = $rpm_theme_options['business-email'];
    }
    if($address <> ''){
        $HTML = '<a href="mailto:'.antispambot($address).'" class="email-link">'.antispambot($address).'</a>';
        return $HTML;
    }
}

// extracts the font size and increases it and then returns the new font size
// for the large fonts in the wide section
// expects $font_text to be something like 16px
function wms_bigger_font($font_text = ''){
    $large_font = 1.5;
    if($font_text == '') return '';
    if(substr($font_text, -2) == 'px'){
        $font_size = substr($font_text, 0, strlen($font_text) - 2);
        if(!is_numeric($font_size)) return '';
        $font_size = $font_size * $large_font;
        return $font_size.'px';
    }else{
        return '24px';
    }
}

// anchor shortcode
// [anchor name="your-anchor"]
function wms_anchor($atts){
    $a = shortcode_atts(array(
        'name' => 'unknown'
    ), $atts);
    $anchor_name = "{$a['name']}";
    return '<a id="'.$anchor_name.'"></a>';
}

// added for CC
// subhead - used in sections
// [subhead xclass=""]...[/subhead]
function wms_subhead($atts, $content){
    $a = shortcode_atts(array(
        'xclass' => ''
    ), $atts);
    $xclass = "{$a['xclass']}";
    return '<div class="subhead '.$xclass.'">'.do_shortcode($content).'</div>';
}

// added to CC
// to only show content based on current date
// [show from="dd/mm/yyyy" to="dd/mm/yyyy"]
// based on server date
// dates are inclusive
function wms_show( $atts, $content ){
    $a = shortcode_atts(array(
        'from' => '',
        'to' => '',
    ), $atts);
    $from = "{$a['from']}";
    $to = "{$a['to']}";
    $now = time();
    if( $from <> '' ){
        $datetime = DateTime::createFromFormat( "d/m/Y H:i:s", $from.' 00:00:00' );
        if($datetime){
            if( $now < $datetime->getTimestamp() ){
                // show it in a bit
                return '';
                // return '<!-- text hidden until '.$datetime->format( 'Y-m-d H:i:s' ).' -->';
            }
        }
    }
    if( $to <> '' ){
        $datetime = DateTime::createFromFormat( "d/m/Y H:i:s", $to.' 23:59:59' );
        if($datetime){
            if( $now > $datetime->getTimestamp() ){
                // showed it previously
                return '';
                // return '<!-- text hidden since '.$datetime->format( 'Y-m-d H:i:s' ).' -->';
            }
        }
    }
    // ok to show it
    return do_shortcode( $content );
}

// helps fit text into predefined space
// returns the max font size that will fit or false if it won't fit, even at 12px
// $advance_width is the width of an average character at $max_font-size
// $adv_width_change = the change in $advance_width for each pixel change in font size
// eg at 24 px the advance width of Sofia Pro 400 is 24 * 0.47 = 11.28
// not used as cc_tnc_text_size already used
function wms_text_max_font( $text, $space, $max_font=24, $adv_width_change=0.47 ){
    $text_length = strlen( $text );
    for ($i=$max_font; $i > 12 ; $i--) { 
        $advance_width = $i * $adv_width_change;
        $text_width = $advance_width * $text_length;
        if( $text_width < $space ){
            return $i;
        }
    }
    return false;
}

// attempts to return a tidier URL in a link
// will remove http/https and www from the front and trailing slash at the back
function wms_text_tidy_url( $raw_url, $xclass='', $target='' ){
    $better_url = $raw_url;
    if( substr( $better_url, 0, 8 ) == 'https://' ){
        $better_url = substr( $better_url, 8 );
    }elseif( substr( $better_url, 0, 7 ) == 'http://' ){
        $better_url = substr( $better_url, 7 );
    }
    if( substr( $better_url, 0, 4 ) == 'www.' ){
        $better_url = substr( $better_url, 4 );
    }
    if( substr( $better_url, -1, 1 ) == '/' ){
        $better_url = substr( $better_url, 0, strlen( $better_url ) - 1 );
    }
    if( $xclass == '' ){
        $link_class = '';
    }else{
        $link_class = ' class="'.$xclass.'"';
    }
    if( $target == '' ){
        $link_target = '';
    }else{
        $link_target = ' target="'.$target.'"';
    }
    return '<a href="'.esc_url( $raw_url ).'"'.$link_class.$link_target.'>'.$better_url.'</a>';
}
