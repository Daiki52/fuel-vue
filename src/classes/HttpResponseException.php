<?php

namespace FuelVue;

/**
 * Response を例外として返却するための HttpException。
 */
class HttpResponseException extends \Fuel\Core\HttpException
{
	/** @var \Fuel\Core\Response|null */
	private $response;

	/**
	 * @param \Fuel\Core\Response|null $response
	 */
	public function __construct($response = null)
	{
		$this->response = $response;
	}

	/**
	 * @return \Fuel\Core\Response|null
	 */
	protected function response()
	{
		return $this->response;
	}
}
