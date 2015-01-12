Utility functions
=================

When you write custom controllers for your package (extended actions or custom UI pages), you will often need
to access your application's context. This is one of the main difficulties when you develop packages for Mouf.
Mouf is loaded with all its classes, but you often need to trigger a function call in your application.

For instance, you might want to modify a session variable in you application (but the session of your application
is not shared with Mouf's session).

Hopefully, Mouf comes with utility classes (proxies) that can help you to perform function calls in your application.

Performing a static method call from Mouf context in your application context
-----------------------------------------------------------------------------

From a Mouf controller, you can call any static method of in the application side using the `ClassProxy` method.

Using it is simple:

```php
// The ClassProxy instance represents a class (fully qualified name passed in parameter)
$proxy = new ClassProxy("Mouf\\Utils\\Cache\\Service\\PurgeCacheService");
// The static method is called on the proxy instance
$proxy->purgeAll();
```

In the example above, we create a **proxy** to the *PurgeCacheService*. When we call the *purgeAll* method,
the *PurgeCacheService::purgeAll* method is called. Please note this method must be **static**.

You don't want to perform a static function call? You would prefer to call a regular method call? Read below!

Performing a method call from Mouf context in one of your application instances
-------------------------------------------------------------------------------

You can also call directly a method of any instance declared in your application. Use the `InstanceProxy` to do this.

Here is a sample:

```php
// The InstanceProxy instance represents an instance
$proxy = new InstanceProxy("myInstanceName");
// You can call any method on this instance
$result = $proxy->myMethod($myParam);
```

<div class="alert alert-info">Behind the scene, the InstanceProxy and the ClassProxy classes are performing CURL
calls. This means that all the parameters you pass to the functions are serialized, and that the return value
is also serialized. You can therefore pass primitive types easily (strings, arrays...) If you want to pass objects
as parameters or as return values, the class must be available in your application and in Mouf's context.</div>