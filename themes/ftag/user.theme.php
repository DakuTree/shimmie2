<?php

class CustomUserPageTheme extends UserPageTheme {
	public function display_user_block(Page $page, User $user, $parts) {
		$h_name = html_escape($user->name);
		$html = 'Logged in as '.$h_name;
		foreach($parts as $part) {
			$html .= '<br><a href="'.$part["link"].'">'.$part["name"].'</a>';
		}
		$page->add_block(new Block("User Links", $html, "left", 90));
	}
}
?>
