<?php
class ImageHistoryTest extends ShimmiePHPUnitTestCase {
	public function setUp() {
		parent::setUp();
		$this->resetState();
	}

	public function testImageHistory() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "tag_a tag_b source:http://example.com");

		//since we use css to add the + and - characters, we need to check the html instead :\

		//test if upload properly adds history
		$this->get_page("image_history/$image_id");
		$this->assert_text_regexp('~'.
			'\\Q<td>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/source:http:/example.com/1\'>source:http://example.com</a></ins>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/tag_a/1\'>tag_a</a></ins>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/tag_b/1\'>tag_b</a></ins>'.
			'</td>\\E'.
		'~');

		//test if history is updated properly after additional changes
		$this->resetState();
		$this->tag_image($image_id, "tag_c http://foo.bar");
		$this->get_page("image_history/$image_id");
		$this->assert_text_regexp('~'.
			'\\Q<td>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/http:/foo.bar/1\'>http://foo.bar</a></ins>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/tag_c/1\'>tag_c</a></ins>'.
				'<del><a href=\'\\E.*?\\Q?q=/post/list/tag_a/1\'>tag_a</a></del>'.
				'<del><a href=\'\\E.*?\\Q?q=/post/list/tag_b/1\'>tag_b</a></del>'.
			'</td>\\E'.
		'~');
		//test if old history is still showing too.
		$this->assert_text_regexp('~'.
			'\\Q<td>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/source:http:/example.com/1\'>source:http://example.com</a></ins>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/tag_a/1\'>tag_a</a></ins>'.
				'<ins><a href=\'\\E.*?\\Q?q=/post/list/tag_b/1\'>tag_b</a></ins>'.
			'</td>\\E'.
		'~');

		//post a new image, make sure both histories show on /image_history/all
		$this->resetState();
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "foo bar");
		$this->get_page("image_history/all");
		$this->assert_text('post/view/'.$image_id);
		$this->assert_text('post/view/'.$image_id_2);
	}

	private function resetState() {
		//ImageHistory doesn't play nice with tests due to the way it works.
		//We need to manually reset the history_id/events for each test to avoid multiple events being merged into one.

		//TODO: This entire chunk of code feels wrong. Is there a better way to do it?
		global $_shm_event_listeners;
		$neededObject = array_filter(
			$_shm_event_listeners['InitExtEvent'],
			function ($class) {
				return get_class($class) == 'ImageHistory';
			}
		);
		$imageHistory = reset($neededObject);
		$key = key($neededObject);

		$imageHistory->history_id = NULL;
		$imageHistory->events     = 0;
		$_shm_event_listeners['InitExtEvent'][$key] = $imageHistory;
	}
}

