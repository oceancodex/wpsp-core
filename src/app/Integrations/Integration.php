<?php

namespace WPSPCORE\App\Integrations;

use Illuminate\Contracts\Container\BindingResolutionException;
use WPSPCORE\App\Routes\RouteTrait;
use WPSPCORE\BaseInstances;

class Integration extends BaseInstances {

	use RouteTrait;

	public $intergrationPackages = [];

	/**
	 * Đăng ký tất cả các gói tích hợp trong một thư mục
	 *
	 * Phương thức này quét tất cả các thư mục con trong đường dẫn được chỉ định,
	 * tìm kiếm các class gói tích hợp trong mỗi thư mục, và đăng ký chúng.
	 * Mỗi gói tích hợp phải có class chính trong namespace tương ứng.
	 *
	 * @param string $dirPath Đường dẫn tuyệt đối đến thư mục chứa các gói tích hợp
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	public function registerAllIntegrationPackages($dirPath) {
		$allIntegrationPackageDirs = $this->funcs->_getAllDirsInDir($dirPath, 0);
		$allIntegrationPackageDirs = array_column($allIntegrationPackageDirs, 'absolute_path');

		$packageClasses = [];

		foreach ($allIntegrationPackageDirs as $integrationPackageDir) {
			$nameSpace = $this->funcs->_getRootNamespace() . '\App\Widen\Integrations\\' . basename($integrationPackageDir);
			$mainClass = $this->funcs->_getAllClassesInDir($integrationPackageDir, $nameSpace, 0)[0] ?? null;
			if ($mainClass) {
				$packageClasses[] = $mainClass;
			}
		}

		// Lưu toàn bộ các gói tích hợp.
		$this->intergrationPackages = $packageClasses;

		// Đăng ký mỗi gói tích hợp.
		foreach ($packageClasses as $packageClass) {
			$this->registerIntergrationPackage($packageClass);
		}
	}

	/**
	 * Đăng ký các gói tích hợp cụ thể
	 *
	 * Phương thức này nhận vào một mảng các class gói tích hợp và đăng ký từng gói.
	 * Khác với registerAllIntegrationPackages, phương thức này cho phép đăng ký
	 * một danh sách các gói tích hợp được chỉ định trước thay vì quét toàn bộ thư mục.
	 *
	 * @param array $packageClasses Mảng các tên class đầy đủ (fully qualified names) của các gói tích hợp cần đăng ký
	 *
	 * @return void
	 */
	public function registerSpecificIntegrationPackages($packageClasses) {
		foreach ($packageClasses as $packageClass) {
			$this->registerIntergrationPackage($packageClass);
		}
	}

	/**
	 * Đăng ký một gói tích hợp
	 *
	 * Phương thức này kiểm tra xem class gói tích hợp có tồn tại và có phương thức init hay không.
	 * Nếu có, nó sẽ khởi tạo gói thông qua container, kiểm tra trạng thái kích hoạt,
	 * và thực thi phương thức init nếu gói được kích hoạt.
	 *
	 * @param string $packageClass Tên đầy đủ (fully qualified name) của class gói tích hợp
	 *
	 * @return void
	 */
	public function registerIntergrationPackage($packageClass) {
		try {
			if (class_exists($packageClass) && method_exists($packageClass, 'init')) {
				$package = $this->funcs->_getApplication()->make($packageClass);
				$activate = $package->getActivate();
				if ($activate) {
					$callback = $this->prepareRouteCallback([$package, 'init']);
					$this->resolveAndCall($callback);
				}
			}
		}
		catch (\Exception $e) {
			if ($this->funcs->_isDebug()) {
				error_log('['.$this->funcs->_config('app.name').']' . $e->getMessage());
			}
		}
	}

}