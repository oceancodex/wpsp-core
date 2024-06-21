<?php

namespace WPSPCORE\Base;

use WPSPCORE\Objects\Http\HttpFoundation;

abstract class BaseNavigationMenu extends HttpFoundation {

	public ?\WP_Post $currentPost                 = null;
	public ?\WP_Post $currentPostParent           = null;
	public mixed     $prepareCurrentPostAndParent = false;

	/*
	 *
	 */

	public function __construct() {
		parent::__construct();
		$this->prepareCurrentPostAndParent();
	}

	/*
	 *
	 */

	public function prepareCurrentPostAndParent(): void {
		if ($this->prepareCurrentPostAndParent) {
			add_action('wp', function() {
				$this->prepareCurrentPost();
				$this->prepareCurrentPostParent();
			});
		}
	}

	public function prepareCurrentPost(): void {
		if (!$this->getCurrentPost()) {
			$currentPost = get_post(get_the_ID());
			$this->setCurrentPost($currentPost);
		}
	}

	public function prepareCurrentPostParent(): void {
		if (!$this->getCurrentPostParent()) {
			$currentPostParent = get_post_parent($this->getCurrentPost());
			$this->setCurrentPostParent($currentPostParent);
		}
	}

	/*
	 *
	 */

	public function getCurrentPost(): ?\WP_Post {
		return $this->currentPost;
	}

	public function setCurrentPost($currentPost): void {
		$this->currentPost = $currentPost;
	}

	public function getCurrentPostParent(): ?\WP_Post {
		return $this->currentPostParent;
	}

	public function setCurrentPostParent($currentPostParent): void {
		$this->currentPostParent = $currentPostParent;
	}

}