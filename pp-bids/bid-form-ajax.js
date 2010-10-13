jQuery(document).ready(function($) {
	$(".bid-form").live( 'submit', function() {
		var link = window.location;
		var bidFormId = $(this).attr('id');
		var formData = $(this).serialize() + '&bid_submit=ajax';

		$("#bid_submit",this).attr("disabled", "true");
		$(this).css('background', 'url("' + bidi18n.siteUrl + '/wp-content/plugins/prospress/images/loadroller.gif") no-repeat center');
		$(this).fadeTo(500,0.5,function(){
			$.post(link,formData,function(data) {
				try{ var jso = $.parseJSON(data);
					window.location.replace(jso.redirect);
					} catch(err){
						var htmlStr = $(data);
						if($(".bid_msg",$('#'+bidFormId)).html().length == 0){
							$(".bid_msg",htmlStr).hide();
						}
						$('#'+bidFormId).replaceWith(htmlStr.fadeTo(0,0.5));
						$('#'+bidFormId).fadeTo(500,1,function(){
							if($(".bid_msg",$('#'+bidFormId)).is(":hidden")){
								$(".bid_msg",$('#'+bidFormId)).slideDown();
							}
							fadeBidMsg($('#'+bidFormId));
						});
					}
			});
		});
		return false;
	});

	function fadeBidMsg(context){
		$(".bid_msg",context).fadeTo(500,0.5);
		$(".bid_msg",context).fadeTo(500,1);
		return false;
	}
	fadeBidMsg();
});
