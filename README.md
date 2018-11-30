# dashifen/container

_container_ (noun): an object that can be used to hold or transport something.

This package defines an object, AbstractContainer, that provides read-only access to its protected properties using `__get()`.  If there are protected properties that need to remain hidden, you can specify a list that won't be returned in that way.

## Installation

`composer install dashifen/container`

## Usage

There are two ways to go here:

1. Extend the `AbstractContainer` object.  
2. Extend the `Container` object.

If you extend the abstract object, you'll be forced to implement your own `getHiddenPropertyNames()` method which returns the array of protected property names that you do not want to be readable by scopes outside the object. 

The `Container` object has already implemented that method and provides read-only access to all protected properties within it. As long as you don't want to hide any of an extensions properties, extending `Container` can save a few moments and some typing. 

## Construction

The constructor for a Container takes an associative array of data such that the indices are the names of properties and the array values will be set as the values for the listed properties. If you write a setter, it'll be called with the values for validation purposes.

The constructor's array argument's indices can either be in the expected camel case for the object's properties or in kabob case as in HTML attributes.  Thus, an index of `start-date` would be "linked" to the `startDate` property. 

## Getters

Typically, because a Container exposes protected properties, getting them with the arrow operator is the way to go.  But, if you want to transform the internal representation of a property for external scopes, you can define a getter for a property that performs a transformation and returns its results.  For example, converting a date from YYYY-MM-DD format into MM/DD/YYYY for display on-screen.

Getters must be in the form of `"get" . ucfirst($propertyName)`.  So, the `startDate` property would have a getter of `getStartDate()`.  Getters can, themselves, be protected if you want to hide them from external scopes and rely on the `__get()` implementation and the arrow operator for access. 

## Setters

Containers do not implement `__set()`, so if you want setters for your properties, you have to write them yourself.  By default, the `AbstractContainer` object will use setters within it's `__construct()` method.  So, if you want to use that method, you'll need to to create them.

Like getters, they must be in the format of `"set" . ucfirst($propertyName)`.  Thus, the setter for `startDate` must be `setStartDate()`.  If you implement setters, they will be called from the Container's constructor when it iterates over it's array argument.

### Example

```php
class Foo extends AbstractContainer {
    protected $bar = "";
    protected $baz = "";
    
    protected function getHiddenPropertyNames() {
        return ["baz"];
    }
    
    public function setBar(string $bar) {
        $this->bar = $bar;   
    }
    
    public function setBaz(string $baz) {
        $this->baz = $baz;
    }
    
    protected function getBar() {
        return ucfirst($bar);
    }
}

$foo = new Foo([
    "bar" => "apple",
    "baz" => "bannana",
]);

echo $foo->bar;         // echos "Apple" because of the getBar() getter
echo $foo->baz;         // throws ContainerException (baz is "hidden")
```

## JsonSerializable

Containers implement the JsonSerializable interface.  Therefore, you can encode them and non-hidden protected properties will be included in the JSON string that action produces.