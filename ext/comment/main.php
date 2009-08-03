<?php
require_once "lib/akismet.class.php";

/* CommentPostingEvent {{{
 * CommentPostingEvent:
 *   $comment_id
 *
 * A comment is being deleted. Maybe used by spam
 * detectors to get a feel for what should be delted
 * and what should be kept?
 */
class CommentPostingEvent extends Event {
	var $image_id, $user, $comment;

	public function CommentPostingEvent($image_id, $user, $comment) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->comment = $comment;
	}
}
// }}}
/* CommentDeletionEvent {{{
 * CommentDeletionEvent:
 *   $comment_id
 *
 * A comment is being deleted. Maybe used by spam
 * detectors to get a feel for what should be delted
 * and what should be kept?
 */
class CommentDeletionEvent extends Event {
	var $comment_id;

	public function CommentDeletionEvent($comment_id) {
		$this->comment_id = $comment_id;
	}
}
// }}}
class CommentPostingException extends SCoreException {}

class Comment { // {{{
	public function Comment($row) {
		$this->owner_id = $row['user_id'];
		$this->owner_name = $row['user_name'];
		$this->comment =  $row['comment'];
		$this->comment_id =  $row['comment_id'];
		$this->image_id =  $row['image_id'];
		$this->poster_ip =  $row['poster_ip'];
		$this->posted =  $row['posted'];
	}

	public static function count_comments_by_user($user) {
		global $database;
		return $database->db->GetOne("SELECT COUNT(*) AS count FROM comments WHERE owner_id=?", array($user->id));
	}
} // }}}

class CommentList implements Extension {
	var $theme;
// event handler {{{
	public function receive_event(Event $event) {
		global $config, $database, $page, $user;

		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			global $config;
			$config->set_default_bool('comment_anon', true);
			$config->set_default_int('comment_window', 5);
			$config->set_default_int('comment_limit', 10);
			$config->set_default_int('comment_count', 5);

			if($config->get_int("ext_comments_version") < 2) {
				$this->install();
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("comment")) {
			if($event->get_arg(0) == "add") {
				if(isset($_POST['image_id']) && isset($_POST['comment'])) {
					try {
						$cpe = new CommentPostingEvent($_POST['image_id'], $user, $_POST['comment']);
						send_event($cpe);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".int_escape($_POST['image_id'])));
					}
					catch(CommentPostingException $ex) {
						$this->theme->display_error($page, "Comment Blocked", $ex->getMessage());
					}
				}
			}
			else if($event->get_arg(0) == "delete") {
				if($user->is_admin()) {
					// FIXME: post, not args
					if($event->count_args() == 3) {
						send_event(new CommentDeletionEvent($event->get_arg(1)));
						$page->set_mode("redirect");
						if(!empty($_SERVER['HTTP_REFERER'])) {
							$page->set_redirect($_SERVER['HTTP_REFERER']);
						}
						else {
							$page->set_redirect(make_link("post/view/".$event->get_arg(2)));
						}
					}
				}
				else {
					$this->theme->display_permission_denied($page);
				}
			}
			else if($event->get_arg(0) == "list") {
				$this->build_page($event->get_arg(1));
			}
		}

		if($event instanceof PostListBuildingEvent) {
			$cc = $config->get_int("comment_count");
			if($cc > 0) {
				$recent = $this->get_recent_comments($cc);
				if(count($recent) > 0) {
					$this->theme->display_recent_comments($page, $recent);
				}
			}
		}

		if($event instanceof DisplayingImageEvent) {
			$this->theme->display_comments(
					$page,
					$this->get_comments($event->image->id),
					$this->can_comment(),
					$event->image);
		}

		if($event instanceof ImageDeletionEvent) {
			$this->delete_comments($event->image->id);
		}
		// TODO: split akismet into a separate class, which can veto the event
		if($event instanceof CommentPostingEvent) {
			$this->add_comment_wrapper($event->image_id, $event->user, $event->comment, $event);
		}
		if($event instanceof CommentDeletionEvent) {
			$this->delete_comment($event->comment_id);
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Comment Options");
			$sb->add_bool_option("comment_anon", "Allow anonymous comments: ");
			$sb->add_label("<br>Limit to ");
			$sb->add_int_option("comment_limit");
			$sb->add_label(" comments per ");
			$sb->add_int_option("comment_window");
			$sb->add_label(" minutes");
			$sb->add_label("<br>Show ");
			$sb->add_int_option("comment_count");
			$sb->add_label(" recent comments on the index");
			$sb->add_text_option("comment_wordpress_key", "<br>Akismet Key ");
			$event->panel->add_block($sb);
		}

		if(is_a($event, 'SearchTermParseEvent')) {
			$matches = array();
			if(preg_match("/comments(<|>|<=|>=|=)(\d+)/", $event->term, $matches)) {
				$cmp = $matches[1];
				$comments = $matches[2];
				$event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM comments GROUP BY image_id HAVING count(image_id) $cmp $comments)"));
			}
			else if(preg_match("/commented_by=(.*)/i", $event->term, $matches)) {
				global $database;
				$user = User::by_name($matches[1]);
				if(!is_null($user)) {
					$user_id = $user->id;
				}
				else {
					$user_id = -1;
				}

				$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
			}
			else if(preg_match("/commented_by_userid=([0-9]+)/i", $event->term, $matches)) {
				$user_id = int_escape($matches[1]);
				$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
			}
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;

		// shortcut to latest
		if($config->get_int("ext_comments_version") < 1) {
			$database->create_table("comments", "
				id SCORE_AIPK,
				image_id INTEGER NOT NULL,
				owner_id INTEGER NOT NULL,
				owner_ip SCORE_INET NOT NULL,
				posted DATETIME DEFAULT NULL,
				comment TEXT NOT NULL,
				INDEX (image_id),
				INDEX (owner_ip),
				INDEX (posted),
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_comments_version", 2);
		}

		// ===
		if($config->get_int("ext_comments_version") < 1) {
			$database->Execute("CREATE TABLE comments (
				id {$database->engine->auto_increment},
				image_id INTEGER NOT NULL,
				owner_id INTEGER NOT NULL,
				owner_ip CHAR(16) NOT NULL,
				posted DATETIME DEFAULT NULL,
				comment TEXT NOT NULL,
				INDEX (image_id)
			) {$database->engine->create_table_extras}");
			$config->set_int("ext_comments_version", 1);
		}

		if($config->get_int("ext_comments_version") == 1) {
			$database->Execute("CREATE INDEX comments_owner_ip ON comments(owner_ip)");
			$database->Execute("CREATE INDEX comments_posted ON comments(posted)");
			$config->set_int("ext_comments_version", 2);
		}
	}
// }}}
// page building {{{
	private function build_page($current_page) {
		global $page;
		global $config;
		global $database;

		if(is_null($current_page) || $current_page <= 0) {
			$current_page = 1;
		}

		$threads_per_page = 10;
		$start = $threads_per_page * ($current_page - 1);

		$get_threads = "
			SELECT image_id,MAX(posted) AS latest
			FROM comments
			GROUP BY image_id
			ORDER BY latest DESC
			LIMIT ? OFFSET ?
			";
		$result = $database->Execute($get_threads, array($threads_per_page, $start));

		$total_pages = (int)($database->db->GetOne("SELECT COUNT(image_id) AS count FROM comments GROUP BY image_id") / 10);


		$this->theme->display_page_start($page, $current_page, $total_pages);

		$n = 10;
		while(!$result->EOF) {
			$image = Image::by_id($result->fields["image_id"]);
			$comments = $this->get_comments($image->id);
			$this->theme->add_comment_list($page, $image, $comments, $n, $this->can_comment());
			$n += 1;
			$result->MoveNext();
		}
	}
// }}}
// get comments {{{
	private function get_recent_comments() {
		global $config;
		global $database;
		$rows = $database->get_all("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				ORDER BY comments.id DESC
				LIMIT ?
				", array($config->get_int('comment_count')));
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}

	private function get_comments($image_id) {
		global $config;
		global $database;
		$i_image_id = int_escape($image_id);
		$rows = $database->get_all("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				WHERE comments.image_id=?
				ORDER BY comments.id ASC
				", array($i_image_id));
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}
// }}}
// add / remove / edit comments {{{
	private function is_comment_limit_hit() {
		global $user;
		global $config;
		global $database;

		// sqlite fails at intervals
		if($database->engine->name == "sqlite") return false;

		$window = int_escape($config->get_int('comment_window'));
		$max = int_escape($config->get_int('comment_limit'));

		$result = $database->Execute("SELECT * FROM comments WHERE owner_ip = ? ".
				"AND posted > date_sub(now(), interval ? minute)",
				Array($_SERVER['REMOTE_ADDR'], $window));
		$recent_comments = $result->RecordCount();

		return ($recent_comments >= $max);
	}

	private function hash_match() {
		return ($_POST['hash'] == $this->get_hash());
	}

	/**
	 * get a hash which semi-uniquely identifies a submission form,
	 * to stop spam bots which download the form once then submit
	 * many times.
	 *
	 * FIXME: assumes comments are posted via HTTP...
	 */
	public function get_hash() {
		return md5($_SERVER['REMOTE_ADDR'] . date("%Y%m%d"));
	}

	private function is_spam($text) {
		global $user;
		global $config;

		if(strlen($config->get_string('comment_wordpress_key')) == 0) {
			return false;
		}
		else {
			$comment = array(
				'author'       => $user->name,
				'email'        => $user->email,
				'website'      => '',
				'body'         => $text,
				'permalink'    => '',
				);

			$akismet = new Akismet(
					$_SERVER['SERVER_NAME'],
					$config->get_string('comment_wordpress_key'),
					$comment);

			if($akismet->errorsExist()) {
				return false;
			}
			else {
				return $akismet->isSpam();
			}
		}
	}

	private function can_comment() {
		global $config;
		global $user;
		return ($config->get_bool('comment_anon') || !$user->is_anonymous());
	}

	private function is_dupe($image_id, $comment) {
		global $database;
		return ($database->db->GetRow("SELECT * FROM comments WHERE image_id=? AND comment=?", array($image_id, $comment)));
	}

	private function add_comment_wrapper($image_id, $user, $comment, $event) {
		global $database;
		global $config;

		// basic sanity checks
		if(!$config->get_bool('comment_anon') && $user->is_anonymous()) {
			throw new CommentPostingException("Anonymous posting has been disabled");
		}
		else if(is_null(Image::by_id($image_id))) {
			throw new CommentPostingException("The image does not exist");
		}
		else if(trim($comment) == "") {
			throw new CommentPostingException("Comments need text...");
		}
		else if(strlen($comment) > 9000) {
			throw new CommentPostingException("Comment too long~");
		}

		// advanced sanity checks
		else if(strlen($comment)/strlen(gzcompress($comment)) > 10) {
			throw new CommentPostingException("Comment too repetitive~");
		}
		else if($user->is_anonymous() && !$this->hash_match()) {
			throw new CommentPostingException(
					"Comment submission form is out of date; refresh the ".
					"comment form to show you aren't a spammer~");
		}

		// database-querying checks
		else if($this->is_comment_limit_hit()) {
			throw new CommentPostingException("You've posted several comments recently; wait a minute and try again...");
		}
		else if($this->is_dupe($image_id, $comment)) {
			throw new CommentPostingException("Someone already made that comment on that image -- try and be more original?");
		}

		// rate-limited external service checks last
		else if($user->is_anonymous() && $this->is_spam($comment)) {
			throw new CommentPostingException("Akismet thinks that your comment is spam. Try rewriting the comment, or logging in.");
		}

		// all checks passed
		else {
			$database->Execute(
					"INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment) ".
					"VALUES(?, ?, ?, now(), ?)",
					array($image_id, $user->id, $_SERVER['REMOTE_ADDR'], $comment));
			$cid = $database->db->Insert_ID();
			log_info("comment", "Comment #$cid added");
		}
	}

	private function delete_comments($image_id) {
		global $database;
		$database->Execute("DELETE FROM comments WHERE image_id=?", array($image_id));
		log_info("comment", "Deleting all comments for Image #$image_id");
	}

	private function delete_comment($comment_id) {
		global $database;
		$database->Execute("DELETE FROM comments WHERE id=?", array($comment_id));
		log_info("comment", "Deleting Comment #$comment_id");
	}
// }}}
}
add_event_listener(new CommentList());
?>
