<?php
/*
 * Name: Image History
 * Author: Daku <admin@codeanimu.net>
 * Description: Keeps a record of all changes made to an image.
 *              Tag & Source history are implemented by default (toggleable)
 *
 *              TODO: Extension support. Rating, Parent, Pools etc.
 *                    Reset history.
 *                    Fix history.
 *                    Import from tag/source history.
 *                    Image deletion history? (Unsure how possible this would be as image history is removed on deletion)
 *                    User restrictions.
 *                    Bulk Revert (See tag_history)
 */

class ImageHistory extends Extension {
	public $history_id; // used for upload
	public $history_ids = [];
	public $events = 0;

	public function get_priority() {return 40;}

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		$config->set_default_bool("ext_imagehistory_tags",          true);
		$config->set_default_bool("ext_imagehistory_source",        true);
		$config->set_default_bool("ext_imagehistory_logdb_tags",   false);
		$config->set_default_bool("ext_imagehistory_logdb_source", false);
		$config->set_default_int( "ext_imagehistory_historyperpage",  50);

		$config->set_default_int("ext_imagehistory_version", -1);
		if($config->get_int("ext_imagehistory_version") < 1) {
			//TODO: Ask if want to import from tag_history/source_history extension
			#try {
				#begin transaction
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
				#commit
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
		//TODO: Find a better way to do this.
		if($event->page_matches("image_history/revert") && $user->can("edit_image_tag")) {
			$image_id   = int_escape($event->get_arg(0));
			$history_id = int_escape($event->get_arg(1));

			$this->revert_history($image_id, $history_id);

			$page->set_mode("redirect");
			$page->set_redirect(make_link("image_history/all"));
		}
		else if($event->page_matches("image_history/all")) {
			$pageN = int_escape($event->get_arg(0)) ?: 1;
			$this->theme->display_history_page($page, $this->get_entire_history($pageN), $pageN, "image_history/all");
		}
		else if($event->page_matches("image_history")) {
			if($image_id = int_escape($event->get_arg(0))){
				$pageN = int_escape($event->get_arg(1)) ?: 1;
				$this->theme->display_history_page($page, $this->get_history_from_id($image_id, $pageN), $pageN, "image_history/{$image_id}");
			}
		}

		//If tag_history or source_history extensions are enabled, show warning
		if(class_exists("Tag_History") || class_exists("Source_History")) {
			$this->theme->display_conflict_warning();
		}
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		$event->add_link("Image Changes", make_link("image_history/all"), 54);
	}

	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		$event->add_part($this->theme->get_history_link_html($event->image->id), 20);
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Image History");

		//CHECK: Is there a neater way to do breaks? This feels ugly.
		$sb->add_bool_option("ext_imagehistory_tags", "Enable tag history: ");
		$sb->add_label("<br />");
		$sb->add_bool_option("ext_imagehistory_source", "Enable source history: ");
		$sb->add_label("<br />");

		if(class_exists('LogDatabase')) {
			$sb->add_label("<br />");
			$sb->add_bool_option("ext_imagehistory_logdb_tags", "Enable tag history (log_db): ");
			$sb->add_label("<br />");
			$sb->add_bool_option("ext_imagehistory_logdb_source", "Enable source history (log_db): ");
			$sb->add_label("<br />");
		}

		$sb->add_label("<br />");
		$sb->add_int_option("ext_imagehistory_historyperpage", "Total history elements per page: ");

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

	//fix for bulk_add, need to make sure history id is different for each image
	public function onDataUpload(DataUploadEvent $event) {
		$this->history_ids = [];
	}

	/** add history functions **/
	private function initPostHistory(Image $image) {
		global $config;
		//This is used when a post doesn't have any image history and the history page is loaded.
		//This can happen when the extension is loaded on an install that already has posts.

		//TODO: Init post history at upload date, to avoid it looking like new post in /all

		//TODO: Send history event instead.
		if($config->get_bool("ext_imagehistory_tags"))   $this->add_tag_history($image, $image->get_tag_array(), TRUE);
		if($config->get_bool("ext_imagehistory_source")) $this->add_source_history($image, $image->source, TRUE);
	}

	private function add_tag_history(Image $image, /*array*/ $new_tags, /*bool*/ $firstRun=FALSE) {
		global $config, $database;

		/*
			Tag History uses up to 3 of the custom columns.
			custom1 (required) - unchanged tags
			custom2 (optional) - added tags
			custom3 (optional) - removed tags
		*/
		$old_tags = (!$firstRun ? $image->get_tag_array() : array());

		// if($new_tags == $old_tags) return; #CHECK: Do we want any blank tag changes to be recorded?

		$new_taglist = Tag::implode($new_tags);
		$old_taglist = Tag::implode($old_tags);

		$history_id = $this->get_history_id($image->id);

		//CHECK: This feels awfully inefficent. This should never have to be checked, assuming this ext is mandetory.
		//       The only time this would ever be needed is when the ext is first loaded, but that could be fixed via mass create row
		$entries = $database->get_one("SELECT COUNT(*) FROM ext_imagehistory WHERE image_id = ?", array($image->id));
		if($entries == 0){ //CHECK: Should this also check if old_tags is empty? Even if empty = tagme
			$this->history_ids[$image->id]['events']++;
			$database->execute("
				INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1)
				VALUES (?, ?, ?, ?)",
				array($history_id, $this->history_ids[$image->id]['events'], 'tags', $old_taglist)
			);
		}

		$diff = array_merge(
			array("unchanged" => array_intersect($new_tags, $old_tags)),
			array("removed"   => array_diff($old_tags, $new_tags)),
			array("added"     => array_diff($new_tags, $old_tags))
		);

		//add a history event
		$this->history_ids[$image->id]['events']++;
		$database->execute("
			INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1, custom2, custom3)
			VALUES (?, ?, ?, ?, ?, ?)",
			array($history_id, $this->history_ids[$image->id]['events'], 'tags', Tag::implode($diff['unchanged']), (Tag::implode($diff['added']) ?: NULL), (Tag::implode($diff['removed']) ?: NULL))
		);

		if($config->get_bool("ext_imagehistory_logdb_tags")) log_debug("image_history", "TagHistory: [{$old_taglist}] -> [{$new_taglist}]", false, array("image_id" => $image->id));

		return;
	}

	private function add_source_history(Image $image, /*string*/ $new_source, /*bool*/ $firstRun=FALSE) {
		global $config, $database;

		/*
			Source history uses 1 of the custom columns.
			custom1 (required) - new source
			custom2 (optional) - old source
		*/
		$old_source = (!$firstRun ? ($image->source ?: '') : '');

		// if($new_source == $old_source) return; #CHECK: Do we want any blank source changes to be recorded?

		$history_id = $this->get_history_id($image->id);

		//FIXME: This feels awfully inefficent. This should never have to be checked, assuming this ext is mandetory.
		//       The only time this would ever be needed is when the ext is first loaded, but that could be fixed via mass create row
		//       This also sets as current user, rather than uploader
		$entries = $database->get_one("SELECT COUNT(*) FROM ext_imagehistory WHERE image_id = ?", array($image->id));
		if($entries == 0){ //CHECK: Should this also check if old_tags is empty? Even if empty = tagme
			$this->history_ids[$image->id]['events']++;
			$database->execute("
				INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1)
				VALUES (?, ?, ?, ?)",
				array($history_id, $this->history_ids[$image->id]['events'], 'source', $old_source)
			);
		}

		//add a history event
		$this->history_ids[$image->id]['events']++;
		$database->execute("
			INSERT INTO ext_imagehistory_events (history_id, event_id, type, custom1, custom2)
			VALUES (?, ?, ?, ?, ?)",
			array($history_id, $this->history_ids[$image->id]['events'], 'source', $new_source, $old_source)
		);

		if($config->get_bool("ext_imagehistory_logdb_source")) log_debug("image_history", "SourceHistory: [{$old_source}] -> [{$new_source}]", false, array("image_id" => $image->id));

		return;
	}

	/** get history_id functions **/
	public function get_history_id($image_id, $create=FALSE) {
		if(!in_array($image_id, $this->history_ids) || $create) {
			//Multiple things can be set/changed at once on post pages
			//To make things less messy on the image history page, these are grouped by a history id.
			$this->generate_history_id($image_id);
		}

		return $this->history_ids[$image_id]['id'];
	}

	private function generate_history_id($image_id) {
		global $database, $user;

		$database->execute("
			INSERT INTO ext_imagehistory (image_id, user_id, user_ip, timestamp)
			VALUES (?, ?, ?, current_timestamp)",
			array($image_id, $user->id, $_SERVER['REMOTE_ADDR'])
		);

		$historyID = $database->get_last_insert_id('ext_imagehistory_id_seq');
		$this->history_ids[$image_id] = ['id' => $historyID, 'events' => 0];
	}

	/** get_history functions **/
	public function get_history_from_id(/*int*/ $image_id, $pageN=1) {
		global $config, $database;

		$limit = $config->get_int("ext_imagehistory_historyperpage");
		$offset = (($pageN-1) * $limit);

		$data = array();
		$total_pages = 0;

		//CHECK: Unsure if this is safe, it just seemed like the best way to avoid duplicate code..
		while(TRUE) {
			$row = $database->get_all("
				SELECT :id AS image_id, eihe.*, eih.timestamp, eih.user_id, eih.user_ip, users.name
				FROM (SELECT * FROM ext_imagehistory WHERE image_id = :id ORDER BY id DESC LIMIT :limit OFFSET :offset) eih
				JOIN users ON eih.user_id = users.id
				JOIN ext_imagehistory_events eihe ON eihe.history_id = eih.id
				ORDER BY eih.id DESC, eihe.event_id DESC",
				array("id" => $image_id, "limit"=>$limit, "offset"=>$offset)
			);

			if($row) {
				//history exists, set variables then end loop
				$data = $row;
				$total_pages = ceil($database->get_one("SELECT COUNT(*)	FROM ext_imagehistory WHERE image_id = :id", array("id" => $image_id)) / $limit);
				break;
			} else {
				//history doesn't exists, check if image_id is valid
				if($image = Image::by_id($image_id)) {
					//image_id is valid, init history then repeat loop to grab history
					$this->initPostHistory($image);
					continue;
				} else {
					//image_id is not valid, end loop
					break;
				}
			}
		}

		return ($row ? array("data"=>$data, "total_pages"=>$total_pages) : array("data"=>$data, "total_pages"=>$total_pages));
	}

	protected function get_entire_history($pageN=1) {
		global $config, $database;

		$limit = $config->get_int("ext_imagehistory_historyperpage");
		$offset = (($pageN-1) * $limit);

		$row = $database->get_all("
			SELECT eih.image_id, eihe.*, eih.timestamp, eih.user_id, eih.user_ip, users.name
			FROM (SELECT * FROM ext_imagehistory ORDER BY id DESC LIMIT :limit OFFSET :offset) eih
			JOIN users ON eih.user_id = users.id
			JOIN ext_imagehistory_events eihe ON eihe.history_id = eih.id
			ORDER BY eih.id DESC, eihe.event_id DESC",
			array("limit"=>$limit, "offset"=>$offset)
		);

		$total_pages = ceil($database->get_one("SELECT COUNT(*)	FROM ext_imagehistory") / $limit);

		return ($row ? array("data"=>$row, "total_pages"=>$total_pages) : array("data"=>array(), "total_pages"=>0));
	}

	/** revert_history functions **/
	protected function revert_history(/*int*/ $image_id, /*int*/ $history_id) {
		global $database;

		if($image = Image::by_id($image_id)) {
			$rows = $database->get_all("
				SELECT `type`, custom1, custom2, custom3
				FROM ext_imagehistory_events
				WHERE history_id = :history_id",
				array("history_id"=>$history_id)
			);

			$tagArray = array();
			foreach($rows as $row) {
				switch($row['type']) {
					case 'tags':
						$tagArray = array_merge($tagArray, explode(" ", $row['custom1'])); //unchanged tags
						$tagArray = array_merge($tagArray, explode(" ", $row['custom2'])); //new tags
						break;
					case 'source':
						$row['custom1'] = "source:".$row['custom1'] ? : "";
						$row['custom2'] = "source:".$row['custom2'] ? : "";
						$tagArray = array_merge($tagArray, explode(" ", $row['custom1'] ?: $row['custom2'])); //source, if exists
						break;
				}
			}

			$tagList = implode(" ", array_filter($tagArray));
			if(!empty($tagList)) { send_event(new TagSetEvent($image, $tagList)); }

			return TRUE;
		} else {
			return FALSE;
		}
	}
}
