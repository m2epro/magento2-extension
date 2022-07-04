<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

class Runtime implements \Ess\M2ePro\Helper\Data\Cache\BaseInterface
{
    /** @var array */
    private $cacheStorage = [];

    /**
     * @inheritDoc
     */
    public function getValue($key)
    {
        return $this->cacheStorage[$key]['data'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setValue($key, $value, array $tags = [], $lifetime = null): void
    {
        $this->cacheStorage[$key] = [
            'data' => $value,
            'tags' => $tags,
        ];
    }

    // ----------------------------------------

    /**
     * @inheritDoc
     */
    public function removeValue($key): void
    {
        unset($this->cacheStorage[$key]);
    }

    /**
     * @inheritDoc
     */
    public function removeTagValues($tag): void
    {
        foreach ($this->cacheStorage as $key => $data) {
            if (!in_array($tag, $data['tags'])) {
                continue;
            }

            unset($this->cacheStorage[$key]);
        }
    }

    /**
     * @inheritDoc
     */
    public function removeAllValues(): void
    {
        $this->cacheStorage = [];
    }
}
