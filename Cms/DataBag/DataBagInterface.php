<?php

namespace Cms\DataBag;


/**
 * Интерфейс обертки над массивом данных
 *
 * @author  Alex Shilkin <shilkin.alexander@gmail.com>
 * @package Cms\DataBag
 */
interface DataBagInterface
{
	/**
	 * Получение значения из обернутых данных
	 *
	 * @param  string $key     Ключ доступа к данным
	 * @param  mixed  $default Значение по умолчанию
	 * @param  string $type    Строгая проверка типа возвращаемых данных
	 *
	 * @return mixed
	 */
	public function get($key, $default=null, $type='mixed');

	/**
	 * Получение сырого значения из обернутых данных
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return mixed
	 */
	public function getRawValue($key);

	/**
	 * Получение значения из обернутых данных
	 *
	 * @param  string $key     Ключ доступа к данным
	 *
	 * @return \Cms\DataBag\DataBagInterface
	 */
	public function getBag($key);

	/**
	 * Устновка значения по ключу
	 *
	 * @param string $key   Ключ доступа к данным
	 * @param mixed  $value Значение
	 * @param bool   $merge Примешивать данные
	 */
	public function set($key, $value, $merge=false);

	/**
	 * Добавление значения по ключу
	 *
	 * @param string $key   Ключ доступа к данным
	 * @param mixed  $value Значение
	 */
	public function push($key, $value);

	/**
	 * @param array $data
	 */
	public function add(array $data);

	/**
	 * Проверка, установлено ли значение по ключу
	 *
	 * @param  string $key  Ключ доступа к данным
	 * @param  string $type Строгая проверка типа возвращаемых данных
	 *
	 * @return bool
	 */
	public function has($key, $type='mixed');

	/**
	 * Удаление данных по ключу
	 *
	 * @param string $key Ключ доступа к данным
	 */
	public function del($key);

	/**
	 * Получение всех данных из хранилища
	 *
	 * @return array
	 */
	public function all();

	/**
	 * Получение количества элементов
	 *
	 * @param  string $key Ключ доступа к данным
	 *
	 * @return int
	 */
	public function count($key='');
}
