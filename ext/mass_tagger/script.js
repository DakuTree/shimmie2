/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

$(function () {
	// Clear the selection, in case it was autocompleted by the browser.
	$("#mass_tagger_ids").val("");

	$("#mass_tagger_activate").on("click", function () {
		$(this).hide();

		$("div.shm-thumb").each(function (index, block) {
			add_mass_tag_button($(block));
		});
		$("#mass_tagger_controls").show();

		return false;
	});

	$("#mass_tagger_mark_all").on("click", function () {
		$("div.shm-thumb").addClass("mass-tagger-selected");
		update_input();
		return false;
	});

	$("#mass_tagger_mark_none").on("click", function () {
		$("div.shm-thumb").removeClass("mass-tagger-selected");
		update_input();
		return false;
	});

	function add_mass_tag_button($block) {
		$block.click(function () {
			$(this).toggleClass("mass-tagger-selected");

			update_input();
			return false;
		});
	}

	function update_input() {
		let ids = $(".mass-tagger-selected")
			.map(function () {
				return $(this).find("a").data("post-id");
			})
			.get();

		$("#mass_tagger_ids").val(ids.join(":"));
	}
});
