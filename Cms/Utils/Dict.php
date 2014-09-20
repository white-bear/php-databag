<?php

namespace Cms\Utils;


/**
 * Class Dict
 * @package Cms\Utils
 * @author  Alex Shilkin <ashilkin@professionali.ru>
 */
class Dict
{
	/**
	 * @param  array $var
	 *
	 * @return bool
	 */
	static public function isAssoc(array &$var)
	{
		foreach (array_keys($var) as $i => $k) {
			if ($i !== $k) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param  array $var
	 *
	 * @return bool
	 */
	static public function isFlat(array &$var)
	{
		foreach ($var as &$val) {
			if (is_array($val)) {
				return false;
			}
			if (is_object($val) && $val instanceof \Traversable) {
				return false;
			}
		}

		return true;
	}
}
