# Policy trong WPSPCORE

Cập nhật: 2026-10-23 · Áp dụng cho: WPSPCORE (laravel/framework 12) và các plugin tạo từ WPSP

WPSPCORE dùng Gate và Policy của Laravel 12 (`Illuminate\Auth\Access\Gate`). Route được chặn bằng `->can()` hoặc `->middleware('can:...')`. Tham số route được tự bind thành Eloquent model trước khi gọi Policy, và RouteTrait có thêm các phần riêng cho WordPress: user dự phòng `WPUsersModel`, AdminPages, FrontPages và Ajaxs.

## Mục lục

1. [Cách hoạt động](#1-cách-hoạt-động)
2. [Viết Policy](#2-viết-policy)
3. [Đăng ký Policy và Gate::define](#3-đăng-ký-policy-và-gatedefine)
4. [Phân quyền trên route](#4-phân-quyền-trên-route)
5. [Đối số của `can`](#5-đối-số-của-can)
6. [Model binding](#6-model-binding)
7. [Phân quyền trong controller / page class](#7-phân-quyền-trong-controller--page-class)
8. [Dùng Gate trong code](#8-dùng-gate-trong-code)
9. [Khi bị từ chối](#9-khi-bị-từ-chối)
10. [Nhiều plugin trong cùng một site](#10-nhiều-plugin-trong-cùng-một-site)
11. [Lỗi thường gặp và chẩn đoán](#11-lỗi-thường-gặp-và-chẩn-đoán)

---

## 1. Cách hoạt động

Khi một route có `can:update,user`, RouteTrait làm lần lượt:

1. Tách chuỗi thành ability `update` và các đối số (`user`).
2. Đổi từng đối số thành giá trị thật: tên class, model đã bind, giá trị tham số route / query, hoặc chuỗi literal.
3. Gọi `Gate::inspect('update', [...])` của container plugin hiện tại.
4. Gate tìm Policy theo model (hoặc tên class) rồi gọi `Policy::before()` và `Policy::update($user, ...)`.
5. Được phép thì chạy action. Bị từ chối thì gửi response riêng của middleware (nếu có), nếu không trả 403 với message của Policy.

User mà Gate đưa vào Policy là user đang đăng nhập theo guard của plugin. Trong wp-admin và front-end, đây có thể là `WPUsersModel`, tức user dự phòng lấy từ WordPress.

`can` hoạt động như nhau trên mọi loại route có gọi `isPassedMiddleware()`:

| Loại route | Khi bị từ chối |
| --- | --- |
| AdminPages | Trang 403 (có lý do khi `WP_DEBUG`) |
| FrontPages | Gửi response riêng của middleware; không có thì để WordPress xử lý tiếp |
| Ajaxs | `wp_send_json` 403 kèm message của Policy |

---

## 2. Viết Policy

Policy là class PHP thường, mỗi method tương ứng một ability. Tham số đầu là user, các tham số sau là đối số truyền vào `can`.

```php
<?php

namespace WPSP\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use WPSP\App\Models\UsersModel;
use WPSP\App\Models\WPUsersModel;

class UsersModelPolicy {

	use HandlesAuthorization;

	/**
	 * Chạy trước mọi method.
	 * true / false = quyết định luôn, null = chạy tiếp method của ability.
	 */
	public function before(UsersModel|WPUsersModel $user, string $ability): ?bool {
		return user_can($this->userId($user), 'manage_options') ? true : null;
	}

	/**
	 * Trang danh sách: không cần model cụ thể.
	 * ->can('viewAny', UsersModel::class)
	 */
	public function viewAny(UsersModel|WPUsersModel $user): bool {
		return user_can($this->userId($user), 'list_users');
	}

	/**
	 * Xem 1 user cụ thể: cần model.
	 * ->can('view', 'user') + action show(UsersModel $user)
	 */
	public function view(UsersModel|WPUsersModel $user, UsersModel $model): bool {
		return $this->userId($user) === (int)$model->getKey();
	}

	public function create(UsersModel|WPUsersModel $user): bool {
		return user_can($this->userId($user), 'create_users');
	}

	/**
	 * Trả Response để kèm message cho người dùng.
	 */
	public function update(UsersModel|WPUsersModel $user, UsersModel $model): Response {
		return $this->userId($user) === (int)$model->getKey()
			? $this->allow()
			: $this->deny('Bạn chỉ được sửa tài khoản của mình.');
	}

	/**
	 * Giấu tài nguyên: trả 404 thay vì 403.
	 */
	public function delete(UsersModel|WPUsersModel $user, UsersModel $model): Response {
		return $this->denyAsNotFound();
	}

	/**
	 * Khách (chưa đăng nhập) chỉ được kiểm tra khi tham số đầu cho phép null.
	 */
	public function viewProfile(?UsersModel $user, UsersModel $model): bool {
		return (bool)$model->is_public;
	}

	protected function userId(UsersModel|WPUsersModel $user): int {
		return (int)($user->ID ?? $user->id ?? $user->getKey());
	}

}
```

**Quy tắc khi viết:**

| Quy tắc | Lý do |
| --- | --- |
| Tham số đầu khai báo `UsersModel\|WPUsersModel` | Trong wp-admin / front-end, Gate có thể đưa vào `WPUsersModel`; khai báo một kiểu sẽ bị `TypeError` |
| Method không cần model chỉ có 1 tham số (`viewAny`, `create`) | Gọi bằng tên class, Gate bỏ tên class đi trước khi gọi method |
| Method cần model khai báo kiểu model ở tham số thứ 2 | Gate truyền model đã bind vào đây |
| Muốn cho khách: `?UsersModel $user` | Tham số đầu không cho null thì Gate tự từ chối khách mà không gọi method |
| Cần message thì trả `Response` | `bool` chỉ cho message mặc định `This action is unauthorized.` |
| Ability có dấu gạch (`view-any`) gọi method camelCase (`viewAny`) | Quy ước của Laravel |

**Các kiểu kết quả:**

```php
return true;                                     // cho phép
return false;                                    // từ chối, message mặc định
return Response::allow();
return Response::deny('Lý do hiển thị cho người dùng.');
return Response::deny('Lý do', 'ERR_NOT_OWNER'); // kèm code
return Response::denyWithStatus(404);
return Response::denyAsNotFound();
return $this->allow();                           // HandlesAuthorization
return $this->deny('Lý do');
```

---

## 3. Đăng ký Policy và Gate::define

**Tự tìm Policy:** thường không cần đăng ký. Laravel tự tìm Policy theo quy ước:

| Model | Policy được tìm |
| --- | --- |
| `WPSP\App\Models\UsersModel` | `WPSP\App\Policies\UsersModelPolicy` |
| `WPSP\App\Models\Shop\Order` | `WPSP\App\Policies\Shop\OrderPolicy`, `WPSP\App\Models\Policies\Shop\OrderPolicy`... |

**Đăng ký thủ công** khi tên không theo quy ước. Đăng ký trong `boot()` của `AppServiceProvider`, không phải `register()` (giống `RateLimiter::for`):

```php
<?php

namespace WPSP\App\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use WPSP\App\Models\UsersModel;
use WPSP\App\Models\WPUsersModel;
use WPSP\App\Policies\UsersModelPolicy;

class AppServiceProvider extends ServiceProvider {

	public function boot(): void {
		$gate = $this->app->make(Gate::class);

		// Policy cho model.
		$gate->policy(UsersModel::class, UsersModelPolicy::class);

		// Ability không gắn model.
		$gate->define('view-reports', fn(UsersModel|WPUsersModel $user) => user_can($user->ID ?? $user->id, 'edit_posts'));

		// Ability nhận đối số literal: can:publish,'draft'
		$gate->define('publish', fn($user, string $state) => $state === 'draft');

		// Ability trỏ tới method của class: 'Class@method'
		$gate->define('export-report', 'WPSP\App\Policies\ReportPolicy@export');

		// Nhóm ability theo Policy: users.viewAny, users.view, users.create, users.update, users.delete
		$gate->resource('users', UsersModelPolicy::class);

		// Chạy trước mọi kiểm tra: admin luôn được phép.
		$gate->before(function (UsersModel|WPUsersModel $user, string $ability) {
			return user_can($user->ID ?? $user->id, 'manage_options') ? true : null;
		});

		// Chạy sau mọi kiểm tra: ghi log.
		$gate->after(function ($user, string $ability, ?bool $result, array $arguments) {
			do_action($this->funcs->_getAppShortName() . '_authorization_checked', $ability, $result);
		});
	}

}
```

---

## 4. Phân quyền trên route

Ba cách viết dưới đây tạo ra cùng một middleware `can:update,user`:

```php
// 1. Sau HTTP verb (RouteData::can) — khuyên dùng
Route::get('users\/(?P<user>\d+)\/edit', [UsersPage::class, 'edit'])->can('update', 'user');

// 2. Chuỗi middleware
Route::get('users\/(?P<user>\d+)\/edit', [UsersPage::class, 'edit'])->middleware('can:update,user');

// 3. Middleware class của Laravel
Route::get('users\/(?P<user>\d+)\/edit', [UsersPage::class, 'edit'])
	->middleware(\Illuminate\Auth\Middleware\Authorize::using('update', 'user'));
```

`Route::can(...)->get()` và `Route::can(...)->group()` (gọi `can` trước HTTP verb) chỉ dùng được khi `__call()` trong `BaseRoute` của CORE xử lý `can`, giống bản vá đã làm cho LITE. Nếu chưa có, viết `Route::middleware('can:...')->group(...)`.

**Ability không cần model:**

```php
Route::get('users', [UsersPage::class, 'index'])->can('viewAny', UsersModel::class);
Route::get('users\/create', [UsersPage::class, 'create'])->can('create', UsersModel::class);
// → UsersModelPolicy::viewAny($user), UsersModelPolicy::create($user)
```

**Ability cần 1 model cụ thể:**

```php
Route::get('users\/(?P<user>\d+)\/edit', [UsersPage::class, 'edit'])->can('update', 'user');
// → UsersModelPolicy::update($currentUser, UsersModel #5)
```

**Capability WordPress** (không cần Policy hay `Gate::define`):

```php
Route::get('wpsp&tab=activity_log', [wpsp_tab_activity_log::class, 'index'])->can('manage_options');
Route::get('posts\/(?P<post>\d+)\/edit', [PostsPage::class, 'edit'])->can('edit_post', 'post'); // meta cap
```

Khi Gate không có kết quả cho ability (không có `before` / Policy / `define` / `after` nào trả về khác `null`), RouteTrait hỏi `user_can()` với user WordPress đang đăng nhập (`wp_get_current_user()`, không phải user của Gate). Model đã bind được đổi thành ID (`getKey()`), tên class bị bỏ qua. Khách luôn bị từ chối. Tắt bằng `'capability_fallback' => false` trong `config/auth.php`.

Fallback này chỉ áp cho `can` của route / controller middleware. `Gate::allows('manage_options')` trong code vẫn là Gate thuần của Laravel; muốn có cùng hành vi thì đăng ký trong `boot()`:

```php
$gate->after(function ($user, string $ability, $result, array $arguments) {
	return $result ?? (is_user_logged_in() ? current_user_can($ability) : false);
});
```

Nên dùng capability (`manage_options`) thay vì tên role (`administrator`): `user_can()` vẫn nhận tên role, nhưng WordPress khuyên kiểm tra capability.

**Ability của `Gate::define`:**

```php
Route::get('reports', [ReportPage::class, 'index'])->can('view-reports');
Route::get('reports\/publish', [ReportPage::class, 'publish'])->middleware("can:publish,'draft'");
```

**Enum làm ability:**

```php
enum UserAbility: string {
	case Update = 'update';
	case ViewAny = 'viewAny';
}

Route::get('users', [UsersPage::class, 'index'])->can(UserAbility::ViewAny, UsersModel::class);
Route::get('users\/(?P<user>\d+)\/edit', [UsersPage::class, 'edit'])->can(UserAbility::Update, 'user');
// BackedEnum → value, UnitEnum → name
```

**Nhiều đối số:**

```php
Route::get('teams\/(?P<team>\d+)\/users\/(?P<user>\d+)', [TeamsPage::class, 'member'])
	->can('manageMember', ['team', 'user']);
// → TeamPolicy::manageMember($user, Team #team, UsersModel #user)
```

**Kết hợp với middleware khác (AND / OR, lồng nhau):**

```php
// Phải đăng nhập VÀ có quyền
->middleware(['relation' => 'AND', AuthMiddleware::class, 'can:update,user'])

// Admin HOẶC chủ tài khoản
->middleware(['relation' => 'OR', 'can:manage-users', 'can:update,user'])

// Lồng nhau
->middleware([
	'relation' => 'AND',
	'throttle:30,1',
	['relation' => 'OR', 'can:manage-users', 'can:update,user'],
])
```

Trong nhánh OR, message của nhánh bị từ chối không lọt sang khi nhánh khác đã cho qua.

**Nhóm route:**

```php
Route::middleware('can:viewAny,' . UsersModel::class)->group(function() {
	Route::get('users', [UsersPage::class, 'index']);
	Route::get('users\/export', [UsersPage::class, 'export']);
});
```

---

## 5. Đối số của `can`

Mỗi đối số sau ability được hiểu theo thứ tự ưu tiên:

| Đối số | Được hiểu là | Ví dụ |
| --- | --- | --- |
| Có dấu `\` | Tên class | `UsersModel::class` → `'WPSP\App\Models\UsersModel'` |
| Trùng tên tham số route | Giá trị tham số; thành model nếu action khai báo kiểu Model | `(?P<user>\d+)` + `edit(UsersModel $user)` |
| Trùng tên query / POST | Giá trị đó | `admin.php?page=wpsp_users&user=5` |
| Trong dấu nháy | Chuỗi literal | `can:publish,'draft'` |
| Tên class không namespace có tồn tại (`WP_Post`) | Tên class | `\WP_Post::class` |
| Còn lại | `null` (giống Laravel) | `can:publish,draft` → `null`; muốn chuỗi thì viết `'draft'` |

**Lưu ý với trang wp-admin (AdminPages):** URL có dạng `admin.php?page=...`, nên tham số thường nằm trên query string. `can:update,user` đọc `user` từ query khi route không có tham số regex cùng tên. Action khai báo `UsersModel $user` thì giá trị query vẫn được bind thành model.

---

## 6. Model binding

Model được bind khi **action của route** khai báo kiểu Model cho tham số trùng tên với đối số của `can`:

```php
Route::get('users\/(?P<user>\d+)\/edit', [UsersPage::class, 'edit'])->can('update', 'user');

class UsersPage {
	public function edit(UsersModel $user) { ... }
}
```

- Model được tìm bằng `findOrFail`. Không tìm thấy thì chạy action `{prefix}_model_not_found` rồi `wp_die` với message của exception (status mặc định của `wp_die`, không phải 404).
- Middleware và action dùng chung một instance model, nên chỉ có 1 query.
- Tên tham số route có thể là bất kỳ, miễn action khai báo kiểu Model cho đúng tên đó:

```php
Route::get('users\/(?P<id>\d+)\/edit', [UsersPage::class, 'edit'])->can('update', 'id');

public function edit(Request $request, UsersModel $id) { ... }
```

- Kiểu union / nullable cũng được bind: `show(Request $request, UsersModel|int|null $user)`.
- Action không khai báo kiểu Model (`edit($id)`) thì Policy nhận **chuỗi** `'5'`, không phải model. Policy có tham số kiểu `UsersModel` sẽ không được gọi và request bị từ chối. Khi `WP_DEBUG`, message chẩn đoán ghi rõ "đối số đầu không phải model".

---

## 7. Phân quyền trong controller / page class

Khai báo quyền ngay trong class xử lý, không lặp lại trên từng route. RouteTrait chỉ kiểm tra khi method được gọi đúng là action của route (`$route->callback[1]`), nên các method vòng đời (`customProperties`, `init`, constructor...) không bị chặn.

### 7.1. Tự áp Policy cho cả nhóm action: `authorizeResource()`

```php
<?php

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use WPSP\App\Models\UsersModel;
use WPSPCORE\App\Traits\ControllerMiddlewareTrait;

class wpsp_tab_users /* extends class page của bạn */ {

	use ControllerMiddlewareTrait, AuthorizesRequests;

	public function customProperties() {
		$this->authorizeResource(UsersModel::class, 'user');
	}

	public function index() { ... }                                    // viewAny($user)
	public function show(Request $request, UsersModel $user) { ... }   // view($user, $model)
	public function create() { ... }                                   // create($user)
	public function store(Request $request) { ... }                    // create($user)
	public function edit(UsersModel $user) { ... }                     // update($user, $model)
	public function update(Request $request, UsersModel $user) { ... } // update($user, $model)
	public function destroy(UsersModel $user) { ... }                  // delete($user, $model)

}
```

| Action | Ability của Policy | Đối số truyền vào Policy |
| --- | --- | --- |
| `index` | `viewAny` | tên class |
| `show` | `view` | model `$user` |
| `create`, `store` | `create` | tên class |
| `edit`, `update` | `update` | model `$user` |
| `destroy` | `delete` | model `$user` |

- Tham số thứ 2 của `authorizeResource` (`'user'`) là tên tham số mà action phải có. Action cần model mà thiếu tham số này thì request bị từ chối, không crash.
- Method không nằm trong bảng (vd: `export`) không bị áp Policy.
- Chỉ áp cho một số action:

```php
$this->authorizeResource(UsersModel::class, 'user', ['only' => ['index', 'show']]);
$this->authorizeResource(UsersModel::class, 'user', ['except' => ['destroy']]);
```

### 7.2. Chọn từng action: `$this->middleware()`

```php
public function customProperties() {
	$this->middleware('can:update,user')->only('edit', 'update');
	$this->middleware('can:viewAny,' . UsersModel::class)->only('index');
	$this->middleware('can:delete,user')->only('destroy');
	$this->middleware(AuthMiddleware::class)->except('index');

	// Closure middleware
	$this->middleware(function ($request, $next) {
		return current_user_can('list_users') ? $next($request) : false;
	})->only('export');
}
```

`ControllerMiddlewareTrait` chỉ đặt trên page / controller class, **không** đặt trên `BaseRoute`.

### 7.3. Laravel 11+: `HasMiddleware`

```php
<?php

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportPage implements HasMiddleware {

	public static function middleware(): array {
		return [
			(new Middleware('can:view-reports'))->except('publicSummary'),
			(new Middleware("can:publish,'draft'"))->only('publish'),
			new Middleware(AuthMiddleware::class),
		];
	}

	public function index() { ... }
	public function publish() { ... }
	public function publicSummary() { ... }

}
```

---

## 8. Dùng Gate trong code

Trong action của route, RouteTrait đã trỏ Container và Facade về plugin hiện tại, nên `Gate::` luôn là Gate của đúng plugin. Ngoài route (hook, cron, REST callback tự viết), lấy Gate từ container của plugin thay vì Facade.

```php
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

// Đúng / sai
if (Gate::allows('viewAny', UsersModel::class)) { ... }
if (Gate::denies('update', $user)) { ... }

// Nhiều ability
Gate::check(['update', 'delete'], $user); // tất cả
Gate::any(['update', 'delete'], $user);   // ít nhất 1
Gate::none(['update', 'delete'], $user);  // không cái nào

// Kết quả kèm message
$response = Gate::inspect('update', $user);
if ($response->denied()) {
	wp_die(esc_html($response->message() ?? 'Không có quyền.'), '', ['response' => $response->status() ?? 403]);
}

// Ném exception: WordPress không có Handler của Laravel bắt, phải tự catch
try {
	Gate::authorize('update', $user);
	// hoặc trong class có trait AuthorizesRequests:
	// $this->authorize('update', $user);
}
catch (AuthorizationException $e) {
	wp_die(esc_html($e->getMessage()), '', ['response' => $e->status() ?? 403]);
}

// Kiểm tra cho user khác
Gate::forUser($otherUser)->allows('update', $user);

// Điều kiện tại chỗ
Gate::allowIf(fn($user) => user_can($user->ID ?? $user->id, 'manage_options'), 'Chỉ admin.');
Gate::denyIf(fn($user) => (bool)get_user_meta($user->ID ?? $user->id, 'banned', true), 'Tài khoản bị khoá.');

// Lấy Policy
$policy = Gate::getPolicyFor(UsersModel::class);

// Ngoài route
$gate = $app->make(\Illuminate\Contracts\Auth\Access\Gate::class);
$gate->allows('update', $user);
```

---

## 9. Khi bị từ chối

Thứ tự xử lý khi `can` từ chối:

1. Middleware nào chặn bằng response riêng (`redirect('/login')`, `back()->withErrors(...)`) thì response đó được gửi. Redirect về chính URL hiện tại bị bỏ qua để tránh vòng lặp.
2. Không có response riêng:
   - AdminPages: trang 403 với message của Policy, hoặc `Access denied.`
   - Ajaxs: JSON 403 kèm message.
   - FrontPages: để WordPress xử lý tiếp.
   - Controller middleware / `authorizeResource`: 403 với message của Policy, hoặc `This action is unauthorized.`

Lấy message trong code:

```php
$message = $this->lastAuthorizationMessage($this->request);
```

Hook (prefix là tên ngắn của plugin):

```php
// Mỗi lần can bị từ chối
add_action($prefix . '_authorization_denied', function ($ability, $arguments, $message, $request) {
	error_log('Từ chối ' . $ability . ': ' . $message);
}, 10, 4);

// Không tìm thấy model khi binding
add_action($prefix . '_model_not_found', function ($className, $id, $exception) { ... }, 10, 3);

// Trước khi gửi response chặn của middleware
add_action($prefix . '_before_send_middleware_response', function ($response, $request) { ... }, 10, 2);

// Quyết định có gửi response chặn không (trả false để tắt hẳn)
add_filter($prefix . '_send_blocked_middleware_response', function ($send, $response, $request) {
	return $send;
}, 10, 3);
```

---

## 10. Nhiều plugin trong cùng một site

Mỗi plugin tạo từ WPSP có container, Gate và Policy riêng:

- Trước mỗi lần kiểm tra, RouteTrait gắn Container, Facade và Model resolver về plugin đang xử lý route. `Gate::` trong action luôn là Gate của plugin đó.
- Key cache (model binding, throttle, blocked response) có prefix `hookPrefix()` của từng plugin.
- Policy đăng ký trong plugin A không áp cho plugin B, kể cả khi trùng tên model.
- Ngoài route (hook WordPress chạy sau khi plugin khác xử lý), Facade có thể đang trỏ về plugin khác. Dùng `$app->make(Gate::class)` của chính plugin mình.

---

## 11. Lỗi thường gặp và chẩn đoán

Khi bật `WP_DEBUG`, lỗi trong Gate / Policy được ném ra kèm gợi ý cách sửa thay vì bị giấu thành 403. Khi tắt, request bị từ chối và lỗi ghi vào `error_log`.

| Hiện tượng | Nguyên nhân | Cách sửa |
| --- | --- | --- |
| `ArgumentCountError: Too few arguments to ...Policy::view()` | `->can('view', Model::class)`: Gate bỏ tên class, gọi `view($user)` thiếu model | Dùng `viewAny` / `create`; hoặc khai báo `?Model $model = null`; hoặc truyền tên tham số route `->can('view', 'user')` + action khai báo kiểu Model |
| `TypeError: Argument #1 ... must be of type UsersModel, WPUsersModel given` | User trong wp-admin / front-end là user dự phòng của WordPress | Khai báo `UsersModel\|WPUsersModel $user` |
| `->can('administrator')` bị 403, message "Không tìm thấy Policy / Gate::define" | RouteTrait cũ chưa có capability fallback | Dùng RouteTrait mới (fallback `user_can()`), hoặc `Gate::define('administrator', ...)` |
| 403 dù Policy trả `true` cho tất cả | Policy không được gọi: action chưa khai báo kiểu Model, tên tham số không khớp, hoặc sai tên ability | Đọc message chẩn đoán trên trang 403 khi `WP_DEBUG` |
| Khách bị 403 dù Policy trả `true` | Tham số đầu không cho phép null | `?UsersModel $user` |
| `Rate limiter [...] is not defined` khi dùng cùng `throttle` | `RateLimiter::for` gọi trong `register()` | Chuyển sang `boot()` |
| Fatal error thay vì trang lỗi | Route class chỉ `catch (\Exception)`, không bắt `\Error` | Sửa Policy theo gợi ý trong message; chỉ hiện khi `WP_DEBUG` |
| Trang lỗi `No query results for model ...` | Model không tồn tại (`findOrFail`) | Kiểm tra id trên URL; xử lý qua action `{prefix}_model_not_found` |

**Message chẩn đoán** (chỉ khi `WP_DEBUG`, Policy không đặt message):

```
can:view,id bị từ chối. User: WPSP\App\Models\WPUsersModel#1 | Đối số: ['1' (string)] | Không tìm thấy Policy / Gate::define cho ability này — đối số đầu không phải model (action đã khai báo kiểu Model cho tham số chưa?)
```

Đọc message này theo thứ tự:

1. **User:** có đúng user mong đợi không, có phải `WPUsersModel` không.
2. **Đối số:** là model (`UsersModel#5`) hay chuỗi (`'5' (string)`).
3. **Phần cuối:** Gate có tìm thấy Policy / define không.
