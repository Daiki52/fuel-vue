<?php

namespace FuelVue;

/**
 * Inertia 用の共有データとセッション値を扱うストア。
 */
class InertiaGlobalStore
{
	/** @var string */
	private static $inertia_root_view_path = 'index';
	/** @var array */
	private static $shared_props = array();
	/** @var array */
	private static $render_params = [];

	/**
	 * 共有 props を登録します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return void
	 */
	public static function share($key, $value = null)
	{
		if (is_array($key))
		{
			self::$shared_props = array_merge(self::$shared_props, $key);
			return;
		}

		self::$shared_props[$key] = $value;
	}

	/**
	 * 共有 props を取得します。
	 *
	 * @return array
	 */
	public static function getSharedProps()
	{
		return self::$shared_props;
	}

	/**
	 * Inertia のページ情報を保持します（テンプレート側で使用）。
	 *
	 * @param array $page
	 * @return void
	 */
	public static function setRenderParams(array $page)
	{
		self::$render_params = $page;
	}

	/**
	 * Inertia のページ情報を取得します。
	 *
	 * @return array
	 */
	public static function getRenderParams()
	{
		return self::$render_params;
	}

	/**
	 * Inertia のルートビューを設定します。
	 *
	 * @param string $path
	 * @return void
	 */
	public static function setRootViewPath($path)
	{
		self::$inertia_root_view_path = $path;
	}

	/**
	 * Inertia のルートビューを取得します。
	 *
	 * @return string
	 */
	public static function getRootViewPath()
	{
		return self::$inertia_root_view_path;
	}

	/**
	 * 指定したURLを直前URLとしてセッションに保存します。
	 *
	 * @param string $url
	 * @return void
	 */
	public static function setPreviousUrl($url)
	{
		\Session::set('_previous.url', $url);
	}

	/**
	 * セッションに保存された直前URLを取得します。
	 *
	 * @return string|null
	 */
	public static function getPreviousUrlFallback()
	{
		return \Session::get('_previous.url');
	}

	/**
	 * inertia 用に保存されたセッションデータを取得します。
	 *
	 * @param string|null $key 取得するキー。null の場合、全データを返します。
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getFlashData($key = null, $default = [])
	{
		if ($key === null)
		{
			return \Session::get(SessionKey::FLASH_DATA_PREFIX, $default);
		}
		return \Session::get(SessionKey::FLASH_DATA_PREFIX . '.' . $key, $default);
	}

	/**
	 * inertia 用のセッションデータを削除します。
	 *
	 * @return void
	 */
	public static function clearFlashData()
	{
		\Session::delete(SessionKey::FLASH_DATA_PREFIX);
	}

	/**
	 * 生のフラッシュデータを取得します。
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getRawFlashData($key, $default = [])
	{
		return \Session::get_flash($key, $default);
	}

	/**
	 * inertia 用のフラッシュデータを保存します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return void
	 */
	public static function flash($key, $value = null)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v)
			{
				self::flash_raw(SessionKey::FLASH_DATA_PREFIX . '.' . $k, $v);
			}
			return;
		}

		\Session::set(SessionKey::FLASH_DATA_PREFIX . '.' . $key, $value);
	}

	/**
	 * 生のキーでフラッシュデータを保存します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return void
	 */
	public static function flash_raw($key, $value = null)
	{
		if (is_array($key))
		{
			foreach ($key as $k => $v)
			{
				\Session::set_flash($k, $v);
			}
			return;
		}

		\Session::set_flash($key, $value);
	}
}
