<?php
/**
 * Portal Contract Banner for My Account Pages
 * Displays contract status warnings for CNWL and NLFT users
 */

/**
 * Display portal contract status banner on My Account pages
 */
function cc_portal_contract_banner($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Only show for portal users
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    if (empty($portal_user)) {
        return '';
    }
    
    // Get organisation contract details
    $contract = get_organisation_contract($portal_user);
    
    // If no contract found or no end date, don't show banner
    if (!$contract || empty($contract->contract_end)) {
        return '';
    }
    
    // Get contract type (default to 'unlimited' for backwards compatibility)
    $contract_type = !empty($contract->contract_type) ? $contract->contract_type : 'unlimited';
    
    // Calculate dates
    $org_end_timestamp = strtotime($contract->contract_end);
    $today = strtotime('today');
    $one_month_future = strtotime('+1 month', $today);
    $two_weeks_future = strtotime('+2 weeks', $today);
    
    // Determine status
    $status = 'ok';
    if ($org_end_timestamp < $today) {
        $status = 'expired';
    } elseif ($org_end_timestamp < $two_weeks_future) {
        $status = 'critical';
    } elseif ($org_end_timestamp < $one_month_future) {
        $status = 'warning';
    }
    
    // Only show banner if there's a status to report
    if ($status === 'ok') {
        return '';
    }
    
    ob_start();
    ?>
    
    <div class="cc-portal-contract-banner mb-4">
        <?php if ($status === 'expired'): ?>
            <!-- Contract Expired -->
            <div class="alert alert-danger d-flex align-items-center dark-bg" role="alert">
                <i class="fa-solid fa-exclamation-circle fa-2x me-3 text-white"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2 text-white">Organisation contract expired</h5>
                    <?php if ($contract_type === 'unlimited'): ?>
                        <p class="mb-2 text-white">
                            Your organisation's contract expired on <?php echo date('j F Y', $org_end_timestamp); ?>. 
                            You no longer have access to register for new training or access on-demand content.
                        </p>
                    <?php else: // fixed_number ?>
                        <p class="mb-2 text-white">
                            Your organisation's contract expired on <?php echo date('j F Y', $org_end_timestamp); ?>. 
                            You can still access training you have already registered for, but you cannot register for any new training courses.
                        </p>
                    <?php endif; ?>
                    <p class="mb-0 text-white">
                        <strong>Please contact your organisation's administrator to arrange contract renewal.</strong>
                    </p>
                </div>
            </div>
            
        <?php elseif ($status === 'critical'): ?>
            <!-- Expiring Within 2 Weeks -->
            <div class="alert alert-warning d-flex align-items-center dark-bg" role="alert">
                <i class="fa-solid fa-exclamation-triangle fa-2x me-3 text-white"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2 text-white">Contract expiring soon</h5>
                    <?php if ($contract_type === 'unlimited'): ?>
                        <p class="mb-2 text-white">
                            Your organisation's contract expires on <strong><?php echo date('j F Y', $org_end_timestamp); ?></strong>. 
                            After this date, you will lose access to all training content and will be unable to register for new courses.
                        </p>
                        <p class="mb-0 text-white">
                            Please encourage your organisation's administrator to renew your contract as soon as possible to avoid any interruption to your training access.
                        </p>
                    <?php else: // fixed_number ?>
                        <p class="mb-2 text-white">
                            Your organisation's contract expires on <strong><?php echo date('j F Y', $org_end_timestamp); ?></strong>. 
                            After this date, you will be unable to register for new courses, but you will retain access to any training you have already registered for.
                        </p>
                        <p class="mb-0 text-white">
                            Please encourage your organisation's administrator to renew your contract soon if you wish to register for additional training courses.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($status === 'warning'): ?>
            <!-- Expiring Within 1 Month -->
            <div class="alert alert-info d-flex align-items-center dark-bg" role="alert">
                <i class="fa-solid fa-clock fa-2x me-3 text-white"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2 text-white">Contract expiring</h5>
                    <?php if ($contract_type === 'unlimited'): ?>
                        <p class="mb-2 text-white">
                            Your organisation's contract expires on <?php echo date('j F Y', $org_end_timestamp); ?>. 
                            To ensure uninterrupted access to training, please remind your organisation's administrator about the upcoming renewal.
                        </p>
                        <p class="mb-0 text-white small">
                            Early renewal will prevent any gap in your training access.
                        </p>
                    <?php else: // fixed_number ?>
                        <p class="mb-2 text-white">
                            Your organisation's contract expires on <?php echo date('j F Y', $org_end_timestamp); ?>. 
                            After this date, you will be unable to register for new training courses.
                        </p>
                        <p class="mb-0 text-white small">
                            You will retain access to any training you have already registered for. Contact your administrator about contract renewal to continue registering for new courses.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
}