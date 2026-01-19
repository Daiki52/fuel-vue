<?php

namespace FuelVue;

/**
 * Inertia レスポンスの生成とセッション処理を担うサービス。
 */
class InertiaService
{
	/**
	 * テンプレート側で Inertia 用の head タグを挿入します。
	 *
	 * @return string
	 */
	public static function inertiaHead()
	{
		return "";
	}

	/**
	 * Inertia の data-page 付きルート要素を出力します。
	 *
	 * @return string
	 */
	public static function inertia()
	{
		$page = InertiaGlobalStore::getRenderParams();
		$page_json = htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8');
		return "<div id=\"app\" data-page=\"{$page_json}\"></div>";
	}

	/**
	 * 現在のURLを _previous.url に保存します。
	 *
	 * @param string $url
	 * @return void
	 */
	public static function setPreviousUrl($url)
	{
		InertiaGlobalStore::setPreviousUrl($url);
	}

	/**
	 * 直前URLが無い場合はセッションや base を使って補完します。
	 *
	 * @return string
	 */
	public static function getPreviousUrlWithFallback()
	{
		return \Input::referrer() ?: InertiaGlobalStore::getPreviousUrlFallback() ?: \Uri::base();
	}

	/**
	 * 検証エラーを処理します。
	 * Precognition の場合は 204/422 を返し、通常リクエストではリダイレクトバックします。
	 *
	 * @param array $errors
	 * @return void
	 * @throws HttpResponseException
	 */
	public static function processValidation($errors = [], $bag = 'default')
	{
		$errors = self::filter_precognition_errors($errors);

		if (!self::is_precognition_request())
		{
			if (empty($errors))
			{
				return;
			}

			$reuqest_bag = self::get_error_bag_name();
			if ($reuqest_bag != $bag) {
				return;
			}
			$response = (new ResponseFactory())
				->back()
				->flash_raw(SessionKey::ERROR, [
					$bag => $errors,
				])
				->withInput((array) \Fuel\Core\Input::json());
			throw new HttpResponseException($response);
		}

		$response = self::build_precognition_response($errors);
		throw new HttpResponseException($response);
	}

	/**
	 * Inertia レスポンスを生成します。
	 *
	 * @param string $component
	 * @param array $props
	 * @return \FuelVue\InertiaResponse
	 */
	public static function render($component, $props = [])
	{
		/** @var InertiaResponse $response */
		$response = InertiaResponse::forge();
		return $response->prepare($component, $props);
	}

	/**
	 * Inertia のルートビューを変更します。
	 *
	 * @param string $path
	 * @return void
	 */
	public static function setInertiaRootViewPath($path)
	{
		InertiaGlobalStore::setRootViewPath($path);
	}

	/**
	 * 全ページで共有する props を登録します。
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
	 * 共有 props を取得します。
	 *
	 * @return array
	 */
	public static function getSharedProps()
	{
		return InertiaGlobalStore::getSharedProps();
	}

	/**
	 * Inertia のページ情報を保持します（テンプレート側で使用）。
	 *
	 * @param array $page
	 * @return void
	 */
	public static function setRenderParams(array $page)
	{
		InertiaGlobalStore::setRenderParams($page);
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
		return InertiaGlobalStore::getFlashData($key, $default);
	}

	/**
	 * inertia 用のセッションデータを削除します。
	 *
	 * @return void
	 */
	public static function clearFlashData()
	{
		InertiaGlobalStore::clearFlashData();
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
		return InertiaGlobalStore::getRawFlashData($key, $default);
	}

	/**
	 * AJAX リクエストかどうかを返します。
	 *
	 * @return bool
	 */
	private static function is_ajax_request()
	{
		return \Fuel\Core\Input::is_ajax();
	}

	/**
	 * GET リクエストかどうかを返します。
	 *
	 * @return bool
	 */
	private static function is_get_request()
	{
		return strtoupper(\Fuel\Core\Input::method()) === 'GET';
	}

	/**
	 * Precognition リクエストかどうかを返します。
	 *
	 * @return bool
	 */
	private static function is_precognition_request()
	{
		return strtolower((string) self::header('Precognition')) === 'true';
	}

	/**
	 * エラーバッグ名を取得します。
	 *
	 * @return string
	 */
	private static function get_error_bag_name()
	{
		$bag = trim((string) self::header('X-Inertia-Error-Bag'));
		return $bag !== '' ? $bag : 'default';
	}

	/**
	 * Precognition 用のレスポンスを生成します。
	 *
	 * @param array $errors
	 * @return \Fuel\Core\Response
	 */
	private static function build_precognition_response($errors)
	{
		if (empty($errors))
		{
			return \Fuel\Core\Response::forge('', 204)
				->set_header('Precognition', 'true')
				->set_header('Precognition-Success', 'true');
		}

		return \Fuel\Core\Response::forge(json_encode(['errors' => $errors]), 422)
			->set_header('Content-Type', 'application/json')
			->set_header('Precognition', 'true');
	}

	/**
	 * Precognition 用のエラーのみを抽出します。
	 *
	 * @param array $errors
	 * @return array
	 */
	private static function filter_precognition_errors($errors)
	{
		if (!self::is_precognition_request())
		{
			return $errors;
		}

		if (!is_array($errors))
		{
			return [];
		}

		$only_header = (string) self::header('Precognition-Validate-Only');
		if ($only_header === '')
		{
			return $errors;
		}

		$only_fields = array_values(array_filter(array_map('trim', explode(',', $only_header)), 'strlen'));
		if (empty($only_fields))
		{
			return $errors;
		}

		$filtered = [];
		foreach ($only_fields as $field)
		{
			if (array_key_exists($field, $errors))
			{
				$filtered[$field] = $errors[$field];
			}
		}

		return $filtered;
	}

	/**
	 * HTTP ヘッダを取得します。
	 *
	 * @param string $name
	 * @return string|null
	 */
	private static function header($name)
	{
		$value = \Fuel\Core\Input::headers($name, null);
		if ($value !== null)
		{
			return $value;
		}

		$key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
		return $_SERVER[$key] ?? null;
	}
}
