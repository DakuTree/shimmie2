<?php

class ImageHistoryTheme extends Themelet {
	public function get_history_link_html(/*int*/ $image_id) {

		$html = make_form(make_link("image_history/{$image_id}"))."
				<input type='submit' value='View Image History'>
			</form>
		";
		return $html;
	}

	public function display_history_page(Page $page, /*array*/ $history) {
		global $user;

		$new_history = array();
		foreach($history as $value) { $new_history[$value['history_id']][] = $value; } //Isn't there a better way of doing this?

		$n = 0;

		//For now this is using the same layout Danbooru does for it's tag history (http://danbooru.donmai.us/post_versions)
		//If somebody can improve on this, be my guest.
		$history_html = "
			<table class='zebra' id='imagehistory'>
				<thead>
					<th width='5%'>Post</th>
					<th width='75px'>Date</th>
					<th width='10%'>User</th>
					".($user->can("view_ip") ? "<th width='10%'>IP Address</th>" : "")."
					<th>Changes</th>
					<th width='10%'></th> <!-- Actions -->
				</thead>
				<tbody>";

		foreach($new_history as $events) {
			//NOTE: We would use show_ip here, but it adds some links which may not work if IP ban ext is not enabled
			$uname = "<a href='".make_link("user/".url_escape($events[0]['name']))."'>".html_escape($events[0]['name'])."</a>";
			$uip   = ($user->can("view_ip") ? "<td>".$events[0]['user_ip']."</td>" : "");

			//TODO: Escape all this stuff for security
			$event_html = "
					<tr id='post-{$events[0]['image_id']}-{$events[0]['history_id']}'>
						<td>
							<a href='".make_link("post/view/{$events[0]['image_id']}")."'>{$events[0]['image_id']}.{$events[0]['history_id']}</a>
						</td>
						<td>
							<time datetime='".date("Y-m-d H:i:s", strtotime($events[0]['timestamp']))."' class='notimeago'>".date("Y-m-d H:i:s", strtotime($events[0]['timestamp']))."</time>
						</td>
						<td>
							{$uname}
						</td>
						{$uip}
						<td>";


			$tag_list   = array(); //unchanged list
			$tag_list_n = array(); //new additions
			$tag_list_r = array(); //new deletions
			foreach($events as $event) {
				//TODO: This will need change when extension support is implemented
				if($event['type'] == 'tags'){
					if(!empty($event['custom1'])) $tag_list   = array_merge($tag_list,   Tag::explode($event['custom1']));
					if(!empty($event['custom2'])) $tag_list_n = array_merge($tag_list_n, Tag::explode($event['custom2']));
					if(!empty($event['custom3'])) $tag_list_r = array_merge($tag_list_r, Tag::explode($event['custom3']));
				}elseif($event['type'] == 'source'){
					//TODO: This feels like a bad way of doing this...
					if(!empty($event['custom2']) && ($event['custom1'] !== $event['custom2'])){
						//source has been updated and old source isn't empty
						array_push($tag_list_n, "source:".$event['custom1']);
						array_push($tag_list_r, "source:".$event['custom2']);
					}else{
						array_push($tag_list, "source:".$event['custom1']);
					}
					//stuff
				}
			}
			foreach($tag_list_n as $tag){ $event_html .= "<ins><a href='".make_link("post/list/$tag/1")."'>{$tag}</a></ins>";  }
			foreach($tag_list_r as $tag){ $event_html .= "<del><a href='".make_link("post/list/$tag/1")."'>{$tag}</a></del>";  }
			foreach($tag_list as $tag){   $event_html .= "<span><a href='".make_link("post/list/$tag/1")."'>{$tag}</a></span> "; }

			$event_html .= "
						</td>
						<td>
							ACTIONS <!-- TODO: Sort this -->
						</td>
					</tr>";

			$history_html .= $event_html;
		}
		$history_html .= "
				</table>";

		$page->set_title('Image History');
		$page->set_heading('Image History');
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Image History", $history_html, "main", 10));
	}

}
