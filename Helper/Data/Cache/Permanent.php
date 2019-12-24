<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

use Ess\M2ePro\Model\Exception;

/**
 * Class \Ess\M2ePro\Helper\Data\Cache\Permanent
 */
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
    ) {
        $this->cache = $cache;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getValue($key)
    {
        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        $value = $this->cache->load($cacheKey);
        return $value === false ? null : $this->getHelper('Data')->unserialize($value);
    }

    public function setValue($key, $value, array $tags = [], $lifeTime = null)
    {
        if ($value === null) {
            throw new Exception('Can\'t store NULL value');
        }

        if (is_object($value)) {
            throw new Exception('Can\'t store a php object');
        }

        if ($lifeTime === null || (int)$lifeTime <= 0) {
            $lifeTime = 60*60*24;
        }

        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;

        $preparedTags = [\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_main'];
        foreach ($tags as $tag) {
            $preparedTags[] = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$tag;
        }

        $this->cache->save(
            \Zend\Serializer\Serializer::getDefaultAdapter()->serialize($value),
            $cacheKey,
            $preparedTags,
            (int)$lifeTime
        );
    }

    //########################################

    public function removeValue($key)
    {
        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$key;
        $this->cache->remove($cacheKey);
    }

    public function removeTagValues($tag)
    {
        $tags = [\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER.'_'.$tag];
        $this->cache->clean($tags);
    }

    public function removeAllValues()
    {
        $this->removeTagValues('main');
    }

    //########################################
}
