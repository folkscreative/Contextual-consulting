<?php
/**
 * Invoicing
 */


add_action('admin_menu', 'ccpa_register_invoice_domain_submenu');
add_action('admin_init', 'ccpa_create_invoice_domain_table_if_needed');
add_action('wp_ajax_ccpa_manage_invoice_domain', 'ccpa_manage_invoice_domain_ajax');

function ccpa_register_invoice_domain_submenu() {
    add_submenu_page(
        'ccpa_payments',
        'Manage Invoice Domains',
        'Invoice Domains',
        'edit_others_posts',
        'ccpa_invoice_domains',
        'ccpa_render_invoice_domains_page'
    );
}

function ccpa_create_invoice_domain_table_if_needed() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'invoice_domains';
    $version_option = 'ccpa_invoice_domains_db_version';
    $current_version = get_option($version_option);
    $expected_version = '1.0';

    if ($current_version !== $expected_version) {
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            domain VARCHAR(191) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY domain (domain)
        ) $charset_collate;";

        dbDelta($sql);
        update_option($version_option, $expected_version);
    }
}

function ccpa_render_invoice_domains_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'invoice_domains';
    $domains = $wpdb->get_results("SELECT * FROM $table_name ORDER BY domain ASC");
    $nonce = wp_create_nonce('ccpa_invoice_domain_nonce');
    ?>
    <div class="wrap">
        <h1>Manage Invoice Domains</h1>
        <p class="mb-4 text-muted">Anybody using an email that is from one of the following domains is pre-authorised to request that they pay by invoice.</p>

        <div id="ccpa_msg">
            <?php
            // Success/error messages placeholder
            if (!empty($_GET['ccpa_msg'])) {
                $msg = sanitize_text_field($_GET['ccpa_msg']);
                echo '<div class="alert alert-success">' . esc_html($msg) . '</div>';
            }
            ?>
        </div>

        <form id="add-domain-form" class="mb-4">
            <label for="">Add a new domain:</label>
            <div class="input-group">
                <input type="text" id="new-domain" class="form-control" placeholder="example.com" required>
                <button type="submit" class="button button-secondary">Add Domain</button>
            </div>
        </form>

        <table class="bs-table table-bordered" id="domain-table">
            <thead><tr><th>Domain (click to edit)</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($domains as $domain): ?>
                    <tr data-id="<?= esc_attr($domain->id) ?>">
                        <td class="editable-domain"><?= esc_html($domain->domain) ?></td>
                        <td>
                            <a href="javascript:void(0);" class="text-danger delete-domain" title="Delete"><i class="fa-solid fa-trash-can"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function ccpa_manage_invoice_domain_ajax() {
    check_ajax_referer('ccpa_invoice_domain_nonce', 'nonce');
    if (!current_user_can('edit_others_posts')) wp_send_json_error('Unauthorized');

    global $wpdb;
    $table = $wpdb->prefix . 'invoice_domains';

    $op = sanitize_text_field($_POST['op']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';

    if ($domain && !preg_match('/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/', $domain)) {
        wp_send_json_error('Invalid domain');
    }

    switch ($op) {
        case 'add':
            $result = $wpdb->insert($table, ['domain' => $domain]);
            if ($result === false) {
                wp_send_json_error('Could not add domain (may already exist).');
            }
            wp_send_json_success();
            break;

        case 'delete':
            $result = $wpdb->delete($table, ['id' => $id]);
            if ($result === false) {
                wp_send_json_error('Could not delete domain.');
            }
            wp_send_json_success();
            break;

        case 'edit':
            $result = $wpdb->update($table, ['domain' => $domain], ['id' => $id]);
            if ($result === false) {
                wp_send_json_error('Could not update domain.');
            }
            wp_send_json_success();
            break;

        default:
            wp_send_json_error('Unknown operation');
    }
}

// can this user pay using an invoice?
// $field can be 'id' or 'email', $value must be a user_id or an email address
// returns 'domain', 'user' or false (checks domain first)
function ccpa_invoice_payment_possible_for( $field, $value ){
    // return 'always';
    global $wpdb;
    $table_name = $wpdb->prefix . 'invoice_domains';
    $user = get_user_by( $field, $value );
    if( $user ){
        $email = $user->user_email;
        $domain = substr( $email, strpos( $email, '@' ) + 1 );
        // check domain
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM $table_name
                 WHERE domain = '%s'",
                $domain
            )
        );
        if( $result !== NULL ){
            return 'domain';
        }
        // check user
        $invoice_allowed = get_user_meta($user->ID, 'invoice_allowed', true);
        if( $invoice_allowed == 'yes' ){
            return 'user';
        }
    }
    return false;
}



