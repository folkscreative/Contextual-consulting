<?php
/**
 * WMS Widgets
 *
 * Includes:
 * - Contact Us
 * - Our Hours
 * - Logos
 * - Map
 * - Recent Posts with Images
 */

// Accommodate shortcodes in widgets
add_filter( 'widget_text', 'do_shortcode' );

/*
 * Contact Us
 */
class WMS_Contact_Us extends WP_Widget {
  public function __construct(){
    $widget_ops = array('classname' => 'WMS_Contact_Us', 'description' => 'Displays your contact details' );
    parent::__construct('WMS_Contact_Us', 'WMS Contact Us Widget', $widget_ops);
  }

  public function form($instance){
    global $rpm_theme_options;
    $defaults = array(
      'title' => 'Contact Us',
      'display_biz_name' => 'on',
      'display_address' => 'on',
      'display_phone' => 'on',
      'display_social' => '',
      'display_email_icon' => '',
      'display_email' => '',
      'location' => '',
      'display_social_list' => '',
      );
    $instance = wp_parse_args( (array) $instance, $defaults );
    $title = $instance['title'];
    $location = $instance['location'];
  ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label>
    </p>
    <?php 
    /*
    if($rpm_theme_options['second-locn']){
      if($rpm_theme_options['location-name'] == ''){
        $location_name = 'Location One';
      }else{
        $location_name = $rpm_theme_options['location-name'];
      }
      if($rpm_theme_options['location-name-2'] == ''){
        $location_name_2 = 'Location Two';
      }else{
        $location_name_2 = $rpm_theme_options['location-name-2'];
      } ?>
      <p>
        <label for="<?php echo $this->get_field_id('location'); ?>">Location: 
          <select class="widefat" id="<?php echo $this->get_field_id('location'); ?>" name="<?php echo $this->get_field_name('location'); ?>">
            <option value=""<?php if($location == '') echo ' selected'; ?>><?php echo $location_name; ?></option>
            <option value="2"<?php if($location == '2') echo ' selected'; ?>><?php echo $location_name_2; ?></option>
          </select>
        </label>
      </p>
    <?php } 
    */
    ?>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_biz_name'], 'on'); ?> id="<?php echo $this->get_field_id('display_biz_name'); ?>" name="<?php echo $this->get_field_name('display_biz_name'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_biz_name'); ?>">Show Location name</label>
    </p>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_address'], 'on'); ?> id="<?php echo $this->get_field_id('display_address'); ?>" name="<?php echo $this->get_field_name('display_address'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_address'); ?>">Show Address</label>
    </p>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_phone'], 'on'); ?> id="<?php echo $this->get_field_id('display_phone'); ?>" name="<?php echo $this->get_field_name('display_phone'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_phone'); ?>">Show Phone No.</label>
    </p>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_social'], 'on'); ?> id="<?php echo $this->get_field_id('display_social'); ?>" name="<?php echo $this->get_field_name('display_social'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_social'); ?>">Show Social Icons</label>
    </p>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_social_list'], 'on'); ?> id="<?php echo $this->get_field_id('display_social_list'); ?>" name="<?php echo $this->get_field_name('display_social_list'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_social_list'); ?>">Show social page names in list</label>
    </p>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_email_icon'], 'on'); ?> id="<?php echo $this->get_field_id('display_email_icon'); ?>" name="<?php echo $this->get_field_name('display_email_icon'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_email_icon'); ?>">Show E-mail Icon</label>
    </p>
    <p>
      <input class="checkbox" type="checkbox" <?php checked($instance['display_email'], 'on'); ?> id="<?php echo $this->get_field_id('display_email'); ?>" name="<?php echo $this->get_field_name('display_email'); ?>" /> 
      <label for="<?php echo $this->get_field_id('display_email'); ?>">Show E-mail Address</label>
    </p>
  <?php
  }

  public function update($new_instance, $old_instance){
    $instance = $old_instance;
    $things = array('title', 'display_biz_name', 'display_address', 'display_phone', 'display_email', 'display_email_icon', 'display_social', 'location', 'display_social_list');
    foreach ($things as $thing) {
      if(isset($new_instance[$thing]) && $new_instance[$thing] <> ''){
        $instance[$thing] = $new_instance[$thing];
      }else{
        $instance[$thing] = '';
      }
    }
    return $instance;
  }

  public function widget($args, $instance){
    global $rpm_theme_options;
    extract($args, EXTR_SKIP);
    /*
    if($rpm_theme_options['second-locn'] && $instance['location'] == '2'){
      $business_name = $rpm_theme_options['location-name-2'];
      $address_box = wms_address_formatted(2, '<br>');
      $phone_number = $rpm_theme_options['phone-number-2'];
      $phone_number_tel = $rpm_theme_options['phone-number-tel-2'];
      // $fax_number = $rpm_theme_options['fax-number-2'];
      $fax_number = '';
      $business_email = $rpm_theme_options['business-email-2'];
    }else{
      */
      $business_name = $rpm_theme_options['business-name'];
      $address_box = wms_address_formatted(1, '<br>');
      $phone_number = $rpm_theme_options['phone-number'];
      $phone_number_tel = $rpm_theme_options['phone-number-tel'];
      // $fax_number = $rpm_theme_options['fax-number'];
      $fax_number = '';
      $business_email = $rpm_theme_options['business-email'];
    // }
    echo $before_widget; ?>
    <div class="contact-us-widget">
      <?php $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
      if (!empty($title)) echo $before_title . $title . $after_title; ?>
      <div class="contact-us-details">
        <?php if($instance['display_biz_name'] == 'on' || $instance['display_address'] == 'on'){
          echo '<p>';
          if($instance['display_biz_name'] == 'on'){
            echo $business_name;
          }
          if($instance['display_biz_name'] == 'on' && $instance['display_address'] == 'on'){
            echo '<br>';
          }
          if($instance['display_address'] == 'on'){
            echo nl2br($address_box);
          }
          echo '</p>';
        }
        echo '<p>';
        $break_it = false;
        if($instance['display_phone'] == 'on' && $phone_number <> ''){
          if($phone_number_tel <> ''){
            $phone_number = '<a href="'.$phone_number_tel.'">'.$phone_number.'</a>';
          }
          echo '<i class="fa fa-phone fa-fw"></i> '.$phone_number;
          $break_it = true;
        }
        if($instance['display_phone'] == 'on' && $fax_number <> ''){
          if($break_it) echo '<br>';
          echo '<i class="fa fa-fax fa-fw"></i> '.$fax_number;
          $break_it = true;
        }
        if('on' == $instance['display_social'] && wms_social_required()){
          if($break_it) echo '<br>';
          echo wms_social_icons();
          if($instance['display_email_icon'] == 'on'){
            if($rpm_theme_options['business-email'] <> ''){
              $html .= '<span class="social-icons"><a class="social-link" href="mailto:'.antispambot($rpm_theme_options['business-email']).'" target="_blank">';
              $html .= '<i class="fas fa-fw fa-envelope"></i>';
              $html .= '</a></span>';
            }
          }
          $break_it = true;
        }elseif($instance['display_email_icon'] == 'on' && $business_email <> ''){
          if($break_it) echo '<br>';
          echo '<a href="mailto:'.antispambot($business_email).'"><i class="fa fa-envelope"></i>';
          $break_it = true;
        }
        if('on' == $instance['display_social_list'] && wms_social_required()){
          if($break_it) echo '<br>';
          echo wms_social_list();
          $break_it = true;
        }
        if('on' == $instance['display_email'] && $business_email <> ''){
          if($break_it) echo '<br>';
          echo '<i class="fa fa-envelope fa-fw"></i> <a href="mailto:'.antispambot($business_email).'">'.antispambot($business_email).'</a>';
        }
        echo '</p>';
        ?>
      </div>
    </div>
    <?php 
    echo $after_widget;
  }
}
add_action( 'widgets_init', function(){
    register_widget( 'WMS_Contact_Us' );
});

/*
 * Our Hours
 */
class WMS_Our_Hours extends WP_Widget {
  public function __construct(){
    $widget_ops = array('classname' => 'WMS_Our_Hours', 'description' => 'Displays a table of opening hours' );
    parent::__construct('WMS_Our_Hours', 'WMS Our Hours Widget', $widget_ops);
  }
  public function form($instance){
    global $rpm_theme_options;
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
    if($title == ''){
      $title = $rpm_theme_options['opening-hours-text'];
    }
    ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
  <?php
  }
  public function update($new_instance, $old_instance){
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
  public function widget($args, $instance){
    global $rpm_theme_options;
    extract($args, EXTR_SKIP);
    echo $before_widget; ?>
    <div class="our-hours">
      <?php $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
      if (!empty($title)) echo $before_title . $title . $after_title;
      if(function_exists('wms_opening_hours')){ // in inc/wms-functions.php
        echo wms_opening_hours();
      }else{ ?>
        <div class="hours-div row">
          <?php
            $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
            foreach($days as $dayname){ ?>
              <div class="day-name col-5"><?php echo $dayname; ?></div>
              <div class="day-hours col-7"><?php echo $rpm_theme_options['opening-hours-'.strtolower($dayname)]; ?></div>
            <?php } ?>
        </div>
      <?php } ?>
    </div>
    <?php 
    echo $after_widget;
  }
}
add_action( 'widgets_init', function(){
    register_widget( 'WMS_Our_Hours' );
});

/*
 * Logos
 */
class WMS_Logos extends WP_Widget {
  public function __construct(){
    $widget_ops = array('classname' => 'WMS_Logos', 'description' => 'Displays multiple logos for sidebars' );
    parent::__construct('WMS_Logos', 'WMS Logos Widget', $widget_ops);
  }
  public function form($instance){
    $defaults = array( 
      'title' => '', 
      'logo_url' => '',
      'max_width' => '100',
      'max_height' => '40',
      'spacing' => '10'
      );  
    $instance = wp_parse_args( (array) $instance, $defaults );
    $title = $instance['title'];
    $logo_url = $instance['logo_url'];
    $max_width = $instance['max_width'];
    $max_height = $instance['max_height'];
    $spacing = $instance['spacing'];
    ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
    <p><label for="<?php echo $this->get_field_id('logo_url'); ?>">Logos URLs (ONE PER LINE): <textarea rows="10" class="widefat" id="<?php echo $this->get_field_id('logo_url'); ?>" name="<?php echo $this->get_field_name('logo_url'); ?>" type="text"><?php echo esc_attr($logo_url); ?></textarea></label></p>
    <p><label for="<?php echo $this->get_field_id('max_width'); ?>">The maximum width of each logo (in pixels) <input type="text" id="<?php echo $this->get_field_id('max_width'); ?>" name="<?php echo $this->get_field_name('max_width'); ?>" value="<?php echo esc_attr($max_width); ?>"></label></p>
    <p><label for="<?php echo $this->get_field_id('max_height'); ?>">The maximum height of each logo (in pixels) <input type="text" id="<?php echo $this->get_field_id('max_height'); ?>" name="<?php echo $this->get_field_name('max_height'); ?>" value="<?php echo esc_attr($max_height); ?>"></label></p>
    <p><label for="<?php echo $this->get_field_id('spacing'); ?>">The spacing in between each logo (in pixels) <input type="text" id="<?php echo $this->get_field_id('spacing'); ?>" name="<?php echo $this->get_field_name('spacing'); ?>" value="<?php echo esc_attr($spacing); ?>"></label></p>
  <?php
  }
  public function update($new_instance, $old_instance){
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['logo_url'] = $new_instance['logo_url'];
    $instance['max_width'] = $new_instance['max_width'];
    $instance['max_height'] = $new_instance['max_height'];
    $instance['spacing'] = $new_instance['spacing'];
    return $instance;
  }
  public function widget($args, $instance){
    extract($args, EXTR_SKIP);
    $text = trim($instance['logo_url']);
    $textAr = explode("\n", $text);
    $textAr = array_filter($textAr, 'trim'); // remove any extra \r characters left behind
    $widget_num = rand(1000,9999);
    echo $before_widget; ?>
    <div id="logos_widget_<?php echo $widget_num; ?>" class="logos_widget">
      <?php $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
      if (!empty($title)) echo $before_title . $title . $after_title;
      foreach ($textAr as $line) {
        echo '<img src="' . $line . '">';
      } 
      ?>
    </div>
    <div class="clear"></div>
    <style>
    #logos_widget_<?php echo $widget_num; ?> img {max-width: <?php echo $instance['max_width']; ?>px; max-height: <?php echo $instance['max_height']; ?>px; margin-right: <?php echo $instance['spacing']; ?>px; margin-left: <?php echo $instance['spacing']; ?>px;}
    </style>
    <?php 
    echo $after_widget;
  }
}
add_action( 'widgets_init', function(){
    register_widget( 'WMS_Logos' );
});

/**
 * Map
 */
class WMS_Google_Maps extends WP_Widget {
  public function __construct(){
    $widget_ops = array('classname' => 'WMS_Google_Maps', 'description' => 'Displays a Google Map' );
    parent::__construct('WMS_Google_Maps', 'WMS Google Maps Widget', $widget_ops);
  }
  public function form($instance){
    global $rpm_theme_options;
    $defaults = array( 
      'title' => '',
      'address_loc' => 'theme',
      'show_directions' => 'yes',
      'join_to_next' => 'no'
      );  
    $instance = wp_parse_args( (array) $instance, $defaults );
    $title = '';
    $address_loc = 'theme';
    $business_name = '';
    $address_line = '';
    $latitude = '';
    $longitude = '';
    $map_zoom = '';
    $show_directions = 'yes';
    $join_to_next = 'no';
    $things = array('title', 'address_loc', 'business_name', 'address_line', 'latitude', 'longitude', 'map_zoom', 'show_directions', 'join_to_next');
    foreach ($things as $thing) {
      if(isset($instance[$thing]) && $instance[$thing] <> ''){
        $$thing = $instance[$thing];
      }
    }
    $second_locn = 'no';
    ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
    <p><label for="<?php echo $this->get_field_id('address_loc'); ?>">Address to use:
      <select class="widefat wms-google-maps-widget-address-loc" id="<?php echo $this->get_field_id('address_loc'); ?>" name="<?php echo $this->get_field_name('address_loc'); ?>">
        <option value="theme"<?php if($address_loc == "theme") echo ' selected'; ?>>Theme Options</option>
        <?php if($second_locn == 'yes'){ ?>
          <option value="second"<?php if($address_loc == "second") echo ' selected'; ?>>Theme (second loc)</option>
        <?php } ?>
        <option value="custom"<?php if($address_loc == "custom") echo ' selected'; ?>>Custom Details</option>
      </select>
    </label></p>
    <div class="wms_google_maps_custom_dets">
      <p><label for="<?php echo $this->get_field_id('business_name'); ?>">Business name: <input type="text" class="widefat" id="<?php echo $this->get_field_id('business_name'); ?>" name="<?php echo $this->get_field_name('business_name'); ?>" value="<?php echo esc_attr($business_name); ?>" /></label></p>
      <p><label for="<?php echo $this->get_field_id('address_line'); ?>">Address line: <input type="text" class="widefat" id="<?php echo $this->get_field_id('address_line'); ?>" name="<?php echo $this->get_field_name('address_line'); ?>" value="<?php echo esc_attr($address_line); ?>" /></label></p>
      <p><label for="<?php echo $this->get_field_id('latitude'); ?>">Latitude: <input type="text" class="widefat" id="<?php echo $this->get_field_id('latitude'); ?>" name="<?php echo $this->get_field_name('latitude'); ?>" value="<?php echo esc_attr($latitude); ?>" /></label></p>
      <p><label for="<?php echo $this->get_field_id('longitude'); ?>">Longitude: <input type="text" class="widefat" id="<?php echo $this->get_field_id('longitude'); ?>" name="<?php echo $this->get_field_name('longitude'); ?>" value="<?php echo esc_attr($longitude); ?>" /></label></p>
      <p><label for="<?php echo $this->get_field_id('map_zoom'); ?>">Map zoom: <input type="text" class="widefat" id="<?php echo $this->get_field_id('map_zoom'); ?>" name="<?php echo $this->get_field_name('map_zoom'); ?>" value="<?php echo esc_attr($map_zoom); ?>" /></label></p>
    </div><!-- .wms_google_maps_custom_dets -->
    <p><label for="<?php echo $this->get_field_id('show_directions'); ?>">Show link to get directions?
      <select class="widefat" id="<?php echo $this->get_field_id('show_directions'); ?>" name="<?php echo $this->get_field_name('show_directions'); ?>">
        <option value="yes"<?php if($show_directions == "yes") echo ' selected'; ?>>Yes</option>
        <option value="no"<?php if($show_directions == "no") echo ' selected'; ?>>No</option>
      </select>
    </label></p>
    <p><label for="<?php echo $this->get_field_id('join_to_next'); ?>">Join to next widget?
      <select name="<?php echo $this->get_field_name('join_to_next'); ?>" id="<?php echo $this->get_field_id('join_to_next'); ?>">
        <option value="no"<?php if($join_to_next == "no") echo ' selected'; ?>>No</option>
        <option value="yes"<?php if($join_to_next == "yes") echo ' selected'; ?>>Yes</option>
      </select>
    </label></p>
    <?php
  }
  public function update($new_instance, $old_instance){
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['address_loc'] = $new_instance['address_loc'];
    $instance['business_name'] = $new_instance['business_name'];
    $instance['address_line'] = $new_instance['address_line'];
    $instance['latitude'] = $new_instance['latitude'];
    $instance['longitude'] = $new_instance['longitude'];
    $instance['map_zoom'] = $new_instance['map_zoom'];
    $instance['show_directions'] = $new_instance['show_directions'];
    $instance['join_to_next'] = $new_instance['join_to_next'];
    return $instance;
  }
  public function widget($args, $instance){
    global $rpm_theme_options;
    extract($args, EXTR_SKIP);
    $join_to_next = empty($instance['join_to_next']) ? '' : $instance['join_to_next'];
    if($join_to_next == 'yes'){
      if(strpos($before_widget, 'class') === false ){
        $before_widget = str_replace('>', 'class="join-to-next">', $before_widget);
      }else{
        $before_widget = str_replace('class="', 'class="join-to-next ', $before_widget);
      }
    }
    echo $before_widget;
    $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
    if (!empty($title)) echo $before_title . $title . $after_title;
    $address_loc = empty($instance['address_loc']) ? 'theme' : $instance['address_loc'];
    if($address_loc == 'theme'){
      $business_name = $rpm_theme_options['business-name'];
      // $address_line = str_replace("'", "&#39;", $rpm_theme_options['address-line']);
      $address_line = wms_address_formatted(1, ', ');
      $latitude = $rpm_theme_options['latitude'];
      $longitude = $rpm_theme_options['longitude'];
      $map_zoom = $rpm_theme_options['map-zoom'];
    }elseif($address_loc == 'second'){
      $business_name = $rpm_theme_options['business-name-2'];
      $address_line = wms_address_formatted(2, ', ');
      $latitude = $rpm_theme_options['latitude-2'];
      $longitude = $rpm_theme_options['longitude-2'];
      $map_zoom = $rpm_theme_options['map-zoom-2'];
    }else{
      $business_name = $instance['business_name'];
      $address_line = str_replace("'", "&#39;", $instance['address_line']);
      $latitude = $instance['latitude'];
      $longitude = $instance['longitude'];
      $map_zoom = $instance['map_zoom'];
    }
    $show_directions = empty($instance['show_directions']) ? 'yes' : $instance['show_directions'];
    $map_num = 'map_canvas_'.rand(1000,9999);
    $map_style = $rpm_theme_options['map-style'];
    if($map_style == 'desat'){
      $map_styling = ', styles: [{stylers: [{saturation: -100}] }]';
    }else{
      $map_styling = '';
    }
    ?>
    <div class="google-map">
      <div id="map_canvas_<?php echo $map_num; ?>" class="canvas"></div>
      <script>
        var myLatlng = new google.maps.LatLng(<?php echo $latitude.','.$longitude; ?>);
        var mapOptions = {
          zoom: <?php echo $map_zoom; ?>,
          center: myLatlng, 
          mapTypeId: google.maps.MapTypeId.ROADMAP<?php echo $map_styling; ?>
        }
        var map<?php echo $map_num; ?> = new google.maps.Map(document.getElementById('map_canvas_<?php echo $map_num; ?>'), mapOptions);
        var contentString<?php echo $map_num; ?> = '<div id="content">'+
          '<h4 id="gMapHeading<?php echo $map_num; ?>" class="gMapHeading"><?php echo $business_name; ?></h4>'+
          '<div id="gMapContent<?php echo $map_num; ?>">'+
          '<p><?php echo $address_line; ?></p>'+
          '</div>'+
          '</div>';
        var infowindow<?php echo $map_num; ?> = new google.maps.InfoWindow({
          content: contentString<?php echo $map_num; ?>,
          maxWidth: 200
        });
        var marker<?php echo $map_num; ?> = new google.maps.Marker({
          position: myLatlng,
          map: map<?php echo $map_num; ?>,
          title: '<?php echo $business_name; ?>'
        });
        google.maps.event.addListener(marker<?php echo $map_num; ?>, 'click', function() {
        infowindow<?php echo $map_num; ?>.open(map<?php echo $map_num; ?>,marker<?php echo $map_num; ?>);
        });
      </script>
    </div>
    <?php if($show_directions == 'yes'){ ?>
      <div class="google-directions text-center">
        <a class="btn btn-default btn-block" href="https://maps.google.com/maps?daddr=<?php echo $latitude.','.$longitude; ?>&hl=en&mra=ltm&t=m&z=17" target="_blank">Get Directions</a>
      </div>
    <?php }
    echo $after_widget;
  }
}
add_action( 'widgets_init', function(){
    register_widget( 'WMS_Google_Maps' );
});

/*
 * Recent Posts with Images
 */
class WMS_Recent_Posts_Images extends WP_Widget {
  public function __construct(){
    $widget_ops = array('classname' => 'WMS_Recent_Posts_Images', 'description' => 'Displays recent posts with images. Uses the Get The Image plugin so please make sure that is active too.' );
    parent::__construct('WMS_Recent_Posts_Images', 'WMS Recent Posts With Images Widget', $widget_ops);
  }
  public function form($instance){
    $defaults = array( 
      'title' => '', 
      'num_posts' => '5',
      'style' => 'list'
      );  
    $instance = wp_parse_args( (array) $instance, $defaults );
    $title = $instance['title'];
    $num_posts = $instance['num_posts'];
    $style = $instance['style'];
    ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
    <p><label for="<?php echo $this->get_field_id('num_posts'); ?>">How many posts to show: 
      <input class="widefat" id="<?php echo $this->get_field_id('num_posts'); ?>" name="<?php echo $this->get_field_name('num_posts'); ?>" type="text" value="<?php echo esc_attr($num_posts); ?>" />
    </label></p>
    <p><label for="<?php echo $this->get_field_id('style'); ?>">What layout do you want?
      <select class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>">
        <option value="list"<?php if($style == "list") echo ' selected'; ?>>List</option>
        <option value="slider"<?php if($style == "slider") echo ' selected'; ?>>Slider</option>
      </select>
    </label></p>
    <?php 
  }
  public function update($new_instance, $old_instance){
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['num_posts'] = $new_instance['num_posts'];
    $instance['style'] = $new_instance['style'];
    return $instance;
  }
  public function widget($args, $instance){
    extract($args, EXTR_SKIP);
    $style = $instance['style'];
    if($style <> 'slider') $style = 'list';
    echo $before_widget; ?>
    <div class="recent-posts-images-widget rpi-<?php echo $style; ?>">
      <?php $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
      if (!empty($title)) echo $before_title . $title . $after_title;
      $num_posts = $instance['num_posts'];
      $args = array(
          'numberposts' => $num_posts,
          'post_status' => 'publish'
          );
      $recent_posts = wp_get_recent_posts( $args, $output = ARRAY_A );
      if($style == 'list'){
          foreach( $recent_posts as $recent ){ ?>
              <div class="rpi-post">
                  <div class="post-image-wrap">
                      <?php if(function_exists('get_the_image')) get_the_image(array('post_id' => $recent["ID"], 'scan' => true, 'size' => 'post-thumb')); ?>
                  </div>
                  <div class="entry-meta">
                      <?php echo get_the_time('d M Y',$recent["ID"]); ?>
                  </div><!-- .entry-meta -->
                  <h5 class="entry-title">
                      <?php echo '<a href="' . get_permalink($recent["ID"]) . '" title="'.esc_attr($recent["post_title"]).'" >' .   $recent["post_title"].'</a>'; ?>
                  </h5>
              </div>
          <?php }
      }else{
          // style = slider
          $rpiw_num = 'rpi_'.rand(1000,9999); ?>
          <div id="<?php echo $rpiw_num; ?>" class="carousel slide" data-ride="carousel">
              <div class="carousel-inner">
                  <?php
                  $item_class = ' active';
                  foreach( $recent_posts as $recent ){ ?>
                      <div class="carousel-item<?php echo $item_class; ?>">
                          <div class="post-image-wrap">
                              <?php if(function_exists('get_the_image')) get_the_image(array('post_id' => $recent["ID"], 'scan' => true, 'size' => 'post-thumb')); ?>
                          </div>
                          <div class="entry-meta">
                              <?php echo get_the_time('d M Y',$recent["ID"]); ?>
                          </div><!-- .entry-meta -->
                          <h5 class="entry-title">
                              <?php echo '<a href="' . get_permalink($recent["ID"]) . '" title="'.esc_attr($recent["post_title"]).'" >' .   $recent["post_title"].'</a>'; ?>
                          </h5>
                      </div>
                      <?php
                      $item_class = '';
                  } ?>
              </div>
              <a class="carousel-control-prev" href="#<?php echo $rpiw_num; ?>" role="button" data-slide="prev">
                  <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                  <span class="sr-only">Previous</span>
              </a>
              <a class="carousel-control-next" href="#<?php echo $rpiw_num; ?>" role="button" data-slide="next">
                  <span class="carousel-control-next-icon" aria-hidden="true"></span>
                  <span class="sr-only">Next</span>
              </a>
          </div>
      <?php } ?>
    </div><!-- .recent-posts-images-widget -->
    <?php 
    echo $after_widget;
  }
}
add_action( 'widgets_init', function(){
    register_widget( 'WMS_Recent_Posts_Images' );
});
