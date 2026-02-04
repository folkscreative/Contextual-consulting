<?php
/*
 * Template Name: Global Podium Recordings
 * Description: the recording registration/viewing page
 */

// the recordingID is the id of the page (recording post) that sent the visitor here
// this will be used (in a mo) to lookup (post meta) the GP recording ID
$recordingID = 0;
if(isset($_GET['r']) && $_GET['r'] <> ''){
	$recordingID = absint($_GET['r']);
}
if($recordingID == 0){
	wp_redirect( home_url() );
	exit;
}

$this_page = get_post(get_the_ID());

get_header('nomenu'); ?>
	<div class="row">
		<div class="columns">
			<article id="post-<?php echo $this_page->ID; ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<h1 class="entry-title">
						<?php
						// get the title from the page that sent us here (the recordingID)
						echo get_the_title($recordingID); ?>
					</h1>
				</header><!-- .entry-header -->

				<div class="entry-content">
					<?php
					// the_content() does not work, it seems not to be able to pick up the post ID - probably due to the page templater??
					$content = $this_page->post_content;
				    $content = apply_filters( 'the_content', $content );
				    $content = str_replace( ']]>', ']]&gt;', $content );
				    echo $content;
					?>
				</div>
				<div>
					<?php
					$gp_recording_id = get_post_meta( $recordingID, 'registration_link_id', true );
					?>
					<script
					    id="podium-event"
					    data-event="<?php echo $gp_recording_id; ?>" data-event-url="https://app-4.globalpodium.com"
					    async src="https://app-4.globalpodium.com/js/embed.js">
					</script>
				</div>
			</article><!-- #post-## -->
		</div>
	</div>
<?php get_footer('simplified');
