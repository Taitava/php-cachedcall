<?php

namespace Taitava\CachedCall;

trait CachedCallTrait
{
	/**
	 * Whether cached_call() should store the call results to cache - usually it should. This can
	 * be set to false to test/debug things without caching.
	 *
	 * @var bool
	 */
	
	protected $enable_cached_calls = true;
	
	/**
	 * Whether cached_static_call() should store the call results to cache - usually it should. This can
	 * be set to false to test/debug things without caching.
	 *
	 * @var bool
	 */
	protected static $enable_cached_static_calls = true;
	
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
	 * @return mixed Returns whatever $call returns.
	 * @throws CacheKeyGeneratingException
	 */
	protected function cached_call($method_name, $parameters, $call)
	{
		if (!$this->enable_cached_calls)
		{
			// Caching is disabled for i.e. temporary testing
			return call_user_func_array($call, $parameters);
		}
		
		// Check if we already have a cached result
		$cache_key = CacheHelper::cache_key($method_name, $parameters);
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
	 * @return mixed Returns whatever $call returns.
	 * @throws CacheKeyGeneratingException
	 */
	protected static function cached_static_call($method_name, $parameters, $call)
	{
		if (static::$enable_cached_static_calls)
		{
			// Caching is disabled for i.e. temporary testing
			return call_user_func_array($call, $parameters);
		}
		
		// Check if we already have a cached result
		$cache_key = CacheHelper::cache_key($method_name, $parameters);
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
	
}