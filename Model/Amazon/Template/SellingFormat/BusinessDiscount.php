<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\SellingFormat;

class BusinessDiscount extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const MODE_PRODUCT   = 1;
    const MODE_SPECIAL   = 2;
    const MODE_ATTRIBUTE = 3;

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->sellingFormatTemplateModel = NULL;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if (is_null($this->sellingFormatTemplateModel)) {
            $this->sellingFormatTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Amazon\Template\SellingFormat', $this->getTemplateSellingFormatId()
            );
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
        $this->sellingFormatTemplateModel = $instance;
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateSellingFormatId()
    {
        return (int)$this->getData('template_selling_format_id');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getQty()
    {
        return (int)$this->getData('qty');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getMode()
    {
        return (int)$this->getData('mode');
    }

    /**
     * @return bool
     */
    public function isModeProduct()
    {
        return $this->getMode() == self::MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isModeSpecial()
    {
        return $this->getMode() == self::MODE_SPECIAL;
    }

    /**
     * @return bool
     */
    public function isModeAttribute()
    {
        return $this->getMode() == self::MODE_ATTRIBUTE;
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getAttribute()
    {
        return (string)$this->getData('attribute');
    }

    /**
     * @return string
     */
    public function getCoefficient()
    {
        return (string)$this->getData('coefficient');
    }

    //########################################

    public function getSource()
    {
        return array(
            'mode'        => $this->getMode(),
            'coefficient' => $this->getCoefficient(),
            'attribute'   => $this->getAttribute(),
        );
    }

    //########################################

    /**
     * @return array
     */
    public function getAttributes()
    {
        $attributes = array();

        if ($this->isModeAttribute()) {
            $attributes[] = $this->getAttribute();
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_unique(
            $this->getAttributes()
        );
    }

    //########################################
}