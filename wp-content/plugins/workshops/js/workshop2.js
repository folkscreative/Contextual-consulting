/**
 * JS for responsive carousel
 * added April 2015
 * added currency switcher Apr 2020
 * and the tot pricing for multiple events
 */

jQuery(document).ready(function( $ ) {

	function initCarousel() {
        var width = $(document).width(); // Getting the width and checking my layout
        if ( width < 768 ) {
            $('.workshop-wrapper').attr('data-cycle-carousel-visible', '1');
        } else if ( width > 768 && width < 980 ) {  
            $('.workshop-wrapper').attr('data-cycle-carousel-visible', '2');
        } else {
            $('.workshop-wrapper').attr('data-cycle-carousel-visible', '3');
        }
        $('.workshop-wrapper').cycle();
    }
    initCarousel();

    var reinitTimer;
    $(window).resize(function() {
        clearTimeout(reinitTimer);
        reinitTimer = setTimeout(reinit_cycle, 100); // Timeout limits the number of calculations   
    });

    function reinit_cycle() {
        var width = $(window).width(); // Checking size again after window resize
        $('.workshop-wrapper').cycle('destroy');
        if ( width < 768 ) {
            reinitCycle(1);
        } else if ( width > 768 && width < 980 ) {
            reinitCycle(2);
        } else {
            reinitCycle(3);
        }
    }

    function reinitCycle(visibleSlides) {
    	$('.workshop-wrapper').attr('data-cycle-carousel-visible', visibleSlides);
    	$('.workshop-wrapper').cycle();
    }

    $(document).on('change', '#currency-switch', function(){
        $('#pmt-curr-switch-wait').show();
        $('#currency-switch-form').submit();
    });

    $(document).on('change', '.event-chooser', function(){
        var numChkd = 0;
        var numFreeChkd = 0;
        var eventIDs = '';
        var totPrice = 0;
        var currency = $('#currency').val();
        $('.event-chooser:checked').each(function(){
            numChkd++;
            eventIDs = eventIDs + $(this).data('eventid') + ',';
            if($(this).data('free') == 'yes'){
                numFreeChkd++;
            }else{
                totPrice = totPrice + $(this).data('price');
            }
        });
        var numPaidChkd = numChkd - numFreeChkd;
        // var eventPrice = $('#tot-price').data('event');
        // var totPrice = eventPrice * numPaidChkd;
        var numEvents = $('#num-events').val();
        var numFree = $('#num-free').val();
        var numPaid = numEvents - numFree;
        if(numPaidChkd == numPaid){
            var discount = $('#tot-price').data('discount');
            if(discount > 0){
                totPrice = totPrice * ((100 - discount) / 100);
            }
        }
        $('#raw-price').val(totPrice);
        $('.tot-price').html( localisePrice(totPrice, currency) );
        $('#eventID').val(eventIDs);
        if(numChkd > 0){
            $('.comp-reg').removeClass('disabled');
            $('.comp-reg').attr('title', '');
        }else{
            $('.comp-reg').addClass('disabled');
            $('.comp-reg').attr('title', 'Select an event first');
        }
    });

    $(document).on('click', '#to-events-panel', function(){
        document.querySelector('#events-reg').scrollIntoView({ 
          behavior: 'smooth' 
        });
    });

});