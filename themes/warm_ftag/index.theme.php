<?php

class CustomIndexTheme extends IndexTheme {
	public function display_page(Page $page, $images) {
		global $config, $page;

		if(count($this->search_terms) == 0) {
			$query = null;
			$page_title = $config->get_string('title');
		}
		else {
			$search_string = implode(' ', $this->search_terms);
			$query = url_escape($search_string);
			//$page_title = html_escape($search_string);
			$page_title = 'search';
			if(count($images) > 0) {
				$page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
			}
		}
		if($this->page_number > 1 || count($this->search_terms) > 0) {
			// $page_title .= " / $page_number";
		}

		$page->add_html_header("<script type='text/javascript'>total_pages = ".($this->total_pages ?: 1).";</script>");

		$nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
		$page->set_title($page_title);
		$page->set_heading($page_title);
		$page->add_block(new Block("Navigation", $nav, "left", 0));
		if(count($images) > 0) {
			if($query) {
				$page->add_block(new Block("Images", $this->build_table($images, "#search=$query"), "main", 10, "image-list"));
				$this->display_paginator($page, "post/list/$query", null, $this->page_number, $this->total_pages, TRUE);
			}
			else {
				$page->add_block(new Block("Images", $this->build_table($images, null), "main", 10, "image-list"));
				$this->display_paginator($page, "post/list", null, $this->page_number, $this->total_pages, TRUE);
			}
		}
		else {
			$this->display_error(404, "No Images Found", "No images were found to match the search criteria");
		}
	}

	public function display_admin_block(/*array(string)*/ $parts) {
		//Hide Block
	}

	protected function build_navigation($page_number, $total_pages, $search_terms) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$u_tags = url_escape(implode(" ", $search_terms));
		$query = empty($u_tags) ? "" : '/'.$u_tags;


		$h_prev = ($page_number <= 1) ? "Prev" : '<a href="'.make_link('post/list'.$query.'/'.$prev).'">Prev</a>';
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" : '<a href="'.make_link('post/list'.$query.'/'.$next).'">Next</a>';
		if(class_exists("RandomImage")) { $h_random = "<br><a href='".make_link("random_image/view/".$query)."'>Random</a>"; }else{ $h_random = ""; }

		$h_search_string = html_escape(implode(" ", $search_terms));
		$h_search = "
			<p>".make_form(NULL, "GET")."
				<input type='search' name='search' placeholder='Search' value='$h_search_string' class='autocomplete_tags' autocomplete='off' />
				<input type='submit' value='Find' style='display: none;' />
			</form>
		";

		return $h_prev.' | '.$h_index.' | '.$h_next.$h_random.'<br>'.$h_search;
	}
}
?>
