/**
 * WMS JS
 */
jQuery(document).ready(function($){
	// sort out the background heights so the section's columns match
    function wmsSectionHeightAdj(){
    	$('.wms-sect-eq-ht').each(function(){
    		var $this = $(this);
    		var maxHeight = -1;
    		$this.find('.wms-sect-inner').each(function(){
    			$(this).css('height','auto');
                if ($(this).outerHeight() > maxHeight){
    				maxHeight = $(this).outerHeight();
    			};
    		});
    		$this.find('.wms-sect-inner').each(function(){
    			$(this).outerHeight(maxHeight);
    		});
    	});
    }
    function wmsGridSectHeightAdj(){
    	$('.wms-section-grid').each(function(){
    		var $this = $(this);
    		var maxHeight = 250; // min height
    		$this.find('.wms-grid-hover-show').each(function(){
    			$(this).css('height', 'auto');
    			if($(this).actual('outerHeight') > maxHeight){
    				maxHeight = $(this).actual('outerHeight');
    			}
    		});
    		$this.find('.wms-sect-inner').each(function(){
    			$(this).outerHeight(maxHeight);
    		});
    		$this.find('.wms-grid-hover-show').each(function(){
    			$(this).outerHeight(maxHeight);
    		});
    	});
    };
    wmsSectionHeightAdj();
    wmsGridSectHeightAdj();
    $(window).resize(function(){
        wmsSectionHeightAdj();
        wmsGridSectHeightAdj();
    });

    // team page modal
    if($('#team-member-modal').length){
        var teamModal = document.getElementById('team-member-modal')
        teamModal.addEventListener('show.bs.modal', function (event){
            // Button that triggered the modal
            var button = event.relatedTarget;
            var teamMember = button.closest('.team-member');
            var teamMemberPhoto = $(teamMember).find('.team-member-photo').css('background-image');
            $('#team-member-modal .team-member-photo').css('background-image', teamMemberPhoto);
            var teamMemberName = $(teamMember).find('.team-member-name a').html();
            $('#team-member-modal .team-member-name').html(teamMemberName);
            var teamMemberRole = $(teamMember).find('.team-member-role a').html();
            $('#team-member-modal .team-member-role').html(teamMemberRole);
            var teamMemberBio = $(teamMember).find('.team-member-bio').html();
            $('#team-member-modal .team-member-bio').html(teamMemberBio);
        });
    }

    // grid elements hover
    /*
    $('.wms-grid-hover').hoverIntent(function(){
    	$(this).find('.wms-grid-hover-show').slideDown();
        $(this).find('.wms-grid-bg-heading').hide();
    }, function(){
    	$(this).find('.wms-grid-hover-show').slideUp();
        $(this).find('.wms-grid-bg-heading').show();
    });
    */

});

// set cookie
// for info on SameSite ... https://web.dev/samesite-cookies-explained/
// if expDays is empty, will be a session cookie
function wmsSetCookie(cName, cValue, expDays) {
    var expires = '';
    if( expDays != '' ){
        let date = new Date();
        date.setTime(date.getTime() + (expDays * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = cName + "=" + cValue + expires + "; SameSite=Lax; path=/";
}

// delete cookie
function wmsDeleteCookie(cName){
    document.cookie = cName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}

// get cookie
function wmsGetCookie(cName) {
    let name = cName + "=";
    // to handle cookies with special characters ...
    let decodedCookie = decodeURIComponent(document.cookie);
    // split into an array
    let ca = decodedCookie.split(';');
    for(let i = 0; i <ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            // cookie found
            return c.substring(name.length, c.length);
        }
    }
    // not found
    return "";
}
