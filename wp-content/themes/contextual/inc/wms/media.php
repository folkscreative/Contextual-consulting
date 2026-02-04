<?php
/**
 * Media
 * 
 * Video and images
 */

// embed responsive hd video
// [hd_video youtube="tMyFrdf06sk" quality="hd720" ratio="16:9"]
// ratio not actually used yet! :-)
add_shortcode('hd_video', 'wms_hd_video_shortcode');
function wms_hd_video_shortcode($atts){
    $a = shortcode_atts( array(
        'youtube' => '',
        'vimeo' => '',
        'quality' => '720p',
        'ratio' => '16:9',
        'hash' => '',
    ), $atts );
    $youtube = "{$a['youtube']}";
    $vimeo = "{$a['vimeo']}";
    $quality = "{$a['quality']}";
    $hash = "{$a['hash']}";
    switch ($quality) {
        case 'hd720':
        case 'HD720':
        case '720p':
            $vq = 'HD720';
            $size = 'width="1280" height="720"';
            break;
        case 'hd1080':
        case 'HD1080':
        case '1080i':
            $vq = 'HD1080';
            $size = 'width="1920" height="1080"';
            break;
    }
    $html = 'No video selected';
    if($youtube <> ''){
        $html = '<div class="hd-video-container '.$vq.'">';
        $html .= '<iframe '.$size.' src="//youtube.com/embed/'.$youtube.'?VQ='.$vq.'&REL=0" frameborder="0" allowfullscreen></iframe>';
        $html .= '</div>';
    }elseif($vimeo <> ''){
        $html = '<div class="hd-video-container '.$vq.'">';
        $html .= '<iframe '.$size.' src="//player.vimeo.com/video/'.$vimeo.'?';
        if( $hash <> '' ){
            $html .= 'h='.$hash.'&';
        }
        $html .= 'color=ff0179&title=0&byline=0&portrait=0" frameborder="0" allowfullscreen></iframe>';
        $html .= '</div>';
    }
    return $html;
}

// adds an image of the necessary size based on the id
// [image id="123" ratio="3:2" size="post-thumb" crop="yes" link="http://..." xclass="wms-square"]
add_shortcode('image', 'wms_media_image_shortcode');
function wms_media_image_shortcode($atts){
    $a = shortcode_atts( array(
        'id' => '',
        'ratio' => '3:2',
        'size' => 'post-thumb',
        'crop' => 'yes',
        'link' => '',
        'xclass' => '',
    ), $atts );
    $id = "{$a['id']}";
    $ratio = "{$a['ratio']}";
    $size = "{$a['size']}";
    $crop = "{$a['crop']}";
    $link = "{$a['link']}";
    $xclass = "{$a['xclass']}";
    if($crop == 'yes' || $crop == 'true'){
        $bg = true;
    }else{
        $bg = false;
    }
    return wms_media_image_html($id, $size, $bg, $ratio, $link, $xclass);
}

// return image html
// $image can be an id or a url
// $ratio is ignored if supplied with a url
// Feb 2022:
// - added $link
// April 2024
// - added lazyload ... but only for bg images!
function wms_media_image_html( $image, $size='post-thumb', $bg=true, $ratio='3:2', $link='', $xclass='', $add_bg_styles=false, $lazyload=false, $bgimg_xclass='', $flag='' ){
    $html = '';
    $image_url = '';
    if(substr($image, 0, 4) == 'http'){
        $image_url = $image;
    }else{
        $image_id = absint($image);
        if($image_id == $image && $image_id > 0){
            $image_data = wp_get_attachment_image_src($image_id, $size);
            if($image_data){
                // url = $image_data[0];
                // width = $image_data[1];
                // height = $image_data[2];
                $image_url = $image_data[0];
            }
        }
    }
    if($image_url <> ''){
        if($ratio == ''){
            $ratio = '3:2';
        }
        if($ratio == 'unset'){
            $padding_css = '';
        }else{
            list($numerator, $denominator) = explode(':', $ratio);
            $padding = 66.6667;
            if($numerator > 0 && $denominator > 0){
                $padding = 100 / $numerator * $denominator;
            }
            $padding_css = 'padding-bottom:'.$padding.'%;';
        }
        if($bg){
            $html .= '<div class="wms-image-wrapper '.$xclass.'">';
            if($add_bg_styles){
                $xstyles = ' background-repeat: no-repeat; background-position: center; background-size: cover; background-color: #ddd;';
            }else{
                $xstyles = '';
            }
            if($link == ''){
                if( $lazyload ){
                    $html .= '<div class="wms-bg-img lazy '.$bgimg_xclass.'" data-src="'.$image_url.'" style="'.$padding_css.$xstyles.'"></div>';
                }else{
                    $html .= '<div class="wms-bg-img '.$bgimg_xclass.'" style="background-image:url('.$image_url.'); '.$padding_css.$xstyles.'"></div>';
                }
            }else{
                if( $lazyload ){
                    $html .= '<a href="'.$link.'" class="wms-bg-img lazy '.$bgimg_xclass.'" data-src="'.$image_url.'" style="'.$padding_css.$xstyles.'"></a>';
                }else{
                    $html .= '<a href="'.$link.'" class="wms-bg-img '.$bgimg_xclass.'" style="background-image:url('.$image_url.'); '.$padding_css.$xstyles.'"></a>';
                }
            }
            if( $flag <> '' ){
                $html .= '<div class="train-card-flag">'.$flag.'</div>';
            }
            $html .= '</div>';
        }else{
            // these should have the option to lazyload too ... add this later
            if($link == ''){
                $html .= '<img src="'.$image_url.'" alt="" class="'.$xclass.'">';
            }else{
                $html .= '<a href="'.$link.'"><img src="'.$image_url.'" alt="" class="'.$xclass.'"></a>';
            }
        }
    }else{
        $html .= '<!-- image '.$image.' not found -->';
    }
    return $html;
}

/**
 * Get all the registered image sizes along with their dimensions
 * @global array $_wp_additional_image_sizes
 * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
 * @return array $image_sizes The image sizes
 */
function wms_media_get_all_image_sizes() {
    global $_wp_additional_image_sizes;
    $default_image_sizes = get_intermediate_image_sizes();
    foreach ( $default_image_sizes as $size ) {
        $image_sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
        $image_sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
        // I don't care about crop so let's save a bit of processing ...
        // $image_sizes[ $size ][ 'crop' ] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
    }
    if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
        $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
    }
    // sort them into order of width
    usort($image_sizes, function($a, $b) {
        return $a['width'] <=> $b['width'];
    });
    return $image_sizes;
}

// allow SVGs to be uploaded
// https://wpengine.co.uk/resources/enable-svg-wordpress/
// WPv4.7.1 or later
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype( $filename, $mimes );
    return [
        'ext'             => $filetype['ext'],
        'type'            => $filetype['type'],
        'proper_filename' => $data['proper_filename']
    ];
}, 10, 4 );

function wms_media_cc_mime_types( $mimes ){
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'wms_media_cc_mime_types' );

function wms_media_fix_svg() {
    echo '<style type="text/css">
        .attachment-266x266, .thumbnail img {
             width: 100% !important;
             height: auto !important;
        }
        </style>';
}
add_action( 'admin_head', 'wms_media_fix_svg' );


// get the ID of an image from its URL
// returns an id or false on failure
function wms_media_get_id( $url ){
    // var_dump($url);
    global $wpdb;
    $sql = "SELECT ID FROM $wpdb->posts WHERE guid = '$url'";
    $image_id = $wpdb->get_var( $sql );
    // var_dump($image_id);
    if( $image_id === null ){
        // maybe it's an auto-generated image
        // let's try to create the url of the base image
        $url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $url );
        // var_dump($url);
        $sql = "SELECT ID FROM $wpdb->posts WHERE guid = '$url'";
        $image_id = $wpdb->get_var( $sql );
        // var_dump($image_id);
        if( $image_id === null ){
            // var_dump('failed');
            return false;
        }
    }
    // var_dump('succeededed');
    return $image_id;
}

