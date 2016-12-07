<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Config\Manager;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const SORT_NONE = 0;
    const SORT_KEY_ASC = 1;
    const SORT_KEY_DESC = 2;
    const SORT_VALUE_ASC = 3;
    const SORT_VALUE_DESC = 4;

    const GLOBAL_GROUP = '__global__';

    const CACHE_LIFETIME = 3600; // 1 hour

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Config\AbstractModel
     */

    protected function getModel()
    {
        return $this->activeRecordFactory->getObject($this->getModelName());
    }

    /**
     * @return string
     */
    protected abstract function getModelName();

    //########################################

    public function getGlobalValue($key)
    {
        return $this->getValue(self::GLOBAL_GROUP, $this->prepareKey($key));
    }

    public function setGlobalValue($key, $value)
    {
        return $this->setValue(self::GLOBAL_GROUP, $this->prepareKey($key), $value);
    }

    public function deleteGlobalValue($key)
    {
        return $this->deleteValue(self::GLOBAL_GROUP, $this->prepareKey($key));
    }

    // ---------------------------------------

    public function getAllGlobalValues($sort = self::SORT_NONE)
    {
        return $this->getAllValues(self::GLOBAL_GROUP, $sort);
    }

    public function deleteAllGlobalValues()
    {
        return $this->deleteAllValues(self::GLOBAL_GROUP);
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
        return $this->getAllValues($this->prepareGroup($group),$sort);
    }

    public function deleteAllGroupValues($group)
    {
        return $this->deleteAllValues($this->prepareGroup($group));
    }

    //########################################

    public function clear()
    {
        $resource = $this->getModel()->getResource();
        $resource->getConnection()->delete($resource->getMainTable());

        $this->removeCacheData();
    }

    //########################################

    private function getValue($group, $key)
    {
        if (empty($group) || empty($key)) {
            return NULL;
        }

        $cacheData = $this->getCacheData();

        if (!empty($cacheData)) {
            return isset($cacheData[$group][$key]) ? $cacheData[$group][$key] : NULL;
        }

        $dbData = $this->getCollection()->toArray();

        $cacheData = array();
        foreach ($dbData['items'] as $item) {

            $item['group'] = $this->prepareGroup($item['group']);
            $item['key']   = $this->prepareKey($item['key']);

            if (!isset($cacheData[$item['group']])) {
                $cacheData[$item['group']] = array();
            }

            $cacheData[$item['group']][$item['key']] = $item['value'];
        }

        $this->setCacheData($cacheData);

        return isset($cacheData[$group][$key]) ? $cacheData[$group][$key] : NULL;
    }

    private function setValue($group, $key, $value)
    {
        if (empty($key) || empty($group)) {
            return false;
        }

        $collection = $this->getCollection();

        if ($group == self::GLOBAL_GROUP) {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), array('null' => true));
        } else {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), $group);
        }

        $collection->addFieldToFilter(new \Zend_Db_Expr('`key`'), $key);
        $dbData = $collection->toArray();

        if (count($dbData['items']) > 0) {

            $existItem = reset($dbData['items']);

            $this->getModel()
                ->load($existItem['id'])
                ->addData(array('value'=>$value))
                ->save();
        } else {

            $this->getModel()
                ->setData(array('group' => $group,'key' => $key,'value' => $value))
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

        $collection = $this->getCollection();

        if ($group == self::GLOBAL_GROUP) {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), array('null' => true));
        } else {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), $group);
        }

        $collection->addFieldToFilter(new \Zend_Db_Expr('`key`'), $key);
        $dbData = $collection->toArray();

        if (empty($dbData['items'])) {
            return false;
        }

        $existItem = reset($dbData['items']);
        $this->getModel()->setId($existItem['id'])->delete();

        $this->removeCacheData();

        return true;
    }

    // ---------------------------------------

    private function getAllValues($group = NULL, $sort = self::SORT_NONE)
    {
        if (empty($group)) {
            return array();
        }

        $result = array();

        $collection = $this->getCollection();

        if ($group == self::GLOBAL_GROUP) {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), array('null' => true));
        } else {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), $group);
        }

        $dbData = $collection->toArray();

        foreach ($dbData['items'] as $item) {
            $result[$item['key']] = $item['value'];
        }

        $this->sortResult($result, $sort);

        return $result;
    }

    private function deleteAllValues($group = NULL)
    {
        if (empty($group)) {
            return false;
        }

        $collection = $this->getCollection();

        if ($group == self::GLOBAL_GROUP) {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), array('null' => true));
        } else {
            $collection->addFieldToFilter(new \Zend_Db_Expr('`group`'), array('like' => $group.'%'));
        }

        $dbData = $collection->toArray();

        foreach ($dbData['items'] as $item) {
            $this->getModel()->setId($item['id'])->delete();
        }

        $this->removeCacheData();

        return true;
    }

    //########################################

    private function getCacheData()
    {
        $key = $this->getModelName().'_data';
        return $this->getCacheModel()->getValue($key);
    }

    private function setCacheData(array $data)
    {
        $key = $this->getModelName().'_data';
        $this->getCacheModel()->setValue($key, $data, array(), self::CACHE_LIFETIME);
    }

    private function removeCacheData()
    {
        $key = $this->getModelName().'_data';
        $this->getCacheModel()->removeValue($key);
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Helper\Data\Cache\AbstractHelper
     */
    private function getCacheModel()
    {
        if ($this->getHelper('Module')->isDevelopmentEnvironment()) {
            return $this->getHelper('Data\Cache\Runtime');
        }

        return $this->getHelper('Data\Cache\Permanent');
    }

    //########################################

    private function prepareGroup($group)
    {
        if (is_null($group) || $group == self::GLOBAL_GROUP) {
            return self::GLOBAL_GROUP;
        }

        if (empty($group)) {
            return false;
        }

        return '/'.strtolower(trim($group,'/')).'/';
    }

    private function prepareKey($key)
    {
        return strtolower($key);
    }

    //----------------------------------------

    private function sortResult(&$array, $sort)
    {
        switch ($sort)
        {
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

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    private function getCollection()
    {
        return $this->getModel()->getCollection();
    }

    //########################################
}