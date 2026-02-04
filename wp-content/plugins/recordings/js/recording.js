/**
 * JS for responsive carousel
 * added April 2015
 */

jQuery(document).ready(function( $ ) {

	function initCycle() {
        var width = $(document).width(); // Getting the width and checking my layout
        if ( width < 768 ) {
            $('.brand-list').cycle({
                fx: 'carousel',
                speed: 600,
                manualSpeed: 100,
                slides: '> li > ul',
                next: '.next',
                prev: '.prev',
                carouselVisible: 2,
                carouselDimension: '180px'
            });
            // console.log('Init Mobile');
        } else if ( width > 768 && width < 980 ) {  
            $('.brand-list').cycle({
                fx: 'carousel',
                speed: 600,
                manualSpeed: 100,
                slides: '> li > ul',
                next: '.next',
                prev: '.prev',
                carouselVisible: 3,
                carouselDimension: '180px'
            });
        } else {
            $('.brand-list').cycle({
                fx: 'carousel',
                speed: 600,
                manualSpeed: 100,
                slides: '> li > ul',
                next: '.next',
                prev: '.prev',
                carouselVisible: 4,
                carouselDimension: '180px'
            });
        }
    }
    initCycle();

    function reinit_cycle() {
        var width = $(window).width(); // Checking size again after window resize
        if ( width < 768 ) {
            $('.brand-list').cycle('destroy');
            reinitCycle(2);
        } else if ( width > 768 && wWidth < 980 ) {
            $('.brand-list').cycle('destroy');
            reinitCycle(3);
        } else {
            $('.brand-list').cycle('destroy');
            reinitCycle(4);
        }
    }
    function reinitCycle(visibleSlides) {
        $('.brand-list').cycle({
            fx: 'carousel',
            speed: 600,
            manualSpeed: 100,
            slides: '> ul',
            next: '.next',
            prev: '.prev',
            carouselVisible: visibleSlides,
            carouselDimension: '180px'
        });
    }
    var reinitTimer;
    $(window).resize(function() {
        clearTimeout(reinitTimer);
        reinitTimer = setTimeout(reinit_cycle, 100); // Timeout limits the number of calculations   
    });

});