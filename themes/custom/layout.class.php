<?php
/**
 * A class to turn a Page data structure into a blob of HTML
 */
class Layout {
	/**
	 * turns the Page into HTML
	 */
	public function display_page(Page $page) {
		global $config;

		$contact_link = $config->get_string('contact_link');
		$contact      = !empty($contact_link) ? "<br><a href='mailto:$contact_link'>Contact</a>" : "";
		$debug        = get_debug_info();

		$header_html = "";
		ksort($page->html_headers);
		foreach($page->html_headers as $line) {
			$header_html .= "\t\t$line\n";
		}
		$header_html = ltrim($header_html);

		$left_block_html = "";
		$main_block_html = "";
		$head_block_html = "";
		$sub_block_html  = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $block->get_html(true);
					break;

				case "head":
					$head_block_html .= $block->body;
					break;

				case "main":
					$main_block_html .= $block->get_html(false);
					break;

				case "main_hide": //allow hiding certain main blocks
					$main_block_html .= $block->get_html(true);
					break;

				case "subheading":
					$sub_block_html .= $block->body; // $this->block_to_html($block, true);
					break;

				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}

		$flash = $page->get_cookie("flash_message");
		$flash_html = "";
		if($flash) {
			$flash_html = "<b id='flash'>".nl2br(html_escape($flash))." <a href='#' onclick=\"\$('#flash').hide(); return false;\">[X]</a></b>";
			//$page->set_cookie("flash_message", "", -1, "/");
		}

		print <<<EOD
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>{$page->title}</title>

		$header_html
	</head>

	<body>
		<header>
			<h1$wrapper>{$page->heading}</h1>
			$head_block_html
			$sub_block_html
		</header>

		<nav>
			$left_block_html
		</nav>

		<article>
			$flash_html
			$main_block_html
		</article>

		<footer>
			Images &copy; their respective owners,
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp;
			<a href="https://github.com/shish/shimmie2/graphs/contributors">The Team</a>
			2007-2015,
			based on the Danbooru concept.
			$debug
			$contact
		</footer>
	</body>
</html>
EOD;
	}
}

