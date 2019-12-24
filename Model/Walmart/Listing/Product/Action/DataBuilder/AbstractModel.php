<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ss-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Listing\Product
     */
    protected $listingProduct = null;

    /**
     * @var array
     */
    protected $cachedData = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    private $warningMessages = [];

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    // ---------------------------------------

    /**
     * @param array $data
     * @return $this
     */
    public function setCachedData(array $data)
    {
        $this->cachedData = $data;
        return $this;
    }

    // ---------------------------------------

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params = [])
    {
        $this->params = $params;
        return $this;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getWalmartAccount()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    protected function getWalmartMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getListing()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    protected function getWalmartAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    protected function getListing()
    {
        return $this->getListingProduct()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing
     */
    protected function getWalmartListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product
     */
    protected function getWalmartListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    protected function getActualMagentoProduct()
    {
        return $this->getWalmartListingProduct()->getActualMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    protected function getMagentoProduct()
    {
        return $this->getListingProduct()->getMagentoProduct();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager
     */
    protected function getVariationManager()
    {
        return $this->getWalmartListingProduct()->getVariationManager();
    }

    //########################################

    protected function searchNotFoundAttributes()
    {
        $this->getMagentoProduct()->clearNotFoundAttributes();
    }

    protected function processNotFoundAttributes($title)
    {
        $attributes = $this->getMagentoProduct()->getNotFoundAttributes();

        if (empty($attributes)) {
            return true;
        }

        $this->addNotFoundAttributesMessages($title, $attributes);

        return false;
    }

    // ---------------------------------------

    protected function addNotFoundAttributesMessages($title, array $attributes)
    {
        $attributesTitles = [];

        foreach ($attributes as $attribute) {
            $attributesTitles[] = $this->getHelper('Magento\Attribute')
                ->getAttributeLabel(
                    $attribute,
                    $this->getListing()->getStoreId()
                );
        }
        // M2ePro\TRANSLATIONS
        // %attribute_title%: Attribute(s) %attributes% were not found in this Product and its value was not sent.
        $this->addWarningMessage(
            $this->getHelper('Module\Translation')->__(
                '%attribute_title%: Attribute(s) %attributes% were not found' .
                ' in this Product and its value was not sent.',
                $this->getHelper('Module\Translation')->__($title),
                implode(',', $attributesTitles)
            )
        );
    }

    //########################################

    protected function addWarningMessage($message)
    {
        $this->warningMessages[sha1($message)] = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getWarningMessages()
    {
        return $this->warningMessages;
    }

    //########################################

    /**
     * @return array
     */
    abstract public function getRequestData();

    //########################################
}
