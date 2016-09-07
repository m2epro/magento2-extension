<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Data\Cache;

class Runtime extends \Ess\M2ePro\Helper\Data\Cache\AbstractHelper
{
    //########################################

    private $cacheStorage = array();

    //########################################

    public function getValue($key)
    {
        return isset($this->cacheStorage[$key]['data']) ? $this->cacheStorage[$key]['data'] : null;
    }

    public function setValue($key, $value, array $tags = array(), $lifetime = null)
    {
        $this->cacheStorage[$key] = array(
            'data' => $value,
            'tags' => $tags,
        );

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

        $this->cacheStorage = array();
        return true;
    }

    //########################################
}