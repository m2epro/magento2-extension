<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

/**
 * Class \Ess\M2ePro\Helper\Data\Cache\Runtime
 */
class Runtime extends \Ess\M2ePro\Helper\Data\Cache\AbstractHelper
{
    //########################################

    private $cacheStorage = [];

    //########################################

    public function getValue($key)
    {
        return isset($this->cacheStorage[$key]['data']) ? $this->cacheStorage[$key]['data'] : null;
    }

    public function setValue($key, $value, array $tags = [], $lifetime = null)
    {
        $this->cacheStorage[$key] = [
            'data' => $value,
            'tags' => $tags,
        ];

        return $value;
    }

    //########################################

    public function removeValue($key)
    {
        if (!isset($this->cacheStorage[$key])) {
            return false;
        }

        unset($this->cacheStorage[$key]);
        return true;
    }

    public function removeTagValues($tag)
    {
        $isDelete = false;
        foreach ($this->cacheStorage as $key => $data) {
            if (!in_array($tag, $data['tags'])) {
                continue;
            }

            unset($this->cacheStorage[$key]);
            $isDelete = true;
        }

        return $isDelete;
    }

    public function removeAllValues()
    {
        if (empty($this->cacheStorage)) {
            return false;
        }

        $this->cacheStorage = [];
        return true;
    }

    //########################################
}
