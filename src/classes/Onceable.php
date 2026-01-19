<?php

namespace FuelVue;

/**
 * Once prop の挙動を提供するインターフェース。
 */
interface Onceable
{
	/**
	 * 一度だけ解決するかどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldResolveOnce();

	/**
	 * 強制的に再送するかどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldBeRefreshed();

	/**
	 * OnceProp のキーを返します。
	 *
	 * @return string|null
	 */
	public function getKey();

	/**
	 * 有効期限（ミリ秒）を返します。
	 *
	 * @return int|null
	 */
	public function expiresAt();
}
