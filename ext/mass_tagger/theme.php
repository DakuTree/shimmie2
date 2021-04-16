<?php

class MassTaggerTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_mass_tagger( Page $page, Event $event, $config ) {
		$data_href = get_base_href();
		$body = "
			<form action='".make_link("mass_tagger/tag")."' method='POST'>
				<a href='#' id='mass_tagger_activate'>Activate Mass Tagger</a>

				<div id='mass_tagger_controls' style='display: none;'>
					Click on images to mark them. Use the 'Index Options' in the Board Config to increase the amount of shown images.
					<br />
					<input type='hidden' name='ids' id='mass_tagger_ids' />

					<br />
					<a href='#' id='mass_tagger_mark_all'>Mark All</a> | <a href='#' id='mass_tagger_mark_none'>Mark None</a>
					<br />

					Set instead of add? <input type='checkbox' name='setadd' value='set' style='width: auto' /><br />
					<label>Tags: <input type='text' name='tag' class='autocomplete_tags' autocomplete='off' style='width: auto'/></label>

					<br />

					<input type='submit' value='Tag Marked Images' />
				</div>
			</form>
		";
		$block = new Block("Mass Tagger", $body, "left", 50);
		$page->add_block( $block );
	}
}
