	   /**
		* jQuery Cookie plugin
		*
		* Copyright (c) 2010 Klaus Hartl (stilbuero.de)
		* Dual licensed under the MIT and GPL licenses:
		* http://www.opensource.org/licenses/mit-license.php
		* http://www.gnu.org/licenses/gpl.html
		*
		*/
		jQuery.cookie = function (key, value, options) {
		
		    // key and at least value given, set cookie...
		    if (arguments.length > 1 && String(value) !== "[object Object]") {
		        options = jQuery.extend({}, options);
		
		        if (value === null || value === undefined) {
		            options.expires = -1;
		        }
		
		        if (typeof options.expires === 'number') {
		            var days = options.expires, t = options.expires = new Date();
		            t.setDate(t.getDate() + days);
		        }
		
		        value = String(value);
		
		        return (document.cookie = [
		            encodeURIComponent(key), '=',
		            options.raw ? value : encodeURIComponent(value),
		            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
		            options.path ? '; path=' + options.path : '',
		            options.domain ? '; domain=' + options.domain : '',
		            options.secure ? '; secure' : ''
		        ].join(''));
		    }
		
		    // key and possibly options given, get cookie...
		    options = value || {};
		    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
		    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
		};

	jQuery(document).ready(function() {

		jQuery('a.socialmedia').click(function(event) {
			var width  = 575,
			    height = 400,
			    left   = (jQuery(window).width()  - width)  / 2,
			    top    = (jQuery(window).height() - height) / 2,
			    url    = this.href,
			    opts   = 'status=1' +
			             ',width='  + width  +
			             ',height=' + height +
			             ',top='    + top    +
			             ',left='   + left;
			
			window.open(url, 'socialmediashare', opts);
			
			return false;
		});

		// FIX ME: Don't hardcode feelbox.local
		// FIX ME: xhr should not be a global variable, but where do I spec this? 
		if (typeof( feelboxAjax ) != 'undefined' && 
		   (feelboxAjax.centralized == '1' ) && 
		   (typeof( feelboxAjax.ajaxurl ) != undefined) ) {		

			easyXDM.DomHelper.requiresJSON("json2.js");
			xhr = new easyXDM.Rpc({
			    remote: feelboxAjax.cors + "/cors/"
			}, {
			    remote: {
			        request: {} // request is exposed by /cors/
			    }
			});
		}
 
		/**
		 * jQuery.fn.sortElements
		 * --------------
		 * @param Function comparator:
		 *   Exactly the same behaviour as [1,2,3].sort(comparator)
		 *   
		 * @param Function getSortable
		 *   A function that should return the element that is
		 *   to be sorted. The comparator will run on the
		 *   current collection, but you may want the actual
		 *   resulting sort to occur on a parent or another
		 *   associated element.
		 *   
		 *   E.g. $('td').sortElements(comparator, function(){
		 *      return this.parentNode; 
		 *   })
		 *   
		 *   The <td>'s parent (<tr>) will be sorted instead
		 *   of the <td> itself.
		 */
		jQuery.fn.sortElements = (function(){
		    var sort = [].sort;
		 
		    return function(comparator, getSortable) {
		 
		        getSortable = getSortable || function(){return this;};
		 
		        var placements = this.map(function(){
		 
		            var sortElement = getSortable.call(this),
		                parentNode = sortElement.parentNode,
		 
		                // Since the element itself will change position, we have
		                // to have some way of storing its original position in
		                // the DOM. The easiest way is to have a 'flag' node:
		                nextSibling = parentNode.insertBefore(
		                    document.createTextNode(''),
		                    sortElement.nextSibling
		                );
		 
		            return function() {
		 
		                if (parentNode === this) {
		                    throw new Error(
		                        "You can't sort elements if any one is a descendant of another."
		                    );
		                }
		 
		                // Insert before flag:
		                parentNode.insertBefore(this, nextSibling);
		                // Remove flag:
		                parentNode.removeChild(nextSibling);
		 
		            };
		 
		        });
		 
		        return sort.call(this, comparator).each(function(i){
		            placements[i].call(getSortable.call(this));
		        });
		 
		    };
		 
		})();
		
		if (typeof( feelboxAjax ) != 'undefined' &&  (typeof( feelboxAjax.ajaxurl ) != undefined) ) {
		
			if ( detect_width() < 510 ) {
				jQuery("#feelbox-widget").addClass('small');
			}
		
			jQuery.post(
				feelboxAjax.ajaxurl, {
					action : 'populate_post',
					postID : feelboxAjax.id,
					token : feelboxAjax.token
				},
				function( response ) {
					after_populate( response );
				}
			);
		}
		
		/*
		jQuery.post(
			feelboxAjax.ajaxurl, {
				action : 'check_ip',
				postID : feelboxAjax.id,
				token : feelboxAjax.token
			},
			function( response ) {
				console.log ( response );
			}
		);
		*/
	});
	
	function detect_width () {
		var widget_width = jQuery("#feelbox-widget").width();
	
		return widget_width;
	}
	
	function after_populate( obj ) {
		// console.log(obj); We'll use calculate_percentages to remove the loading screen
		// jQuery("#feelbox-widget #body #loading").css("display","none");
		jQuery("#feelbox-widget #mdr-e1 .count").html(obj.emotion1 ? obj.emotion1 : '0');
		jQuery("#feelbox-widget #mdr-e2 .count").html(obj.emotion2 ? obj.emotion2 : '0');
		jQuery("#feelbox-widget #mdr-e3 .count").html(obj.emotion3 ? obj.emotion3 : '0');
		jQuery("#feelbox-widget #mdr-e4 .count").html(obj.emotion4 ? obj.emotion4 : '0');
		jQuery("#feelbox-widget #mdr-e5 .count").html(obj.emotion5 ? obj.emotion5 : '0');
		jQuery("#feelbox-widget #mdr-e6 .count").html(obj.emotion6 ? obj.emotion6 : '0');
		jQuery("#feelbox-widget #total").html( obj.sum );	
		jQuery("#feelbox-widget #voted").html( obj.voted );

		calculate_percentages();		
		
		if (jQuery("#feelbox-widget #voted").html()) {
			jQuery("#feelbox-widget").addClass("voted");
			jQuery("#mdr-e" + jQuery("#feelbox-widget #voted").html() + " .cell div");

		} else {
			jQuery("#feelbox-widget ul li").click(function(){
				var id = jQuery(this).attr("id").substr(5);
				// console.log(id);
				jQuery("#feelbox-widget #bd #loading").css("display","block");
				myplugin_cast_vote(id,'voteresults');
			});
			
			jQuery("#feelbox-widget ul li").hover(
				function() {
					jQuery(this).find(".cell div").append(jQuery("<span class='vftthx'>I FEEL THIS WAY</span>"));
				},
				function() {
					jQuery(this).find("span.vftthx").remove();
				}
			);
		}	
		
	}
	
	function myplugin_cast_vote( vote_field, results_div ) {
		// alert("start here?");
		jQuery.post(
			// see tip #1 for how we declare global javascript variables
			feelboxAjax.ajaxurl,
			{
				// here we declare the parameters to send along with the request
				// this means the following action hooks will be fired:
				// wp_ajax_nopriv_feelboxAjax-submit and wp_ajax_feelboxAjax-submit
				action : 'cast_vote',
				token: feelboxAjax.token,
				feelboxvote : vote_field, 
				// other parameters can be added along with "action"
				postID : feelboxAjax.id,
				results_div_id : results_div
			},
			function( response ) {
				// console.log ( response );
				// alert("end here?");
				after_vote( response );
				
				
				if ( feelboxAjax.centralized == '1' ) {
					xhr.request({
					    url: feelboxAjax.cors + "/api/articles/vote",
					    method: "POST",
					    data: { 
					    		'w':  feelboxAjax.siteid, 
					    		'a':  feelboxAjax.id, 
					    		'v':  vote_field, 
					    		'at': feelboxAjax.title, 
					    		'au': feelboxAjax.url,
					    		'api':feelboxAjax.api  
					    	  }
					}, function(response) {
					    // console.log(response.status);
					    // console.log(response.data);
					    jQuery.cookie('feelbox_' + feelboxAjax.id, vote_field, { expires : 10, path: '/' });
					    //console.log('cookie of feelbox_' + feelboxAjax.id, jQuery.cookie('feelbox_' + feelboxAjax.id));
					});

				} else {
				    jQuery.cookie('feelbox_' + feelboxAjax.id, vote_field, { expires : 10, path: '/' });
				}

				if ( feelboxAjax.tweet == 'on' ) {
					var strEmo = jQuery("#feelbox-widget #mdr-e" + vote_field + " .m").html();
					var message = "I just voted \"" + strEmo + "\" in \"" + feelboxAjax.title + "\"";
					var tweetButton = jQuery('#tweet-button').attr('href').replace(/text=[^&]+/, "text=" + encodeURIComponent(message) );
					var fbButton = jQuery('#fb-button').attr('href').replace(/p\[summary\]=[^&]+/, "p[summary]=" + encodeURIComponent(message) );
					
					jQuery('#tweet-button').attr('href', tweetButton);
					jQuery('#fb-button').attr('href', fbButton);

					jQuery("#feelbox-widget #lyr1").css("visibility","visible");
					jQuery("#feelbox-widget #lyr2").css("visibility","visible");
					
					jQuery("#feelbox-widget #clr").click(function(){
						jQuery("#feelbox-widget #lyr1").css("visibility","hidden");
						jQuery("#feelbox-widget #lyr2").css("visibility","hidden");
						return false;
					});
			
				}		
			}
		);
	} // end of JavaScript function myplugin_cast_vote
	//]]>
	
	function after_vote( obj ) {

		var oldvoteval = parseInt(jQuery("#feelbox-widget #mdr-e"+obj.vote+" .count").html());
		//console.log(obj.vote);
		//console.log(jQuery("#feelbox-widget #mdr-e"+obj.vote+" .count").html());
		//console.log(oldvoteval);
		jQuery("#feelbox-widget #mdr-e"+obj.vote+" .count").html( oldvoteval + 1 );

		var oldtotal = parseInt(jQuery("#feelbox-widget #total").html());
		jQuery("#feelbox-widget #total").html( oldtotal + 1 );
		jQuery('#feelbox-widget ul li').unbind('click').unbind('mouseenter').unbind('mouseleave');

		jQuery("#feelbox-widget").addClass("voted");
		jQuery("#feelbox-widget #voted").html( obj.vote );
		jQuery("#mdr-e" + jQuery("#feelbox-widget #voted").html() + " .cell div")
			.find("span.vftthx").remove();
		jQuery("#mdr-e" + jQuery("#feelbox-widget #voted").html() + " .cell div")
			.append(jQuery("<span class='vftthx'>THANKS!</span>"));

		calculate_percentages();
		// We'll disable jQuery animations for now, since different blogs have problems with the "effect" method.
		// jQuery("#mdr-e" + jQuery("#feelbox-widget #voted").html()).effect("highlight", {}, 1000);
	}
	
	function calculate_percentages() {
	
		var total = parseInt(jQuery("#feelbox-widget #total").html());
		
		jQuery("#feelbox-widget #sparkbardiv").css("display", (total && feelboxAjax.sparkline == 'on') ? "block" : "none");
		if (total) {
			jQuery("#feelbox-widget .sparkbar").html("");
		}
		
		for (var i=1; i<7; i=i+1) {
			var moodVotes = parseInt(jQuery("#feelbox-widget #mdr-e"+i+" .count").html());
			var percentage = moodVotes/total;
			jQuery("#feelbox-widget #mdr-e"+i+" .percent").html(
				(percentage) ? Math.round(percentage*100) + '%'
						: jQuery('<span class="p-0">0%</span>')
			);
			
			/* Now update the spark bar, if applicable */
			if (moodVotes && feelboxAjax.sparkline == 'on') {
				jQuery("#feelbox-widget .sparkbar")
					.append('<div class="spark' + i + '" style="width: ' + percentage*100 + '%"></div>');
			}
		}	

		if ( feelboxAjax.sortmoods == 'on') {
			jQuery("#feelbox-widget #bd ul li").sortElements(function(a, b){
			    return parseInt(jQuery(a).find(".count").text()) < parseInt(jQuery(b).find(".count").text()) ? 1 : -1;
			});	
	
			jQuery("#feelbox-widget .sparkbar div").sortElements(function(a, b){
			    return parseInt(jQuery(a).css("width")) < parseInt(jQuery(b).css("width")) ? 1 : -1;
			});	
		}
		
		jQuery("#feelbox-widget #bd #loading").css("display","none");
	}