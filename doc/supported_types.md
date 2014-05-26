Supported types
===============

There are 4 kinds of supported types:

- Classes and interfaces
- Primitive types
- Arrays (and associative arrays)
- Mixed types

Note: if you are using a third party library that requires injecting a type that is not supported,
you can always fallback to [injecting values via PHP code](declare_instance_via_php_code.md).

##Classes and interfaces

We already saw that classes and interfaces can be used as types in the example above.

In the Mouf user interface, these properties will be displayed like this:

![Object properties](images/property_object.png)


##Primitive types

Mouf supports those primitive types:

- string
- char
- bool
- boolean
- int
- integer
- double
- float
- real
- mixed
- number

If your type is one of those, you will be able to input text directly in the property in Mouf UI.
Note that there is a special behaviour for "bool" and "boolean". They are rendered as a checkbox in Mouf UI.

For instance:

```php
/**
 * @var string
 */
public $user;

/**
 * @var int
 */
public $port;

/**
 * @var bool
 */
public $isActive;
```

will be rendered this way:

![Primitive properties](images/property_primitive.png)


##Arrays

You can also inject arrays into properties.
You can use arrays of primitive types or arrays of objects (but you cannot mix both).

There are 2 ways to write arrays: using bracket or the **array** keyword.

Here are a bunch of samples: 

- `string[]` : an array of strings
- `array<string>` : an array of strings
- `LoggerInterface[]` : an array of objects implementing LoggerInterface
- `array<LoggerInterface>` : an array of objects implementing LoggerInterface
- `array<string,string>` : an associative array of strings
- `array<string,LoggerInterface>` : an associative array of objects implementing LoggerInterface

- **Unsupported**: `array`. Because Mouf does not know if this is an array of primitive or an array of objects.

As you can notice, associative arrays (or maps) can only be achieved using the **array** notation.

Below is a screenshot showing reprensentations of various arrays in Mouf:

![Arrays](images/property_array.png)

<div class="alert">If you are trying to create an instance of a class provided by
 a third party library, it is likely that you will
have at some point a property whose type is <code>array</code>. Mouf has no clue what can be injected in an
array (is it an array of strings? of objects? a map? In this case, you can still inject the value
you want by using the <a href="declare_instance_via_php_code.md">inject property by PHP code</a> 
option that will let you type some PHP code to fill the property.</div>



##Mixed types

Mouf does not support the **mixed** keyword.
However, you can use a pipe to declare that a property can have many different types.

For instance:

- `string|ValueInterface|array<ValueInterface>` is a valid type

Using the UI, you can choose the type you want to inject by clicking on it.
Below is a sample screenshot:

![Multitype](images/property_multitype.png)
