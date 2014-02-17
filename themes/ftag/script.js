$(function() {
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
});
