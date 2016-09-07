<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

use Ess\M2ePro\Model\Exception;

class Permanent extends \Ess\M2ePro\Helper\Data\Cache\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Cache
     */
    protected $cache;

    //########################################

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->cache = $cache;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getValue($key)
    {
        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        $value = $this->cache->load($cacheKey);
        return $value === false ? NULL : unserialize($value);
    }

    public function setValue($key, $value, array $tags = array(), $lifeTime = NULL)
    {
        if ($value === NULL) {
            throw new Exception('Can\'t store NULL value');
        }

        if (is_null($lifeTime) || (int)$lifeTime <= 0) {
            $lifeTime = 60*60*24*365*5;
        }

        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;

        $preparedTags = array(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_main');
        foreach ($tags as $tag) {
            $preparedTags[] = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$tag;
        }

        $this->cache->save(serialize($value), $cacheKey, $preparedTags, (int)$lifeTime);
    }

    //########################################

    public function removeValue($key)
    {
        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        $this->cache->remove($cacheKey);
    }

    public function removeTagValues($tag)
    {
        $tags = array(\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$tag);
        $this->cache->clean($tags);
    }

    public function removeAllValues()
    {
        $this->removeTagValues('main');
    }

    //########################################
}