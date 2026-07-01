<?php

namespace WPSPCORE\App\Routes;

class RouteRegexParser {

	protected string $regex;
	protected bool   $sanitize;
	protected int    $length;
	protected int    $pos = 0;

	public function __construct(string $regex, $sanitize = true) {
		$this->regex    = $regex;
		$this->sanitize = $sanitize;
		$this->length   = strlen($regex);
	}

	public function build(array &$params): string {
		$this->pos = 0;

		return $this->parse($params);
	}

	protected function parse(array &$params, string $until = null): string {
		$result = '';

		while ($this->pos < $this->length) {

			if ($until !== null &&
				substr($this->regex, $this->pos, strlen($until)) === $until) {

				$this->pos += strlen($until);
				break;
			}

			// escape
			if ($this->regex[$this->pos] === '\\') {

				$this->pos++;

				if ($this->pos >= $this->length)
					break;

				$result .= $this->regex[$this->pos];
				$this->pos++;

				continue;
			}

			// Named capture
			if (substr($this->regex, $this->pos, 4) === '(?P<') {

				$result .= $this->parseCapture($params);
				continue;
			}

			// Optional group
			if (substr($this->regex, $this->pos, 3) === '(?:') {

				$result .= $this->parseOptional($params);
				continue;
			}

			// Ignore regex syntax
			if ($this->regex[$this->pos] === '(') {

				$this->skipGroup();

				continue;
			}

			$result .= $this->regex[$this->pos];
			$this->pos++;
		}

		return $result;
	}

	protected function parseCapture(array &$params): string {
		$this->pos += 4;

		$name = '';

		while ($this->pos < $this->length && $this->regex[$this->pos] != '>') {
			$name .= $this->regex[$this->pos];
			$this->pos++;
		}

		$this->pos++;

		$depth = 1;

		while ($this->pos < $this->length && $depth) {

			if ($this->regex[$this->pos] == '\\') {
				$this->pos += 2;
				continue;
			}

			if ($this->regex[$this->pos] == '(')
				$depth++;

			if ($this->regex[$this->pos] == ')')
				$depth--;

			$this->pos++;
		}

		$value = $params[$name] ?? '';

		unset($params[$name]);

		return $this->sanitize ? rawurlencode($value) : $value;
	}

	protected function parseOptional(array &$params): string {
		$this->pos += 3;

		$start = $this->pos;

		$depth = 1;

		while ($this->pos < $this->length && $depth) {
			if ($this->regex[$this->pos] == '\\') {
				$this->pos += 2;
				continue;
			}

			if ($this->regex[$this->pos] == '(')
				$depth++;

			if ($this->regex[$this->pos] == ')')
				$depth--;

			$this->pos++;
		}

		$optional = substr(
			$this->regex,
			$start,
			$this->pos - $start - 1
		);

		if ($this->pos < $this->length && $this->regex[$this->pos] === '?') {
			$this->pos++;
		}

		preg_match_all('/\(\?P<([^>]+)>/', $optional, $m);

		foreach ($m[1] as $name) {
			if (!array_key_exists($name, $params)) {
				return '';
			}
		}

		$parser = new self($optional, $this->sanitize);

		return $parser->build($params);
	}

	protected function skipGroup(): void {
		$depth = 1;

		$this->pos++;

		while ($this->pos < $this->length && $depth) {
			if ($this->regex[$this->pos] == '\\') {
				$this->pos += 2;
				continue;
			}

			if ($this->regex[$this->pos] == '(')
				$depth++;

			if ($this->regex[$this->pos] == ')')
				$depth--;

			$this->pos++;
		}
	}

}