<?php

namespace FuelVue;

/**
 * merge props の設定を管理するトレイト。
 */
trait MergesProps
{
	/** @var bool */
	protected $merge = false;
	/** @var bool */
	protected $deep_merge = false;
	/** @var array */
	protected $match_on = [];
	/** @var bool */
	protected $append = true;
	/** @var array */
	protected $appends_at_paths = [];
	/** @var array */
	protected $prepends_at_paths = [];

	/**
	 * マージ対象として設定します。
	 *
	 * @return $this
	 */
	public function merge()
	{
		$this->merge = true;
		return $this;
	}

	/**
	 * deep merge を設定します。
	 *
	 * @return $this
	 */
	public function deepMerge()
	{
		$this->deep_merge = true;
		return $this->merge();
	}

	/**
	 * マージ対象の突き合わせキーを設定します。
	 *
	 * @param string|array $match_on
	 * @return $this
	 */
	public function matchOn($match_on)
	{
		$this->match_on = $this->wrap($match_on);
		return $this;
	}

	/**
	 * マージ対象かどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldMerge()
	{
		return $this->merge;
	}

	/**
	 * deep merge の対象かどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldDeepMerge()
	{
		return $this->deep_merge;
	}

	/**
	 * マージ時に突き合わせるキーを返します。
	 *
	 * @return array
	 */
	public function matchesOn()
	{
		return $this->match_on;
	}

	/**
	 * root レベルへの append かどうかを返します。
	 *
	 * @return bool
	 */
	public function appendsAtRoot()
	{
		return $this->append && $this->mergesAtRoot();
	}

	/**
	 * root レベルへの prepend かどうかを返します。
	 *
	 * @return bool
	 */
	public function prependsAtRoot()
	{
		return !$this->append && $this->mergesAtRoot();
	}

	/**
	 * append 指定を行います。
	 *
	 * @param bool|string|array $path
	 * @param string|null $match_on
	 * @return $this
	 */
	public function append($path = true, $match_on = null)
	{
		if (is_bool($path))
		{
			$this->append = $path;
		}
		elseif (is_string($path))
		{
			$this->appends_at_paths[] = $path;
		}
		elseif (is_array($path))
		{
			foreach ($path as $key => $value)
			{
				if (is_numeric($key))
				{
					$this->append($value);
				}
				else
				{
					$this->append($key, $value);
				}
			}
		}

		if (is_string($path) && $match_on)
		{
			$this->matchOn(array_merge($this->match_on, [$path . '.' . $match_on]));
		}

		return $this;
	}

	/**
	 * prepend 指定を行います。
	 *
	 * @param bool|string|array $path
	 * @param string|null $match_on
	 * @return $this
	 */
	public function prepend($path = true, $match_on = null)
	{
		if (is_bool($path))
		{
			$this->append = !$path;
		}
		elseif (is_string($path))
		{
			$this->prepends_at_paths[] = $path;
		}
		elseif (is_array($path))
		{
			foreach ($path as $key => $value)
			{
				if (is_numeric($key))
				{
					$this->prepend($value);
				}
				else
				{
					$this->prepend($key, $value);
				}
			}
		}

		if (is_string($path) && $match_on)
		{
			$this->matchOn(array_merge($this->match_on, [$path . '.' . $match_on]));
		}

		return $this;
	}

	/**
	 * append 対象のパスを返します。
	 *
	 * @return array
	 */
	public function appendsAtPaths()
	{
		return $this->appends_at_paths;
	}

	/**
	 * prepend 対象のパスを返します。
	 *
	 * @return array
	 */
	public function prependsAtPaths()
	{
		return $this->prepends_at_paths;
	}

	/**
	 * root レベルでマージするかどうかを返します。
	 *
	 * @return bool
	 */
	private function mergesAtRoot()
	{
		return empty($this->appends_at_paths) && empty($this->prepends_at_paths);
	}

	/**
	 * 値を配列としてラップします。
	 *
	 * @param mixed $value
	 * @return array
	 */
	private function wrap($value)
	{
		if ($value === null)
		{
			return [];
		}

		return is_array($value) ? array_values($value) : [$value];
	}
}
