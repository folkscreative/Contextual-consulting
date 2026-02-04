<?php
/*
Plugin Name: Therapy Bot
Description: A more complex therapy bot for WordPress.
Version: 2.0
Author: Your Name
*/

// Create a shortcode to display the therapy bot
function therapy_bot_shortcode() {
    ob_start();
    ?>
    <div id="therapy-bot-container">
        <h3>Welcome to the JoeBot 9000 ACT therapist </h3>
        <p>I am an ACT therapist. How can I assist you today?</p>
        <input type="text" id="user-input" placeholder="Type your message here...">
        <button id="submit-btn">Submit</button>
        <ul id="chat-log"></ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('therapy_bot', 'therapy_bot_shortcode');

// Enqueue the necessary scripts
function therapy_bot_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('therapy-bot-script', plugins_url('/js/therapy-bot.js', __FILE__), array('jquery'), '2.0', true);
}
add_action('wp_enqueue_scripts', 'therapy_bot_scripts');

// Process user input and generate bot response
function therapy_bot_process_input() {
    if (isset($_POST['user_input'])) {
        $user_input = sanitize_text_field($_POST['user_input']);
        $bot_response = generate_bot_response($user_input);
        echo $bot_response;
        exit;
    }
}
add_action('wp_ajax_therapy_bot_process_input', 'therapy_bot_process_input');
add_action('wp_ajax_nopriv_therapy_bot_process_input', 'therapy_bot_process_input');

// Generate a response from the therapy bot
function generate_bot_response($user_input) {
    $bot_response = "I understand. How does that make you feel?";

    // Check user input for specific patterns and generate appropriate responses
    if (preg_match('/\b(value|values)\b/i', $user_input)) {
        $bot_response = "Values are an important aspect of ACT therapy. What values are important to you?";
    } elseif (preg_match('/\b(mindful|mindfulness)\b/i', $user_input)) {
        $bot_response = "Mindfulness is a key component of ACT therapy. How can you bring more mindfulness into your daily life?";
    } elseif (preg_match('/\b(thought|thinking)\b/i', $user_input)) {
        $bot_response = "When you have a thought, it can be helpful to practice defusion techniques, like stepping back and observing the thought without getting caught up in it. How might you approach this thought with a sense of curiosity?";
    } elseif (preg_match('/\b(emotion|feeling)\b/i', $user_input)) {
        $bot_response = "When you experience an emotion, it can be helpful to practice acceptance and making room for the feeling. How can you create space for the emotion without trying to push it away?";
    } elseif (preg_match('/\b(story|narrative)\b/i', $user_input)) {
        $bot_response = "In ACT therapy, we often explore the idea of self-as-context, where we hold our stories and narratives lightly. How might you approach your self-narrative with a sense of curiosity and openness?";
    } elseif (preg_match('/\b(action|act|behaviour)\b/i', $user_input)) {
        $bot_response = "Taking committed actions aligned with your values is an essential aspect of ACT therapy. What actions can you take to move closer to a life that reflects your values?";
    } elseif (preg_match('/\bhello\b/i', $user_input)) {
        $bot_response = "Hello, how are you today?";
    } elseif (preg_match('/\b(happy|joyful|excited)\b/i', $user_input)) {
        $bot_response = "That's wonderful! What is bringing you joy or excitement?";
    } elseif (preg_match('/\b(sad|down|depressed)\b/i', $user_input)) {
        $bot_response = "I'm sorry to hear that. Would you like to talk about what's been bothering you?";
    } elseif (preg_match('/\b(help|support)\b/i', $user_input)) {
        $bot_response = "I'm here to provide support. How can I assist you today?";
    }

    return $bot_response;
}