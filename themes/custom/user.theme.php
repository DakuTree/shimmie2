<?php
class CustomUserPageTheme extends UserPageTheme {
	public function display_user_block(Page $page, User $user, $parts) {
		$html = "<div id='linkblock'>";
		foreach($parts as $part) {
			$html .= "<a href='{$part["link"]}'>{$part["name"]}</a> - ";
		}
		$html = substr($html, 0, -3);
		$html = "<div id='style-config'>";

		$html .= "</div>";

		$page->add_block(new Block(null, $html, "head", 90));
	}

	//Preferably we'd just link to the login page, but it seems a bit pointless since other links don't show if user not logged in
	public function display_login_block(Page $page) {
		global $config;
		$html = '
			'.make_form(make_link("user_admin/login"))."
				<label for='user'>Name</label><input id='user' type='text' name='user'>
				<label for='pass'>Password</label><input id='pass' type='password' name='pass'>
				<input type='submit' value='Log In'>".
		        ($config->get_bool("login_signup_enabled") ? "<small><a href='".make_link("user_admin/create")."'><button type='button'>Signup</button></a></small>" : "")."
			</form>
		";
		$page->add_block(new Block("Login", $html, "head", 90));
	}
}

