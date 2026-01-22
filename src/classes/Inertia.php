<?php

namespace FuelVue;

/**
 * Inertia 操作をまとめた静的ファサード。
 */
class Inertia
{
	/**
	 * OptionalProp を生成します。
	 *
	 * @param mixed $value
	 * @return OptionalProp
	 */
	public static function optional($value)
	{
		return new OptionalProp($value);
	}

	/**
	 * AlwaysProp を生成します。
	 *
	 * @param mixed $value
	 * @return AlwaysProp
	 */
	public static function always($value)
	{
		return new AlwaysProp($value);
	}

	/**
	 * OnceProp を生成します。
	 *
	 * @param mixed $value
	 * @return OnceProp
	 */
	public static function once($value)
	{
		return new OnceProp($value);
	}

	/**
	 * MergeProp を生成します。
	 *
	 * @param mixed $value
	 * @return MergeProp
	 */
	public static function merge($value)
	{
		return new MergeProp($value);
	}

	/**
	 * deep merge を行う MergeProp を生成します。
	 *
	 * @param mixed $value
	 * @return MergeProp
	 */
	public static function deepMerge($value)
	{
		return (new MergeProp($value))->deepMerge();
	}

	/**
	 * DeferredProp を生成します。
	 *
	 * @param mixed $value
	 * @param string $group
	 * @return DeferredProp
	 */
	public static function defer($value, $group = 'default')
	{
		return new DeferredProp($value, $group);
	}

	/**
	 * 全ページ共有の props を登録します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return void
	 */
	public static function share($key, $value = null)
	{
		InertiaGlobalStore::share($key, $value);
	}

	/**
	 * ルートビューのパスを設定します。
	 *
	 * @param string $path
	 * @return void
	 */
	public static function setRootView($path)
	{
		InertiaGlobalStore::setRootViewPath($path);
	}

	/**
	 * Inertia レスポンスを生成します。
	 *
	 * @param string $component
	 * @param array $props
	 * @return InertiaResponse
	 */
	public static function render($component, $props = [])
	{
		return InertiaService::render($component, $props);
	}

	/**
	 * バリデーション結果を処理します。
	 *
	 * @param array $errors
	 * @return void
	 */
	public static function processValidation($errors = [], $bag = 'default')
	{
		InertiaService::processValidation($errors, $bag);
	}

	/**
	 * ResponseFactory を生成します。
	 *
	 * @return ResponseFactory
	 */
	public static function redirect()
	{
		return new ResponseFactory();
	}

	/**
	 * 外部リダイレクト用の location レスポンスを生成します。
	 * Inertia リクエスト時は 409 + X-Inertia-Location を返します。
	 *
	 * @param string $url
	 * @return \Fuel\Core\Response
	 */
	public static function location($url)
	{
		return InertiaResponse::location($url);
	}

	/**
	 * inertia 用のフラッシュデータを保存します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return ResponseFactory
	 */
	public static function flash($key, $value = null)
	{
		return (new ResponseFactory())->flash($key, $value);
	}

	/**
	 * inertia 用に保存されたセッションデータを取得します。
	 *
	 * @param string|null $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getFlashData($key = null, $default = null)
	{
		return InertiaGlobalStore::getFlashData($key, $default);
	}

	/**
	 * inertia 用に保存されたセッションデータをすべて取得します。
	 *
	 * @return array
	 */
	public static function getFlashed()
	{
		return InertiaGlobalStore::getFlashData();
	}

	/**
	 * 生のフラッシュデータを取得します。
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function getRawFlashData($key, $default = null)
	{
		return InertiaGlobalStore::getRawFlashData($key, $default);
	}
}
