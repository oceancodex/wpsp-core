<?php
namespace OCBPCORE\Objects\File;

//use League\Flysystem\Filesystem;
//use League\Flysystem\FilesystemException;
//use League\Flysystem\Local\LocalFilesystemAdapter;
use Illuminate\Filesystem\Filesystem;

class FileHandler {
	public static ?Filesystem $fileSystem = null;

	public static function getFileSystem(): Filesystem {
		if (self::$fileSystem == null) {
			self::$fileSystem = self::initFileSystem();
		}
		return self::$fileSystem;
	}

	public static function initFileSystem(): Filesystem {
//      $adapter            = new LocalFilesystemAdapter('/');
//      static::$fileSystem = new Filesystem($adapter);
        static::$fileSystem = new Filesystem();
        return static::$fileSystem;
    }

	public static function deleteFile($filePath): array {
		try {
			self::getFileSystem()->delete($filePath);
			return [
				'success' => true,
				'data'    => [
					'deleted_file_path' => $filePath,
				],
				'message' => '',
				'code'    => 200,
			];
		}
		catch (\Exception $e) {
			_debug($e->getMessage());
			return [
				'success' => false,
				'data'    => [],
				'message' => '',
				'code'    => 100,
			];
		}
	}

	public static function saveFile($data, $filePath, $config = []): array {
		try {
			self::getFileSystem()->replace($filePath, $data);
			return [
				'success' => true,
				'data'    => [
					'saved_file_path' => $filePath,
				],
				'message' => '',
				'code'    => 200,
			];
		}
		catch (\Exception $e) {
			_debug($e->getMessage());
			return [
				'success' => false,
				'data'    => [],
				'message' => '',
				'code'    => 100,
			];
		}
	}

}