<?php

class ImageHistoryTheme extends Themelet {
	public function display_history_page(Page $page, /*int*/ $image_id, /*array*/ $history) {
		global $user;

		//FIXME: Due to the way the TagTermParseEvent currently works, metatags aren't stripped before TagSetEvent.
		//       This means metatags will show in the tag history.

		//TODO: USE TAGS INSTEAD OF EACH HAVING DIFF ROW
		//example: "tag_a tag_b source:this_is_a_source pool:1" etc etc.

		foreach($history as $value) { $new_history[$value['history_id']][] = $value; } //Isn't there a better way of doing this?

		$history_html = "";
		$n = 0;

		foreach($new_history as $events) {
			//TODO: Escape all this stuff for security

			//NOTE: We would use show_ip here, but it adds some links which may not work if IP ban ext is not enabled
			$uname = "<a href='".make_link("user/".url_escape($events[0]['name']))."'>".html_escape($events[0]['name'])."</a>".($user->can("view_ip") ? " (".$events[0]['user_ip'].")" : "");
			$start_html = "
				<div id='{$events[0]['history_id']}'>
					<header>
						<span>Revision: {$events[0]['history_id']}</span> | <span>{$events[0]['timestamp']}</span> | <span>{$uname}</span>
					</header>
					<div class='image_changes'>";

			$event_html = "";
			foreach($events as $event) {
				//TODO: Change this depending on type
				$event_html .= "
					<div>
						{$event['type']} : {$event['custom1']} | <ins>{$event['custom2']}</ins> | <del>{$event['custom3']}</del>
					</div>";
			}

			$end_html = "
					</div>
				</div>";
			$history_html .= $start_html.$event_html.$end_html;
		}

		$page->set_title('Image History: '.$image_id);
		$page->set_heading('Image History: '.$image_id);
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Image History", $history_html, "main", 10));
	}

}
