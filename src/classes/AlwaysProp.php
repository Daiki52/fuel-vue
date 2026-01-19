<?php

namespace FuelVue;

/**
 * 部分リロード時でも常に送信する props を表すラッパー。
 */
class AlwaysProp
{
	/** @var mixed */
	private $value;

	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/**
	 * ラップされた値を返します。
	 *
	 * @return mixed
	 */
	public function value()
	{
		return $this->value;
	}
}
