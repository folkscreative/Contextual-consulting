<?php
/**
* Template Name: Training Delivery Page
* - Vimeo script is enqueued for this page template
*/

// should this person be here???
$training_id = 0;
if( isset( $_POST['id'] ) ){
	$training_id = absint( $_POST['id'] );
}
if( $training_id == 0 || ! wp_verify_nonce( $_POST['training_delivery_nonce'], 'course_id'.$training_id ) ){
	wp_redirect( '/my-account' );
    exit;
}

if( ! cc_users_is_valid_user_logged_in() ){
	wp_redirect( '/member-login' );
    exit;
}

$user = wp_get_current_user();

$recording_access = ccrecw_user_can_view( $training_id, $user->ID ); 
if( ! $recording_access['access'] ){
	wp_redirect( '/my-account' );
    exit;
}

// ok, all looks good

$course = course_get_all( $training_id );
$viewing_data = get_training_viewing_stats( $training_id, $user->ID );

// Debug: Log what we're getting
// error_log('Training ' . $training_id . ' viewing data: ' . print_r($viewing_data, true));
// error_log('Last viewed section: ' . ($viewing_data['last_viewed_section'] ?? 'not set'));

$first_video_section = 0;
$video_progress_data = array();

get_header();
while ( have_posts() ) : 
	the_post(); ?>
	<div id="workshop-header-<?php echo $training_id; ?>" class="workshop-header-wrap wms-section wms-section-std sml-padd-top sml-padd-bot">
		<div class="wms-sect-bg wms-bg-img px-xxl-5">
			<div class="container">
				<div class="workshop-header">
					<div class="row workshop-title-row">
						<div class="col text-center">
							<h1 class="workshop-title"><?php echo $course['post_title']; ?></h1>
							<?php
							$workshop_subtitle = course_single_meta( $course, 'subtitle' );
							if($workshop_subtitle <> ''){ ?>
								<h3 class="workshop-subtitle"><?php echo $workshop_subtitle; ?></h3>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	$feat_img_id = course_single_meta( $course, '_thumbnail_id' );
	if($feat_img_id > 0){
		echo wms_section_css('workshop-header-'.$training_id, '#eef1f4', '', '#ffffff', 'rgba(0,0,0,0.5)', $feat_img_id, '', '', 'center', '', 'sect');
	}
    ?>

    <div id="ptd-training-container" class="ptd-training-container my-5">
        <!-- Menu Toggle Button -->
        <button class="ptd-menu-toggle d-lg-none mb-5" id="ptd-menu-toggle">
            <i class="fas fa-bars menu-closed"></i>
            <i class="fas fa-times menu-open"></i>
        </button>

        <div class="row ptd-training-row d-flex">

            <div class="col-lg-3 ptd-menu-column" id="ptdMenuColumn">

                <div class="ptd-training-menu" id="trainingMenu">
                    <div id="menuContent" class="ptd-menu-content">
                        <div class="row">
                            <div class="col-11 col-lg-12">
                                <h5 class="mb-3" id="courseTitle"><?php echo $course['post_title']; ?></h5>
                            </div>
                            <div class="col-1 d-lg-none text-end">
                                <a id="ptd-menu-closer" class="ptd-menu-closer" href="javascript:void(0);">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                        <?php
                        $use_accordions = ( $course['module_counts']['module_count'] > 1 ) ? true : false;
                        // $show_section_titles = ( $course['module_counts']['all_sections_count'] > 1 ) ? true : false;
                        ?>
                        <?php
                        if( $use_accordions ){
                            $accordion_already_opened = false;
                            ?>
                            <div class="accordion" id="ptd-menu-accordion">
                        <?php }
                        foreach ($course['modules'] as $module_index => $module) {
                            if( $use_accordions ){
                                // does this module contain the last viewed section?
                                $section_ids = array_column( $module['sections'], 'id' );
                                if( ! $accordion_already_opened && ( $viewing_data['last_viewed_section'] == 0 || in_array( $viewing_data['last_viewed_section'], $section_ids ) ) ){
                                    $accordion_collapse_class = 'show';
                                    $accordion_button_class = '';
                                    $aria_expanded = 'true';
                                    $accordion_already_opened = true;
                                }else{
                                    $accordion_collapse_class = '';
                                    $accordion_button_class = 'collapsed';
                                    $aria_expanded = 'false';
                                }
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button ptd-module-title <?php echo $accordion_button_class; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#ptd-module-<?php echo $module['id']; ?>" aria-expanded="<?php echo $aria_expanded; ?>" aria-controls="ptd-module-<?php echo $module['id']; ?>">
                                            <?php echo $module['title']; ?>
                                        </button>
                                    </h2>
                                    <div id="ptd-module-<?php echo $module['id']; ?>" class="accordion-collapse collapse <?php echo $accordion_collapse_class; ?>">
                                        <div class="accordion-body">
                            <?php }

                            foreach ( $module['sections'] as $section_index => $section ){
                                $section_id = $section['id'];
                                ?>

                                <div class="ptd-section-item">

                                    <?php
                                    // section title shown if there are multiple sections ... nope!
                                    /*
                                    if( $module['sections_count'] > 1 ){ ?>
                                        <div class="fw-semibold mb-2"><?php echo $section['title']; ?></div>
                                    <?php }
                                    */

                                    // video
                                    if( $section['recording_type'] == 'vimeo' && $section['recording_id'] <> '' ){
                                        if( $first_video_section == 0 ){
                                            $first_video_section = $section_id;
                                        }
                                        $duration = get_video_duration( $section['recording_id'] );
                                        if( $duration === false ){
                                            $duration = 0;
                                        }
                                        $progress = 0;
                                        if( $duration > 0 && isset( $viewing_data['viewing_stats'][$section_id]['last_playhead'] ) && $viewing_data['viewing_stats'][$section_id]['last_playhead'] > 0 ){
                                            $progress = round( $viewing_data['viewing_stats'][$section_id]['last_playhead'] / $duration * 100 );
                                        }
                                        if( $section['zoom_chat']['chat'] == '' ){
                                            $has_chat = 'false';
                                        }else{
                                            $has_chat = 'true';
                                        }

                                        // store video progress for the javascript
                                        $last_position = isset( $viewing_data['viewing_stats'][$section_id]['last_playhead'] ) ? $viewing_data['viewing_stats'][$section_id]['last_playhead'] : 0;
                                        $video_progress_data[$section_id] = $last_position;

                                        // to make setting the data variables simpler ....
                                        $video_data = $viewing_data['viewing_stats'][$section_id];

                                        ?>
                                        <a href="#" 
                                            id="ptd-video-item-<?php echo $section_id; ?>" 
                                            class="ptd-video-item" 
                                            data-section-id="<?php echo $section_id; ?>" 
                                            data-vimeo-id="<?php echo $section['recording_id']; ?>" 
                                            data-has-chat="<?php echo $has_chat; ?>" 
                                            data-duration="<?php echo $duration; ?>"
                                            data-last-position="<?php echo esc_attr($video_data['last_playhead'] ?? 0); ?>"
                                            data-furthest-seconds="<?php echo esc_attr($video_data['last_playhead'] ?? 0); ?>"
                                            data-completed="<?php echo $video_data['viewed_end'] === 'yes' ? '1' : '0'; ?>"
                                            data-cumulative-seconds="<?php echo esc_attr($video_data['viewing_time'] ?? 0); ?>">
                                            <i class="fas fa-play-circle me-2"></i>
                                            <span class="ptd-video-title">
                                                <?php echo $section['title']; ?>
                                            </span>
                                            <?php
                                            if( $duration > 0 ){
                                                echo ' <small>('.cc_timezones_format_duration( $duration ).')</small>';
                                            }
                                            ?>
                                            <div class="ptd-video-progress">
                                                <div id="ptd-video-progress-bar-<?php echo $section_id; ?>" class="ptd-video-progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                        </a>
                                    <?php }

                                    // section resources
                                    if( ! empty( $section['resources'] ) ){
                                        foreach ( $section['resources'] as $resource_index => $resource ){
                                            $resource_name = $resource['resource_name'] == '' ? esc_url( $resource['resource_url'] ) : $resource['resource_name'];
                                            // $file_type = strtolower( substr($resource['resource_url'], strrpos($resource['resource_url'], '.') + 1 ) );
                                            echo resource_icon_link( $resource['resource_url'], $resource_name, 'ptd-resource-item', 'me-2' );
                                            /*
                                            $resource_icon = resource_file_icon( $resource['resource_url'], 'me-2' );
                                            ?>
                                            <a href="<?php echo esc_url( $resource['resource_url'] ); ?>" class="ptd-resource-item" download>
                                                <?php echo $resource_icon.$resource_name; ?>
                                            </a>
                                            */
                                        }
                                    }
                                    ?>

                                </div><!-- .ptd-section-item -->

                                <?php
                            }

                            // are there any module resources?
                            if( ! empty( $module['resources'] ) ){
                                foreach ( $module['resources'] as $resource_index => $resource ){
                                    $resource_name = $resource['resource_name'] == '' ? esc_url( $resource['resource_url'] ) : $resource['resource_name'];
                                    echo resource_icon_link( $resource['resource_url'], $resource_name, 'ptd-module-resource-item', 'me-2' );
                                    /*
                                    $resource_icon = resource_file_icon( $resource['resource_url'], 'me-2' );
                                    ?>
                                    <a href="<?php echo esc_url( $resource['resource_url'] ); ?>" class="ptd-module-resource-item" download>
                                        <?php echo $resource_icon.$resource_name; ?>
                                    </a>
                                    */
                                }
                            }

                            if( $use_accordions ){ ?>
                                </div><!-- .accordion-body -->
                                </div><!-- .accordion-collapse -->
                                </div><!-- .accordion-item -->
                            <?php }

                        }

                        if( $use_accordions ){ ?>
                            </div><!-- .accordion -->
                        <?php } ?>

                        <div class="ptd-my-acct-wrap text-end">
                            <a href="/my-account">My account</a>
                        </div>

                    </div>
                </div>

            </div>

            <?php // video column ?>
            <div class="col-lg-6 col-12 ptd-video-column p-3" id="videoColumn">
                <h4 id="ptd-curr-vid-title" class="ptd-curr-vid-title">Select a video from the menu <span class="d-inline d-lg-none">above </span>to begin</h4>
                <div class="video-wrapper">
                    <div class="ptd-video-container" id="ptd-video-container">
                        <div class="ptd-video-loading" id="ptd-video-loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php // chat column ?>
            <div class="col-lg-3 col-12 ptd-chat-column" id="ptd-chat-column">
                <div class="chat-wrapper" id="chatWrapper">
                    <?php // Header that shows when minimized ?>
                    <div class="chat-header-only">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Chat messages</h6>
                            <small><a id="ptd-chat-shower" href="javascript:void(0);" class="text-decoration-none">show</a></small>
                        </div>
                    </div>
                    <div class="ptd-chat-messages">
                        <div class="row">
                            <div class="col">
                                <h6 class="mb-3">Chat messages</h6>
                            </div>
                            <div class="col text-end small">
                                <a id="ptd-chat-hider" href="javascript:void(0);">hide</a>
                            </div>
                        </div>
                        <div id="ptd-chat-messages">
                            <!-- Chat messages will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- .row -->

    </div><!-- .training-container -->

    <!-- Mobile overlay -->
    <div class="ptd-mobile-overlay d-lg-none" id="ptd-mobile-overlay"></div>

    <?php
    // set up the first video if none have been watched
    if( $viewing_data['last_viewed_section'] == 0 ){
        $vid_section_to_use = $first_video_section;
    }else{
        $vid_section_to_use = $viewing_data['last_viewed_section'];
    }
    ?>

    <input type="hidden" id="ptd-training-data" 
        data-course-title="<?php echo esc_attr($course['post_title']); ?>"
        data-last-watched-video="<?php echo esc_attr($vid_section_to_use); ?>"
        data-last-position="<?php echo esc_attr($viewing_data['last_viewed_playhead']); ?>"
        data-user-id="<?php echo esc_attr($user->ID); ?>"
        data-training-id="<?php echo esc_attr($training_id); ?>"
        data-video-progress="<?php echo esc_attr( json_encode( $video_progress_data ) ); ?>">

<?php

endwhile; // end of the loop.
get_footer();
