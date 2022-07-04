<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

class Permanent implements \Ess\M2ePro\Helper\Data\Cache\BaseInterface
{
    /** @var \Magento\Framework\App\Cache */
    protected $cache;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        $this->cache = $cache;
        $this->dataHelper = $dataHelper;
    }

    // ----------------------------------------

    /**
     * @inheritDoc
     */
    public function getValue($key)
    {
        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key;
        $value = $this->cache->load($cacheKey);

        return $value === false ? null : $this->dataHelper->unserialize($value);
    }

    /**
     * @inheritDoc
     */
    public function setValue($key, $value, array $tags = [], $lifeTime = null): void
    {
        if ($value === null) {
            throw new \Ess\M2ePro\Model\Exception('Can\'t store NULL value');
        }

        if (is_object($value)) {
            throw new \Ess\M2ePro\Model\Exception('Can\'t store a php object');
        }

        if ($lifeTime === null || (int)$lifeTime <= 0) {
            $lifeTime = 60 * 60 * 24;
        }

        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key;

        $preparedTags = [\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_main'];
        foreach ($tags as $tag) {
            $preparedTags[] = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $tag;
        }

        $this->cache->save(
            $this->dataHelper->serialize($value),
            $cacheKey,
            $preparedTags,
            (int)$lifeTime
        );
    }

    // ----------------------------------------

    /**
     * @inheritDoc
     */
    public function removeValue($key): void
    {
        $cacheKey = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $key;
        $this->cache->remove($cacheKey);
    }

    /**
     * @inheritDoc
     */
    public function removeTagValues($tag): void
    {
        $tags = [\Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER . '_' . $tag];
        $this->cache->clean($tags);
    }

    /**
     * @inheritDoc
     */
    public function removeAllValues(): void
    {
        $this->removeTagValues('main');
    }
}
