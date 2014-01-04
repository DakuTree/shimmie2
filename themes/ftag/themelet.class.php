<?php
class Themelet extends BaseThemelet {
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$i_id = (int) $image->id;
		$h_view_link = make_link('post/view/'.$i_id, $query);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$h_tags = strtolower($image->get_tag_list());
		$base = get_base_href();

		$fn = substr($image->filename, 0, -4);

		// If the file doesn't support thumbnail generation, show it at max size.
		if($image->ext === 'swf' || $image->ext === 'svg'){
			$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
		}
		else{
			$tsize = get_thumbnail_size($image->width, $image->height);
		}

		return "<div class='thumb shm-thumb'><a href=\"ftag://".$fn."\">".
		       "<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' class='lazy' data-original='$h_thumb_link' src='$base/lib/static/grey.gif'>".
		       "<noscript><img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' src='$h_thumb_link'>Test</img></noscript>".
			   "<div><a href='$h_view_link'  data-tags='$h_tags' data-post-id='$i_id'>$fn</a></div>".
			   "</a></div>";
	}
}
?>
