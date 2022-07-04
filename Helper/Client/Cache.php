<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Client;

class Cache
{
    public const BACKEND_TYPE_APC       = 'apc';
    public const BACKEND_TYPE_MEMCACHED = 'memcached';
    public const BACKEND_TYPE_REDIS     = 'cm_cache_backend_redis';
    public const BACKEND_TYPE_FILE      = 'file';
    public const BACKEND_TYPE_SQLITE    = 'sqlite';
    public const BACKEND_TYPE_DB        = 'database';

    /**
     * @return bool
     */
    public function isApcAvailable(): bool
    {
        return extension_loaded('apc') && ini_get('apc.enabled');
    }

    /**
     * @return bool
     */
    public function isMemchachedAvailable(): bool
    {
        return (extension_loaded('memcache') || extension_loaded('memcached')) &&
               (class_exists('Memcache', false) || class_exists('Memcached', false));
    }

    /**
     * @return bool
     */
    public function isRedisAvailable(): bool
    {
        return extension_loaded('redis') && class_exists('Redis', false);
    }

    /**
     * @return bool
     */
    public function isZendOpcacheAvailable(): bool
    {
        return function_exists('opcache_get_status');
    }
}
