/**
 * JS for responsive carousel
 * added April 2015
 */

jQuery(document).ready(function( $ ) {

	function initCarousel() {
        var width = $(document).width(); // Getting the width and checking my layout
        if ( width < 768 ) {
            $('.recording-wrapper').attr('data-cycle-carousel-visible', '1');
        } else if ( width > 768 && width < 980 ) {  
            $('.recording-wrapper').attr('data-cycle-carousel-visible', '2');
        } else {
            $('.recording-wrapper').attr('data-cycle-carousel-visible', '3');
        }
        $('.recording-wrapper').cycle();
    }
    initCarousel();

    var reinitTimer;
    $(window).resize(function() {
        clearTimeout(reinitTimer);
        reinitTimer = setTimeout(reinit_cycle, 100); // Timeout limits the number of calculations   
    });

    function reinit_cycle() {
        var width = $(window).width(); // Checking size again after window resize
        $('.recording-wrapper').cycle('destroy');
        if ( width < 768 ) {
            reinitCycle(1);
        } else if ( width > 768 && width < 980 ) {
            reinitCycle(2);
        } else {
            reinitCycle(3);
        }
    }

    function reinitCycle(visibleSlides) {
    	$('.recording-wrapper').attr('data-cycle-carousel-visible', visibleSlides);
    	$('.recording-wrapper').cycle();
    }

});