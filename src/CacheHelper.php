<?php

namespace Taitava\CachedCall;

/**
 * Class CacheHelper
 *
 * @internal Meant to be called only from inside Taitava\CachedCall package.
 */
class CacheHelper
{
	/**
	 * @internal Meant to be called only from inside Taitava\CachedCall package.
	 *
	 * Contains the internal logic used by both CachedCallTrait::cached_call() and CachedCallTrait::cached_static_call() .
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @param callable $call
	 * @param bool $enable_cache
	 * @param array $cache_array Passed by reference.
	 * @return mixed
	 * @throws CacheKeyGeneratingException
	 */
	public static function do_cached_call($method_name, $parameters, $call, $enable_cache, &$cache_array)
	{
		if (!$enable_cache)
		{
			// Caching is disabled for i.e. temporary testing
			return call_user_func_array($call, $parameters);
		}
		
		// Check if we already have a cached result
		$cache_key = static::cache_key($method_name, $parameters);
		if (static::is_call_cached($cache_key, $cache_array))
		{
			// A previous function call with the same parameters exists.
			// Return the cached result
			return $cache_array[$cache_key];
		}
		else
		{
			// No cache result was found
			// Call the function and cache the result
			$cache_array[$cache_key] = call_user_func_array($call, $parameters);
			return $cache_array[$cache_key];
		}
	}
	
	/**
	 * @param string $cache_key
	 * @param array $cache_array
	 * @return bool
	 */
	public static function is_call_cached($cache_key, array $cache_array)
	{
		return array_key_exists($cache_key, $cache_array);  // Do not use isset() because it would falsely say that cache doesn't exist if a previous call had returned null / was void.
	}
	
	/**
	 * @internal Meant to be called only from inside Taitava\CachedCall package.
	 *
	 * @param string $method_name
	 * @param array $parameters
	 * @return string
	 * @throws CacheKeyGeneratingException
	 */
	public static function cache_key($method_name, $parameters)
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