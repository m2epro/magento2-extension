<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Config;

/**
 * Class \Ess\M2ePro\Model\Config\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    const SORT_NONE = 0;
    const SORT_KEY_ASC = 1;
    const SORT_KEY_DESC = 2;
    const SORT_VALUE_ASC = 3;
    const SORT_VALUE_DESC = 4;

    const CACHE_LIFETIME = 3600; // 1 hour

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function getGroupValue($group, $key)
    {
        return $this->getValue($this->prepareGroup($group), $this->prepareKey($key));
    }

    public function setGroupValue($group, $key, $value)
    {
        return $this->setValue($this->prepareGroup($group), $this->prepareKey($key), $value);
    }

    public function deleteGroupValue($group, $key)
    {
        return $this->deleteValue($this->prepareGroup($group), $this->prepareKey($key));
    }

    // ---------------------------------------

    public function getAllGroupValues($group, $sort = self::SORT_NONE)
    {
        return $this->getAllValues($this->prepareGroup($group), $sort);
    }

    public function deleteAllGroupValues($group)
    {
        return $this->deleteAllValues($this->prepareGroup($group));
    }

    //########################################

    public function clear()
    {
        $resource = $this->activeRecordFactory->getObject('Config')->getResource();
        $resource->getConnection()->delete($resource->getMainTable());

        $this->removeCacheData();
    }

    //########################################

    private function getValue($group, $key)
    {
        if (empty($group) || empty($key)) {
            return null;
        }

        $cacheData = $this->getCacheData();

        if (!empty($cacheData)) {
            return isset($cacheData[$group][$key]) ? $cacheData[$group][$key] : null;
        }

        $dbData = $this->activeRecordFactory->getObject('Config')->getCollection()->toArray();

        $cacheData = [];
        foreach ($dbData['items'] as $item) {
            $item['group'] = $this->prepareGroup($item['group']);
            $item['key']   = $this->prepareKey($item['key']);

            if (!isset($cacheData[$item['group']])) {
                $cacheData[$item['group']] = [];
            }

            $cacheData[$item['group']][$item['key']] = $item['value'];
        }

        $this->setCacheData($cacheData);

        return isset($cacheData[$group][$key]) ? $cacheData[$group][$key] : null;
    }

    private function setValue($group, $key, $value)
    {
        if (empty($key) || empty($group)) {
            return false;
        }

        $collection = $this->activeRecordFactory->getObject('Config')->getCollection();
        $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), $group);
        $collection->addFieldToFilter(new \Zend_Db_Expr('`key`'), $key);

        $dbData = $collection->toArray();

        if (!empty($dbData['items'])) {
            $existItem = reset($dbData['items']);

            $this->activeRecordFactory->getObject('Config')
                ->load($existItem['id'])
                ->addData(['value'=>$value])
                ->save();
        } else {
            $this->activeRecordFactory->getObject('Config')
                ->setData(['group' => $group, 'key' => $key, 'value' => $value])
                ->save();
        }

        $this->removeCacheData();

        return true;
    }

    private function deleteValue($group, $key)
    {
        if (empty($key) || empty($group)) {
            return false;
        }

        $collection = $this->activeRecordFactory->getObject('Config')->getCollection();
        $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), $group);
        $collection->addFieldToFilter(new \Zend_Db_Expr('`key`'), $key);

        $dbData = $collection->toArray();

        if (empty($dbData['items'])) {
            return false;
        }

        $existItem = reset($dbData['items']);
        $this->activeRecordFactory->getObject('Config')->setId($existItem['id'])->delete();

        $this->removeCacheData();

        return true;
    }

    // ---------------------------------------

    private function getAllValues($group, $sort = self::SORT_NONE)
    {
        if (empty($group)) {
            return [];
        }

        $result = [];

        $collection = $this->activeRecordFactory->getObject('Config')->getCollection();
        $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), $group);

        $dbData = $collection->toArray();

        foreach ($dbData['items'] as $item) {
            $result[$item['key']] = $item['value'];
        }

        $this->sortResult($result, $sort);

        return $result;
    }

    private function deleteAllValues($group)
    {
        if (empty($group)) {
            return false;
        }

        $collection = $this->activeRecordFactory->getObject('Config')->getCollection();
        $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), ['like' => $group]);

        $dbData = $collection->toArray();

        foreach ($dbData['items'] as $item) {
            $this->activeRecordFactory->getObject('Config')->setId($item['id'])->delete();
        }

        $this->removeCacheData();

        return true;
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAllConfigData()
    {
        $collection = $this->activeRecordFactory->getObject('Config')->getCollection()->toArray();
        return $collection['items'];
    }

    //########################################

    private function getCacheData()
    {
        return $this->getCacheModel()->getValue('m2ePro_config_data');
    }

    private function setCacheData(array $data)
    {
        $this->getCacheModel()->setValue('m2ePro_config_data', $data, [], self::CACHE_LIFETIME);
    }

    private function removeCacheData()
    {
        $this->getCacheModel()->removeValue('m2ePro_config_data');
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Helper\Data\Cache\AbstractHelper
     */
    private function getCacheModel()
    {
        return $this->getHelper('Data_Cache_Permanent');
    }

    //########################################

    private function prepareGroup($group)
    {
        if (empty($group)) {
            return false;
        }

        return '/'.strtolower(trim($group, '/')).'/';
    }

    private function prepareKey($key)
    {
        return strtolower($key);
    }

    //----------------------------------------

    private function sortResult(&$array, $sort)
    {
        switch ($sort) {
            case self::SORT_KEY_ASC:
                ksort($array);
                break;

            case self::SORT_KEY_DESC:
                krsort($array);
                break;

            case self::SORT_VALUE_ASC:
                asort($array);
                break;

            case self::SORT_VALUE_DESC:
                arsort($array);
                break;
        }
    }

    //########################################
}
