<?php
/**
 * A WordPress cache adapter.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

use Psr\SimpleCache\CacheInterface;

/**
 * Cache class.
 */
class Cache implements CacheInterface
{
	/**
	 * Fetches a value from the cache.
	 *
	 * @since 0.0.1
	 * @param string $key
	 * @param mixed  $standard
	 *
	 * @return mixed | false
	 */
	public function get( $key, $standard = null )
	{
		if (get_transient( $key )) {
			return get_transient( $key );
		} else {
			return '';
		}
	}

	/**
	 * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
	 *
	 * @since 0.0.1
	 * @param string                 $key
	 * @param mixed                  $value
	 * @param null|int|\DateInterval $ttl
	 */
	public function set( $key, $value, $ttl = null ): bool
	{
		return set_transient( $key, $value, $ttl );
	}

	/**
	 * Delete an item from the cache by its unique key.
	 *
	 * @since 0.0.1
	 * @param string $key
	 */
	public function delete( $key ): bool
	{
		return delete_transient( $key );
	}

	/**
	 * Clear the entire cache's keys.
	 *
	 * @since 0.0.1
	 */
	public function clear(): bool
	{
		// non-existent
		return false;
	}

	/**
	 * Obtains multiple cache items by their unique keys.
	 *
	 * @since 0.0.1
	 * @param iterable $keys
	 * @param mixed    $standard
	 */
	public function getMultiple( $keys, $standard = null ): iterable
	{
		$values = array();

		foreach ($keys as $key) {
			$values[ $key ] = $this->get( $key, $standard );
		}

		return $values;
	}

	/**
	 * Persists a set of key => value pairs in the cache, with an optional TTL.
	 *
	 * @since 0.0.1
	 * @param iterable               $values
	 * @param null|int|\DateInterval $ttl
	 */
	public function setMultiple( $values, $ttl = null ): bool
	{
		foreach ($values as $key => $value) {
			$this->set( $key, $value, $ttl );
		}

		return true;
	}

	/**
	 * Deletes multiple cache items in a single operation.
	 *
	 * @since 0.0.1
	 * @param iterable $keys
	 */
	public function deleteMultiple( $keys ): bool
	{
		foreach ($keys as $key) {
			$this->delete( $key );
		}

		return true;
	}

	/**
	 * Determines whether an item is present in the cache.
	 *
	 * @since 0.0.1
	 * @param string $key
	 */
	public function has( $key ): bool
	{
		return (bool) $this->get( $key );
	}
}
