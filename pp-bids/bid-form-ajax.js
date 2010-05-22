jQuery(document).ready(function($) {

	$(".bid_form").live( 'submit', function() {
		var link = window.location;
		var form_data = $(this).serialize() + '&bid_submit=ajax';
		//alert('form_data = ' + form_data);
		//alert('#post_ID this = ' + $("#post_ID",this).val());
		var post_id = $("#post_ID",this).val();
		var $bidContainer = $("#bid-" + post_id);
		//alert('bidBox = ' + $bidContainer.html() );

		$("#bid_submit",this).attr("disabled", "true");
		$(this).css('background', 'url("http://localhost.localdomain/trunk/wp-content/plugins/prospress/images/loadroller.gif") no-repeat center');
		$bidContainer.fadeTo(500,0.5,function(){
		//$(this).fadeTo(500,0.5,function(){
			$.post(link,form_data,function(data) {
				try{ var jso = $.parseJSON(data);
					window.location.replace(jso.redirect);
					} catch(err){
						var htmlStr = $(data);
						if($(".bid_msg",this).length == 0){
							$(".bid_msg",htmlStr).hide();
						}
						$bidContainer.replaceWith(htmlStr.fadeTo(0,0.5));
						$bidContainer = $("#bid-" + post_id);
						$bidContainer.fadeTo(500,1,function(){
							if($(".bid_msg",$bidContainer).is(":hidden")){
								$(".bid_msg",$bidContainer).slideDown();
							}
							fadeBidMsg($bidContainer);
						});
					}
			});
		});
		return false;
	});

	function fadeBidMsg(context){
		$(".bid_msg",context).fadeTo(600,0.5);
		$(".bid_msg",context).fadeTo(600,1);
		return false;
	}
	fadeBidMsg();
});
