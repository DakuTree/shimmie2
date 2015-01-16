<?php
/*
 * Name: Image History
 * Author: Daku <admin@codeanimu.net>
 * Description: Keeps a record of all changes made to an image.
 *              Tag & Source history are implemented by default (toggleable)
 *
 *              TODO: Extension support.
 *                    Revert/undo tags.
 *                    Reset history.
 *                    Fix history.
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
					type VARCHAR(255) NOT NULL,
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
		global $page;

		//TODO: Find a better way to do this.

		if($event->page_matches("image_history/revert")) {
			if(isset($_POST['image_id'])){}
		}
		else if($event->page_matches("image_history/all")) {
			$this->theme->display_history_page($page, $this->get_entire_history());
		}
		else if($event->page_matches("image_history")) {
			if($image_id = int_escape($event->get_arg(0))){
				$this->theme->display_history_page($page, $this->get_history_from_id($image_id));
			}
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		global $user;
		if($user->can("bulk_edit_image_tag")) {
			$event->add_link("Image Changes", make_link("image_history/all/1"), 54);
		}
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		$event->add_part($this->theme->get_history_link_html($event->image->id), 20);
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Image History");

		//TODO: Fix formatting
		$sb->add_bool_option("ext_imagehistory_tags", "Enable tag history: ");
		$sb->add_bool_option("ext_imagehistory_source", "Enable source history: ");

		//FIXME: Check if log_db ext is enabled
		$sb->add_bool_option("ext_imagehistory_logdb_tags", "Enable tag history (log_db): ");
		$sb->add_bool_option("ext_imagehistory_logdb_source", "Enable source history (log_db): ");

		$event->panel->add_block($sb);
	}

	public function onTagSet(TagSetEvent $event) {
		global $config;
		if($config->get_bool("ext_imagehistory_tags")) $this->add_tag_history($event->image, $event->tags);
	}

	public function onSourceSet(SourceSetEvent $event) {
		global $config;
		if($config->get_bool("ext_imagehistory_source")) $this->add_source_history($event->image, $event->source);
	}

	private function add_tag_history(Image $image, /*array*/ $new_tags) {
		global $config, $database;

		/*
			Tag History uses up to 3 of the custom columns.
			custom1 (required) - unchanged tags
			custom2 (optional) - added tags
			custom3 (optional) - removed tags
		*/
		$old_tags = $image->get_tag_array();

		// if($new_tags == $old_tags) return; #CHECK: Do we want any blank tag changes to be recorded?

		$new_taglist = Tag::implode($new_tags);
		$old_taglist = Tag::implode($old_tags);

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

		$diff = array_merge(array("unchanged" => array_intersect($new_tags, $old_tags)), array("removed" => array_diff($old_tags, $new_tags)), array("added" => array_diff($new_tags, $old_tags)));

		//add a history event
		$this->events++;
		$database->execute("
			INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1, custom2, custom3)
			VALUES (?, ?, ?, ?, ?, ?)",
			array($history_id, $this->events, 'tags', Tag::implode($diff['unchanged']), (Tag::implode($diff['added']) ?: NULL), (Tag::implode($diff['removed']) ?: NULL)));

		if($config->get_bool("ext_imagehistory_logdb_tags")) log_debug("image_history", "TagHistory: [{$old_taglist}] -> [{$new_taglist}]", false, array("image_id" => $image->id));

		return;
	}

	private function add_source_history(Image $image, /*string*/ $new_source) {
		global $config, $database;

		/*
			Source history uses 1 of the custom columns.
			custom1 (required) - new source
			custom2 (optional) - old source
		*/
		$old_source = $image->source;

		// if($new_source == $old_source) return; #CHECK: Do we want any blank source changes to be recorded?

		$history_id = $this->get_history_id($image->id);

		//FIXME: This feels awfully inefficent. This should never have to be checked, assuming this ext is mandetory.
		//       The only time this would ever be needed is when the ext is first loaded, but that could be fixed via mass create row
		//       This also sets as current user, rather than uploader
		$entries = $database->get_one("SELECT COUNT(*) FROM ext_imagehistory WHERE image_id = ?", array($image->id));
		if($entries == 0){ //CHECK: Should this also check if old_tags is empty? Even if empty = tagme
			$this->events++;
			$database->execute("
				INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1)
				VALUES (?, ?, ?, ?)",
				array($history_id, $this->events, 'source', $old_source));
		}

		//add a history event
		$this->events++;
		$database->execute("
			INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1, custom2)
			VALUES (?, ?, ?, ?, ?)",
			array($history_id, $this->events, 'source', $new_source, $old_source));

		if($config->get_bool("ext_imagehistory_logdb_source")) log_debug("image_history", "SourceHistory: [{$old_source}] -> [{$new_source}]", false, array("image_id" => $image->id));

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

		$this->history_id = $database->get_last_insert_id(NULL);
	}

	public function get_history_from_id(/*int*/ $image_id) {
		global $database;
		$row = $database->get_all("
				SELECT ? AS image_id, eihe.*, eih.timestamp, eih.user_id, eih.user_ip, users.name
				FROM ext_imagehistory eih
				JOIN users ON eih.user_id = users.id
				JOIN ext_imagehistory_events eihe ON eihe.history_id = eih.id
				WHERE image_id = ?
				ORDER BY eih.id DESC, eihe.event_id DESC",
				array($image_id, $image_id));
		return ($row ? $row : array());
	}

	protected function get_entire_history() {
		global $database;
		$row = $database->get_all("
				SELECT eih.image_id, eihe.*, eih.timestamp, eih.user_id, eih.user_ip, users.name
				FROM ext_imagehistory eih
				JOIN users ON eih.user_id = users.id
				JOIN ext_imagehistory_events eihe ON eihe.history_id = eih.id
				ORDER BY eih.id DESC, eihe.event_id DESC");
		return ($row ? $row : array());
	}
}

