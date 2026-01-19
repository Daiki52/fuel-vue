<?php

namespace FuelVue;

/**
 * 遅延 props の挙動を提供するインターフェース。
 */
interface Deferrable
{
	/**
	 * 遅延対象かどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldDefer();

	/**
	 * 遅延グループ名を返します。
	 *
	 * @return string
	 */
	public function group();
}
