<?php

class CustomPoolsTheme extends PoolsTheme {
	public function new_pool_composer(Page $page) {
		$title = (isset($_GET['title']) ? $_GET['title'] : '');
		$create_html = "
			".make_form("pool/create")."
				<table>
					<tr><td>Title:</td><td><input type='text' name='title' value=\"".htmlspecialchars($title)."\"></td></tr>
					<tr><td>Public?</td><td><input name='public' type='checkbox' value='Y' checked='checked'/></td></tr>
					<tr><td>Description:</td><td><textarea name='description'></textarea></td></tr>
					<tr><td colspan='2'><input type='submit' value='Create' /></td></tr>
				</table>
			</form>
		";

		$this->display_top(null, "Create Pool");
		$page->add_block(new Block("Create Pool", $create_html, "main", 20));
	}
}
?>
