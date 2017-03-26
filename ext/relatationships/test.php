<?php
class RelationshipsTest extends ShimmiePHPUnitTestCase {
	public function testAddParentField() {
		$this->log_in_as_user();
	
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg",   "a b c");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "d e f");

		//Test if can add parent via parent id field
		$this->get_page("post/view/{$image_id_1}");
		$this->set_field("tag_edit__parent", $image_id_2);
		$this->click("Set");
		$this->assert_text("This post belongs to a parent post.");
		$this->get_page("post/view/{$image_id_2}");
		$this->assert_text("This post has a child post (post #{$image_id_1}).");

		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->log_out();
	}
	public function testAddParentMetatag() {
		$this->log_in_as_user();
	
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg",   "a b c");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "d e f");

		//Test if can add parent via parent id metatag
		$this->get_page("post/view/{$image_id_1}");
		$this->set_field("tag_edit__tags", "a b c parent:{$image_id_2}");
		$this->click("Set");
		$this->assert_text("This post belongs to a parent post.");
		$this->get_page("post/view/{$image_id_2}");
		$this->assert_text("This post has a child post (post #{$image_id_1}).");

		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->log_out();
	}
	public function testAddChildMetatag() {
		$this->log_in_as_user();
	
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg",   "a b c");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "d e f");

		//Test if can add child via metatag
		$this->get_page("post/view/{$image_id_1}");
		$this->set_field("tag_edit__tags", "a b c child:{$image_id_2}");
		$this->click("Set");
		$this->assert_text("This post has a child post (post #{$image_id_2}).");
		$this->get_page("post/view/{$image_id_2}");
		$this->assert_text("This post belongs to a parent post.");

		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->log_out();
	}

	public function testSearchMetatags() {
		$this->log_in_as_user();
	
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg",   "tagme");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "tagme parent:{$image_id_1}");

		//NOTE: All of these will only return a single result, so they will be redirected to image page.

		//Test if can find post via parent:ID metatag search
		$this->get_page("post/list/parent:{$image_id_1}/1");
		$this->assert_title("Image {$image_id_2}: tagme");

		//Test if can find post via parent:ANY metatag search
		$this->get_page("post/list/parent:any/1");
		$this->assert_title("Image {$image_id_2}: tagme");

		//Test if can find post via parent:NONE metatag search
		$this->get_page("post/list/parent:none/1");
		$this->assert_text("No Images Found");

		//Test if can find post via child:ANY metatag search
		$this->get_page("post/list/child:any/1");
		$this->assert_title("Image {$image_id_1}: tagme");

		//Test if can find post via child:NONE metatag search
		$this->get_page("post/list/child:none/1");
		$this->assert_text("Image {$image_id_2}: tagme");

		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->log_out();
	}
}

