<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $sellingFormatPromotionModel \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion
     */
    private $sellingFormatPromotionModel = null;

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
     * @param \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion $instance
     * @return $this
     */
    public function setSellingFormatPromotion(\Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion $instance)
    {
        $this->sellingFormatPromotionModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion
     */
    public function getSellingFormatPromotion()
    {
        return $this->sellingFormatPromotionModel;
    }

    //########################################

    public function getStartDate()
    {
        $result = null;

        switch ($this->getSellingFormatPromotion()->getStartDateMode()) {
            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::START_DATE_MODE_VALUE:
                $result = $this->getSellingFormatPromotion()->getStartDateValue();
                break;

            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::START_DATE_MODE_ATTRIBUTE:
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getSellingFormatPromotion()->getStartDateAttribute()
                );
                break;
        }

        return $result;
    }

    public function getEndDate()
    {
        $result = null;

        switch ($this->getSellingFormatPromotion()->getEndDateMode()) {
            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::END_DATE_MODE_VALUE:
                $result = $this->getSellingFormatPromotion()->getEndDateValue();
                break;

            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::END_DATE_MODE_ATTRIBUTE:
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getSellingFormatPromotion()->getEndDateAttribute()
                );
                break;
        }

        return $result;
    }

    //########################################
}
