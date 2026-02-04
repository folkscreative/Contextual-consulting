<?php
/**
 * Mailster eBook Code Manager
 * 
 * This class manages unique eBook codes for Mailster subscribers.
 * Place this file in your theme directory and require it in functions.php:
 * require_once get_template_directory() . '/mailster-ebook-code-manager.php';
 * 
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MailsterEbookCodeManager {
    
    private $table_name;
    private $db_version = '1.0.0';
    private $option_name = 'mailster_ebook_code_db_version';
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mailster_ebook_codes';
        
        // Hook into WordPress
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // Create/update database table
        $this->create_database_table();
        
        // Register Mailster hooks if plugin is active
        if (function_exists('mailster')) {
            add_action('mailster_add_tag', array($this, 'register_ebook_tag'));
        }
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Register AJAX handlers
        add_action('wp_ajax_get_allocated_codes', array($this, 'ajax_get_allocated_codes'));
    }
    
    /**
     * Create database table for unallocated codes
     */
    private function create_database_table() {
        global $wpdb;
        
        $installed_ver = get_option($this->option_name);
        
        if ($installed_ver != $this->db_version) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$this->table_name} (
                id int(11) NOT NULL AUTO_INCREMENT,
                code varchar(50) NOT NULL,
                allocated tinyint(1) DEFAULT 0,
                subscriber_id int(11) DEFAULT NULL,
                allocated_date datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY code (code),
                KEY allocated (allocated)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            update_option($this->option_name, $this->db_version);
        }
    }
    
    /**
     * Register the eBook code tag for Mailster
     */
    public function register_ebook_tag() {
        if (!function_exists('mailster_add_tag')) return;
        
        mailster_add_tag('ebook_code', array($this, 'replace_ebook_tag'));
    }
    
    /**
     * Replace the eBook code tag with actual code
     */
    public function replace_ebook_tag($option, $fallback, $campaignID = NULL, $subscriberID = NULL) {
        // If no subscriber ID, return fallback
        if (!$subscriberID) {
            return !empty($fallback) ? $fallback : '[CODE_ERROR]';
        }
        
        // Get subscriber from Mailster
        $subscriber = mailster('subscribers')->get($subscriberID);
        if (!$subscriber) {
            return !empty($fallback) ? $fallback : '[CODE_ERROR]';
        }
        
        // Check if subscriber already has a code stored in our custom table
        global $wpdb;
        $existing_code = $wpdb->get_var($wpdb->prepare(
            "SELECT code FROM {$this->table_name} WHERE subscriber_id = %d AND allocated = 1",
            $subscriberID
        ));
        
        if (!empty($existing_code)) {
            return $existing_code;
        }
        
        // Allocate a new code
        $new_code = $this->allocate_code_to_subscriber($subscriberID);
        
        if ($new_code) {
            return $new_code;
        }
        
        return !empty($fallback) ? $fallback : '[NO_CODES_AVAILABLE]';
    }
    
    /**
     * Allocate a code to a subscriber
     */
    private function allocate_code_to_subscriber($subscriber_id) {
        global $wpdb;
        
        // Get an unallocated code
        $code = $wpdb->get_var("
            SELECT code 
            FROM {$this->table_name} 
            WHERE allocated = 0 
            LIMIT 1
        ");
        
        if ($code) {
            // Mark as allocated
            $wpdb->update(
                $this->table_name,
                array(
                    'allocated' => 1,
                    'subscriber_id' => $subscriber_id,
                    'allocated_date' => current_time('mysql')
                ),
                array('code' => $code)
            );
            
            return $code;
        }
        
        return false;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'eBook Code Manager',
            'eBook Codes',
            'manage_options',
            'ebook-code-manager',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Handle admin form submissions
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) return;
        
        // Handle CSV upload
        if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
            check_admin_referer('ebook_code_upload');
            $this->handle_csv_upload($_FILES['csv_file']);
        }
        
        // Handle text area submission
        if (isset($_POST['add_codes_text'])) {
            check_admin_referer('ebook_code_text');
            $this->handle_text_codes($_POST['codes_text']);
        }
        
        // Handle single code deletion
        if (isset($_GET['delete_code']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_code')) {
                $this->delete_code($_GET['delete_code']);
            }
        }
        
        // Handle clear all codes
        if (isset($_POST['clear_all_codes'])) {
            check_admin_referer('ebook_code_clear');
            $this->clear_all_codes();
        }
    }
    
    /**
     * Handle CSV file upload
     */
    private function handle_csv_upload($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            add_settings_error('ebook_codes', 'upload_error', 'Error uploading file.', 'error');
            return;
        }
        
        $content = file_get_contents($file['tmp_name']);
        $lines = explode("\n", $content);
        $added = 0;
        
        foreach ($lines as $line) {
            $code = trim($line);
            if (!empty($code)) {
                if ($this->add_code($code)) {
                    $added++;
                }
            }
        }
        
        add_settings_error('ebook_codes', 'upload_success', "$added codes added successfully.", 'success');
    }
    
    /**
     * Handle text area code submission
     */
    private function handle_text_codes($text) {
        $lines = explode("\n", $text);
        $added = 0;
        
        foreach ($lines as $line) {
            $code = trim($line);
            if (!empty($code)) {
                if ($this->add_code($code)) {
                    $added++;
                }
            }
        }
        
        add_settings_error('ebook_codes', 'text_success', "$added codes added successfully.", 'success');
    }
    
    /**
     * Add a single code to the database
     */
    private function add_code($code) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array('code' => $code),
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a single code
     */
    private function delete_code($code_id) {
        global $wpdb;
        
        $wpdb->delete(
            $this->table_name,
            array('id' => intval($code_id))
        );
        
        add_settings_error('ebook_codes', 'delete_success', 'Code deleted successfully.', 'success');
    }
    
    /**
     * Clear all codes from database
     */
    private function clear_all_codes() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        add_settings_error('ebook_codes', 'clear_success', 'All codes cleared successfully.', 'success');
    }
    
    /**
     * Get statistics
     */
    private function get_stats() {
        global $wpdb;
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $allocated = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE allocated = 1");
        $available = $total - $allocated;
        
        return array(
            'total' => $total,
            'allocated' => $allocated,
            'available' => $available
        );
    }
    
    /**
     * Get allocated codes with subscriber info
     */
    private function get_allocated_codes() {
        global $wpdb;
        
        // First, let's get all allocated codes
        $codes = $wpdb->get_results("
            SELECT * FROM {$this->table_name}
            WHERE allocated = 1
            ORDER BY allocated_date DESC
        ");
        
        // Now enhance with subscriber info if available
        foreach ($codes as &$code) {
            if ($code->subscriber_id) {
                $subscriber = mailster('subscribers')->get($code->subscriber_id);
                if ($subscriber) {
                    $code->email = $subscriber->email;
                    $code->firstname = $subscriber->firstname;
                    $code->lastname = $subscriber->lastname;
                } else {
                    $code->email = 'Subscriber ID: ' . $code->subscriber_id;
                    $code->firstname = '';
                    $code->lastname = '';
                }
            }
        }
        
        return $codes;
    }

    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        $stats = $this->get_stats();
        $allocated_codes = $this->get_allocated_codes();
        
        ?>
        <div class="wrap">
            <h1>eBook Code Manager for Mailster</h1>
            
            <?php settings_errors('ebook_codes'); ?>
            
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>📚 Instructions</h2>
                <p><strong>How this system works:</strong></p>
                <ol>
                    <li><strong>Upload Codes:</strong> Add your unique eBook codes using either CSV upload or copy/paste method below.</li>
                    <li><strong>Use in Newsletters:</strong> In your Mailster campaigns, use the tag <code>{ebook_code}</code> where you want the code to appear.</li>
                    <li><strong>Automatic Assignment:</strong> When an email is sent, each subscriber gets a unique code that's permanently assigned to them.</li>
                    <li><strong>Reuse:</strong> If the same subscriber receives another email with the tag, they'll get the same code they were originally assigned.</li>
                </ol>
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>✅ Codes must be unique - duplicates will be automatically ignored</li>
                    <li>✅ Once a code is assigned to a subscriber, it cannot be reassigned</li>
                    <li>⚠️ Make sure you have enough codes for all your subscribers</li>
                    <li>📊 Current Status: <strong><?php echo $stats['available']; ?></strong> codes available, <strong><?php echo $stats['allocated']; ?></strong> allocated</li>
                </ul>
            </div>
            
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>➕ Add New Codes</h2>
                
                <div style="display: flex; gap: 20px;">
                    <!-- CSV Upload -->
                    <div style="flex: 1;">
                        <h3>Option 1: Upload CSV File</h3>
                        <p><em>Upload a CSV file with one code per line</em></p>
                        <form method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('ebook_code_upload'); ?>
                            <input type="file" name="csv_file" accept=".csv,.txt" required />
                            <p>
                                <input type="submit" name="upload_csv" class="button button-primary" value="Upload CSV" />
                            </p>
                        </form>
                    </div>
                    
                    <!-- Text Area -->
                    <div style="flex: 1;">
                        <h3>Option 2: Paste Codes</h3>
                        <p><em>Paste codes below (one per line)</em></p>
                        <form method="post">
                            <?php wp_nonce_field('ebook_code_text'); ?>
                            <textarea name="codes_text" rows="6" style="width: 100%;" placeholder="CODE123ABC&#10;CODE456DEF&#10;CODE789GHI"></textarea>
                            <p>
                                <input type="submit" name="add_codes_text" class="button button-primary" value="Add Codes" />
                            </p>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>📊 Code Statistics</h2>
                <table class="widefat">
                    <tr>
                        <td><strong>Total Codes:</strong></td>
                        <td><?php echo $stats['total']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Available Codes:</strong></td>
                        <td style="color: <?php echo $stats['available'] > 0 ? 'green' : 'red'; ?>;">
                            <?php echo $stats['available']; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Allocated Codes:</strong></td>
                        <td><?php echo $stats['allocated']; ?></td>
                    </tr>
                </table>
                
                <?php if ($stats['total'] > 0): ?>
                <form method="post" style="margin-top: 10px;" onsubmit="return confirm('Are you sure? This will delete ALL codes (both allocated and unallocated)!');">
                    <?php wp_nonce_field('ebook_code_clear'); ?>
                    <input type="submit" name="clear_all_codes" class="button button-secondary" value="Clear All Codes" />
                </form>
                <?php endif; ?>
            </div>
            
            <div class="card" style="max-width: 100%; margin-top: 20px;">
                <h2>👥 Allocated Codes</h2>
                <?php if (empty($allocated_codes)): ?>
                    <p><em>No codes have been allocated yet.</em></p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Subscriber Email</th>
                                <th>Name</th>
                                <th>Allocated Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocated_codes as $code): ?>
                            <tr>
                                <td><code><?php echo esc_html($code->code); ?></code></td>
                                <td><?php echo esc_html($code->email ?: 'Unknown'); ?></td>
                                <td><?php echo esc_html(trim($code->firstname . ' ' . $code->lastname) ?: '-'); ?></td>
                                <td><?php echo esc_html($code->allocated_date); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('tools.php?page=ebook-code-manager&delete_code=' . $code->id), 'delete_code'); ?>" 
                                       onclick="return confirm('Delete this code? This cannot be undone.');"
                                       class="button button-small">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card" style="max-width: 100%; margin-top: 20px; background: #f0f8ff;">
                <h2>ℹ️ Testing Your Setup</h2>
                <p>To test if everything is working:</p>
                <ol>
                    <li>Add some test codes using the form above</li>
                    <li>Create a test Mailster campaign</li>
                    <li>Add <code>{ebook_code}</code> where you want the code to appear</li>
                    <li>Send a test email to yourself</li>
                    <li>Check this page to see if the code was allocated</li>
                </ol>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new MailsterEbookCodeManager();