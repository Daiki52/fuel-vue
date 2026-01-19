<?php

namespace FuelVue;

/**
 * クライアント側でマージ対象となる props を表すラッパー。
 */
class MergeProp implements Mergeable
{
	use MergesProps;

	/** @var mixed */
	private $value;

	/**
	 * @param mixed $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
		$this->merge = true;
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
