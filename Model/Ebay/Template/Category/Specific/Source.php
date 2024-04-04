<?php

namespace Ess\M2ePro\Model\Ebay\Template\Category\Specific;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $config;
    /** @var \Ess\M2ePro\Model\Magento\Product */
    private $magentoProduct = null;
    /** @var  \Ess\M2ePro\Model\Ebay\Template\Category\Specific */
    private $categorySpecificTemplateModel = null;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;
    /** @var \Ess\M2ePro\Model\Ebay\Category\SpecificValidator */
    private $ebayCategorySpecificValidator;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Ebay\Category\SpecificValidator $ebayCategorySpecificValidator
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->ebayCategorySpecificValidator = $ebayCategorySpecificValidator;
    }

    //------------------------------

    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct): self
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    public function getMagentoProduct(): \Ess\M2ePro\Model\Magento\Product
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    public function setCategorySpecificTemplate(\Ess\M2ePro\Model\Ebay\Template\Category\Specific $instance): self
    {
        $this->categorySpecificTemplateModel = $instance;

        return $this;
    }

    public function getCategorySpecificTemplate(): \Ess\M2ePro\Model\Ebay\Template\Category\Specific
    {
        return $this->categorySpecificTemplateModel;
    }

    // ---------------------------------------

    public function getCategoryTemplate(): \Ess\M2ePro\Model\Ebay\Template\Category
    {
        return $this->getCategorySpecificTemplate()->getCategoryTemplate();
    }

    //------------------------------

    public function getLabel()
    {
        if (
            $this->getCategorySpecificTemplate()->isCustomItemSpecificsMode() &&
            $this->getCategorySpecificTemplate()->isCustomAttributeValueMode()
        ) {
            return $this->getAttributeLabel();
        }

        return $this->getCategorySpecificTemplate()->getData('attribute_title');
    }

    public function getValues()
    {
        $valueData = [];

        if ($this->getCategorySpecificTemplate()->isNoneValueMode()) {
            $valueData[] = '--';
        }

        if ($this->getCategorySpecificTemplate()->isEbayRecommendedValueMode()) {
            $valueData = \Ess\M2ePro\Helper\Json::decode(
                $this->getCategorySpecificTemplate()->getData('value_ebay_recommended')
            );
        }

        if ($this->getCategorySpecificTemplate()->isCustomValueValueMode()) {
            $valueData = \Ess\M2ePro\Helper\Json::decode(
                $this->getCategorySpecificTemplate()->getData('value_custom_value')
            );
        }

        if (
            !$this->getCategorySpecificTemplate()->isCustomAttributeValueMode() &&
            !$this->getCategorySpecificTemplate()->isCustomLabelAttributeValueMode()
        ) {
            return $valueData;
        }

        $attributeCode = $this->getCategorySpecificTemplate()->getData('value_custom_attribute');
        $valueTemp = $this->getAttributeValue($attributeCode);
        if (is_array($valueTemp) && !empty($valueTemp['found_in_children'])) {
            return $valueTemp;
        }

        $categoryId = $this->getCategoryTemplate()->getCategoryId();
        $marketplaceId = $this->getCategoryTemplate()->getMarketplaceId();

        if (
            empty($categoryId) || empty($marketplaceId) || strpos($valueTemp, ',') === false ||
            $this->getMagentoProduct()->getAttributeFrontendInput($attributeCode) !== 'multiselect'
        ) {
            $valueData[] = $valueTemp;

            return $valueData;
        }

        $specifics = $this->componentEbayCategoryEbay->getSpecifics($categoryId, $marketplaceId);

        if (empty($specifics)) {
            $valueData[] = $valueTemp;

            return $valueData;
        }

        foreach ($specifics as $specific) {
            if (
                $specific['title'] === $this->getCategorySpecificTemplate()->getData('attribute_title') &&
                in_array($specific['type'], ['select_multiple_or_text', 'select_multiple'])
            ) {
                foreach (explode(',', $valueTemp) as $val) {
                    $valueData[] = trim($val);
                }

                return $valueData;
            }
        }

        $valueData[] = $valueTemp;

        return $valueData;
    }

    //------------------------------

    private function getAttributeLabel()
    {
        return $this->getHelper('Magento\Attribute')->getAttributeLabel(
            $this->getCategorySpecificTemplate()->getData('value_custom_attribute'),
            $this->getMagentoProduct()->getStoreId()
        );
    }

    private function getAttributeValue(string $attributeCode)
    {
        $attributeValue = $this->getMagentoProduct()->getAttributeValue($attributeCode);
        if ($attributeCode == 'country_of_manufacture') {
            $locale = $this->config->getValue(
                \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore($this->getMagentoProduct()->getStoreId())->getCode()
            );

            if ($countryName = $this->getHelper('Magento')->getTranslatedCountryName($attributeValue, $locale)) {
                $attributeValue = $countryName;
            }
        }

        if (!$attributeValue) {
            $attributeValue = $this->ebayCategorySpecificValidator->checkForAttributeInChildren(
                $this->getMagentoProduct(),
                $attributeCode
            );

            if ($attributeValue) {
                $this->getMagentoProduct()->clearNotFoundAttributes();
                $attributeValue = [
                    'found_in_children' => true,
                    'value' => $attributeValue,
                    'code' => $attributeCode,
                ];
            }
        }

        return $attributeValue;
    }
}
