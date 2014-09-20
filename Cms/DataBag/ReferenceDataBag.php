<?php

namespace Cms\DataBag;


/**
 * Class ReferenceDataBag
 * @package Cms\DataBag
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 */
class ReferenceDataBag
{
	/**
	 * Хранимые данные
	 *
	 * @var array
	 */
	protected $data = [];


	public function __construct(array &$data)
	{
		$this->data = &$data;
	}

	/**
	 * Получение значения из обернутых данных
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 *
	 * @return mixed
	 */
	public function &get($key, $default=null)
	{
		$val = &$this->data;
		foreach ($this->keyToArray($key) as $key) {
			if (! is_array($val)) {
				$val = [$key => $default];
			}
			elseif (! array_key_exists($key, $val)) {
				$val[$key] = $default;
			}

			$val = &$val[$key];
		}

		return $val;
	}

	/**
	 * Изменение значения в обернутых данных
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 */
	public function set($key, $value)
	{
		$val = & $this->get($key);
		$val = $value;
	}

	/**
	 * @param  string $key
	 *
	 * @return bool
	 */
	public function has($key)
	{
		$val = &$this->data;
		foreach ($this->keyToArray($key) as $key) {
			if (! is_array($val) || ! array_key_exists($key, $val)) {
				return false;
			}

			$val = &$val[$key];
		}

		return true;
	}

	/**
	 * Удаление данных по ключу
	 *
	 * @param string $key Ключ доступа к данным
	 */
	public function del($key)
	{
		$val = &$this->data;
		$parts = $this->keyToArray($key);
		$n = count($parts) - 1;
		foreach ($parts as $i => $part) {
			if ($i == $n) {
				unset($val[$part]);

				return;
			}

			if (! is_array($val) || ! array_key_exists($part, $val)) {
				return;
			}

			$val = &$val[$part];
		}
	}

	/**
	 * Преобразование ключа в массив
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return array
	 */
	protected function keyToArray($key)
	{
		if (strlen($key) == 0) {
			return [];
		}

		if (strpos($key, '[') !== false) {
			$key = str_replace(']', '', $key);

			return explode('[', $key);
		}

		return explode('.', $key);
	}
}
