/**
 * Mailster Form JavaScript with Bootstrap Validation
 * Save this content as mailster-form.js in your theme's js directory
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Handle form submission
    $('.mailster-subscribe-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $messages = $form.find('.form-messages');
        
        // Clear previous messages
        $messages.empty();
        
        // Bootstrap form validation
        if (!this.checkValidity()) {
            $form.addClass('was-validated');
            return;
        }
        
        // Remove validation classes for clean submission
        $form.removeClass('was-validated');
        $form.find('.form-control').removeClass('is-valid is-invalid');
        
        // Add loading state
        $form.addClass('form-submitting');
        $button.prop('disabled', true);
        
        // Get form data
        var firstname = $form.find('input[name="firstname"]').val().trim();
        var lastname = $form.find('input[name="lastname"]').val().trim();
        var email = $form.find('input[name="email"]').val().trim();
        
        // Additional client-side validation
        if (!firstname || !lastname || !email) {
            showMessage('error', 'Please fill in all required fields.');
            resetForm();
            return;
        }
        
        if (!isValidEmail(email)) {
            showMessage('error', 'Please enter a valid email address.');
            $form.find('input[name="email"]').addClass('is-invalid');
            resetForm();
            return;
        }
        
        // Submit form via AJAX
        $.ajax({
            type: 'POST',
            url: mailster_ajax.ajax_url,
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    $form[0].reset(); // Reset form fields
                    $form.removeClass('was-validated');
                    $form.find('.form-control').removeClass('is-valid is-invalid');
                } else {
                    showMessage('error', response.data.message || 'An error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showMessage('error', 'Connection error. Please check your internet connection and try again.');
            },
            complete: function() {
                resetForm();
            }
        });
        
        function showMessage(type, message) {
            var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            var icon = type === 'success' ? '✓' : '⚠';
            
            $messages.html(
                '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                '<strong>' + icon + '</strong> ' + message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>'
            );
            
            // Scroll to message if needed
            $messages[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function resetForm() {
            $form.removeClass('form-submitting');
            $button.prop('disabled', false);
        }
        
        function isValidEmail(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    });
    
    // Real-time validation feedback (optional enhancement)
    $('.mailster-subscribe-form input').on('blur', function() {
        var $input = $(this);
        var $form = $input.closest('form');
        
        if ($form.hasClass('was-validated')) {
            if (this.checkValidity()) {
                $input.removeClass('is-invalid').addClass('is-valid');
            } else {
                $input.removeClass('is-valid').addClass('is-invalid');
            }
        }
    });
});
