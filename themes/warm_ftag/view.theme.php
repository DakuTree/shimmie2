<?php

class CustomViewImageTheme extends ViewImageTheme {
	public function display_page(Image $image, $editor_parts) {
		global $page;

		$h_metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));

		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->add_html_header("<meta name=\"keywords\" content=\"$h_metatags\">");
		$page->add_html_header("<meta property=\"og:title\" content=\"$h_metatags\">");
		$page->add_html_header("<meta property=\"og:type\" content=\"article\">");
		$page->add_html_header("<meta property=\"og:image\" content=\"".make_http($image->get_thumb_link())."\">");
		$page->add_html_header("<meta property=\"og:url\" content=\"".make_http(make_link("post/view/{$image->id}"))."\">");
		$page->add_html_header("<script type=\"text/javascript\">
			(function($){
				$(document).keyup(function(e) {
					if($(e.target).is('input', 'textarea')){ return; }
					if (e.keyCode == 46) { $('input[value=\"Delete\"]').click(); }
				});
			})(jQuery);
			</script>", 60);
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block("Navigation", $this->build_navigation($image), "left", 0));
		$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 20));
	}

	public function display_admin_block(Page $page, $parts) {
		if(count($parts) > 0) {
			$html = "";

			foreach($parts as $part) {
				if((strpos($part, "Image Only") == false) && (strpos($part, "Replace") == false) && (strpos($part, "shm-zoomer") == false)){
					$html .= $part;
				}
				
			}

			$page->add_block(new Block("Image Controls", $html, "left", 50));
		}
	}

	protected function build_pin(Image $image) {
		global $database;

		if(isset($_GET['search'])) {
			$search_terms = explode(' ', $_GET['search']);
			$query = "search=".url_escape($_GET['search']);
		}
		else {
			$search_terms = array();
			$query = null;
		}

		$h_prev = "<a id='prevlink' href='".make_link("post/prev/{$image->id}", $query)."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = "<a id='nextlink' href='".make_link("post/next/{$image->id}", $query)."'>Next</a>";
		if(class_exists("RandomImage")) { $h_random = "<br><a href='".make_link("random_image/view")."'>Random</a>"; }else{ $h_random = ""; }
		return $h_prev.' | '.$h_index.' | '.$h_next.$h_random;
	}

	protected function build_navigation(Image $image) {
		$h_pin = $this->build_pin($image);
		$h_search = "
			".make_form(NULL, "GET")."
				<input placeholder='Search' name='search' type='text'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
		";

		return "$h_pin<br>$h_search";
	}

	protected function build_info(Image $image, $editor_parts) {
		global $user;

		if(count($editor_parts) == 0) return ($image->is_locked() ? "<br>[Image Locked]" : "");

		$html = "
			<table id='qt_table'>
				<tr>
					<td><a href='#' class='qt_a'>-@:@notchecked</a></td>
				</tr>
				<tr>
					<td><a href='#' class='qt_a'>vote:up</a></td>
				</tr>
				<tr>
					<td><a href='#' class='qt_a'>vote:doubleup</a></td>
				</tr>
				<tr>
					<td><a href='#' class='qt_a'>vote:tripleup</a></td>
				</tr>
				<tr>
					<td><a href='#' class='qt_a'>@:long</a></td><td><a href='#' class='qt_a'>@:short</a></td>
				</tr>
				<tr>
					<td><a href='#' class='qt_a'>@:incomplete</a></td>
				</tr>
				<tr>
					<td><a href='#' class='qt_a'>pool:lastcreated</a></td>
				</tr>
			</table>
		";

		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->numeric_score);

		$html .= "<div id='scoreblock'>
			Current Score: $i_score

			".make_form("numeric_score_vote", "POST", array(), TRUE)."
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='hidden' name='vote' value='tripleup'>
				<input type='submit' value='Triple Vote Up'>
			</form>
		
			".make_form("numeric_score_vote", "POST", array(), TRUE)."
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='hidden' name='vote' value='doubleup'>
				<input type='submit' value='Double Vote Up'>
			</form>

			".make_form("numeric_score_vote", "POST", array(), TRUE)."
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='hidden' name='vote' value='up'>
				<input type='submit' value='Vote Up'>
			</form>

			".make_form("numeric_score_vote", "POST", array(), TRUE)."
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='hidden' name='vote' value='null'>
				<input type='submit' value='Remove Vote'>
			</form>

			<form action='".make_link("numeric_score_vote")."' method='POST'>
			".$user->get_auth_html()."
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='down'>
			<input type='submit' value='Vote Down'>
			</form></div>
		";
		$html .= make_form("post/set")."
					<input type='hidden' name='image_id' value='{$image->id}'>
					<table style='width: 500px;' class='image_info form'>
		";

		foreach($editor_parts as $part) {
				$html .= $part;
		}
		if((!$image->is_locked() || $user->can("edit_image_lock")) && $user->can("edit_image_tag")) {
			$html .= "
						<tr><td colspan='4'>
							<input class='view' type='submit' value='Set'>
						</td></tr>
			";
		}
		$html .= "
					</table>
				</form>
		";

		return $html;
	}
}
?>
