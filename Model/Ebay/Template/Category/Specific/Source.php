<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category\Specific;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $categorySpecificTemplateModel \Ess\M2ePro\Model\Ebay\Template\Category\Specific
     */
    private $categorySpecificTemplateModel = null;

    protected $storeManager;
    protected $config;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category\Specific $instance
     * @return $this
     */
    public function setCategorySpecificTemplate(\Ess\M2ePro\Model\Ebay\Template\Category\Specific $instance)
    {
        $this->categorySpecificTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Specific
     */
    public function getCategorySpecificTemplate()
    {
        return $this->categorySpecificTemplateModel;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategoryTemplate()
    {
        return $this->getCategorySpecificTemplate()->getCategoryTemplate();
    }

    //########################################

    public function getLabel()
    {
        if ($this->getCategorySpecificTemplate()->isCustomItemSpecificsMode() &&
            $this->getCategorySpecificTemplate()->isCustomAttributeValueMode()) {
            return $this->getAttributeLabel();
        }

        return $this->getCategorySpecificTemplate()->getData('attribute_title');
    }

    public function getValues()
    {
        $valueData = array();

        if ($this->getCategorySpecificTemplate()->isNoneValueMode()) {
            $valueData[] = '--';
        }

        if ($this->getCategorySpecificTemplate()->isEbayRecommendedValueMode()) {
            $valueData = $this->getHelper('Data')->jsonDecode(
                $this->getCategorySpecificTemplate()->getData('value_ebay_recommended')
            );
        }

        if ($this->getCategorySpecificTemplate()->isCustomValueValueMode()) {
            $valueData = $this->getHelper('Data')->jsonDecode(
                $this->getCategorySpecificTemplate()->getData('value_custom_value')
            );
        }

        if (!$this->getCategorySpecificTemplate()->isCustomAttributeValueMode() &&
            !$this->getCategorySpecificTemplate()->isCustomLabelAttributeValueMode()) {
            return $valueData;
        }

        $attributeCode = $this->getCategorySpecificTemplate()->getData('value_custom_attribute');
        $valueTemp = $this->getAttributeValue($attributeCode);

        $categoryId = $this->getCategoryTemplate()->getCategoryMainId();
        $marketplaceId = $this->getCategoryTemplate()->getMarketplaceId();

        if (empty($categoryId) || empty($marketplaceId) || strpos($valueTemp, ',') === false ||
            $this->getMagentoProduct()->getAttributeFrontendInput($attributeCode) !== 'multiselect') {

            $valueData[] = $valueTemp;
            return $valueData;
        }

        $specifics = $this->getHelper('Component\Ebay\Category\Ebay')
            ->getSpecifics($categoryId, $marketplaceId);

        if (empty($specifics)) {
            $valueData[] = $valueTemp;
            return $valueData;
        }

        foreach ($specifics as $specific) {

            if ($specific['title'] === $this->getCategorySpecificTemplate()->getData('attribute_title') &&
                in_array($specific['type'],array('select_multiple_or_text','select_multiple'))) {

                foreach (explode(',', $valueTemp) as $val) {
                    $valueData[] =  trim($val);
                }

                return $valueData;
            }
        }

        $valueData[] = $valueTemp;
        return $valueData;
    }

    //########################################

    private function getAttributeLabel()
    {
        return $this->getHelper('Magento\Attribute')->getAttributeLabel(
                    $this->getCategorySpecificTemplate()->getData('value_custom_attribute'),
                    $this->getMagentoProduct()->getStoreId()
                );
    }

    private function getAttributeValue($attributeCode)
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

        return $attributeValue;
    }

    //########################################
}