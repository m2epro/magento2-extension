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

//    public function getBackend()
//    {
//        return strtolower((string)Mage::getConfig()->getNode('global/cache/backend'));
//    }
//
//    public function getFastBackend()
//    {
//        return strtolower((string)Mage::getConfig()->getNode('global/cache/fast_backend'));
//    }
//
//    public function getSlowBackend()
//    {
//        return strtolower((string)Mage::getConfig()->getNode('global/cache/slow_backend'));
//    }

    //########################################

//    public function isApcEnabled()
//    {
//        return $this->getBackend() == self::BACKEND_TYPE_APC ||
//               $this->getFastBackend() == self::BACKEND_TYPE_APC;
//    }
//
//    public function isMemchachedEnabled()
//    {
//        return $this->getBackend() == self::BACKEND_TYPE_MEMCACHED ||
//               $this->getFastBackend() == self::BACKEND_TYPE_MEMCACHED;
//    }
//
//    public function isRedisEnabled()
//    {
//        return $this->getBackend() == self::BACKEND_TYPE_REDIS ||
//               $this->getFastBackend() == self::BACKEND_TYPE_REDIS;
//    }
//
//    public function isTwoLevelsCacheEnabled()
//    {
//        return Mage::app()->getCache()->getBackend() instanceof Zend_Cache_Backend_TwoLevels;
//    }
//
//    public function isAutoRefreshCacheEnabled()
//    {
//       return (bool)Mage::getConfig()->getNode('global/cache/auto_refresh_fast_cache');
//    }
//
//    // ---------------------------------------
//
//    public function isWrongCacheConfiguration()
//    {
//        if (!$this->isTwoLevelsCacheEnabled()) {
//            return false;
//        }
//
//        if ($this->isAutoRefreshCacheEnabled()) {
//            return true;
//        }
//
//        if ($this->getSlowBackend() != '' &&
//            $this->getSlowBackend() != self::BACKEND_TYPE_FILE &&
//            $this->getSlowBackend() != self::BACKEND_TYPE_SQLITE &&
//            $this->getSlowBackend() != self::BACKEND_TYPE_DB) {
//
//            return true;
//        }
//
//        if (($this->getSlowBackend() == '' || $this->getSlowBackend() == self::BACKEND_TYPE_FILE) &&
//            Mage::getConfig()->getNode('global/cache/slow_backend_options')) {
//
//            return true;
//        }
//
//        return false;
//    }

    //########################################
}