<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

use Ess\M2ePro\Model\Walmart\Template\SellingFormat as WalmartSellingFormat;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $sellingFormatTemplateModel \Ess\M2ePro\Model\Template\Sellingformat
     */
    private $sellingFormatTemplateModel = null;

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
        $this->sellingFormatTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->sellingFormatTemplateModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getWalmartSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    //########################################

    public function getLagTime()
    {
        $result = 0;
        $src = $this->getWalmartSellingFormatTemplate()->getLagTimeSource();

        if ($src['mode'] == WalmartSellingFormat::LAG_TIME_MODE_RECOMMENDED) {
            $result = $src['value'];
        }

        if ($src['mode'] == WalmartSellingFormat::LAG_TIME_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        $result = (int)$result;
        $result < 0 && $result = 0;

        return $result;
    }

    public function getItemWeight()
    {
        $result = 0;
        $src = $this->getWalmartSellingFormatTemplate()->getItemWeightSource();

        if ($src['mode'] == WalmartSellingFormat::WEIGHT_MODE_CUSTOM_VALUE) {
            $result = $src['custom_value'];
        }

        if ($src['mode'] == WalmartSellingFormat::WEIGHT_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['custom_attribute']);
        }

        $result < 0 && $result = 0;

        return $result;
    }

    public function getProductTaxCode()
    {
        $result = '';
        $src = $this->getWalmartSellingFormatTemplate()->getProductTaxCodeSource();

        if ($src['mode'] == WalmartSellingFormat::PRODUCT_TAX_CODE_MODE_VALUE) {
            $result = $src['value'];
        }

        if ($src['mode'] == WalmartSellingFormat::PRODUCT_TAX_CODE_MODE_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $result;
    }

    public function getMustShipAlone()
    {
        $result = null;
        $src = $this->getWalmartSellingFormatTemplate()->getMustShipAloneSource();

        if ($src['mode'] == WalmartSellingFormat::MUST_SHIP_ALONE_MODE_YES) {
            $result = true;
        }

        if ($src['mode'] == WalmartSellingFormat::MUST_SHIP_ALONE_MODE_NO) {
            $result = false;
        }

        if ($src['mode'] == WalmartSellingFormat::MUST_SHIP_ALONE_MODE_CUSTOM_ATTRIBUTE) {
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($src['attribute']);

            if ($attributeValue == $this->helperFactory->getObject('Module\Translation')->__('Yes')) {
                $result = true;
            }

            if ($attributeValue == $this->helperFactory->getObject('Module\Translation')->__('No')) {
                $result = false;
            }
        }

        return $result;
    }

    public function getShipsInOriginalPackaging()
    {
        $result = null;
        $src = $this->getWalmartSellingFormatTemplate()->getShipsInOriginalPackagingModeSource();

        if ($src['mode'] == WalmartSellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_YES) {
            $result = true;
        }

        if ($src['mode'] == WalmartSellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NO) {
            $result = false;
        }

        if ($src['mode'] == WalmartSellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_CUSTOM_ATTRIBUTE) {
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($src['attribute']);

            if ($attributeValue == $this->helperFactory->getObject('Module\Translation')->__('Yes')) {
                $result = true;
            }

            if ($attributeValue == $this->helperFactory->getObject('Module\Translation')->__('No')) {
                $result = false;
            }
        }

        return $result;
    }

    public function getStartDate()
    {
        $result = null;
        $src = $this->getWalmartSellingFormatTemplate()->getSaleTimeStartDateSource();

        if ($src['mode'] == WalmartSellingFormat::DATE_VALUE) {
            $result = $src['value'];
        }

        if ($src['mode'] == WalmartSellingFormat::DATE_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $result;
    }

    public function getEndDate()
    {
        $result = null;
        $src = $this->getWalmartSellingFormatTemplate()->getSaleTimeEndDateSource();

        if ($src['mode'] == WalmartSellingFormat::DATE_VALUE) {
            $result = $src['value'];
        }

        if ($src['mode'] == WalmartSellingFormat::DATE_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if ($this->getWalmartSellingFormatTemplate()->isAttributesModeNone()) {
            return [];
        }

        $result = [];
        $src = $this->getWalmartSellingFormatTemplate()->getAttributesSource();

        foreach ($src['template'] as $value) {
            if (empty($value)) {
                continue;
            }

            $result[$value['name']] = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                $value['value'],
                $this->getMagentoProduct()
            );
        }

        return $result;
    }

    //########################################
}
