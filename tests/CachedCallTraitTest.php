<?php

namespace Taitava\CachedCall\Tests;

use PHPUnit\Framework\TestCase;
use Taitava\CachedCall\CachedCallTrait;

class CachedCallTraitTest extends TestCase
{
	use CachedCallTrait;
	
	// TESTS:
	
	/**
	 * @throws \Taitava\CachedCall\CacheKeyGeneratingException
	 */
	public function test_cached_call()
	{
		// Test that disabling cache works
		$this->assertEquals(
			"from non_cacheable_method 1 2",
			$this->non_cacheable_method(1, 2)
		);
		$this->assertEquals(
			"from non_cacheable_method 3 4",
			$this->non_cacheable_method(3, 4)
		);
		$this->assertFalse(
			$this->is_call_cached(static::class."::non_cacheable_method", [1, 2])
		);
		
		// Test that a non-void method uses cache
		$this->assertEquals(
			"from cacheable_method 1 2",
			$this->cacheable_method(1, 2)
		);
		$this->assertEquals(
			"from cacheable_method 3 4",
			$this->cacheable_method(3, 4)
		);
		$this->assertTrue(
			$this->is_call_cached(static::class."::cacheable_method", [1, 2])
		);
		$this->assertFalse(
			$this->is_call_cached(static::class."::cacheable_method", [5, 6]) // Different parameters -> should not be found.
		);
		
		// Test that a void method uses cache
		$this->assertNull(
			$this->cacheable_void_method(1, 2)
		);
		$this->assertTrue(
			$this->is_call_cached(static::class."::cacheable_void_method", [1, 2])
		);
		$this->assertFalse(
			$this->is_call_cached(static::class."::cacheable_void_method", [3, 4]) // Different parameters -> should not be found.
		);
	}
	
	/**
	 * @throws \Taitava\CachedCall\CacheKeyGeneratingException
	 */
	public function test_cached_static_call()
	{
		// Test that disabling cache works
		$this->assertEquals(
			"from non_cacheable_static_method 1 2",
			static::non_cacheable_static_method(1, 2)
		);
		$this->assertEquals(
			"from non_cacheable_static_method 3 4",
			static::non_cacheable_static_method(3, 4)
		);
		$this->assertFalse(
			static::is_static_call_cached(static::class."::non_cacheable_static_method", [1, 2])
		);
		
		// Test that a non-void method uses cache
		$this->assertEquals(
			"from cacheable_static_method 1 2",
			static::cacheable_static_method(1, 2)
		);
		$this->assertEquals(
			"from cacheable_static_method 3 4",
			static::cacheable_static_method(3, 4)
		);
		$this->assertTrue(
			static::is_static_call_cached(static::class . "::cacheable_static_method", [1, 2])
		);
		$this->assertFalse(
			static::is_static_call_cached(static::class . "::cacheable_static_method", [5, 6]) // Different parameters -> should not be found.
		);
		
		// Test that a void method uses cache
		$this->assertNull(
			static::cacheable_static_void_method(1, 2)
		);
		$this->assertTrue(
			static::is_static_call_cached(static::class . "::cacheable_static_void_method", [1, 2])
		);
		$this->assertFalse(
			static::is_static_call_cached(static::class . "::cacheable_static_void_method", [3, 4]) // Different parameters -> should not be found.
		);
	}
	
	
	// HELPERS:
	
	private function non_cacheable_method($a, $b)
	{
		$this->enable_cached_calls = false;
		$result = $this->cached_call(__METHOD__, func_get_args(), function ($a, $b)
		{
			return "from non_cacheable_method $a $b";
		});
		$this->enable_cached_calls = true;
		return $result;
	}
	
	private function cacheable_method($a, $b)
	{
		return $this->cached_call(__METHOD__, func_get_args(), function ($a, $b)
		{
			return "from cacheable_method $a $b";
		});
	}
	
	private function cacheable_void_method($a, $b)
	{
		$this->cached_call(__METHOD__, func_get_args(), function ($a, $b)
		{
			// Do nothing.
		});
	}
	
	private static function non_cacheable_static_method($a, $b)
	{
		static::$enable_cached_static_calls = false;
		$result = static::cached_static_call(__METHOD__, func_get_args(), function ($a, $b)
		{
			return "from non_cacheable_static_method $a $b";
		});
		static::$enable_cached_static_calls = true;
		return $result;
	}
	
	private static function cacheable_static_method($a, $b)
	{
		return static::cached_static_call(__METHOD__, func_get_args(), function ($a, $b)
		{
			return "from cacheable_static_method $a $b";
		});
	}
	
	private static function cacheable_static_void_method($a, $b)
	{
		static::cached_static_call(__METHOD__, func_get_args(), function ($a, $b)
		{
			// Do nothing.
		});
	}
}