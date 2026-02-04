jQuery(document).ready(function($) {
    // Initialize the dynamic pricing functionality
    initializeTrainingGroupSelection();
    
    function initializeTrainingGroupSelection() {
    const $card = $('.tgsd-card');
    if ($card.length === 0) return;
    
    const seriesDiscount = parseFloat($card.data('series-discount')) || 0;
    const currency = $card.data('currency') || 'USD';
    const $checkboxes = $('.tgsd-cb');
    const $undiscountedPrice = $('#tgsd-price-undiscounted');
    const $discountedPrice = $('#tgsd-price-discounted');
    const $button = $('#tgsd-btn');
    const $form = $('#tgsd-form');
    const $amountPayable = $('#amount_payable');
    const $trainingType = $('#training_type');
    const $seriesSaving = $('#series_saving');
    
    const totalCourses = parseInt($button.data('total-courses')) || 0;
    const originalButtonText = $button.data('original-text') || 'Register for the series';
    const partialButtonText = $button.data('partial-text') || 'Register for selected courses';
    
    // Handle checkbox changes
    $checkboxes.on('change', function() {
        updatePricing();
        updateButtonText();
    });
    
    function updatePricing() {
        let totalPrice = 0;
        let checkedCount = 0;
        
        // Calculate total price of selected courses
        $checkboxes.each(function() {
            if ($(this).is(':checked')) {
                totalPrice += parseFloat($(this).data('price')) || 0;
                checkedCount++;
            }
        });
        
        // Determine if discount applies (all courses selected)
        const allSelected = checkedCount === totalCourses && checkedCount > 0;
        let finalPrice = totalPrice;
        let showDiscount = false;
        
        if (allSelected && seriesDiscount > 0) {
            const discount = Math.round(totalPrice * seriesDiscount) / 100;
            if (discount > 0) {
                finalPrice = totalPrice - discount;
                showDiscount = true;
            }
        }
        
        // Add animation class for price update
        $undiscountedPrice.addClass('price-updated');
        $discountedPrice.addClass('price-updated');
        setTimeout(() => {
            $undiscountedPrice.removeClass('price-updated');
            $discountedPrice.removeClass('price-updated');
        }, 300);
        
        // Update price display
        if (checkedCount === 0) {
            // No courses selected
            $undiscountedPrice.text(formatMoney(0, currency)).removeClass('tgsd-price-strike').hide();
            $discountedPrice.text(formatMoney(0, currency)).show();
            finalPrice = 0;
        } else if (showDiscount) {
            // Show both prices with discount
            $undiscountedPrice.text(formatMoney(totalPrice, currency)).addClass('tgsd-price-strike').show();
            $discountedPrice.text(formatMoney(finalPrice, currency)).show();
        } else {
            // Show single price without discount - centered
            $undiscountedPrice.text('').removeClass('tgsd-price-strike').hide();
            $discountedPrice.text(formatMoney(finalPrice, currency)).show();
        }
        
        // Update hidden form fields
        $amountPayable.val(finalPrice.toFixed(2));
        
        // Update training_type and series_saving fields
        if (checkedCount === 0) {
            $trainingType.val(''); // No selection
            $seriesSaving.val('0');
        } else if (allSelected) {
            $trainingType.val('s'); // Full series
            const savings = showDiscount ? (totalPrice - finalPrice) : 0;
            $seriesSaving.val(savings.toFixed(2));
        } else {
            $trainingType.val('g'); // Group/partial selection
            $seriesSaving.val('0');
        }
        
        // Update button state
        if (checkedCount === 0) {
            $button.prop('disabled', true);
        } else {
            $button.prop('disabled', false);
        }
    }
    
    function updateButtonText() {
        const checkedCount = $checkboxes.filter(':checked').length;
        const allSelected = checkedCount === totalCourses;
        
        if (checkedCount === 0) {
            $button.text('Select courses to register');
        } else if (allSelected) {
            $button.text(originalButtonText);
        } else {
            $button.text(partialButtonText);
        }
    }
    
    // Format money to match cc_money_format function
    function formatMoney(amount, currency) {
        // Replicate the logic from your cc_money_format function
        let locale;
        if (currency === 'AUD') {
            locale = 'en-AU';
        } else if (currency === 'USD') {
            locale = 'en-US';
        } else if (currency === 'EUR') {
            locale = 'en-IE'; // To show € symbol
        } else {
            locale = 'en-GB';
        }
        
        try {
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        } catch (e) {
            // Fallback formatting
            const symbols = {
                'USD': '$',
                'EUR': '€',
                'GBP': '£',
                'AUD': 'A$',
                'CAD': 'C$'
            };
            const symbol = symbols[currency] || currency + ' ';
            return symbol + amount.toFixed(2);
        }
    }
    
    // Handle form submission
    $form.on('submit', function(e) {
        const selectedCourses = [];
        $checkboxes.filter(':checked').each(function() {
            selectedCourses.push($(this).val());
        });
        
        if (selectedCourses.length === 0) {
            e.preventDefault();
            alert('Please select at least one course to register.');
            return false;
        }
        
        // Add selected training IDs as array to form
        selectedCourses.forEach(function(courseId, index) {
            $('<input>').attr({
                type: 'hidden',
                name: 'training_id[' + index + ']',
                value: courseId
            }).appendTo($form);
        });
        
        // Form will submit normally to /registration
        return true;
    });
    
    // Initialize the display with a small delay to ensure DOM is ready
    // This fixes the back button issue where checkboxes state doesn't match pricing
    setTimeout(function() {
        updatePricing();
        updateButtonText();
    }, 50);
    
    // Also run it immediately in case the delay isn't needed
    updatePricing();
    updateButtonText();
    }
});

