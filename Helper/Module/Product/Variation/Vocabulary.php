<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Product\Variation;

class Vocabulary
{
    public const VOCABULARY_AUTO_ACTION_NOT_SET = 0;
    public const VOCABULARY_AUTO_ACTION_YES     = 1;
    public const VOCABULARY_AUTO_ACTION_NO      = 2;

    private const VALUE_TYPE_ATTRIBUTE = 'attribute';
    private const VALUE_TYPE_OPTION    = 'option';

    private const LOCAL_DATA_REGISTRY_KEY      = '/product/variation/vocabulary/local/';
    private const SERVER_DATA_REGISTRY_KEY     = '/product/variation/vocabulary/server/';
    private const SERVER_METADATA_REGISTRY_KEY = '/product/variation/vocabulary/server/metadata/';

    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->modelFactory = $modelFactory;
        $this->moduleHelper = $moduleHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->config = $config;
        $this->registry = $registry;
    }

    /**
     * @param $productAttribute
     * @param $channelAttribute
     *
     * @return bool
     */
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

    /**
     * @param $productAttribute
     * @param $channelAttribute
     *
     * @return void
     */
    public function addAttributeToLocalStorage($productAttribute, $channelAttribute)
    {
        $vocabularyData = $this->registry->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);
        $vocabularyData[$channelAttribute]['names'][] = $productAttribute;

        if (!isset($vocabularyData[$channelAttribute]['options'])) {
            $vocabularyData[$channelAttribute]['options'] = [];
        }

        $this->registry->setValue(self::LOCAL_DATA_REGISTRY_KEY, $vocabularyData);

        $this->removeLocalDataCache();
    }

    /**
     * @param $productAttribute
     * @param $channelAttribute
     *
     * @return void
     */
    public function addAttributeToServerStorage($productAttribute, $channelAttribute)
    {
        try {
            /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $dispatcherObject */
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
            $this->exceptionHelper->process($exception);
        }
    }

    /**
     * @param $productAttribute
     * @param $channelAttribute
     *
     * @return void
     */
    public function removeAttributeFromLocalStorage($productAttribute, $channelAttribute)
    {
        $vocabularyData = $this->registry->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);
        if (empty($vocabularyData[$channelAttribute]['names'])) {
            return;
        }

        if (($nameKey = array_search($productAttribute, $vocabularyData[$channelAttribute]['names'])) === false) {
            return;
        }

        unset($vocabularyData[$channelAttribute]['names'][$nameKey]);

        $vocabularyData[$channelAttribute]['names'] = array_values($vocabularyData[$channelAttribute]['names']);

        $this->registry->setValue(
            self::LOCAL_DATA_REGISTRY_KEY,
            $vocabularyData
        );

        $this->removeLocalDataCache();
    }

    /**
     * @param $productAttribute
     * @param $channelAttribute
     *
     * @return bool
     */
    public function isAttributeExistsInLocalStorage($productAttribute, $channelAttribute): bool
    {
        return $this->isAttributeExists(
            $productAttribute,
            $channelAttribute,
            $this->getLocalData()
        );
    }

    /**
     * @param $productAttribute
     * @param $channelAttribute
     *
     * @return bool
     */
    public function isAttributeExistsInServerStorage($productAttribute, $channelAttribute): bool
    {
        return $this->isAttributeExists(
            $productAttribute,
            $channelAttribute,
            $this->getServerData()
        );
    }

    /**
     * @param $productAttribute
     * @param $channelAttribute
     * @param $vocabularyData
     *
     * @return bool
     */
    public function isAttributeExists($productAttribute, $channelAttribute, $vocabularyData): bool
    {
        if (empty($vocabularyData[$channelAttribute]['names'])) {
            return false;
        }

        if (!in_array($productAttribute, $vocabularyData[$channelAttribute]['names'])) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isAttributeAutoActionNotSet(): bool
    {
        return !$this->isAttributeAutoActionEnabled() && !$this->isAttributeAutoActionDisabled();
    }

    /**
     * @return bool
     */
    public function isAttributeAutoActionEnabled(): bool
    {
        $configValue = $this->getConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return (bool)$configValue;
    }

    /**
     * @return bool
     */
    public function isAttributeAutoActionDisabled(): bool
    {
        $configValue = $this->getConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return !(bool)$configValue;
    }

    /**
     * @return bool
     */
    public function enableAttributeAutoAction(): bool
    {
        return $this->setConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled', 1);
    }

    /**
     * @return bool
     */
    public function disableAttributeAutoAction(): bool
    {
        return $this->setConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled', 0);
    }

    /**
     * @return bool
     */
    public function unsetAttributeAutoAction(): bool
    {
        return $this->unsetConfigValue('/product/variation/vocabulary/attribute/auto_action/', 'enabled');
    }

    /**
     * @param $productOption
     * @param $channelOption
     * @param $channelAttribute
     *
     * @return bool
     */
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

    /**
     * @param $productOption
     * @param $channelOption
     * @param $channelAttribute
     *
     * @return void
     */
    public function addOptionToLocalStorage($productOption, $channelOption, $channelAttribute)
    {
        $vocabularyData = $this->registry->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);

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

        $this->registry->setValue(self::LOCAL_DATA_REGISTRY_KEY, $vocabularyData);

        $this->removeLocalDataCache();
    }

    /**
     * @param $productOption
     * @param $channelOption
     * @param $channelAttribute
     *
     * @return void
     */
    public function addOptionToServerStorage($productOption, $channelOption, $channelAttribute)
    {
        try {

            /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $dispatcherObject */
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
            $this->exceptionHelper->process($exception);
        }
    }

    /**
     * @param $productOption
     * @param $productOptionsGroup
     * @param $channelAttribute
     *
     * @return void
     */
    public function removeOptionFromLocalStorage($productOption, $productOptionsGroup, $channelAttribute): void
    {
        $vocabularyData = $this->registry->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);
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

        $this->registry->setValue(self::LOCAL_DATA_REGISTRY_KEY, $vocabularyData);

        $this->removeLocalDataCache();
    }

    /**
     * @param $productOption
     * @param $channelOption
     * @param $channelAttribute
     *
     * @return bool
     */
    public function isOptionExistsInLocalStorage($productOption, $channelOption, $channelAttribute): bool
    {
        return $this->isOptionExists(
            $productOption,
            $channelOption,
            $channelAttribute,
            $this->getLocalData()
        );
    }

    /**
     * @param $productOption
     * @param $channelOption
     * @param $channelAttribute
     *
     * @return bool
     */
    public function isOptionExistsInServerStorage($productOption, $channelOption, $channelAttribute): bool
    {
        return $this->isOptionExists(
            $productOption,
            $channelOption,
            $channelAttribute,
            $this->getServerData()
        );
    }

    /**
     * @param $productOption
     * @param $channelOption
     * @param $channelAttribute
     * @param $vocabularyData
     *
     * @return bool
     */
    public function isOptionExists($productOption, $channelOption, $channelAttribute, $vocabularyData): bool
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

    /**
     * @return bool
     */
    public function isOptionAutoActionNotSet(): bool
    {
        return !$this->isOptionAutoActionEnabled() && !$this->isOptionAutoActionDisabled();
    }

    /**
     * @return bool
     */
    public function isOptionAutoActionEnabled(): bool
    {
        $configValue = $this->getConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return (bool)$configValue;
    }

    /**
     * @return bool
     */
    public function isOptionAutoActionDisabled(): bool
    {
        $configValue = $this->getConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled');
        if ($configValue === null) {
            return false;
        }

        return !(bool)$configValue;
    }

    /**
     * @return bool
     */
    public function enableOptionAutoAction(): bool
    {
        return $this->setConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled', 1);
    }

    /**
     * @return bool
     */
    public function disableOptionAutoAction(): bool
    {
        return $this->setConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled', 0);
    }

    /**
     * @return bool
     */
    public function unsetOptionAutoAction(): bool
    {
        return $this->unsetConfigValue('/product/variation/vocabulary/option/auto_action/', 'enabled');
    }

    /**
     * @return array|null
     */
    public function getLocalData(): ?array
    {
        if (!$this->moduleHelper->isDevelopmentEnvironment()) {
            $cacheData = $this->getLocalDataCache();
            if (is_array($cacheData)) {
                return $cacheData;
            }
        }

        $vocabularyData = $this->registry->getValueFromJson(self::LOCAL_DATA_REGISTRY_KEY);

        if (!$this->moduleHelper->isDevelopmentEnvironment()) {
            $this->setLocalDataCache($vocabularyData);
        }

        return $vocabularyData;
    }

    /**
     * @param $attribute
     *
     * @return array
     */
    public function getLocalAttributeNames($attribute): array
    {
        return $this->getAttributeNames($attribute, $this->getLocalData());
    }

    /**
     * @param $attribute
     * @param $option
     *
     * @return array
     */
    public function getLocalOptionNames($attribute, $option)
    {
        return $this->getOptionNames($attribute, $option, $this->getLocalData());
    }

    /**
     * @return array|null
     */
    public function getServerData(): ?array
    {
        $cacheData = $this->getServerDataCache();
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $vocabularyData = $this->registry->getValueFromJson(self::SERVER_DATA_REGISTRY_KEY);

        $this->setServerDataCache($vocabularyData);

        return $vocabularyData;
    }

    /**
     * @param $attribute
     *
     * @return array
     */
    public function getServerAttributeNames($attribute): array
    {
        return $this->getAttributeNames($attribute, $this->getServerData());
    }

    /**
     * @param $attribute
     * @param $option
     *
     * @return array
     */
    public function getServerOptionNames($attribute, $option): array
    {
        return $this->getOptionNames($attribute, $option, $this->getServerData());
    }

    /**
     * @return array|null
     */
    public function getServerMetaData(): ?array
    {
        $cacheData = $this->getServerMetadataCache();
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $vocabularyData = $this->registry
            ->getValueFromJson(self::SERVER_METADATA_REGISTRY_KEY);

        $this->setServerMetadataCache($vocabularyData);

        return $vocabularyData;
    }

    /**
     * @param $attribute
     * @param $vocabularyData
     *
     * @return array
     */
    public function getAttributeNames($attribute, $vocabularyData): array
    {
        if (empty($vocabularyData[$attribute]['names'])) {
            return [];
        }

        return $vocabularyData[$attribute]['names'];
    }

    /**
     * @param $attribute
     * @param $option
     * @param $vocabularyData
     *
     * @return array
     */
    public function getOptionNames($attribute, $option, $vocabularyData): array
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

    /**
     * @param array $data
     *
     * @return void
     */
    public function setLocalData(array $data)
    {
        $this->registry->setValue(self::LOCAL_DATA_REGISTRY_KEY, $data);

        $this->removeLocalDataCache();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function setServerData(array $data)
    {
        $this->registry->setValue(self::SERVER_DATA_REGISTRY_KEY, $data);

        $this->removeServerDataCache();
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function setServerMetadata(array $data): void
    {
        $this->registry->setValue(self::SERVER_METADATA_REGISTRY_KEY, $data);

        $this->removeServerMetadataCache();
    }

    /**
     * @return mixed
     */
    private function getLocalDataCache()
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        return $this->permanentCacheHelper->getValue($cacheKey);
    }

    /**
     * @return mixed
     */
    private function getServerDataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        return $this->permanentCacheHelper->getValue($cacheKey);
    }

    /**
     * @return array|string|null
     */
    private function getServerMetadataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_METADATA_REGISTRY_KEY;
        return $this->permanentCacheHelper->getValue($cacheKey);
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function setLocalDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        $this->permanentCacheHelper->setValue($cacheKey, $data);
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function setServerDataCache(array $data)
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        $this->permanentCacheHelper->setValue($cacheKey, $data);
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function setServerMetadataCache(array $data)
    {
        $cacheKey = __CLASS__.self::SERVER_METADATA_REGISTRY_KEY;
        $this->permanentCacheHelper->setValue($cacheKey, $data);
    }


    /**
     * @return void
     */
    private function removeLocalDataCache()
    {
        $cacheKey = __CLASS__.self::LOCAL_DATA_REGISTRY_KEY;
        $this->permanentCacheHelper->removeValue($cacheKey);
    }

    /**
     * @return void
     */
    private function removeServerDataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_DATA_REGISTRY_KEY;
        $this->permanentCacheHelper->removeValue($cacheKey);
    }

    /**
     * @return void
     */
    private function removeServerMetadataCache()
    {
        $cacheKey = __CLASS__.self::SERVER_METADATA_REGISTRY_KEY;
        $this->permanentCacheHelper->removeValue($cacheKey);
    }

    /**
     * @param $group
     * @param $key
     *
     * @return mixed|null
     */
    private function getConfigValue($group, $key)
    {
        return $this->config->getGroupValue($group, $key);
    }

    /**
     * @param $group
     * @param $key
     * @param $value
     *
     * @return bool
     */
    private function setConfigValue($group, $key, $value)
    {
        return $this->config->setGroupValue($group, $key, $value);
    }

    /**
     * @param $group
     * @param $key
     *
     * @return bool
     */
    private function unsetConfigValue($group, $key)
    {
        return $this->config->deleteGroupValue($group, $key);
    }
}
