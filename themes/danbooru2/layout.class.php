<?php
/**
* Name: Danbooru 2 Theme
* Author: Bzchan <bzchan@animemahou.com>, updated by Daniel Oaks <danneh@danneh.net>
* Link: http://trac.shishnet.org/shimmie2/
* License: GPLv2
* Description: This is a simple theme changing the css to make shimme
*              look more like danbooru as well as adding a custom links
*              bar and title to the top of every page.
*/
//Small changes added by zshall <http://seemslegit.com>
//Changed CSS and layout to make shimmie look even more like danbooru
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
Danbooru 2 Theme - Notes (Bzchan)

Files: default.php, style.css

How to use a theme
- Copy the danbooru2 folder with all its contained files into the "themes"
  directory in your shimmie installation.
- Log into your shimmie and change the Theme in the Board Config to your
  desired theme.

Changes in this theme include
- Adding and editing various elements in the style.css file.
- $site_name and $front_name retreival from config added.
- $custom_link and $title_link preparation just before html is outputed.
- Altered outputed html to include the custom links and removed heading
  from being displayed (subheading is still displayed) 
- Note that only the sidebar has been left aligned. Could not properly
  left align the main block because blocks without headers currently do
  not have ids on there div elements. (this was a problem because
  paginator block must be centered and everything else left aligned)
  
Tips
- You can change custom links to point to whatever pages you want as well as adding
  more custom links.
- The main title link points to the Front Page set in your Board Config options.
- The text of the main title is the Title set in your Board Config options.
- Themes make no changes to your database or main code files so you can switch
  back and forward to other themes all you like.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Layout {
	public function display_page($page) {
		global $config, $user;

		//$theme_name = $config->get_string('theme');
		//$base_href = $config->get_string('base_href');
		//$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');


		$header_html = "";
		ksort($page->html_headers);
		foreach($page->html_headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$user_block_html = "";
		$main_block_html = "";
		$sub_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $block->get_html(true);
					break;
				case "user":
					$user_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				case "subheading":
					$sub_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				case "main":
					if($block->header == "Images") {
						$block->header = "&nbsp;";
					}
					$main_block_html .= $block->get_html(false);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='mailto:$contact_link'>Contact</a>";

		if(empty($this->subheading)) {
			$subheading = "";
		}
		else {
			$subheading = "<div id='subtitle'>{$this->subheading}</div>";
		}

		$site_name = $config->get_string('title'); // bzchan: change from normal default to get title for top of page
		$main_page = $config->get_string('main_page'); // bzchan: change from normal default to get main page for top of page

		// bzchan: CUSTOM LINKS are prepared here, change these to whatever you like
		$custom_links = "";
		if($user->is_anonymous()) {
			$custom_links .= $this->navlinks(make_link('user_admin/login'), "Sign in", array("user", "user_admin", "setup", "admin"));
		}
		else {
			$custom_links .= $this->navlinks(make_link('user'), "My Account", array("user", "user_admin"));
		}
		if($user->is_admin()) {
			$custom_links .= $this->navlinks(make_link('admin'), "Admin", array("admin", "ext_manager", "setup"));
		}
		$custom_links .= $this->navlinks(make_link('post/list'), "Posts", array("post", "upload", "", "random_image"));
		$custom_links .= $this->navlinks(make_link('comment/list'), "Comments", array("comment"));
		$custom_links .= $this->navlinks(make_link('tags'), "Tags", array("tags", "alias"));
		if(class_exists("Pools")) {
			$custom_links .= $this->navlinks(make_link('pool/list'), "Pools", array("pool"));
		}
		if(class_exists("Wiki")) {
			$custom_links .= $this->navlinks(make_link('wiki'), "Wiki", array("wiki"));
			$custom_links .= $this->navlinks(make_link('wiki/more'), "More &raquo;", array("wiki/more"));
		}

		$custom_sublinks = "";
		// hack
		$username = url_escape($user->name);
		// hack
		$qp = explode("/", ltrim(_get_query(), "/"));
		// php sucks
		switch($qp[0]) {
			default:
			case "ext_doc":
				$custom_sublinks .= $user_block_html;
				break;
			case "user":
			case "user_admin":
				if($user->is_anonymous()) {
					$custom_sublinks .= "<li><a href='".make_link('user_admin/create')."'>Sign up</a></li>";
					// $custom_sublinks .= "<li><a href='".make_link('')."'>Reset Password</a></li>";
					// $custom_sublinks .= "<li><a href='".make_link('')."'>Login Reminder</a></li>";
				} else {
					$custom_sublinks .= "<li><a href='".make_link('user_admin/logout')."'>Sign out</a></li>";
				}
				break;
			case "":
				# FIXME: this assumes that the front page is
				# post/list; in 99% of case it will either be
				# post/list or home, and in the latter case
				# the subnav links aren't shown, but it would
				# be nice to be correct
			case "random_image":
			case "post":
			case "upload":
				if(class_exists("NumericScore")){ $custom_sublinks .= "<li><b>Popular by </b><a href='".make_link('popular_by_day')."'>Day</a>/<a href='".make_link('popular_by_month')."'>Month</a>/<a href='".make_link('popular_by_year')."'>Year</a></li>";}
				$custom_sublinks .= "<li><a href='".make_link('post/list')."'>Listing</a></li>";
				if(class_exists("Favorites")){ $custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by={$username}/1")."'>My Favorites</a></li>";}
				if(class_exists("RSS_Images")){ $custom_sublinks .= "<li><a href='".make_link('rss/images')."'>Feed</a></li>";}
				if(class_exists("RandomImage")){ $custom_sublinks .= "<li><a href='".make_link("random_image/view")."'>Random</a></li>";}
				$custom_sublinks .= "<li><a href='".make_link('upload')."'>Upload</a></li>";
				if(class_exists("Wiki")){ $custom_sublinks .= "<li><a href='".make_link("wiki/posts")."'>Help</a></li>";
				}else{ $custom_sublinks .= "<li><a href='".make_link("ext_doc/index")."'>Help</a></li>";}
				break;
			case "comment":
				$custom_sublinks .= "<li><a href='".make_link('comment/list')."'>All</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/comment")."'>Help</a></li>";
				break;
			case "pool":
				$custom_sublinks .= "<li><a href='".make_link('pool/list')."'>List</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/new")."'>Create</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/updated")."'>Changes</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/pools")."'>Help</a></li>";
				break;
			case "wiki":
				$custom_sublinks .= "<li><a href='".make_link('wiki')."'>Index</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/rules")."'>Rules</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/wiki")."'>Help</a></li>";
				break;
			case "tags":
			case "alias":
				$custom_sublinks .= "<li><a href='".make_link('tags/map')."'>Map</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('tags/alphabetic')."'>Alphabetic</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('tags/popularity')."'>Popularity</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('tags/categories')."'>Categories</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('alias/list')."'>Aliases</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/tag_edit")."'>Help</a></li>";
				break;
			case "admin":
			case "ext_manager":
			case "setup":
				if($user->is_admin()) {
					$custom_sublinks .= "<li><a href='".make_link('ext_manager')."'>Extension Manager</a></li>";
					$custom_sublinks .= "<li><a href='".make_link('setup')."'>Board Config</a></li>";
					$custom_sublinks .= "<li><a href='".make_link('alias/list')."'>Alias Editor</a></li>";
				} else {
					$custom_sublinks .= "<li>I think you might be lost</li>";
				}
				break;
		}


		// bzchan: failed attempt to add heading after title_link (failure was it looked bad)
		//if($this->heading==$site_name)$this->heading = '';
		//$title_link = "<h1><a href='".make_link($main_page)."'>$site_name</a>/$this->heading</h1>";

		// bzchan: prepare main title link
		$title_link = "<h1 id='site-title'><a href='".make_link($main_page)."'>$site_name</a></h1>";

		if($page->left_enabled) {
			$left = "<nav>$left_block_html</nav>";
			$withleft = "withleft";
		}
		else {
			$left = "";
			$withleft = "noleft";
		}

		$flash = $page->get_cookie("flash_message");
		$flash_html = "";
		if($flash) {
			$flash_html = "<b id='flash'>".nl2br(html_escape($flash))." <a href='#' onclick=\"\$('#flash').hide(); return false;\">[X]</a></b>";
		}

		print <<<EOD
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
	<head>
		<title>{$page->title}</title>
$header_html
	</head>

	<body>
		<header>
			$title_link
			<ul id="navbar" class="flat-list">
				$custom_links
			</ul>
			<ul id="subnavbar" class="flat-list">
				$custom_sublinks
			</ul>
		</header>
		$subheading
		$sub_block_html
		$left
		<article class="$withleft">
			$flash_html
			$main_block_html
		</article>
		<footer><div>
			Running Shimmie &ndash;
			Images &copy; their respective owners,
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/graphs/contributors">The Team</a>
			2007-2015,
			based on the Danbooru concept<br />
			$debug
			$contact
		</div></footer>
	</body>
</html>
EOD;
	}

	/**
	 * @param string $link
	 * @param string $desc
	 * @param string[] $pages_matched
	 * @return string
	 */
	private function navlinks($link, $desc, $pages_matched) {
	/**
	 * Woo! We can actually SEE THE CURRENT PAGE!! (well... see it highlighted in the menu.)
	 */
		$html = "";
		$url = _get_query();

		$re1='.*?';
		$re2='((?:[a-z][a-z_]+))';

		if (preg_match_all("/".$re1.$re2."/is", $url, $matches)) {
			$url=$matches[1][0];
		}
		
		$count_pages_matched = count($pages_matched);
		
		for($i=0; $i < $count_pages_matched; $i++) {
			if($url == $pages_matched[$i]) {
				$html = "<li class='current-page'><a href='$link'>$desc</a></li>";
			}
		}
		if(empty($html)) {$html = "<li><a class='tab' href='$link'>$desc</a></li>";}
		return $html;
	}
}

