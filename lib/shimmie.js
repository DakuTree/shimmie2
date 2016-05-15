/*jshint bitwise:false, curly:true, eqeqeq:true, evil:true, forin:false, noarg:true, noempty:true, nonew:true, undef:false, strict:false, browser:true */

$(document).ready(function() {
	/** Load jQuery extensions **/
	//Code via: http://stackoverflow.com/a/13106698
	$.fn.highlight = function (fadeOut) {
		fadeOut = typeof fadeOut !== 'undefined' ? fadeOut : 5000;
		$(this).each(function () {
			var el = $(this);
			$("<div/>")
				.width(el.outerWidth())
				.height(el.outerHeight())
				.css({
					"position": "absolute",
					"left": el.offset().left,
					"top": el.offset().top,
					"background-color": "#ffff99",
					"opacity": ".7",
					"z-index": "9999999",
					"border-top-left-radius": parseInt(el.css("borderTopLeftRadius"), 10),
					"border-top-right-radius": parseInt(el.css("borderTopRightRadius"), 10),
					"border-bottom-left-radius": parseInt(el.css("borderBottomLeftRadius"), 10),
					"border-bottom-right-radius": parseInt(el.css("borderBottomRightRadius"), 10)
				}).appendTo('body').fadeOut(fadeOut).queue(function () { $(this).remove(); });
		});
	};

	/** Setup jQuery.timeago **/
	$.timeago.settings.cutoff = 365 * 24 * 60 * 60 * 1000; // Display original dates older than 1 year
	$("time").timeago();

	/** Setup tablesorter **/
	$("table.sortable").tablesorter();

	/** Hide sidebar elements defined in ui-sidebar-hidden cookie **/
	var sidebar_hidden = (Cookies.get("ui-sidebar-hidden") || "").split("|");
	for(var i = 0; i < sidebar_hidden.length; i++) {
		//Check for 0 length to avoid accidentally removing all .blockbody elements
		if(sidebar_hidden[i].length > 0) {
			$(sidebar_hidden[i]+" .blockbody").hide();
		}
	}

	/** Setup sidebar toggle click events **/
	$(".shm-toggler").each(function(idx, elm) {
		var target_id = $(elm).data("toggle-sel");
		var target_elm = $(target_id+" .blockbody");
		$(elm).click(function() {
			target_elm.slideToggle("slow");

			//Save toggle status to ui-sidebar-hidden cookie
			var i = sidebar_hidden.indexOf(target_id);
			if(i === -1) {
				sidebar_hidden.push(target_id);
			} else {
				sidebar_hidden.splice(i, 1);
			}
			Cookies.set("ui-sidebar-hidden", sidebar_hidden.join("|"), {path: '/', expires: 365});
		});
	});

	$(".shm-clink").each(function(idx, elm) {
		var target_id = $(elm).data("clink-sel");
		if(target_id && $(target_id).length > 0) {
			// if the target comment is already on this page, don't bother
			// switching pages
			$(elm).attr("href", target_id);
			// highlight it when clicked
			$(elm).click(function(e) {
				// This needs jQuery UI
				$(target_id).highlight();
			});
			// vanilla target name should already be in the URL tag, but this
			// will include the anon ID as displayed on screen
			$(elm).html("@"+$(target_id+" .username").html());
		}
	});

	$(".shm-unlocker").each(function(idx, elm) {
		var tid = $(elm).data("unlock-sel");
		var tob = $(tid);
		$(elm).click(function(e) {
			$(elm).attr("disabled", true);
			tob.attr("disabled", false);
		});
	});

	if(document.location.hash.length > 3) {
		query = document.location.hash.substring(1);
		a = document.getElementById("prevlink");
		a.href = a.href + '?' + query;
		a = document.getElementById("nextlink");
		a.href = a.href + '?' + query;
	}

	/*
	 * If an image list has a data-query attribute, append
	 * that query string to all thumb links inside the list.
	 * This allows us to cache the same thumb for all query
	 * strings, adding the query in the browser.
	 */
	$(".shm-image-list").each(function(idx, elm) {
		var query = $(this).data("query");
		if(query) {
			$(this).find(".shm-thumb-link").each(function(idx2, elm2) {
				$(this).attr("href", $(this).attr("href") + query);
			});
		}
	});
});


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
*                              LibShish-JS                                  *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function addEvent(obj, event, func, capture){
	if (obj.addEventListener){
		obj.addEventListener(event, func, capture);
	} else if (obj.attachEvent){
		obj.attachEvent("on"+event, func);
	}
}


function byId(id) {
	return document.getElementById(id);
}


// used once in ext/setup/main
function getHTTPObject() { 
	if (window.XMLHttpRequest){
		return new XMLHttpRequest();
	}
	else if(window.ActiveXObject){
		return new ActiveXObject("Microsoft.XMLHTTP");
	}
}


/* get, set, and delete cookies */
function getCookie( name ) {
	var start = document.cookie.indexOf( name + "=" );
	var len = start + name.length + 1;
	if ( ( !start ) && ( name !== document.cookie.substring( 0, name.length ) ) ) {
		return null;
	}
	if ( start === -1 ) { return null; }
	var end = document.cookie.indexOf( ";", len );
	if ( end === -1 ) { end = document.cookie.length; }
	return unescape( document.cookie.substring( len, end ) );
}
	
function setCookie( name, value, expires, path, domain, secure ) {
	var today = new Date();
	today.setTime( today.getTime() );
	if ( expires ) {
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date( today.getTime() + (expires) );
	document.cookie = name+"="+escape( value ) +
		( ( expires ) ? ";expires="+expires_date.toGMTString() : "" ) + //expires.toGMTString()
		( ( path ) ? ";path=" + path : "" ) +
		( ( domain ) ? ";domain=" + domain : "" ) +
		( ( secure ) ? ";secure" : "" );
}

function replyTo(imageId, commentId, userId) {
	var box = $("#comment_on_"+imageId);
	var text = "[url=site://post/view/"+imageId+"#c"+commentId+"]@"+userId+"[/url]: ";

	box.focus();
	box.val(box.val() + text);
	$("#c"+commentId).highlight();
}
