<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method getSettings($fieldName)
 * @method getSetting($fieldName, $settingNamePath, $defaultValue = null)
 * @method setSettings($fieldName, array $settings = [])
 * @method setSetting($fieldName, $settingNamePath, $settingValue)
 */
class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const FIELD_NOT_CONFIGURED = 0;
    public const FIELD_CUSTOM_VALUE = 1;
    public const FIELD_CUSTOM_ATTRIBUTE = 2;

    public const VIEW_MODE_ALL_ATTRIBUTES = 0;
    public const VIEW_MODE_REQUIRED_ATTRIBUTES = 1;

    public const GENERAL_PRODUCT_TYPE_NICK = 'PRODUCT';

    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;

    /** @var ?\Ess\M2ePro\Model\Amazon\Dictionary\ProductType */
    private $dictionary = null;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->productTypeHelper = $productTypeHelper;
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType::class);
    }

    /**
     * @return int
     */
    public function getDictionaryProductTypeId(): int
    {
        return (int)$this->getData('dictionary_product_type_id');
    }

    /**
     * @return int
     */
    public function getMarketplaceId(): int
    {
        return $this->getDictionary()->getMarketplaceId();
    }

    /**
     * @return string
     */
    public function getNick(): string
    {
        return $this->getDictionary()->getNick();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getDictionary()->getTitle();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
     */
    public function getDictionary(): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
        if ($this->dictionary === null) {
            $this->dictionary = $this->productTypeHelper->getProductTypeDictionaryById(
                $this->getDictionaryProductTypeId()
            );
        }

        return $this->dictionary;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCustomAttributesName(): array
    {
        $specifics = $this->getSettings('settings');
        $customAttributes = [];
        foreach ($specifics as $values) {
            foreach ($values as $value) {
                if (!isset($value['mode'])) {
                    continue;
                }

                if ((int)$value['mode'] === self::FIELD_CUSTOM_ATTRIBUTE) {
                    $customAttributes[] = $value['attribute_code'];
                }
            }
        }

        return array_unique($customAttributes);
    }

    public function getCustomAttributesList(): array
    {
        $result = [];
        foreach ($this->getCustomAttributes() as $attributeName => $values) {
            foreach ($values as $value) {
                if ((int)$value['mode'] !== self::FIELD_CUSTOM_ATTRIBUTE) {
                    continue;
                }

                $result[] = [
                    'name' => $attributeName,
                    'attribute_code' => $value['attribute_code']
                ];
            }
        }

        return $result;
    }

    private function getCustomAttributes(): array
    {
        $specifics = $this->getSettings('settings');
        $filterCallback = static function (array $values) {
            foreach ($values as $value) {
                if (!isset($value['mode'])) {
                    continue;
                }

                return (int)$value['mode'] === self::FIELD_CUSTOM_ATTRIBUTE;
            }

            return false;
        };

        return array_filter($specifics, $filterCallback);
    }

    public function getViewMode(): int
    {
        $viewMode = $this->getData('view_mode');
        if ($viewMode === null) {
            return self::VIEW_MODE_REQUIRED_ATTRIBUTES;
        }

        return (int)$viewMode;
    }
}
