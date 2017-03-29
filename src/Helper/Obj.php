<?php

namespace Tense\Helper;

class Obj {
	public static function merge($a, $b/*, $c, ...*/) {
		if (func_num_args() > 2) {
			// merge objects from the end
			$objectsReversed = array_reverse(func_get_args());
			return array_reduce($objectsReversed, function ($mergedObject, $object) {
				return self::merge($object, $mergedObject);
			}, new \stdClass());
		}

		if (! $a || ! $b) {
			return $a ? $a : $b;
		}

		foreach ($b as $property => $value) {
			if (! property_exists($a, $property)) {
				$a->{$property} = $value;
			} elseif (is_object($value) && is_object($a->{$property})) {
				$a->{$property} = self::merge($a->{$property}, $value);
			}
		}

		return $a;
	}

	public static function get($object, $property, $default = null) {
		if (! is_object($object)) {
			return $default;
		}

		return property_exists($object, $property) ? $object->{$property} : $default;
	}
}