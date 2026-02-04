<?php
/**
 * Template Name: Resume Registration
 */

get_header();

global $wpdb;
$redirect_home = home_url();

// Get token from URL
$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : null;

if (!$token) {
    wp_redirect($redirect_home);
    exit;
}

// Look up token
$table = $wpdb->prefix . 'abandoned_registrations';
$record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE token = %s", $token));

if (!$record) {
    wp_redirect($redirect_home);
    exit;
}

$data = json_decode($record->data, true);
$token_user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$logged_in_user_id = get_current_user_id();

if ($logged_in_user_id && $logged_in_user_id !== $token_user_id) {
    // Logged in as someone else
    wp_redirect( get_permalink( $data['training_id'] ) );
    exit;
}

// If not logged in or matching user, proceed to resume registration
$registration_url = home_url('/registration');

?>

<form id="resumeForm" action="<?php echo esc_url($registration_url); ?>" method="POST" style="display:none;">
    <input type="hidden" name="training-type" value="<?php echo $data['training_type']; ?>">
    <input type="hidden" name="workshop-id" value="<?php echo esc_attr($data['training_id']); ?>">
    <input type="hidden" name="eventID" value="<?php echo esc_attr($data['event_id']); ?>">
    <input type="hidden" name="num-events" value="<?php echo esc_attr($data['num_events']); ?>">
    <input type="hidden" name="num-free" value="<?php echo esc_attr($data['num_free']); ?>">
    <input type="hidden" name="currency" value="<?php echo esc_attr($data['currency']); ?>">
    <input type="hidden" name="raw-price" value="<?php echo esc_attr($data['price']); ?>">
    <input type="hidden" name="user-timezone" value="<?php echo esc_attr($data['timezone']); ?>">
    <input type="hidden" name="user-prettytime" value="<?php echo esc_attr($data['prettytime']); ?>">
    <input type="hidden" name="student" value="<?php echo esc_attr($data['student']); ?>">
    <input type="hidden" name="student-price" value="<?php echo esc_attr($data['student_price']); ?>">
</form>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("resumeForm").submit();
    });
</script>

<?php get_footer(); ?>
