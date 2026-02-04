<?php
/**
 * The content of the team page - modal option selected
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
    <div id="" class="wms-section-std wms-team-section wms-team-modals no-padd-top">
        <div class="container">
            <div class="row">
                <?php foreach ($team_dets['members'] as $member) { ?>
                    <div class="team-member col-12 col-sm-6 col-md-4">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#team-member-modal">
                            <div class="team-member-photo wms-bg-img"
                                <?php if($member['photo'] <> ''){ ?>
                                    style="background-image:url(<?php echo wms_section_image_url($member['photo'], 'post-thumb'); ?>);"
                                <?php } ?>
                            ></div>
                        </a>
                        <h3 class="team-member-name"><a href="#" data-bs-toggle="modal" data-bs-target="#team-member-modal"><?php echo $member['membername']; ?></a></h3>
                        <h5 class="team-member-role"><a href="#" data-bs-toggle="modal" data-bs-target="#team-member-modal"><?php echo $member['role']; ?></a></h5>
                        <div class="team-member-bio d-none"><?php echo nl2br($member['bio']); ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php echo wms_get_the_signature(); ?>
</article><!-- #post-<?php the_ID(); ?> -->

<div id="team-member-modal" class="team-member-modal modal fade" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="team-member-photo wms-bg-img"></div>
                        <h3 class="team-member-name"></h3>
                        <h5 class="team-member-role"></h5>
                    </div>
                    <div class="col-12 col-sm-6 col-md-8">
                        <div class="team-member-bio"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
