<?php
class Themelet extends BaseThemelet {
	public function build_thumb_html(Image $image) {
		global $config;

		$i_id = (int) $image->id;
		$h_view_link = make_link('post/view/'.$i_id);
		$h_thumb_link = $image->get_thumb_link();
		$h_tip = html_escape($image->get_tooltip());
		$h_tags = strtolower($image->get_tag_list());

		$fn = substr($image->filename, 0, -4);
		$basefn = base64_encode($fn);

		$extArr = array_flip(array('swf', 'svg')); //List of thumbless filetypes
		if(!isset($extArr[$image->ext])){
			$tsize = get_thumbnail_size($image->width, $image->height);
		}else{
			//Use max thumbnail size if using thumbless filetype
			$tsize = get_thumbnail_size($config->get_int('thumb_width'), $config->get_int('thumb_height'));
		}

		$custom_classes = "";
		if(class_exists("Relationships")){
			if(property_exists($image, 'parent_id') && $image->parent_id !== NULL){	$custom_classes .= "shm-thumb-has_parent ";	}
			if(property_exists($image, 'has_children') && $image->has_children == 'Y'){ $custom_classes .= "shm-thumb-has_child $image->has_children"; }
		}

		// $base64 = base64_encode(file_get_contents($image->get_thumb_filename()));
		return "<div class='thumb shm-thumb'>".
		       "	<a href=\"ftag://{$basefn}\" class='{$custom_classes}' data-tags='$h_tags' data-post-id='$i_id'>".
		       "		<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' data-base64='{$h_thumb_link}' src='data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs='>".
		       "	</a>".
			   "	<div>".
			   "		<a href='$h_view_link' class='thumb shm-thumb' data-tags='$h_tags' data-post-id='$i_id'>$fn</a>".
			   "	</div>".
		       "</div>";

		// $base64 = base64_encode(file_get_contents($image->get_thumb_filename()));
		// return "<div class='thumb shm-thumb'>".
		       // "	<a href=\"ftag://{$fn}\" class='{$custom_classes}' data-tags='$h_tags' data-post-id='$i_id'>".
		       // "		<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' height='{$tsize[1]}' width='{$tsize[0]}' data-base64='data:image/jpeg;base64,{$base64}' src='data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAVgBWAAD/4QBoRXhpZgAATU0AKgAAAAgABAEaAAUAAAABAAAAPgEbAAUAAAABAAAARgEoAAMAAAABAAIAAAExAAIAAAASAAAATgAAAAAAAABWAAAAAQAAAFYAAAABUGFpbnQuTkVUIHYzLjUuMTAA/9sAQwD//////////////////////////////////////////////////////////////////////////////////////9sAQwH//////////////////////////////////////////////////////////////////////////////////////8AAEQgAAQABAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AkooooA//2Q=='>".
		       // "	</a>".
			   // "	<div>".
			   // "		<a href='$h_view_link' class='thumb shm-thumb' data-tags='$h_tags' data-post-id='$i_id'>$fn</a>".
			   // "	</div>".
		       // "</div>";
	}
}
?>
