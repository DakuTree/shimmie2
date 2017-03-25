<?php

class CustomCommentListTheme extends CommentListTheme {
	public function display_image_comments(Image $image, $comments, $postbox) {
		global $page;
		$this->show_anon_id = true;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment);
		}
		if($postbox) {
			$html .= $this->build_postbox($image->id);
		}
		$page->add_block(new Block("Comments", $html, "mainhide", 30, "comment-list-image"));
	}
}
?>
