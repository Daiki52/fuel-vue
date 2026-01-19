<?php

namespace FuelVue;

/**
 * 一度だけ送信する props を表すラッパー。
 */
class OnceProp implements Onceable
{
	use ResolvesOnce;

	/** @var mixed */
	private $value;

	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
		$this->once = true;
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
