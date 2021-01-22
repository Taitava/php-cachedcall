# php-cachedcall
A simple PHP trait that makes it easy to cache class method call results within a single session.

Good for:
 - Caching class method calls. Uses class private properties for caching.
 - A trait that is easy to apply to your own classes.
 - Simple and short - you can read and understand the source code in just a couple of minutes, so no need to buy a pig in a sack. :)

Not good for:
 - **Can't be used to store the cache to persist between sessions.** After your program completes, the cache will be gone.
 - Not meaningful to use for caching functions outside of classes due to the trait based design. But this can be improved later if needed.

# Installation

Use composer:
```sh
composer require "taitava/php-cachedcall:*"
```

# Usage

Say you have a class `Page` like this:

```php
class Page
{
  public $template = "page_template.html";
  public $title;
  public $author;
  public $content;
  public $date;

  public function __construct($data)
  {
    foreach ($data as $key => $value)
    {
      $this->$key = $value;
    }
  }

  public static function getPageFromDB($id)
  {
    $data = ORM::table("Page")->getById($id); // ORM class is not defined in this example, but let's just pretend that it exists. :)
    return new self($data);
  }

  public function RenderContent()
  {
    $template = file_get_contents($this->template);
    $data = [
      $this->title,
      $this->author,
      $this->content,
      $this->date,
    ];
    // ...
    // Do some placeholder replacements to $template with $data here...
    // ...
    return $template
  }
}
```

So we have two methods (plus a contructor) and we would like to cache both of them in order to avoid rerunning code if the methods get called multiple times with same parameters. Our code transforms to this:

```php
class Page
{
  use Taitava\CachedCall\CachedCallTrait; // Apply the trait for this class.

  public $template = "page_template.html";
  public $title;
  public $author;
  public $content;
  public $date;

  public function __construct($data)
  {
    foreach ($data as $key => $value)
    {
      $this->$key = $value;
    }
  }

  public static function getPageFromDB($id)
  {
    return static::cached_static_call(__METHOD__, func_get_args(), function ($id)
    {
      $data = ORM::table("Page")->getById($id); // ORM class is not defined in this example, but let's just pretend that it exists. :)
      return new self($data);
     });
  }

  public function renderContent()
  {
    return $this->cached_call(__METHOD__, func_get_args() /* Actually this is an empty array because this method has no parameters. */, function ()
    {
      $template = file_get_contents($this->template);
      $data = [
        $this->title,
        $this->author,
        $this->content,
        $this->date,
      ];
      // ...
      // Do some placeholder replacements to $template with $data here...
      // ...
      return $template
    });
  }
}
```

`getPageFromDB()` is a static method that takes `$id` as a parameter. `renderContent()` method is non-static. When caching a non-static method, you should call `$this->cached_call()` method, where as in static methods you should call `self::cached_static_call()` method (or `static::cached_static_call()`). Remember to add the `_static_` word in between when needed!

Both `cached_call()` and `cached_static_call()` have a similar set of parameters:
 - string `$method_name`: Name of the method from which you call `cached_call()`/`cached_static_call()`. You can use `__METHOD__`. Part of the cache key.
 - array `$parameters`: Any parameters you want to pass to $call function. Will also be part of the cache key. Use `func_get_args()` if you don't need to alter the parameters in any way.
 - callable `$call`: A function that will be called **if** no cache with a matching `$method_name`+`$parameters` cache key was found.
 - boolean `$enable_cache`: Defaults to true. Set to false if you want to temporarily test your code without caching.

## Limitations regarding `$parameters`
Please note that the `$parameters` can only contain:
 - scalar values: numbers, strings, booleans.
 - objects that do have a recognisable identifier field: `$ID`, `$id`, `$Id` or `$iD`

The following are not accepted:
 - arrays
 - objects without a recognised id property
 - resources
 - anything that I have forgotten to list here

This limitation is in place because generating a cache key needs to be a fast process and it's hard to define a reliable cache key for i.e. a big array whose content can change between method calls. Also object support is quite limited because an object needs to have an id property. `id()` or `getId()` methods are not supported. All limitations can be improved later if better cache key generating ways come to mind.

To overcome some of the limitations, you can for example bypass an array parameter like this:

```php
class Page
{
  use Taitava\CachedCall\CachedCallTrait; // Apply the trait for this class.

  public $template = "page_template.html";
  public $title;
  public $author;
  public $content;
  public $date;

  // ...

  public function doSomething(array $an_array)
  {
    $stringified_array = implode(",", $an_array); // Create a custom cache key. This can be done IF we know that the array is likely to be *small*. Be careful!
    $parameters = [$stringified_array];
    return $this->cached_call(__METHOD__, $parameters, function () use ($an_array)
    {
      // ...
      // Do something with $an_array...
      // ...
      return $result
    });
  }
}
```

Note that we avoided passing `$an_array` to `cached_call()`, but we passed it to our closure function with the `use ($an_array)` statement! So we are able to use the array in it's original form.

# Contributing
Ideas, bug reports and pull requests of fixes and new features are all welcome :).

# Author
Jarkko Linnanvirta
