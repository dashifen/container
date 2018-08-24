<?php

namespace Dashifen\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use JsonSerializable;

/**
 * Class AbstractContainer
 * @package Dashifen\Container
 */
abstract class AbstractContainer implements JsonSerializable {
	/**
	 * @var array
	 */
	protected $__properties;

	/**
	 * AbstractContainer constructor.
	 *
	 * If given an associative data array, loops over its values settings
	 * properties that match indices therein.
	 *
	 * @param array $data
	 *
	 * @throws ReflectionException
	 * @throws ContainerException
	 */
	public function __construct(array $data = []) {
		$this->initializeProperties();

		foreach ($data as $field => $value) {
			if (property_exists($this, $field)) {
				$setter = "set" . ucfirst($field);
				if (method_exists($this, $setter)) {
					$this->{$setter}($value);
				} else {

					// if we find a property but it doesn't have a setter,
					// we're going to throw an exception.  children can always
					// modify this behavior if it's a problem.  or, apps can
					// catch and ignore them.  regardless, it seems worthwhile
					// to inform someone that they've probably forgotten to
					// write a method.

					throw new ContainerException("Setter missing: $setter.",
						ContainerException::UNKNOWN_SETTER);
				}
			} else {

				// similarly, if we receive data for which we do not have a
				// property, then we'll throw a different exception.  same
				// reasoning applies:  this could be a problem, and only the
				// programmer of the app using this object will know.

				throw new ContainerException("Unknown property: $field.",
					ContainerException::UNKNOWN_PROPERTY);
			}
		}
	}

	/**
	 * initializeProperties
	 *
	 * Uses a ReflectionClass to initialize an array of the names of public
	 * and protected properties of our object that should be available via
	 * the __get() method.
	 *
	 * @throws ReflectionException
	 */
	final private function initializeProperties() {

		// first, we get a list of our property names.  then, we get a list
		// of the ones that should be hidden, and we force that second list
		// to include the $__properties property so that it's not available
		// to anyone other than us.  finally, we make sure anything in the
		// second list is removed from the first.

		$properties = $this->getPropertyNames();
		$hidden = array_merge(["__properties"], $this->getHiddenPropertyNames());
		$this->__properties = array_diff($properties, $hidden);
	}

	/**
	 * getPropertyNames
	 *
	 * Uses a ReflectionClass to initialize an array of the names of public
	 * and protected properties.
	 *
	 * @return array
	 * @throws ReflectionException
	 */
	final private function getPropertyNames(): array {

		// we use the late static binding on our class name so that children
		// reflect themselves and not this object.  then, we get a list of
		// their properties such that they're

		$reflection = new ReflectionClass(static::class);
		$properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

		// we don't want an array of ReflectionProperty objects in the calling
		// scope.  so, we'll use array_map to loop over our list and return
		// only their names.

		return array_map(function (ReflectionProperty $property) {
			return $property->getName();
		}, $properties);
	}

	/**
	 * getHiddenPropertyNames
	 *
	 * Returns an array of protected properties that shouldn't be returned
	 * by the __get() method or an empty array if the should all have read
	 * access.
	 *
	 * @return array
	 */
	abstract protected function getHiddenPropertyNames(): array;

	/**
	 * convertFieldToProperty
	 *
	 * Given a string using dashes to separate words in the way HTML likes
	 * it, return a camelCase string like PHP properties like it.
	 *
	 * @param string $field
	 *
	 * @return string
	 */
	protected function convertFieldToProperty(string $field) {
		return preg_replace_callback("/-(\w)/", function($matches) {

			// for any character preceded by a dash, we want to return the
			// capital version of that letter.  notice that this also removes
			// the dash since it's included in the match.  thus, event-name
			// becomes eventName.

			return strtoupper($matches[1]);
		}, $field);
	}

	/**
	 * convertPropertyToField
	 *
	 * Given a camelCases string, return it using dashes to separate words
	 * like HTML likes it.
	 *
	 * @param string $property
	 *
	 * @return string
	 */
	protected function convertPropertyToField(string $property) {
		return preg_replace_callback("/(?<=[a-z])([A-Z])/", function($matches) {

			// this time, any capital letter preceded by a lowercase one
			// is converted to a dash followed by the lowercase version of
			// it.  unlike the prior method, we need to leave the originally
			// lowercase letter in the string, so we use a positive look-
			// behind group to identify it.  thus, eventName becomes
			// event-name.

			return "-" . strtolower($matches[1]);
		}, $property);
	}

	/**
	 * __get()
	 *
	 * Given the name of a property, if it's in the $__properties property,
	 * return it's value.
	 *
	 * @param string $property
	 *
	 * @return mixed|null
	 * @throws ReflectionException
	 * @throws ContainerException
	 */
	public function __get(string $property) {
		if (!in_array($property, $this->__properties)) {
			throw new ContainerException("Unknown property: $property.",
				ContainerException::UNKNOWN_PROPERTY);
		}

		return $this->{$property};
	}

	/**
	 * __isset()
	 *
	 * This simply returns true if the requested property exists and is
	 * not hidden.  This is to make empty() and __get() play well together.
	 *
	 * @param string $property
	 *
	 * @return bool
	 */
	public function __isset(string $property) {
		return in_array($property, $this->__properties);
	}

	/**
	 * jsonSerialize
	 *
	 * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
	 *
	 * @return array
	 */
	public function jsonSerialize() {

		// that which we want to JSON-ify is the list of properties to which
		// __get() has access.  thus, we can loop over __properties adding them
		// to an array, and then, we return them.

		foreach ($this->__properties as $property) {
			$jsonData[$property] = $this->{$property};
		}

		return $jsonData ?? [];
	}
}