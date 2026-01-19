<?php

namespace FuelVue;

/**
 * merge props の挙動を提供するインターフェース。
 */
interface Mergeable
{
	/**
	 * マージ対象かどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldMerge();

	/**
	 * deep merge の対象かどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldDeepMerge();

	/**
	 * マージ時の突き合わせキーを返します。
	 *
	 * @return array
	 */
	public function matchesOn();

	/**
	 * root レベルへの append かどうかを返します。
	 *
	 * @return bool
	 */
	public function appendsAtRoot();

	/**
	 * root レベルへの prepend かどうかを返します。
	 *
	 * @return bool
	 */
	public function prependsAtRoot();

	/**
	 * append 対象のパスを返します。
	 *
	 * @return array
	 */
	public function appendsAtPaths();

	/**
	 * prepend 対象のパスを返します。
	 *
	 * @return array
	 */
	public function prependsAtPaths();
}
