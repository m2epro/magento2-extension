<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Client;

use Ess\M2ePro\Helper\AbstractHelper;

class Cache extends AbstractHelper
{
    const BACKEND_TYPE_APC       = 'apc';
    const BACKEND_TYPE_MEMCACHED = 'memcached';
    const BACKEND_TYPE_REDIS     = 'cm_cache_backend_redis';
    const BACKEND_TYPE_FILE      = 'file';
    const BACKEND_TYPE_SQLITE    = 'sqlite';
    const BACKEND_TYPE_DB        = 'database';

    //########################################

    public function isApcAvailable()
    {
        return extension_loaded('apc') && ini_get('apc.enabled');
    }

    public function isMemchachedAvailable()
    {
        return (extension_loaded('memcache') || extension_loaded('memcached')) &&
               (class_exists('Memcache', false) || class_exists('Memcached', false));
    }

    public function isRedisAvailable()
    {
        return extension_loaded('redis') && class_exists('Redis', false);
    }

    // ---------------------------------------

    public function isZendOpcacheAvailable()
    {
        return function_exists('opcache_get_status');
    }

    //########################################
}