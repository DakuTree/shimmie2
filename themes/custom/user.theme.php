<?php

class UserPageTheme extends Themelet {
	public function display_user_block(Page $page, User $user, $parts) {
		$h_name = html_escape($user->name);
		$html = 'Logged in as '.$h_name;
		foreach($parts as $part) {
			$html .= '<br><a href="'.$part["link"].'">'.$part["name"].'</a>';
		}
		$page->add_block(new Block("User Links", $html, "left", 90));
	}

	public function display_login_block(Page $page) {
		global $config;
		$html = '
			'.make_form(make_link("user_admin/login"))."
				<table style='width: 100%;' class='form'>
					<tbody>
						<tr>
							<th><label for='user'>Name</label></th>
							<td><input id='user' type='text' name='user'></td>
						</tr>
						<tr>
							<th><label for='pass'>Password</label></th>
							<td><input id='pass' type='password' name='pass'></td>
						</tr>
					</tbody>
					<tfoot>
						<tr><td colspan='2'><input type='submit' value='Log In'></td></tr>
					</tfoot>
				</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
		}
		$page->add_block(new Block("Login", $html, "left", 90));
	}
}

