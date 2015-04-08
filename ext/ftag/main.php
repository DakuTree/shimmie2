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

		if($config->get_int("db_version_ftag") < 1) {
			$config->set_bool("in_upgrade", true);
			$config->set_int("db_version_ftag", 1);

			log_info("upgrade", "Database_ftag at version 1");
			$config->set_bool("in_upgrade", false);
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $user, $page;

		if(!$user->is_anonymous()){
			$page->add_html_header(
'<script type="text/javascript">
	$(document).ready(function() {
		$(".thumb img").attr("src", function(){ return $(this).attr("data-base64"); });
	});
</script>');
		}
	}

	public function get_priority() {return 5;}
}

