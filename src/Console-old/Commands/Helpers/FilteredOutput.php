<?php

namespace WPSPCORE\Console\Commands\Helpers;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class FilteredOutput extends Output {

	private OutputInterface $original;

	public function __construct(OutputInterface $original) {
		parent::__construct($original->getVerbosity(), $original->isDecorated(), $original->getFormatter());
		$this->original = $original;
	}

	protected function doWrite($message, $newline): void {
		// Bỏ qua các dòng cảnh báo Doctrine mặc định
		if (stripos($message, 'WARNING! You are about to execute a migration') !== false) {
			return;
		}
		if (stripos($message, 'Are you sure you wish to continue?') !== false) {
			return;
		}
		if (stripos($message, '>') !== false) {
			return;
		}
		if (strlen($message) < 3) {
			return;
		}
		// In bình thường các dòng khác
		$this->original->write($message, $newline);
	}

}