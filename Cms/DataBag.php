<?php

namespace Cms;

use
	Cms\DataBag\NoValueException,
	Cms\DataBag\DataBagInterface;


/**
 * Обертка над массивом данных
 *
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 * @package Cms
 */
class DataBag implements DataBagInterface
{
	/**
	 * Хранимые данные
	 *
	 * @var array
	 */
	protected $data = [];


	/**
	 * Формирование обертки
	 *
	 * @param array $data
	 */
	public function __construct(array $data=[])
	{
		$this->add($data);
	}

	/**
	 * Получение значения из обернутых данных
	 *
	 * @param  string $key     Ключ доступа к данным
	 * @param  mixed  $default Значение по умолчанию
	 * @param  string $type    Строгая проверка типа возвращаемых данных
	 *
	 * @return mixed
	 */
	public function get($key, $default=null, $type='mixed')
	{
		try {
			$result = $this->getValue($key);
		}
		catch (NoValueException $e) {
			return $default;
		}

		return
			$this->validateType($result, $type) ?
				$result :
				$default;
	}

	/**
	 * Получение сырого значения из обернутых данных
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return mixed
	 */
	public function getRawValue($key)
	{
		return $this->getValue($key);
	}

	/**
	 * Получение значения из обернутых данных
	 *
	 * @param  string $key     Ключ доступа к данным
	 *
	 * @return \Cms\DataBag\DataBagInterface
	 */
	public function getBag($key)
	{
		return new self($this->get($key, [], 'array'));
	}

	/**
	 * Устновка значения по ключу
	 *
	 * @param string $key   Ключ доступа к данным
	 * @param mixed  $value Значение
	 * @param bool   $merge Примешивать данные
	 */
	public function set($key, $value, $merge=false)
	{
		$val = &$this->data;
		foreach ($this->keyToArray($key) as $part) {
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
	 * Добавление значения по ключу
	 *
	 * @param string $key   Ключ доступа к данным
	 * @param mixed  $value Значение
	 */
	public function push($key, $value)
	{
		$val = &$this->data;
		foreach ($this->keyToArray($key) as $part) {
			if (! is_array($val) || ! array_key_exists($part, $val)) {
				$val[$part] = [];
			}

			$val = &$val[$part];
		}

		$val []= $value;
	}

	/**
	 * @param array $data
	 */
	public function add(array $data)
	{
		foreach ($data as $key => $val) {
			$this->set($key, $val, true);
		}
	}

	/**
	 * Проверка, установлено ли значение по ключу
	 *
	 * @param  string $key  Ключ доступа к данным
	 * @param  string $type Строгая проверка типа возвращаемых данных
	 *
	 * @return bool
	 */
	public function has($key, $type='mixed')
	{
		try {
			$result = $this->getValue($key);
		}
		catch (NoValueException $e) {
			return false;
		}

		return $this->validateType($result, $type);
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
	 * Получение всех данных из хранилища
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->getValue('');
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

	/**
	 * Внутренняя реализация извлечения значения из исходных данных
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return mixed
	 *
	 * @throws \Cms\DataBag\NoValueException
	 */
	protected function getValue($key)
	{
		$val = $this->data;
		foreach ($this->keyToArray($key) as $part) {
			if (! is_array($val) || ! array_key_exists($part, $val)) {
				throw new NoValueException($part);
			}

			$val = $val[$part];
		}

		return $val;
	}

	/**
	 * Внутренняя реализация строгой проверки данных на соответствие типу
	 *
	 * @param  mixed  $value Значение
	 * @param  string $type  Тип, с которым выполняется сравнение
	 *
	 * @return bool
	 */
	protected function validateType($value, $type)
	{
		if ($type == 'mixed') {
			return true;
		}

		$function_name = 'is_' . $type;
		if (function_exists($function_name)) {
			return $function_name($value);
		}

		return false;
	}

	/**
	 * Получение количества элементов
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return int
	 */
	public function count($key='')
	{
		if (strlen($key) == 0) {
			return count($this->data);
		}

		if (! $this->has($key)) {
			return 0;
		}

		return count($this->get($key, [], 'array'));
	}
}
