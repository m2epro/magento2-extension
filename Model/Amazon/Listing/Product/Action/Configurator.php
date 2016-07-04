<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action;

class Configurator extends \Ess\M2ePro\Model\Listing\Product\Action\Configurator
{
    const DATA_TYPE_QTY     = 'qty';
    const DATA_TYPE_PRICE   = 'price';
    const DATA_TYPE_IMAGES  = 'images';
    const DATA_TYPE_DETAILS = 'details';
    const DATA_TYPE_SHIPPING_OVERRIDE = 'shipping_override';

    //########################################

    /**
     * @return array
     */
    public function getAllDataTypes()
    {
        return array(
            self::DATA_TYPE_QTY,
            self::DATA_TYPE_PRICE,
            self::DATA_TYPE_DETAILS,
            self::DATA_TYPE_IMAGES,
            self::DATA_TYPE_SHIPPING_OVERRIDE
        );
    }

    //########################################

    /**
     * @return bool
     */
    public function isQtyAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_QTY);
    }

    /**
     * @return $this
     */
    public function allowQty()
    {
        return $this->allow(self::DATA_TYPE_QTY);
    }

    /**
     * @return $this
     */
    public function disallowQty()
    {
        return $this->disallow(self::DATA_TYPE_QTY);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isPriceAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_PRICE);
    }

    /**
     * @return $this
     */
    public function allowPrice()
    {
        return $this->allow(self::DATA_TYPE_PRICE);
    }

    /**
     * @return $this
     */
    public function disallowPrice()
    {
        return $this->disallow(self::DATA_TYPE_PRICE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isDetailsAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_DETAILS);
    }

    /**
     * @return $this
     */
    public function allowDetails()
    {
        return $this->allow(self::DATA_TYPE_DETAILS);
    }

    /**
     * @return $this
     */
    public function disallowDetails()
    {
        return $this->disallow(self::DATA_TYPE_DETAILS);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isImagesAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_IMAGES);
    }

    /**
     * @return $this
     */
    public function allowImages()
    {
        return $this->allow(self::DATA_TYPE_IMAGES);
    }

    /**
     * @return $this
     */
    public function disallowImages()
    {
        return $this->disallow(self::DATA_TYPE_IMAGES);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isShippingOverrideAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_SHIPPING_OVERRIDE);
    }

    /**
     * @return $this
     */
    public function allowShippingOverride()
    {
        return $this->allow(self::DATA_TYPE_SHIPPING_OVERRIDE);
    }

    /**
     * @return $this
     */
    public function disallowShippingOverride()
    {
        return $this->disallow(self::DATA_TYPE_SHIPPING_OVERRIDE);
    }

    //########################################
}