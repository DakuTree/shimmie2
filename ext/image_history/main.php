<?php
/*
 * Name: Image History
 * Author: Daku <admin@codeanimu.net>
 * Description: Keeps a record of all changes made to an image.
 *              Tag & Source history are implemented by default (toggleable)
 *              Extension support is planned.
 */

class ImageHistory extends Extension {
	public $history_id;
	public $events = 0;

	public function get_priority() {return 40;}

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		$config->set_default_bool("ext_imagehistory_tags",          true);
		$config->set_default_bool("ext_imagehistory_source",        true);
		$config->set_default_bool("ext_imagehistory_logdb_tags",   false);
		$config->set_default_bool("ext_imagehistory_logdb_source", false);

		$config->set_default_int("ext_imagehistory_version", -1);
		if($config->get_int("ext_imagehistory_version") < 1) {
			//TODO: Ask if want to import from tag_history/source_history extension
			#try {
				$database->create_table("ext_imagehistory", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					user_ip SCORE_INET NOT NULL,
					timestamp TIMESTAMP NOT NULL,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
					FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
					");
				$database->execute("CREATE INDEX ext_imagehistory_image_id ON ext_imagehistory(image_id)", array());
				$database->execute("CREATE INDEX ext_imagehistory_user_id  ON ext_imagehistory(user_id)", array());
				$database->execute("CREATE INDEX ext_imagehistory_user_ip  ON ext_imagehistory(user_ip)", array());

				//TODO: Possibly diff name than type?
				//The "custom" columns are not required to be used, but can be if the extension requires it.
				$database->create_table("ext_imagehistory_events", "
					history_id INTEGER NOT NULL,
					event_id INTEGER NOT NULL,
					type VARCHAR(255),
					custom1 TEXT,
					custom2 TEXT,
					custom3 TEXT,
					custom4 TEXT,
					custom5 TEXT,
					FOREIGN KEY (history_id) REFERENCES ext_imagehistory(id) ON DELETE CASCADE
					");
				$database->execute("CREATE UNIQUE INDEX ext_imagehistory_events_hideid ON ext_imagehistory_events(history_id, event_id)", array());
				$database->execute("CREATE INDEX ext_imagehistory_events_history_id    ON ext_imagehistory_events(history_id)", array());
				$database->execute("CREATE INDEX ext_imagehistory_events_type          ON ext_imagehistory_events(type)", array());
			#} catch (Exception $e) {
				//revert
				//throw error
			#}
			$config->set_int("ext_imagehistory_version", 1);
		}
	}

	// public function onAdminBuilding(AdminBuildingEvent $event) {
		// $this->theme->display_admin_block();
	// }

	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		if($event->page_matches("image_history/revert")) {
			if(isset($_GET['image_id'])){}
		}
		else if($event->page_matches("image_history/all")) {}
		else if($event->page_matches("image_history") && $event->count_args() == 0) {
			if(isset($_GET['image_id'])){
				//theme show image history
			}
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		//IDEA: Why isn't it possible to do something like $event->add_submit(ACTION, SUBMITVALUE, (optional)METHOD)
		$event->add_part("
			<form action='".make_link("image_history")."' method='GET'>
				<input type='hidden' name='image_id' value='{$event->image->id}'>
				<input type='submit' value='View Image History'>
			</form>
		", 20);
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Image History");

		$sb->add_bool_option("ext_imagehistory_tags", "Enable tag history: ");
		$sb->add_bool_option("ext_imagehistory_logdb_tags", "Enable tag history (log_db): ");
		// $sb->add_bool_option("ext_imagehistory_source", "Enable source history: ");
		// $sb->add_bool_option("ext_imagehistory_logdb_source", "Enable source history (log_db): ");

		$event->panel->add_block($sb);
	}

	public function onTagSet(TagSetEvent $event) {
		global $config;
		if($config->get_bool("ext_imagehistory_tags")) $this->add_tag_history($event->image, $event->tags);
	}

	private function add_tag_history(Image $image, /*array*/ $new_tags) {
		global $config, $database, $user;

		/*
			Tag History uses up to 3 of the custom columns.
			custom1 (required) - new taglist
			custom2 (optional) - added tags
			custom3 (optional) - removed tags
		*/
		$old_tags = $image->get_tag_array();
		$new_taglist = Tag::implode($new_tags);
		$old_taglist = Tag::implode($old_tags);
		//CHECK: Does the new_tags array need sorted?

		// if($new_tags == $old_tags) return; #CHECK: Do we want any blank tag changes to be recorded?

		$history_id = $this->get_history_id($image->id);

		//CHECK: This feels awfully inefficent. This should never have to be checked, assuming this ext is mandetory.
		//       The only time this would ever be needed is when the ext is first loaded, but that could be fixed via mass create row
		$entries = $database->get_one("SELECT COUNT(*) FROM ext_imagehistory WHERE image_id = ?", array($image->id));
		if($entries == 0){ //CHECK: Should this also check if old_tags is empty? Even if empty = tagme
			$this->events++;
			$database->execute("
				INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1)
				VALUES (?, ?, ?, ?)",
				array($history_id, $this->events, 'tags', $old_taglist));
		}

		$diff = array_merge(array("removed" => array_diff($old_tags, $new_tags)), array("added" => array_diff($new_tags, $old_tags)));

		//add a history event
		$this->events++;
		$database->execute("
			INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1, custom2, custom3)
			VALUES (?, ?, ?, ?, ?, ?)",
			array($history_id, $this->events, 'tags', $new_taglist, Tag::implode($diff['added']), Tag::implode($diff['removed'])));

		if($config->get_bool("ext_imagehistory_logdb_tags")) log_debug("image_history", "TagHistory: [{$old_taglist}] -> [{$new_taglist}]", false, array("image_id" => $image->id));

		return;
	}


	public function get_history_id($image_id, $create=FALSE) {
		if(is_null($this->history_id) || $create){
			//Multiple things can be set/changed at once on post pages
			//To make things lessy messy on the image history page, these are grouped by a history id.
			$this->generate_history_id($image_id);
		}
		return $this->history_id;
	}

	private function generate_history_id($image_id) {
		global $database, $user;

		$database->execute("
			INSERT INTO ext_imagehistory (image_id, user_id, user_ip, timestamp)
			VALUES (?, ?, ?, current_timestamp)",
			array($image_id, $user->id, $_SERVER['REMOTE_ADDR']));

		$this->history_id = $database->get_last_insert_id();
	}
}

