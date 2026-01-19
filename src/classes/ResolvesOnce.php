<?php

namespace FuelVue;

/**
 * once props の状態管理を行うトレイト。
 */
trait ResolvesOnce
{
	/** @var bool */
	protected $once = false;
	/** @var bool */
	protected $refresh = false;
	/** @var int|null */
	protected $ttl = null;
	/** @var string|null */
	protected $key = null;

	/**
	 * once として扱うかどうかを設定します。
	 *
	 * @param bool $value
	 * @param string|null $as
	 * @param mixed $until
	 * @return $this
	 */
	public function once($value = true, $as = null, $until = null)
	{
		$this->once = (bool) $value;
		if ($as !== null)
		{
			$this->as($as);
		}
		if ($until !== null)
		{
			$this->until($until);
		}
		return $this;
	}

	/**
	 * 一度だけ解決するかどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldResolveOnce()
	{
		return $this->once;
	}

	/**
	 * 強制的に再送するかどうかを返します。
	 *
	 * @return bool
	 */
	public function shouldBeRefreshed()
	{
		return $this->refresh;
	}

	/**
	 * OnceProp のキーを取得します。
	 *
	 * @return string|null
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * OnceProp のキーを設定します。
	 *
	 * @param string $key
	 * @return $this
	 */
	public function as($key)
	{
		$this->key = is_string($key) ? $key : (string) $key;
		return $this;
	}

	/**
	 * 強制的に再送するかどうかを設定します。
	 *
	 * @param bool $value
	 * @return $this
	 */
	public function fresh($value = true)
	{
		$this->refresh = (bool) $value;
		return $this;
	}

	/**
	 * OnceProp の有効期限を設定します（秒 or DateInterval/DateTimeInterface）。
	 *
	 * @param mixed $delay
	 * @return $this
	 */
	public function until($delay)
	{
		$this->ttl = $this->seconds_until($delay);
		return $this;
	}

	/**
	 * 有効期限（ミリ秒）を返します。
	 *
	 * @return int|null
	 */
	public function expiresAt()
	{
		if ($this->ttl === null)
		{
			return null;
		}

		return (time() + $this->ttl) * 1000;
	}

	/**
	 * 秒数に変換します。
	 *
	 * @param mixed $delay
	 * @return int
	 */
	private function seconds_until($delay)
	{
		if ($delay instanceof \DateTimeInterface)
		{
			return max(0, $delay->getTimestamp() - time());
		}

		if ($delay instanceof \DateInterval)
		{
			$now = new \DateTimeImmutable();
			$target = $now->add($delay);
			return max(0, $target->getTimestamp() - $now->getTimestamp());
		}

		if (is_int($delay))
		{
			return max(0, $delay);
		}

		return 0;
	}
}
