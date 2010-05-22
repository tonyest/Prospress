jQuery(document).ready(function($) {

	$(".bid_form").live( 'submit', function() {
		var link = window.location;
		var form_data = $(this).serialize() + '&bid_submit=ajax';
		//alert('form_data = ' + form_data);
		//alert('#post_ID this = ' + $("#post_ID",this).val());
		var $bidBox = $( "#bid-" + $("#post_ID",this).val() );
		//alert('bidBox = ' + $bidBox.html() );

		$("#bid_submit",this).attr("disabled", "true");
		$(this).css('background', 'url("http://localhost.localdomain/trunk/wp-content/plugins/prospress/images/loadroller.gif") no-repeat center');
		$bidBox.fadeTo(500,0.5,function(){
		//$(this).fadeTo(500,0.5,function(){
			$.post(link,form_data,function(data) {
				try{ var jso = $.parseJSON(data);
					window.location.replace(jso.redirect);
					} catch(err){
						var htmlStr = $(data);
						if($(".bid_msg",this).length == 0){
							$(".bid_msg",htmlStr).hide();
						}
						//alert('before replace htmlStr = ' + htmlStr.html() );
						//alert('before replace bidBox = ' + $bidBox.html() );
						//$("#bid").replaceWith(htmlStr.fadeTo(0,0.5));
						//$bidBox.replaceWith(htmlStr.fadeTo(0,0.5));
						$($bidBox).replaceWith(htmlStr.fadeTo(0,0.5));
						//$( "#bid-" + $("#post_ID",this)).replaceWith(htmlStr.fadeTo(0,0.5));
						//alert('after replace bidBox = ' + $bidBox.html() );
						//$bidBox = $( "#bid-" + $("#post_ID",this).val());
						//alert('after reselection bidBox = ' + $bidBox.html() );
						$bidBox.fadeTo(500,1,function(){
							if($(".bid_msg",$bidBox).is(":hidden")){
								$(".bid_msg",$bidBox).slideDown();
							}
							fadeBidMsg($bidBox);
						});
					}
			});
		});
		return false;
	});

	function fadeBidMsg(context){
		$(".bid_msg",context).fadeTo(400,0.5);
		$(".bid_msg",context).fadeTo(600,1);
		return false;
	}
	fadeBidMsg();
});
