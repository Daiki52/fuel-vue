<?php

namespace FuelVue;

/**
 * deferred props の状態管理を行うトレイト。
 */
trait DefersProps
{
	/** @var bool */
	protected $deferred = false;
	/** @var string|null */
	protected $defer_group = null;

	/**
	 * 遅延対象として設定します。
	 *
	 * @param string|null $group
	 * @return $this
	 */
	public function defer($group = null)
	{
		$this->deferred = true;
		$this->defer_group = $group;
		return $this;
	}

	/**
	 * 遅延対象かどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldDefer()
	{
		return $this->deferred;
	}

	/**
	 * 遅延グループ名を返します。
	 *
	 * @return string
	 */
	public function group()
	{
		return $this->defer_group !== null ? $this->defer_group : 'default';
	}
}
