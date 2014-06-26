<?php

class CustomPixelFileHandlerTheme extends PixelFileHandlerTheme {
	public function display_image(Page $page, Image $image) {
		global $config;

		$u_ilink = $image->get_image_link();
		if($config->get_bool("image_show_meta") && function_exists("exif_read_data")) {
			# FIXME: only read from jpegs?
			$exif = @exif_read_data($image->get_image_filename(), 0, true);
			if($exif) {
				$head = "";
				foreach ($exif as $key => $section) {
					foreach ($section as $name => $val) {
						if($key == "IFD0") {
							$head .= html_escape("$name: $val")."<br>\n";
						}
					}
				}
				if($head) {
					$page->add_block(new Block("EXIF Info", $head, "left"));
				}
			}
		}

		$html = "<img alt='main image' class='shm-main-image' id='main_image' src='$u_ilink' data-width='{$image->width}' data-height='{$image->height}' data-filename=\"".substr($image->filename, 0, -4)."\">";
		$fn = substr($image->filename, 0, -4);
		$fn = str_replace("'", "&apos;", $fn);
		$page->add_block(new Block("<a href=\"ftag://$fn\">$fn</a>", $html, "main", 10));
	}
}
?>
