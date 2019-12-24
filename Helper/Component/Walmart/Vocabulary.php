<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

/**
 * Class \Ess\M2ePro\Helper\Component\Walmart\Vocabulary
 */
class Vocabulary extends \Ess\M2ePro\Helper\AbstractHelper
{
    const VOCABULARY_AUTO_ACTION_NOT_SET = 0;
    const VOCABULARY_AUTO_ACTION_YES = 1;
    const VOCABULARY_AUTO_ACTION_NO = 2;

    const VALUE_TYPE_ATTRIBUTE = 'attribute';
    const VALUE_TYPE_OPTION    = 'option';

    const LOCAL_DATA_REGISTRY_KEY  = 'walmart_vocabulary_local';
    const SERVER_DATA_REGISTRY_KEY = 'walmart_vocabulary_server';

    protected $modelFactory;
    protected $activeRecordFactory;
    protected $walmartParentFactory;
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartParentFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->walmartParentFactory = $walmartParentFactory;
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function addAttribute($productAttribute, $channelAttribute)
    {
        if ($productAttribute == $channelAttribute) {
            return;
        }

        $needRunProcessors = false;

        if (!$this->isAttributeExistsInLocalStorage($productAttribute, $channelAttribute)) {
            $this->addAttributeToLocalStorage($productAttribute, $channelAttribute);
            $needRunProcessors = true;
        }

        if (!$this->isAttributeExistsInServerStorage($productAttribute, $channelAttribute)) {
            $this->addAttributeToServerStorage($productAttribute, $channelAttribute);
            $needRunProcessors = true;
        }

        if (!$needRunProcessors) {
            return;
        }

        $affectedParentListingsProducts = $this->getParentListingsProductsAffectedToAttribute($channelAttribute);
        if (empty($affectedParentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    // ---------------------------------------

    public function addAttributeToLocalStorage($productAttribute, $channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::LOCAL_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $vocabularyData = $registry->getSettings('value');
        $vocabularyData[$channelAttribute]['names'][] = $productAttribute;

        if (!isset($vocabularyData[$channelAttribute]['options'])) {
            $vocabularyData[$channelAttribute]['options'] = [];
        }

        $registry->setData('key', self::LOCAL_DATA_REGISTRY_KEY);
        $registry->setSettings('value', $vocabularyData)->save();

        $this->removeLocalDataCache();
    }

    public function addAttributeToServerStorage($productAttribute, $channelAttribute)
    {
        try {

            /** @var $dispatcherObject \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'product',
                'add',
                'vocabulary',
                [
                    'type'     => self::VALUE_TYPE_ATTRIBUTE,
                    'original' => $channelAttribute,
                    'value'    => $productAttribute
                ]
            );

            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }
    }

    // ---------------------------------------

    public function removeAttributeFromLocalStorage($productAttribute, $channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::LOCAL_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $vocabularyData = $registry->getSettings('value');
        if (empty($vocabularyData[$channelAttribute]['names'])) {
            return;
        }

        if (($nameKey = array_search($productAttribute, $vocabularyData[$channelAttribute]['names'])) === false) {
            return;
        }

        unset($vocabularyData[$channelAttribute]['names'][$nameKey]);

        $vocabularyData[$channelAttribute]['names'] = array_values($vocabularyData[$channelAttribute]['names']);

        $registry->setData('key', self::LOCAL_DATA_REGISTRY_KEY);
        $registry->setSettings('value', $vocabularyData)->save();

        $this->removeLocalDataCache();
    }

    // ---------------------------------------

    public function isAttributeExistsInLocalStorage($productAttribute, $channelAttribute)
    {
        return $this->isAttributeExists(
            $productAttribute,
            $channelAttribute,
            $this->getLocalData()
        );
    }

    public function isAttributeExistsInServerStorage($productAttribute, $channelAttribute)
    {
        return $this->isAttributeExists(
            $productAttribute,
            $channelAttribute,
            $this->getServerData()
        );
    }

    // ---------------------------------------

    public function isAttributeExists($productAttribute, $channelAttribute, $vocabularyData)
    {
        if (empty($vocabularyData[$channelAttribute]['names'])) {
            return false;
        }

        if (!in_array($productAttribute, $vocabularyData[$channelAttribute]['names'])) {
            return false;
        }

        return true;
    }

    // ---------------------------------------

    public function isAttributeAutoActionNotSet()
    {
        return !$this->isAttributeAutoActionEnabled() && !$this->isAttributeAutoActionDisabled();
    }

    public function isAttributeAutoActionEnabled()
    {
        $configValue = $this->getConfigValue('/walmart/vocabulary/attribute/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return (bool)$configValue;
    }

    public function isAttributeAutoActionDisabled()
    {
        $configValue = $this->getConfigValue('/walmart/vocabulary/attribute/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return !(bool)$configValue;
    }

    // ---------------------------------------

    public function enableAttributeAutoAction()
    {
        return $this->setConfigValue('/walmart/vocabulary/attribute/auto_action/', 'enabled', 1);
    }

    public function disableAttributeAutoAction()
    {
        return $this->setConfigValue('/walmart/vocabulary/attribute/auto_action/', 'enabled', 0);
    }

    public function unsetAttributeAutoAction()
    {
        return $this->unsetConfigValue('/walmart/vocabulary/attribute/auto_action/', 'enabled');
    }

    //########################################

    public function addOption($productOption, $channelOption, $channelAttribute)
    {
        if ($productOption == $channelOption) {
            return;
        }

        $needRunProcessors = false;

        if (!$this->isOptionExistsInLocalStorage($productOption, $channelOption, $channelAttribute)) {
            $this->addOptionToLocalStorage($productOption, $channelOption, $channelAttribute);
            $needRunProcessors = true;
        }

        if (!$this->isOptionExistsInServerStorage($productOption, $channelOption, $channelAttribute)) {
            $this->addOptionToServerStorage($productOption, $channelOption, $channelAttribute);
            $needRunProcessors = true;
        }

        if (!$needRunProcessors) {
            return;
        }

        $affectedParentListingsProducts = $this->getParentListingsProductsAffectedToOption(
            $channelAttribute,
            $channelOption
        );

        if (empty($affectedParentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    // ---------------------------------------

    public function addOptionToLocalStorage($productOption, $channelOption, $channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::LOCAL_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $vocabularyData = $registry->getSettings('value');

        if (!isset($vocabularyData[$channelAttribute]['names'])) {
            $vocabularyData[$channelAttribute]['names'] = [];
        }

        if (!isset($vocabularyData[$channelAttribute]['options'])) {
            $vocabularyData[$channelAttribute]['options'] = [];
        }

        $isAdded = false;
        foreach ($vocabularyData[$channelAttribute]['options'] as &$options) {
            if (!in_array($channelOption, $options)) {
                continue;
            }

            $options[] = $productOption;
            $isAdded = true;
        }

        if (!$isAdded) {
            $vocabularyData[$channelAttribute]['options'][] = [
                $channelOption,
                $productOption,
            ];
        }

        $registry->setData('key', self::LOCAL_DATA_REGISTRY_KEY);
        $registry->setSettings('value', $vocabularyData)->save();

        $this->removeLocalDataCache();
    }

    public function addOptionToServerStorage($productOption, $channelOption, $channelAttribute)
    {
        try {

            /** @var $dispatcherObject \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'product',
                'add',
                'vocabulary',
                [
                    'type'      => self::VALUE_TYPE_OPTION,
                    'attribute' => $channelAttribute,
                    'original'  => $channelOption,
                    'value'     => $productOption
                ]
            );

            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }
    }

    // ---------------------------------------

    public function removeOptionFromLocalStorage($productOption, $productOptionsGroup, $channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::LOCAL_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $vocabularyData = $registry->getSettings('value');
        if (empty($vocabularyData[$channelAttribute]['options'])) {
            return;
        }

        foreach ($vocabularyData[$channelAttribute]['options'] as $optionsGroupKey => &$options) {
            $comparedOptions = array_diff($productOptionsGroup, $options);
            $nameKey = array_search($productOption, $options);

            if (empty($comparedOptions) && $nameKey !== false) {
                unset($options[$nameKey]);

                $vocabularyData[$channelAttribute]['options'][$optionsGroupKey] = array_values($options);

                if (count($options) == 1) {
                    unset($vocabularyData[$channelAttribute]['options'][$optionsGroupKey]);
                }
                break;
            }
        }

        $registry->setData('key', self::LOCAL_DATA_REGISTRY_KEY);
        $registry->setSettings('value', $vocabularyData)->save();

        $this->removeLocalDataCache();
    }

    // ---------------------------------------

    public function isOptionExistsInLocalStorage($productOption, $channelOption, $channelAttribute)
    {
        return $this->isOptionExists(
            $productOption,
            $channelOption,
            $channelAttribute,
            $this->getLocalData()
        );
    }

    public function isOptionExistsInServerStorage($productOption, $channelOption, $channelAttribute)
    {
        return $this->isOptionExists(
            $productOption,
            $channelOption,
            $channelAttribute,
            $this->getServerData()
        );
    }

    // ---------------------------------------

    public function isOptionExists($productOption, $channelOption, $channelAttribute, $vocabularyData)
    {
        if (empty($vocabularyData[$channelAttribute])) {
            return false;
        }

        $attributeData = $vocabularyData[$channelAttribute];
        if (empty($attributeData['options']) || !is_array($attributeData['options'])) {
            return false;
        }

        foreach ($attributeData['options'] as $options) {
            if (in_array($channelOption, $options) && in_array($productOption, $options)) {
                return true;
            }
        }

        return false;
    }

    // ---------------------------------------

    public function isOptionAutoActionNotSet()
    {
        return !$this->isOptionAutoActionEnabled() && !$this->isOptionAutoActionDisabled();
    }

    public function isOptionAutoActionEnabled()
    {
        $configValue = $this->getConfigValue('/walmart/vocabulary/option/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return (bool)$configValue;
    }

    public function isOptionAutoActionDisabled()
    {
        $configValue = $this->getConfigValue('/walmart/vocabulary/option/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return !(bool)$configValue;
    }

    // ---------------------------------------

    public function enableOptionAutoAction()
    {
        return $this->setConfigValue('/walmart/vocabulary/option/auto_action/', 'enabled', 1);
    }

    public function disableOptionAutoAction()
    {
        return $this->setConfigValue('/walmart/vocabulary/option/auto_action/', 'enabled', 0);
    }

    public function unsetOptionAutoAction()
    {
        return $this->unsetConfigValue('/walmart/vocabulary/option/auto_action/', 'enabled');
    }

    //########################################

    public function getLocalData()
    {
        if (!$this->getHelper('Module')->isDevelopmentEnvironment()) {
            $cacheData = $this->getLocalDataCache();
            if (is_array($cacheData)) {
                return $cacheData;
            }
        }

        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::LOCAL_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $vocabularyData = $registry->getSettings('value');

        if (!$this->getHelper('Module')->isDevelopmentEnvironment()) {
            $this->setLocalDataCache($vocabularyData);
        }

        return $vocabularyData;
    }

    public function getLocalAttributeNames($attribute)
    {
        return $this->getAttributeNames($attribute, $this->getLocalData());
    }

    public function getLocalOptionNames($attribute, $option)
    {
        return $this->getOptionNames($attribute, $option, $this->getLocalData());
    }

    // ---------------------------------------

    public function getServerData()
    {
        $cacheData = $this->getServerDataCache();
        if (is_array($cacheData)) {
            return $cacheData;
        }

        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::SERVER_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }
        $vocabularyData = $registry->getSettings('value');

        $this->setServerDataCache($vocabularyData);

        return $vocabularyData;
    }

    public function getServerAttributeNames($attribute)
    {
        return $this->getAttributeNames($attribute, $this->getServerData());
    }

    public function getServerOptionNames($attribute, $option)
    {
        return $this->getOptionNames($attribute, $option, $this->getServerData());
    }

    // ---------------------------------------

    public function getAttributeNames($attribute, $vocabularyData)
    {
        if (empty($vocabularyData[$attribute]['names'])) {
            return [];
        }

        return $vocabularyData[$attribute]['names'];
    }

    public function getOptionNames($attribute, $option, $vocabularyData)
    {
        if (empty($vocabularyData[$attribute]['options'])) {
            return [];
        }

        $resultNames = [];

        foreach ($vocabularyData[$attribute]['options'] as $optionNames) {
            $preparedOption      = strtolower($option);
            $preparedOptionNames = array_map('strtolower', $optionNames);

            if (!in_array($preparedOption, $preparedOptionNames)) {
                continue;
            }

            $resultNames = array_merge($resultNames, $optionNames);
        }

        return $resultNames;
    }

    //########################################

    public function setLocalData(array $data)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::LOCAL_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $registry->setData('key', self::LOCAL_DATA_REGISTRY_KEY);
        $registry->setSettings('value', $data)->save();

        $this->removeLocalDataCache();
    }

    public function setServerData(array $data)
    {
        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry',
            self::SERVER_DATA_REGISTRY_KEY,
            'key',
            false
        );

        if ($registry === null) {
            $registry = $this->activeRecordFactory->getObject('Registry');
        }

        $registry->setData('key', self::SERVER_DATA_REGISTRY_KEY);
        $registry->setSettings('value', $data)->save();

        $this->removeServerDataCache();
    }

    //########################################

    public function getParentListingsProductsAffectedToAttribute($channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_variation_parent', 1);

        $collection->addFieldToFilter(
            'additional_data',
            ['regexp' => '"variation_channel_attributes":.*"'.$channelAttribute.'"']
        );

        return $collection->getItems();
    }

    public function getParentListingsProductsAffectedToOption($channelAttribute, $channelOption)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('variation_parent_id', ['notnull' => true]);

        $collection->addFieldToFilter('additional_data', [
            'regexp'=> '"variation_channel_options":.*"'.$channelAttribute.'":"'.$channelOption.'"}']);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns([
            'second_table.variation_parent_id'
        ]);

        $parentIds = $collection->getColumnValues('variation_parent_id');
        if (empty($parentIds)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_variation_parent', 1);
        $collection->addFieldToFilter('id', ['in' => $parentIds]);

        return $collection->getItems();
    }

    //########################################

    private function getLocalDataCache()
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        return $this->getHelper('Data_Cache_Permanent')->getValue($cacheKey);
    }

    private function getServerDataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        return $this->getHelper('Data_Cache_Permanent')->getValue($cacheKey);
    }

    // ---------------------------------------

    private function setLocalDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->setValue($cacheKey, $data);
    }

    private function setServerDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->setValue($cacheKey, $data);
    }

    // ---------------------------------------

    private function removeLocalDataCache()
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->removeValue($cacheKey);
    }

    private function removeServerDataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->removeValue($cacheKey);
    }

    //########################################

    private function getConfigValue($group, $key)
    {
        return $this->moduleConfig->getGroupValue($group, $key);
    }

    private function setConfigValue($group, $key, $value)
    {
        return $this->moduleConfig->setGroupValue($group, $key, $value);
    }

    private function unsetConfigValue($group, $key)
    {
        return $this->moduleConfig->deleteGroupValue($group, $key);
    }

    //########################################
}
