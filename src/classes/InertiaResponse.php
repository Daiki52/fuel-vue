<?php

namespace FuelVue;

/**
 * Inertia レスポンスを構築する Response クラス。
 */
class InertiaResponse extends \Fuel\Core\Response
{
	/**
	 * Inertia::location 用のレスポンスを生成します。
	 * Inertia リクエスト時は 409 + X-Inertia-Location を返し、
	 * 非 Inertia リクエスト時は通常のリダイレクトを返します。
	 *
	 * @param string $url
	 * @return \Fuel\Core\Response
	 */
	public static function location($url)
	{
		$response = new \Fuel\Core\Response();
		if (self::is_inertia_request_static())
		{
			$response->set_status(409);
			$response->set_header('X-Inertia-Location', $url);
			$response->body('');
			return $response;
		}

		$response->set_status(302);
		$response->set_header('Location', $url);
		return $response;
	}

	/** @var string|null */
	private $component;
	/** @var array */
	private $props = [];
	/** @var bool */
	private $built = false;

	/**
	 * Inertia レスポンスの設定を受け取ります。
	 * 実際の構築は send/__toString のタイミングで行います。
	 *
	 * @param string $component
	 * @param array $props
	 * @return $this
	 */
	public function prepare($component, $props = [])
	{
		if ($this->built)
		{
			throw new \RuntimeException('InertiaResponse has already been built.');
		}

		$this->component = $component;
		$this->props = $props;
		$this->built = false;

		return $this;
	}

	/**
	 * クライアントへレスポンスを送信します。
	 *
	 * @param bool $send_headers
	 * @return void
	 */
	public function send($send_headers = false)
	{
		$this->ensure_built();
		return parent::send($send_headers);
	}

	/**
	 * HTTP ヘッダを送信します。
	 *
	 * @return void
	 */
	public function send_headers()
	{
		$this->ensure_built();
		return parent::send_headers();
	}

	/**
	 * ボディの getter/setter を提供します。
	 *
	 * @param string|false $value
	 * @return string|$this
	 */
	public function body($value = false)
	{
		if (func_num_args() === 1)
		{
			return parent::body($value);
		}
		$this->ensure_built();
		return parent::body();
	}

	/**
	 * 送信されるレスポンスボディを文字列として取得します。
	 *
	 * @return string
	 */
	public function __toString()
	{
		$this->ensure_built();
		return parent::__toString();
	}

	/**
	 * inertia 用フラッシュデータを保存します。
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @return void
	 */
	public function flash($key, $value = null)
	{
		(new ResponseFactory())->flash($key, $value);
	}

	/**
	 * build を一度だけ実行します。
	 *
	 * @return void
	 */
	public function ensure_built()
	{
		if ($this->built)
		{
			return;
		}

		$this->built = true;
		$this->build();
	}

	/**
	 * Inertia レスポンスを構築します。
	 *
	 * @return void
	 */
	private function build()
	{
		$url = $_SERVER['REQUEST_URI'] ?? '/';
		$version = $this->asset_version();
		$merged_props = array_merge(InertiaGlobalStore::getSharedProps(), $this->props);

		$partial_component = $this->_get_header('X-Inertia-Partial-Component');
		$only = $this->get_only_props();
		$except = $this->get_except_props();
		$except_once = $this->get_except_once_props();
		$is_partial = $this->is_partial_request_with($partial_component, $this->component);

		$deferred_props = $this->build_deferred_props_metadata($merged_props, $is_partial, $except_once);
		$once_props = $this->build_once_props_metadata($merged_props, $only, $except);
		$merge_props = $this->build_merge_props_metadata($merged_props, $only, $except);

		$props = $this->apply_partial_props_filter($merged_props, $this->component, $partial_component, $only, $except);
		$props = $this->apply_deferred_props_filter($props, $is_partial);
		$props = $this->apply_once_props_filter($props, $is_partial, $except_once);
		$props = $this->merge_flash_errors($props);
		$props = $this->merge_flash_old_input($props);
		$props = $this->resolve_lazy_props($props);
		$props = $this->normalize_json_props($props);
		$page = $this->build_inertia_page($this->component, $props, $url, $version, $once_props, $merge_props, $deferred_props);

		$flash = InertiaGlobalStore::getFlashData();
		if (!empty($flash))
		{
			$page['flash'] = $flash;
		}
		InertiaGlobalStore::clearFlashData();

		if ($this->is_get_request())
		{
			InertiaGlobalStore::setPreviousUrl($url);
		}

		if ($this->is_inertia_request())
		{
			$client_version = $this->_get_header('X-Inertia-Version');
			if ($this->is_get_request() && $client_version && $client_version !== $version)
			{
				$this->set_status(409);
				$this->set_header('X-Inertia-Location', $url);
				$this->body('');
				return;
			}

			$this->set_json_response($page, $version);
			return;
		}

		InertiaGlobalStore::setRenderParams($page);
		$this->body(\Fuel\Core\View::forge(InertiaGlobalStore::getRootViewPath(), [
			'page' => $page,
		]));
	}

	/**
	 * Inertia のページデータを構築します。
	 *
	 * @param string $component
	 * @param array $props
	 * @param string $url
	 * @param string|null $version
	 * @param array $once_props
	 * @param array $merge_props
	 * @param array $deferred_props
	 * @return array
	 */
	private function build_inertia_page($component, $props, $url, $version, array $once_props, array $merge_props, array $deferred_props)
	{
		$page = [
			'component' => $component,
			'props' => $props,
			'url' => $url,
			'version' => $version,
			// Note: History Encryption は FuelVue ではサポートしていません
			'clearHistory' => false,
			'encryptHistory' => false,
		];
		if (!empty($once_props))
		{
			$page['onceProps'] = $once_props;
		}
		if (!empty($merge_props))
		{
			$page = array_merge($page, $merge_props);
		}
		if (!empty($deferred_props))
		{
			$page['deferredProps'] = $deferred_props;
		}
		return $page;
	}

	/**
	 * JSON レスポンスのヘッダと body を設定します。
	 *
	 * @param array $page
	 * @param string|null $version
	 * @return void
	 */
	private function set_json_response($page, $version)
	{
		$this->set_status(200);
		$this->set_header('Content-Type', 'application/json');
		$this->set_header('X-Inertia', 'true');
		$this->set_header('Vary', 'Accept');
		if (!empty($version))
		{
			$this->set_header('X-Inertia-Version', $version);
		}

		$this->body(json_encode($page));
	}

	/**
	 * Inertia の部分リロード用ヘッダに応じて props をフィルタします。
	 *
	 * @param array $props
	 * @param string $component
	 * @return array
	 */
	private function apply_partial_props_filter(array $props, $component, $partial_component, array $only, array $except)
	{
		if ($partial_component && $partial_component === $component)
		{
			return $this->filter_partial_props($props, $only, $except);
		}

		return $this->filter_optional_props($props);
	}

	/**
	 * 遅延 props を通常ロードから除外します。
	 *
	 * @param array $props
	 * @param array $only
	 * @return array
	 */
	private function apply_deferred_props_filter(array $props, $is_partial)
	{
		if ($is_partial)
		{
			return $props;
		}

		$filtered = [];
		foreach ($props as $key => $value)
		{
			if ($value instanceof Deferrable && $value->shouldDefer())
			{
				continue;
			}
			$filtered[$key] = $value;
		}

		return $filtered;
	}

	/**
	 * OnceProp の再送制御を行います。
	 * X-Inertia-Except-Once-Props が指定されている場合は該当 prop を除外します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function apply_once_props_filter(array $props, $is_partial, array $except_once)
	{
		if (!$this->is_inertia_request() || $is_partial)
		{
			return $props;
		}

		if (empty($except_once))
		{
			return $props;
		}

		$filtered = [];
		foreach ($props as $key => $value)
		{
			if (!$value instanceof Onceable)
			{
				$filtered[$key] = $value;
				continue;
			}

			if (!$this->should_exclude_once_prop($value, $key, $except_once))
			{
				$filtered[$key] = $value;
				continue;
			}

		}

		return $filtered;
	}

	/**
	 * onceProps のメタ情報を構築します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function build_once_props_metadata(array $props, array $only, array $except)
	{
		$metadata = [];
		foreach ($props as $key => $value)
		{
			if (!$value instanceof Onceable)
			{
				continue;
			}

			if (!$value->shouldResolveOnce())
			{
				continue;
			}

			if (!$this->should_include_prop($key, $only, $except))
			{
				continue;
			}

			$prop_key = $value->getKey();
			$prop_key = $prop_key ?: $key;

			$metadata[$prop_key] = [
				'prop' => $key,
				'expiresAt' => $value->expiresAt(),
			];
		}

		return $metadata;
	}

	/**
	 * 遅延 props のメタ情報を構築します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function build_deferred_props_metadata(array $props, $is_partial, array $except_once)
	{
		if ($is_partial)
		{
			return [];
		}

		$metadata = [];
		foreach ($props as $key => $value)
		{
			if (!$value instanceof Deferrable || !$value->shouldDefer())
			{
				continue;
			}

			$prop_key = $value instanceof Onceable ? ($value->getKey() ?: $key) : $key;
			if (in_array($prop_key, $except_once, true))
			{
				continue;
			}

			$group = $value->group();
			if (!isset($metadata[$group]))
			{
				$metadata[$group] = [];
			}
			$metadata[$group][] = $key;
		}

		return $metadata;
	}

	/**
	 * 部分リロード時の props を optional/always を考慮してフィルタします。
	 *
	 * @param array $props
	 * @param array $only
	 * @param array $except
	 * @return array
	 */
	private function filter_partial_props(array $props, array $only, array $except)
	{
		$filtered = [];
		foreach ($props as $key => $value)
		{
			$is_always = $value instanceof AlwaysProp;
			$is_optional = $value instanceof OptionalProp;

			if (!empty($only))
			{
				if (in_array($key, $only, true) || $is_always)
				{
					$filtered[$key] = $this->unwrap_prop($value);
				}
				continue;
			}

			if (!empty($except) && in_array($key, $except, true) && !$is_always)
			{
				continue;
			}

			if ($is_optional)
			{
				continue;
			}

			$filtered[$key] = $this->unwrap_prop($value);
		}

		return $filtered;
	}

	/**
	 * 通常ロード時に OptionalProp を除外し、AlwaysProp を展開します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function filter_optional_props(array $props)
	{
		$filtered = [];
		foreach ($props as $key => $value)
		{
			if ($value instanceof OptionalProp)
			{
				continue;
			}

			$filtered[$key] = $this->unwrap_prop($value);
		}

		return $filtered;
	}

	/**
	 * フラッシュされたバリデーションエラーを props に合成します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function merge_flash_errors(array $props)
	{
		$flash_errors = InertiaGlobalStore::getRawFlashData(SessionKey::ERROR);
		if (!is_array($flash_errors) || empty($flash_errors))
		{
			return $props;
		}

		$bag = $this->get_error_bag_name();
		if ($bag == 'default') {
			$flash_errors = $flash_errors['default'] ?? [];
		}

		if (empty($flash_errors))
		{
			return $props;
		}

		$props['errors'] = array_merge($props['errors'] ?? [], $flash_errors);
		return $props;
	}

	/**
	 * フラッシュされた old input を props に合成します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function merge_flash_old_input(array $props)
	{
		$flash_old = InertiaGlobalStore::getRawFlashData(SessionKey::OLD_INPUT);
		if (!is_array($flash_old) || empty($flash_old))
		{
			return $props;
		}

		$props['old'] = array_merge($props['old'] ?? [], $flash_old);
		return $props;
	}

	/**
	 * 遅延評価のプロパティを解決します。
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function resolve_lazy_props($value)
	{
		if ($value instanceof OptionalProp || $value instanceof AlwaysProp || $value instanceof OnceProp || $value instanceof MergeProp || $value instanceof DeferredProp)
		{
			$value = $value->value();
		}

		if ($value instanceof \Closure || (is_object($value) && is_callable($value)))
		{
			return $this->resolve_lazy_props($value());
		}

		if (is_array($value))
		{
			$resolved = [];
			foreach ($value as $key => $item)
			{
				$resolved[$key] = $this->resolve_lazy_props($item);
			}
			return $resolved;
		}

		return $value;
	}

	/**
	 * JSON 互換の配列に正規化します。
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function normalize_json_props($value)
	{
		if (is_array($value))
		{
			$normalized = [];
			foreach ($value as $key => $item)
			{
				$normalized[$key] = $this->normalize_json_props($item);
			}
			return $normalized;
		}

		if (is_null($value) || is_scalar($value))
		{
			return $value;
		}

		if (is_object($value))
		{
			if ((class_exists('\\Orm\\Model') && $value instanceof \Orm\Model)
				|| (class_exists('\\Fuel\\Core\\Model_Crud') && $value instanceof \Fuel\Core\Model_Crud))
			{
				return $this->normalize_json_props($value->to_array());
			}
			if (method_exists($value, '__toString'))
			{
				return (string) $value;
			}

			return get_class($value);
		}

		if (is_resource($value))
		{
			return 'resource';
		}

		return (string) $value;
	}

	/**
	 * OptionalProp/AlwaysProp から素の値を取り出します。
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function unwrap_prop($value)
	{
		if ($value instanceof OptionalProp || $value instanceof AlwaysProp)
		{
			return $value->value();
		}

		return $value;
	}

	/**
	 * エラーバッグ名を取得します。
	 *
	 * @return string
	 */
	private function get_error_bag_name()
	{
		$bag = trim($this->_get_header('X-Inertia-Error-Bag'));
		return $bag !== '' ? $bag : 'default';
	}

	/**
	 * アセットのバージョンを取得します。
	 *
	 * @return string|null
	 */
	private function asset_version()
	{
		$manifest_path = FuelVue::manifest_path();
		if (is_file($manifest_path))
		{
			return hash_file('sha256', $manifest_path);
		}

		return null;
	}

	/**
	 * Inertia リクエストかどうかを返します。
	 *
	 * @return bool
	 */
	private function is_inertia_request()
	{
		return (bool) $this->_get_header('X-Inertia');
	}

	/**
	 * 静的メソッドから Inertia リクエストかどうかを判定します。
	 *
	 * @return bool
	 */
	private static function is_inertia_request_static()
	{
		return (bool) \Fuel\Core\Input::headers('X-Inertia');
	}

	/**
	 * 部分リロードかどうかを返します。
	 *
	 * @return bool
	 */
	private function is_partial_request()
	{
		$partial_component = $this->_get_header('X-Inertia-Partial-Component');
		return $this->is_partial_request_with($partial_component, $this->component);
	}

	private function is_partial_request_with($partial_component, $component)
	{
		return $partial_component !== null && $partial_component === $component;
	}

	/**
	 * マージ用メタデータを構築します。
	 *
	 * @param array $props
	 * @return array
	 */
	private function build_merge_props_metadata(array $props, array $only, array $except)
	{
		$reset = $this->get_reset_props();

		$merge_props = [];
		foreach ($props as $key => $value)
		{
			if (!$value instanceof Mergeable)
			{
				continue;
			}

			if (!$value->shouldMerge())
			{
				continue;
			}

			if (!$this->should_include_prop($key, $only, $except))
			{
				continue;
			}

			if (in_array($key, $reset, true))
			{
				continue;
			}

			$merge_props[$key] = $value;
		}

		if (empty($merge_props))
		{
			return [];
		}

		$append = $this->resolve_append_merge_props($merge_props);
		$prepend = $this->resolve_prepend_merge_props($merge_props);
		$deep = $this->resolve_deep_merge_props($merge_props);
		$match = $this->resolve_match_props_on($merge_props);

		$metadata = [];
		if (!empty($append))
		{
			$metadata['mergeProps'] = $append;
		}
		if (!empty($prepend))
		{
			$metadata['prependProps'] = $prepend;
		}
		if (!empty($deep))
		{
			$metadata['deepMergeProps'] = $deep;
		}
		if (!empty($match))
		{
			$metadata['matchPropsOn'] = $match;
		}

		return $metadata;
	}

	/**
	 * append 対象の props を取得します。
	 *
	 * @param array $merge_props
	 * @return array
	 */
	private function resolve_append_merge_props(array $merge_props)
	{
		$targets = [];
		foreach ($merge_props as $key => $prop)
		{
			if ($prop->shouldDeepMerge())
			{
				continue;
			}

			if ($prop->appendsAtRoot())
			{
				$targets[] = $key;
				continue;
			}

			foreach ($prop->appendsAtPaths() as $path)
			{
				$targets[] = $key . '.' . $path;
			}
		}

		return array_values(array_unique($targets));
	}

	/**
	 * prepend 対象の props を取得します。
	 *
	 * @param array $merge_props
	 * @return array
	 */
	private function resolve_prepend_merge_props(array $merge_props)
	{
		$targets = [];
		foreach ($merge_props as $key => $prop)
		{
			if ($prop->shouldDeepMerge())
			{
				continue;
			}

			if ($prop->prependsAtRoot())
			{
				$targets[] = $key;
				continue;
			}

			foreach ($prop->prependsAtPaths() as $path)
			{
				$targets[] = $key . '.' . $path;
			}
		}

		return array_values(array_unique($targets));
	}

	/**
	 * deep merge 対象の props を取得します。
	 *
	 * @param array $merge_props
	 * @return array
	 */
	private function resolve_deep_merge_props(array $merge_props)
	{
		$targets = [];
		foreach ($merge_props as $key => $prop)
		{
			if ($prop->shouldDeepMerge())
			{
				$targets[] = $key;
			}
		}

		return array_values(array_unique($targets));
	}

	/**
	 * matchPropsOn のメタ情報を構築します。
	 *
	 * @param MergeProp[] $merge_props
	 * @return array
	 */
	private function resolve_match_props_on(array $merge_props)
	{
		$targets = [];
		foreach ($merge_props as $key => $prop)
		{
			foreach ($prop->matchesOn() as $path)
			{
				$targets[] = $key . '.' . $path;
			}
		}

		return array_values(array_unique($targets));
	}

	/**
	 * partial reload の only/except を取得します。
	 *
	 * @return array
	 */
	private function get_only_props()
	{
		return $this->split($this->_get_header('X-Inertia-Partial-Data'));
	}

	/**
	 * partial reload の except を取得します。
	 *
	 * @return array
	 */
	private function get_except_props()
	{
		return $this->split($this->_get_header('X-Inertia-Partial-Except'));
	}

	/**
	 * reset 対象の props を取得します。
	 *
	 * @return array
	 */
	private function get_reset_props()
	{
		return $this->split($this->_get_header('X-Inertia-Reset'));
	}

	private function get_except_once_props()
	{
		return $this->split($this->_get_header('X-Inertia-Except-Once-Props'));
	}

	private function should_exclude_once_prop(Onceable $prop, $key, array $except_once)
	{
		if (!$prop->shouldResolveOnce())
		{
			return false;
		}

		if ($prop->shouldBeRefreshed())
		{
			return false;
		}

		$prop_key = $prop->getKey();
		$prop_key = $prop_key ?: $key;

		return in_array($prop_key, $except_once, true);
	}

	/**
	 * only/except を考慮して対象プロパティか判定します。
	 *
	 * @param string $key
	 * @param array $only
	 * @param array $except
	 * @return bool
	 */
	private function should_include_prop($key, array $only, array $except)
	{
		if (!empty($only) && !in_array($key, $only, true))
		{
			return false;
		}

		if (!empty($except) && in_array($key, $except, true))
		{
			return false;
		}

		return true;
	}

	/**
	 * GET リクエストかどうかを返します。
	 *
	 * @return bool
	 */
	private function is_get_request()
	{
		return strtoupper(\Fuel\Core\Input::method()) === 'GET';
	}

	/**
	 * HTTP ヘッダを取得します。
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return string|null
	 */
	private function _get_header($name, $default = null)
	{
		return \Fuel\Core\Input::headers($name, $default);
	}

	/**
	 * 逗号区切りのヘッダ値を配列に変換します。
	 *
	 * @param string|null $value
	 * @return array
	 */
	private function split($value)
	{
		if ($value === null || $value === '')
		{
			return [];
		}

		$parts = array_map('trim', explode(',', $value));
		return array_values(array_filter($parts, 'strlen'));
	}
}
