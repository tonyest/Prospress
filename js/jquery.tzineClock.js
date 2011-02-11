/*!
 * jquery.tzineClock.js - Tutorialzine Colorful Clock Plugin
 *
 * Copyright (c) 2009 Martin Angelov
 * http://tutorialzine.com/
 *
 * Licensed under MIT
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Launch  : December 2009
 * Version : 1.0
 * Released: Monday 28th December, 2009 - 00:00
 * Modified: Modified to apply countdown for multiple prospress posts by Anthony Khoo.
 */

(function($) {
	
	// Extending the jQuery core:
	$.fn.tzineClock = function(opts){
	
		// "this" contains the elements that were selected when calling the plugin: $('elements').tzineClock();
		// If the selector returned more than one element, use the first one:
		
		var container = this.eq(0);

		if(!container)
		{
			try{
				console.log("Invalid selector!");
			} catch(e){}
			
			return false;
		}
		
		if(!opts) opts = {}; 
		
		var defaults = {
			/* Additional options will be added in future versions of the plugin. */
		};
		
		/* Merging the provided options with the default ones (will be used in future versions of the plugin): */
		$.each(defaults,function(k,v){
			opts[k] = opts[k] || defaults[k];
		})

		// Calling the setUp function and passing the container,
		// will be available to the setUp function as "this":
		setUp.call(container);
		
		return this;
	}
	
	function setUp()
	{

		//get the start time
		var id = $(this).attr('id');
		
		// The colors of the dials:
		var colors = ['orange','blue','green'];
		
		var tmp;
		
		for(var i=0;i<3;i++) {
			// Creating a new element and setting the color as a class name:
			
			tmp = $('<div>').attr('class',colors[i]+' clock').html(
					'<div class="display"></div>'+
				
					'<div class="front left"></div>'+
				
					'<div class="rotate left">'+
						'<div class="bg left"></div>'+
					'</div>'+
				
					'<div class="rotate right">'+
						'<div class="bg right"></div>'+
					'</div>'
			);
			
			// Appending to the container:
			$(this).append(tmp);	

			//date checks
			var now = new Date();
			var post_end = new Date();
			//calculate time difference
			post_end.setTime(id*1000);	//format to UTC(miliseconds)
			var td=post_end-now;
			var one_day=1000*24*60*60;
			if ( td <= 0 || td >= one_day ) {	
				$(this).find('.tzine-countdown').hide();
			} else {
				$(this).find('span.end-time').hide();
			}
		
		}
		//wrap container with formatting container block
		$(this).find('.clock').wrapAll('<div class="tzine-countdown"></div>');
		
		//attach date data to container
		$(this).find('.tzine-countdown').data( 'countdown', { 'post_end': post_end } );
		
		// Setting up a interval, executed every 1000 milliseconds:
		setInterval(function(){
			
			$('.tzine-countdown').each( function(index,element) {

				var countdown = $(element).data('countdown');
				var now = new Date();
				var post_end = new Date();
			//calculate time difference
				post_end = countdown.post_end;
				var td=post_end-now;
			//convert time difference to groups: Years, Months, Weeks, Days, Hours, Minutes, Seconds remaining
				var one_day=1000*24*60*60;
				var one_hr=1000*60*60;
				var one_min=60000;
				var h=Math.floor((td%one_day)/one_hr);
				var m=Math.floor(((td%one_day)%one_hr)/one_min);
				var s=Math.floor((td%one_day)%one_hr%one_min/1000);

			//stop timer on timeout & hide before 24hrs
				if ( td <= 0 || td >= one_day ) {
						$(element).hide();
					} else {
						$(element).show();
						$(element).parent().find('span.end-time').hide();
					}
				var tVar = {};
				for(var i=0;i<3;i++) {

					tmp = $(this).find( '.' + colors[i] );
					tmp.rotateLeft = tmp.find('.rotate.left');
					tmp.rotateRight = tmp.find('.rotate.right');
					tmp.display = tmp.find('.display');
					tVar[colors[i]] = tmp;		
				}
				animation(tVar.green, s, 60);
				animation(tVar.blue, m, 60);
				animation(tVar.orange, h, 24);	
			});
			
		},1000);
	}
	
	function animation(clock, current, total)
	{

		// Calculating the current angle:
		var angle = (360/total)*(current+1);

		var element;

		if(current==0)
		{
			// Hiding the right half of the background:
			clock.rotateRight.hide();
			
			// Resetting the rotation of the left part:
			rotateElement(clock.rotateLeft,0);
		}
		
		if(angle<=180)
		{
			// The left part is rotated, and the right is currently hidden:
			element = clock.rotateLeft;
		}
		else
		{
			// The first part of the rotation has completed, so we start rotating the right part:
			clock.rotateRight.show();
			clock.rotateLeft.show();
			
			rotateElement(clock.rotateLeft,180);
			
			element = clock.rotateRight;
			angle = angle-180;
		}

		rotateElement(element,angle);
		
		// Setting the text inside of the display element, inserting a leading zero if needed:
		clock.display.html(current<10?'0'+current:current);
	}
	
	function rotateElement(element,angle)
	{
		// Rotating the element, depending on the browser:
		var rotate = 'rotate('+angle+'deg)';
		
		if(element.css('MozTransform')!=undefined)
			element.css('MozTransform',rotate);
			
		else if(element.css('WebkitTransform')!=undefined)
			element.css('WebkitTransform',rotate);
	
		// A version for internet explorer using filters, works but is a bit buggy (no surprise here):
		else if(element.css("filter")!=undefined)
		{
			var cos = Math.cos(Math.PI * 2 / 360 * angle);
			var sin = Math.sin(Math.PI * 2 / 360 * angle);
			
			element.css("filter","progid:DXImageTransform.Microsoft.Matrix(M11="+cos+",M12=-"+sin+",M21="+sin+",M22="+cos+",SizingMethod='auto expand',FilterType='nearest neighbor')");
	
			element.css("left",-Math.floor((element.width()-200)/2));
			element.css("top",-Math.floor((element.height()-200)/2));
		}
	
	}
	
})(jQuery)