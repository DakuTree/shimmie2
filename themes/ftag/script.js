$(function() {
	var current_page = $('#paginator b a').text();

	$('#orderby').change(function(){
		var value = $("#orderby option:selected")[0].value;
		$.cookie("shm_order_by", value, {path: '/', expires: 365});
		window.location.href = '';
	});

	$('.qt_a').click(function(){
		var tag = $(this).text();
		var taglist = $('#tag_editor').val().split(" ");

		if(tag.substr(0, 1) === "-"){
			var i = $.inArray(tag.substring(1), taglist);
			i !== -1 ? taglist.splice(i, 1) : null;
		}else{
			taglist.unshift(tag);
		}

		$('#tag_editor').val(taglist.join(" "));
		return false;
	});

	$('#slidetoggle').click(function(){
		$('#slider').slideDown("slow");
		$(this).hide("slow");
		return false;
	});

	$('.image_info > tbody > tr:eq(2) > td:eq(0), .image_info > tbody > tr:eq(3) > td:eq(0)').click(function(e){
		if($(e.target).is('a')){
			return true;
		}else if(!$(e.target).is('input')){
			$(this).find('span').toggle();
			$(this).find('input').toggle();
		}
	});

	$('#paginator a:contains("Random")').hover(function(){
		if(total_pages >= 5){
			$(this).attr('href', $(this).attr('href').replace(/(.*?)[0-9]+$/, "$1"));

			var rdm = current_page;

			while(rdm == current_page){
				rdm = Math.floor(Math.random() * (total_pages - 1 + 1) + 1);
			}
			$(this).attr('href', $(this).attr('href').concat(rdm));
		}
	});
});
