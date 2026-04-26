<?php

namespace WPSPCORE\App\WordPress\RewriteFrontPages;

use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

abstract class BaseRewriteFrontPage extends BaseInstances {

	use RouteTrait;

	public $path                     = null;
	public $fullPath                 = null;
	public $rewriteIdent             = null;
	public $useTemplate              = false;
	public $rewriteFrontPageSlug     = 'rewrite-front-pages';
	public $rewriteFrontPagePostType = 'page';
	public $callback_function        = null;

	/**
	 * Khởi tạo sau construct
	 */
	public function afterConstruct() {
		$this->callback_function = $this->extraParams['callback_function'];
		$this->overridePath($this->extraParams['path']);
		$this->overrideFullPath($this->extraParams['full_path']);
	}

	/**
	 * Override path nếu được truyền từ ngoài
	 */
	private function overridePath($path = null) {
		if ($path && !$this->path) {
			$this->path = $path;
		}
	}

	/**
	 * Override fullPath nếu được truyền từ ngoài
	 */
	private function overrideFullPath($fullPath = null) {
		if ($fullPath && !$this->fullPath) {
			$this->fullPath = $fullPath;
		}
	}

	/**
	 * Hàm init chính: đăng ký rewrite + hook lifecycle
	 */
	public function init($path = null, $fullPath = null) {
		$path     = $this->path ?? $path;
		$fullPath = $this->fullPath ?? $fullPath;

		/**
		 * Chuẩn hóa regex path
		 * - luôn có ^ ở đầu
		 * - luôn có $ ở cuối
		 */
		$regexPrefix = '^';
		$regexSuffix = '$';
		$regexPath   = $this->funcs->_regexPath($fullPath);
		$regexPath   = !str_starts_with($regexPath, $regexPrefix) ? $regexPrefix . $regexPath : $regexPath;
		$regexPath   = !str_ends_with($regexPath, $regexSuffix) ? $regexPath . $regexSuffix : $regexPath;

		$appShortName = $this->funcs->_config('app.short_name');

		if ($path && $fullPath) {
			/**
			 * Parse tất cả capture group (...) trong regex
			 * để map sang query vars: _rewrite_group_1, _rewrite_group_2, ...
			 */
			preg_match_all('/\(.+?\)/iu', $regexPath, $groupMatches);

			$stringMatches = '';

			if (!empty($groupMatches) && !empty($groupMatches[0])) {
				foreach ($groupMatches[0] as $groupMatchKey => $groupMatch) {
					$stringMatches .= '&' . $appShortName . '_rewrite_group_' . ($groupMatchKey + 1) . '=$matches[' . ($groupMatchKey + 1) . ']';
				}
			}

			/**
			 * Thêm rewrite ident để nhận diện route
			 */
			if ($this->rewriteIdent) {
				$stringMatches .= '&' . $appShortName . '_rewrite_ident=' . $this->rewriteIdent;
			}

			/**
			 * Đăng ký rewrite rule
			 *
			 * Ví dụ:
			 * stories/abc/chapter
			 * → index.php?pagename=rewrite-front-pages&...
			 */
			add_rewrite_rule($regexPath, 'index.php?post_type=' . $this->rewriteFrontPagePostType . '&pagename=' . $this->rewriteFrontPageSlug . '&is_rewrite=true' . $stringMatches, 'top');

			/**
			 * Lấy request path hiện tại
			 */
			$requestPath = ltrim($this->request->getPathInfo(), '/\\');

			/**
			 * Nếu URL hiện tại không match regex → bỏ qua
			 * (tránh hook không cần thiết)
			 */
			if (!preg_match('/' . $regexPath . '/iu', $requestPath, $matches)) return;

			/**
			 * Xử lý khi truy cập URL rewrite ở frontend.
			 */
			if (!is_admin()) {
				/**
				 * -----------------------------
				 * 1. parse_request (input layer)
				 * -----------------------------
				 *
				 * - Inject lại query_vars
				 * - Xóa các var gây conflict (attachment, name...)
				 */
				add_action('parse_request', function($wp) use ($fullPath, $requestPath, $regexPath, $stringMatches, $matches, $appShortName) {
					unset($wp->query_vars['attachment']);
					unset($wp->query_vars['page']);
					unset($wp->query_vars['name']);

					$wp->query_vars['is_rewrite'] = true;
//					$wp->query_vars['page']       = $this->rewriteFrontPageSlug;
					// ép WP hiểu đây là page hợp lệ
					$wp->query_vars['pagename']   = $this->rewriteFrontPageSlug;
					$wp->query_vars['post_type']  = $this->rewriteFrontPagePostType;

					// inject params từ regex
					foreach ($matches as $k => $v) {
						if ($k === 0) continue;
						$wp->query_vars[$appShortName . '_rewrite_group_' . $k] = $v;
					}
				}, 100);

				/**
				 * -----------------------------
				 * 2. pre_get_posts (query layer)
				 * -----------------------------
				 *
				 * - Chuẩn hóa main query
				 * - Fix lỗi WP tự set 404
				 */
				add_action('pre_get_posts', function ($query) {
					if ($query->get('is_rewrite')) {
						$query->set('post_type', 'page');
						$query->set('pagename', $this->rewriteFrontPageSlug);
						// ép WP hiểu đúng context
						$query->is_page = true;
						$query->is_singular = true;
						$query->is_home = false;
						$query->is_404 = false;
					}
				}, 1);

				/**
				 * ----------------------------------
				 * 3. template_redirect (final guard)
				 * ----------------------------------
				 *
				 * - Nếu có plugin/core set lại 404
				 * - thì override lần cuối
				 */
				add_action('template_redirect', function () {
					if (get_query_var('is_rewrite')) {
						global $wp_query;

						if ($wp_query->is_404) {
							$wp_query->is_404 = false;
							$wp_query->is_page = true;
							$wp_query->is_singular = true;
							$wp_query->is_home = false;
						}
					}
				}, 1);

				/**
				 * ----------------------------------
				 * 4. redirect_canonical
				 * ----------------------------------
				 *
				 * - Ngăn WP redirect về URL canonical
				 * - nếu không sẽ phá custom URL
				 */
				add_filter('redirect_canonical', function ($redirect, $request) {
					if (get_query_var('is_rewrite')) {
						return false;
					}
					return $redirect;
				}, 10, 2);

				/**
				 * ----------------------------------
				 * 5. wp (execution layer)
				 * ----------------------------------
				 *
				 * - Lúc này global $post đã sẵn sàng
				 * - Gọi callback xử lý logic route
				 * - Cần phải hook vào 'wp' để có thể truy cập được global $post.
				 */
				add_action('wp', function() use ($path, $fullPath, $requestPath) {
					$this->maybeNoTemplate();
					$callback = $this->prepareCallbackFunction($this->callback_function, $path, $fullPath);
					$this->resolveAndCall($callback);
				});
			}
		}
	}

	/**
	 * Override template nếu không dùng WP template
	 */
	public function maybeNoTemplate() {
		if (!$this->useTemplate) {
			add_filter('template_include', function($template) {
				return $this->funcs->_getResourcesPath('/views/rewrite-front-pages/layout/base.blade.php');
			});
		}
	}

}