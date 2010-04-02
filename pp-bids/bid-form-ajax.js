jQuery(document).ready(function($) {

	$("#bid_form").live( 'submit', function() {
		var link = window.location;
		var form_data = $("#bid_form").serialize() + '&bid_submit=ajax';

		$("#bid_submit").attr("disabled", "true");
		$("#bid_form").css('background', 'url("http://localhost.localdomain/trunk/wp-content/plugins/prospress/images/loadroller.gif") no-repeat center');
		$("#bid").fadeTo(500,0.5,function(){
			$.post(link,form_data,function(data) {
				try{ var jso = $.parseJSON(data);
					window.location.replace(jso.redirect);
					} catch(err){
						var htmlStr = $(data);
						if($("#bid_msg").length == 0){
							$("#bid_msg",htmlStr).hide();
						}
						$("#bid").replaceWith(htmlStr.fadeTo(0,0.5));
						$("#bid").fadeTo(500,1,function(){
							if($("#bid_msg").is(":hidden")){
								$("#bid_msg").slideDown();
							}
							fadeBidMsg();
						});
					}
			});
		});
		return false;
	});

	function fadeBidMsg(){
		$("#bid_msg").fadeTo(300,0.5);
		$("#bid_msg").fadeTo(300,1);
		$("#bid_msg").fadeTo(300,0.5);
		$("#bid_msg").fadeTo(300,1);
		return false;
	}
	fadeBidMsg();
});
