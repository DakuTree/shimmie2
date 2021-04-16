<?php
/*
 * Name: FTAG ('Folder Tagger')
 * Author: Daku
 * Description: Deals with all the extra FTAG related stuff
 * Visibility: admin
 */

class FTAG extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		if ($config->get_int('db_version_ftag') < 1) {
			$config->set_bool('in_upgrade', TRUE);
			$config->set_int('db_version_ftag', 1);

			log_info('upgrade', 'Database_ftag at version 1');
			$config->set_bool('in_upgrade', FALSE);
		}


		if ($config->get_int('db_version_ftag') < 2) {
			$config->set_bool('in_upgrade', TRUE);
			$config->set_int('db_version_ftag', 2);

			log_info('ftag', 'Changing filename column to VARCHAR(255)');
			if ($database->get_driver_name() === 'mysql') {
				$database->execute('ALTER TABLE images MODIFY COLUMN filename VARCHAR(255)');
			} else if ($database->get_driver_name() === 'pgsql') {
				$database->execute('ALTER TABLE images ALTER COLUMN filename SET DATA TYPE VARCHAR(255)');
			}
			log_info('ftag', 'Creating index for filename');
			$database->execute('CREATE INDEX images_filename_idx ON images(filename)');
			log_info('ftag', 'Database_ftag at version 2');
			$config->set_bool('in_upgrade', FALSE);
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $user, $page;

		if (!$user->is_anonymous()) {
			$page->add_html_header(
				"<script type='text/javascript'>
						$(document).ready(function() {
							$('.thumb img').attr('src', function(){ return $(this).attr('data-base64'); });
						});
					</script>");
		}
	}

	public function onTagTermParse(TagTermParseEvent $event) {
		$matches = array();

		if(preg_match("/^(?:util:)?pages[=|:]([0-9]+)$/", $event->term, $matches)) {
			$pageCount = $matches[1];
			$this->setPageCount($event->id, $pageCount);
		}

		if(!empty($matches)) $event->metatag = true;
	}
	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();

		if(preg_match('/^status[=|:](\d)$/', $event->term, $matches)) {
			$status = $matches[1];

			$event->add_querylet(new Querylet('images.status = :status', array('status' =>$status)));
		}
		else if (preg_match("/^pages([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
			$cmp = ltrim($matches[1], ':') ?: '=';
			$event->add_querylet(new Querylet("page_count $cmp :pages", array('pages' => int_escape($matches[2]))));
		}
	}

	private function setPageCount(/*int*/ $imageID, /*int*/ $pageCount){
		global $database;

		$database->execute("UPDATE images SET page_count = :pc WHERE id = :iid", array("iid"=> $imageID, "pc"=> $pageCount));
	}

	public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event) {
		$event->add_part($this->theme->get_parent_editor_html($event->image), 45);
	}

	public function get_priority() { return 5; }
}
