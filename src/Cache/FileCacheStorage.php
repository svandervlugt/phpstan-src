<?php declare(strict_types = 1);

namespace PHPStan\Cache;

class FileCacheStorage implements CacheStorage
{

	/** @var string */
	private $directory;

	public function __construct(string $directory)
	{
		$this->directory = $directory;
	}

	private function makeDir(string $directory): void
	{
		$result = @mkdir($directory, 0777, true);
		if ($result === false && !is_dir($directory)) {
			throw new \InvalidArgumentException(sprintf('Directory "%s" doesn\'t exist.', $this->directory));
		}
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function load(string $key)
	{
		return (function (string $key) {
			$filePath = $this->getFilePath($key);
			return is_file($filePath) ? require $filePath : null;
		})($key);
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @return void
	 */
	public function save(string $key, $data): void
	{
		$path = $this->getFilePath($key);
		$this->makeDir(dirname($path));
		$success = @file_put_contents(
			$path,
			sprintf(
				"<?php declare(strict_types = 1);\n\nreturn %s;",
				var_export($data, true)
			)
		);
		if ($success === false) {
			throw new \InvalidArgumentException(sprintf('Could not write data to cache file %s.', $path));
		}
	}

	private function getFilePath(string $key): string
	{
		$keyHash = sha1($key);
		return sprintf(
			'%s/%s/%s/%s.php',
			$this->directory,
			substr($keyHash, 0, 2),
			substr($keyHash, 2, 2),
			$keyHash
		);
	}

}
