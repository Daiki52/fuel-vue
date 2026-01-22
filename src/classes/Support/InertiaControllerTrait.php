<?php

namespace FuelVue\Support;

/**
 * Inertia コントローラ用の初期化と例外処理を提供するトレイト。
 */
trait InertiaControllerTrait
{
	/**
	 * REST ルーティングに合わせてアクションを解決します。
	 *
	 * @param string $resource
	 * @param array $arguments
	 * @return mixed
	 */
	public function router($resource, $arguments)
	{
		$controller_method = strtolower(\Input::method()) . '_' . $resource;

		if (!method_exists($this, $controller_method)) {
			$controller_method = 'action_' . $resource;
		}

		try {
			if (method_exists($this, $controller_method)) {
				return call_fuel_func_array(array($this, $controller_method), $arguments);
			}
			throw new \Fuel\Core\HttpNotFoundException();
		} catch (\Throwable $e) {
			$response = $this->on_exception($e);
			if ($response !== null) {
				return $response;
			}
			throw $e;
		} finally {
			$this->on_finally();
		}
	}

	/**
	 * 例外時の Inertia レスポンスを組み立てます。
	 *
	 * @param \Throwable $e
	 * @return mixed|null
	 */
	protected function on_exception(\Throwable $e)
	{
		if (!$this->should_render_exception()) {
			return null;
		}

		// FuelVueが生成した例外はエラーページに置き換えない
		if ($e instanceof \FuelVue\HttpResponseException) {
			return null;
		}

		$component = \FuelVue\InertiaGlobalStore::getErrorPageComponent();
		return \FuelVue\Inertia::render($component, [
			'status' => $this->resolve_http_status($e),
		]);
	}

	/**
	 * 例外画面の描画可否を返します。
	 *
	 * @return bool
	 */
	protected function should_render_exception()
	{
		return isset(\Fuel\Core\Fuel::$env)
			&& \Fuel\Core\Fuel::$env === \Fuel\Core\Fuel::PRODUCTION;
	}

	/**
	 * HttpException からステータスコードを解決します。
	 *
	 * @param \Throwable $e
	 * @return int
	 */
	protected function resolve_http_status(\Throwable $e)
	{
		if ($e instanceof \Fuel\Core\HttpBadRequestException) {
			return 400;
		}
		if ($e instanceof \Fuel\Core\HttpNoAccessException) {
			return 403;
		}
		if ($e instanceof \Fuel\Core\HttpNotFoundException) {
			return 404;
		}
		if ($e instanceof \Fuel\Core\HttpServerErrorException) {
			return 500;
		}

		return 500;
	}

	/**
	 * 成功/例外どちらでも呼ばれるフックです。
	 *
	 * @return void
	 */
	protected function on_finally() {}
}
