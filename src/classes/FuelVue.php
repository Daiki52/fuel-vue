<?php

namespace FuelVue;

/**
 * テンプレートエンジンで Vite のビルド成果物を利用するためのクラス。
 */
class FuelVue
{
	/** @var string ビルド成果物の配置ディレクトリ名 */
	private static $build_dir = "build";
	/** @var string manifest ファイル名 */
	private static $manifest_file = "manifest.json";
	/** @var string|null 開発サーバーのURL */
	private static $dev_server = 'http://localhost:5173';
	/** @var bool|null 開発モードの明示指定 */
	private static $devMode = null;

	/**
	 * ビルド成果物の配置ディレクトリ名を変更します。
	 *
	 * @param string $dir
	 * @return void
	 */
	public static function useBuildDir($dir)
	{
		self::$build_dir = $dir;
	}

	/**
	 * manifest ファイル名を変更します。
	 *
	 * @param string $file
	 * @return void
	 */
	public static function useManifestFile($file)
	{
		self::$manifest_file = $file;
	}

	/**
	 * Vite 開発サーバーのURLを変更します。
	 *
	 * @param string $url
	 * @return void
	 */
	public static function useDevServer($url)
	{
		self::$dev_server = $url;
	}

	/**
	 * 開発モードを設定します。デフォルトでは FuelPHP の環境設定に従います。
	 *
	 * @param bool $devMode
	 * @return void
	 */
	public static function setDevMode($devMode)
	{
		self::$devMode = $devMode;
	}

	/**
	 * entry から <link>/<script> タグを生成します。
	 *
	 * @param string|array $entry_key manifest のエントリキー
	 * @return string
	 * @throws FuelVueException manifest が読めない/entry が存在しない場合
	 */
	public static function vite($entry_key)
	{
		if (self::is_dev_mode())
		{
			return self::build_dev_tags($entry_key);
		}

		return self::build_production_tags($entry_key);
	}

	/**
	 * Inertia の head 要素を生成します。
	 *
	 * @return string
	 */
	public static function inertiaHead()
	{
		return InertiaService::inertiaHead();
	}

	/**
	 * Inertia のルート要素を生成します。
	 *
	 * @return string
	 */
	public static function inertia()
	{
		return InertiaService::inertia();
	}

	/**
	 * 公開ディレクトリのパスを返します。
	 *
	 * @return string
	 */
	public static function public_path()
	{
		return rtrim(DOCROOT, DIRECTORY_SEPARATOR);
	}

	/**
	 * manifest.json のパスを返します。
	 *
	 * @return string
	 */
	public static function manifest_path()
	{
		return self::public_path()
			. DIRECTORY_SEPARATOR
			. rtrim(self::$build_dir, DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR
			. rtrim(self::$manifest_file, DIRECTORY_SEPARATOR);
	}

	/**
	 * 開発モードかどうかを返します。
	 *
	 * @return bool
	 */
	private static function is_dev_mode()
	{
		if (self::$devMode !== null)
		{
			return self::$devMode;
		}

		return isset(\Fuel\Core\Fuel::$env) && \Fuel\Core\Fuel::$env === \Fuel\Core\Fuel::DEVELOPMENT;
	}

	/**
	 * 開発サーバー向けの <script> タグを生成します。
	 *
	 * @param string|array $entry_key
	 * @return string
	 */
	private static function build_dev_tags($entry_key)
	{
		$entry_keys = is_array($entry_key) ? $entry_key : array($entry_key);
		$dev_server = rtrim(self::$dev_server, '/');
		$tags = array();
		$tags[] = '<script type="module" src="' . htmlspecialchars($dev_server . '/@vite/client', ENT_QUOTES, 'UTF-8') . '"></script>';
		foreach ($entry_keys as $entry)
		{
			$entry = ltrim($entry, '/');
			$tags[] = '<script type="module" src="' . htmlspecialchars($dev_server . '/' . $entry, ENT_QUOTES, 'UTF-8') . '"></script>';
		}

		return implode("\n", $tags);
	}

	/**
	 * manifest.json から <link>/<script> タグを組み立てます。
	 *
	 * @param string|array $entry_key
	 * @return string
	 * @throws FuelVueException
	 */
	private static function build_production_tags($entry_key)
	{
		$manifest_path = self::manifest_path();
		$manifest = self::read($manifest_path);
		if ($manifest === null)
		{
			throw new FuelVueException('Failed to read: ' . $manifest_path);
		}

		$assets_dir = dirname($manifest_path);
		if (!is_dir($assets_dir))
		{
			throw new FuelVueException('Assets directory not found: ' . $assets_dir);
		}
		$assets_url = self::normalize_assets_url($assets_dir);

		$entry_keys = is_array($entry_key) ? $entry_key : array($entry_key);
		$preload = array();
		$styles = array();
		$scripts = array();

		foreach ($entry_keys as $key)
		{
			if (!isset($manifest[$key]))
			{
				throw new FuelVueException('Entry not found in manifest: ' . $key);
			}

			$entry = $manifest[$key];
			if (isset($entry['file']))
			{
				$src = htmlspecialchars($assets_url . '/' . ltrim($entry['file'], '/'), ENT_QUOTES, 'UTF-8');
				$scripts[] = '<script type="module" src="' . $src . '"></script>';
			}

			if (isset($entry['imports']) && is_array($entry['imports']))
			{
				foreach ($entry['imports'] as $import_key)
				{
					if (!isset($manifest[$import_key]))
					{
						continue;
					}
					$import = $manifest[$import_key];
					if (isset($import['file']))
					{
						$href = htmlspecialchars($assets_url . '/' . ltrim($import['file'], '/'), ENT_QUOTES, 'UTF-8');
						$preload[] = '<link rel="modulepreload" href="' . $href . '">';
					}
					if (isset($import['css']) && is_array($import['css']))
					{
						foreach ($import['css'] as $css)
						{
							$href = htmlspecialchars($assets_url . '/' . ltrim($css, '/'), ENT_QUOTES, 'UTF-8');
							$preload[] = '<link rel="preload" as="style" href="' . $href . '">';
							$styles[] = '<link rel="stylesheet" href="' . $href . '">';
						}
					}
				}
			}

			if (isset($entry['css']) && is_array($entry['css']))
			{
				foreach ($entry['css'] as $css)
				{
					$href = htmlspecialchars($assets_url . '/' . ltrim($css, '/'), ENT_QUOTES, 'UTF-8');
					$preload[] = '<link rel="preload" as="style" href="' . $href . '">';
					$styles[] = '<link rel="stylesheet" href="' . $href . '">';
				}
			}
		}

		$preload = array_values(array_unique($preload));
		$styles = array_values(array_unique($styles));
		$scripts = array_values(array_unique($scripts));

		return implode("\n", array_merge($preload, $styles, $scripts));
	}

	/**
	 * manifest.json を読み込み、配列として返します。
	 *
	 * @param string $path
	 * @return array|null
	 */
	private static function read($path)
	{
		if (!is_file($path))
		{
			return null;
		}

		$data = json_decode(file_get_contents($path), true);
		if (!is_array($data))
		{
			return null;
		}

		return $data;
	}

	/**
	 * public 配下の assets URL を正規化します。
	 *
	 * @param string $assets_dir
	 * @return string
	 */
	private static function normalize_assets_url($assets_dir)
	{
		$assets_url = str_replace(self::public_path(), '', $assets_dir);
		if ($assets_url === '')
		{
			$assets_url = '/';
		}
		if ($assets_url[0] !== '/')
		{
			$assets_url = '/' . $assets_url;
		}
		return rtrim($assets_url, '/');
	}
}
