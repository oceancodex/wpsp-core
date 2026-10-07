<?php

namespace WPSPCORE\App\Widen\Http;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

if (class_exists('Illuminate\Http\Request')) {
	class Request extends \Illuminate\Http\Request {}
}
else {
	/**
	 * Standalone HTTP Request mirroring the public API of Illuminate\Http\Request
	 * (including the Symfony HttpFoundation members Laravel relies on).
	 *
	 * Bags (public, like Symfony):
	 *   $request->query, ->request, ->attributes, ->cookies, ->server  => ParameterBag
	 *   $request->headers                                               => HeaderBag
	 *   $request->files                                                 => FileBag
	 *
	 * Requires PHP 8.0+. Optional integrations (used only when present):
	 *  - Illuminate\Support\{Collection, Stringable, Uri}, Carbon\Carbon
	 *  - a session store exposing flashInput() / getOldInput()
	 */
	class Request implements ArrayAccess, JsonSerializable {

		public ParameterBag $query;
		public ParameterBag $request;
		public ParameterBag $attributes;
		public ParameterBag $cookies;
		public FileBag      $files;
		public ParameterBag $server;
		public HeaderBag    $headers;

		protected ?string       $content = null;
		protected ?ParameterBag $json    = null;

		protected ?string $baseUrl                = null;
		protected ?string $pathInfo               = null;
		protected ?array  $acceptableContentTypes = null;
		protected ?array  $languages              = null;
		protected ?string $format                 = null;
		protected ?string $locale                 = null;
		protected string  $defaultLocale          = 'en';

		protected          $session       = null;
		protected ?Closure $userResolver  = null;
		protected ?Closure $routeResolver = null;

		protected static array $trustedProxies              = [];
		protected static array $macros                      = [];
		protected static bool  $httpMethodParameterOverride = false;

		protected static array $formats = [
			'html'   => ['text/html', 'application/xhtml+xml'],
			'txt'    => ['text/plain'],
			'js'     => ['application/javascript', 'application/x-javascript', 'text/javascript'],
			'css'    => ['text/css'],
			'json'   => ['application/json', 'application/x-json'],
			'jsonld' => ['application/ld+json'],
			'xml'    => ['text/xml', 'application/xml', 'application/x-xml'],
			'rdf'    => ['application/rdf+xml'],
			'atom'   => ['application/atom+xml'],
			'rss'    => ['application/rss+xml'],
			'form'   => ['application/x-www-form-urlencoded', 'multipart/form-data'],
		];

		public function __construct(
			array $query = [], array $request = [], array $attributes = [],
			array $cookies = [], array $files = [], array $server = [], ?string $content = null
		) {
			$this->initialize($query, $request, $attributes, $cookies, $files, $server, $content);
		}

		public function initialize(
			array $query = [], array $request = [], array $attributes = [],
			array $cookies = [], array $files = [], array $server = [], ?string $content = null
		): void {
			$this->query      = new ParameterBag($query);
			$this->request    = new ParameterBag($request);
			$this->attributes = new ParameterBag($attributes);
			$this->cookies    = new ParameterBag($cookies);
			$this->files      = new FileBag($files);
			$this->server     = new ParameterBag($server);
			$this->headers    = new HeaderBag($this->extractHeaders($server));
			$this->content    = $content;
			$this->json       = null;
			$this->resetCache();
		}

		/** Xoá các giá trị đã cache (gọi sau khi sửa server/headers thủ công). */
		public function resetCache(): static {
			$this->baseUrl                = $this->pathInfo = $this->format = null;
			$this->acceptableContentTypes = $this->languages = null;
			return $this;
		}

		public function __clone() {
			$sharesJson = $this->json !== null && $this->json === $this->request;

			$this->query      = clone $this->query;
			$this->request    = clone $this->request;
			$this->attributes = clone $this->attributes;
			$this->cookies    = clone $this->cookies;
			$this->files      = clone $this->files;
			$this->server     = clone $this->server;
			$this->headers    = clone $this->headers;
			$this->json       = $sharesJson ? $this->request : ($this->json ? clone $this->json : null);
		}

		/* ================================================================== *
		 | Construction
		 * ================================================================== */

		/** Symfony: Request::createFromGlobals(). */
		public static function createFromGlobals(): static {
			$request = new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
			$request->bindJsonToRequest();
			return $request;
		}

		public static function capture(): static {
			static::enableHttpMethodParameterOverride();

			return static::createFromGlobals();
		}

		/** For JSON requests, expose the decoded body as $request->request (like Laravel). */
		protected function bindJsonToRequest(): void {
			if ($this->isJson()) {
				$this->request->replace($this->json()->all());
				$this->setJson($this->request);
			}
		}

		public static function create(string $uri, string $method = 'GET', array $parameters = [], array $server = [], ?string $content = null, array $cookies = [], array $files = []): static {
			$parts  = parse_url($uri) ?: [];
			$method = strtoupper($method);
			$query  = [];
			if (isset($parts['query'])) parse_str($parts['query'], $query);

			$scheme = $parts['scheme'] ?? 'http';
			$server = array_replace([
				'REQUEST_METHOD' => $method,
				'REQUEST_URI'    => ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''),
				'QUERY_STRING'   => $parts['query'] ?? '',
				'HTTP_HOST'      => ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : ''),
				'SERVER_NAME'    => $parts['host'] ?? 'localhost',
				'SERVER_PORT'    => $parts['port'] ?? ($scheme === 'https' ? 443 : 80),
				'HTTPS'          => $scheme === 'https' ? 'on' : 'off',
				'REMOTE_ADDR'    => '127.0.0.1',
			], $server);

			if (in_array($method, ['GET', 'HEAD'], true)) {
				return new static(array_replace($query, $parameters), [], [], $cookies, $files, $server, $content);
			}
			return new static($query, $parameters, [], $cookies, $files, $server, $content);
		}

		/** Build from a Symfony Request (or any object exposing the same bags). */
		public static function createFromBase(object $base): static {
			$new = new static(
				$base->query->all(), $base->request->all(), $base->attributes->all(),
				$base->cookies->all(), $base->files->all(), $base->server->all(), $base->getContent()
			);

			$headers = [];
			foreach ($base->headers->all() as $k => $v) $headers[$k] = $v;
			$new->headers->replace($headers);

			$new->bindJsonToRequest();
			return $new;
		}

		public static function createFrom(self $from, ?self $to = null): static {
			$request = $to ?: new static;

			$request->initialize(
				$from->query->all(), $from->request->all(), $from->attributes->all(), $from->cookies->all(),
				array_filter($from->files->all()), $from->server->all(), $from->getContent()
			);

			$request->headers->replace($from->headers->all());
			$request->setRequestLocale($from->getLocale());
			$request->setDefaultRequestLocale($from->getDefaultLocale());
			$request->setJson($from->json());

			if ($from->hasSession() && $session = $from->session()) {
				$request->setLaravelSession($session);
			}

			$request->setUserResolver($from->getUserResolver());
			$request->setRouteResolver($from->getRouteResolver());

			return $request;
		}

		public function duplicate(?array $query = null, ?array $request = null, ?array $attributes = null, ?array $cookies = null, ?array $files = null, ?array $server = null): static {
			$dup = clone $this;

			if ($query !== null) $dup->query->replace($query);
			if ($request !== null) $dup->request->replace($request);
			if ($attributes !== null) $dup->attributes->replace($attributes);
			if ($cookies !== null) $dup->cookies->replace($cookies);
			if ($files !== null) $dup->files->replace($this->filterFiles($files) ?? []);
			if ($server !== null) {
				$dup->server->replace($server);
				$dup->headers->replace($dup->extractHeaders($server));
			}
			if ($query !== null || $request !== null || $server !== null) $dup->json = null;

			return $dup->resetCache();
		}

		public function instance(): static {
			return $this;
		}

		protected function extractHeaders(array $server): array {
			$headers = [];
			foreach ($server as $key => $value) {
				if (str_starts_with((string)$key, 'HTTP_')) {
					$headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
				}
				elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true) && $value !== '') {
					$headers[strtolower(str_replace('_', '-', $key))] = $value;
				}
			}
			if (!isset($headers['authorization'])) {
				if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
					$headers['authorization'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
				}
				elseif (isset($server['PHP_AUTH_USER'])) {
					$headers['authorization'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . ($server['PHP_AUTH_PW'] ?? ''));
				}
			}
			return $headers;
		}

		/* ================================================================== *
		 | HTTP method
		 * ================================================================== */

		public static function enableHttpMethodParameterOverride(): void {
			static::$httpMethodParameterOverride = true;
		}

		public static function getHttpMethodParameterOverride(): bool {
			return static::$httpMethodParameterOverride;
		}

		public function getRealMethod(): string {
			return strtoupper((string)$this->server->get('REQUEST_METHOD', 'GET'));
		}

		public function getMethod(): string {
			$method = $this->getRealMethod();
			if ($method === 'POST' && static::$httpMethodParameterOverride) {
				$override = $this->headers->get('X-HTTP-METHOD-OVERRIDE')
					?? $this->request->get('_method')
					?? $this->query->get('_method');
				if (is_string($override) && $override !== '') {
					$override = strtoupper($override);
					if (in_array($override, ['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'PURGE', 'OPTIONS', 'TRACE', 'CONNECT'], true)) {
						return $override;
					}
				}
			}
			return $method;
		}

		public function method(): string {
			return $this->getMethod();
		}

		public function setMethod(string $method): void {
			$this->server->set('REQUEST_METHOD', strtoupper($method));
		}

		public function isMethod(string $method): bool {
			return $this->getMethod() === strtoupper($method);
		}

		public function isMethodSafe(): bool {
			return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true);
		}

		public function isMethodIdempotent(): bool {
			return in_array($this->getMethod(), ['HEAD', 'GET', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PURGE'], true);
		}

		public function isMethodCacheable(): bool {
			return in_array($this->getMethod(), ['GET', 'HEAD'], true);
		}

		/* ================================================================== *
		 | URL / path
		 * ================================================================== */

		public function setBaseUrl(string $baseUrl): static {
			$this->baseUrl  = rtrim($baseUrl, '/');
			$this->pathInfo = null;
			return $this;
		}

		/** Sub-directory the app is installed in ("" when at domain root). */
		public function getBaseUrl(): string {
			return $this->baseUrl ??= $this->prepareBaseUrl();
		}

		public function getBasePath(): string {
			return $this->getBaseUrl();
		}

		public function getScriptName(): string {
			return (string)$this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
		}

		protected function prepareBaseUrl(): string {
			$uri = $this->requestUriPath();
			if (function_exists('home_url')) {                       // WordPress: use the site's own path
				$dir = rtrim((string)parse_url(home_url('/'), PHP_URL_PATH), '/');
			}
			else {
				$dir = rtrim(str_replace('\\', '/', dirname((string)$this->server->get('SCRIPT_NAME', '/index.php'))), '/');
			}
			if ($dir === '') return '';
			return ($uri === $dir || str_starts_with($uri, $dir . '/')) ? $dir : '';
		}

		public function getRequestUri(): string {
			return (string)$this->server->get('REQUEST_URI', '/');
		}

		protected function requestUriPath(): string {
			return (string)(parse_url($this->getRequestUri(), PHP_URL_PATH) ?: '/');
		}

		/** Raw path after the base URL, with leading slash ("/" for root). */
		public function getPathInfo(): string {
			if ($this->pathInfo !== null) return $this->pathInfo;

			$path = $this->requestUriPath();
			$base = $this->getBaseUrl();
			if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base));
			return $this->pathInfo = ($path === '' ? '/' : $path);
		}

		public function path(): string {
			$pattern = trim($this->getPathInfo(), '/');
			return $pattern === '' ? '/' : $pattern;
		}

		public function decodedPath(): string {
			return rawurldecode($this->path());
		}

		public function segments(): array {
			return array_values(array_filter(explode('/', $this->decodedPath()), fn($v) => $v !== ''));
		}

		public function segment(int $index, $default = null) {
			return $this->segments()[$index - 1] ?? $default;
		}

		public function getQueryString(): string {
			return (string)$this->server->get('QUERY_STRING', $this->buildQuery($this->query->all()));
		}

		public function getScheme(): string {
			return $this->isSecure() ? 'https' : 'http';
		}

		public function getHttpHost(): string {
			if ($this->isFromTrustedProxy() && ($forwarded = $this->headers->get('X-Forwarded-Host'))) {
				return trim(explode(',', $forwarded)[0]);
			}
			return $this->headers->get('Host') ?? (string)$this->server->get('SERVER_NAME', 'localhost');
		}

		public function getHost(): string {
			return strtolower((string)preg_replace('/:\d+$/', '', $this->getHttpHost()));
		}

		public function getPort(): int {
			if (preg_match('/:(\d+)$/', $this->getHttpHost(), $m)) return (int)$m[1];
			if ($this->isFromTrustedProxy() && ($port = $this->headers->get('X-Forwarded-Port'))) return (int)$port;
			return (int)($this->server->get('SERVER_PORT') ?? ($this->isSecure() ? 443 : 80));
		}

		public function getSchemeAndHttpHost(): string {
			return $this->getScheme() . '://' . $this->getHttpHost();
		}

		public function getUri(): string {
			$qs = $this->getQueryString();
			return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . ($qs !== '' ? '?' . $qs : '');
		}

		public function getUser(): ?string {
			return $this->server->get('PHP_AUTH_USER');
		}

		public function getPassword(): ?string {
			return $this->server->get('PHP_AUTH_PW');
		}

		public function getUserInfo(): ?string {
			$user = $this->getUser();
			$pass = $this->getPassword();
			return $pass !== null && $pass !== '' ? $user . ':' . $pass : $user;
		}

		public function getProtocolVersion(): ?string {
			return $this->server->get('SERVER_PROTOCOL');
		}

		public function root(): string {
			return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/');
		}

		public function url(): string {
			return rtrim((string)preg_replace('/\?.*/', '', $this->getUri()), '/');
		}

		protected function queryQuestionMark(): string {
			return $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';
		}

		public function fullUrl(): string {
			$query = $this->getQueryString();
			return $query ? $this->url() . $this->queryQuestionMark() . $query : $this->url();
		}

		public function fullUrlWithQuery(array $query): string {
			return $this->query->count() > 0
				? $this->url() . $this->queryQuestionMark() . $this->buildQuery(array_merge($this->query->all(), $query))
				: $this->fullUrl() . $this->queryQuestionMark() . $this->buildQuery($query);
		}

		public function fullUrlWithoutQuery(array|string $keys): string {
			$query = $this->query->all();
			foreach ((array)$keys as $k) unset($query[$k]);
			return count($query) > 0
				? $this->url() . $this->queryQuestionMark() . $this->buildQuery($query)
				: $this->url();
		}

		protected function buildQuery(array $q): string {
			return http_build_query($q, '', '&', PHP_QUERY_RFC3986);
		}

		/** Illuminate\Support\Uri instance (requires illuminate/support). */
		public function uri() {
			if (!class_exists('\Illuminate\Support\Uri')) {
				throw new RuntimeException('Illuminate\Support\Uri is not available.');
			}
			return \Illuminate\Support\Uri::of($this->fullUrl());
		}

		public function is(string ...$patterns): bool {
			$path = $this->decodedPath();
			foreach ($patterns as $pattern) {
				if (static::matchesPattern($pattern, $path)) return true;
			}
			return false;
		}

		public function fullUrlIs(string ...$patterns): bool {
			$url = $this->fullUrl();
			foreach ($patterns as $pattern) {
				if (static::matchesPattern($pattern, $url)) return true;
			}
			return false;
		}

		public function routeIs(string ...$patterns): bool {
			$route = $this->route();
			return $route && is_object($route) && method_exists($route, 'named') && $route->named(...$patterns);
		}

		protected static function matchesPattern(string $pattern, string $value): bool {
			if ($pattern === $value) return true;
			if ($pattern !== '/' && !str_contains($pattern, '://')) {
				$pattern = trim($pattern, '/') ?: '/';
			}
			$regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '\z#u';
			return (bool)preg_match($regex, $value);
		}

		public function host(): string {
			return $this->getHost();
		}

		public function httpHost(): string {
			return $this->getHttpHost();
		}

		public function schemeAndHttpHost(): string {
			return $this->getSchemeAndHttpHost();
		}

		/* ================================================================== *
		 | Client, proxies, security
		 * ================================================================== */

		public static function setTrustedProxies(array $proxies): void {
			static::$trustedProxies = $proxies;
		}

		public static function getTrustedProxies(): array {
			return static::$trustedProxies;
		}

		public function isFromTrustedProxy(): bool {
			return $this->isTrustedIp((string)$this->server->get('REMOTE_ADDR', ''));
		}

		protected function isTrustedIp(string $ip): bool {
			foreach (static::$trustedProxies as $proxy) {
				if ($proxy === '*' || $proxy === $ip) return true;
				if (str_contains($proxy, '/') && static::ipInCidr($ip, $proxy)) return true;
			}
			return false;
		}

		protected static function ipInCidr(string $ip, string $cidr): bool {
			[$subnet, $bits] = explode('/', $cidr, 2);
			$ipBin  = @inet_pton($ip);
			$subBin = @inet_pton($subnet);
			if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) return false;
			$bits  = (int)$bits;
			$bytes = intdiv($bits, 8);
			$rem   = $bits % 8;
			if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) return false;
			if ($rem > 0) {
				$mask = (0xFF << (8 - $rem)) & 0xFF;
				return (ord($ipBin[$bytes]) & $mask) === (ord($subBin[$bytes]) & $mask);
			}
			return true;
		}

		public function isSecure(): bool {
			$https = (string)$this->server->get('HTTPS', '');
			if ($https !== '' && strtolower($https) !== 'off') return true;
			if ($this->isFromTrustedProxy()) {
				return strtolower((string)$this->headers->get('X-Forwarded-Proto', '')) === 'https';
			}
			return false;
		}

		public function secure(): bool {
			return $this->isSecure();
		}

		/** Most trusted address first, least trusted (original client) last. */
		public function getClientIps(): array {
			$remote = (string)$this->server->get('REMOTE_ADDR', '');
			if ($remote === '') return [];
			if (!$this->isFromTrustedProxy()) return [$remote];

			$chain = [];
			if ($forwarded = $this->headers->get('X-Forwarded-For')) {
				foreach (explode(',', $forwarded) as $ip) {
					$ip = trim($ip);
					if (filter_var($ip, FILTER_VALIDATE_IP)) $chain[] = $ip;
				}
			}
			$chain[] = $remote;
			$chain   = array_reverse($chain);   // nearest hop first

			$untrusted = array_values(array_filter($chain, fn($ip) => !$this->isTrustedIp($ip)));
			return $untrusted ?: [$remote];
		}

		public function getClientIp(): ?string {
			return $this->getClientIps()[0] ?? null;
		}

		public function ips(): array {
			return $this->getClientIps();
		}

		public function ip(): ?string {
			return $this->getClientIp();
		}

		public function userAgent(): ?string {
			return $this->headers->get('User-Agent');
		}

		public function ajax(): bool {
			return $this->isXmlHttpRequest();
		}

		public function isXmlHttpRequest(): bool {
			return strtolower((string)$this->headers->get('X-Requested-With', '')) === 'xmlhttprequest';
		}

		public function pjax(): bool {
			return ($this->headers->get('X-PJAX') ?? '') == true;
		}

		public function prefetch(): bool {
			return strcasecmp((string)$this->server->get('HTTP_X_MOZ', ''), 'prefetch') === 0
				|| strcasecmp((string)$this->headers->get('Purpose', ''), 'prefetch') === 0
				|| strcasecmp((string)$this->headers->get('Sec-Purpose', ''), 'prefetch') === 0;
		}

		/* ================================================================== *
		 | Headers
		 * ================================================================== */

		/** $request->header() => all headers; $request->header('X-Foo') => first value. */
		public function header(?string $key = null, $default = null) {
			if ($key === null) return $this->headers->all();
			return $this->headers->get($key, $default);
		}

		public function hasHeader(string $key): bool {
			return $this->headers->has($key);
		}

		public function bearerToken(): ?string {
			$header   = (string)$this->header('Authorization', '');
			$position = strripos($header, 'Bearer ');
			if ($position !== false) {
				$header = substr($header, $position + 7);
				return str_contains($header, ',') ? strstr($header, ',', true) : $header;
			}
			return null;
		}

		/* ================================================================== *
		 | Content negotiation (InteractsWithContentTypes)
		 * ================================================================== */

		public function getContentType(): ?string {
			$type = $this->headers->get('Content-Type');
			return $type === null ? null : strtolower(trim(explode(';', $type)[0]));
		}

		public function getContentTypeFormat(): ?string {
			return $this->getFormat((string)$this->getContentType());
		}

		public function getMimeType(string $format): ?string {
			return static::$formats[$format][0] ?? null;
		}

		public function getFormat(?string $mimeType): ?string {
			$canonical = $mimeType !== null ? strtolower(trim(explode(';', $mimeType)[0])) : '';
			foreach (static::$formats as $format => $mimeTypes) {
				if (in_array($canonical, $mimeTypes, true)) return $format;
			}
			return null;
		}

		public function getRequestFormat(?string $default = 'html'): ?string {
			$this->format ??= $this->attributes->get('_format');
			return $this->format ?? $default;
		}

		public function setRequestFormat(?string $format): void {
			$this->format = $format;
		}

		public function isJson(): bool {
			$type = (string)$this->headers->get('Content-Type', '');
			return str_contains($type, '/json') || str_contains($type, '+json');
		}

		public function getAcceptableContentTypes(): array {
			return $this->acceptableContentTypes ??= $this->parseAcceptHeader((string)$this->headers->get('Accept', ''));
		}

		public function getCharsets(): array {
			return $this->parseAcceptHeader((string)$this->headers->get('Accept-Charset', ''));
		}

		public function getEncodings(): array {
			return $this->parseAcceptHeader((string)$this->headers->get('Accept-Encoding', ''));
		}

		public function getETags(): array {
			return preg_split('/\\s*,\\s*/', (string)$this->headers->get('If-None-Match', ''), -1, PREG_SPLIT_NO_EMPTY);
		}

		public function isNoCache(): bool {
			return str_contains((string)$this->headers->get('Cache-Control', ''), 'no-cache')
				|| $this->headers->get('Pragma') === 'no-cache';
		}

		public function preferSafeContent(): bool {
			return $this->isSecure() && str_contains(strtolower((string)$this->headers->get('Prefer', '')), 'safe');
		}

		/** Thêm/ghi đè định dạng: Request::setFormat('csv', 'text/csv'). */
		public static function setFormat(string $format, string|array $mimeTypes): void {
			static::$formats[$format] = (array)$mimeTypes;
		}

		/** Định dạng ưu tiên: _format attribute > Accept > $default. */
		public function getPreferredFormat(?string $default = 'html'): ?string {
			if ($format = $this->getRequestFormat(null)) return $format;
			foreach ($this->getAcceptableContentTypes() as $type) {
				if ($format = $this->getFormat($type)) return $format;
			}
			return $default;
		}

		protected function parseAcceptHeader(string $header): array {
			if ($header === '') return [];
			$items = [];
			foreach (explode(',', $header) as $part) {
				$bits = explode(';', trim($part));
				$name = strtolower(trim($bits[0]));
				if ($name === '') continue;
				$q = 1.0;
				foreach (array_slice($bits, 1) as $param) {
					if (preg_match('/^\s*q=([\d.]+)/', $param, $m)) $q = (float)$m[1];
				}
				$items[$name] = $q;
			}
			arsort($items);
			return array_keys($items);
		}

		public function expectsJson(): bool {
			return ($this->ajax() && !$this->pjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
		}

		public function wantsJson(): bool {
			$acceptable = $this->getAcceptableContentTypes();
			if (!isset($acceptable[0])) return false;
			$first = strtolower($acceptable[0]);
			return str_contains($first, '/json') || str_contains($first, '+json');
		}

		public function accepts(string|array $contentTypes): bool {
			$accepts = $this->getAcceptableContentTypes();
			if (count($accepts) === 0) return true;

			$types = (array)$contentTypes;
			foreach ($accepts as $accept) {
				if ($accept === '*/*' || $accept === '*') return true;
				foreach ($types as $type) {
					$accept = strtolower($accept);
					$type   = strtolower($type);
					if ($this->matchesType($accept, $type) || $accept === strtok($type, '/') . '/*') return true;
				}
			}
			return false;
		}

		public function prefers(string|array $contentTypes): ?string {
			$accepts      = $this->getAcceptableContentTypes();
			$contentTypes = (array)$contentTypes;

			foreach ($accepts as $accepted) {
				if (in_array($accepted, ['*/*', '*'], true)) return $contentTypes[0];
				foreach ($contentTypes as $contentType) {
					$type   = $this->getMimeType($contentType) ?? $contentType;
					$accept = strtolower($accepted);
					$type   = strtolower($type);
					if ($this->matchesType($type, $accept) || $accept === strtok($type, '/') . '/*') return $contentType;
				}
			}
			return null;
		}

		public function acceptsAnyContentType(): bool {
			$acceptable = $this->getAcceptableContentTypes();
			return count($acceptable) === 0 || (isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*'));
		}

		public function acceptsJson(): bool {
			return $this->accepts('application/json');
		}

		public function acceptsHtml(): bool {
			return $this->accepts('text/html');
		}

		public function matchesType(string $actual, string $type): bool {
			if ($actual === $type) return true;
			$split = explode('/', $actual);
			return isset($split[1]) && preg_match('#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote($split[1], '#') . '#', $type);
		}

		public function format(string $default = 'html'): string {
			foreach ($this->getAcceptableContentTypes() as $type) {
				if ($format = $this->getFormat($type)) return $format;
			}
			return $default;
		}

		/* ------------------------------------------------------------------ *
		 | Languages / locale
		 * ------------------------------------------------------------------ */

		public function getLanguages(): array {
			return $this->languages ??= $this->parseLanguages();
		}

		protected function parseLanguages(): array {
			$header = (string)$this->headers->get('Accept-Language', '');
			if ($header === '') return [];
			$langs = [];
			foreach (explode(',', $header) as $part) {
				$bits = explode(';', trim($part));
				$code = str_replace('-', '_', trim($bits[0]));
				if ($code === '') continue;
				$q = 1.0;
				foreach (array_slice($bits, 1) as $p) {
					if (preg_match('/q=([\d.]+)/', $p, $m)) $q = (float)$m[1];
				}
				$langs[$code] = $q;
			}
			arsort($langs);
			return array_keys($langs);
		}

		public function getPreferredLanguage(?array $locales = null): ?string {
			$preferred = $this->getLanguages();
			if (!$locales) return $preferred[0] ?? null;
			if (!$preferred) return $locales[0];

			$norm = fn($l) => str_replace('-', '_', $l);
			foreach ($preferred as $p) {
				foreach ($locales as $l) {
					if (strcasecmp($norm($l), $p) === 0) return $l;
				}
				$lang = strstr($p, '_', true) ?: $p;
				foreach ($locales as $l) {
					if (strcasecmp(strstr($norm($l), '_', true) ?: $norm($l), $lang) === 0) return $l;
				}
			}
			return $locales[0];
		}

		public function getLocale(): string {
			return $this->locale ?? $this->defaultLocale;
		}

		public function setLocale(string $locale): void {
			$this->locale = $locale;
		}

		public function getDefaultLocale(): string {
			return $this->defaultLocale;
		}

		public function setDefaultLocale(string $locale): void {
			$this->defaultLocale = $locale;
		}

		public function setRequestLocale(string $locale): void {
			$this->locale = $locale;
		}

		public function setDefaultRequestLocale(string $locale): void {
			$this->defaultLocale = $locale;
		}

		/* ================================================================== *
		 | Body / JSON
		 * ================================================================== */

		public function getContent(): string {
			return $this->content ??= (string)file_get_contents('php://input');
		}

		/** $request->json() => ParameterBag; $request->json('a.b') => value. */
		public function json(?string $key = null, $default = null) {
			if ($this->json === null) {
				$content    = trim($this->getContent());
				$decoded    = json_decode($content === '' ? '[]' : $content, true);
				$this->json = new ParameterBag(is_array($decoded) ? $decoded : []);
			}
			if ($key === null) return $this->json;
			return $this->dataGet($this->json->all(), $key, $default);
		}

		public function setJson(ParameterBag $json): static {
			$this->json = $json;
			return $this;
		}

		/* ================================================================== *
		 | Input (InteractsWithInput)
		 * ================================================================== */

		protected function getInputSource(): ParameterBag {
			if ($this->isJson()) return $this->json();
			return in_array($this->getRealMethod(), ['GET', 'HEAD'], true) ? $this->query : $this->request;
		}

		public function server(?string $key = null, $default = null) {
			return $key === null ? $this->server->all() : $this->server->get($key, $default);
		}

		public function all($keys = null): array {
			$input = array_replace_recursive($this->input(), $this->allFiles());
			if ($keys === null) return $input;

			$results = [];
			foreach (is_array($keys) ? $keys : func_get_args() as $key) {
				$this->dataSet($results, (string)$key, $this->dataGet($input, (string)$key));
			}
			return $results;
		}

		public function input(?string $key = null, $default = null) {
			$data = $this->getInputSource()->all() + $this->query->all();
			return $key === null ? $data : $this->dataGet($data, $key, $default);
		}

		public function query(?string $key = null, $default = null) {
			return $key === null ? $this->query->all() : $this->dataGet($this->query->all(), $key, $default);
		}

		public function post(?string $key = null, $default = null) {
			return $key === null ? $this->request->all() : $this->dataGet($this->request->all(), $key, $default);
		}

		public function keys(): array {
			return array_merge(array_keys($this->input()), $this->files->keys());
		}

		protected function keysFrom(array $args): array {
			return is_array($args[0] ?? null) ? $args[0] : $args;
		}

		public function exists(...$keys): bool {
			return $this->has(...$keys);
		}

		public function has(...$keys): bool {
			$input = $this->all();
			foreach ($this->keysFrom($keys) as $value) {
				if (!$this->dataHas($input, (string)$value)) return false;
			}
			return true;
		}

		public function hasAny(...$keys): bool {
			$input = $this->all();
			foreach ($this->keysFrom($keys) as $value) {
				if ($this->dataHas($input, (string)$value)) return true;
			}
			return false;
		}

		/** True when at least one key is absent (mirrors Laravel: !has($keys)). */
		public function missing(...$keys): bool {
			return !$this->has($this->keysFrom($keys));
		}

		public function filled(...$keys): bool {
			foreach ($this->keysFrom($keys) as $value) {
				if ($this->isEmptyString((string)$value)) return false;
			}
			return true;
		}

		public function isNotFilled(...$keys): bool {
			foreach ($this->keysFrom($keys) as $value) {
				if (!$this->isEmptyString((string)$value)) return false;
			}
			return true;
		}

		public function anyFilled(...$keys): bool {
			foreach ($this->keysFrom($keys) as $key) {
				if ($this->filled($key)) return true;
			}
			return false;
		}

		protected function isEmptyString(string $key): bool {
			$value = $this->input($key);
			return !is_bool($value) && !is_array($value) && trim((string)$value) === '';
		}

		public function whenHas(string $key, callable $callback, ?callable $default = null) {
			if ($this->has($key)) return $callback($this->dataGet($this->all(), $key)) ?: $this;
			if ($default) return $default();
			return $this;
		}

		public function whenFilled(string $key, callable $callback, ?callable $default = null) {
			if ($this->filled($key)) return $callback($this->dataGet($this->all(), $key)) ?: $this;
			if ($default) return $default();
			return $this;
		}

		public function whenMissing(string $key, callable $callback, ?callable $default = null) {
			if ($this->missing($key)) return $callback($this->dataGet($this->all(), $key)) ?: $this;
			if ($default) return $default();
			return $this;
		}

		public function str(string $key, $default = null) {
			return $this->string($key, $default);
		}

		public function string(string $key, $default = null) {
			$value = $this->input($key, $default);
			return class_exists('\Illuminate\Support\Stringable') ? new \Illuminate\Support\Stringable($value) : (string)$value;
		}

		public function boolean(?string $key = null, bool $default = false): bool {
			return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
		}

		public function integer(string $key, int $default = 0): int {
			return intval($this->input($key, $default));
		}

		public function float(string $key, float $default = 0.0): float {
			return floatval($this->input($key, $default));
		}

		public function date(string $key, ?string $format = null, ?string $tz = null) {
			if ($this->isNotFilled($key)) return null;
			$value = $this->input($key);

			if (class_exists('\Carbon\Carbon')) {
				return $format === null
					? \Carbon\Carbon::parse($value, $tz)
					: \Carbon\Carbon::createFromFormat($format, $value, $tz);
			}
			$zone = $tz ? new DateTimeZone($tz) : null;
			return $format === null ? new DateTimeImmutable($value, $zone) : DateTimeImmutable::createFromFormat($format, $value, $zone);
		}

		public function enum(string $key, string $enumClass, $default = null) {
			if ($this->isNotFilled($key) || !function_exists('enum_exists') || !enum_exists($enumClass) || !method_exists($enumClass, 'tryFrom')) {
				return static::value($default);
			}
			return $enumClass::tryFrom($this->input($key)) ?: static::value($default);
		}

		public function enums(string $key, string $enumClass): array {
			if ($this->isNotFilled($key) || !function_exists('enum_exists') || !enum_exists($enumClass) || !method_exists($enumClass, 'tryFrom')) {
				return [];
			}
			return array_values(array_filter(
				array_map(fn($v) => $enumClass::tryFrom($v), (array)$this->input($key))
			));
		}

		public function collect($key = null) {
			$data = is_array($key) ? $this->only($key) : $this->input($key);
			$data = is_array($data) ? $data : (array)$data;
			return class_exists('\Illuminate\Support\Collection') ? new \Illuminate\Support\Collection($data) : new \ArrayObject($data);
		}

		public function only(...$keys): array {
			$input   = $this->all();
			$results = [];
			foreach ($this->keysFrom($keys) as $key) {
				if ($this->dataHas($input, (string)$key)) $this->dataSet($results, (string)$key, $this->dataGet($input, (string)$key));
			}
			return $results;
		}

		public function except(...$keys): array {
			$results = $this->all();
			foreach ($this->keysFrom($keys) as $key) $this->dataForget($results, (string)$key);
			return $results;
		}

		public function merge(array $input): static {
			$source = $this->getInputSource();
			$all    = $source->all();
			foreach ($input as $key => $value) $this->dataSet($all, (string)$key, $value);
			$source->replace($all);
			return $this;
		}

		public function mergeIfMissing(array $input): static {
			return $this->merge(array_filter($input, fn($v, $k) => $this->missing($k), ARRAY_FILTER_USE_BOTH));
		}

		public function replace(array $input): static {
			$this->getInputSource()->replace($input);
			return $this;
		}

		/** Symfony-style getter (deprecated in Laravel in favour of input()). */
		public function get(string $key, mixed $default = null): mixed {
			if ($this->attributes->has($key)) return $this->attributes->get($key);
			if ($this->query->has($key)) return $this->query->get($key);
			if ($this->request->has($key)) return $this->request->get($key);
			return $default;
		}

		public function dump(...$keys): static {
			$keys = $this->keysFrom($keys);
			$data = count($keys) > 0 ? $this->only($keys) : $this->all();
			function_exists('dump') ? dump($data) : var_dump($data);
			return $this;
		}

		public function dd(...$keys) {
			$this->dump(...$keys);
			exit(1);
		}

		/* ================================================================== *
		 | Cookies, files
		 * ================================================================== */

		public function hasCookie(string $key): bool {
			return $this->cookies->has($key);
		}

		public function cookie(?string $key = null, $default = null) {
			return $key === null ? $this->cookies->all() : $this->dataGet($this->cookies->all(), $key, $default);
		}

		public function allFiles(): array {
			return $this->files->all();
		}

		public function file(?string $key = null, $default = null) {
			return $key === null ? $this->allFiles() : $this->dataGet($this->allFiles(), $key, $default);
		}

		public function hasFile(string $key): bool {
			$files = $this->file($key);
			if (!is_array($files)) $files = [$files];
			foreach ($files as $file) {
				if ($this->isValidFile($file)) return true;
			}
			return false;
		}

		protected function isValidFile($file): bool {
			return $file instanceof \SplFileInfo && $file->getPath() !== '';
		}

		/** Remove empty entries (e.g. file inputs with no upload) recursively. */
		protected function filterFiles($files) {
			if (!$files) return null;

			foreach ($files as $key => $file) {
				if (is_array($file)) $files[$key] = $this->filterFiles($files[$key]);
				if (empty($files[$key])) unset($files[$key]);
			}
			return $files;
		}

		/* ================================================================== *
		 | Session & flash data (InteractsWithFlashData)
		 * ================================================================== */

		public function hasSession(bool $skipIfUninitialized = false): bool {
			return $this->session !== null;
		}

		public function getSession() {
			if (!$this->hasSession()) throw new RuntimeException('Session not found on request.');
			return $this->session;
		}

		public function session() {
			if (!$this->hasSession()) throw new RuntimeException('Session store not set on request.');
			return $this->session;
		}

		public function setLaravelSession($session): void {
			$this->session = $session;
		}

		public function setSession($session): void {
			$this->session = $session;
		}

		public function old(?string $key = null, $default = null) {
			return $this->hasSession() ? $this->session()->getOldInput($key, $default) : $default;
		}

		public function flash(): void {
			$this->session()->flashInput($this->filterFiles($this->input()) ?? []);
		}

		public function flashOnly(...$keys): void {
			$this->session()->flashInput($this->only(...$keys));
		}

		public function flashExcept(...$keys): void {
			$this->session()->flashInput($this->except(...$keys));
		}

		public function flush(): void {
			$this->session()->flashInput([]);
		}

		/* ================================================================== *
		 | Precognition (CanBePrecognitive)
		 * ================================================================== */

		public function isAttemptingPrecognition(): bool {
			return $this->header('Precognition') === 'true';
		}

		public function isPrecognitive(): bool {
			return (bool)$this->attributes->get('precognitive', false);
		}

		/* ================================================================== *
		 | Route & user
		 * ================================================================== */

		public function user($guard = null) {
			return call_user_func($this->getUserResolver(), $guard);
		}

		public function getUserResolver(): Closure {
			return $this->userResolver ?: function() {
				return null;
			};
		}

		public function setUserResolver(Closure $callback): static {
			$this->userResolver = $callback;
			return $this;
		}

		/** Resolver returns a route object (with parameter()/parameters()) or an array of parameters. */
		public function route(?string $param = null, $default = null) {
			$route = call_user_func($this->getRouteResolver());
			if ($route === null || $param === null) return $route;

			if (is_array($route)) return $this->dataGet($route, $param, $default);
			if (is_object($route) && method_exists($route, 'parameter')) return $route->parameter($param, $default);
			return static::value($default);
		}

		public function getRouteResolver(): Closure {
			return $this->routeResolver ?: function() {
				return null;
			};
		}

		public function setRouteResolver(Closure $callback): static {
			$this->routeResolver = $callback;
			return $this;
		}

		/** Unique hash for route + IP. */
		public function fingerprint(): string {
			if (!($route = $this->route()) || !is_object($route)) {
				throw new RuntimeException('Unable to generate fingerprint. Route unavailable.');
			}
			return sha1(implode('|', array_merge(
				$route->methods(),
				[$route->getDomain(), $route->uri(), $this->ip()]
			)));
		}

		/* ================================================================== *
		 | Conditionable & Macroable
		 * ================================================================== */

		public function when($value = null, ?callable $callback = null, ?callable $default = null) {
			$value = $value instanceof Closure ? $value($this) : $value;
			if ($value) return $callback ? ($callback($this, $value) ?? $this) : $this;
			if ($default) return $default($this, $value) ?? $this;
			return $this;
		}

		public function unless($value = null, ?callable $callback = null, ?callable $default = null) {
			$value = $value instanceof Closure ? $value($this) : $value;
			if (!$value) return $callback ? ($callback($this, $value) ?? $this) : $this;
			if ($default) return $default($this, $value) ?? $this;
			return $this;
		}

		public static function macro(string $name, callable $macro): void {
			static::$macros[$name] = $macro;
		}

		public static function mixin(object $mixin, bool $replace = true): void {
			$methods = (new ReflectionClass($mixin))->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
			foreach ($methods as $method) {
				if ($replace || !static::hasMacro($method->name)) {
					$method->setAccessible(true);
					static::macro($method->name, $method->invoke($mixin));
				}
			}
		}

		public static function hasMacro(string $name): bool {
			return isset(static::$macros[$name]);
		}

		public static function flushMacros(): void {
			static::$macros = [];
		}

		public static function __callStatic(string $method, array $args) {
			if (!static::hasMacro($method)) {
				throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
			}
			$macro = static::$macros[$method];
			if ($macro instanceof Closure) $macro = $macro->bindTo(null, static::class);
			return $macro(...$args);
		}

		public function __call(string $method, array $args) {
			if (!static::hasMacro($method)) {
				throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $method));
			}
			$macro = static::$macros[$method];
			if ($macro instanceof Closure) $macro = $macro->bindTo($this, static::class);
			return $macro(...$args);
		}

		/* ================================================================== *
		 | Arrayable / ArrayAccess / magic
		 * ================================================================== */

		public function toArray(): array {
			return $this->all();
		}

		/** Raw HTTP message (request line + headers + body), như Symfony. */
		public function __toString(): string {
			$headers = '';
			foreach ($this->headers->all() as $name => $values) {
				$name = implode('-', array_map('ucfirst', explode('-', $name)));
				foreach ($values as $value) $headers .= $name . ': ' . $value . "\r\n";
			}
			return sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(), $this->getProtocolVersion() ?? 'HTTP/1.1') . "\r\n"
				. $headers . "\r\n" . $this->getContent();
		}

		public function jsonSerialize(): mixed {
			return $this->all();
		}

		public function offsetExists(mixed $offset): bool {
			$route  = $this->route();
			$params = is_array($route) ? $route : (is_object($route) && method_exists($route, 'parameters') ? $route->parameters() : []);
			return $this->dataHas($this->all() + $params, (string)$offset);
		}

		public function offsetGet(mixed $offset): mixed {
			return $this->__get((string)$offset);
		}

		public function offsetSet(mixed $offset, mixed $value): void {
			$this->getInputSource()->set((string)$offset, $value);
		}

		public function offsetUnset(mixed $offset): void {
			$this->getInputSource()->remove((string)$offset);
		}

		public function __isset(string $key): bool {
			return $this->__get($key) !== null;
		}

		public function __get(string $key) {
			return $this->dataGet($this->all(), $key, fn() => $this->route($key));
		}

		/* ================================================================== *
		 | Helpers (data_get / Arr::set / Arr::has / Arr::forget)
		 * ================================================================== */

		protected static function value($value) {
			return $value instanceof Closure ? $value() : $value;
		}

		protected function dataGet(array $array, ?string $key, $default = null) {
			if ($key === null) return $array;
			if (array_key_exists($key, $array)) return $array[$key];

			foreach (explode('.', $key) as $segment) {
				if (is_array($array) && array_key_exists($segment, $array)) $array = $array[$segment];
				else return static::value($default);
			}
			return $array;
		}

		protected function dataHas(array $array, string $key): bool {
			if (array_key_exists($key, $array)) return true;
			foreach (explode('.', $key) as $segment) {
				if (is_array($array) && array_key_exists($segment, $array)) $array = $array[$segment];
				else return false;
			}
			return true;
		}

		protected function dataSet(array &$array, string $key, $value): void {
			$segments = explode('.', $key);
			while (count($segments) > 1) {
				$seg = array_shift($segments);
				if (!isset($array[$seg]) || !is_array($array[$seg])) $array[$seg] = [];
				$array = &$array[$seg];
			}
			$array[array_shift($segments)] = $value;
		}

		protected function dataForget(array &$array, string $key): void {
			if (array_key_exists($key, $array)) {
				unset($array[$key]);
				return;
			}
			$segments = explode('.', $key);
			while (count($segments) > 1) {
				$seg = array_shift($segments);
				if (!isset($array[$seg]) || !is_array($array[$seg])) return;
				$array = &$array[$seg];
			}
			unset($array[array_shift($segments)]);
		}

	}
}