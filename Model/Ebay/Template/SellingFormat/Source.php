<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\SellingFormat\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $sellingTemplateModel \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     * @return $this
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
        $this->sellingTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->sellingTemplateModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\SellingFormat
     */
    public function getEbaySellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return string
     */
    public function getTaxCategory()
    {
        $src = $this->getEbaySellingFormatTemplate()->getTaxCategorySource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::TAX_CATEGORY_MODE_NONE) {
            return '';
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::TAX_CATEGORY_MODE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        $src = $this->getEbaySellingFormatTemplate()->getDurationSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::DURATION_TYPE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    /**
     * @return int
     */
    public function getListingType()
    {
        $src = $this->getEbaySellingFormatTemplate()->getListingTypeSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_ATTRIBUTE) {
            $ebayStringType = $this->getMagentoProduct()->getAttributeValue($src['attribute']);

            switch ($ebayStringType) {
                case \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_FIXED:
                    return \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED;
                case \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Selling::LISTING_TYPE_AUCTION:
                    return \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION;
            }

            return \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED;
        }

        return $src['mode'];
    }

    //########################################
}
