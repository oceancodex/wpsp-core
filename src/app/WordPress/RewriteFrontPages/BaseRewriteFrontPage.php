<?php

namespace WPSPCORE\App\WordPress\RewriteFrontPages;

use WPSPCORE\BaseInstances;

abstract class BaseRewriteFrontPage extends BaseInstances {

	public $path                     = null;
	public $fullPath                 = null;
	public $rewriteIdent             = null;
	public $useTemplate              = false;
	public $rewriteFrontPageSlug     = 'rewrite-front-pages';
	public $rewriteFrontPagePostType = 'page';

	public $callback_function        = null;

	/*
	 *
	 */

	public function afterConstruct() {
		$this->overrideCallbackFunction($this->extraParams['callback_function'] ?? null);
		$this->overrideFullPath($this->extraParams['full_path']);
		$this->overridePath($this->extraParams['path']);
	}

	/*
	 *
	 */

	private function overrideCallbackFunction($callback_function = null) {
		if ($callback_function && $this->callback_function === null) {
			$this->callback_function = $callback_function;
		}
	}

	private function overridePath($path = null) {
		if ($path && !$this->path) {
			$this->path = $path;
		}
		elseif ($this->path) {
			$this->fullPath = $this->path;
		}
	}

	private function overrideFullPath($fullPath = null) {
		if ($fullPath && !$this->fullPath) {
			$this->fullPath = $fullPath;
		}
	}

	/*
	 *
	 */

	public function init($path = null, $fullPath = null) {
		$path     = $this->path ?? $path;
		$fullPath = $this->fullPath ?? $fullPath;

		// Loại bỏ dấu / ở đầu, vì rewrite rule sẽ lỗi khi regex bắt đầu bằng dấu /.
		$fullPath = ltrim($fullPath, '/\\');

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

		$fullPathEx = !str_starts_with($fullPath, $regexPrefix) ? $regexPrefix . $fullPath : $fullPath;
		$fullPathEx = !str_ends_with($fullPathEx, $regexSuffix) ? $fullPathEx . $regexSuffix : $fullPathEx;

		$appShortName = $this->funcs->_config('app.short_name');

		if ($path && $fullPath) {
			/**
			 * Parse tất cả capture group (...) trong regex
			 * để map sang query vars: _rewrite_group_1, _rewrite_group_2, ...
			 */
			@preg_match_all('/\(.+?\)/iu', $regexPath, $groupMatches);

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
			 */
			add_rewrite_rule(
				$regexPath,
				'index.php?post_type=' . $this->rewriteFrontPagePostType
				. '&' . $this->getSlugQueryVar() . '=' . $this->rewriteFrontPageSlug
				. '&is_rewrite=true' . $stringMatches,
				'top'
			);

			/**
			 * Lấy request path hiện tại
			 */
			$requestPath = ltrim($this->request->getRequestUri(), '/\\');

			/**
			 * Nếu URL hiện tại không match regex → bỏ qua
			 * (tránh hook không cần thiết)
			 */
			try {
				$matched = @preg_match('/' . $regexPath . '/iu', $requestPath, $matches);
				if (!$matched) {
					$matched = @preg_match('/' . $fullPathEx . '/iu', $requestPath, $matches);
				}
			}
			catch (\Throwable $e) {
				$matched = false;
			}

			if (!$matched) return;

			/**
			 * Xử lý khi truy cập URL rewrite ở frontend.
			 */
			if (!is_admin()) {
				/**
				 * ---
				 * (1) parse_request (INPUT LAYER)
				 * ---
				 * `Sửa query_vars ngay sau khi WP đọc URL`
				 *
				 * Thời điểm chạy:
				 *   WP::parse_request() đã so khớp request URI với các rewrite rule và
				 *   sinh ra $wp->query_vars. Hook này chạy ngay sau đó, trước khi
				 *   WP_Query được tạo. Đây là lần cuối có thể quyết định WP sẽ đi
				 *   tìm cái gì.
				 *
				 * Tại sao cần, khi đã có add_rewrite_rule():
				 *   Rewrite rule chỉ là "đề xuất". Trong thực tế rule của mình có thể
				 *   không được dùng, hoặc dùng nhưng bị lẫn query var khác:
				 *
				 *   a) Chưa flush rewrite rules. Rule mới thêm chưa có trong option
				 *      'rewrite_rules', nên WP rơi vào rule mặc định khác.
				 *      Ví dụ: /wpsp-lite-with-template khớp rule Page "(.?.+?)/?$"
				 *      → pagename=wpsp-lite-with-template → không có Page đó → 404.
				 *
				 *   b) Một rule khác khớp trước rule của mình (dù mình dùng 'top',
				 *      plugin khác cũng có thể dùng 'top' và đăng ký trước).
				 *
				 *   c) Rule mặc định sinh ra query var gây xung đột:
				 *      - 'attachment': permalink dạng /%postname%/ có rule
				 *        "[^/]+/([^/]+)/?$" → attachment=$matches[1].
				 *        Ví dụ: /stories/abc → WP hiểu là attachment "abc" của post
				 *        "stories" → 404 hoặc redirect sang attachment page.
				 *      - 'page': biến phân trang của rule "(.?.+?)(?:/([0-9]+))?/?$".
				 *        Ví dụ: /chapter/12 → page=12, WP đi tìm trang 12 của nội dung
				 *        → sai hoặc 404.
				 *      - 'name' / 'pagename': slug do rule khác gán, sẽ đè slug của mình.
				 *      - 'error': WP set error=404 khi không rule nào khớp.
				 *
				 * Việc làm ở đây:
				 *   - Xóa các var gây xung đột kể trên.
				 *   - Gán lại chính xác post_type + slug (name hoặc pagename tùy
				 *     post type có hierarchical hay không).
				 *   - Inject các capture group của regex thành query var
				 *     {app}_rewrite_group_N. Dùng $matches tự tính, nên vẫn có tham số
				 *     dù rewrite rule chưa flush.
				 *
				 * Priority rất lớn: chạy sau mọi plugin khác để có quyết định cuối.
				 */
				add_action('parse_request', function($wp) use ($matches, $appShortName) {
					unset(
						$wp->query_vars['attachment'],
						$wp->query_vars['page'],
						$wp->query_vars['name'],
						$wp->query_vars['pagename'],
						$wp->query_vars['error']
					);

					// Cờ đánh dấu request này thuộc rewrite front page.
					// Các hook phía sau dựa vào cờ này để chỉ xử lý đúng request.
					$wp->query_vars['is_rewrite'] = true;

					// Ép WP tìm đúng post: post_type + slug.
					// - page / CPT hierarchical → 'pagename' (hỗ trợ đường dẫn cha/con)
					// - CPT thường              → 'name'
					$wp->query_vars['post_type'] = $this->rewriteFrontPagePostType;
					$wp->query_vars[$this->getSlugQueryVar()] = $this->rewriteFrontPageSlug;

					// Inject tham số động từ URL.
					// Ví dụ: regex "^stories/([^/]+)/chapter/([0-9]+)$", URL /stories/abc/chapter/5
					// → {app}_rewrite_group_1 = 'abc', {app}_rewrite_group_2 = '5'
					foreach ($matches as $k => $v) {
						if ($k === 0) continue; // $matches[0] là toàn bộ chuỗi khớp, bỏ qua
						$wp->query_vars[$appShortName . '_rewrite_group_' . $k] = $v;
					}
				}, 9999999999);

				/**
				 * ---
				 * (2) pre_get_posts (QUERY LAYER)
				 * ---
				 * `Chốt main query trước khi chạy SQL`
				 *
				 *
				 * Thời điểm chạy:
				 *   WP_Query đã nhận query_vars và chạy parse_query() để tính các cờ
				 *   (is_page, is_single, is_archive...). pre_get_posts chạy ngay sau đó,
				 *   trước get_posts() sinh SQL. Đây là chỗ cuối để sửa điều kiện truy vấn.
				 *
				 * Tại sao cần, khi parse_request đã set query_vars:\
				 *   a) Plugin/theme khác cũng hook pre_get_posts và sửa main query.\
				 *      Ví dụ thường gặp:\
				 *      - Theme ép main query frontend chỉ lấy post_type 'post'.\
				 *      - Plugin đa ngôn ngữ (Polylang, WPML) thêm điều kiện ngôn ngữ,
				 *        bài của mình không có bản dịch phù hợp → 0 kết quả → 404.\
				 *      - Plugin membership/filter thêm meta_query, tax_query.
				 *      Với priority rất lớn, hook này chạy sau cùng và set lại giá trị
				 *      đúng.
				 *
				 *   b) Cờ do parse_query() tính có thể sai ngữ cảnh. Nếu query_vars
				 *      còn sót var lạ, WP có thể coi request là archive/home. Template
				 *      và các hàm is_singular(), is_page() khi đó trả kết quả sai, kéo
				 *      theo SEO plugin sinh title/canonical sai.
				 *
				 *   c) Tránh ảnh hưởng query khác: chỉ xử lý main query. Widget,
				 *      menu, WP_Query tự tạo trong template không bị đụng tới.
				 *
				 * Việc làm ở đây:
				 *   - Set lại post_type + slug var, xóa slug var còn lại để không
				 *     còn điều kiện WHERE thừa (ví dụ vừa post_name='test' vừa
				 *     tìm page theo path).
				 *   - Set cờ ngữ cảnh: 'page' → is_page; CPT → is_single.
				 */
				add_action('pre_get_posts', function ($query) {
					// Chỉ xử lý main query của request rewrite.
					if (!$query->is_main_query() || !$query->get('is_rewrite')) return;

					$slugVar  = $this->getSlugQueryVar();
					$otherVar = $slugVar === 'name' ? 'pagename' : 'name';

					$query->set('post_type', $this->rewriteFrontPagePostType);
					$query->set($slugVar, $this->rewriteFrontPageSlug);
					$query->set($otherVar, ''); // tránh 2 điều kiện slug chồng nhau

					$this->applyQueryFlags($query);
				}, 9999999999);

				/**
				 * ---
				 * (3) template_redirect (FINAL GUARD)
				 * ---
				 * `Chặn 404 lần cuối trước khi render`
				 *
				 * Thời điểm chạy:
				 *   SQL đã chạy, WP::handle_404() đã xét kết quả, action 'wp' đã chạy.
				 *   template_redirect chạy ngay trước khi WP chọn file template.
				 *   Đây là điểm cuối để thay đổi kết quả "404 hay không".
				 *
				 * Tại sao cần, khi 2 hook trên đã đúng:\
				 *   a) WP::handle_404() tự set 404 khi main query không có post.
				 *      Ví dụ: bài 'test' của wpsp_lite_content đang ở trạng thái draft,
				 *      bị xóa, hoặc slug gõ sai → query rỗng → is_404 = true.
				 *      Với route kiểu "trang ảo" (nội dung do callback render, post chỉ
				 *      là cái khung), ta vẫn muốn hiển thị thay vì 404.
				 *
				 *   b) Plugin khác chủ động gọi $wp_query->set_404() ở 'wp' hoặc
				 *      template_redirect priority thường.
				 *      Ví dụ: Yoast tắt attachment page, plugin bảo mật chặn URL lạ,
				 *      plugin membership ẩn CPT với khách.
				 *
				 *   c) handle_404() không chỉ set cờ mà còn gọi status_header(404).
				 *      Nếu chỉ đổi is_404 = false, trang hiển thị bình thường nhưng
				 *      HTTP status vẫn là 404. Hậu quả: Google không index, CDN/cache
				 *      lưu như trang lỗi, fetch/AJAX coi là lỗi.
				 *      → Bắt buộc gọi status_header(200).
				 *
				 * Lưu ý:
				 *   Hook này chỉ sửa trạng thái 404, không tạo ra post. Nếu query
				 *   thực sự rỗng thì global $post vẫn null. Callback ở 'wp' (chạy
				 *   trước hook này) và template cần tự kiểm tra $post trước khi dùng.
				 *   Nói cách khác, đây là lưới an toàn, không phải cách chính để có
				 *   trang hợp lệ. Cách chính vẫn là tạo đúng post với đúng slug.
				 */
				add_action('template_redirect', function () {
					global $wp_query;

					if (get_query_var('is_rewrite') && $wp_query->is_404) {
						$this->applyQueryFlags($wp_query);
						status_header(200); // ghi đè status 404 mà handle_404() đã set
					}
				}, 9999999999);

				/**
				 * ---
				 * (4) redirect_canonical (PREVENT REDIRECT LAYER)
				 * ---
				 * `Ngăn WP redirect về URL canonical nếu không sẽ phá custom URL`
				 */
				add_filter('redirect_canonical', function($redirect, $request) {
					if (get_query_var('is_rewrite')) {
						return false;
					}
					return $redirect;
				}, 9999999999, 2);

				/**
				 * ----
				 * (5) wp (EXECUTE LAYER)
				 * ---
				 * `Thực thi callback`
				 *
				 * - Lúc này global $post đã sẵn sàng
				 * - Gọi callback xử lý logic route
				 * - Cần phải hook vào 'wp' để có thể truy cập được global $post.
				 */
				add_action('wp', function() use ($path, $fullPath, $requestPath) {
					$this->maybeNoTemplate();
//					$callback = $this->prepareCallbackFunction($this->callback_function, $path, $fullPath);
//					$this->resolveAndCall($callback);
					$this->autoResolveAndCall($path, $fullPath, $requestPath, $this, $this->callback_function);
				}, 9999999999);
			}
		}
	}

	/*
	 *
	 */

	public function maybeNoTemplate() {
		if (!$this->useTemplate) {
			add_filter('template_include', function($template) {
				return $this->funcs->_getResourcesPath('/views/rewrite-front-pages/layout/base.blade.php');
			}, 9999999999);
		}
	}

	public function isHierarchicalPostType() {
		if ($this->rewriteFrontPagePostType === 'page') return true;
		$obj = get_post_type_object($this->rewriteFrontPagePostType);
		return $obj && $obj->hierarchical;
	}

	/** 'pagename' cho page/CPT hierarchical, 'name' cho CPT thường */
	public function getSlugQueryVar() {
		return $this->isHierarchicalPostType() ? 'pagename' : 'name';
	}

	public function applyQueryFlags($query) {
		$isPage                      = $this->rewriteFrontPagePostType === 'page';
		$query->is_page              = $isPage;
		$query->is_single            = !$isPage;
		$query->is_singular          = true;
		$query->is_home              = false;
		$query->is_404               = false;
		$query->is_archive           = false;
		$query->is_post_type_archive = false;
	}

}