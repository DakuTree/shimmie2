<?php
/**
 * Name: Creation Date
 * Author: Angus Johnston <admin@codeanimu.net>
 * License: GPLv2
 * Description: Allows posts to have a searchable creation date.
 */

class CreationDate extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		// Create the database tables
		if ($config->get_int("ext_creationdate_version") < 1){
			$database->Execute("ALTER TABLE images ADD creation_date DATE NULL DEFAULT NULL, ADD INDEX (creation_date);");

			$config->set_int("ext_creationdate_version", 1);
			log_info("creationdate", "extension installed");
		}
	}

	public function onImageInfoSet(ImageInfoSetEvent $event) {
        global $user;
		if(isset($_POST['tag_edit__tags']) ? !preg_match('/cdate[=|:]/', $_POST["tag_edit__tags"]) : TRUE) { //Ignore tag_edit__parent if tags contain parent metatag
			if(isset($_POST["tag_edit__cdate"])) {
				if(preg_match("/^(([0-9]+[-|\/][0-9]+[-|\/][0-9]+)|none)$/", $_POST["tag_edit__cdate"], $matches)) {
					$creationDate = $matches[1];

					if($creationDate == "none"){
						$this->remove_cdate($event->image->id);
					}
					elseif(preg_match("/^([0-9]{2}[-|\/][0-9]{2}[-|\/][0-9]{4})$/", $creationDate)){ //DD-MM-YYYY
						if($date = DateTime::createFromFormat('d#m#Y', $creationDate)){
							$this->set_cdate($event->image->id, $date->format('Y/m/d'));
						}
					}
					elseif(preg_match("/^([0-9]{4}[-|\/][0-9]{2}[-|\/][0-9]{2})$/", $creationDate)){ //YYYY-MM-DD
						if($date = DateTime::createFromFormat('Y#m#d', $creationDate)){
							$this->set_cdate($event->image->id, $date->format('Y/m/d'));
						}
					}
				}elseif(preg_match("/^((19|20)\d\d)(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[01])$/", $_POST["tag_edit__cdate"], $matches)) {
					if($date = DateTime::createFromFormat('Y#m#d', $matches[1]."/".$matches[3]."/".$matches[4])){
						$this->set_cdate($event->image->id, $date->format('Y/m/d'));
					}
				}
			}else{
				$this->remove_cdate($event->image->id);
			}
		}
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();
		if(preg_match("/^created([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(([0-9]+[-|\/][0-9]+[-|\/][0-9]+)|any|none)$/", $event->term, $matches)) {
			$cmp = ltrim($matches[1], ":") ?: "=";
			$creationDate = $matches[2];

			if(preg_match("/^(any|none)$/", $creationDate)){
				$not = ($parentID == "any" ? "NOT" : "");
				$event->add_querylet(new Querylet("images.creation_date IS $not NULL"));
			}
			elseif(preg_match("/^([0-9]{2}[-|\/][0-9]{2}[-|\/][0-9]{4})$/", $creationDate)){ //DD-MM-YYYY
				if($date = DateTime::createFromFormat('d#m#Y', $creationDate)){
					$event->add_querylet(new Querylet("images.creation_date $cmp :cdate", array("cdate"=>$date->format('Y/m/d'))));
				}
			}
			elseif(preg_match("/^([0-9]{4}[-|\/][0-9]{2}[-|\/][0-9]{2})$/", $creationDate)){ //YYYY-MM-DD
				if($date = DateTime::createFromFormat('Y#m#d', $creationDate)){
					$event->add_querylet(new Querylet("images.creation_date $cmp :cdate", array("cdate"=>$date->format('Y/m/d'))));
				}
			}
		}
		else if(preg_match("/^order[=|:](creation_date|creation)[_]?(desc|asc)?$/i", $event->term, $matches)){
			global $order_sql;
			$ord = strtolower($matches[1]);
			$ord = "creation_date";
			$default_order_for_column = preg_match("/^(id|filename)$/", $matches[1]) ? "ASC" : "DESC";
			$sort = isset($matches[2]) ? strtoupper($matches[2]) : $default_order_for_column;
			$order_sql = "images.$ord $sort";
			$event->add_querylet(new Querylet("1=1")); //small hack to avoid metatag being treated as normal tag
		}
	}

	public function onTagTermParse(TagTermParseEvent $event) {
		$matches = array();

		if(preg_match("/^cdate[=|:](([0-9]+[-|\/][0-9]+[-|\/][0-9]+)|none)$/", $event->term, $matches)) {
			$creationDate = $matches[1];

			if($creationDate == "none"){
				$this->remove_cdate($event->id);
			}
			elseif(preg_match("/^([0-9]{2}[-|\/][0-9]{2}[-|\/][0-9]{4})$/", $creationDate)){ //DD-MM-YYYY
				if($date = DateTime::createFromFormat('d#m#Y', $creationDate)){
					$this->set_cdate($event->id, $date->format('Y/m/d'));
				}
			}
			elseif(preg_match("/^([0-9]{4}[-|\/][0-9]{2}[-|\/][0-9]{2})$/", $creationDate)){ //YYYY-MM-DD
				if($date = DateTime::createFromFormat('Y#m#d', $creationDate)){
					$this->set_cdate($event->id, $date->format('Y/m/d'));
				}
			}
		}

		if(!empty($matches)) $event->metatag = true;
	}

	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
		$event->add_part($this->theme->get_cdate_editor_html($event->image), 45);
	}

	private function set_cdate(/*int*/ $imageID, /*int*/ $creationDate){
		global $database;

		$database->execute("UPDATE images SET creation_date = :cde WHERE id = :iid", array("iid"=>$imageID, "cde"=>$creationDate));
	}

	private function remove_cdate(/*int*/ $imageID){
		global $database;

		$database->execute("UPDATE images SET creation_date = NULL WHERE id = :iid", array("iid"=>$imageID));
	}
}

