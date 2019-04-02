/*
jQuery( document ).on( 'click', '.love-button', function() {
	var post_id = jQuery(this).data('id');
	jQuery.ajax({
		url : postlove.ajax_url,
		type : 'post',
		data : {
			action : 'post_love_add_love',
			post_id : post_id
		},
		success : function( response ) {
			jQuery('#love-count').html( response );
		}
	});

	return false;
})
*/

jQuery("#buddypress").on("change", ".jdx-bp-post-privacy-opt", function () {
  /*
      var a = jQuery(this).attr("id");
      a = a.split("-"), a = a[a.length - 1];
      var b = this;
      data = {
        activity_id: a,
        privacy: jQuery(this).val(),
        nonce: jQuery("#rtmedia_activity_privacy_nonce").val(),
        action: "rtm_change_activity_privacy"
      }, jQuery.post(ajaxurl, data, function (a) {
        var c = "",
          d = "";
        "true" == a ? (c = rtmedia_main_js_strings.privacy_update_success, d = "success") : (c = rtmedia_main_js_strings.privacy_update_error, d = "fail"), jQuery(b).after('<p class="rtm-ac-privacy-updated ' + d + '">' + c + "</p>"), setTimeout(function () {
          jQuery(b).siblings(".rtm-ac-privacy-updated").remove()
        }, 2e3)
      })
      */
      var a = jQuery(this).attr("id");
      var pid = a.split("-"), pid = pid[pid.length - 1];

      jQuery.ajax({
        url : bp_post_privacy.ajax_url,
        type : 'post',
        data : {
          action : 'bp_post_privacy_change',
          post_id : pid,
          bp_post_privacy: jQuery(this).val(),
          nonce: jQuery("#bp_post_privacy_nonce").val()
      },
        success : function( response ) {
          //jQuery('#love-count').html( response );
          //alert(response);
        }
      });

	     //return false;
    })