jQuery(document).ready(function($) {
		$(".updated").fadeIn("slow");
		if($('input[class="pp_cubepoints_mode"][type="hidden"]').attr("value")==1){
			$(".pp_cubepoints_mode#enabled").show();
		}else if($('input[class="pp_cubepoints_mode"][type="hidden"]').attr("value")==0){
			$(".pp_cubepoints_mode#disabled").show();
		}
		
		$(".pp_cubepoints_mode").click(function () {
			$(".general-settings *").attr( "disabled", function() {
			  return !this.disabled;
			});
			$(".pp_cubepoints_mode#enabled").toggle();
			$(".pp_cubepoints_mode#disabled").toggle();
	    });
	

});

function enable_cubepoints_mode(){
	$.ajax({
	  success: function(data) {
	    $('.result').html(data);
	    alert('Load was performed.');
	  }
	});
			$.ajax({
			   type: "POST",
				url: 'http://localhost/wp_ppT/wp-content/plugins/Prospress/pp-cubepoints.php',
			   data: 	"update_option('cp_mode_enabled',false);",
			   success: function(msg){
			     alert( "Data Saved: " + msg );
			   }
			 });
}




