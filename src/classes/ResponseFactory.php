<?php

namespace FuelVue;

/**
 * リダイレクトとフラッシュデータ操作をまとめたレスポンス生成クラス。
 */
class ResponseFactory extends \Fuel\Core\Response
{
	/**
	 * リダイレクト先を設定します。
	 *
	 * @param string $url
	 * @param int $status
	 * @return $this
	 */
	public function to($url, $status = 303)
	{
		$this->set_status($status);
		$this->set_header('Location', $url);
		return $this;
	}

	/**
	 * 直前のURLへリダイレクトします。
	 *
	 * @param int $status
	 * @return $this
	 */
	public function back($status = 303)
	{
		$previous_url = InertiaService::getPreviousUrlWithFallback();
		return $this->to($previous_url, $status);
	}

	/**
	 * inertia 用のフラッシュデータを保存します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return $this
	 */
	public function with($key, $value = null)
	{
		return $this->flash($key, $value);
	}

	/**
	 * old input をフラッシュに保存します。
	 *
	 * @param array $input
	 * @return $this
	 */
	public function withInput(array $input)
	{
		return $this->flash_raw(SessionKey::OLD_INPUT, $input);
	}

	/**
	 * inertia 用のフラッシュデータを保存します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return $this
	 */
	public function flash($key, $value = null)
	{
		InertiaGlobalStore::flash($key, $value);
		return $this;
	}

	/**
	 * 生のキーでフラッシュデータを保存します。
	 * これは Session::get_flash() で取得可能です。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return $this
	 */
	public function flash_raw($key, $value = null)
	{
		InertiaGlobalStore::flash_raw($key, $value);
		return $this;
	}
}
