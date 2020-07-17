<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Product\Variation;

/**
 * Class \Ess\M2ePro\Helper\Module\Product\Vocabulary
 */
class Vocabulary extends \Ess\M2ePro\Helper\AbstractHelper
{
    const VOCABULARY_AUTO_ACTION_NOT_SET = 0;
    const VOCABULARY_AUTO_ACTION_YES     = 1;
    const VOCABULARY_AUTO_ACTION_NO      = 2;

    const VALUE_TYPE_ATTRIBUTE = 'attribute';
    const VALUE_TYPE_OPTION    = 'option';

    const LOCAL_DATA_REGISTRY_KEY      = '/product/variation/vocabulary/local/';
    const SERVER_DATA_REGISTRY_KEY     = '/product/variation/vocabulary/server/';
    const SERVER_METADATA_REGISTRY_KEY = '/product/variation/vocabulary/server/metadata/';

    protected $modelFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function addAttribute($productAttribute, $channelAttribute)
    {
        if ((string)$productAttribute === (string)$channelAttribute) {
            return false;
        }

        $added = false;

        if (!$this->isAttributeExistsInLocalStorage($productAttribute, $channelAttribute)) {
            $this->addAttributeToLocalStorage($productAttribute, $channelAttribute);
            $added = true;
        }

        if (!$this->isAttributeExistsInServerStorage($productAttribute, $channelAttribute)) {
            $this->addAttributeToServerStorage($productAttribute, $channelAttribute);
            $added = true;
        }

        return $added;
    }

    // ---------------------------------------

    public function addAttributeToLocalStorage($productAttribute, $channelAttribute)
    {
        $vocabularyData = $this->getHelper('Module')->getRegistry()->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);
        $vocabularyData[$channelAttribute]['names'][] = $productAttribute;

        if (!isset($vocabularyData[$channelAttribute]['options'])) {
            $vocabularyData[$channelAttribute]['options'] = [];
        }

        $this->getHelper('Module')->getRegistry()->setValue(self::LOCAL_DATA_REGISTRY_KEY, $vocabularyData);

        $this->removeLocalDataCache();
    }

    public function addAttributeToServerStorage($productAttribute, $channelAttribute)
    {
        try {

            /** @var $dispatcherObject \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('M2ePro_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'product',
                'variationVocabulary',
                'add',
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
        $vocabularyData = $this->getHelper('Module')->getRegistry()->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);
        if (empty($vocabularyData[$channelAttribute]['names'])) {
            return;
        }

        if (($nameKey = array_search($productAttribute, $vocabularyData[$channelAttribute]['names'])) === false) {
            return;
        }

        unset($vocabularyData[$channelAttribute]['names'][$nameKey]);

        $vocabularyData[$channelAttribute]['names'] = array_values($vocabularyData[$channelAttribute]['names']);

        $this->getHelper('Module')->getRegistry()->setValue(
            self::LOCAL_DATA_REGISTRY_KEY,
            $vocabularyData
        );

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
        $configValue = $this->getConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return (bool)$configValue;
    }

    public function isAttributeAutoActionDisabled()
    {
        $configValue = $this->getConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return !(bool)$configValue;
    }

    // ---------------------------------------

    public function enableAttributeAutoAction()
    {
        return $this->setConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled', 1);
    }

    public function disableAttributeAutoAction()
    {
        return $this->setConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled', 0);
    }

    public function unsetAttributeAutoAction()
    {
        return $this->unsetConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled');
    }

    //########################################

    public function addOption($productOption, $channelOption, $channelAttribute)
    {
        if ($productOption == $channelOption) {
            return false;
        }

        $added = false;

        if (!$this->isOptionExistsInLocalStorage($productOption, $channelOption, $channelAttribute)) {
            $this->addOptionToLocalStorage($productOption, $channelOption, $channelAttribute);
            $added = true;
        }

        if (!$this->isOptionExistsInServerStorage($productOption, $channelOption, $channelAttribute)) {
            $this->addOptionToServerStorage($productOption, $channelOption, $channelAttribute);
            $added = true;
        }

        return $added;
    }

    // ---------------------------------------

    public function addOptionToLocalStorage($productOption, $channelOption, $channelAttribute)
    {
        $vocabularyData = $this->getHelper('Module')->getRegistry()->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);

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

        $this->getHelper('Module')->getRegistry()->setValue(self::LOCAL_DATA_REGISTRY_KEY, $vocabularyData);

        $this->removeLocalDataCache();
    }

    public function addOptionToServerStorage($productOption, $channelOption, $channelAttribute)
    {
        try {

            /** @var $dispatcherObject \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('M2ePro_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'product',
                'variationVocabulary',
                'add',
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
        $vocabularyData = $this->getHelper('Module')->getRegistry()->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);
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

        $this->getHelper('Module')->getRegistry()->setValue(self::LOCAL_DATA_REGISTRY_KEY, $vocabularyData);

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
        $configValue = $this->getConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return (bool)$configValue;
    }

    public function isOptionAutoActionDisabled()
    {
        $configValue = $this->getConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return !(bool)$configValue;
    }

    // ---------------------------------------

    public function enableOptionAutoAction()
    {
        return $this->setConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled', 1);
    }

    public function disableOptionAutoAction()
    {
        return $this->setConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled', 0);
    }

    public function unsetOptionAutoAction()
    {
        return $this->unsetConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled');
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

        $vocabularyData = $this->getHelper('Module')->getRegistry()->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);

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

        $vocabularyData = $this->getHelper('Module')->getRegistry()->getValueFromJson(self::SERVER_DATA_REGISTRY_KEY);

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

    public function getServerMetaData()
    {
        $cacheData = $this->getServerMetadataCache();
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $vocabularyData = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson(self::SERVER_METADATA_REGISTRY_KEY);

        $this->setServerMetadataCache($vocabularyData);

        return $vocabularyData;
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
        $this->getHelper('Module')->getRegistry()->setValue(self::LOCAL_DATA_REGISTRY_KEY, $data);

        $this->removeLocalDataCache();
    }

    public function setServerData(array $data)
    {
        $this->getHelper('Module')->getRegistry()->setValue(self::SERVER_DATA_REGISTRY_KEY, $data);

        $this->removeServerDataCache();
    }

    public function setServerMetadata(array $data)
    {
        $this->getHelper('Module')->getRegistry()->setValue(self::SERVER_METADATA_REGISTRY_KEY, $data);

        $this->removeServerMetadataCache();
    }

    //########################################

    protected function getLocalDataCache()
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        return $this->getHelper('Data_Cache_Permanent')->getValue($cacheKey);
    }

    protected function getServerDataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        return $this->getHelper('Data_Cache_Permanent')->getValue($cacheKey);
    }

    protected function getServerMetadataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_METADATA_REGISTRY_KEY;
        return $this->getHelper('Data_Cache_Permanent')->getValue($cacheKey);
    }

    // ---------------------------------------

    protected function setLocalDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->setValue($cacheKey, $data);
    }

    protected function setServerDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->setValue($cacheKey, $data);
    }

    protected function setServerMetadataCache(array $data)
    {
        $cacheKey = __CLASS__.self::SERVER_METADATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->setValue($cacheKey, $data);
    }

    // ---------------------------------------

    protected function removeLocalDataCache()
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->removeValue($cacheKey);
    }

    protected function removeServerDataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->removeValue($cacheKey);
    }

    protected function removeServerMetadataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_METADATA_REGISTRY_KEY;
        $this->getHelper('Data_Cache_Permanent')->removeValue($cacheKey);
    }

    //########################################

    protected function getConfigValue($group, $key)
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue($group, $key);
    }

    protected function setConfigValue($group, $key, $value)
    {
        return $this->getHelper('Module')->getConfig()->setGroupValue($group, $key, $value);
    }

    protected function unsetConfigValue($group, $key)
    {
        return $this->getHelper('Module')->getConfig()->deleteGroupValue($group, $key);
    }

    //########################################
}
