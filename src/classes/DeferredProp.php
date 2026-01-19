<?php

namespace FuelVue;

/**
 * 遅延ロード対象の props を表すラッパー。
 */
class DeferredProp implements Deferrable, Mergeable, Onceable
{
	use DefersProps;
	use MergesProps;
	use ResolvesOnce;

	/** @var mixed */
	private $value;

	/**
	 * @param mixed $value
	 * @param string $group
	 */
	public function __construct($value, $group = 'default')
	{
		$this->value = $value;
		$this->deferred = true;
		$this->defer_group = $group;
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
