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
		return is_file(self::hot_file_path());
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
		$dev_server = rtrim(self::read_hot_url(), '/');
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
	 * hot ファイルのパスを返します。
	 *
	 * @return string
	 */
	private static function hot_file_path()
	{
		return self::public_path() . DIRECTORY_SEPARATOR . 'hot';
	}

	/**
	 * hot ファイルから開発サーバーのURLを取得します。
	 *
	 * @return string
	 * @throws FuelVueException
	 */
	private static function read_hot_url()
	{
		$path = self::hot_file_path();
		if (!is_file($path))
		{
			throw new FuelVueException('Hot file not found: ' . $path);
		}

		$url = trim(file_get_contents($path));
		if ($url === '')
		{
			throw new FuelVueException('Hot file is empty: ' . $path);
		}

		return $url;
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
