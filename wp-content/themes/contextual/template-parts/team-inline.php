<?php
/**
 * The content of the team page - inline option selected
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php
    if(has_shortcode($post->post_content, 'section')){
        the_content();
    }else{
        echo do_shortcode('[section xclass="sml-padd-top no-padd-bot"]'.apply_filters('the_content',get_the_content()).'[/section]');
    }
    $team_dets = wms_team_dets_get(get_the_ID());
    ?>
    <div id="" class="wms-section-std wms-team-section wms-team-inlines no-padd-top">
        <div class="container">
            <?php foreach ($team_dets['members'] as $member) { ?>
                <div class="row team-member">
                    <div class="col-12 col-md-4 col-lg-3">
                        <div class="team-member-photo wms-bg-img" <?php
                            if($member['photo'] <> ''){ ?>
                                style="background-image:url(<?php echo wms_section_image_url($member['photo'], 'post-thumb'); ?>);"
                            <?php }
                        ?>></div>
                    </div>
                    <div class="col-12 col-md-8 col-lg-9">
                        <h3 class="team-member-name"><?php echo $member['membername']; ?></h3>
                        <h5 class="team-member-role"><?php echo $member['role']; ?></h5>
                        <div class="team-member-bio"><?php echo nl2br($member['bio']); ?></div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php echo wms_get_the_signature(); ?>
</article><!-- #post-<?php the_ID(); ?> -->
