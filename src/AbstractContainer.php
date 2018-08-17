<?php

namespace Dashifen\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class AbstractContainer
 * @package Dashifen\Container
 */
abstract class AbstractContainer {
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
}