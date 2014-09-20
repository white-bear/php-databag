<?php

namespace Cms\DataBag;

use Cms\DataBag;
use Cms\Utils\Dict;


/**
 * Обертка над данными с возможностью выполнять выражения
 * Допустимые виды выражений:
 * ${some.var}
 * {{ some.var }}
 * {% "${some.var}" == "some val" %}
 * В случае, если выражение не удалось выполнить - возвращается значение "undefined"
 *
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 * @package Cms\DataBag
 */
class Expression extends DataBag
{
	/**
	 * Устновка значения по ключу
	 *
	 * @param string $key   Ключ доступа к данным
	 * @param mixed  $value Значение
	 * @param bool   $merge Примешивать данные
	 */
	public function set($key, $value, $merge=false)
	{
		if (is_array($value) && Dict::isAssoc($value)) {
			foreach ($value as $k => $v) {
				$this->set($key . '[' . $k . ']', $v);
			}

			return;
		}

		$val = &$this->data;
		foreach ($this->keyToArray($key) as $part) {
			if ($this->isExpression($val)) {
				$val = $this->evalExpressions($val);
			}

			if (! is_array($val) || ! array_key_exists($part, $val)) {
				$val[$part] = [];
			}

			$val = &$val[$part];
		}

		if ($merge && is_array($val) && is_array($value)) {
			$val = array_merge_recursive($val, $value);
		}
		else {
			$val = $value;
		}
	}

	/**
	 * Получение сырого значения из обернутых данных
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return mixed
	 * @throws \Cms\DataBag\NoValueException
	 */
	public function getRawValue($key)
	{
		return $this->getValueProcessor($key);
	}

	/**
	 * Извлечение значения из исходных данных
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return mixed
	 * @throws \Cms\DataBag\NoValueException
	 */
	protected function getValue($key)
	{
		return $this->evalExpressions($this->getValueProcessor($key));
	}

	/**
	 * Внутренняя реализация извлечения значения из исходных данных
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return mixed
	 * @throws \Cms\DataBag\NoValueException
	 */
	protected function getValueProcessor($key)
	{
		$val = $this->data;
		$keys = $this->keyToArray($key);

		$count = count($keys) - 1;
		foreach ($keys as $i => $key) {
			if (! is_array($val)) {
				throw new NoValueException($key);
			}

			$found = false;
			foreach (array_keys($val) as $k) {
				if ($k == $key) {
					$found = true;
					break;
				}

				$test_key = $this->evalExpression($k);
				if ($test_key == $key) {
					$found = true;
					$key = $k;
					break;
				}
			}

			if (! $found) {
				throw new NoValueException($key);
			}

			$val = $val[$key];
			// если в середине пути мы встретили строку, попробуем вычислить ее выражение
			if ($i < $count && is_string($val)) {
				$val = $this->evalExpression(trim($val));
			}
		}

		return $val;
	}

	/**
	 * Рекурсивное выполнение выражений
	 *
	 * @param  mixed $value Значение
	 *
	 * @return mixed
	 */
	protected function evalExpressions($value)
	{
		if (is_array($value)) {
			foreach ($value as &$val) {
				$val = $this->evalExpressions($val);
			}
		}
		elseif (is_string($value)) {
			return $this->evalExpression(trim($value));
		}

		return $value;
	}

	/**
	 * Выполнение выражений для конкретной строки данных
	 *
	 * @param  string $value Значение
	 *
	 * @return mixed
	 */
	protected function evalExpression($value)
	{
		if (strpos($value, '${') !== false) {
			$callback = function($matches, $export=true) {
				$key = $matches[1];
				$result = $this->get($key, 'undefined');

				return $export ?
					(is_scalar($result) && ! is_bool($result) ? $result : var_export($result, true)) :
					$result;
			};

			$pattern = '\$\{([^\{\}]+)\}';
			while (preg_match("~{$pattern}~us", $value)) {
				if (preg_match("~^{$pattern}\$~us", $value, $matches)) {
					return $callback($matches, $export=false);
				}

				$value = preg_replace_callback("~{$pattern}~us", $callback, $value);
			}
		}

		if (strpos($value, '{{') !== false) {
			$pattern = '\{\{\s(((?!\s\}\}).)+)\s\}\}';
			$callback = function($matches, $export=true) {
				$key = $matches[1];
				$default = 'undefined';

				if (preg_match('~^(.+)\|default\((.*)\)$~us', $key, $m)) {
					$key = $m[1];
					$default = $m[2];
				}

				$result = $this->get($key, $default);

				return $export ?
					(is_scalar($result) && ! is_bool($result) ? $result : var_export($result, true)) :
					$result;
			};

			if (preg_match("~^{$pattern}\$~us", $value, $matches)) {
				return $callback($matches, $export=false);
			}

			$value = preg_replace_callback("~{$pattern}~us", $callback, $value);
		}

		if (strpos($value, '{%') !== false) {
			$pattern = '\{%\s(.+?)\s%\}';
			$callback = function($matches) {
				$expr = $matches[1];
				if (strpos($expr, 'return ') === false) {
					$expr = 'return ' . $expr;
				}

				$result = '';
				eval("\$source = function() { {$expr}; }; \$result = \$source();");

				return $result;
			};

			if (preg_match("~^{$pattern}\$~us", $value, $matches)) {
				return $callback($matches);
			}

			$value = preg_replace_callback("~{$pattern}~us", $callback, $value);
		}

		return $value;
	}

	/**
	 * @param  mixed &$value
	 *
	 * @return bool
	 */
	protected function isExpression(&$value)
	{
		if (! is_string($value)) {
			return false;
		}

		if (strpos($value, '${') !== false && preg_match('~\$\{([^\{\}]+)\}~us', $value)) {
			return true;
		}

		if (strpos($value, '{{') !== false && preg_match('~\{\{\s(((?!\s\}\}).)+)\s\}\}~us', $value)) {
			return true;
		}

		if (strpos($value, '{% ') !== false && preg_match('~\{%\s(.+?)\s%\}~us', $value)) {
			return true;
		}

		return false;
	}
}
