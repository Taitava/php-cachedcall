<?php

namespace Taitava\CachedCall;

trait CachedCallTrait
{
	/**
	 * Method call results related to a specific instance of a class. These come from non-static methods.
	 *
	 * @internal Do not access directly from outside of the CachedCall trait.
	 * @var array
	 */
	private $_cached_calls = [];
	
	/**
	 * Method call results related to a class, but not to any instance. These come from static methods.
	 *
	 * @internal Do not access directly from outside of the CachedCall trait.
	 * @var array
	 */
	private static $_cached_static_calls = [];
	
	/**
	 * Calls the callable $call if it has not been previously called with the same set of parameters and with this particular
	 * class instance.
	 *
	 * @param string $method_name Will be used as a part of a cache key.
	 * @param array $parameters Will be passed to $call and will also be used as part of the cache key. Note that because of the latter, only scalars and objects with an ID property can be passed as parameters!
	 * @param callable $call A function that will be called if the result cannot be found from cache. This is usually a closure function.
	 * @param bool $enable_cache Defaults to true. If you want to temporarily test your code without cache, you can set this to false, in which case $call will be called but the result will not be stored.
	 * @return mixed Returns whatever $call returns.
	 * @throws CacheKeyGeneratingException
	 */
	protected function cached_call($method_name, $parameters, $call, $enable_cache = true)
	{
		if (!$enable_cache)
		{
			// Caching is disabled for i.e. temporary testing
			return call_user_func_array($call, $parameters);
		}
		
		// Check if we already have a cached result
		$cache_key = static::_cache_key($method_name, $parameters);
		if (array_key_exists($cache_key, $this->_cached_calls)) // Do not use isset() because it would falsely say that cache doesn't exist if a previous call had returned null / was void.
		{
			// A previous function call with the same parameters exists.
			// Return the cached result
			return $this->_cached_calls[$cache_key];
		}
		else
		{
			// No cache result was found
			// Call the function and cache the result
			$this->_cached_calls[$cache_key] = call_user_func_array($call, $parameters);
			return $this->_cached_calls[$cache_key];
		}
	}
	
	/**
	 * Calls the callable $call if it has not been previously called with the same set of parameters.
	 *
	 * @param string $method_name Will be used as a part of a cache key.
	 * @param array $parameters Will be passed to $call and will also be used as part of the cache key. Note that because of the latter, only scalars and objects with an ID property can be passed as parameters!
	 * @param callable $call A function that will be called if the result cannot be found from cache. This is usually a closure function.
	 * @param bool $enable_cache Defaults to true. If you want to temporarily test your code without cache, you can set this to false, in which case $call will be called but the result will not be stored.
	 * @return mixed Returns whatever $call returns.
	 * @throws CacheKeyGeneratingException
	 */
	protected static function cached_static_call($method_name, $parameters, $call, $enable_cache = true)
	{
		if (!$enable_cache)
		{
			// Caching is disabled for i.e. temporary testing
			return call_user_func_array($call, $parameters);
		}
		
		// Check if we already have a cached result
		$cache_key = static::_cache_key($method_name, $parameters);
		if (array_key_exists($cache_key, static::$_cached_static_calls)) // Do not use isset() because it would falsely say that cache doesn't exist if a previous call had returned null / was void.
		{
			// A previous function call with the same parameters exists.
			// Return the cached result
			return static::$_cached_static_calls[$cache_key];
		}
		else
		{
			// No cache result was found
			// Call the function and cache the result
			static::$_cached_static_calls[$cache_key] = call_user_func_array($call, $parameters);
			return static::$_cached_static_calls[$cache_key];
		}
	}
	
	/**
	 * @internal This should only be called from inside the CachedCall trait.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @return string
	 * @throws CacheKeyGeneratingException
	 */
	private static function _cache_key($method_name, $parameters)
	{
		$cache_key_parts = [$method_name];
		foreach ($parameters as $parameter)
		{
			if (is_scalar($parameter))
			{
				// The parameter is a simple int, float, string or bool value.
				$cache_key_parts[] = $parameter;
			}
			elseif (is_array($parameter))
			{
				// The parameter is an array
				// Do not accept this because I haven't decided how to handle arrays performance-wise: should they be iterated (can be bad if the array is big) or what?
				throw new CacheKeyGeneratingException(__METHOD__ . ": Caching method calls with an array as a parameter is not supported.");
			}
			elseif (is_object($parameter))
			{
				// The parameter is an object
				// Try to find an identifier
				$identifier = null; // If this stays null, we will not accept this object as a cache key part because then we cannot identify the object in any way.
				$identifier_variants = ["ID", "id", "Id", "iD"]; // All case versions of "ID".
				foreach ($identifier_variants as $identifier_variant)
				{
					if (isset($parameter->$identifier_variant))
					{
						$identifier = $parameter->$identifier_variant;
					}
				}
				if (null === $identifier)
				{
					// The object does not have an identifier that we would recognise.
					throw new CacheKeyGeneratingException(__METHOD__ . ": Caching method calls with an object as a parameter is not supported. Exception: An object that would have an ID property is supported, but the passed object does not have it.");
				}
				// We have an identifier
				$cache_key_parts[] = get_class($parameter) . "#" . $identifier;
			}
			else
			{
				throw new CacheKeyGeneratingException(__METHOD__ . ": Caching method calls is not supported with a parameter variable of this type: " . gettype($parameter));
			}
		}
		return implode(" | ", $cache_key_parts);
	}
}