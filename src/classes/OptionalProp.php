<?php

namespace FuelVue;

/**
 * 部分リロード時のみ送信する props を表すラッパー。
 */
class OptionalProp
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
