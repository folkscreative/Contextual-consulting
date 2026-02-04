<?php
/**
 * Quiz User Results Admin Page
 * Displays quiz results for users searched by email address
 * Includes detailed answer review via AJAX
 */

// Add the admin menu item
add_action('admin_menu', 'quiz_add_user_results_page');

function quiz_add_user_results_page() {
    add_submenu_page(
        'edit.php?post_type=quiz',
        'User Quiz Results',
        'User Results',
        'manage_options',
        'quiz-user-results',
        'quiz_user_results_page_callback'
    );
}

// Main page callback
function quiz_user_results_page_callback() {
    ?>
    <div class="wrap">
        <h1>User Quiz Results</h1>
        
        <!-- Search Form -->
        <div class="quiz-search-form">
            <h2>Search by Email Address</h2>
            <form method="post" action="" id="user-search-form">
                <?php wp_nonce_field('quiz_user_search', 'quiz_search_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_email">User Email:</label>
                        </th>
                        <td>
                            <input type="email" 
                                   name="user_email" 
                                   id="user_email" 
                                   class="regular-text" 
                                   value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>" 
                                   required>
                            <?php submit_button('Search Results', 'primary', 'submit', false); ?>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <div id="quiz-results-container">
            <?php
            // Process form submission
            if (isset($_POST['submit']) && check_admin_referer('quiz_user_search', 'quiz_search_nonce')) {
                $user_email = sanitize_email($_POST['user_email']);
                
                if (!empty($user_email)) {
                    quiz_display_user_results($user_email);
                } else {
                    echo '<div class="notice notice-error"><p>Please enter a valid email address.</p></div>';
                }
            }
            ?>
        </div>
    </div>
    <?php
}

// Display quiz results for a specific user
function quiz_display_user_results($user_email) {
    $user = get_user_by('email', $user_email);
    
    if (!$user) {
        echo '<div class="notice notice-error"><p>No user found with email address: <strong>' . esc_html($user_email) . '</strong></p></div>';
        return;
    }
    
    echo '<div class="quiz-results-section">';
    echo '<h2>Quiz Results for: ' . esc_html($user->display_name) . '</h2>';
    echo '<p class="user-info">Email: <strong>' . esc_html($user_email) . '</strong> | User ID: <strong>' . $user->ID . '</strong></p>';
    
    // Get all user meta keys that start with _quiz_
    $all_meta = get_user_meta($user->ID);
    $quiz_meta_keys = array();
    
    foreach ($all_meta as $key => $value) {
        if (strpos($key, '_quiz_') === 0) {
            // Extract the training_id from the key
            $training_id = str_replace('_quiz_', '', $key);
            if (is_numeric($training_id)) {
                $quiz_meta_keys[] = $training_id;
            }
        }
    }
    
    if (empty($quiz_meta_keys)) {
        echo '<div class="notice notice-warning"><p>No quiz results found for this user.</p></div>';
        echo '</div>';
        return;
    }
    
    // Display results for each training course
    echo '<table class="wp-list-table widefat fixed striped quiz-results-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="col-course">Course / Training</th>';
    echo '<th class="col-attempts">Attempts</th>';
    echo '<th class="col-scores">Scores</th>';
    echo '<th class="col-status">Status</th>';
    echo '<th class="col-actions">Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($quiz_meta_keys as $training_id) {
        $results = get_user_meta($user->ID, '_quiz_' . $training_id, false);
        
        if (empty($results)) {
            continue;
        }
        
        // Get course title
        $course_title = get_the_title($training_id);
        if (empty($course_title)) {
            $course_title = 'Unknown Course';
        }
        
        // Sort results by date
        usort($results, function($a, $b) {
            return strcmp($a['when'], $b['when']);
        });
        
        $num_attempts = count($results);
        $best_score = 0;
        $passed = false;
        $scores_html = '';
        
        foreach ($results as $index => $result) {
            $attempt_num = $index + 1;
            $score = isset($result['score']) ? $result['score'] : 0;
            $date = isset($result['when']) ? DateTime::createFromFormat('Y-m-d H:i:s', $result['when']) : null;
            
            if ($score > $best_score) {
                $best_score = $score;
            }
            
            if ($score >= 70) {
                $passed = true;
                $score_class = 'score-pass';
            } else {
                $score_class = 'score-fail';
            }
            
            $scores_html .= '<div class="attempt-score">';
            $scores_html .= '<strong>Attempt ' . $attempt_num . ':</strong> ';
            $scores_html .= '<span class="' . $score_class . '">' . $score . '%</span>';
            if ($date) {
                $scores_html .= ' <span class="attempt-date">(' . $date->format('M d, Y') . ')</span>';
            }
            $scores_html .= ' <button type="button" class="button button-small view-details-btn" 
                                data-user-id="' . $user->ID . '" 
                                data-training-id="' . $training_id . '" 
                                data-attempt="' . $attempt_num . '" 
                                data-user-quiz-id="' . esc_attr($result['user_quiz_id']) . '">View Details</button>';
            $scores_html .= '</div>';
        }
        
        // Overall status
        if ($passed) {
            $status_html = '<span class="status-badge status-pass">PASSED</span>';
            $status_html .= '<div class="status-info">Best Score: ' . $best_score . '%</div>';
        } else {
            $status_html = '<span class="status-badge status-fail">NOT PASSED</span>';
            if ($num_attempts < 3) {
                $remaining = 3 - $num_attempts;
                $status_html .= '<div class="status-info">' . $remaining . ' attempt' . ($remaining > 1 ? 's' : '') . ' remaining</div>';
            } else {
                $status_html .= '<div class="status-info">No attempts remaining</div>';
            }
        }
        
        echo '<tr class="quiz-result-row">';
        echo '<td class="col-course"><strong>' . esc_html($course_title) . '</strong><br><span class="course-id">ID: ' . $training_id . '</span></td>';
        echo '<td class="col-attempts"><strong>' . $num_attempts . '</strong> of 3</td>';
        echo '<td class="col-scores">' . $scores_html . '</td>';
        echo '<td class="col-status">' . $status_html . '</td>';
        echo '<td class="col-actions">';
        echo '</td>';
        echo '</tr>';
        
        // Hidden row for detailed results (populated via AJAX)
        echo '<tr class="details-row details-row-' . $training_id . '-all" style="display:none;">';
        echo '<td colspan="5" class="details-cell"><div class="loading-spinner">Loading...</div></td>';
        echo '</tr>';
        
        foreach ($results as $index => $result) {
            $attempt_num = $index + 1;
            echo '<tr class="details-row details-row-' . $training_id . '-' . $attempt_num . '" style="display:none;">';
            echo '<td colspan="5" class="details-cell"><div class="loading-spinner">Loading...</div></td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// AJAX handler to get detailed quiz answers
add_action('wp_ajax_get_quiz_details', 'quiz_get_detailed_results');

function quiz_get_detailed_results() {
    // Check nonce
    check_ajax_referer('quiz_details_nonce', 'nonce');
    
    // Get parameters
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $training_id = isset($_POST['training_id']) ? intval($_POST['training_id']) : 0;
    $attempt = isset($_POST['attempt']) ? intval($_POST['attempt']) : 0;
    
    if ($user_id === 0 || $training_id === 0) {
        wp_send_json_error('Invalid parameters');
        return;
    }
    
    // Get quiz results
    $results = get_user_meta($user_id, '_quiz_' . $training_id, false);
    
    if (empty($results)) {
        wp_send_json_error('No results found');
        return;
    }
    
    // Sort by date
    usort($results, function($a, $b) {
        return strcmp($a['when'], $b['when']);
    });
    
    // Get the specific attempt (1-indexed)
    if ($attempt > 0 && isset($results[$attempt - 1])) {
        $result = $results[$attempt - 1];
    } else {
        wp_send_json_error('Attempt not found');
        return;
    }
    
    // Get quiz questions
    $quiz_id = cc_quizzes_quiz_id($training_id);
    if (!$quiz_id) {
        wp_send_json_error('Quiz not found');
        return;
    }
    
    $questions = get_post_meta($quiz_id, '_questions', true);
    if (empty($questions)) {
        wp_send_json_error('No questions found');
        return;
    }
    
    // Build detailed HTML
    $html = '<div class="quiz-details-content">';
    $html .= '<div class="details-header">';
    $html .= '<h3>Detailed Results - Attempt ' . $attempt . '</h3>';
    
    $user = get_user_by('ID', $user_id);
    $course_title = get_the_title($training_id);
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $result['when']);
    
    $html .= '<div class="details-meta">';
    $html .= '<p><strong>User:</strong> ' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</p>';
    $html .= '<p><strong>Course:</strong> ' . esc_html($course_title) . ' (ID: ' . $training_id . ')</p>';
    $html .= '<p><strong>Date:</strong> ' . $date->format('F j, Y \a\t g:i A') . '</p>';
    $html .= '<p><strong>Score:</strong> ' . $result['correct'] . ' out of ' . count($questions) . ' (' . $result['score'] . '%)</p>';
    $html .= '<p><strong>Result:</strong> <span class="' . ($result['score'] >= 70 ? 'text-pass' : 'text-fail') . '">' . ($result['score'] >= 70 ? 'PASSED' : 'FAILED') . '</span> (Pass mark: 70%)</p>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Display each question and answer
    $html .= '<div class="questions-review">';
    
    foreach ($questions as $qnum => $question) {
        $user_answer = isset($result['answers'][$qnum]) ? $result['answers'][$qnum] : '-';
        $correct_answer = $question['correct'];
        $is_correct = ($user_answer === $correct_answer);
        
        $html .= '<div class="question-block ' . ($is_correct ? 'correct-answer' : 'incorrect-answer') . '">';
        $html .= '<div class="question-header">';
        $html .= '<span class="question-number">Question ' . $qnum . '</span>';
        $html .= '<span class="answer-status ' . ($is_correct ? 'status-correct' : 'status-incorrect') . '">';
        $html .= $is_correct ? '✓ Correct' : '✗ Incorrect';
        $html .= '</span>';
        $html .= '</div>';
        
        $html .= '<div class="question-text">' . esc_html($question['question']) . '</div>';
        
        $html .= '<div class="answers-list">';
        
        $answer_options = array('a', 'b', 'c', 'd', 'e');
        foreach ($answer_options as $option) {
            if (!empty($question['answer' . $option])) {
                $classes = array('answer-option');
                
                // Highlight correct answer
                if ($option === $correct_answer) {
                    $classes[] = 'correct-option';
                }
                
                // Highlight user's answer
                if ($option === $user_answer) {
                    $classes[] = 'user-option';
                    if ($option !== $correct_answer) {
                        $classes[] = 'user-incorrect';
                    }
                }
                
                $html .= '<div class="' . implode(' ', $classes) . '">';
                $html .= '<span class="option-letter">' . strtoupper($option) . '</span>';
                $html .= '<span class="option-text">' . esc_html($question['answer' . $option]) . '</span>';
                
                if ($option === $correct_answer) {
                    $html .= '<span class="option-badge correct-badge">Correct Answer</span>';
                }
                if ($option === $user_answer && $option !== $correct_answer) {
                    $html .= '<span class="option-badge user-badge">Your Answer</span>';
                }
                if ($option === $user_answer && $option === $correct_answer) {
                    $html .= '<span class="option-badge both-badge">Your Answer (Correct)</span>';
                }
                
                $html .= '</div>';
            }
        }
        
        $html .= '</div>'; // Close answers-list
        $html .= '</div>'; // Close question-block
    }
    
    $html .= '</div>'; // Close questions-review
    $html .= '</div>'; // Close quiz-details-content
    
    wp_send_json_success($html);
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'quiz_results_admin_scripts');

function quiz_results_admin_scripts($hook) {
    // Only load on our specific page
    if ($hook !== 'quiz_page_quiz-user-results') {
        return;
    }
    
    // Enqueue the JavaScript
    wp_enqueue_script(
        'quiz-results-admin',
        get_template_directory_uri() . '/js/quiz-results-admin.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Pass AJAX URL and nonce to JavaScript
    wp_localize_script('quiz-results-admin', 'quizResultsAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('quiz_details_nonce')
    ));
    
    // Enqueue the CSS
    wp_enqueue_style(
        'quiz-results-admin',
        get_template_directory_uri() . '/css/quiz-results-admin.css',
        array(),
        '1.0.0'
    );
}