<?php
/*
 * Name: FTAG ("Folder Tagger")
 * Author: Daku
 * Description: Deals with all the extra FTAG related stuff
 * Visibility: admin
 */

class FTAG extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		$config->set_default_int("db_version_ftag", 1);

		if($config->get_int("db_version") < 2) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version", 2);

			log_info("upgrade", "Changing filename column to VARCHAR(255)");
			$database->alter_table_column_type('images', 'filename', 'VARCHAR(255)', 'NOT NULL');

			log_info("upgrade", "Creating index for filename");
			$database->execute("CREATE INDEX images_filename_idx ON images(filename)");

			log_info("upgrade", "Database_ftag at version 2");
			$config->set_bool("in_upgrade", false);
		}
	}

	public function get_priority() {return 5;}
}

